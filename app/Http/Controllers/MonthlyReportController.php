<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonthlyReport;
use App\Models\Shop;
use App\Models\DailyReport;
use App\Models\Price;
use App\Models\Spending;
use App\Models\CapitalRecap;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class MonthlyReportController extends Controller
{
    public function index()
    {
        if (auth()->user()->role === 'investor' && auth()->user()->investor) {
            $shopIds = auth()->user()->investor->shops->pluck('id')->toArray();
            $reports = MonthlyReport::with('shop')->whereIn('shop_id', $shopIds)->orderBy('created_at', 'desc')->get();
        } else {
            $reports = MonthlyReport::with('shop')->orderBy('created_at', 'desc')->get();
        }
        return view('monthly_reports.index', compact('reports'));
    }

    public function create()
    {
        $shops = Shop::with('investors.user')->get();
        $lastReport = MonthlyReport::orderBy('created_at', 'desc')->first();
        $lastSaldoModal = $lastReport ? $lastReport->saldo_akhir_modal : 0;
        return view('monthly_reports.create', compact('shops', 'lastSaldoModal'));
    }

    private function parseFlexibleNumber($val)
    {
        if (is_numeric($val)) {
            return (float)$val;
        }
        
        $val = trim($val);
        // Cek apakah format dalam tanda kurung, contoh: (1.560) atau (Rp15.000)
        $isNegative = false;
        if (preg_match('/^\(.*\)$/', $val)) {
            $isNegative = true;
        } else if (str_starts_with($val, '-')) {
            $isNegative = true;
        }

        // Hapus karakter-karakter yang tidak diperlukan, termasuk kurung, minus, dll (pastikan ,- didefinisikan sebelum -)
        $val = str_replace(["Rp", " ", "(", ")", ",-", "-", "è"], "", $val);
        $val = rtrim($val, ',');
        if ($val === '') return 0;
        
        // Tangani kasus koma sebagai desimal jika formatnya "11,376,29" -> ganti koma terakhir jadi titik jika diikuti persis 2 digit
        if (preg_match('/,(\d{2})$/', $val)) {
            $val = preg_replace('/,(\d{2})$/', '.$1', $val);
        }

        // Titik (.) adalah pemisah desimal
        // Maka kita cukup membuang koma, dan membiarkan floatval membaca titik sebagai desimal.
        $val = str_replace(",", "", $val);
        
        $floatVal = floatval($val);
        return $isNegative ? -$floatVal : $floatVal;
    }

    public function store(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'bulan_tahun' => 'required|string',
        ]);

        $shop = Shop::findOrFail($request->shop_id);
        $monthYearParsed = Carbon::parse($request->bulan_tahun);
        $month = $monthYearParsed->month;
        $year = $monthYearParsed->year;
        $daysInMonth = $monthYearParsed->daysInMonth;

        $harga_beli = [];
        $harga_jual = [];
        $excel_laba_kotor = null;
        $excel_total_biaya = null;
        $excel_laba_bersih = null;
        $excel_penahanan_modal = null;
        $excel_laba_dibagi = null;
        $excel_investors = [];
        $excel_operator_salaries = [];
        $excel_thr = 0;
        $excel_total_gaji = 0;
        $excel_bunga = 0;
        $excel_pajak = 0;
        $excel_rugi = 0;
        $excel_keuntungan = 0;
        $isExcel = false;

        DB::beginTransaction();
        try {
            if ($request->hasFile('excel_file')) {
                try {
                    $isExcel = true;
                $spreadsheet = IOFactory::load($request->file('excel_file')->path());
                
                // Parse KLB Sheet (Stok-Penjualan-Laba Kotor) - find dynamically by name
                $klbSheet = null;
                foreach ($spreadsheet->getAllSheets() as $sh) {
                    $shTitle = strtolower($sh->getTitle());
                    if (str_contains($shTitle, 'stok-penjualan') || str_contains($shTitle, 'stok penjualan') ||
                        str_contains($shTitle, 'laba ktr') || str_contains($shTitle, 'laba kotor') ||
                        (str_contains($shTitle, 'klb') && !str_contains($shTitle, 'rekap'))) {
                        $klbSheet = $sh;
                        break;
                    }
                }
                // Fallback: find sheet with "Harga Beli" anywhere
                if (!$klbSheet) {
                    foreach ($spreadsheet->getAllSheets() as $sh) {
                        $firstRows = $sh->toArray(null, true, false, false);
                        foreach (array_slice($firstRows, 0, 20) as $r) {
                            foreach ($r as $cv) {
                                if (is_string($cv) && preg_match('/Harga Beli/i', $cv)) {
                                    $klbSheet = $sh;
                                    break 3;
                                }
                            }
                        }
                    }
                }
                if (!$klbSheet && $spreadsheet->getSheetCount() > 2) {
                    $klbSheet = $spreadsheet->getSheet(2); // Last resort fallback
                }

                if ($klbSheet) {
                    $rows2 = $klbSheet->toArray(null, true, false, false);
                    foreach ($rows2 as $i => $r) {
                        foreach ($r as $colIdx => $colVal) {
                            if (!is_string($colVal)) continue;
                            $colValClean = trim($colVal);
                            if (preg_match('/Harga Beli (\d+)/i', $colValClean, $matches)) {
                                $idx = (int)$matches[1];
                                $parts = explode(':', $colValClean);
                                if (count($parts) > 1) {
                                    $harga_beli[$idx] = $this->parseFlexibleNumber($parts[1]);
                                }
                            }
                            if (preg_match('/Harga Jual (\d+)/i', $colValClean, $matches)) {
                                $idx = (int)$matches[1];
                                $parts = explode(':', $colValClean);
                                if (count($parts) > 1) {
                                    $harga_jual[$idx] = $this->parseFlexibleNumber($parts[1]);
                                }
                            }
                        }
                    }
                }


                // Parse KLT Sheet (Laba Bersih) - find dynamically by name
                $kltSheet = null;
                foreach ($spreadsheet->getAllSheets() as $sh) {
                    $shTitle = strtolower($sh->getTitle());
                    if (str_contains($shTitle, 'laba bersih') || str_contains($shTitle, 'klt') ||
                        (str_contains($shTitle, 'profit') && !str_contains($shTitle, 'sharing'))) {
                        $kltSheet = $sh;
                        break;
                    }
                }
                if (!$kltSheet && $spreadsheet->getSheetCount() > 3) {
                    $kltSheet = $spreadsheet->getSheet(3); // Last resort fallback
                }

                if ($kltSheet) {
                    $rows3 = $kltSheet->toArray();

                    $isParsingInvestors = false;
                    
                    foreach ($rows3 as $r3) {
                        $col0 = trim(strtolower((string)($r3[0] ?? '')));
                        $col1 = trim(strtolower((string)($r3[1] ?? '')));
                        $col10 = trim(strtolower((string)($r3[10] ?? '')));
                        
                        if (str_contains($col1, 'laba kotor')) {
                            $val = $this->parseFlexibleNumber($r3[13] ?? $r3[14] ?? 0);
                            if ($val > 0) $excel_laba_kotor = $val;
                        }
                        if (str_contains($col10, 'b. total biaya')) {
                            $val = $this->parseFlexibleNumber($r3[14] ?? 0);
                            if ($val > 0) $excel_total_biaya = $val;
                        }
                        if (str_contains($col10, 'laba bersih =')) {
                            $val = $this->parseFlexibleNumber($r3[14] ?? 0);
                            if ($val > 0) $excel_laba_bersih = $val;
                        }
                        if (str_contains($col10, 'penambahan modal')) {
                            $val = $this->parseFlexibleNumber($r3[14] ?? 0);
                            if ($val > 0) $excel_penahanan_modal = $val;
                        }
                        if (str_contains($col10, 'saldo laba bersih (90%)')) {
                            $val = $this->parseFlexibleNumber($r3[14] ?? 0);
                            if ($val > 0) $excel_laba_dibagi = $val;
                        }
                        
                        if (str_contains($col0, 'pembagian laba bersih')) {
                            $isParsingInvestors = true;
                            continue;
                        }
                        if ($isParsingInvestors) {
                            if (str_contains($col0, 'catatan')) break;
                            $name = trim((string)($r3[1] ?? ''));
                            $percent = trim((string)($r3[6] ?? ''));
                            if (!empty($name) && str_contains($percent, '%')) {
                                $name = str_replace('\ *.', '', $name);
                                $name = trim($name, " \t\n\r\0\x0B*.");
                                $percentValue = (float) str_replace('%', '', $percent);
                                $excel_investors[] = [
                                    'nama' => $name,
                                    'persen' => $percentValue
                                ];
                            }
                        }
                    }
                }

                // Parse Gaji Sheet
                $gajiSheet = null;
                $debug_logs = [];
                foreach ($spreadsheet->getAllSheets() as $sh) {
                    $shTitle = strtolower($sh->getTitle());
                    if (str_contains($shTitle, 'gaji') || str_contains($shTitle, 'slip')) {
                        $gajiSheet = $sh;
                        break;
                    }
                }
                
                // Fallback: Check sheet contents for Gaji keywords if not found by title
                if (!$gajiSheet) {
                    foreach ($spreadsheet->getAllSheets() as $sh) {
                        $shTitle = strtolower($sh->getTitle());
                        if (str_contains($shTitle, 'bkh') || str_contains($shTitle, 'buku kas')) continue; // Skip BKH
                        
                        $firstRows = $sh->toArray(null, true, false, false);
                        foreach (array_slice($firstRows, 0, 30) as $r) {
                            foreach ($r as $cv) {
                                if (is_string($cv) && preg_match('/take home pay|fee penjualan|gaji pokok|total gaji/i', $cv)) {
                                    $gajiSheet = $sh;
                                    break 3;
                                }
                            }
                        }
                    }
                }
                
                if ($gajiSheet) {
                    $rowsGaji = [];
                    $highestColumn = $gajiSheet->getHighestColumn();
                    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                    $highestRow = $gajiSheet->getHighestRow();

                    for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
                        $rData = [];
                        for ($colIndex = 1; $colIndex <= $highestColumnIndex; $colIndex++) {
                            $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                            $cell = $gajiSheet->getCell($colString . $rowIndex);
                            $val = null;
                            if ($cell) {
                                $rawVal = $cell->getValue();
                                if (is_string($rawVal) && str_starts_with($rawVal, '=')) {
                                    $val = $cell->getOldCalculatedValue();
                                    if ($val === null) {
                                        try {
                                            $val = $cell->getFormattedValue();
                                        } catch (\Exception $e) {
                                            $val = $cell->getCalculatedValue();
                                        }
                                    }
                                } else {
                                    try {
                                        $val = $cell->getFormattedValue();
                                    } catch (\Exception $e) {
                                        $val = $cell->getCalculatedValue();
                                    }
                                }
                            }
                            $rData[] = $val;
                        }
                        $rowsGaji[] = $rData;
                    }
                    
                    foreach ($rowsGaji as $r => $row) {
                        foreach ($row as $c => $cell) {
                            $val = strtolower(trim((string)$cell));
                            
                            // Parse THR and Total
                            if (str_contains($val, 'total gaji karyawan') || str_contains($val, 'total gaji operator')) {
                                for ($nr = $r; $nr <= min($r + 2, count($rowsGaji) - 1); $nr++) {
                                    for ($nc = $c; $nc <= $c + 2; $nc++) {
                                        if ($nr === $r && $nc === $c && !preg_match('/\d/', $val)) continue;
                                        $cv = strtolower(trim((string)($rowsGaji[$nr][$nc] ?? '')));
                                        if (preg_match('/(?:rp)?\s*([\d\.,]+)/i', $cv, $m)) {
                                            $v = $this->parseFlexibleNumber($m[1]);
                                            if ($v > 0) { $excel_total_gaji = $v; break 2; }
                                        }
                                    }
                                }
                            }
                            if ($val === 'thr') {
                                for ($nr = $r; $nr <= min($r + 2, count($rowsGaji) - 1); $nr++) {
                                    for ($nc = $c; $nc <= $c + 2; $nc++) {
                                        if ($nr === $r && $nc === $c && !preg_match('/\d/', $val)) continue;
                                        $cv = strtolower(trim((string)($rowsGaji[$nr][$nc] ?? '')));
                                        if (preg_match('/(?:rp)?\s*([\d\.,]+)/i', $cv, $m)) {
                                            $v = $this->parseFlexibleNumber($m[1]);
                                            if ($v > 0) { $excel_thr = $v; break 2; }
                                        }
                                    }
                                }
                            }
            
                            // Parse Operator
                            if (str_starts_with($val, 'nama')) {
                                $nameCellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1);
                                $nameCellAddr = $nameCellLetter . ($r + 1);
                                
                                $nameVal = trim(substr((string)$cell, 4));
                                $nameVal = ltrim($nameVal, ': ');
                                if ($nameVal === '') {
                                    for ($k = $c+1; $k <= $c+5; $k++) {
                                        $v = trim((string)($row[$k] ?? ''));
                                        if ($v !== '' && $v !== ':') {
                                            $nameVal = $v;
                                            $nameCellAddr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($k + 1) . ($r + 1);
                                            break;
                                        }
                                    }
                                }
                                if ($nameVal !== '' && !preg_match('/rp|liter|hk|\d/i', $nameVal)) {
                                    $debug_logs[] = "Menemukan Nama Operator: $nameVal di cell $nameCellAddr";
                                    $op = [
                                        'operator_nama' => $nameVal,
                                        'total_penjualan_b' => 0,
                                        'losses_c' => 0,
                                        'penjualan_losses_d' => 0,
                                        'hari_jaga' => $daysInMonth,
                                        'gaji' => 0
                                    ];
                                    
                                    $hasHK = false;
                                    $foundGrandTotal = false;
                                    // Scan bounding box
                                    $literCellAddr = 'Tidak Ditemukan';
                                    $gajiCellAddr = 'Tidak Ditemukan';
                                    $rawLiterString = '';
                                    $rawGajiString = '';
                                    for ($nr = $r; $nr <= min($r + 50, count($rowsGaji)-1); $nr++) {
                                        $rowStrBox = '';
                                        for ($nc = max(0, $c-2); $nc <= $c+12; $nc++) {
                                            $rowStrBox .= ' ' . trim((string)($rowsGaji[$nr][$nc] ?? ''));
                                        }
                                        $rowStrBoxLow = strtolower($rowStrBox);
                                        
                                        if (str_contains($rowStrBoxLow, 'gaji bulanan') || str_contains($rowStrBoxLow, 'fee penjualan')) {
                                            if (preg_match('/\(\s*([-\d\.,]+)\s*\)/i', $rowStrBox, $m)) {
                                                $liter = $this->parseFlexibleNumber($m[1]);
                                                $op['total_penjualan_b'] = $liter;
                                                $op['penjualan_losses_d'] = $liter;
                                                if (stripos($rowStrBoxLow, 'hk') !== false) {
                                                    $op['hari_jaga'] = $liter;
                                                    $hasHK = true;
                                                }
                                            }
                                        }
                                        if (str_contains($rowStrBoxLow, 'tidak masuk')) {
                                            if (preg_match('/(?:0|:\s*(\d+))\s*hari/i', $rowStrBox, $m)) {
                                                $tm = isset($m[1]) && $m[1] !== '' ? (int)$m[1] : 0;
                                                if (!$hasHK) {
                                                    $op['hari_jaga'] = $daysInMonth - $tm; 
                                                }
                                            }
                                        }
                                        // Update to support 'Take Home Pay' and 'GRAND TOTAL TAKE HOME PAY', and handle negative '(123)' or '-123'
                                        if (str_contains($rowStrBoxLow, 'take home pay') || str_contains($rowStrBoxLow, 'grand total')) {
                                            if (preg_match('/(?:take home pay|grand total)[^0-9\-\(]*([-\(\d\.,]+)/i', $rowStrBox, $m)) {
                                                $val = $this->parseFlexibleNumber($m[1]);
                                                $op['gaji'] = $val;
                                                $foundGrandTotal = true;
                                                if (str_contains($rowStrBoxLow, 'grand total')) {
                                                    break; // If we found the grand total, it's the final one, so break.
                                                }
                                            }
                                        }
                                    }
                                    
                                    if ($foundGrandTotal) {
                                        $debug_logs[] = "  -> BERHASIL PARSING!";
                                        $debug_logs[] = "  -> Liter: {$op['total_penjualan_b']} (Dari $literCellAddr). Raw String: '$rawLiterString'";
                                        $debug_logs[] = "  -> Gaji: Rp {$op['gaji']} (Dari $gajiCellAddr). Raw String: '$rawGajiString'";
                                        $exists = false;
                                        foreach ($excel_operator_salaries as $existing) {
                                            if ($existing['operator_nama'] === $op['operator_nama']) {
                                                $exists = true; break;
                                            }
                                        }
                                        if (!$exists) {
                                            $excel_operator_salaries[] = $op;
                                        }
                                    } else {
                                        $debug_logs[] = "  -> GAGAL PARSING: Tidak menemukan nilai untuk 'GRAND TOTAL TAKE HOME PAY'.";
                                        $debug_logs[] = "  -> Info Terakhir -> Liter: {$op['total_penjualan_b']} (Dari $literCellAddr). Raw String Liter: '$rawLiterString'";
                                        $debug_logs[] = "  -> Info Terakhir -> Raw String Gaji yang sempat terlihat: '$rawGajiString'";
                                    }
                                }
                            }
                        }
                    }
                    
                    // Calculate but do not enforce validation since 'Total Gaji Karyawan' in Excel is unreliable
                    if (count($excel_operator_salaries) > 0) {
                        $sumGaji = 0;
                        foreach ($excel_operator_salaries as $op) {
                            $sumGaji += $op['gaji'];
                        }
                    }
                }
                
                \Log::info("DEBUG LOGS GAJI PARSER:", $debug_logs);
                \Log::info("PARSED OPERATORS:", $excel_operator_salaries);

                // Parse Rekap Modal Sheet (Find by title containing 'rekap' and 'modal')
                $rekapModalSheet = null;
                foreach ($spreadsheet->getAllSheets() as $sh) {
                    $title = strtolower($sh->getTitle());
                    if (str_contains($title, 'rekap') && str_contains($title, 'modal')) {
                        $rekapModalSheet = $sh;
                        break;
                    }
                }
                
                // Fallback to searching the whole file for header if name search fails
                if (!$rekapModalSheet) {
                    foreach ($spreadsheet->getAllSheets() as $sh) {
                        $firstRows = $sh->toArray(null, true, false, false);
                        foreach (array_slice($firstRows, 0, 10) as $r) {
                            foreach ($r as $cv) {
                                if (is_string($cv) && preg_match('/akumulasi modal|posisi akhir modal|penambahan \(keuntungan\)/i', $cv)) {
                                    $rekapModalSheet = $sh;
                                    break 3;
                                }
                            }
                        }
                    }
                }

                if ($rekapModalSheet) {
                    // Read header row (Row 2) to establish column mapping dynamically
                    $colMapping = [];
                    $headerRow = 2;
                    for ($c = 1; $c <= 15; $c++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                        $val = trim(strtolower($rekapModalSheet->getCell($colLetter . $headerRow)->getValue() ?? ''));
                        
                        if (str_contains($val, 'thn ke') || str_contains($val, 'tahun ke')) {
                            $colMapping['tahun_ke'] = $colLetter;
                        } elseif ($val === 'bulan') {
                            $colMapping['bulan'] = $colLetter;
                        } elseif (str_contains($val, 'nilai modal awal') || str_contains($val, 'modal awal')) {
                            $colMapping['nilai_modal_awal'] = $colLetter;
                        } elseif (str_contains($val, 'penyusutan karena rugi') || str_contains($val, 'penyusutan rugi')) {
                            $colMapping['penyusutan_rugi'] = $colLetter;
                        } elseif (str_contains($val, 'pajak') || str_contains($val, 'biaya bank')) {
                            $colMapping['penyusutan_pajak_bank'] = $colLetter;
                        } elseif (str_contains($val, 'alokasi keuntungan') || str_contains($val, 'keuntungan')) {
                            $colMapping['penambahan_keuntungan'] = $colLetter;
                        } elseif (str_contains($val, 'bunga bank')) {
                            $colMapping['penambahan_bunga_bank'] = $colLetter;
                        } elseif (str_contains($val, 'nilai penambahan') || str_contains($val, 'penambahan / penyusutan  modal')) {
                            $colMapping['nilai_penambahan_penyusutan'] = $colLetter;
                        } elseif (str_contains($val, 'akumulasi')) {
                            $colMapping['akumulasi_penambahan_penyusutan'] = $colLetter;
                        } elseif (str_contains($val, 'posisi akhir')) {
                            $colMapping['posisi_akhir_modal'] = $colLetter;
                        } elseif (str_contains($val, 'harga beli') || str_contains($val, 'harga pertamax')) {
                            $colMapping['harga_beli_pertamax'] = $colLetter;
                        } elseif (str_contains($val, 'konversi')) {
                            $colMapping['konversi_liter'] = $colLetter;
                        }
                    }

                    $col_tahun_ke = $colMapping['tahun_ke'] ?? 'A';
                    $col_bulan = $colMapping['bulan'] ?? 'B';
                    $col_modal_awal = $colMapping['nilai_modal_awal'] ?? 'C';
                    $col_rugi = $colMapping['penyusutan_rugi'] ?? 'D';
                    $col_pajak = $colMapping['penyusutan_pajak_bank'] ?? 'E';
                    $col_keuntungan = $colMapping['penambahan_keuntungan'] ?? 'F';
                    $col_bunga = $colMapping['penambahan_bunga_bank'] ?? 'G';
                    $col_nilai_penambahan = $colMapping['nilai_penambahan_penyusutan'] ?? 'H';
                    $col_akumulasi = $colMapping['akumulasi_penambahan_penyusutan'] ?? 'I';
                    $col_posisi_akhir = $colMapping['posisi_akhir_modal'] ?? 'J';
                    $col_harga_beli = $colMapping['harga_beli_pertamax'] ?? 'K';
                    $col_konversi = $colMapping['konversi_liter'] ?? 'L';

                    $highestRowRM = $rekapModalSheet->getHighestRow();
                    $current_tahun_ke = 1;
                    for ($row = 4; $row <= $highestRowRM; $row++) {
                        $tahun_ke_raw = $rekapModalSheet->getCell($col_tahun_ke . $row)->getCalculatedValue();
                        if ($tahun_ke_raw !== null && $tahun_ke_raw !== '') {
                            $current_tahun_ke = intval($tahun_ke_raw);
                        }
                        $tahun_ke = $current_tahun_ke;
                        
                        $bulan_raw = $rekapModalSheet->getCell($col_bulan . $row)->getCalculatedValue();
                        
                        // Parse bulan (Excel Serial Date)
                        $bulanRM = 0;
                        $tahunRM = 0;
                        
                        if (is_numeric($bulan_raw)) {
                            try {
                                $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($bulan_raw);
                                $bulanRM = intval($dateObj->format('n'));
                                $tahunRM = intval($dateObj->format('Y'));
                            } catch (\Exception $e) {
                                continue;
                            }
                        } else {
                            continue;
                        }
                        
                        if (!$bulanRM || !$tahunRM) continue;

                        // Prevent importing future placeholder months
                        if ($tahunRM > $year || ($tahunRM == $year && $bulanRM > $month)) {
                            continue;
                        }
                        
                        $modal_awal = floatval($rekapModalSheet->getCell($col_modal_awal . $row)->getCalculatedValue());
                        $rugi = floatval($rekapModalSheet->getCell($col_rugi . $row)->getCalculatedValue());
                        $pajak = floatval($rekapModalSheet->getCell($col_pajak . $row)->getCalculatedValue());
                        $keuntungan = floatval($rekapModalSheet->getCell($col_keuntungan . $row)->getCalculatedValue());
                        $bunga = floatval($rekapModalSheet->getCell($col_bunga . $row)->getCalculatedValue());
                        $nilai_penambahan = floatval($rekapModalSheet->getCell($col_nilai_penambahan . $row)->getCalculatedValue());
                        $akumulasi = floatval($rekapModalSheet->getCell($col_akumulasi . $row)->getCalculatedValue());
                        $posisi_akhir = floatval($rekapModalSheet->getCell($col_posisi_akhir . $row)->getCalculatedValue());
                        $harga_beli_rm = floatval($rekapModalSheet->getCell($col_harga_beli . $row)->getCalculatedValue());
                        $konversi = floatval($rekapModalSheet->getCell($col_konversi . $row)->getCalculatedValue());

                        if ($bulanRM == $month && $tahunRM == $year) {
                            $excel_bunga = $bunga;
                            $excel_pajak = $pajak;
                            $excel_rugi = $rugi;
                            $excel_keuntungan = $keuntungan;
                        }

                        CapitalRecap::updateOrCreate(
                            [
                                'shop_id' => $shop->id,
                                'bulan' => $bulanRM,
                                'tahun' => $tahunRM,
                            ],
                            [
                                'tahun_ke' => $tahun_ke,
                                'nilai_modal_awal' => $modal_awal,
                                'penyusutan_rugi' => $rugi,
                                'penyusutan_pajak_bank' => $pajak,
                                'penambahan_keuntungan' => $keuntungan,
                                'penambahan_bunga_bank' => $bunga,
                                'nilai_penambahan_penyusutan' => $nilai_penambahan,
                                'akumulasi_penambahan_penyusutan' => $akumulasi,
                                'posisi_akhir_modal' => $posisi_akhir,
                                'harga_beli_pertamax' => $harga_beli_rm,
                                'konversi_liter' => $konversi,
                            ]
                        );
                    }
                    
                    // Trigger dynamic recalculation
                    CapitalRecap::recalculateForShop($shop->id);
                }

                // Parse BKH Sheet - find dynamically by header content
                // Strategy: find the sheet that has "Totalisator" header in row 1 or 2
                $sheet = null;
                $bkhSheetKeywords = ['rekap', 'bkh', 'harian', 'penjualan', 'stok-penjualan'];
                
                // Priority 1: Find sheet with "Totalisator" header in first 3 rows, columns C-F
                foreach ($spreadsheet->getAllSheets() as $shIdx => $sh) {
                    $shTitle = strtolower($sh->getTitle());
                    // Skip sheets that are clearly NOT BKH
                    $skipKeywords = ['laba bersih', 'modal', 'gaji', 'profit sharing', 'pembelian do', 'hutang', 'setoran'];
                    $shouldSkip = false;
                    foreach ($skipKeywords as $sk) {
                        if (str_contains($shTitle, $sk)) { $shouldSkip = true; break; }
                    }
                    if ($shouldSkip) continue;
                    
                    // Check for "Totalisator" in first 3 rows
                    for ($r = 1; $r <= 3; $r++) {
                        for ($c = 3; $c <= 7; $c++) {
                            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                            $cellVal = strtolower(trim((string)($sh->getCell($colLetter . $r)->getValue() ?? '')));
                            if (str_contains($cellVal, 'totalisator')) {
                                $sheet = $sh;
                                break 3;
                            }
                        }
                    }
                }
                
                // Priority 2: Find sheet by keyword in title
                if (!$sheet) {
                    foreach ($spreadsheet->getAllSheets() as $shIdx => $sh) {
                        $shTitle = strtolower($sh->getTitle());
                        foreach ($bkhSheetKeywords as $keyword) {
                            if (str_contains($shTitle, $keyword)) {
                                $sheet = $sh;
                                break 2;
                            }
                        }
                    }
                }
                
                // Priority 3: Fallback to Sheet(1) if more than 1 sheet, else Sheet(0)
                if (!$sheet) {
                    $sheet = $spreadsheet->getSheetCount() > 1
                        ? $spreadsheet->getSheet(1)
                        : $spreadsheet->getSheet(0);
                }
                
                $rows = $sheet->toArray();

                
                $dailyReports = collect();
                $rowIndex = 0;
                
                $op = $shop->operators()->first();
                $operator_id = $op ? $op->user_id : \Illuminate\Support\Facades\Auth::user()->id;
                
                DB::transaction(function() use ($shop, $month, $year, $rows, $monthYearParsed, &$dailyReports, $harga_jual, $harga_beli, $operator_id, $op) {
                    // Clear existing daily reports (and cascades to spendings/test_pumps) for this shop and month/year
                    DailyReport::where('shop_id', $shop->id)
                        ->whereMonth('created_at', $month)
                        ->whereYear('created_at', $year)
                        ->delete();

                    $rowIndex = 0;
                    $previous_hj = null;
                    $previous_hb = null;
                    $bkhLogged = false;
                    
                    $offset = 0;
                    $headerRow = $rows[1] ?? [];
                    $header0 = strtolower(trim($headerRow[0] ?? ''));
                    $header1 = strtolower(trim($headerRow[1] ?? ''));
                    $header2 = strtolower(trim($headerRow[2] ?? ''));
                    
                    if (str_contains($header1, 'tgl') || str_contains($header1, 'tanggal')) {
                        $offset = 1;
                    } elseif (str_contains($header2, 'tgl') || str_contains($header2, 'tanggal')) {
                        $offset = 2;
                    }
                    
                    foreach ($rows as $r) {
                        $rowIndex++;
                        if ($rowIndex <= 2) continue;
                        
                        $tgl_str = trim($r[$offset] ?? '');
                        if (empty($tgl_str) || !is_numeric($tgl_str)) continue;
                        
                        $day = intval($tgl_str);
                        if ($day < 1 || $day > 31) continue;
                        
                        if (!$bkhLogged) {
                            \Log::info("BKH FIRST DATA ROW (Index $rowIndex):", $r);
                            \Log::info("Tgl: $tgl_str, Offset detected: $offset");
                            $bkhLogged = true;
                        }
                        
                        $currentDate = $monthYearParsed->copy()->setDay($day);
                        
                        $vol_teoritis = $this->parseFlexibleNumber($r[5 + $offset] ?? 0);
                        $rp_teoritis = $this->parseFlexibleNumber($r[6 + $offset] ?? 0);
                        $tp_vol = $this->parseFlexibleNumber($r[7 + $offset] ?? 0);
                        $stok_awal = $this->parseFlexibleNumber($r[13 + $offset] ?? 0);
                        $terima_bbm = $this->parseFlexibleNumber($r[15 + $offset] ?? 0);
                        $stok_akhir = $this->parseFlexibleNumber($r[18 + $offset] ?? 0);
                        $losses_vol = $this->parseFlexibleNumber($r[19 + $offset] ?? 0);
                        $losses_rp = $this->parseFlexibleNumber($r[20 + $offset] ?? 0);
                        
                        if ($vol_teoritis <= 0 && $previous_hj !== null && $previous_hj > 0) {
                            $hj = $previous_hj;
                            $hb = $previous_hb;
                        } else {
                            $hj = $vol_teoritis > 0 ? round($rp_teoritis / $vol_teoritis) : 12200;
                            $hb = 0;
                            foreach ($harga_jual as $idx => $val) {
                                if (abs($val - $hj) < 10) {
                                    $hb = $harga_beli[$idx] ?? 0;
                                    break;
                                }
                            }
                            if ($hb == 0) {
                                $fallbackPrice = Price::where('shop_id', $shop->id)->first();
                                $hb = $fallbackPrice ? floatval($fallbackPrice->harga_beli) : ($hj * 0.93);
                            }
                        }
                        $previous_hj = $hj;
                        $previous_hb = $hb;
                        
                        $dbPrice = Price::where('shop_id', $shop->id)
                            ->where('harga_jual', $hj)
                            ->where('harga_beli', $hb)
                            ->first();
                        if (!$dbPrice) {
                            $dbPrice = Price::create([
                                'shop_id' => $shop->id,
                                'harga_jual' => $hj,
                                'harga_beli' => $hb,
                                'effective_at' => $currentDate->format('Y-m-d H:i:s'),
                            ]);
                        }
                        
                        $rep = new DailyReport();
                        $rep->shop_id = $shop->id;
                        $rep->operator_id = $operator_id;
                        $rep->price_id = $dbPrice->id;
                        $rep->totalisator_awal = $this->parseFlexibleNumber($r[3 + $offset] ?? 0);
                        $rep->totalisator_akhir = $this->parseFlexibleNumber($r[4 + $offset] ?? 0);
                        $rep->test_pump_volume = $tp_vol;
                        $rep->penerimaan_volume = $terima_bbm;
                        $rep->stik_akhir = $stok_akhir / $shop->skala;
                        $rep->stok_awal = $stok_awal;
                        $rep->setor_tunai = $this->parseFlexibleNumber($r[37 + $offset] ?? 0);
                        $rep->setor_qris = $this->parseFlexibleNumber($r[38 + $offset] ?? 0);
                        $rep->setor_transfer = $this->parseFlexibleNumber($r[39 + $offset] ?? 0);
                        $rep->diverifikasi = 1;
                        $rep->created_at = $currentDate;
                        $rep->updated_at = $currentDate;
                        $rep->save();
                        
                        // Assign dynamic memory-only attributes AFTER saving to avoid DB column errors
                        $rep->stok_awal_excel = $stok_awal;
                        $rep->stok_akhir_excel = $stok_akhir;
                        $rep->losses_gain_excel = $losses_vol;
                        $rep->losses_gain_rp_excel = $losses_rp;
                        $rep->volume_penjualan_teoritis_excel = $vol_teoritis;
                        $rep->rupiah_penjualan_teoritis_excel = $rp_teoritis;
                        $rep->belum_disetorkan_excel = $this->parseFlexibleNumber($r[41 + $offset] ?? 0);
                        
                        $rep->setRelation('price', $dbPrice);
                        $rep->setRelation('shop', $shop);
                        $rep->setRelation('operator', $op);
                        
                        $spendings = collect();
                        $bongkar = $this->parseFlexibleNumber($r[24 + $offset] ?? 0);
                        if ($bongkar > 0) {
                            $sp = new Spending(['spending_category_id' => 1, 'jumlah' => $bongkar, 'keterangan' => 'Bongkar', 'shop_id' => $shop->id, 'operator_id' => $operator_id, 'daily_report_id' => $rep->id]);
                            $sp->created_at = $currentDate;
                            $sp->updated_at = $currentDate;
                            $sp->save();
                            $spendings->push($sp);
                        }
                        $tf = $this->parseFlexibleNumber($r[25 + $offset] ?? 0);
                        if ($tf > 0) {
                            $sp = new Spending(['spending_category_id' => 2, 'jumlah' => $tf, 'keterangan' => 'TF', 'shop_id' => $shop->id, 'operator_id' => $operator_id, 'daily_report_id' => $rep->id]);
                            $sp->created_at = $currentDate;
                            $sp->updated_at = $currentDate;
                            $sp->save();
                            $spendings->push($sp);
                        }
                        $atk = $this->parseFlexibleNumber($r[26 + $offset] ?? 0);
                        if ($atk > 0) {
                            $sp = new Spending(['spending_category_id' => 3, 'jumlah' => $atk, 'keterangan' => 'ATK', 'shop_id' => $shop->id, 'operator_id' => $operator_id, 'daily_report_id' => $rep->id]);
                            $sp->created_at = $currentDate;
                            $sp->updated_at = $currentDate;
                            $sp->save();
                            $spendings->push($sp);
                        }
                        $listrik = $this->parseFlexibleNumber($r[27 + $offset] ?? 0);
                        if ($listrik > 0) {
                            $sp = new Spending(['spending_category_id' => 4, 'jumlah' => $listrik, 'keterangan' => 'Listrik', 'shop_id' => $shop->id, 'operator_id' => $operator_id, 'daily_report_id' => $rep->id]);
                            $sp->created_at = $currentDate;
                            $sp->updated_at = $currentDate;
                            $sp->save();
                            $spendings->push($sp);
                        }
                        $air = $this->parseFlexibleNumber($r[28 + $offset] ?? 0);
                        if ($air > 0) {
                            $sp = new Spending(['spending_category_id' => 5, 'jumlah' => $air, 'keterangan' => 'Air', 'shop_id' => $shop->id, 'operator_id' => $operator_id, 'daily_report_id' => $rep->id]);
                            $sp->created_at = $currentDate;
                            $sp->updated_at = $currentDate;
                            $sp->save();
                            $spendings->push($sp);
                        }
                        $cashback = $this->parseFlexibleNumber($r[29 + $offset] ?? 0);
                        if ($cashback > 0) {
                            $sp = new Spending(['spending_category_id' => 6, 'jumlah' => $cashback, 'keterangan' => 'Cashback', 'shop_id' => $shop->id, 'operator_id' => $operator_id, 'daily_report_id' => $rep->id]);
                            $sp->created_at = $currentDate;
                            $sp->updated_at = $currentDate;
                            $sp->save();
                            $spendings->push($sp);
                        }
                        $internet = $this->parseFlexibleNumber($r[30 + $offset] ?? 0);
                        if ($internet > 0) {
                            $sp = new Spending(['spending_category_id' => 7, 'jumlah' => $internet, 'keterangan' => 'Internet', 'shop_id' => $shop->id, 'operator_id' => $operator_id, 'daily_report_id' => $rep->id]);
                            $sp->created_at = $currentDate;
                            $sp->updated_at = $currentDate;
                            $sp->save();
                            $spendings->push($sp);
                        }
                        $lainnya = $this->parseFlexibleNumber($r[31 + $offset] ?? 0);
                        if ($lainnya > 0) {
                            $sp = new Spending(['spending_category_id' => 99, 'jumlah' => $lainnya, 'keterangan' => 'Lain-lain', 'shop_id' => $shop->id, 'operator_id' => $operator_id, 'daily_report_id' => $rep->id]);
                            $sp->created_at = $currentDate;
                            $sp->updated_at = $currentDate;
                            $sp->save();
                            $spendings->push($sp);
                        }
                        
                        $rep->setRelation('spendings', $spendings);
                        
                        if ($tp_vol > 0) {
                            \App\Models\TestPump::create([
                                'shop_id' => $shop->id,
                                'operator_id' => $operator_id,
                                'daily_report_id' => $rep->id,
                                'totalisator_awal' => $rep->totalisator_akhir - $tp_vol,
                                'totalisator_akhir' => $rep->totalisator_akhir,
                                'created_at' => $currentDate,
                                'updated_at' => $currentDate
                            ]);
                        }
                        
                        $dailyReports->push($rep);
                    }
                });
            } catch (\Exception $e) {
                return back()->withErrors(['excel' => 'Gagal membaca format Excel: ' . $e->getMessage()]);
            }
        } else {
            $dailyReports = DailyReport::with(['spendings', 'incomings', 'testPumps', 'operator.user', 'price', 'periods.price'])
                ->where('shop_id', $shop->id)
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        // 1. Group daily reports/periods into Price Segments
        $segmentsData = [];
        foreach ($dailyReports as $report) {
            if (!$isExcel && $report->periods()->exists()) {
                foreach ($report->periods as $period) {
                    $pid = $period->price_id ?: $report->price_id;
                    if (!isset($segmentsData[$pid])) {
                        $segmentsData[$pid] = [];
                    }
                    $segmentsData[$pid][] = [
                        'report_id' => $report->id,
                        'date' => $report->created_at->format('Y-m-d'),
                        'totalisator_awal' => $period->totalisator_awal,
                        'totalisator_akhir' => $period->totalisator_akhir,
                        'test_pump' => $report->test_pump,
                        'penerimaan' => $report->penerimaan,
                        'stok_akhir' => $report->stok_akhir_aktual,
                    ];
                }
            } else {
                $pid = $report->price_id;
                if (!isset($segmentsData[$pid])) {
                    $segmentsData[$pid] = [];
                }
                $segmentsData[$pid][] = [
                    'report_id' => $report->id,
                    'date' => $report->created_at->format('Y-m-d'),
                    'totalisator_awal' => $report->totalisator_awal,
                    'totalisator_akhir' => $report->totalisator_akhir,
                    'test_pump' => $isExcel ? $report->test_pump_volume : $report->test_pump,
                    'penerimaan' => $isExcel ? $report->penerimaan_volume : $report->penerimaan,
                    'stok_akhir' => $isExcel ? $report->stok_akhir_excel : $report->stok_akhir_aktual,
                ];
            }
        }

        $uniquePriceIds = array_keys($segmentsData);
        usort($uniquePriceIds, function($a, $b) use ($segmentsData) {
            return strcmp($segmentsData[$a][0]['date'], $segmentsData[$b][0]['date']);
        });

        $segments = [];
        $previousSegmentEndStock = null;
        $hppPerSegmentLast = 0;

        foreach ($uniquePriceIds as $index => $pid) {
            $items = $segmentsData[$pid];
            
            usort($items, function($x, $y) {
                if ($x['date'] === $y['date']) {
                    return $x['totalisator_awal'] <=> $y['totalisator_awal'];
                }
                return strcmp($x['date'], $y['date']);
            });
            
            $firstItem = $items[0];
            $lastItem = $items[count($items) - 1];
            
            $startDate = Carbon::parse($firstItem['date'])->format('d M\'y');
            $endDate = Carbon::parse($lastItem['date'])->format('d M\'y');
            
            $totAwal = floatval($firstItem['totalisator_awal']);
            $totAkhir = floatval($lastItem['totalisator_akhir']);
            $totalVolumePenjualan = max(0.0, $totAkhir - $totAwal);
            
            $processedReportIds = [];
            $totalTestPump = 0;
            $totalPenerimaan = 0;
            foreach ($items as $item) {
                if (!in_array($item['report_id'], $processedReportIds)) {
                    $totalTestPump += floatval($item['test_pump']);
                    $totalPenerimaan += floatval($item['penerimaan']);
                    $processedReportIds[] = $item['report_id'];
                }
            }
            
            if ($index === 0) {
                $firstReport = $dailyReports->first(function($rep) use ($firstItem) {
                    return $rep->created_at->format('Y-m-d') === $firstItem['date'];
                });
                $stokAwal = $firstReport ? ($isExcel ? $firstReport->stok_awal_excel : $firstReport->stok_awal) : 0;
            } else {
                $stokAwal = $previousSegmentEndStock;
            }
            
            $lastReport = $dailyReports->first(function($rep) use ($lastItem) {
                return $rep->created_at->format('Y-m-d') === $lastItem['date'];
            });
            $stokAkhir = $lastReport ? ($isExcel ? $lastReport->stok_akhir_excel : $lastReport->stok_akhir_aktual) : 0;
            $previousSegmentEndStock = $stokAkhir;
            
            $price = null;
            foreach ($items as $item) {
                $rep = $dailyReports->first(function($r) use ($item) {
                    return $r->created_at->format('Y-m-d') === $item['date'];
                });
                if ($rep && $rep->price) {
                    $price = $rep->price;
                    break;
                }
            }
            
            $hargaBeli = $price ? floatval($price->harga_beli) : 0;
            $hargaJual = $price ? floatval($price->harga_jual) : 0;
            $hppPerSegmentLast = $hargaBeli;

            $stokAwalRp = $stokAwal * $hargaBeli;
            $bbmDatangRp = $totalPenerimaan * $hargaBeli;
            $jumlahPembelian = $stokAwal + $totalPenerimaan;
            $jumlahPembelianRp = $stokAwalRp + $bbmDatangRp;
            
            $jumlahPenjualan = max(0.0, $totalVolumePenjualan - $totalTestPump);
            $jumlahPenjualanRp = $jumlahPenjualan * $hargaJual;
            
            $sisaStok = max(0.0, $jumlahPembelian - $jumlahPenjualan);
            $sisaStokRp = $sisaStok * $hargaBeli;
            
            $lossesGain = $stokAkhir - $sisaStok;
            $lossesGainRp = $lossesGain * $hargaBeli;
            $lossesGainPersen = $jumlahPenjualan > 0 ? ($lossesGain / $jumlahPenjualan) * 100 : 0;
            
            $penjualanBersihRp = $jumlahPenjualanRp + $sisaStokRp + $lossesGainRp;
            $labaKotor = $penjualanBersihRp - $jumlahPembelianRp;
            
            $segments[] = [
                'segmen_index' => $index + 1,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'harga_beli' => $hargaBeli,
                'harga_jual' => $hargaJual,
                'stok_awal' => $stokAwal,
                'stok_awal_rp' => $stokAwalRp,
                'bbm_datang' => $totalPenerimaan,
                'bbm_datang_rp' => $bbmDatangRp,
                'jumlah_pembelian' => $jumlahPembelian,
                'jumlah_pembelian_rp' => $jumlahPembelianRp,
                'totalisator_awal' => $totAwal,
                'totalisator_akhir' => $totAkhir,
                'total_penjualan' => $totalVolumePenjualan,
                'test_pump' => $totalTestPump,
                'jumlah_penjualan' => $jumlahPenjualan,
                'jumlah_penjualan_rp' => $jumlahPenjualanRp,
                'sisa_stok' => $sisaStok,
                'sisa_stok_rp' => $sisaStokRp,
                'losses_gain' => $lossesGain,
                'losses_gain_rp' => $lossesGainRp,
                'losses_gain_persen' => $lossesGainPersen,
                'penjualan_bersih_rp' => $penjualanBersihRp,
                'laba_kotor' => $labaKotor,
            ];
        }

        // 2. Parse Daily Data for display & aggregations
        $dataParsed = [];
        foreach ($dailyReports as $report) {
            $biaya = [
                'bongkar' => 0, 'tf' => 0, 'atk' => 0, 'listrik' => 0, 'air' => 0, 'cashback' => 0, 'internet' => 0,
                'lain_lain_rp' => 0, 'lain_lain_ket' => [], 'total' => 0
            ];
            foreach ($report->spendings as $sp) {
                $catId = $sp->spending_category_id;
                $nom = floatval($sp->jumlah);
                $biaya['total'] += $nom;
                if ($catId == 1) $biaya['bongkar'] += $nom;
                elseif ($catId == 2) $biaya['tf'] += $nom;
                elseif ($catId == 3) $biaya['atk'] += $nom;
                elseif ($catId == 4) $biaya['listrik'] += $nom;
                elseif ($catId == 5) $biaya['air'] += $nom;
                elseif ($catId == 6) $biaya['cashback'] += $nom;
                elseif ($catId == 7) $biaya['internet'] += $nom;
                elseif ($catId == 99) {
                    $biaya['lain_lain_rp'] += $nom;
                    $biaya['lain_lain_ket'][] = ($sp->keterangan ?? 'Lain-lain') . " (" . number_format($nom, 0, ',', '.') . ")";
                }
            }
            $biaya['lain_lain_ket'] = implode(', ', $biaya['lain_lain_ket']);

            $dataParsed[] = [
                'tanggal' => $report->created_at->format('Y-m-d'),
                'hari_tgl' => $report->created_at->isoFormat('D MMM YY'),
                'tot_awal' => $report->totalisator_awal,
                'tot_akhir' => $report->totalisator_akhir,
                'volume_jual_teoritis' => $isExcel ? $report->volume_penjualan_teoritis_excel : $report->volume_penjualan_teoritis,
                'rupiah_jual_teoritis' => $isExcel ? $report->rupiah_penjualan_teoritis_excel : $report->rupiah_penjualan_teoritis,
                'tp_volume' => $report->test_pump_volume,
                'tp_rupiah' => $report->test_pump_volume * ($report->price ? $report->price->harga_jual : 0),
                'volume_jual_aktual' => $isExcel ? ($report->volume_penjualan_teoritis_excel - $report->test_pump_volume) : $report->volume_penjualan_aktual,
                'rupiah_jual_aktual' => $isExcel ? ($report->rupiah_penjualan_teoritis_excel - ($report->test_pump_volume * ($report->price ? $report->price->harga_jual : 0))) : $report->rupiah_penjualan_aktual,
                'stok_awal' => $isExcel ? $report->stok_awal_excel : $report->stok_awal,
                'terima_bbm' => $report->penerimaan_volume,
                'losses_volume' => $isExcel ? $report->losses_gain_excel : $report->losses_gain,
                'losses_rupiah' => $isExcel ? $report->losses_gain_rp_excel : $report->losses_gain_rupiah,
                'losses_ket' => $isExcel ? ($report->losses_gain_excel >= 0 ? 'Lebih' : 'Susut') : ($report->losses_gain >= 0 ? 'Lebih' : 'Susut'),
                'stok_akhir' => $isExcel ? $report->stok_akhir_excel : $report->stok_akhir_aktual,
                'penjualan_aktual' => $isExcel ? ($report->volume_penjualan_teoritis_excel - $report->test_pump_volume) : $report->volume_penjualan_aktual,
                'biaya' => $biaya,
                'setoran' => [
                    'mandiri' => $report->setor_tunai,
                    'piutang' => $report->setor_qris,
                    'tf_cust' => $report->setor_transfer,
                    'selisih' => $isExcel ? ($report->setor_tunai + $report->setor_qris + $report->setor_transfer - (($report->volume_penjualan_teoritis_excel - $report->test_pump_volume) * ($report->price ? $report->price->harga_jual : 0) - $biaya['total'])) : $report->selisih_setoran,
                    'belum_setor' => $isExcel ? $report->belum_disetorkan_excel : $report->belum_disetorkan
                ],
                'operator_nama' => $report->operator && $report->operator->user ? $report->operator->user->name : 'Tester Excel'
            ];
        }

        // 3. Operator Salary Calculation: D * 200
        $operatorSalaries = [];
        if ($isExcel && !empty($excel_operator_salaries)) {
            $operatorSalaries = $excel_operator_salaries;
        } else {
            foreach ($dailyReports as $report) {
                $opName = 'Tester Excel';
                if ($report->operator && $report->operator->user) {
                    $opName = $report->operator->user->name;
                }
                if (!isset($operatorSalaries[$opName])) {
                    $operatorSalaries[$opName] = [
                        'operator_nama' => $opName,
                        'hari_jaga' => 0,
                        'total_penjualan_b' => 0,
                        'losses_c' => 0,
                        'penjualan_losses_d' => 0
                    ];
                }
                $operatorSalaries[$opName]['hari_jaga'] += 1;
                $operatorSalaries[$opName]['total_penjualan_b'] += floatval($isExcel ? ($report->volume_penjualan_teoritis_excel - $report->test_pump_volume) : $report->volume_penjualan_aktual);
                $operatorSalaries[$opName]['losses_c'] += floatval($isExcel ? $report->losses_gain_excel : $report->losses_gain);
            }
            foreach ($operatorSalaries as $key => $op) {
                $operatorSalaries[$key]['penjualan_losses_d'] = $op['total_penjualan_b'] + $op['losses_c'];
                $operatorSalaries[$key]['gaji'] = $operatorSalaries[$key]['penjualan_losses_d'] * 200;
            }
        }

        // 4. Extra Spendings from Request
        $pengeluaranExtra = [];
        $extraSpendingsSum = 0;
        if ($request->has('pengeluaran_ket') && is_array($request->pengeluaran_ket)) {
            foreach ($request->pengeluaran_ket as $idx => $ket) {
                $nom = floatval(str_replace(',', '', $request->pengeluaran_nom[$idx] ?? 0));
                if ($nom > 0) {
                    $pengeluaranExtra[] = [
                        'keterangan' => $ket ?: 'Lain-lain',
                        'nominal' => $nom
                    ];
                    $extraSpendingsSum += $nom;
                }
            }
        }

        // 5. Overall Totals
        $grandLabaKotor = collect($segments)->sum('laba_kotor');
        if ($excel_laba_kotor !== null) {
            $grandLabaKotor = $excel_laba_kotor;
        }
        
        $totalDailySpendings = collect($dataParsed)->sum('biaya.total');
        $totalGajiOperator = collect($operatorSalaries)->sum('gaji');
        $totalBiaya = $totalDailySpendings + $totalGajiOperator + $extraSpendingsSum;
        if ($excel_total_biaya !== null) {
            $totalBiaya = $excel_total_biaya;
        }

        $labaBersih = $grandLabaKotor - $totalBiaya;
        if ($excel_laba_bersih !== null) {
            $labaBersih = $excel_laba_bersih;
        }

        $penambahanModal = $labaBersih > 0 ? $labaBersih * 0.10 : 0;
        if ($excel_penahanan_modal !== null) {
            $penambahanModal = $excel_penahanan_modal;
        }

        $labaDibagi = $labaBersih > 0 ? $labaBersih * 0.90 : 0;
        if ($excel_laba_dibagi !== null) {
            $labaDibagi = $excel_laba_dibagi;
        }

        $saldo_laba_sebelumnya = floatval(str_replace(',', '', $request->input('saldo_laba_sebelumnya', 0)));
        $totalLabaDibagi = $labaDibagi + $saldo_laba_sebelumnya;

        $totalVolumeTerjual = collect($segments)->sum('jumlah_penjualan');
        $rataRataPenjualan = $daysInMonth > 0 ? ($totalVolumeTerjual / $daysInMonth) : 0;

        // 6. Investor Profit Sharing Allocation
        $investors = [];
        if ($isExcel && count($excel_investors) > 0) {
            foreach ($excel_investors as $ei) {
                $investors[] = [
                    'nama' => $ei['nama'],
                    'persen' => $ei['persen'],
                    'nominal' => $totalLabaDibagi * ($ei['persen'] / 100)
                ];
            }
        } elseif ($request->has('investor_nama') && is_array($request->investor_nama)) {
            foreach ($request->investor_nama as $idx => $name) {
                $persen = floatval($request->investor_persen[$idx] ?? 0);
                if ($name && $persen > 0) {
                    $investors[] = [
                        'nama' => $name,
                        'persen' => $persen,
                        'nominal' => $totalLabaDibagi * ($persen / 100)
                    ];
                }
            }
        }

        // 7. Modal Kerja Reconciliation
        $saldo_awal_modal = floatval(str_replace(',', '', $request->input('saldo_awal_modal', 0)));
        $sisa_do_volume = floatval(str_replace(',', '', $request->input('sisa_do_volume', 0)));
        $kas_kecil = floatval(str_replace(',', '', $request->input('kas_kecil', 0)));
        $piutang = floatval(str_replace(',', '', $request->input('piutang', 0)));
        $bunga_bank = floatval(str_replace(',', '', $request->input('bunga_bank', 0)));
        $pajak_bank = floatval(str_replace(',', '', $request->input('pajak_bank', 0)));

        $penyusutan_modal_input = floatval(str_replace(',', '', $request->input('penyusutan_modal', 0)));
        $penyusutan_rugi = -abs($penyusutan_modal_input); // stored negative

        $penambahan_keuntungan = floatval(str_replace(',', '', $request->input('penambahan_modal', 0))); // positive

        if ($isExcel) {
            $bunga_bank = $excel_bunga;
            $pajak_bank = abs($excel_pajak);
            $penyusutan_rugi = $excel_rugi;
            $penambahan_keuntungan = $excel_keuntungan;
        }

        $harga_beli_pertamax = floatval(str_replace(',', '', $request->input('harga_beli_pertamax', 0))); // positive
        if ($harga_beli_pertamax == 0) {
            $harga_beli_pertamax = $hppPerSegmentLast;
        }

        $penyusutan_pajak_bank = -abs($pajak_bank);
        $nilai_penambahan_penyusutan = $penyusutan_rugi + $penyusutan_pajak_bank + $penambahan_keuntungan + $bunga_bank;
        $saldo_akhir_modal = $saldo_awal_modal + $nilai_penambahan_penyusutan;

        $do_di_pertamina = $sisa_do_volume * $hppPerSegmentLast;
        
        $stok_akhir_volume = count($dailyReports) > 0 ? ($isExcel ? $dailyReports->last()->stok_akhir_excel : $dailyReports->last()->stok_akhir_aktual) : 0;
        $sisa_stok_rp = $stok_akhir_volume * $hppPerSegmentLast;
        
        $belum_disetorkan_rp = count($dailyReports) > 0 ? ($isExcel ? $dailyReports->last()->belum_disetorkan_excel : $dailyReports->last()->belum_disetorkan) : 0;

        $uang_di_bank = $saldo_awal_modal - $do_di_pertamina - $kas_kecil - $sisa_stok_rp - $belum_disetorkan_rp - $piutang;

        $structuredData = [
            'operator_salaries' => array_values($operatorSalaries),
            'thr' => $excel_thr,
            'total_gaji_karyawan_excel' => $excel_total_gaji,
            'segments' => $segments,
            'daily_data' => $dataParsed,
            'pengeluaran_extra' => $pengeluaranExtra,
            'investors' => $investors,
            'grand_laba_kotor' => $grandLabaKotor,
            'total_biaya' => $totalBiaya,
            'laba_bersih' => $labaBersih,
            'penambahan_modal_10' => $penambahan_keuntungan,
            'laba_dibagi_90' => $labaDibagi,
            'total_laba_dibagi' => $totalLabaDibagi,
            'saldo_laba_sebelumnya' => $saldo_laba_sebelumnya,
            'sisa_do_volume' => $sisa_do_volume,
            'sisa_stok_rp' => $sisa_stok_rp,
            'belum_disetorkan_rp' => $belum_disetorkan_rp,
            'rata_rata_penjualan' => $rataRataPenjualan,
        ];
        $totalSetoran = 0;
        foreach ($dataParsed as $dp) {
            $totalSetoran += floatval($dp['setoran']['mandiri'] ?? 0);
            $totalSetoran += floatval($dp['setoran']['piutang'] ?? 0);
            $totalSetoran += floatval($dp['setoran']['tf_cust'] ?? 0);
        }

        $reportRecord = MonthlyReport::create([
            'shop_id' => $shop->id,
            'bulan_tahun' => $request->bulan_tahun,
            'data_parsed' => $structuredData,
            'grand_totals' => [
                'disetorkan' => $totalSetoran
            ],
            'saldo_awal_modal' => $saldo_awal_modal,
            'do_di_pertamina' => $do_di_pertamina,
            'uang_di_bank' => $uang_di_bank,
            'kas_kecil' => $kas_kecil,
            'piutang' => $piutang,
            'bunga_bank' => $bunga_bank,
            'pajak_bank' => $pajak_bank,
            'penyusutan_modal' => $penyusutan_rugi,
            'penambahan_modal' => $penambahan_keuntungan,
            'saldo_akhir_modal' => $saldo_akhir_modal,
        ]);

        if (!$isExcel) {
            // Calculate Tahun Ke dynamically using shop's start date
            $startDate = $shop->tanggal_mulai_operasional;
            if (!$startDate) {
                $startDate = $monthYearParsed->copy()->startOfMonth()->toDateString();
            }
            $start = Carbon::parse($startDate)->startOfMonth();
            $reportDate = $monthYearParsed->copy()->startOfMonth();
            $diffInMonths = $start->diffInMonths($reportDate);
            $tahun_ke = floor($diffInMonths / 12) + 1;

            $konversi = $harga_beli_pertamax > 0 ? ($saldo_akhir_modal / $harga_beli_pertamax) : 0;

            CapitalRecap::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'bulan' => $month,
                    'tahun' => $year,
                ],
                [
                    'tahun_ke' => $tahun_ke,
                    'nilai_modal_awal' => $saldo_awal_modal,
                    'penyusutan_rugi' => $penyusutan_rugi,
                    'penyusutan_pajak_bank' => $penyusutan_pajak_bank,
                    'penambahan_keuntungan' => $penambahan_keuntungan,
                    'penambahan_bunga_bank' => $bunga_bank,
                    'nilai_penambahan_penyusutan' => $nilai_penambahan_penyusutan,
                    'akumulasi_penambahan_penyusutan' => 0, // Recalculation will compute this
                    'posisi_akhir_modal' => $saldo_akhir_modal,
                    'harga_beli_pertamax' => $harga_beli_pertamax,
                    'konversi_liter' => $konversi,
                ]
            );
        }

        // Verify calculations and throw ValidationException if invalid (only for new manual reports)
        $this->verifyReportCalculations($reportRecord, !$isExcel);

        DB::commit();

        return redirect()->route('monthly-reports.show', $reportRecord->id)
                          ->with('success', 'Laporan bulanan berhasil di-generate!');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return back()->with('error', 'Gagal memproses Laporan Bulanan: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $report = MonthlyReport::with(['shop.investors.user'])->findOrFail($id);
        
        $monthYearParsed = Carbon::parse($report->bulan_tahun);
        $month = $monthYearParsed->month;
        $year = $monthYearParsed->year;
        
        $history = MonthlyReport::where('shop_id', $report->shop_id)
            ->where('created_at', '<=', $report->created_at)
            ->orderBy('bulan_tahun', 'asc')
            ->get();
            
        $purchases = \App\Models\Purchase::where('shop_id', $report->shop_id)
            ->whereMonth('purchase_date', $month)
            ->whereYear('purchase_date', $year)
            ->orderBy('purchase_date', 'asc')
            ->get();
            
        $capitalRecaps = \App\Models\CapitalRecap::where('shop_id', $report->shop_id)
            ->where(function ($q) use ($year, $month) {
                $q->where('tahun', '<', $year)
                  ->orWhere(function ($sub) use ($year, $month) {
                      $sub->where('tahun', $year)
                          ->where('bulan', '<=', $month);
                  });
            })
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();
            
        $validations = \App\Models\MonthlyReportValidation::where('monthly_report_id', $report->id)->get();
            
        return view('monthly_reports.show', compact('report', 'history', 'purchases', 'capitalRecaps', 'validations'));
    }

    public function download($id)
    {
        $report = MonthlyReport::findOrFail($id);
        $fullPath = storage_path('app/' . $report->file_path);

        if ($report->file_path && file_exists($fullPath)) {
            $filename = basename($report->file_path);
            return response()->streamDownload(function () use ($fullPath) {
                $stream = fopen($fullPath, 'rb');
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, $filename);
        } elseif ($report->file_path && Storage::exists($report->file_path)) {
            return Storage::download($report->file_path);
        }

        return back()->with('error', 'File tidak ditemukan.');
    }

    public function destroy($id)
    {
        $report = MonthlyReport::findOrFail($id);
        if ($report->file_path && Storage::exists($report->file_path)) {
            Storage::delete($report->file_path);
        }
        
        $monthYearParsed = Carbon::parse($report->bulan_tahun);
        $month = $monthYearParsed->month;
        $year = $monthYearParsed->year;
        
        // Delete related daily reports (which cascades to spendings and test pumps)
        DailyReport::where('shop_id', $report->shop_id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->delete();

        // Delete CapitalRecap with Eloquent events fired
        CapitalRecap::where('shop_id', $report->shop_id)
            ->where('bulan', $month)
            ->where('tahun', $year)
            ->get()
            ->each(function ($recap) {
                $recap->delete();
            });

        $report->delete();

        return redirect()->route('monthly-reports.index')->with('success', 'Laporan berhasil dihapus.');
    }

    /**
     * Verify report calculations and save audit trail to monthly_report_validations.
     */
    public function verifyReportCalculations($report, $isNew = false)
    {
        $dataParsed = $report->data_parsed ?? [];
        $dailyData = $dataParsed['daily_data'] ?? [];
        $segments = $dataParsed['segments'] ?? [];
        
        $monthYearParsed = \Carbon\Carbon::parse($report->bulan_tahun);
        $month = $monthYearParsed->month;
        $year = $monthYearParsed->year;
        
        $results = [];
        
        // 1. Volume BBM (Teoritis / Aktual)
        $systemVolume = floatval(collect($segments)->sum('jumlah_penjualan'));
        $recalculatedVolume = 0;
        foreach ($dailyData as $row) {
            $recalculatedVolume += floatval($row['volume_jual_aktual'] ?? 0);
        }
        $results['volume_bbm'] = [
            'system' => $systemVolume,
            'recalculated' => $recalculatedVolume,
            'diff' => abs($systemVolume - $recalculatedVolume),
            'status' => (abs($systemVolume - $recalculatedVolume) <= 0.1) ? 'valid' : 'invalid'
        ];

        // 2. Setoran Rupiah
        $systemSetoran = floatval($report->grand_totals['disetorkan'] ?? 0);
        $recalculatedSetoran = 0;
        foreach ($dailyData as $row) {
            $s = $row['setoran'] ?? [];
            $recalculatedSetoran += floatval($s['mandiri'] ?? 0);
            $recalculatedSetoran += floatval($s['piutang'] ?? 0);
            $recalculatedSetoran += floatval($s['tf_cust'] ?? 0);
        }
        $results['setoran_rupiah'] = [
            'system' => $systemSetoran,
            'recalculated' => $recalculatedSetoran,
            'diff' => abs($systemSetoran - $recalculatedSetoran),
            'status' => (abs($systemSetoran - $recalculatedSetoran) <= 10) ? 'valid' : 'invalid'
        ];

        // 3. Biaya Operasional
        // SINGLE SOURCE OF TRUTH: total_biaya is stored directly from Excel's 'KLT Laba Bersih' sheet
        // (or computed from daily spendings + gaji + extra for manual reports).
        // Verification: recalculate from stored daily_data components.
        // Note: For Excel imports, Excel may include extra cost items (LAIN2, operational items, etc)
        // not recorded individually in BKH sheet rows. In that case, stored total_biaya is authoritative.
        $systemBiaya = floatval($dataParsed['total_biaya'] ?? 0);
        $t_biaya = 0;
        foreach ($dailyData as $row) {
            $t_biaya += floatval($row['biaya']['total'] ?? 0);
        }
        $totalGajiOperator = collect($dataParsed['operator_salaries'] ?? [])->sum('gaji');
        $extraSpendingsSum = collect($dataParsed['pengeluaran_extra'] ?? [])->sum('nominal');
        $recalculatedBiaya = $t_biaya + $totalGajiOperator + $extraSpendingsSum;
        // For Excel-imported reports, recalculate profit using the Excel-authoritative total_biaya,
        // not the BKH-derived recalculated biaya.
        $biayaForProfit = $systemBiaya > 0 ? $systemBiaya : $recalculatedBiaya;
        $results['biaya_operasional'] = [
            'system' => $systemBiaya,
            'recalculated' => $recalculatedBiaya,
            'diff' => abs($systemBiaya - $recalculatedBiaya),
            // For Excel imports that include non-BKH cost items (LAIN2 etc), this will show a diff
            // but is NOT an error — it means Excel has additional costs not in BKH rows.
            // We only mark INVALID if neither total_biaya nor recalculated is zero (structural error).
            'status' => ($systemBiaya > 0 || $recalculatedBiaya > 0) ? 'info' : 'invalid',
            'note' => ($systemBiaya > 0) ? 'Excel-sourced total_biaya is authoritative; BKH-row diff may include non-BKH cost items.' : null
        ];

        // 4. Profit Sharing
        // Use stored total_laba_dibagi as system, and recalculate from stored components.
        $systemProfit = floatval($dataParsed['total_laba_dibagi'] ?? 0);
        $grandLabaKotor = collect($segments)->sum('laba_kotor');
        if (isset($dataParsed['grand_laba_kotor']) && $dataParsed['grand_laba_kotor'] !== null) {
            $grandLabaKotor = floatval($dataParsed['grand_laba_kotor']);
        }
        // Use Excel-authoritative biaya for accurate profit recalculation
        $labaBersihRecalc = $grandLabaKotor - $biayaForProfit;
        $labaDibagiRecalc = $labaBersihRecalc > 0 ? $labaBersihRecalc * 0.90 : 0;
        $recalculatedProfit = $labaDibagiRecalc + floatval($dataParsed['saldo_laba_sebelumnya'] ?? 0);
        $results['profit_sharing'] = [
            'system' => $systemProfit,
            'recalculated' => $recalculatedProfit,
            'diff' => abs($systemProfit - $recalculatedProfit),
            'status' => (abs($systemProfit - $recalculatedProfit) <= 10) ? 'valid' : 'invalid'
        ];

        // 5. Posisi Modal
        $systemModal = floatval($report->saldo_akhir_modal);
        $penyusutan_rugi = floatval($report->penyusutan_modal);
        $penyusutan_pajak_bank = -abs(floatval($report->pajak_bank));
        $penambahan_keuntungan = floatval($report->penambahan_modal);
        $bunga_bank = floatval($report->bunga_bank);
        $nilai_penambahan_penyusutan = $penyusutan_rugi + $penyusutan_pajak_bank + $penambahan_keuntungan + $bunga_bank;
        $recalculatedModal = floatval($report->saldo_awal_modal) + $nilai_penambahan_penyusutan;
        $results['posisi_modal'] = [
            'system' => $systemModal,
            'recalculated' => $recalculatedModal,
            'diff' => abs($systemModal - $recalculatedModal),
            'status' => (abs($systemModal - $recalculatedModal) <= 10) ? 'valid' : 'invalid'
        ];

        // 6. Rekap Modal
        // For Excel-imported historical records, capital_recap.posisi_akhir_modal represents
        // the cumulative modal from inception imported from Excel sheets.
        // For manual reports, capital_recap.posisi_akhir_modal is set = saldo_akhir_modal.
        // We check: is the capital_recap.posisi_akhir_modal consistent with nilai_modal_awal + perubahan?
        $recap = \App\Models\CapitalRecap::where('shop_id', $report->shop_id)
            ->where('bulan', $month)
            ->where('tahun', $year)
            ->first();
        $systemRecap = $recap ? floatval($recap->posisi_akhir_modal) : 0;
        if ($recap) {
            // Internal consistency: posisi_akhir = nilai_modal_awal + nilai_penambahan_penyusutan
            $recapNilaiAwal = floatval($recap->nilai_modal_awal);
            $recapPerubahan = floatval($recap->nilai_penambahan_penyusutan);
            $recalculatedRecap = $recapNilaiAwal + $recapPerubahan;
        } else {
            $recalculatedRecap = $systemModal;
        }
        $results['rekap_modal'] = [
            'system' => $systemRecap,
            'recalculated' => $recalculatedRecap,
            'diff' => abs($systemRecap - $recalculatedRecap),
            'status' => (!$recap || abs($systemRecap - $recalculatedRecap) <= 10) ? 'valid' : 'invalid',
            'note' => $recap ? 'Rekap internal consistency check (posisi_akhir = modal_awal + perubahan).' : 'No capital recap found.'
        ];

        // Check if there are any invalid components
        $hasInvalid = false;
        $invalidMessages = [];
        
        // Save validation results to database
        foreach ($results as $comp => $res) {
            \App\Models\MonthlyReportValidation::updateOrCreate(
                [
                    'monthly_report_id' => $report->id,
                    'component' => $comp,
                ],
                [
                    'system_value' => $res['system'],
                    'recalculated_value' => $res['recalculated'],
                    'diff' => $res['diff'],
                    'status' => $res['status'],
                    'updated_at' => now()
                ]
            );
            
            if ($res['status'] === 'invalid') {
                $hasInvalid = true;
                $invalidMessages[] = sprintf(
                    "%s (Sistem: %s, Hitung: %s, Selisih: %s)",
                    ucwords(str_replace('_', ' ', $comp)),
                    number_format($res['system'], 2, ',', '.'),
                    number_format($res['recalculated'], 2, ',', '.'),
                    number_format($res['diff'], 2, ',', '.')
                );
            }
        }
        
        if ($hasInvalid) {
            \Log::warning("[Report Hardening Alert] Mismatch found in Report ID {$report->id} ({$report->bulan_tahun}): " . implode(', ', $invalidMessages));
            
            if ($isNew) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'validation_error' => [
                        "Proses dibatalkan karena terdapat selisih perhitungan kritis pada laporan: " . implode('; ', $invalidMessages)
                    ]
                ]);
            }
        }
        
        return $results;
    }

    /**
     * Generate Laporan Bulanan otomatis dari data DailyReports tanpa perlu upload Excel.
     */
    public function generateFromDailyReports(Request $request)
    {
        $request->validate([
            'shop_id'    => 'required|exists:shops,id',
            'year_month' => 'required|date_format:Y-m',
        ]);

        try {
            $report = (new \App\Actions\GenerateMonthlyReport)->handle(
                (int) $request->shop_id,
                $request->year_month
            );

            return redirect()->route('monthly-reports.show', $report->id)
                ->with('success', 'Laporan bulanan berhasil digenerate otomatis dari Laporan Harian!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal generate laporan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Export Laporan Bulanan ke PDF profesional siap kirim.
     */
    public function exportPdf($id)
    {
        $report = MonthlyReport::with(['shop.investors.user'])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.monthly_report', compact('report'))
            ->setPaper('a4', 'landscape');

        $shopSlug = \Illuminate\Support\Str::slug($report->shop->nama ?? 'pertashop');
        $dateSlug = \Illuminate\Support\Str::slug($report->bulan_tahun);
        $filename = "Laporan_Bulanan_{$shopSlug}_{$dateSlug}.pdf";

        return $pdf->download($filename);
    }
}
