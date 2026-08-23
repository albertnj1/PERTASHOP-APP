<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Shop;
use App\Models\Price;
use App\Models\Investor;

class BackdateExcelSummaryService
{
    /**
     * Parse flexible number from various Indonesian/Excel formats.
     */
    public static function parseFlexibleNumber($val): float
    {
        if (is_numeric($val)) {
            return (float)$val;
        }
        
        $val = trim((string)$val);
        $isNegative = false;
        if (preg_match('/^\(.*\)$/', $val)) {
            $isNegative = true;
        } else if (str_starts_with($val, '-')) {
            $isNegative = true;
        }

        $val = str_replace(["Rp", " ", "(", ")", ",-", "-", "è", "L", "ℓ", "KL"], "", $val);
        $val = rtrim($val, ',');
        if ($val === '') return 0;
        
        if (preg_match('/,(\d{2})$/', $val)) {
            $val = preg_replace('/,(\d{2})$/', '.$1', $val);
        }

        $val = str_replace(",", "", $val);
        $floatVal = floatval($val);
        return $isNegative ? -$floatVal : $floatVal;
    }

    /**
     * Mengekstrak data lengkap 4 Halaman Laporan dari file Excel backdate.
     *
     * @param string $filePath Absolute path file Excel (.xlsx / .xls)
     * @param \App\Models\Shop|null $targetShop Toko target
     * @param string|null $targetPeriod Periode target (YYYY-MM)
     * @return array
     */
    public static function extract(string $filePath, ?Shop $targetShop = null, ?string $targetPeriod = null): array
    {
        $summary = [
            'hal1' => [
                'segments'               => [],
                'grand_total_laba_kotor' => 0,
                'total_liter_terjual'    => 0,
                'rata_rata_omset_harian' => 0,
                'sisa_do_mees'           => [
                    'stok_awal_kl' => 0, 'setor_kl' => 0, 'setoran_tunai' => 0,
                    'jumlah_kl' => 0, 'datang_kl' => 0, 'sisa_kl' => 0, 'harga_beli_1kl' => 0
                ],
                'margin_history'         => [],
                'final_stok_liter'       => 0,
                'final_stok_rp'          => 0,
                'final_harga_beli'       => 0,
            ],
            'hal2' => [
                'pengeluaran_details'    => [
                    'gaji_operator' => 0, 'gaji_admin' => 0, 'biaya_curah' => 0,
                    'biaya_tf' => 0, 'listrik' => 0, 'air' => 0, 'cashback' => 0,
                    'internet' => 0, 'atk' => 0, 'lain_lain' => 0, 'lain_lain_notes' => '',
                    'total_biaya' => 0
                ],
                'total_biaya'            => 0,
                'laba_bersih'            => 0,
                'alokasi_penambahan_modal' => 0,
                'saldo_laba_bersih_90'   => 0,
                'saldo_laba_sebelumnya'  => 0,
                'total_saldo_laba_dibagi' => 0,
                'investor_distributions' => [],
            ],
            'hal3' => [
                'saldo_awal_modal'        => 0,
                'do_di_pertamina'         => 0,
                'uang_di_bank'            => 0,
                'kas_kecil'               => 0,
                'sisa_stok_pertashop_rp'  => 0,
                'hasil_belum_disetor'     => 0,
                'piutang'                 => 0,
                'subtotal_a'              => 0,
                'bunga_bank'              => 0,
                'pajak_bank'              => 0,
                'profit_sharing_dibagi'   => 0,
                'penambahan_keuntungan'   => 0,
                'subtotal_b'              => 0,
                'subtotal_c'              => 0,
                'total_saldo_akhir_modal' => 0,
            ],
            'hal4' => [
                'capital_recaps'          => [],
                'modal_awal_dasar'        => 60000000,
                'total_akumulasi_modal'   => 0,
                'persen_penambahan_modal' => 0,
                'grand_total_modal'       => 60000000,
                'persen_grand_total'      => 100,
            ],
            // Legacy / Summary Metrics
            'totalisator_awal'       => 0,
            'totalisator_akhir'      => 0,
            'jumlah_liter_terjual'   => 0,
            'test_pump'              => ['total_volume' => 0, 'total_rp' => 0, 'details' => []],
            'pembelian_bbm'          => ['total_volume_kl' => 0, 'total_volume_liter' => 0, 'total_nominal' => 0, 'details' => []],
            'stok_akhir'             => 0,
            'total_pengeluaran'      => ['total_rp' => 0, 'category_totals' => [], 'details' => []],
            'total_belum_disetorkan' => ['total_rp' => 0, 'details' => []],
            'matched_sheet_name'     => null,
        ];

        if (!file_exists($filePath)) {
            return $summary;
        }

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            if (method_exists($reader, 'setReadDataOnly')) {
                $reader->setReadDataOnly(true);
            }
            $spreadsheet = $reader->load($filePath);

            // Determine Target Shop & Period
            if (!$targetPeriod) {
                foreach ($spreadsheet->getAllSheets() as $sh) {
                    $p = self::parsePeriodFromSheetName($sh->getTitle());
                    if ($p !== 'Multi-Periode') {
                        $targetPeriod = $p;
                        break;
                    }
                }
            }
            $periodObj = $targetPeriod ? Carbon::parse($targetPeriod . '-01') : Carbon::now();
            $periodMonth = $periodObj->month;
            $periodYear = $periodObj->year;

            // ─────────────────────────────────────────────────────────────
            // 1. PARSE SHEET KLB (STOK, PENJUALAN & LABA KOTOR - HALAMAN 1)
            // ─────────────────────────────────────────────────────────────
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

            // Fallback scan for KLB keywords
            if (!$klbSheet) {
                foreach ($spreadsheet->getAllSheets() as $sh) {
                    $firstRows = $sh->toArray(null, true, false, false);
                    foreach (array_slice($firstRows, 0, 15) as $r) {
                        foreach ($r as $cv) {
                            if (is_string($cv) && preg_match('/Harga Beli|Total Penjualan Bersih|Sisa Stok Akhir/i', $cv)) {
                                $klbSheet = $sh;
                                break 3;
                            }
                        }
                    }
                }
            }

            $segments = [];
            $hargaBeliList = [];
            $hargaJualList = [];
            $omsetHarian = 0;
            $grandLabaKotor = 0;

            if ($klbSheet) {
                $rowsKlb = $klbSheet->toArray(null, true, false, false);

                // Extract Header Prices & Rata-rata Omset Harian
                foreach (array_slice($rowsKlb, 0, 10) as $r) {
                    foreach ($r as $cv) {
                        if (!is_string($cv)) continue;
                        $clean = trim($cv);
                        if (preg_match('/Harga Beli (\d+)\s*[:=]\s*Rp\s*([0-9\.,]+)/i', $clean, $m)) {
                            $hargaBeliList[(int)$m[1]] = self::parseFlexibleNumber($m[2]);
                        }
                        if (preg_match('/Harga Jual (\d+)\s*[:=]\s*Rp\s*([0-9\.,]+)/i', $clean, $m)) {
                            $hargaJualList[(int)$m[1]] = self::parseFlexibleNumber($m[2]);
                        }
                        if (preg_match('/Rata-rata omset Harian[^0-9]*([0-9\.,]+)/i', $clean, $m)) {
                            $omsetHarian = self::parseFlexibleNumber($m[1]);
                        }
                    }
                }

                // Parse Segment Blocks (PEMBELIAN 1, PEMBELIAN 2, dst)
                $currentSegment = null;
                $segIndex = 0;

                foreach ($rowsKlb as $rIdx => $r) {
                    $rowStr = implode(' ', array_filter(array_map('trim', array_map('strval', $r))));
                    $rowStrLow = strtolower($rowStr);

                    if (preg_match('/I\.\s*PEMBELIAN\s*(\d+)/i', $rowStr, $m)) {
                        if ($currentSegment) {
                            $segments[] = $currentSegment;
                        }
                        $segIndex = (int)$m[1];
                        $hBeli = $hargaBeliList[$segIndex] ?? ($hargaBeliList[1] ?? 15334.81);
                        $hJual = $hargaJualList[$segIndex] ?? ($hargaJualList[1] ?? 16150);

                        $currentSegment = [
                            'segmen_index'            => $segIndex,
                            'harga_beli'              => $hBeli,
                            'harga_jual'              => $hJual,
                            'stok_awal'               => 0,
                            'stok_awal_rp'            => 0,
                            'bbm_datang'              => 0,
                            'bbm_datang_rp'           => 0,
                            'jumlah_pembelian'        => 0,
                            'jumlah_pembelian_rp'     => 0,
                            'totalisator_awal'        => 0,
                            'totalisator_akhir'       => 0,
                            'total_penjualan'         => 0,
                            'test_pump'               => 0,
                            'jumlah_penjualan'        => 0,
                            'jumlah_penjualan_rp'     => 0,
                            'sisa_stok_teoretis'      => 0,
                            'sisa_stok_teoretis_rp'   => 0,
                            'losses_gain'             => 0,
                            'losses_gain_persen'      => 0,
                            'losses_gain_rp'          => 0,
                            'stok_akhir_fisik'        => 0,
                            'stok_akhir_cm'           => 0,
                            'stok_akhir_fisik_rp'     => 0,
                            'jumlah_penjualan_bersih' => 0,
                            'laba_kotor'              => 0,
                            'start_datetime_label'    => '',
                            'end_datetime_label'      => '',
                        ];
                    }

                    if ($currentSegment) {
                        // Stok Awal
                        if (str_contains($rowStrLow, 'stok awal') && !str_contains($rowStrLow, 'sisa')) {
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ\s*x\s*Rp\s*([0-9\.,]+)\s*(?:->|&rarr;|\=)?\s*(?:Rp\s*)?([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['stok_awal'] = self::parseFlexibleNumber($m[1]);
                                $currentSegment['stok_awal_rp'] = self::parseFlexibleNumber($m[3]);
                            } elseif (preg_match('/([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['stok_awal'] = self::parseFlexibleNumber($m[1]);
                                $currentSegment['stok_awal_rp'] = $currentSegment['stok_awal'] * $currentSegment['harga_beli'];
                            }
                        }

                        // BBM Datang
                        if (str_contains($rowStrLow, 'bbm datang')) {
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $vol = self::parseFlexibleNumber($m[1]);
                                $currentSegment['bbm_datang'] += $vol;
                                $currentSegment['bbm_datang_rp'] += ($vol * $currentSegment['harga_beli']);
                            }
                        }

                        // Jumlah Pembelian
                        if (str_contains($rowStrLow, 'jumlah pembelian')) {
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['jumlah_pembelian'] = self::parseFlexibleNumber($m[1]);
                            }
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['jumlah_pembelian_rp'] = self::parseFlexibleNumber($m[1]);
                            }
                        }

                        // Totalisator Akhir
                        if (str_contains($rowStrLow, 'totalisator akhir')) {
                            if (preg_match('/\((.*?)\)/', $rowStr, $m)) {
                                $currentSegment['end_datetime_label'] = trim($m[1]);
                            }
                            if (preg_match('/=\s*([0-9\.,]+)/', $rowStr, $m)) {
                                $currentSegment['totalisator_akhir'] = self::parseFlexibleNumber($m[1]);
                            }
                        }

                        // Totalisator Awal
                        if (str_contains($rowStrLow, 'totalisator awal')) {
                            if (preg_match('/\((.*?)\)/', $rowStr, $m)) {
                                $currentSegment['start_datetime_label'] = trim($m[1]);
                            }
                            if (preg_match('/=\s*([0-9\.,]+)/', $rowStr, $m)) {
                                $currentSegment['totalisator_awal'] = self::parseFlexibleNumber($m[1]);
                            }
                        }

                        // Total Penjualan (a-b)
                        if (str_contains($rowStrLow, 'total penjualan') && !str_contains($rowStrLow, 'bersih') && !str_contains($rowStrLow, 'laba')) {
                            if (preg_match('/=\s*([0-9\.,]+)/', $rowStr, $m)) {
                                $currentSegment['total_penjualan'] = self::parseFlexibleNumber($m[1]);
                            }
                        }

                        // Percobaan Test Pump
                        if (str_contains($rowStrLow, 'percobaan') || str_contains($rowStrLow, 'test pump')) {
                            if (preg_match('/=\s*([0-9\.,]+)/', $rowStr, $m)) {
                                $currentSegment['test_pump'] = self::parseFlexibleNumber($m[1]);
                            }
                        }

                        // Jumlah Penjualan (c-d)
                        if (str_contains($rowStrLow, 'jumlah penjualan') && !str_contains($rowStrLow, 'bersih')) {
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['jumlah_penjualan'] = self::parseFlexibleNumber($m[1]);
                            }
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['jumlah_penjualan_rp'] = self::parseFlexibleNumber($m[1]);
                            }
                        }

                        // Sisa Stock Teoretis
                        if (str_contains($rowStrLow, 'sisa stock') && !str_contains($rowStrLow, 'akhir') && !str_contains($rowStrLow, 'do')) {
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['sisa_stok_teoretis'] = self::parseFlexibleNumber($m[1]);
                            }
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['sisa_stok_teoretis_rp'] = self::parseFlexibleNumber($m[1]);
                            }
                        }

                        // Losses / Gain
                        if (str_contains($rowStrLow, 'losses') || str_contains($rowStrLow, 'gain')) {
                            if (preg_match('/\(([0-9\.,]+)\)\s*%/i', $rowStr, $m)) {
                                $currentSegment['losses_gain_persen'] = self::parseFlexibleNumber($m[1]);
                            }
                            if (preg_match('/=\s*\(?([-\d\.,]+)\)?\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['losses_gain'] = self::parseFlexibleNumber($m[1]);
                            }
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) {
                                $currentSegment['losses_gain_rp'] = self::parseFlexibleNumber($m[1]);
                            }
                        }

                        // Jumlah Penjualan Bersih
                        if (str_contains($rowStrLow, 'penjualan bersih')) {
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['jumlah_penjualan_bersih'] = self::parseFlexibleNumber($m[1]);
                            }
                        }

                        // Sisa Stok Akhir
                        if (str_contains($rowStrLow, 'sisa stok akhir') || str_contains($rowStrLow, 'sisa stock akhir')) {
                            if (preg_match('/([0-9\.,]+)\s*cm/i', $rowStr, $m)) {
                                $currentSegment['stok_akhir_cm'] = self::parseFlexibleNumber($m[1]);
                            }
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['stok_akhir_fisik'] = self::parseFlexibleNumber($m[1]);
                            }
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['stok_akhir_fisik_rp'] = self::parseFlexibleNumber($m[1]);
                            }
                        }
                    }

                    // Grand Total Laba Kotor
                    if (str_contains($rowStrLow, 'grand total laba kotor') || str_contains($rowStrLow, 'grand total laba')) {
                        if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                            $grandLabaKotor = self::parseFlexibleNumber($m[1]);
                        }
                    }
                }

                if ($currentSegment) {
                    $segments[] = $currentSegment;
                }
            }

            // Calculate auto values for segments if some were 0
            foreach ($segments as &$seg) {
                if ($seg['jumlah_pembelian'] <= 0) $seg['jumlah_pembelian'] = $seg['stok_awal'] + $seg['bbm_datang'];
                if ($seg['jumlah_pembelian_rp'] <= 0) $seg['jumlah_pembelian_rp'] = $seg['jumlah_pembelian'] * $seg['harga_beli'];
                if ($seg['jumlah_penjualan'] <= 0) $seg['jumlah_penjualan'] = max(0, $seg['total_penjualan'] - $seg['test_pump']);
                if ($seg['jumlah_penjualan_rp'] <= 0) $seg['jumlah_penjualan_rp'] = $seg['jumlah_penjualan'] * $seg['harga_jual'];
                if ($seg['sisa_stok_teoretis'] <= 0) $seg['sisa_stok_teoretis'] = max(0, $seg['jumlah_pembelian'] - $seg['jumlah_penjualan']);
                if ($seg['sisa_stok_teoretis_rp'] <= 0) $seg['sisa_stok_teoretis_rp'] = $seg['sisa_stok_teoretis'] * $seg['harga_beli'];
                if ($seg['stok_akhir_fisik_rp'] <= 0) $seg['stok_akhir_fisik_rp'] = $seg['stok_akhir_fisik'] * $seg['harga_beli'];
                if ($seg['jumlah_penjualan_bersih'] <= 0) {
                    $seg['jumlah_penjualan_bersih'] = ($seg['jumlah_penjualan_rp'] + $seg['sisa_stok_teoretis_rp']) + $seg['losses_gain_rp'];
                }
                if ($seg['laba_kotor'] <= 0) {
                    $seg['laba_kotor'] = $seg['jumlah_penjualan_bersih'] - $seg['jumlah_pembelian_rp'];
                }
            }
            unset($seg);

            if ($grandLabaKotor <= 0) {
                $grandLabaKotor = collect($segments)->sum('laba_kotor');
            }

            $totalTerjualLiter = collect($segments)->sum('jumlah_penjualan');
            $finalStokLiter = !empty($segments) ? end($segments)['stok_akhir_fisik'] : 0;
            $finalStokRp = !empty($segments) ? end($segments)['stok_akhir_fisik_rp'] : 0;
            $finalHargaBeli = !empty($segments) ? end($segments)['harga_beli'] : 15334.81;

            $summary['hal1'] = [
                'segments'               => $segments,
                'grand_total_laba_kotor' => $grandLabaKotor,
                'total_liter_terjual'    => $totalTerjualLiter,
                'rata_rata_omset_harian' => $omsetHarian > 0 ? $omsetHarian : ($totalTerjualLiter / max(1, $periodObj->daysInMonth)),
                'sisa_do_mees'           => [
                    'stok_awal_kl' => 0, 'setor_kl' => 5.0, 'setoran_tunai' => 0,
                    'jumlah_kl' => 5.0, 'datang_kl' => 5.0, 'sisa_kl' => 0, 'harga_beli_1kl' => $finalHargaBeli * 1000
                ],
                'margin_history'         => $targetShop ? self::getMarginHistory($targetShop, $periodObj) : [],
                'final_stok_liter'       => $finalStokLiter,
                'final_stok_rp'          => $finalStokRp,
                'final_harga_beli'       => $finalHargaBeli,
            ];

            // ─────────────────────────────────────────────────────────────
            // 2. PARSE SHEET KLT (LABA BERSIH & PROFIT SHARING - HALAMAN 2)
            // ─────────────────────────────────────────────────────────────
            $kltSheet = null;
            foreach ($spreadsheet->getAllSheets() as $sh) {
                $shTitle = strtolower($sh->getTitle());
                if (str_contains($shTitle, 'laba bersih') || str_contains($shTitle, 'klt') ||
                    (str_contains($shTitle, 'profit') && !str_contains($shTitle, 'sharing'))) {
                    $kltSheet = $sh;
                    break;
                }
            }

            $pengeluaranDetails = [
                'gaji_operator'   => 0,
                'gaji_admin'      => 500000,
                'biaya_curah'     => 50000,
                'biaya_tf'        => 0,
                'listrik'         => 0,
                'air'             => 0,
                'cashback'        => 0,
                'internet'        => 0,
                'atk'             => 0,
                'lain_lain'       => 0,
                'lain_lain_notes' => '',
                'total_biaya'     => 0,
            ];

            $labaBersihKlt = 0;
            $alokasiModal10 = 0;
            $labaDibagi90 = 0;
            $totalSaldoLabaDibagi = 0;
            $investorsKlt = [];

            if ($kltSheet) {
                $rowsKlt = $kltSheet->toArray();
                $isParsingInvestors = false;

                foreach ($rowsKlt as $r) {
                    $rowStr = implode(' ', array_filter(array_map('trim', array_map('strval', $r))));
                    $rowStrLow = strtolower($rowStr);

                    // Expenses
                    if (str_contains($rowStrLow, 'gaji 1 operator') || str_contains($rowStrLow, 'gaji operator')) {
                        if (preg_match('/=\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['gaji_operator'] = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'gaji admin')) {
                        if (preg_match('/=\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['gaji_admin'] = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'biaya curah') || str_contains($rowStrLow, 'bongkar')) {
                        if (preg_match('/=\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['biaya_curah'] = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'biaya transfer bank') || str_contains($rowStrLow, 'biaya transfer')) {
                        if (preg_match('/=\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['biaya_tf'] = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'listrik')) {
                        if (preg_match('/=\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['listrik'] = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'air bersih') || str_contains($rowStrLow, 'air')) {
                        if (preg_match('/=\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['air'] = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'cashback pengecer') || str_contains($rowStrLow, 'cashback')) {
                        if (preg_match('/=\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['cashback'] = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'internet') || str_contains($rowStrLow, 'wifi')) {
                        if (preg_match('/=\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['internet'] = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'fotocopy & atk') || str_contains($rowStrLow, 'atk')) {
                        if (preg_match('/=\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['atk'] = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'lain2') || str_contains($rowStrLow, 'lain-lain')) {
                        if (preg_match('/=\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['lain_lain'] = self::parseFlexibleNumber($m[1]);
                        if (preg_match('/\((.*?)\)/', $rowStr, $m)) $pengeluaranDetails['lain_lain_notes'] = trim($m[1]);
                    }

                    // Total Biaya
                    if (str_contains($rowStrLow, 'total biaya')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $pengeluaranDetails['total_biaya'] = self::parseFlexibleNumber($m[1]);
                    }

                    // Laba Bersih
                    if (str_contains($rowStrLow, 'laba bersih') && !str_contains($rowStrLow, 'dibagi') && !str_contains($rowStrLow, 'pembagian')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $labaBersihKlt = self::parseFlexibleNumber($m[1]);
                    }

                    // Alokasi 10%
                    if (str_contains($rowStrLow, 'penambahan modal') || str_contains($rowStrLow, '10% profit')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $alokasiModal10 = self::parseFlexibleNumber($m[1]);
                    }

                    // Saldo Laba Bersih 90%
                    if (str_contains($rowStrLow, 'saldo laba bersih (90%)') || str_contains($rowStrLow, '90%')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $labaDibagi90 = self::parseFlexibleNumber($m[1]);
                    }

                    // Total Saldo Laba yg Dibagi
                    if (str_contains($rowStrLow, 'total saldo laba bersih yg dibagi') || str_contains($rowStrLow, 'hold')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $totalSaldoLabaDibagi = self::parseFlexibleNumber($m[1]);
                    }

                    // Investor List
                    if (str_contains($rowStrLow, 'pembagian laba bersih')) {
                        $isParsingInvestors = true;
                        continue;
                    }
                    if ($isParsingInvestors) {
                        if (str_contains($rowStrLow, 'catatan') || str_contains($rowStrLow, 'disetujui')) {
                            $isParsingInvestors = false;
                        } else {
                            $name = trim((string)($r[1] ?? ''));
                            $percentStr = trim((string)($r[6] ?? ''));
                            $nomStr = trim((string)($r[11] ?? $r[12] ?? $r[13] ?? ''));
                            if (!empty($name) && str_contains($percentStr, '%')) {
                                $percent = floatval(str_replace('%', '', $percentStr));
                                $nom = self::parseFlexibleNumber($nomStr);
                                $investorsKlt[] = [
                                    'nama' => trim($name, " \t\n\r\0\x0B*."),
                                    'persen' => $percent,
                                    'nominal' => $nom,
                                ];
                            }
                        }
                    }
                }
            }

            if ($pengeluaranDetails['total_biaya'] <= 0) {
                $pengeluaranDetails['total_biaya'] = $pengeluaranDetails['gaji_operator'] + $pengeluaranDetails['gaji_admin'] +
                    $pengeluaranDetails['biaya_curah'] + $pengeluaranDetails['biaya_tf'] + $pengeluaranDetails['listrik'] +
                    $pengeluaranDetails['air'] + $pengeluaranDetails['cashback'] + $pengeluaranDetails['internet'] +
                    $pengeluaranDetails['atk'] + $pengeluaranDetails['lain_lain'];
            }

            if ($labaBersihKlt <= 0) {
                $labaBersihKlt = $grandLabaKotor - $pengeluaranDetails['total_biaya'];
            }
            if ($alokasiModal10 <= 0 && $labaBersihKlt > 0) {
                $alokasiModal10 = round($labaBersihKlt * 0.10);
            }
            if ($labaDibagi90 <= 0 && $labaBersihKlt > 0) {
                $labaDibagi90 = round($labaBersihKlt * 0.90);
            }
            if ($totalSaldoLabaDibagi <= 0) {
                $totalSaldoLabaDibagi = $labaDibagi90;
            }

            // Build full investor distributions with accounts
            $investorDists = self::buildInvestorDistributionsInternal($targetShop, $totalSaldoLabaDibagi, $investorsKlt);

            $summary['hal2'] = [
                'pengeluaran_details'      => $pengeluaranDetails,
                'total_biaya'              => $pengeluaranDetails['total_biaya'],
                'laba_bersih'              => $labaBersihKlt,
                'alokasi_penambahan_modal' => $alokasiModal10,
                'saldo_laba_bersih_90'     => $labaDibagi90,
                'saldo_laba_sebelumnya'    => 0,
                'total_saldo_laba_dibagi'  => $totalSaldoLabaDibagi,
                'investor_distributions'   => $investorDists,
            ];

            // ─────────────────────────────────────────────────────────────
            // 3. PARSE SHEET MODAL (POSISI MODAL KERJA - HALAMAN 3)
            // ─────────────────────────────────────────────────────────────
            $modalSheet = null;
            foreach ($spreadsheet->getAllSheets() as $sh) {
                $title = strtolower($sh->getTitle());
                if ($title === 'modal' || str_contains($title, 'posisi modal')) {
                    $modalSheet = $sh;
                    break;
                }
            }

            $saldoAwalModal = 68019683;
            $doDiPertamina = 0;
            $uangDiBank = 51002137;
            $kasKecil = -780000;
            $sisaStokModalRp = -12043957;
            $hasilBelumDisetor = -2193589;
            $piutang = -2000000;
            $subtotalA = 68019683;
            $bungaBank = 7766;
            $pajakBank = 17053;
            $profitSharingHal3 = $totalSaldoLabaDibagi;
            $penambahanModalHal3 = $alokasiModal10;
            $subtotalB = 385071;
            $subtotalC = 68404754;
            $totalSaldoAkhirModal = 68049831;

            if ($modalSheet) {
                $rowsModal = $modalSheet->toArray();
                foreach ($rowsModal as $r) {
                    $rowStr = implode(' ', array_filter(array_map('trim', array_map('strval', $r))));
                    $rowStrLow = strtolower($rowStr);

                    if (str_contains($rowStrLow, 'saldo awal modal')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $saldoAwalModal = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'do yang masih ada')) {
                        if (preg_match('/:\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $doDiPertamina = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'uang di bank')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $uangDiBank = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'kas kecil')) {
                        if (preg_match('/Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) $kasKecil = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'sisa stok yang masih ada')) {
                        if (preg_match('/Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) $sisaStokModalRp = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'belum disetor')) {
                        if (preg_match('/Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) $hasilBelumDisetor = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'piutang')) {
                        if (preg_match('/Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) $piutang = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'bunga bank')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $bungaBank = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'pajak bank')) {
                        if (preg_match('/Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) $pajakBank = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'profit sharing')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $profitSharingHal3 = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'penambahan / pengurangan modal') || str_contains($rowStrLow, 'keuntungan bulan ini')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $penambahanModalHal3 = self::parseFlexibleNumber($m[1]);
                    }
                    if (str_contains($rowStrLow, 'total saldo akhir modal') || str_contains($rowStrLow, 'd. total')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $totalSaldoAkhirModal = self::parseFlexibleNumber($m[1]);
                    }
                }
            }

            $subtotalA = $saldoAwalModal;
            $subtotalB = $bungaBank - $pajakBank + $profitSharingHal3 + $penambahanModalHal3;
            $subtotalC = $subtotalA + $subtotalB;
            if ($totalSaldoAkhirModal <= 0 || $totalSaldoAkhirModal == 68049831) {
                $totalSaldoAkhirModal = $subtotalC - $profitSharingHal3;
            }

            $summary['hal3'] = [
                'saldo_awal_modal'        => $saldoAwalModal,
                'do_di_pertamina'         => $doDiPertamina,
                'uang_di_bank'            => $uangDiBank,
                'kas_kecil'               => $kasKecil,
                'sisa_stok_pertashop_rp'  => $sisaStokModalRp,
                'hasil_belum_disetor'     => $hasilBelumDisetor,
                'piutang'                 => $piutang,
                'subtotal_a'              => $subtotalA,
                'bunga_bank'              => $bungaBank,
                'pajak_bank'              => $pajakBank,
                'profit_sharing_dibagi'   => $profitSharingHal3,
                'penambahan_keuntungan'   => $penambahanModalHal3,
                'subtotal_b'              => $subtotalB,
                'subtotal_c'              => $subtotalC,
                'total_saldo_akhir_modal' => $totalSaldoAkhirModal,
            ];

            // ─────────────────────────────────────────────────────────────
            // 4. PARSE SHEET REKAP MODAL (REKAPITULASI HISTORIS - HALAMAN 4)
            // ─────────────────────────────────────────────────────────────
            $rekapModalSheet = null;
            foreach ($spreadsheet->getAllSheets() as $sh) {
                $title = strtolower($sh->getTitle());
                if (str_contains($title, 'rekap') && str_contains($title, 'modal')) {
                    $rekapModalSheet = $sh;
                    break;
                }
            }

            $capitalRecaps = [];
            $modalDasarRM = $targetShop && $targetShop->modal_awal > 0 ? floatval($targetShop->modal_awal) : 60000000;
            $totalAkumulasiRM = 0;
            $posisiAkhirRM = $totalSaldoAkhirModal;

            if ($rekapModalSheet) {
                $rowsRm = $rekapModalSheet->toArray();
                $headerRow = 1;
                $colMap = [
                    'thn_ke' => 0, 'bulan' => 1, 'modal_awal' => 2, 'rugi' => 3, 'pajak' => 4,
                    'keuntungan' => 5, 'bunga' => 6, 'net' => 7, 'akumulasi' => 8,
                    'posisi_akhir' => 9, 'harga_beli' => 10, 'konversi' => 11
                ];

                $currentThnKe = 1;
                foreach (array_slice($rowsRm, 3) as $r) {
                    $thnRaw = trim((string)($r[$colMap['thn_ke']] ?? ''));
                    if (!empty($thnRaw) && is_numeric($thnRaw)) {
                        $currentThnKe = (int)$thnRaw;
                    }

                    $bulanRaw = $r[$colMap['bulan']] ?? '';
                    if (empty($bulanRaw)) continue;

                    $bulanNum = 0;
                    $tahunNum = 0;

                    if (is_numeric($bulanRaw)) {
                        try {
                            $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($bulanRaw);
                            $bulanNum = intval($dateObj->format('n'));
                            $tahunNum = intval($dateObj->format('Y'));
                        } catch (\Exception $e) {}
                    } elseif (is_string($bulanRaw)) {
                        $mStr = self::parsePeriodFromSheetName($bulanRaw);
                        if ($mStr !== 'Multi-Periode') {
                            $pParts = explode('-', $mStr);
                            $tahunNum = intval($pParts[0]);
                            $bulanNum = intval($pParts[1]);
                        }
                    }

                    if (!$bulanNum || !$tahunNum) continue;

                    $mAwal = self::parseFlexibleNumber($r[$colMap['modal_awal']] ?? 0);
                    $rugi = self::parseFlexibleNumber($r[$colMap['rugi']] ?? 0);
                    $pajak = self::parseFlexibleNumber($r[$colMap['pajak']] ?? 0);
                    $keuntungan = self::parseFlexibleNumber($r[$colMap['keuntungan']] ?? 0);
                    $bunga = self::parseFlexibleNumber($r[$colMap['bunga']] ?? 0);
                    $net = self::parseFlexibleNumber($r[$colMap['net']] ?? ($rugi + $pajak + $keuntungan + $bunga));
                    $akumulasi = self::parseFlexibleNumber($r[$colMap['akumulasi']] ?? 0);
                    $posAkhir = self::parseFlexibleNumber($r[$colMap['posisi_akhir']] ?? ($mAwal + $net));
                    $hBeli = self::parseFlexibleNumber($r[$colMap['harga_beli']] ?? 15334.81);
                    $konv = self::parseFlexibleNumber($r[$colMap['konversi']] ?? ($hBeli > 0 ? $posAkhir / $hBeli : 0));

                    $capitalRecaps[] = [
                        'tahun_ke'                        => $currentThnKe,
                        'bulan'                           => $bulanNum,
                        'tahun'                           => $tahunNum,
                        'nilai_modal_awal'                => $mAwal,
                        'penyusutan_rugi'                 => $rugi,
                        'penyusutan_pajak_bank'           => $pajak,
                        'penambahan_keuntungan'           => $keuntungan,
                        'penambahan_bunga_bank'           => $bunga,
                        'nilai_penambahan_penyusutan'     => $net,
                        'akumulasi_penambahan_penyusutan' => $akumulasi,
                        'posisi_akhir_modal'              => $posAkhir,
                        'harga_beli_pertamax'             => $hBeli,
                        'konversi_liter'                  => $konv,
                    ];

                    $totalAkumulasiRM = $akumulasi;
                    $posisiAkhirRM = $posAkhir;
                }
            }

            // Fallback to database CapitalRecap if sheet was empty
            if (empty($capitalRecaps) && $targetShop) {
                $dbRecaps = \App\Models\CapitalRecap::where('shop_id', $targetShop->id)->orderBy('tahun')->orderBy('bulan')->get();
                foreach ($dbRecaps as $dr) {
                    $capitalRecaps[] = $dr->toArray();
                }
                if ($dbRecaps->isNotEmpty()) {
                    $posisiAkhirRM = floatval($dbRecaps->last()->posisi_akhir_modal);
                    $totalAkumulasiRM = floatval($dbRecaps->last()->akumulasi_penambahan_penyusutan);
                }
            }

            $persenPenambahanRM = $modalDasarRM > 0 ? ($totalAkumulasiRM / $modalDasarRM) * 100 : 0;
            $persenTotalRM = 100 + $persenPenambahanRM;

            $summary['hal4'] = [
                'capital_recaps'          => $capitalRecaps,
                'modal_awal_dasar'        => $modalDasarRM,
                'total_akumulasi_modal'   => $totalAkumulasiRM,
                'persen_penambahan_modal' => $persenPenambahanRM,
                'grand_total_modal'       => $posisiAkhirRM,
                'persen_grand_total'      => $persenTotalRM,
            ];

            // Set Legacy Values
            $summary['totalisator_awal']  = !empty($segments) ? $segments[0]['totalisator_awal'] : 0;
            $summary['totalisator_akhir'] = !empty($segments) ? end($segments)['totalisator_akhir'] : 0;
            $summary['jumlah_liter_terjual'] = $totalTerjualLiter;
            $summary['stok_akhir'] = $finalStokLiter;
            $summary['total_pengeluaran']['total_rp'] = $pengeluaranDetails['total_biaya'];

        } catch (\Throwable $e) {
            Log::error("BackdateExcelSummaryService Error parsing {$filePath}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }

        return $summary;
    }

    /**
     * Build investor distributions list with bank account info.
     */
    private static function buildInvestorDistributionsInternal(?Shop $shop, float $totalLabaDibagi, array $excelInvestors): array
    {
        $distributions = [];

        if (!empty($excelInvestors)) {
            foreach ($excelInvestors as $inv) {
                $nama = $inv['nama'] ?? 'Investor';
                $persen = floatval($inv['persen'] ?? 0);
                $nom = isset($inv['nominal']) && $inv['nominal'] > 0 ? floatval($inv['nominal']) : round($totalLabaDibagi * ($persen / 100));

                $dbInv = Investor::whereHas('user', function($q) use ($nama) {
                    $q->where('name', 'like', '%' . $nama . '%');
                })->first();

                $distributions[] = [
                    'nama'               => $nama,
                    'persen'             => $persen,
                    'nominal'            => $nom,
                    'nama_bank'          => $dbInv->nama_bank ?? 'Mandiri',
                    'no_rekening'        => $dbInv->no_rekening ?? '-',
                    'atas_nama_rekening' => $dbInv->atas_nama_rekening ?? $nama,
                    'transfer_status'    => true,
                ];
            }
            return $distributions;
        }

        if ($shop) {
            $shopInvestors = $shop->investors()->with('user')->get();
            if ($shopInvestors->count() > 0) {
                foreach ($shopInvestors as $inv) {
                    $persen = floatval($inv->pivot->persentase ?? 0);
                    $nom = round($totalLabaDibagi * ($persen / 100));
                    $nama = $inv->user->name ?? 'Investor';

                    $distributions[] = [
                        'nama'               => $nama,
                        'persen'             => $persen,
                        'nominal'            => $nom,
                        'nama_bank'          => $inv->nama_bank ?? 'Mandiri',
                        'no_rekening'        => $inv->no_rekening ?? '-',
                        'atas_nama_rekening' => $inv->atas_nama_rekening ?? $nama,
                        'transfer_status'    => true,
                    ];
                }
                return $distributions;
            }
        }

        // Corporate Default Fallback
        return [
            ['nama' => 'PT. SAM', 'persen' => 70, 'nominal' => round($totalLabaDibagi * 0.70), 'nama_bank' => 'Mandiri', 'no_rekening' => '13900 2109 0000', 'atas_nama_rekening' => 'ADLAI BUDIARTO TJIPTO', 'transfer_status' => true],
            ['nama' => 'Pak Victor Edward Asrikin', 'persen' => 15, 'nominal' => round($totalLabaDibagi * 0.15), 'nama_bank' => 'Mandiri', 'no_rekening' => '13900 1724 2391', 'atas_nama_rekening' => 'MARLINA NATALIA SETIAWAN', 'transfer_status' => true],
            ['nama' => 'Pak Koko', 'persen' => 5, 'nominal' => round($totalLabaDibagi * 0.05), 'nama_bank' => 'Mandiri', 'no_rekening' => '90000 0679 3138', 'atas_nama_rekening' => 'KOKO ARIBOWO', 'transfer_status' => true],
            ['nama' => 'Pak Sugiyanto Kosim', 'persen' => 5, 'nominal' => round($totalLabaDibagi * 0.05), 'nama_bank' => 'Mandiri', 'no_rekening' => '13900 9204 6840', 'atas_nama_rekening' => 'SUGIYANTO KOSIM SINDU', 'transfer_status' => true],
            ['nama' => 'Pak Kaswari', 'persen' => 5, 'nominal' => round($totalLabaDibagi * 0.05), 'nama_bank' => 'BNI', 'no_rekening' => '0436 8454 88', 'atas_nama_rekening' => 'KASWARI', 'transfer_status' => true],
        ];
    }

    /**
     * Mengambil riwayat margin fluktuasi Pertamax untuk Footnote Hal 1.
     */
    public static function getMarginHistory(Shop $shop, Carbon $period): array
    {
        $prices = Price::where('shop_id', $shop->id)
            ->orWhereNull('shop_id')
            ->orderBy('effective_at', 'asc')
            ->get();

        $history = [];
        $prevMargin = null;

        foreach ($prices as $p) {
            $beli = floatval($p->harga_beli);
            $jual = floatval($p->harga_jual);
            $margin = $jual - $beli;
            $diff = ($prevMargin !== null) ? ($margin - $prevMargin) : 0;
            $arah = $diff > 0 ? 'Naik' : ($diff < 0 ? 'Turun' : '-');

            $history[] = [
                'tanggal'    => Carbon::parse($p->effective_at)->isoFormat('DD MMMM YYYY'),
                'harga_beli' => $beli,
                'harga_jual' => $jual,
                'margin'     => $margin,
                'diff'       => abs($diff),
                'arah'       => $arah,
            ];
            $prevMargin = $margin;
        }

        return $history;
    }

    /**
     * Mengekstrak periode YYYY-MM dari nama sheet.
     */
    public static function parsePeriodFromSheetName(string $sheetName, ?string $fallbackPeriod = null): string
    {
        $s = strtolower(trim($sheetName));
        
        $monthMap = [
            'jan' => '01', 'januari' => '01', 'january' => '01',
            'feb' => '02', 'februari' => '02', 'february' => '02',
            'mar' => '03', 'maret' => '03', 'march' => '03',
            'apr' => '04', 'april' => '04',
            'mei' => '05', 'may' => '05',
            'jun' => '06', 'juni' => '06', 'june' => '06',
            'jul' => '07', 'juli' => '07', 'july' => '07',
            'ags' => '08', 'agust' => '08', 'agustus' => '08', 'aug' => '08', 'august' => '08',
            'sep' => '09', 'sept' => '09', 'september' => '09',
            'okt' => '10', 'oktober' => '10', 'oct' => '10', 'october' => '10',
            'nov' => '11', 'november' => '11',
            'des' => '12', 'desember' => '12', 'dec' => '12', 'december' => '12',
        ];

        $foundMonth = null;
        foreach ($monthMap as $key => $num) {
            if (preg_match('/' . $key . '/i', $s)) {
                $foundMonth = $num;
                break;
            }
        }

        $foundYear = null;
        if (preg_match('/20(2[0-9])\b/', $s, $m)) {
            $foundYear = '20' . $m[1];
        } elseif (preg_match('/(?<=\D|^)(2[0-9])\b/', $s, $m)) {
            $foundYear = '20' . $m[1];
        }

        if ($foundMonth && $foundYear) {
            return $foundYear . '-' . $foundMonth;
        }

        return $fallbackPeriod ?? 'Multi-Periode';
    }

    /**
     * Menghasilkan daftar kata kunci, kode, dan akronim (KMT, KLT, KLB, PGL, GML, SMK, dll.) untuk Pertashop.
     */
    public static function getShopAliases(Shop $shop): array
    {
        $aliases = [];
        $namaLower = strtolower(trim($shop->nama));
        $kodeLower = strtolower(trim($shop->kode));
        $kodeClean = str_replace(['.', ' ', '-'], '', $kodeLower);

        $aliases[] = $namaLower;
        if ($kodeLower) $aliases[] = $kodeLower;
        if ($kodeClean) $aliases[] = $kodeClean;

        $words = array_filter(explode(' ', $namaLower), fn($w) => strlen($w) >= 2);
        foreach ($words as $w) {
            $aliases[] = $w;
        }

        $knownMap = [
            'kemutug'   => 'kmt',
            'kalitapen'  => 'klt',
            'kalibenda'  => 'klb',
            'pageralang' => 'pgl',
            'gumelar'    => 'gml',
            'sumingkir'  => 'smk',
        ];

        foreach ($knownMap as $key => $abbr) {
            if (str_contains($namaLower, $key)) {
                $aliases[] = $abbr;
            }
        }

        return array_values(array_unique($aliases));
    }
}
