<?php

namespace App\Services;

use App\Models\BackdateExcelFile;
use App\Models\DailyReport;
use App\Models\Operator;
use App\Models\Price;
use App\Models\Shop;
use App\Models\Spending;
use App\Models\SpendingCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BackdateDailyReportIngestionService
{
    /**
     * Memproses dan memetakan 1 file Excel Backdate ke baris-baris tabel daily_reports.
     *
     * @param BackdateExcelFile $backdateFile
     * @return int Jumlah baris daily_reports yang berhasil diparsing dan disimpan
     */
    public static function ingest(BackdateExcelFile $backdateFile): int
    {
        $shop = $backdateFile->shop ?? Shop::find($backdateFile->shop_id);
        if (!$shop) {
            Log::error("BackdateDailyReportIngestionService: Shop ID {$backdateFile->shop_id} tidak ditemukan.");
            return 0;
        }

        $fullPath = self::resolvePhysicalPath($backdateFile->file_path);
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            Log::error("BackdateDailyReportIngestionService: File fisik tidak ditemukan di [{$fullPath}].");
            return 0;
        }

        try {
            $spreadsheet = IOFactory::load($fullPath);
        } catch (\Throwable $e) {
            Log::error("BackdateDailyReportIngestionService: Gagal memuat spreadsheet: " . $e->getMessage());
            return 0;
        }

        // Cari lembar kerja (worksheet) harian yang sesuai
        $dailySheet = self::findDailySheet($spreadsheet, $shop, $backdateFile->bulan_tahun);
        if (!$dailySheet) {
            Log::warning("BackdateDailyReportIngestionService: Lembar kerja harian tidak terdeteksi untuk file [{$backdateFile->original_filename}] toko [{$shop->nama}].");
            return 0;
        }

        // Ekstraksi data harian per tanggal dari sheet
        $dailyData = self::parseWorksheetRows($dailySheet, $shop, $backdateFile->bulan_tahun);
        if (empty($dailyData)) {
            Log::warning("BackdateDailyReportIngestionService: Tidak ada baris data harian yang valid pada sheet [{$dailySheet->getTitle()}].");
            return 0;
        }

        // Simpan baris-baris data ke database
        $savedCount = self::saveDailyReportsToDatabase($dailyData, $shop, $backdateFile);

        // Update saldo berjalan setoran kumulatif
        self::recalculateRunningBalance($shop->id);

        Log::info("BackdateDailyReportIngestionService: Berhasil mengimpor {$savedCount} baris laporan harian untuk toko {$shop->nama} dari file [{$backdateFile->original_filename}].");

        return $savedCount;
    }

    /**
     * Cari lembar kerja harian yang paling cocok berdasarkan alias toko dan periode
     */
    protected static function findDailySheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, Shop $shop, ?string $period): ?\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $aliases = BackdateExcelSummaryService::getShopAliases($shop);
        $periodObj = null;
        if (!empty($period) && $period !== 'Multi-Periode') {
            try {
                $periodObj = Carbon::parse($period . '-01');
            } catch (\Throwable $e) {}
        }

        $monthNames = [
            1 => ['jan', 'januari'], 2 => ['feb', 'februari'], 3 => ['mar', 'maret'],
            4 => ['apr', 'april'],   5 => ['mei', 'may'],       6 => ['jun', 'juni'],
            7 => ['jul', 'juli'],    8 => ['ags', 'agustus', 'agu'], 9 => ['sep', 'sept', 'september'],
            10 => ['okt', 'oktober'], 11 => ['nov', 'november'], 12 => ['des', 'desember'],
        ];
        $validMonths = ($periodObj && isset($monthNames[$periodObj->month])) ? $monthNames[$periodObj->month] : [];

        // 1. Cek kecocokan kode Pertamina spesifik
        $kodeClean = str_replace(['.', ' ', '-', '_'], '', strtolower($shop->kode ?? ''));
        foreach ($spreadsheet->getAllSheets() as $sh) {
            $tLow = strtolower($sh->getTitle());
            if (str_contains($tLow, 'gaji') || str_contains($tLow, 'qris') || str_contains($tLow, 'yusuf')) continue;

            if (!empty($kodeClean) && str_contains(str_replace(['.', ' ', '-', '_'], '', $tLow), $kodeClean)) {
                return $sh;
            }
        }

        // 2. Cek kecocokan alias nama toko
        foreach ($spreadsheet->getAllSheets() as $sh) {
            $tLow = strtolower($sh->getTitle());
            if (str_contains($tLow, 'gaji') || str_contains($tLow, 'qris') || str_contains($tLow, 'yusuf')) continue;

            foreach ($aliases as $al) {
                if (strlen($al) >= 3 && str_contains($tLow, strtolower($al))) {
                    return $sh;
                }
            }
        }

        // 3. Cek nama sheet berdasarkan bulan periode (misal: "Juni 26", "REKAP KLT 01-30 Juni 2026")
        if (!empty($validMonths)) {
            foreach ($spreadsheet->getAllSheets() as $sh) {
                $tLow = strtolower($sh->getTitle());
                if (str_contains($tLow, 'gaji') || str_contains($tLow, 'qris') || str_contains($tLow, 'yusuf')) continue;

                foreach ($validMonths as $vm) {
                    if (str_contains($tLow, $vm)) {
                        return $sh;
                    }
                }
            }
        }

        // 4. Cek sheet yang diawali dengan kata "rekap" atau "laporan" atau "penjualan"
        foreach ($spreadsheet->getAllSheets() as $sh) {
            $tLow = strtolower($sh->getTitle());
            if (str_contains($tLow, 'gaji') || str_contains($tLow, 'qris') || str_contains($tLow, 'modal')) continue;

            if (str_contains($tLow, 'rekap') || str_contains($tLow, 'penjualan') || str_contains($tLow, 'daily')) {
                return $sh;
            }
        }

        // Fallback: sheet pertama
        return $spreadsheet->getSheet(0);
    }

    /**
     * Membaca baris demi baris lembar kerja dan mengagregasi per tanggal operasional.
     *
     * PERBAIKAN KRITIS:
     * 1. headerEndRow hanya dari baris teks header murni (bukan formula/angka baris data)
     * 2. dataStartRow dicari secara dinamis dari baris pertama yang punya serial date valid
     * 3. Kolom tanggal menggunakan serial date Excel (bukan angka hari 1-31)
     * 4. Kolom pengeluaran menggunakan "Total Pengeluaran" (AG), bukan "Biaya Transfer" (Z)
     * 5. Kolom BBM datang menggunakan "Terima BBM" (P), bukan yang tidak ada
     * 6. Kolom stik_awal dari N (STOK AWAL), inisialisasi dari baris 3
     */
    protected static function parseWorksheetRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, Shop $shop, ?string $periodStr): array
    {
        $highestRow = min($sheet->getHighestRow(), 150);
        $highestColIndex = min(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn()),
            50
        );

        // -----------------------------------------------------------------------
        // STEP 1: Kumpulkan label TEKS (bukan angka/formula) dari baris 1 dan 2
        // Gabungkan label baris 1 & 2 per kolom (karena header bertingkat/merged)
        // -----------------------------------------------------------------------
        $headerLabels = []; // $headerLabels[$colIndex] = string gabungan label baris 1 & 2

        for ($r = 1; $r <= min(2, $highestRow); $r++) {
            for ($c = 1; $c <= $highestColIndex; $c++) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
                $rawVal = $sheet->getCell($coord)->getValue();

                // HANYA proses jika raw value adalah STRING (bukan formula, bukan angka)
                if (!is_string($rawVal) || empty(trim($rawVal))) continue;

                $label = strtolower(trim($rawVal));
                if (!isset($headerLabels[$c])) {
                    $headerLabels[$c] = $label;
                } else {
                    $headerLabels[$c] .= ' ' . $label; // Gabung baris 1 & 2
                }
            }
        }

        // -----------------------------------------------------------------------
        // STEP 2: Deteksi kolom dari label gabungan
        // -----------------------------------------------------------------------
        $colMap = [
            'tanggal_serial'    => null, // Kolom C: Excel serial date (46082 = 2026-03-01)
            'operator'          => null,
            'tot_awal'          => null, // D
            'tot_akhir'         => null, // E
            'vol_jual'          => null, // F: Toritis Penjualan Volume
            'rp_jual'           => null, // G: F × harga_jual (dari Excel langsung)
            'test_pump'         => null, // H
            'bbm_datang'        => null, // P: Terima BBM (ℓ)
            'bbm_beli'          => null, // O: Pembelian BBM (ℓ)
            'stik_awal'         => null, // N: STOK AWAL (ℓ)
            'stik_akhir'        => null, // R: STOK AKTUAL AKHIR (CM)
            'stok_akhir'        => null, // W: STOK AKHIR (ℓ)
            'total_pengeluaran' => null, // AG: Total Pengeluaran
            'pengeluaran'       => null, // Fallback pengeluaran
            'ket_pengeluaran'   => null,
            'setor_tunai'       => null, // AL sub-BRI
            'setor_qris'        => null, // AM: QRIS BRI
            'setor_tf'          => null, // AN: Transfer by Operator
            'disetorkan'        => null, // AL: JUMLAH DISETORKAN
        ];

        foreach ($headerLabels as $c => $label) {
            // Tanggal (kolom C: "Tanggal" atau "Tgl")
            if (preg_match('/\btanggal\b|\btgl\b/', $label) && $colMap['tanggal_serial'] === null) {
                $colMap['tanggal_serial'] = $c;
            }

            // Totalisator
            if (str_contains($label, 'totalisator awal')) {
                $colMap['tot_awal'] = $c;
            } elseif (str_contains($label, 'totalisator akhir')) {
                $colMap['tot_akhir'] = $c;
            }

            // Volume & Rupiah penjualan
            if ((str_contains($label, 'toritis') || str_contains($label, 'penjualan liter')) && $colMap['vol_jual'] === null) {
                $colMap['vol_jual'] = $c;
            } elseif (str_contains($label, 'aktual penjualan') && str_contains($label, 'volume') && $colMap['vol_jual'] === null) {
                $colMap['vol_jual'] = $c;
            }

            // Test Pump
            if (str_contains($label, 'test pump') && $colMap['test_pump'] === null) {
                $colMap['test_pump'] = $c;
            }

            // BBM: Terima (P) vs Pembelian (O)
            // "terima bbm (ℓ)" = P = BBM yang DITERIMA/MASUK ke tangki
            if ((str_contains($label, 'terima') && str_contains($label, 'bbm')) && $colMap['bbm_datang'] === null) {
                $colMap['bbm_datang'] = $c;
            } elseif ((str_contains($label, 'bbm datang') || str_contains($label, 'curah') || str_contains($label, 'penerimaan bbm')) && $colMap['bbm_datang'] === null) {
                $colMap['bbm_datang'] = $c;
            } elseif ((str_contains($label, 'pembelian') || str_contains($label, 'beli')) && str_contains($label, 'bbm') && $colMap['bbm_beli'] === null) {
                $colMap['bbm_beli'] = $c;
            }

            // Stok Awal (N: "stok awal (ℓ)")
            if ((str_contains($label, 'stok') || str_contains($label, 'stik')) && str_contains($label, 'awal') && $colMap['stik_awal'] === null) {
                $colMap['stik_awal'] = $c;
            }

            // Stik/Stok Akhir
            if (str_contains($label, 'stok aktual akhir') && $colMap['stik_akhir'] === null) {
                $colMap['stik_akhir'] = $c; // R = CM stik aktual
            } elseif ((str_contains($label, 'stok') || str_contains($label, 'stik')) && str_contains($label, 'akhir') && !str_contains($label, 'teoritis')) {
                if (str_contains($label, 'volume') || str_contains($label, 'ℓ') || str_contains($label, 'liter')) {
                    if ($colMap['stok_akhir'] === null) $colMap['stok_akhir'] = $c; // W = volume ℓ
                } elseif ($colMap['stik_akhir'] === null) {
                    $colMap['stik_akhir'] = $c;
                }
            }

            // Total Pengeluaran (AG: "total pengeluaran") — HARUS dibedakan dari "biaya transfer"
            if (str_contains($label, 'total pengeluaran') && $colMap['total_pengeluaran'] === null) {
                $colMap['total_pengeluaran'] = $c;
            } elseif (str_contains($label, 'pengeluaran') && !str_contains($label, 'total') && !str_contains($label, 'dikurangi') && !str_contains($label, 'selisih') && !str_contains($label, 'pendapatan') && $colMap['pengeluaran'] === null) {
                $colMap['pengeluaran'] = $c;
            }

            // Keterangan pengeluaran
            if ((str_contains($label, 'keterangan lain') || $label === 'keterangan') && $colMap['ket_pengeluaran'] === null) {
                $colMap['ket_pengeluaran'] = $c;
            }

            // Jumlah Disetorkan (AL header "JUMLAH DISETORKAN")
            if (str_contains($label, 'jumlah disetorkan') || str_contains($label, 'total disetorkan')) {
                $colMap['disetorkan'] = $c;
            }

            // BRI Tunai (sub-header "BRI" di bawah JUMLAH DISETORKAN)
            if ($label === 'bri' && $colMap['setor_tunai'] === null) {
                $colMap['setor_tunai'] = $c;
            }

            // QRIS (AM: "QRIS BRI")
            if (str_contains($label, 'qris') && $colMap['setor_qris'] === null) {
                $colMap['setor_qris'] = $c;
            }

            // Transfer by Operator (AN)
            if ((str_contains($label, 'transfer by operator') || (str_contains($label, 'transfer') && str_contains($label, 'operator'))) && $colMap['setor_tf'] === null) {
                $colMap['setor_tf'] = $c;
            } elseif ((str_contains($label, 'ke bank') || str_contains($label, 'yusuf')) && $colMap['setor_tf'] === null) {
                $colMap['setor_tf'] = $c;
            }

            // Operator / Shift
            if ((str_contains($label, 'operator') || str_contains($label, 'shift') || $label === 'nama') && $colMap['operator'] === null) {
                $colMap['operator'] = $c;
            }
        }

        // Rupiah penjualan: kolom G = F+1 (tepat setelah vol_jual toritis)
        if ($colMap['vol_jual'] !== null) {
            $colMap['rp_jual'] = $colMap['vol_jual'] + 1;
        }

        // Default fallback berdasarkan struktur template standar KMT/KLT/dll
        if ($colMap['tanggal_serial'] === null) $colMap['tanggal_serial'] = 3;  // C
        if ($colMap['tot_awal']       === null) $colMap['tot_awal']       = 4;  // D
        if ($colMap['tot_akhir']      === null) $colMap['tot_akhir']      = 5;  // E
        if ($colMap['vol_jual']       === null) $colMap['vol_jual']       = 6;  // F
        if ($colMap['rp_jual']        === null) $colMap['rp_jual']        = 7;  // G
        if ($colMap['test_pump']      === null) $colMap['test_pump']      = 8;  // H
        if ($colMap['stik_awal']      === null) $colMap['stik_awal']      = 14; // N
        if ($colMap['stik_akhir']     === null) $colMap['stik_akhir']     = 18; // R
        // bbm_datang default ke P (16) = "Terima BBM"
        $efektifBbmDatangCol    = $colMap['bbm_datang'] ?? $colMap['bbm_beli'] ?? 16;
        // Pengeluaran default ke AG (33) = "Total Pengeluaran"
        $efektifPengeluaranCol  = $colMap['total_pengeluaran'] ?? $colMap['pengeluaran'];

        // -----------------------------------------------------------------------
        // STEP 3: Temukan baris data pertama secara dinamis
        // Baris data = baris yang punya serial date valid di kolom tanggal_serial
        // Struktur: Row 1 = Header 1, Row 2 = Header 2, Row 3 = Inisialisasi stok, Row 4+ = Data
        // -----------------------------------------------------------------------
        $dataStartRow = 4; // Default template
        for ($r = 3; $r <= min(8, $highestRow); $r++) {
            $tglCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMap['tanggal_serial']) . $r;
            $tglVal   = $sheet->getCell($tglCoord)->getCalculatedValue();

            if (is_numeric($tglVal) && $tglVal > 30000 && $tglVal < 70000) {
                // Ini adalah serial date — baris data valid
                $dataStartRow = $r;
                break;
            }
            if (is_numeric($tglVal) && $tglVal >= 1 && $tglVal <= 31) {
                // Angka hari — cek apakah totalisator juga ada (bukan baris inisialisasi)
                $totCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMap['tot_akhir']) . $r;
                $totVal   = $sheet->getCell($totCoord)->getCalculatedValue();
                if (is_numeric($totVal) && $totVal > 10000) {
                    $dataStartRow = $r;
                    break;
                }
            }
        }

        // Ekstrak stok_awal inisialisasi dari baris 3 (sebelum data pertama), kolom stik_awal (N)
        $initStokAwal = 0.0;
        if ($colMap['stik_awal'] !== null && $dataStartRow > 3) {
            $initCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMap['stik_awal']) . 3;
            $initVal   = $sheet->getCell($initCoord)->getCalculatedValue();
            if (is_numeric($initVal) && $initVal > 0) {
                $initStokAwal = (float) $initVal;
            }
        }

        // -----------------------------------------------------------------------
        // STEP 4: Parse baris data (dataStartRow s/d akhir)
        // -----------------------------------------------------------------------
        $basePeriod = (!empty($periodStr) && $periodStr !== 'Multi-Periode')
            ? Carbon::parse($periodStr . '-01')
            : Carbon::now();

        $rowsPerDay    = [];
        $lastKnownDate = null;

        for ($r = $dataStartRow; $r <= $highestRow; $r++) {
            // Ambil serial date dari kolom C
            $tglCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMap['tanggal_serial']) . $r;
            $rawDate  = $sheet->getCell($tglCoord)->getCalculatedValue();

            // Validasi totalisator
            $totAwalCoord  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMap['tot_awal'])  . $r;
            $totAkhirCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMap['tot_akhir']) . $r;
            $totAwalVal    = BackdateExcelSummaryService::cleanNumeric($sheet->getCell($totAwalCoord)->getCalculatedValue());
            $totAkhirVal   = BackdateExcelSummaryService::cleanNumeric($sheet->getCell($totAkhirCoord)->getCalculatedValue());

            // Skip baris kosong
            if ($totAwalVal <= 0 && $totAkhirVal <= 0 && (empty($rawDate) || $rawDate === 0)) {
                continue;
            }

            // Parse tanggal operasional
            $parsedDate = self::resolveDateString($rawDate, $basePeriod, $lastKnownDate);
            if (!$parsedDate) {
                if ($lastKnownDate && ($totAwalVal > 0 || $totAkhirVal > 0)) {
                    $parsedDate = $lastKnownDate; // Shift lanjutan hari yang sama
                } else {
                    continue;
                }
            } else {
                $lastKnownDate = $parsedDate;
            }

            // Helper closure untuk baca nilai numerik
            $getNum = function (int $col) use ($sheet, $r): float {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $r;
                return BackdateExcelSummaryService::cleanNumeric($sheet->getCell($coord)->getCalculatedValue());
            };

            // Ambil operator
            $opName = '';
            if ($colMap['operator']) {
                $opCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMap['operator']) . $r;
                $opVal   = $sheet->getCell($opCoord)->getCalculatedValue();
                if (is_string($opVal) && !is_numeric($opVal)) $opName = trim($opVal);
            }

            // Metrik utama
            $volJual    = $getNum($colMap['vol_jual']);
            $rpJual     = $getNum($colMap['rp_jual']);
            $testPump   = $getNum($colMap['test_pump']);
            $bbmDatang  = $getNum($efektifBbmDatangCol);
            $stikAkhir  = $getNum($colMap['stik_akhir']);

            // Stik Awal dari kolom N (formula =N(prev_row) atau nilai langsung)
            $stikAwal = 0.0;
            if ($colMap['stik_awal'] !== null) {
                $sv = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMap['stik_awal']) . $r)->getCalculatedValue();
                if (is_numeric($sv) && $sv > 0) $stikAwal = (float) $sv;
            }

            // Stok Akhir volume (ℓ) dari kolom W
            $stokAkhir = ($colMap['stok_akhir'] !== null) ? $getNum($colMap['stok_akhir']) : 0.0;

            // Pengeluaran: dari "Total Pengeluaran" (AG)
            $pengeluaran = ($efektifPengeluaranCol !== null) ? $getNum($efektifPengeluaranCol) : 0.0;

            $ketPengeluaran = '';
            if ($colMap['ket_pengeluaran']) {
                $ketPengeluaran = trim((string) $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colMap['ket_pengeluaran']) . $r)->getCalculatedValue());
            }

            // Setoran
            $setorTunai = ($colMap['setor_tunai'] !== null) ? $getNum($colMap['setor_tunai']) : 0.0;
            $setorQris  = ($colMap['setor_qris']  !== null) ? $getNum($colMap['setor_qris'])  : 0.0;
            $setorTf    = ($colMap['setor_tf']     !== null) ? $getNum($colMap['setor_tf'])    : 0.0;
            $disetorkan = ($colMap['disetorkan']   !== null) ? $getNum($colMap['disetorkan'])  : 0.0;

            // Hitung tunai dari disetorkan total jika sub-tunai belum diset
            if ($setorTunai <= 0 && $disetorkan > 0) {
                $setorTunai = max(0.0, $disetorkan - $setorQris - $setorTf);
            }

            $rowsPerDay[$parsedDate][] = [
                'row_index'       => $r,
                'operator_name'   => $opName,
                'tot_awal'        => $totAwalVal,
                'tot_akhir'       => $totAkhirVal,
                'vol_jual'        => $volJual,
                'rp_jual'         => $rpJual,
                'test_pump'       => $testPump,
                'bbm_datang'      => $bbmDatang,
                'stik_awal'       => $stikAwal,
                'stik_akhir'      => $stikAkhir,
                'stok_awal'       => $stikAwal, // Kolom N = STOK AWAL = stik_awal
                'stok_akhir'      => $stokAkhir,
                'pengeluaran'     => $pengeluaran,
                'ket_pengeluaran' => $ketPengeluaran,
                'setor_tunai'     => $setorTunai,
                'setor_qris'      => $setorQris,
                'setor_tf'        => $setorTf,
                'disetorkan'      => $disetorkan,
            ];
        }

        // -----------------------------------------------------------------------
        // STEP 5: Agregasi shift → 1 laporan harian per tanggal
        // -----------------------------------------------------------------------
        $aggregatedDays = [];
        ksort($rowsPerDay);

        foreach ($rowsPerDay as $date => $shifts) {
            $validAwalShifts  = array_filter($shifts, fn($s) => $s['tot_awal']  > 0);
            $validAkhirShifts = array_filter($shifts, fn($s) => $s['tot_akhir'] > 0);

            $firstShift = reset($shifts);
            $lastShift  = end($shifts);

            $totAwal  = !empty($validAwalShifts)  ? reset($validAwalShifts)['tot_awal']  : $firstShift['tot_awal'];
            $totAkhir = !empty($validAkhirShifts) ? end($validAkhirShifts)['tot_akhir']  : $lastShift['tot_akhir'];

            // Stik Akhir: shift terakhir yang punya nilai > 0
            $stikAkhir = 0;
            for ($i = count($shifts) - 1; $i >= 0; $i--) {
                if ($shifts[$i]['stik_akhir'] > 0) { $stikAkhir = $shifts[$i]['stik_akhir']; break; }
            }

            // Stik Awal: shift pertama yang punya nilai > 0
            $stikAwal = 0;
            foreach ($shifts as $s) {
                if ($s['stik_awal'] > 0) { $stikAwal = $s['stik_awal']; break; }
            }

            // Stok Awal: dari kolom N hari itu, atau fallback ke inisialisasi baris 3
            $stokAwal = $firstShift['stok_awal'] > 0 ? $firstShift['stok_awal'] : $initStokAwal;

            $sumTestPump    = array_sum(array_column($shifts, 'test_pump'));
            $sumBbmDatang   = array_sum(array_column($shifts, 'bbm_datang'));
            $sumPengeluaran = array_sum(array_column($shifts, 'pengeluaran'));
            $sumSetorTunai  = array_sum(array_column($shifts, 'setor_tunai'));
            $sumSetorQris   = array_sum(array_column($shifts, 'setor_qris'));
            $sumSetorTf     = array_sum(array_column($shifts, 'setor_tf'));
            $sumDisetorkan  = array_sum(array_column($shifts, 'disetorkan'));

            // Rincian spending
            $spendings = [];
            foreach ($shifts as $s) {
                if ($s['pengeluaran'] > 0) {
                    $spendings[] = [
                        'jumlah'     => $s['pengeluaran'],
                        'keterangan' => $s['ket_pengeluaran'] ?: 'Pengeluaran Operasional ' . Carbon::parse($date)->format('d/m/Y'),
                    ];
                }
            }

            $operatorNames = array_filter(array_column($shifts, 'operator_name'));
            $primaryOpName = !empty($operatorNames) ? reset($operatorNames) : '';

            // Rupiah & Volume Penjualan:
            // Template ini 1 baris = 1 hari, jadi ambil langsung dari baris pertama (bukan sum)
            // Untuk multi-shift (Gumelar), sum digunakan karena tiap shift = sebagian hari
            if (count($shifts) === 1) {
                $rpJual  = $firstShift['rp_jual'];
                $volJual = $firstShift['vol_jual'];
            } else {
                // Multi-shift: totalisasi — tapi jika F = E-D (formula), max lebih aman dari sum
                $rpJual  = array_sum(array_column($shifts, 'rp_jual'));
                $volJual = array_sum(array_column($shifts, 'vol_jual'));
            }

            $aggregatedDays[$date] = [
                'tanggal'           => $date,
                'operator_name'     => $primaryOpName,
                'totalisator_awal'  => $totAwal,
                'totalisator_akhir' => $totAkhir,
                'stik_awal'         => $stikAwal,
                'stik_akhir'        => $stikAkhir,
                'stok_awal'         => $stokAwal,
                'vol_jual'          => $volJual,
                'rp_jual'           => $rpJual,
                'test_pump_volume'  => $sumTestPump,
                'penerimaan_volume' => $sumBbmDatang,
                'pengeluaran'       => $sumPengeluaran,
                'spendings'         => $spendings,
                'setor_tunai'       => $sumSetorTunai,
                'setor_qris'        => $sumSetorQris,
                'setor_transfer'    => $sumSetorTf,
                'disetorkan'        => $sumDisetorkan > 0 ? $sumDisetorkan : ($sumSetorTunai + $sumSetorQris + $sumSetorTf),
            ];
        }

        return $aggregatedDays;
    }

    /**
     * Konversi nilai tanggal Excel ke format 'YYYY-MM-DD'
     * Mendukung: Excel serial date (46082), angka hari 1-31, string tanggal
     */
    protected static function resolveDateString($val, Carbon $basePeriod, ?string $lastKnownDate): ?string
    {
        if (empty($val) || $val === '-' || $val === '#N/A' || $val === '#VALUE!' || $val === 0) {
            return null;
        }

        // 1. Excel Serial Date (misal: 46082 → 2026-03-01)
        if (is_numeric($val) && $val > 30000 && $val < 70000) {
            try {
                return ExcelDate::excelToDateTimeObject((int) $val)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        // 2. Angka Hari dalam Bulan (1 s/d 31)
        if (is_numeric($val) && $val >= 1 && $val <= 31) {
            $day = (int) $val;
            try {
                return $basePeriod->copy()->day($day)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        // 3. String Tanggal (misal: '2026-06-01', '01/06/2026', '1 Juni 2026')
        if (is_string($val)) {
            $val = trim($val);
            if (empty($val) || $val === '-') return null;
            try {
                return Carbon::parse($val)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        return null;
    }

    /**
     * Menyimpan baris-baris harian hasil ekstraksi ke tabel daily_reports & spendings
     */
    protected static function saveDailyReportsToDatabase(array $dailyData, Shop $shop, BackdateExcelFile $backdateFile): int
    {
        $savedCount = 0;

        // Ambil daftar operator toko untuk pencocokan nama
        $shopOperators   = Operator::where('shop_id', $shop->id)->with('user')->get();
        $defaultOperator = $shopOperators->first() ?? Operator::first();

        // Ambil semua harga BBM (terbaru pertama) untuk mencocokkan harga yang berlaku
        $prices = Price::where('shop_id', $shop->id)
            ->orWhereNull('shop_id')
            ->orderBy('effective_at', 'desc')
            ->get();
        $fallbackPrice = $prices->first() ?? Price::first();

        // Kategori spending umum
        $spendingCat = SpendingCategory::firstOrCreate(
            ['nama' => 'Biaya Operasional'],
            ['id'   => 99]
        );

        foreach ($dailyData as $date => $row) {
            $reportDateTime = $date . ' 17:00:00';

            // Cocokkan Operator
            $matchedOperator = $defaultOperator;
            if (!empty($row['operator_name'])) {
                $searchName = strtolower($row['operator_name']);
                foreach ($shopOperators as $op) {
                    $uName = strtolower($op->user->name ?? '');
                    if (str_contains($uName, $searchName) || str_contains($searchName, $uName)) {
                        $matchedOperator = $op;
                        break;
                    }
                }
            }

            // Cocokkan Price yang berlaku pada tanggal ini
            $reportPrice = $prices->first(function ($p) use ($reportDateTime) {
                return strtotime($p->effective_at) <= strtotime($reportDateTime);
            }) ?? $fallbackPrice;

            // Jika ada rp_jual dari Excel, coba cocokkan ke harga yang tepat
            // (back-calculate implied harga_jual = rp_jual / vol_jual)
            $rpJualExcel = (float) ($row['rp_jual'] ?? 0);
            $volJualRow  = (float) ($row['vol_jual'] ?? 0);
            if ($rpJualExcel > 0 && $volJualRow > 0) {
                $impliedHarga = round($rpJualExcel / $volJualRow);
                // Cari price dengan harga_jual terdekat ke implied (toleransi ±200)
                $betterPrice = $prices->first(function ($p) use ($impliedHarga, $reportDateTime) {
                    return abs(floatval($p->harga_jual) - $impliedHarga) <= 200
                        && strtotime($p->effective_at) <= strtotime($reportDateTime);
                });
                if ($betterPrice) $reportPrice = $betterPrice;
            }

            // Pastikan totalisator valid
            $totAwal  = (float) $row['totalisator_awal'];
            $totAkhir = (float) $row['totalisator_akhir'];
            if ($totAkhir <= 0 && $totAwal > 0) $totAkhir = $totAwal;

            // Stik & Stok
            $stikAkhir = (float) $row['stik_akhir'];
            $stikAwal  = (float) ($row['stik_awal'] ?: ($stikAkhir > 0 ? $stikAkhir : ($shop->stik_awal ?? 100)));
            $stokAwal  = (float) ($row['stok_awal'] ?: ($stikAwal * ($shop->skala ?? 21.0)));

            $setorTunai   = max(0.0, min(99999999.99, (float) $row['setor_tunai']));
            $setorQris    = max(0.0, min(99999999.99, (float) $row['setor_qris']));
            $setorTransfer = max(0.0, min(99999999.99, (float) $row['setor_transfer']));
            $rawDisetorkan = (float) $row['disetorkan'];
            $disetorkan = ($rawDisetorkan > 0 && $rawDisetorkan <= 99999999.99)
                ? $rawDisetorkan
                : min(99999999.99, $setorTunai + $setorQris + $setorTransfer);

            // Update or Create Daily Report (idempotent per toko & tanggal)
            $dailyReport = DailyReport::updateOrCreate(
                [
                    'shop_id'    => $shop->id,
                    'created_at' => $reportDateTime,
                ],
                [
                    'operator_id'        => $matchedOperator?->id ?? 1,
                    'price_id'           => $reportPrice?->id ?? 1,
                    'totalisator_awal'   => $totAwal,
                    'totalisator_akhir'  => $totAkhir,
                    'stik_awal'          => $stikAwal,
                    'stik_akhir'         => $stikAkhir,
                    'stok_awal'          => $stokAwal,
                    'test_pump_volume'   => max(0.0, min(99999.999, (float) $row['test_pump_volume'])),
                    'penerimaan_volume'  => max(0.0, min(99999.99, (float) $row['penerimaan_volume'])),
                    'setor_tunai'        => $setorTunai,
                    'setor_qris'         => $setorQris,
                    'setor_transfer'     => $setorTransfer,
                    'disetorkan'         => $disetorkan,
                    'diverifikasi'       => 1,
                    'status_lifecycle'   => 'imported',
                    'sumber_data'        => 'import_excel_arsip',
                    'sumber_file_excel'  => $backdateFile->original_filename,
                    'backdate_file_id'   => $backdateFile->id,
                    'updated_at'         => now(),
                ]
            );

            // Simpan Data Pengeluaran (Spendings) jika ada
            if (!empty($row['spendings'])) {
                Spending::where('daily_report_id', $dailyReport->id)->delete();

                foreach ($row['spendings'] as $sp) {
                    Spending::create([
                        'daily_report_id'      => $dailyReport->id,
                        'spending_category_id' => $spendingCat->id,
                        'keterangan'           => $sp['keterangan'],
                        'jumlah'               => $sp['jumlah'],
                        'shop_id'              => $shop->id,
                        'operator_id'          => $matchedOperator?->id ?? 1,
                        'created_at'           => $reportDateTime,
                        'updated_at'           => now(),
                    ]);
                }
            }

            $savedCount++;
        }

        return $savedCount;
    }

    /**
     * Hitung ulang saldo berjalan kumulatif belum disetor (carry-forward)
     */
    public static function recalculateRunningBalance(int $shopId): void
    {
        $reports = DailyReport::where('shop_id', $shopId)
            ->orderBy('created_at', 'asc')
            ->get();

        $runningBalance = 0;
        foreach ($reports as $r) {
            $pendapatan = $r->pendapatan;
            $disetorkan = $r->disetorkan;
            $runningBalance += ($pendapatan - $disetorkan);

            if ($r->running_balance_setoran != $runningBalance) {
                DB::table('daily_reports')
                    ->where('id', $r->id)
                    ->update(['running_balance_setoran' => $runningBalance]);
            }
        }
    }

    /**
     * Cari path fisik file di disk lokal maupun public
     */
    protected static function resolvePhysicalPath(?string $filePath): string
    {
        if (empty($filePath)) return '';
        $storagePath = storage_path('app/public/' . $filePath);
        if (file_exists($storagePath) && is_file($storagePath)) return $storagePath;
        $publicPath = public_path('storage/' . $filePath);
        if (file_exists($publicPath) && is_file($publicPath)) return $publicPath;
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->path($filePath);
        }
        return $storagePath;
    }
}
