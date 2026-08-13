<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CapitalRecap;
use App\Models\Shop;
use App\Models\DailyReport;
use App\Models\MonthlyReport;
use App\Models\Price;
use App\Models\Spending;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CapitalRecapController extends Controller
{
    public function index(Request $request)
    {
        $shops = Shop::all();
        $query = CapitalRecap::with('shop')->orderBy('shop_id')->orderBy('tahun')->orderBy('bulan');
        
        if ($request->has('shop_id') && $request->shop_id != '') {
            $query->where('shop_id', $request->shop_id);
        }
        
        $recaps = $query->get();
        return view('capital_recaps.index', compact('recaps', 'shops'));
    }

    public function importForm()
    {
        $shops = Shop::all();
        return view('capital_recaps.import', compact('shops'));
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'shop_id'    => 'required|exists:shops,id',
            'dari_bulan' => 'required|string', // format: YYYY-MM
            'sampai_bulan' => 'required|string',
            'modal_awal'   => 'required|numeric|min:0',
            'excel_file'   => 'required|file|mimes:xlsx,xls',
        ]);

        $shop         = Shop::findOrFail($request->shop_id);
        $dariCarbon   = Carbon::parse($request->dari_bulan . '-01')->startOfMonth();
        $sampaiCarbon = Carbon::parse($request->sampai_bulan . '-01')->startOfMonth();

        if ($dariCarbon->gt($sampaiCarbon)) {
            return back()->withErrors(['dari_bulan' => 'Dari Bulan tidak boleh lebih besar dari Sampai Bulan.'])->withInput();
        }

        // Indonesian month names → number
        $bulanIndo = [
            'januari' => 1, 'februari' => 2, 'maret' => 3,
            'april' => 4, 'mei' => 5, 'juni' => 6,
            'juli' => 7, 'agustus' => 8, 'september' => 9,
            'oktober' => 10, 'november' => 11, 'desember' => 12,
            // English fallback
            'january' => 1, 'february' => 2, 'march' => 3,
            'may' => 5, 'june' => 6, 'july' => 7,
            'august' => 8, 'october' => 10, 'december' => 12,
        ];

        try {
            $spreadsheet = IOFactory::load($request->file('excel_file')->getRealPath());
            $sheetNames  = $spreadsheet->getSheetNames();

            // Build a map: [bulan => tahun] => sheetName
            $sheetMap = [];
            foreach ($sheetNames as $name) {
                $lower = strtolower(trim($name));

                // Try to detect month and year from sheet name
                // Patterns: "Juli 2025", "Juli-2025", "07-2025", "2025-07"
                $detectedMonth = null;
                $detectedYear  = null;

                // Pattern: Indonesian/English month name + year
                foreach ($bulanIndo as $monthName => $monthNum) {
                    if (str_contains($lower, $monthName)) {
                        $detectedMonth = $monthNum;
                        // Extract year (4 consecutive digits)
                        if (preg_match('/\b(20\d{2})\b/', $lower, $m)) {
                            $detectedYear = (int)$m[1];
                        }
                        break;
                    }
                }

                // Pattern: MM-YYYY or YYYY-MM (numeric)
                if (!$detectedMonth) {
                    if (preg_match('/\b(\d{1,2})[-\/](\d{4})\b/', $lower, $m)) {
                        $detectedMonth = (int)$m[1];
                        $detectedYear  = (int)$m[2];
                    } elseif (preg_match('/\b(20\d{2})[-\/](\d{1,2})\b/', $lower, $m)) {
                        $detectedYear  = (int)$m[1];
                        $detectedMonth = (int)$m[2];
                    }
                }

                if ($detectedMonth && $detectedYear) {
                    $key = $detectedYear . '-' . str_pad($detectedMonth, 2, '0', STR_PAD_LEFT);
                    $sheetMap[$key] = $name;
                }
            }

            if (empty($sheetMap)) {
                return back()->withErrors(['excel_file' => 'Tidak ada sheet yang dapat dikenali sebagai laporan bulanan. Pastikan nama sheet mengandung nama bulan (contoh: "Juli 2025").'])->withInput();
            }

            DB::beginTransaction();

            $currentModal = floatval($request->modal_awal);
            $processedMonths = 0;
            $skippedSheets   = [];

            // Iterate chronologically through the date range
            $cursor = $dariCarbon->copy();
            while ($cursor->lte($sampaiCarbon)) {
                $key = $cursor->format('Y-m');

                if (!isset($sheetMap[$key])) {
                    $skippedSheets[] = $cursor->translatedFormat('F Y');
                    $cursor->addMonth();
                    continue;
                }

                $sheetName = $sheetMap[$key];
                $sheet     = $spreadsheet->getSheetByName($sheetName);
                $rows      = $sheet->toArray(null, true, true, false);

                $month = $cursor->month;
                $year  = $cursor->year;

                // ── Parse BKH rows ───────────────────────────────────────────
                $dailySummary = $this->parseBkhSheet($rows, $shop, $month, $year, $cursor->copy());

                $labaKotor   = $dailySummary['laba_kotor'];
                $totalBiaya  = $dailySummary['total_biaya'];
                $labaBersih  = $labaKotor - $totalBiaya;
                $penambahan  = $labaBersih > 0 ? round($labaBersih * 0.10) : 0;
                $labaDibagi  = $labaBersih > 0 ? round($labaBersih * 0.90) : 0;
                $penyusutan  = $labaBersih < 0 ? $labaBersih : 0;

                $nilaiPenambahan = $penambahan + $penyusutan; // penyusutan is negative
                $posisiAkhir     = $currentModal + $nilaiPenambahan;

                $hargaBeli = $dailySummary['harga_beli_last'] > 0 ? $dailySummary['harga_beli_last'] : 0;
                $konversi  = $hargaBeli > 0 ? round($posisiAkhir / $hargaBeli, 2) : 0;

                // ── Calculate tahun_ke ────────────────────────────────────────
                $startDate = $shop->tanggal_mulai_operasional;
                if (!$startDate) {
                    $startDate = $cursor->copy()->startOfMonth()->toDateString();
                }
                $startCarbon  = Carbon::parse($startDate)->startOfMonth();
                $diffInMonths = $startCarbon->diffInMonths($cursor->copy()->startOfMonth());
                $tahunKe      = (int)floor($diffInMonths / 12) + 1;

                // ── Save / Update CapitalRecap ────────────────────────────────
                CapitalRecap::withoutEvents(function () use (
                    $shop, $month, $year, $tahunKe, $currentModal,
                    $penyusutan, $penambahan, $nilaiPenambahan,
                    $posisiAkhir, $hargaBeli, $konversi
                ) {
                    CapitalRecap::updateOrCreate(
                        ['shop_id' => $shop->id, 'bulan' => $month, 'tahun' => $year],
                        [
                            'tahun_ke'                      => $tahunKe,
                            'nilai_modal_awal'              => $currentModal,
                            'penyusutan_rugi'               => $penyusutan,
                            'penyusutan_pajak_bank'         => 0,
                            'penambahan_keuntungan'         => $penambahan,
                            'penambahan_bunga_bank'         => 0,
                            'nilai_penambahan_penyusutan'   => $penambahan + $penyusutan,
                            'akumulasi_penambahan_penyusutan' => 0, // will be recalculated
                            'posisi_akhir_modal'            => $posisiAkhir,
                            'harga_beli_pertamax'           => $hargaBeli,
                            'konversi_liter'                => $konversi,
                        ]
                    );
                });

                // ── Save / Update MonthlyReport ───────────────────────────────
                $structuredData = [
                    'segments'         => $dailySummary['segments'],
                    'daily_data'       => $dailySummary['daily_data'],
                    'operator_salaries' => [],
                    'pengeluaran_extra' => [],
                    'investors'        => [],
                    'grand_laba_kotor' => $labaKotor,
                    'total_biaya'      => $totalBiaya,
                    'laba_bersih'      => $labaBersih,
                    'penambahan_modal_10' => $penambahan,
                    'laba_dibagi_90'   => $labaDibagi,
                    'total_laba_dibagi' => $labaDibagi,
                    'saldo_laba_sebelumnya' => 0,
                    'sisa_do_volume'   => 0,
                    'sisa_stok_rp'     => 0,
                    'belum_disetorkan_rp' => 0,
                    'rata_rata_penjualan' => $dailySummary['rata_rata_penjualan'],
                    'thr' => 0,
                    'total_gaji_karyawan_excel' => 0,
                ];

                $totalSetoran = $dailySummary['total_setoran'];

                MonthlyReport::updateOrCreate(
                    [
                        'shop_id'     => $shop->id,
                        'bulan_tahun' => $cursor->format('Y-m'),
                    ],
                    [
                        'data_parsed'       => $structuredData,
                        'grand_totals'      => ['disetorkan' => $totalSetoran],
                        'saldo_awal_modal'  => $currentModal,
                        'do_di_pertamina'   => 0,
                        'uang_di_bank'      => 0,
                        'kas_kecil'         => 0,
                        'piutang'           => 0,
                        'bunga_bank'        => 0,
                        'pajak_bank'        => 0,
                        'penyusutan_modal'  => $penyusutan,
                        'penambahan_modal'  => $penambahan,
                        'saldo_akhir_modal' => $posisiAkhir,
                    ]
                );

                // Modal berantai: posisi akhir bulan ini = modal awal bulan berikutnya
                $currentModal = $posisiAkhir;
                $processedMonths++;
                $cursor->addMonth();
            }

            // Final recalculation to fix akumulasi
            CapitalRecap::recalculateForShop($shop->id);

            DB::commit();

            $message = "Berhasil mengimpor {$processedMonths} bulan data untuk {$shop->nama}.";
            if (!empty($skippedSheets)) {
                $message .= ' Sheet tidak ditemukan untuk: ' . implode(', ', $skippedSheets) . '.';
            }

            return redirect()->route('capital-recaps.index', ['shop_id' => $shop->id])
                             ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['excel_file' => 'Gagal memproses Excel: ' . $e->getMessage()])->withInput();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: Parse BKH sheet and return aggregated monthly figures
    // ─────────────────────────────────────────────────────────────────────────
    private function parseBkhSheet(array $rows, Shop $shop, int $month, int $year, Carbon $monthCarbon): array
    {
        $segments        = [];
        $dailyData       = [];
        $totalSetoran    = 0;
        $labaKotor       = 0;
        $totalBiaya      = 0;
        $hargaBeliLast   = 0;

        // Detect column offset (0, 1, or 2) based on header
        $offset = 0;
        $headerRow = $rows[1] ?? [];
        $header1   = strtolower(trim($headerRow[1] ?? ''));
        $header2   = strtolower(trim($headerRow[2] ?? ''));
        if (str_contains($header1, 'tgl') || str_contains($header1, 'tanggal')) {
            $offset = 1;
        } elseif (str_contains($header2, 'tgl') || str_contains($header2, 'tanggal')) {
            $offset = 2;
        }

        $previous_hj   = null;
        $previous_hb   = null;
        $totalPenjualan = 0;
        $daysInMonth   = $monthCarbon->daysInMonth;

        foreach ($rows as $rowIdx => $r) {
            if ($rowIdx <= 1) continue; // skip header rows

            $tgl_str = trim($r[$offset] ?? '');
            if (empty($tgl_str) || !is_numeric($tgl_str)) continue;

            $day = intval($tgl_str);
            if ($day < 1 || $day > 31) continue;

            $vol_teoritis = $this->flexNum($r[5 + $offset] ?? 0);
            $rp_teoritis  = $this->flexNum($r[6 + $offset] ?? 0);
            $tp_vol       = $this->flexNum($r[7 + $offset] ?? 0);
            $stok_awal    = $this->flexNum($r[13 + $offset] ?? 0);
            $terima_bbm   = $this->flexNum($r[15 + $offset] ?? 0);
            $stok_akhir   = $this->flexNum($r[18 + $offset] ?? 0);
            $losses_vol   = $this->flexNum($r[19 + $offset] ?? 0);
            $losses_rp    = $this->flexNum($r[20 + $offset] ?? 0);

            // Detect harga jual & beli
            if ($vol_teoritis > 0 && $rp_teoritis > 0) {
                $hj = round($rp_teoritis / $vol_teoritis);
                // Estimate harga beli as 93% of jual if no price data
                $hb = $hj * 0.93;

                $dbPrice = Price::where('shop_id', $shop->id)
                    ->where('harga_jual', $hj)
                    ->first();
                if ($dbPrice) {
                    $hb = floatval($dbPrice->harga_beli);
                }

                $previous_hj = $hj;
                $previous_hb = $hb;
            } elseif ($previous_hj) {
                $hj = $previous_hj;
                $hb = $previous_hb;
            } else {
                $hj = 0;
                $hb = 0;
            }

            if ($hb > 0) $hargaBeliLast = $hb;

            // Pengeluaran (biaya operasional harian)
            $bongkar  = $this->flexNum($r[24 + $offset] ?? 0);
            $tf       = $this->flexNum($r[25 + $offset] ?? 0);
            $atk      = $this->flexNum($r[26 + $offset] ?? 0);
            $listrik  = $this->flexNum($r[27 + $offset] ?? 0);
            $air      = $this->flexNum($r[28 + $offset] ?? 0);
            $cashback = $this->flexNum($r[29 + $offset] ?? 0);
            $internet = $this->flexNum($r[30 + $offset] ?? 0);
            $lainnya  = $this->flexNum($r[31 + $offset] ?? 0);
            $biayaTotal = $bongkar + $tf + $atk + $listrik + $air + $cashback + $internet + $lainnya;

            $totalBiaya += $biayaTotal;

            // Penjualan aktual (teoritis - test pump)
            $volAktual = max(0, $vol_teoritis - $tp_vol);
            $rpAktual  = $volAktual * $hj;
            $totalPenjualan += $volAktual;

            // Laba kotor kontribusi hari ini
            $labaKotor += ($rpAktual - ($volAktual * $hb));

            // Setoran
            $setor_tunai    = $this->flexNum($r[37 + $offset] ?? 0);
            $setor_qris     = $this->flexNum($r[38 + $offset] ?? 0);
            $setor_transfer = $this->flexNum($r[39 + $offset] ?? 0);
            $totalSetoran  += $setor_tunai + $setor_qris + $setor_transfer;

            $currentDate = $monthCarbon->copy()->setDay($day);

            $dailyData[] = [
                'tanggal'             => $currentDate->format('Y-m-d'),
                'hari_tgl'            => $currentDate->isoFormat('D MMM YY'),
                'tot_awal'            => $this->flexNum($r[3 + $offset] ?? 0),
                'tot_akhir'           => $this->flexNum($r[4 + $offset] ?? 0),
                'volume_jual_teoritis' => $vol_teoritis,
                'rupiah_jual_teoritis' => $rp_teoritis,
                'tp_volume'           => $tp_vol,
                'tp_rupiah'           => $tp_vol * $hj,
                'volume_jual_aktual'  => $volAktual,
                'rupiah_jual_aktual'  => $rpAktual,
                'stok_awal'           => $stok_awal,
                'terima_bbm'          => $terima_bbm,
                'losses_volume'       => $losses_vol,
                'losses_rupiah'       => $losses_rp,
                'losses_ket'          => $losses_vol >= 0 ? 'Lebih' : 'Susut',
                'stok_akhir'          => $stok_akhir,
                'penjualan_aktual'    => $volAktual,
                'biaya'               => [
                    'bongkar'     => $bongkar,
                    'tf'          => $tf,
                    'atk'         => $atk,
                    'listrik'     => $listrik,
                    'air'         => $air,
                    'cashback'    => $cashback,
                    'internet'    => $internet,
                    'lain_lain_rp' => $lainnya,
                    'lain_lain_ket' => '',
                    'total'       => $biayaTotal,
                ],
                'setoran' => [
                    'mandiri'   => $setor_tunai,
                    'piutang'   => $setor_qris,
                    'tf_cust'   => $setor_transfer,
                    'selisih'   => 0,
                    'belum_setor' => 0,
                ],
                'operator_nama' => '-',
            ];
        }

        $rataRata = $daysInMonth > 0 ? $totalPenjualan / $daysInMonth : 0;

        // Build a simple single segment
        $segments = [];
        if (!empty($dailyData)) {
            $first = $dailyData[0];
            $last  = $dailyData[count($dailyData) - 1];
            $segments[] = [
                'segmen_index'       => 1,
                'start_date'         => Carbon::parse($first['tanggal'])->format("d M'y"),
                'end_date'           => Carbon::parse($last['tanggal'])->format("d M'y"),
                'harga_beli'         => $hargaBeliLast,
                'harga_jual'         => $previous_hj ?? 0,
                'stok_awal'          => $first['stok_awal'],
                'stok_awal_rp'       => $first['stok_awal'] * $hargaBeliLast,
                'bbm_datang'         => array_sum(array_column($dailyData, 'terima_bbm')),
                'bbm_datang_rp'      => array_sum(array_column($dailyData, 'terima_bbm')) * $hargaBeliLast,
                'jumlah_pembelian'   => 0,
                'jumlah_pembelian_rp' => 0,
                'totalisator_awal'   => $first['tot_awal'],
                'totalisator_akhir'  => $last['tot_akhir'],
                'total_penjualan'    => $totalPenjualan,
                'test_pump'          => array_sum(array_column($dailyData, 'tp_volume')),
                'jumlah_penjualan'   => $totalPenjualan,
                'jumlah_penjualan_rp' => $totalPenjualan * ($previous_hj ?? 0),
                'sisa_stok'          => $last['stok_akhir'],
                'sisa_stok_rp'       => $last['stok_akhir'] * $hargaBeliLast,
                'losses_gain'        => array_sum(array_column($dailyData, 'losses_volume')),
                'losses_gain_rp'     => array_sum(array_column($dailyData, 'losses_rupiah')),
                'losses_gain_persen' => 0,
                'penjualan_bersih_rp' => $totalPenjualan * ($previous_hj ?? 0),
                'laba_kotor'         => $labaKotor,
            ];
        }

        return [
            'laba_kotor'          => $labaKotor,
            'total_biaya'         => $totalBiaya,
            'total_setoran'       => $totalSetoran,
            'rata_rata_penjualan' => $rataRata,
            'harga_beli_last'     => $hargaBeliLast,
            'segments'            => $segments,
            'daily_data'          => $dailyData,
        ];
    }

    private function flexNum($val): float
    {
        if (is_numeric($val)) return (float)$val;
        $val = trim((string)$val);
        $isNeg = str_starts_with($val, '-') || preg_match('/^\(.*\)$/', $val);
        $val = str_replace(['Rp', ' ', '(', ')', ',-', '-'], '', $val);
        $val = rtrim($val, ',');
        if ($val === '') return 0.0;
        if (preg_match('/,(\d{2})$/', $val)) {
            $val = preg_replace('/,(\d{2})$/', '.$1', $val);
        }
        $val = str_replace(',', '', $val);
        $f = floatval($val);
        return $isNeg ? -$f : $f;
    }
}
