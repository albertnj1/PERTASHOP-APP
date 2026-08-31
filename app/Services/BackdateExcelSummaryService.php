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
     * Standardized & safe numeric sanitizer for Indonesian & Excel formats.
     */
    public static function cleanNumeric($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (empty($value) || $value === '-' || $value === '#DIV/0!' || $value === '#VALUE!' || $value === '#N/A' || $value === '#REF!' || $value === '#NAME?') {
            return 0.0;
        }
        
        $val = trim((string)$value);
        $isNegative = false;
        if (preg_match('/^\(.*\)$/', $val) || str_starts_with($val, '-')) {
            $isNegative = true;
        }

        $clean = str_replace(["Rp", " ", "(", ")", ",-", ".-", "-", "è", "à", "L", "ℓ", "KL"], "", $val);
        $clean = trim($clean, ".,");
        if ($clean === '') return 0.0;
        
        // Handle Indonesian vs US decimal separators
        if (strpos($clean, ',') !== false && strpos($clean, '.') !== false) {
            $lastDot = strrpos($clean, '.');
            $lastComma = strrpos($clean, ',');
            if ($lastComma > $lastDot) {
                // Indonesian: 1.234.567,89 -> 1234567.89
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                // US: 1,234,567.89 -> 1234567.89
                $clean = str_replace(',', '', $clean);
            }
        } elseif (strpos($clean, ',') !== false) {
            if (preg_match('/^\d{1,3}(,\d{3})+$/', $clean)) {
                $clean = str_replace(',', '', $clean);
            } else {
                $clean = str_replace(',', '.', $clean);
            }
        } elseif (strpos($clean, '.') !== false) {
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $clean)) {
                $clean = str_replace('.', '', $clean);
            } elseif (substr_count($clean, '.') > 1) {
                $parts = explode('.', $clean);
                $last = array_pop($parts);
                $clean = implode('', $parts) . '.' . $last;
            }
        }

        $floatVal = (float) $clean;
        return $isNegative ? -$floatVal : $floatVal;
    }

    /**
     * Alias for backward compatibility
     */
    public static function parseFlexibleNumber($val): float
    {
        return self::cleanNumeric($val);
    }

    /**
     * Memastikan harga per liter BBM Pertamax berada dalam rentang normal (Rp 10.000 - Rp 20.000).
     */
    public static function normalizeFuelPrice(float $price): float
    {
        if ($price > 0 && $price < 100) {
            return $price * 1000;
        }
        return $price;
    }

    /**
     * Mengekstrak data lengkap 4 Halaman Laporan dari file Excel backdate atau objek Spreadsheet.
     *
     * @param string|\PhpOffice\PhpSpreadsheet\Spreadsheet $filePathOrSpreadsheet Absolute path file Excel (.xlsx / .xls) atau objek Spreadsheet
     * @param \App\Models\Shop|null $targetShop Toko target
     * @param string|null $targetPeriod Periode target (YYYY-MM)
     * @return array
     */
    public static function extract($filePathOrSpreadsheet, ?Shop $targetShop = null, ?string $targetPeriod = null): array
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

        try {
            if ($filePathOrSpreadsheet instanceof \PhpOffice\PhpSpreadsheet\Spreadsheet) {
                $spreadsheet = $filePathOrSpreadsheet;
            } else {
                $filePath = (string)$filePathOrSpreadsheet;
                if (!file_exists($filePath)) {
                    return $summary;
                }
                $reader = IOFactory::createReaderForFile($filePath);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($filePath);
            }

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
                        if (preg_match('/Harga Beli\s*(\d+)?\s*[:=]\s*Rp\s*([0-9\.,]+)/i', $clean, $m)) {
                            $idx = !empty($m[1]) ? (int)$m[1] : 1;
                            $hargaBeliList[$idx] = self::normalizeFuelPrice(self::parseFlexibleNumber($m[2]));
                        }
                        if (preg_match('/Harga Jual\s*(\d+)?\s*[:=]\s*Rp\s*([0-9\.,]+)/i', $clean, $m)) {
                            $idx = !empty($m[1]) ? (int)$m[1] : 1;
                            $hargaJualList[$idx] = self::normalizeFuelPrice(self::parseFlexibleNumber($m[2]));
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
                            if ($currentSegment['totalisator_akhir'] > 0 || $currentSegment['totalisator_awal'] > 0 || $currentSegment['jumlah_penjualan'] > 0 || $currentSegment['stok_awal'] > 0) {
                                $segments[] = $currentSegment;
                            }
                        }
                        $segIndex = (int)$m[1];
                        $hBeli = $hargaBeliList[$segIndex] ?? ($hargaBeliList[1] ?? 11376.29);
                        $hJual = $hargaJualList[$segIndex] ?? ($hargaJualList[1] ?? 12200);

                        $currentSegment = [
                            'segmen_index'            => $segIndex,
                            'harga_beli'              => self::normalizeFuelPrice($hBeli),
                            'harga_jual'              => self::normalizeFuelPrice($hJual),
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
                                $currentSegment['stok_awal'] = self::cleanNumeric($m[1]);
                                $currentSegment['stok_awal_rp'] = self::cleanNumeric($m[3]);
                            } elseif (preg_match('/([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['stok_awal'] = self::cleanNumeric($m[1]);
                                $currentSegment['stok_awal_rp'] = $currentSegment['stok_awal'] * $currentSegment['harga_beli'];
                            }
                        }

                        // BBM Datang (Fuzzy DO Ingestion - Jalur 2)
                        if (str_contains($rowStrLow, 'datang') || str_contains($rowStrLow, 'do datang') || str_contains($rowStrLow, 'bbm datang') || str_contains($rowStrLow, 'pgrl datang')) {
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $vol = self::cleanNumeric($m[1]);
                                $currentSegment['bbm_datang'] += $vol;
                                $currentSegment['bbm_datang_rp'] += ($vol * $currentSegment['harga_beli']);
                            } elseif (preg_match('/([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $vol = self::cleanNumeric($m[1]);
                                $currentSegment['bbm_datang'] += $vol;
                                $currentSegment['bbm_datang_rp'] += ($vol * $currentSegment['harga_beli']);
                            }
                        }

                        // Jumlah Pembelian
                        if (str_contains($rowStrLow, 'jumlah pembelian')) {
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['jumlah_pembelian'] = self::cleanNumeric($m[1]);
                            }
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['jumlah_pembelian_rp'] = self::cleanNumeric($m[1]);
                            }
                        }

                        // Totalisator Akhir
                        if (str_contains($rowStrLow, 'totalisator akhir')) {
                            if (preg_match('/\((.*?)\)/', $rowStr, $m)) {
                                $currentSegment['end_datetime_label'] = trim($m[1]);
                            }
                            if (preg_match('/=\s*([0-9\.,]+)/', $rowStr, $m)) {
                                $currentSegment['totalisator_akhir'] = self::cleanNumeric($m[1]);
                            }
                        }

                        // Totalisator Awal
                        if (str_contains($rowStrLow, 'totalisator awal')) {
                            if (preg_match('/\((.*?)\)/', $rowStr, $m)) {
                                $currentSegment['start_datetime_label'] = trim($m[1]);
                            }
                            if (preg_match('/=\s*([0-9\.,]+)/', $rowStr, $m)) {
                                $currentSegment['totalisator_awal'] = self::cleanNumeric($m[1]);
                            }
                        }

                        // Total Penjualan (a-b)
                        if (str_contains($rowStrLow, 'total penjualan') && !str_contains($rowStrLow, 'bersih') && !str_contains($rowStrLow, 'laba')) {
                            if (preg_match('/=\s*([0-9\.,]+)/', $rowStr, $m)) {
                                $currentSegment['total_penjualan'] = self::cleanNumeric($m[1]);
                            }
                        }

                        // Percobaan Test Pump
                        if (str_contains($rowStrLow, 'percobaan') || str_contains($rowStrLow, 'test pump')) {
                            if (preg_match('/=\s*([0-9\.,]+)/', $rowStr, $m)) {
                                $currentSegment['test_pump'] = self::cleanNumeric($m[1]);
                            }
                        }

                        // Jumlah Penjualan (c-d)
                        if (str_contains($rowStrLow, 'jumlah penjualan') && !str_contains($rowStrLow, 'bersih')) {
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['jumlah_penjualan'] = self::cleanNumeric($m[1]);
                            }
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['jumlah_penjualan_rp'] = self::cleanNumeric($m[1]);
                            }
                        }

                        // Sisa Stock Teoretis
                        if (str_contains($rowStrLow, 'sisa stock') && !str_contains($rowStrLow, 'akhir') && !str_contains($rowStrLow, 'do')) {
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['sisa_stok_teoretis'] = self::cleanNumeric($m[1]);
                            }
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['sisa_stok_teoretis_rp'] = self::cleanNumeric($m[1]);
                            }
                        }

                        // Losses / Gain
                        if (str_contains($rowStrLow, 'losses') || str_contains($rowStrLow, 'gain')) {
                            if (preg_match('/\(([0-9\.,]+)\)\s*%/i', $rowStr, $m)) {
                                $currentSegment['losses_gain_persen'] = self::cleanNumeric($m[1]);
                            }
                            if (preg_match('/=\s*\(?([-\d\.,]+)\)?\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['losses_gain'] = self::cleanNumeric($m[1]);
                            }
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) {
                                $currentSegment['losses_gain_rp'] = self::cleanNumeric($m[1]);
                            }
                        }

                        // Jumlah Penjualan Bersih
                        if (str_contains($rowStrLow, 'penjualan bersih')) {
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['jumlah_penjualan_bersih'] = self::cleanNumeric($m[1]);
                            }
                        }

                        // Sisa Stok Akhir
                        if (str_contains($rowStrLow, 'sisa stok akhir') || str_contains($rowStrLow, 'sisa stock akhir')) {
                            if (preg_match('/([0-9\.,]+)\s*cm/i', $rowStr, $m)) {
                                $currentSegment['stok_akhir_cm'] = self::cleanNumeric($m[1]);
                            }
                            if (preg_match('/=\s*([0-9\.,]+)\s*ℓ/i', $rowStr, $m)) {
                                $currentSegment['stok_akhir_fisik'] = self::cleanNumeric($m[1]);
                            }
                            if (preg_match('/(?:->|&rarr;|=)\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) {
                                $currentSegment['stok_akhir_fisik_rp'] = self::cleanNumeric($m[1]);
                            }
                        }
                    }
                }

                if ($currentSegment && ($currentSegment['totalisator_akhir'] > 0 || $currentSegment['totalisator_awal'] > 0 || $currentSegment['jumlah_penjualan'] > 0 || $currentSegment['stok_awal'] > 0)) {
                    $segments[] = $currentSegment;
                }
            }

            // 1. PERHITUNGAN LABA KOTOR PER BATCH (Sesuai Aturan User #1)
            foreach ($segments as &$seg) {
                if ($seg['totalisator_akhir'] > 0 && $seg['totalisator_awal'] > 0) {
                    $selisih = max(0, $seg['totalisator_akhir'] - $seg['totalisator_awal']);
                    $seg['total_penjualan'] = $selisih;
                    $seg['jumlah_penjualan'] = max(0, $selisih - $seg['test_pump']);
                } elseif ($seg['total_penjualan'] > 0 && $seg['jumlah_penjualan'] <= 0) {
                    $seg['jumlah_penjualan'] = max(0, $seg['total_penjualan'] - $seg['test_pump']);
                }

                if ($seg['jumlah_pembelian'] <= 0) $seg['jumlah_pembelian'] = $seg['stok_awal'] + $seg['bbm_datang'];
                if ($seg['jumlah_pembelian_rp'] <= 0) $seg['jumlah_pembelian_rp'] = $seg['jumlah_pembelian'] * $seg['harga_beli'];
                if ($seg['jumlah_penjualan_rp'] <= 0) $seg['jumlah_penjualan_rp'] = $seg['jumlah_penjualan'] * $seg['harga_jual'];
                if ($seg['sisa_stok_teoretis'] <= 0) $seg['sisa_stok_teoretis'] = max(0, $seg['jumlah_pembelian'] - $seg['jumlah_penjualan']);
                if ($seg['sisa_stok_teoretis_rp'] <= 0) $seg['sisa_stok_teoretis_rp'] = $seg['sisa_stok_teoretis'] * $seg['harga_beli'];
                if ($seg['stok_akhir_fisik_rp'] <= 0) $seg['stok_akhir_fisik_rp'] = $seg['stok_akhir_fisik'] * $seg['harga_beli'];
                if ($seg['jumlah_penjualan_bersih'] <= 0) {
                    $seg['jumlah_penjualan_bersih'] = ($seg['jumlah_penjualan_rp'] + $seg['sisa_stok_teoretis_rp']) + $seg['losses_gain_rp'];
                }

                // Volume Terjual * (Harga Jual - Harga Beli HPP) + Losses/Gain Rp
                $marginPerLiter = $seg['harga_jual'] - $seg['harga_beli'];
                $seg['laba_kotor'] = ($seg['jumlah_penjualan'] * $marginPerLiter) + $seg['losses_gain_rp'];
            }
            unset($seg);

            $grandLabaKotor = collect($segments)->sum('laba_kotor');
            $totalTerjualLiter = collect($segments)->sum('jumlah_penjualan');

            // Fallback to Daily Sales Sheet parser if KLB format segments not found
            if (empty($segments)) {
                $dailySummary = self::parseDailySalesSheet($spreadsheet, $targetShop, $periodObj);
                if ($dailySummary !== null && !empty($dailySummary['hal1']['segments'])) {
                    return $dailySummary;
                }
            }

            $finalStokLiter = !empty($segments) ? end($segments)['stok_akhir_fisik'] : 0;
            $finalStokRp = !empty($segments) ? end($segments)['stok_akhir_fisik_rp'] : 0;
            $finalHargaBeli = !empty($segments) ? end($segments)['harga_beli'] : ($hargaBeliList[1] ?? 11376.29);

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
            // 2. PARSE SHEET LABA BERSIH & PROFIT SHARING (HALAMAN 2)
            // ─────────────────────────────────────────────────────────────
            $kltSheet = null;
            foreach ($spreadsheet->getAllSheets() as $sh) {
                $shTitle = strtolower($sh->getTitle());
                if (str_contains($shTitle, 'laba bersih')) {
                    $kltSheet = $sh;
                    break;
                }
            }
            if (!$kltSheet) {
                foreach ($spreadsheet->getAllSheets() as $sh) {
                    $shTitle = strtolower($sh->getTitle());
                    if (str_contains($shTitle, 'klt') || (str_contains($shTitle, 'profit') && !str_contains($shTitle, 'sharing'))) {
                        $kltSheet = $sh;
                        break;
                    }
                }
            }

            $pengeluaranDetails = [
                'gaji_operator'   => 0,
                'gaji_admin'      => 0,
                'biaya_curah'     => 0,
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

            $investorsKlt = [];

            if ($kltSheet) {
                $rowsKlt = $kltSheet->toArray();
                $isParsingInvestors = false;

                foreach ($rowsKlt as $r) {
                    $rowStr = implode(' ', array_filter(array_map('trim', array_map('strval', $r))));
                    $rowStrLow = strtolower($rowStr);

                    // Extract numeric value from row
                    $rowNum = null;
                    for ($i = count($r) - 1; $i >= 0; $i--) {
                        $cVal = $r[$i];
                        if ($cVal === null || $cVal === '') continue;
                        if (is_numeric($cVal)) {
                            $rowNum = (float)$cVal;
                            break;
                        }
                        if (is_string($cVal) && (str_contains($cVal, 'Rp') || preg_match('/^[0-9.,-]+$/', trim($cVal)))) {
                            $parsed = self::cleanNumeric($cVal);
                            if ($parsed != 0) {
                                $rowNum = $parsed;
                                break;
                            }
                        }
                    }

                    if ($rowNum !== null) {
                        // Expenses parsing by keyword matching (Rule 2.B)
                        if (preg_match('/gaji.*(operator|karyawan|thr)/i', $rowStrLow) || (str_contains($rowStrLow, 'gaji') && !str_contains($rowStrLow, 'admin'))) {
                            $pengeluaranDetails['gaji_operator'] = $rowNum;
                        } elseif (preg_match('/gaji.*admin|admin/i', $rowStrLow) && !str_contains($rowStrLow, 'operator')) {
                            $pengeluaranDetails['gaji_admin'] = $rowNum;
                        } elseif (preg_match('/curah|ongkos bongkar|bongkar/i', $rowStrLow)) {
                            $pengeluaranDetails['biaya_curah'] = $rowNum;
                        } elseif (preg_match('/transfer|adm bank|biaya tf/i', $rowStrLow)) {
                            $pengeluaranDetails['biaya_tf'] = $rowNum;
                        } elseif (preg_match('/listrik|pln|pulsa listrik/i', $rowStrLow)) {
                            $pengeluaranDetails['listrik'] = $rowNum;
                        } elseif (preg_match('/air bersih|pdam|air/i', $rowStrLow)) {
                            $pengeluaranDetails['air'] = $rowNum;
                        } elseif (preg_match('/cashback|bakulan/i', $rowStrLow)) {
                            $pengeluaranDetails['cashback'] = $rowNum;
                        } elseif (preg_match('/internet|cctv|modem|wifi/i', $rowStrLow)) {
                            $pengeluaranDetails['internet'] = $rowNum;
                        } elseif (preg_match('/atk|fotocopy|kertas|perlengkapan/i', $rowStrLow)) {
                            $pengeluaranDetails['atk'] = $rowNum;
                        } elseif (preg_match('/lain|vixal|genset|iuran|yusup|keamanan/i', $rowStrLow)) {
                            $pengeluaranDetails['lain_lain'] += $rowNum;
                        }
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
                                $nom = self::cleanNumeric($nomStr);
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

            // 3. TOTAL BEBAN OPERASIONAL (Rule 2.B)
            $calcTotalBiaya = array_sum([
                $pengeluaranDetails['gaji_operator'],
                $pengeluaranDetails['gaji_admin'],
                $pengeluaranDetails['biaya_curah'],
                $pengeluaranDetails['biaya_tf'],
                $pengeluaranDetails['listrik'],
                $pengeluaranDetails['air'],
                $pengeluaranDetails['cashback'],
                $pengeluaranDetails['internet'],
                $pengeluaranDetails['atk'],
                $pengeluaranDetails['lain_lain']
            ]);
            $pengeluaranDetails['total_biaya'] = $calcTotalBiaya;

            // 4. SANITY ASSERTION LAYER - LABA BERSIH & ALOKASI (Rule 2.E)
            $calculatedLabaBersih = $grandLabaKotor - $calcTotalBiaya;
            $alokasiModal10 = ($calculatedLabaBersih > 0) ? round($calculatedLabaBersih * 0.10) : 0;
            $labaDibagi90 = ($calculatedLabaBersih > 0) ? round($calculatedLabaBersih * 0.90) : 0;
            $totalSaldoLabaDibagi = $labaDibagi90;

            // Build full investor distributions with accounts
            $investorDists = self::buildInvestorDistributionsInternal($targetShop, $totalSaldoLabaDibagi, $investorsKlt);

            $summary['hal2'] = [
                'pengeluaran_details'      => $pengeluaranDetails,
                'total_biaya'              => $calcTotalBiaya,
                'laba_bersih'              => $calculatedLabaBersih,
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

            $modalAwalOutlet = $targetShop && $targetShop->modal_awal > 0 ? floatval($targetShop->modal_awal) : 60000000;
            $saldoAwalModal = $modalAwalOutlet;
            $doDiPertamina = 0;
            $uangDiBank = 0;
            $kasKecil = 0;
            $sisaStokModalRp = $finalStokRp;
            $hasilBelumDisetor = 0;
            $piutang = 0;
            $bungaBank = 0;
            $pajakBank = 0;
            $profitSharingHal3 = $totalSaldoLabaDibagi;
            $penambahanModalHal3 = $alokasiModal10;
            $totalSaldoAkhirModal = $modalAwalOutlet;

            if ($modalSheet) {
                $rowsModal = $modalSheet->toArray();
                foreach ($rowsModal as $r) {
                    $rowStr = implode(' ', array_filter(array_map('trim', array_map('strval', $r))));
                    $rowStrLow = strtolower($rowStr);

                    if (str_contains($rowStrLow, 'saldo awal modal')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $saldoAwalModal = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'do yang masih ada')) {
                        if (preg_match('/:\s*Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $doDiPertamina = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'uang di bank')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $uangDiBank = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'kas kecil')) {
                        if (preg_match('/Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) $kasKecil = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'sisa stok yang masih ada')) {
                        if (preg_match('/Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) $sisaStokModalRp = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'belum disetor')) {
                        if (preg_match('/Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) $hasilBelumDisetor = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'piutang')) {
                        if (preg_match('/Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) $piutang = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'bunga bank')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $bungaBank = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'pajak bank')) {
                        if (preg_match('/Rp\s*\(?([-\d\.,]+)\)?/i', $rowStr, $m)) $pajakBank = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'profit sharing')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $profitSharingHal3 = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'penambahan / pengurangan modal') || str_contains($rowStrLow, 'keuntungan bulan ini')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $penambahanModalHal3 = self::cleanNumeric($m[1]);
                    }
                    if (str_contains($rowStrLow, 'total saldo akhir modal') || str_contains($rowStrLow, 'd. total')) {
                        if (preg_match('/Rp\s*([0-9\.,]+)/i', $rowStr, $m)) $totalSaldoAkhirModal = self::cleanNumeric($m[1]);
                    }
                }
            }

            if ($saldoAwalModal <= 0) {
                $saldoAwalModal = $modalAwalOutlet;
            }

            // Dynamic sum of all current asset components
            $subtotalA = $doDiPertamina + $uangDiBank + $kasKecil + $sisaStokModalRp + $hasilBelumDisetor + $piutang;
            if ($subtotalA == 0) {
                $subtotalA = $sisaStokModalRp > 0 ? $sisaStokModalRp : $saldoAwalModal;
            }

            $subtotalB = $bungaBank - $pajakBank - $profitSharingHal3 + $penambahanModalHal3;
            $subtotalC = $subtotalA + $subtotalB;
            if ($totalSaldoAkhirModal <= 0) {
                $totalSaldoAkhirModal = $subtotalC;
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
                            $tahunNum = intval($pParts[0] ?? 0);
                            $bulanNum = intval($pParts[1] ?? 0);
                        }
                    }

                    // Strict validation: skip invalid dates (e.g. year 1900 from empty date serial 0)
                    if (!$bulanNum || !$tahunNum || $tahunNum < 2020 || $tahunNum > 2100 || $bulanNum < 1 || $bulanNum > 12) {
                        continue;
                    }

                    $mAwal = self::cleanNumeric($r[$colMap['modal_awal']] ?? 0);
                    $rugi = self::cleanNumeric($r[$colMap['rugi']] ?? 0);
                    $pajak = self::cleanNumeric($r[$colMap['pajak']] ?? 0);
                    $keuntungan = self::cleanNumeric($r[$colMap['keuntungan']] ?? 0);
                    $bunga = self::cleanNumeric($r[$colMap['bunga']] ?? 0);
                    $net = self::cleanNumeric($r[$colMap['net']] ?? ($rugi + $pajak + $keuntungan + $bunga));
                    $akumulasi = self::cleanNumeric($r[$colMap['akumulasi']] ?? 0);
                    $posAkhir = self::cleanNumeric($r[$colMap['posisi_akhir']] ?? ($mAwal + $net));
                    $hBeli = self::cleanNumeric($r[$colMap['harga_beli']] ?? 11451.29);
                    $konv = self::cleanNumeric($r[$colMap['konversi']] ?? ($hBeli > 0 ? $posAkhir / $hBeli : 0));

                    // Skip blank template rows extending into the future with 0s
                    if ($rugi == 0 && $pajak == 0 && $keuntungan == 0 && $bunga == 0 && $net == 0 && ($mAwal == $posAkhir || $posAkhir <= 0)) {
                        continue;
                    }

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
    /**
     * Menghasilkan daftar kata kunci, kode, dan akronim (KMT, KLT, KLB, PGL, GML, SMK, dll.) untuk Pertashop.
     */
    public static function getShopAliases(Shop $shop): array
    {
        $aliases = [];
        $namaLower = strtolower(trim($shop->nama));
        $kodeLower = strtolower(trim($shop->kode ?? ''));
        $kodeClean = str_replace(['.', ' ', '-', '_'], '', $kodeLower);

        $aliases[] = $namaLower;
        if ($kodeLower) $aliases[] = $kodeLower;
        if ($kodeClean) $aliases[] = $kodeClean;

        $words = array_filter(explode(' ', $namaLower), fn($w) => strlen($w) >= 2);
        foreach ($words as $w) {
            $aliases[] = $w;
        }

        $knownMap = [
            'kemutug'   => ['kmt', '53143', '531.43', '4p.531.43', '4p53143', 'kemutug lor'],
            'kalitapen' => ['klt', '53119', '531.19', '4p.531.19', '4p53119'],
            'kalibenda' => ['klb', '53134', '531.34', '4p.531.34', '4p53134'],
            'pageralang'=> ['pgl', '53164', '531.64', '4p.531.64', '4p53164'],
            'gumelar'   => ['gml', '53158', '531.58', '4p.531.58', '4p53158'],
            'sumingkir' => ['smk', '53240', '532.40', '4p.532.40', '4p53240', '53.4.40', '53440', '53.440'],
        ];

        foreach ($knownMap as $key => $abbrs) {
            if (str_contains($namaLower, $key)) {
                $aliases = array_merge($aliases, (array)$abbrs);
            }
        }

        return array_values(array_unique($aliases));
    }

    /**
     * Peta kode outlet Pertamina resmi (termasuk variasi format / typo) → keyword nama outlet.
     */
    public static function getPertaminaCodeMap(): array
    {
        return [
            '4P.531.43' => 'kemutug',
            '4P.53143'  => 'kemutug',
            '53143'     => 'kemutug',
            '531.43'    => 'kemutug',

            '4P.531.19' => 'kalitapen',
            '4P.53119'  => 'kalitapen',
            '53119'     => 'kalitapen',
            '531.19'    => 'kalitapen',

            '4P.531.34' => 'kalibenda',
            '4P.53134'  => 'kalibenda',
            '53134'     => 'kalibenda',
            '531.34'    => 'kalibenda',

            '4P.531.64' => 'pageralang',
            '4P.53164'  => 'pageralang',
            '53164'     => 'pageralang',
            '531.64'    => 'pageralang',

            '4P.531.58' => 'gumelar',
            '4P.53158'  => 'gumelar',
            '53158'     => 'gumelar',
            '531.58'    => 'gumelar',

            '4P.532.40' => 'sumingkir',
            '4P.53240'  => 'sumingkir',
            '4p - 53.4.40' => 'sumingkir',
            '53.4.40'   => 'sumingkir',
            '53240'     => 'sumingkir',
            '532.40'    => 'sumingkir',
            '53440'     => 'sumingkir',
        ];
    }

    /**
     * Deteksi outlet HANYA dari string nama sheet atau nama file (tanpa membaca cell).
     */
    public static function detectOutletFromSheetNameOnly(string $textToScan, $shops): ?Shop
    {
        $titleLower = strtolower($textToScan);
        $titleClean = str_replace(['.', ' ', '-', '_', '(', ')'], '', $titleLower);

        // 1. Cek Kode Pertamina di title
        $pertaminaCodes = self::getPertaminaCodeMap();
        foreach ($pertaminaCodes as $code => $outletKeyword) {
            $codeClean = str_replace(['.', ' ', '-', '_'], '', strtolower($code));
            if (str_contains($titleLower, strtolower($code)) || str_contains($titleClean, $codeClean)) {
                foreach ($shops as $shop) {
                    if (str_contains(strtolower($shop->nama), $outletKeyword)) {
                        return $shop;
                    }
                }
            }
        }

        // 2. Cek nama & alias outlet di title
        $genericStopwords = ['ps', 'ps.', 'pertashop', 'desa', 'kec', 'kecamatan', 'kab', 'kabupaten',
            'toko', 'outlet', 'gaji', 'penjualan', 'laporan', 'daily', 'sheet', 'laba', 'modal',
            'rekap', 'stok', 'bersih', 'kotor', 'profit', 'sales', 'report', 'pt', 'sam', 'xlsx', 'xls'];

        foreach ($shops as $shop) {
            $aliases = self::getShopAliases($shop);
            $validAliases = array_filter($aliases, function ($alias) use ($genericStopwords) {
                return strlen($alias) >= 3 && !in_array(strtolower($alias), $genericStopwords);
            });

            foreach ($validAliases as $alias) {
                $aliasClean = str_replace(['.', ' ', '-', '_'], '', strtolower($alias));
                if (str_contains($titleLower, strtolower($alias)) || str_contains($titleClean, $aliasClean)) {
                    return $shop;
                }
            }
        }

        return null;
    }

    /**
     * Deteksi outlet dari teks (nama sheet / nama file) dan isi header sel.
     * Mengembalikan Shop atau null jika tidak terdeteksi.
     *
     * @param string $textToScan Nama sheet atau nama file
     * @param array $headerCells Baris-baris awal sheet (array of rows)
     * @param \Illuminate\Support\Collection $shops
     * @return \App\Models\Shop|null
     */
    public static function detectOutletFromSheet(string $textToScan, array $headerCells, $shops): ?Shop
    {
        $titleLower = strtolower($textToScan);
        $titleClean = str_replace(['.', ' ', '-', '_', '(', ')'], '', $titleLower);

        // Gabungkan header cells jadi satu string untuk scanning
        $headerStr = '';
        foreach (array_slice($headerCells, 0, 20) as $row) {
            if (is_array($row)) {
                $headerStr .= ' ' . implode(' ', array_filter(array_map(function ($v) {
                    return is_string($v) ? $v : '';
                }, $row)));
            }
        }
        $headerStrLower = strtolower($headerStr);
        $headerStrClean = str_replace(['.', ' ', '-', '_', '(', ')'], '', $headerStrLower);

        // 1. Cek Kode Pertamina di header & title
        $pertaminaCodes = self::getPertaminaCodeMap();
        foreach ($pertaminaCodes as $code => $outletKeyword) {
            $codeClean = str_replace(['.', ' ', '-', '_'], '', strtolower($code));
            if (
                str_contains($titleLower, strtolower($code)) ||
                str_contains($titleClean, $codeClean) ||
                str_contains($headerStrLower, strtolower($code)) ||
                str_contains($headerStrClean, $codeClean)
            ) {
                foreach ($shops as $shop) {
                    if (str_contains(strtolower($shop->nama), $outletKeyword)) {
                        return $shop;
                    }
                }
            }
        }

        // 2. Cek nama & alias outlet di title & header
        $genericStopwords = ['ps', 'ps.', 'pertashop', 'desa', 'kec', 'kecamatan', 'kab', 'kabupaten',
            'toko', 'outlet', 'gaji', 'penjualan', 'laporan', 'daily', 'sheet', 'laba', 'modal',
            'rekap', 'stok', 'bersih', 'kotor', 'profit', 'sales', 'report', 'pt', 'sam', 'xlsx', 'xls'];

        foreach ($shops as $shop) {
            $aliases = self::getShopAliases($shop);
            $validAliases = array_filter($aliases, function ($alias) use ($genericStopwords) {
                return strlen($alias) >= 3 && !in_array(strtolower($alias), $genericStopwords);
            });

            foreach ($validAliases as $alias) {
                $aliasClean = str_replace(['.', ' ', '-', '_'], '', strtolower($alias));
                if (
                    str_contains($titleLower, strtolower($alias)) ||
                    str_contains($titleClean, $aliasClean) ||
                    str_contains($headerStrLower, strtolower($alias)) ||
                    str_contains($headerStrClean, $aliasClean)
                ) {
                    return $shop;
                }
            }
        }

        return null;
    }

    /**
     * MESIN UTAMA V2: Membaca 1 file Excel dan mengekstrak data per-outlet.
     *
     * @param string $filePath Path absolut file Excel
     * @param \Illuminate\Support\Collection|null $shops Daftar outlet (jika null, ambil semua dari DB)
     * @param string $originalFilename Nama berkas asli (misal: "08. Sales Report Kemutug Lor...")
     * @return array
     */
    public static function extractMultiShopFromFile(string $filePath, $shops = null, string $originalFilename = ''): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        if ($shops === null) {
            $shops = Shop::all();
        }

        $results = [];

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);

            $fileIdentifier = $originalFilename ?: basename($filePath);

            // Deteksi outlet dari NAMA FILE terlebih dahulu (sangat akurat)
            $fileLevelShop = self::detectOutletFromSheet($fileIdentifier, [], $shops);

            // Fase 1: Scan semua sheet → identifikasi outlet dan partisi per periode/bulan
            $shopPeriodGroups = []; // "shopId_period" => ['shop' => Shop, 'period' => '...', 'sheet_names' => [...]]

            foreach ($spreadsheet->getAllSheets() as $sheetIdx => $sheet) {
                $sheetTitle = $sheet->getTitle();
                $sheetTitleLow = strtolower(trim($sheetTitle));

                // Lewati sheet pembantu/transaksional non-laporan bulanan
                if (str_contains($sheetTitleLow, 'cek qris') || str_contains($sheetTitleLow, 'p. yusuf') || str_contains($sheetTitleLow, 'qris')) {
                    continue;
                }

                $headerRows = [];
                try {
                    $rows = $sheet->toArray(null, true, false, false);
                    $headerRows = array_slice($rows, 0, 20);
                } catch (\Throwable $e) {
                    continue;
                }

                // 1. Cek apakah sheet title secara spesifik menyebut nama/kode toko (misal: 'PS Kemutug Lor', 'PS.Gumelar', 'PS Kalitapen')
                $sheetShop = self::detectOutletFromSheetNameOnly($sheetTitle, $shops);

                if ($sheetShop) {
                    $detectedShop = $sheetShop;
                } elseif ($fileLevelShop) {
                    // 2. Jika sheet title tidak menyebut toko lain tapi nama file sudah spesifik (misal: 'PENJUALAN PS.SUMINGKIR.xlsx'), gunakan nama file!
                    $detectedShop = $fileLevelShop;
                } else {
                    // 3. Fallback: deteksi dari isi header sel hanya jika nama file & sheet title tidak spesifik
                    $detectedShop = self::detectOutletFromSheet($sheetTitle, $headerRows, $shops);
                }

                if ($detectedShop) {
                    // Ekstrak periode spesifik untuk sheet ini (misal: 'jul25' -> 2025-07, 'Juni 26' -> 2026-06)
                    $sheetPeriod = self::parsePeriodFromSheetName($sheetTitle);
                    if ($sheetPeriod === 'Multi-Periode') {
                        $sheetPeriod = self::parsePeriodFromSheetName($fileIdentifier);
                    }

                    $groupKey = $detectedShop->id . '_' . $sheetPeriod;

                    if (!isset($shopPeriodGroups[$groupKey])) {
                        $shopPeriodGroups[$groupKey] = [
                            'shop' => $detectedShop,
                            'period' => $sheetPeriod,
                            'sheet_names' => [],
                        ];
                    }
                    $shopPeriodGroups[$groupKey]['sheet_names'][] = $sheetTitle;
                }
            }

            // Fase 2: Per outlet dan per periode yang terpartisi, jalankan extraction engine
            foreach ($shopPeriodGroups as $group) {
                $shop = $group['shop'];
                $period = $group['period'];

                // Gunakan extract() dengan objek $spreadsheet yang sudah dimuat
                $summary = self::extract($spreadsheet, $shop, $period);

                $results[] = [
                    'shop' => $shop,
                    'shop_id' => $shop->id,
                    'shop_nama' => $shop->nama,
                    'period' => $period ?? 'Multi-Periode',
                    'summary' => $summary,
                    'matched_sheets' => $group['sheet_names'],
                ];
            }

            // Fase 3: Jika masih kosong tapi ada fileLevelShop, jalankan untuk toko itu
            if (empty($results) && $fileLevelShop) {
                $allSheetNames = [];
                foreach ($spreadsheet->getAllSheets() as $sh) {
                    $allSheetNames[] = $sh->getTitle();
                }

                $detectedPeriod = self::parsePeriodFromSheetName($fileIdentifier);
                $summary = self::extract($spreadsheet, $fileLevelShop, $detectedPeriod);

                $results[] = [
                    'shop' => $fileLevelShop,
                    'shop_id' => $fileLevelShop->id,
                    'shop_nama' => $fileLevelShop->nama,
                    'period' => $detectedPeriod ?? 'Multi-Periode',
                    'summary' => $summary,
                    'matched_sheets' => $allSheetNames,
                ];
            }

            // Urutkan partisi hasil secara kronologis: dari yang paling lama ke paling baru (ASC)
            usort($results, function ($a, $b) {
                return strcmp($a['period'], $b['period']);
            });

        } catch (\Throwable $e) {
            Log::error("extractMultiShopFromFile Error: {$filePath} — " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Proses batch dari beberapa file sekaligus (1–12 files).
     * Mengembalikan array gabungan hasil per outlet.
     *
     * @param array $fileItems Array of file paths ATAU array of ['fullPath' => ..., 'originalFilename' => ..., 'storedPath' => ..., 'fileSize' => ...]
     * @param \Illuminate\Support\Collection|null $shops
     * @param callable|null $progressCallback fn(int $fileIndex, int $totalFiles, string $filename)
     * @return array [shop_id => ['shop' => Shop, 'period' => '...', 'summary' => [...], 'source_files' => [...], 'stored_path' => '...', 'original_filename' => '...']]
     */
    public static function processMultipleFiles(array $fileItems, $shops = null, ?callable $progressCallback = null): array
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '1024M');

        if ($shops === null) {
            $shops = Shop::all();
        }

        $allResults = [];
        $totalFiles = count($fileItems);

        foreach ($fileItems as $fileIdx => $item) {
            $filePath = is_array($item) ? ($item['fullPath'] ?? '') : $item;
            $originalFilename = is_array($item) ? ($item['originalFilename'] ?? basename($filePath)) : basename($filePath);
            $storedPath = is_array($item) ? ($item['storedPath'] ?? '') : '';
            $fileSize = is_array($item) ? ($item['fileSize'] ?? 0) : 0;

            if ($progressCallback) {
                $progressCallback($fileIdx + 1, $totalFiles, $originalFilename);
            }

            $fileResults = self::extractMultiShopFromFile($filePath, $shops, $originalFilename);

            foreach ($fileResults as $shopId => $result) {
                $result['stored_path'] = $storedPath;
                $result['original_filename'] = $originalFilename;
                $result['file_size'] = $fileSize;
                $result['source_files'] = [$originalFilename];
                $allResults[] = $result;
            }
        }

        return $allResults;
    }

    /**
     * Mesin ekstraksi untuk format Sheet Laporan Penjualan Harian (Daily Sales Sheet).
     */
    public static function parseDailySalesSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, ?Shop $targetShop, ?Carbon $periodObj): ?array
    {
        // Cari sheet harian yang cocok untuk target shop
        $dailySheet = null;
        $gajiSheet = null;
        $targetShopAliases = $targetShop ? self::getShopAliases($targetShop) : [];

        foreach ($spreadsheet->getAllSheets() as $sh) {
            $title = $sh->getTitle();
            $titleLow = strtolower($title);
            $isGaji = str_contains($titleLow, 'gaji');

            if ($targetShop) {
                // 1. Prioritaskan kecocokan kode Pertamina spesifik (misal 53240, 53143, dst.)
                $kodeClean = str_replace(['.', ' ', '-', '_'], '', strtolower($targetShop->kode ?? ''));
                if (!empty($kodeClean) && str_contains(str_replace(['.', ' ', '-', '_'], '', $titleLow), $kodeClean)) {
                    if ($isGaji) {
                        $gajiSheet = $sh;
                    } else {
                        $dailySheet = $sh;
                    }
                    continue;
                }

                // 2. Cek alias nama toko jika belum ditemukan via kode
                if (!$dailySheet) {
                    foreach ($targetShopAliases as $al) {
                        if (str_contains($titleLow, strtolower($al))) {
                            if ($isGaji) {
                                $gajiSheet = $sh;
                            } else {
                                $dailySheet = $sh;
                            }
                            break;
                        }
                    }
                }
            } else {
                if (!$isGaji && (str_contains($titleLow, 'ps') || str_contains($titleLow, 'penjualan') || preg_match('/^(jul|ags|sep|okt|nov|des|jan|feb|mar|apr|mei|jun)/i', $title))) {
                    $dailySheet = $sh;
                }
            }
        }

        // 3. Jika belum ditemukan (misal file khusus 1 outlet berisi sheet bulanan: jul25, ags25, Juni 26, dst.)
        if (!$dailySheet && $periodObj) {
            $monthNum = $periodObj->month;
            $monthNames = [
                1 => ['jan', 'januari'],
                2 => ['feb', 'februari'],
                3 => ['mar', 'maret'],
                4 => ['apr', 'april'],
                5 => ['mei', 'may'],
                6 => ['jun', 'juni'],
                7 => ['jul', 'juli'],
                8 => ['ags', 'agustus', 'agu'],
                9 => ['sep', 'sept', 'september'],
                10 => ['okt', 'oktober'],
                11 => ['nov', 'november'],
                12 => ['des', 'desember'],
            ];
            $validMonths = $monthNames[$monthNum] ?? [];

            foreach ($spreadsheet->getAllSheets() as $sh) {
                $t = strtolower(trim($sh->getTitle()));
                if (str_contains($t, 'gaji') || str_contains($t, 'qris') || str_contains($t, 'yusuf')) continue;
                foreach ($validMonths as $vm) {
                    if (str_contains($t, $vm)) {
                        $dailySheet = $sh;
                        break 2;
                    }
                }
            }
        }

        // Fallback jika hanya 1 sheet di spreadsheet
        if (!$dailySheet && $spreadsheet->getSheetCount() === 1) {
            $dailySheet = $spreadsheet->getActiveSheet();
        }

        if (!$dailySheet) {
            return null;
        }

        $rows = $dailySheet->toArray(null, true, false, false);
        if (count($rows) < 3) {
            return null;
        }

        // Scan header kolom
        $totAwalCol = 3;
        $totAkhirCol = 4;
        $volCol = 5;
        $rpCol = 6;
        $testPumpCol = 9;
        $bbmDatangCol = 10; // CURAH
        $stokAwalCol = 12;
        $stokAkhirCol = 12; // STOK AKTUAL
        $lossesCol = 13;
        $lossesRpCol = 14;
        $ongkosBongkarCol = null;
        $biayaTfCol = null;
        $atkCol = null;
        $listrikCol = null;
        $airCol = null;
        $cashbackCol = null;
        $internetCol = null;
        $lainLainCol = null;
        $headerEndIdx = 2;

        for ($rIdx = 0; $rIdx < min(5, count($rows)); $rIdx++) {
            foreach ($rows[$rIdx] as $cIdx => $val) {
                if (!is_string($val)) continue;
                $vLow = strtolower(trim($val));
                if (str_contains($vLow, 'totalisator awal')) $totAwalCol = $cIdx;
                if (str_contains($vLow, 'totalisator akhir')) $totAkhirCol = $cIdx;
                if (str_contains($vLow, 'penjualan liter') || str_contains($vLow, 'toritis penjualan') || str_contains($vLow, 'aktual penjualan')) $volCol = $cIdx;
                if (str_contains($vLow, 'test pump')) $testPumpCol = $cIdx;
                if (str_contains($vLow, 'curah') || str_contains($vLow, 'bbm datang')) $bbmDatangCol = $cIdx;
                if (str_contains($vLow, 'stok aktual') || str_contains($vLow, 'stok akhir')) $stokAkhirCol = $cIdx;
                if (str_contains($vLow, 'gain/losess') || str_contains($vLow, 'losses')) $lossesCol = $cIdx;
                if (str_contains($vLow, 'ongkos bongkar')) $ongkosBongkarCol = $cIdx;
            }
            if ($totAwalCol !== null && $totAkhirCol !== null) {
                $headerEndIdx = $rIdx + 1;
            }
        }
        $rpCol = $volCol + 1;
        $lossesRpCol = $lossesCol + 1;

        // Akumulasi baris-baris harian
        $totAwalFirst = null;
        $totAkhirLast = null;
        $sumVol = 0;
        $sumRp = 0;
        $sumTestPump = 0;
        $sumBbmDatang = 0;
        $firstStokAwal = null;
        $lastStokAkhir = null;
        $sumLosses = 0;
        $sumLossesRp = 0;
        $sumOngkosBongkar = 0;
        $sumBiayaTf = 0;
        $sumAtk = 0;
        $sumListrik = 0;
        $sumAir = 0;
        $sumCashback = 0;
        $sumInternet = 0;
        $sumLainLain = 0;
        $activeDays = 0;

        for ($rIdx = $headerEndIdx; $rIdx < count($rows); $rIdx++) {
            $row = $rows[$rIdx];
            $tglVal = $row[1] ?? ($row[0] ?? null);

            // Valid daily row check (Excel serial date > 40000 or day 1-31)
            $isValidDateRow = (is_numeric($tglVal) && (($tglVal >= 1 && $tglVal <= 31) || $tglVal > 40000));
            $volRaw = self::cleanNumeric($row[$volCol] ?? 0);
            
            // Skip bottom summary rows or negative formula error rows
            if ($volRaw < 0 || $volRaw > 50000) {
                continue;
            }

            if ($isValidDateRow || ($volRaw > 0 && $rIdx < 95)) {
                $tAwal = self::cleanNumeric($row[$totAwalCol] ?? 0);
                $tAkhir = self::cleanNumeric($row[$totAkhirCol] ?? 0);
                $vol = $volRaw;
                $rp = self::cleanNumeric($row[$rpCol] ?? 0);
                $tp = self::cleanNumeric($row[$testPumpCol] ?? 0);
                $bbm = self::cleanNumeric($row[$bbmDatangCol] ?? 0);
                $stokAktual = self::cleanNumeric($row[$stokAkhirCol] ?? 0);
                $loss = self::cleanNumeric($row[$lossesCol] ?? 0);
                $lossRp = self::cleanNumeric($row[$lossesRpCol] ?? 0);

                if ($totAwalFirst === null && $tAwal > 1000) $totAwalFirst = $tAwal;
                if ($tAkhir > 1000) $totAkhirLast = $tAkhir;
                if ($firstStokAwal === null && $stokAktual > 0) $firstStokAwal = $stokAktual;
                if ($stokAktual > 0) $lastStokAkhir = $stokAktual;

                $sumVol += $vol;
                $sumRp += $rp;
                $sumTestPump += $tp;
                if ($bbm > 0 && $bbm < 50000) $sumBbmDatang += $bbm;
                $sumLosses += $loss;
                $sumLossesRp += $lossRp;
                if ($vol > 0) $activeDays++;

                if ($ongkosBongkarCol !== null) $sumOngkosBongkar += self::cleanNumeric($row[$ongkosBongkarCol] ?? 0);
            }
        }

        if ($sumVol <= 0 && $totAwalFirst === null) {
            return null;
        }

        // Ekstraksi Beban dari Sheet REKAP (Khusus Format Tipe B)
        $rekapSheet = null;
        foreach ($spreadsheet->getAllSheets() as $sh) {
            $t = strtolower($sh->getTitle());
            if (str_contains($t, 'rekap') && !str_contains($t, 'modal')) {
                $rekapSheet = $sh;
                break;
            }
        }

        $gajiOperator = 0;
        $totalBebanTipeB = 0;
        if ($rekapSheet) {
            $rRows = $rekapSheet->toArray(null, true, false, false);
            foreach ($rRows as $r) {
                $firstCell = strtolower(trim((string)($r[1] ?? $r[0] ?? '')));
                if (str_contains($firstCell, 'pengeluaran gaji') || str_contains($firstCell, 'total gaji')) {
                    foreach (array_slice($r, 2) as $cv) {
                        $num = self::cleanNumeric($cv);
                        if ($num > 0) { $gajiOperator = $num; break; }
                    }
                }
                if (str_contains($firstCell, 'pengeluaran lain') || str_contains($firstCell, 'lain-lain')) {
                    foreach (array_slice($r, 2) as $cv) {
                        $num = self::cleanNumeric($cv);
                        if ($num > 0) { $sumLainLain = $num; break; }
                    }
                }
            }
        }

        if ($gajiOperator <= 0) {
            $gajiOperator = 5000000; // Standar Gaji Gumelar
        }

        $totalBiaya = $gajiOperator + $sumLainLain + $sumOngkosBongkar;

        $hargaJual = 12200; // Standar Pertamax
        $hargaBeli = 11451.29; // Standar Pertamax HPP
        if ($sumVol > 0 && $sumRp > 0) {
            $calcHj = round($sumRp / $sumVol, 2);
            if ($calcHj >= 10000 && $calcHj <= 20000) {
                $hargaJual = $calcHj;
            }
        }

        $volumeTerjual = max(0, $sumVol - $sumTestPump);
        $labaKotor = ($volumeTerjual * ($hargaJual - $hargaBeli)) + $sumLossesRp;
        $labaBersih = $labaKotor - $totalBiaya;
        $alokasiModal10 = $labaBersih > 0 ? round($labaBersih * 0.10) : 0;
        $labaDibagi90 = $labaBersih > 0 ? round($labaBersih * 0.90) : 0;
        $totalSaldoLabaDibagi = $labaDibagi90;

        // Distribusi Investor
        $investors = self::buildInvestorDistributionsInternal($targetShop, $totalSaldoLabaDibagi, []);

        $segment = [
            'segmen_index'            => 1,
            'harga_beli'              => $hargaBeli,
            'harga_jual'              => $hargaJual,
            'stok_awal'               => $firstStokAwal ?? 0,
            'stok_awal_rp'            => ($firstStokAwal ?? 0) * $hargaBeli,
            'bbm_datang'              => $sumBbmDatang,
            'bbm_datang_rp'           => $sumBbmDatang * $hargaBeli,
            'jumlah_pembelian'        => ($firstStokAwal ?? 0) + $sumBbmDatang,
            'jumlah_pembelian_rp'     => (($firstStokAwal ?? 0) + $sumBbmDatang) * $hargaBeli,
            'totalisator_awal'        => $totAwalFirst ?? 0,
            'totalisator_akhir'       => $totAkhirLast ?? 0,
            'total_penjualan'         => $sumVol,
            'test_pump'               => $sumTestPump,
            'jumlah_penjualan'        => $sumVol - $sumTestPump,
            'jumlah_penjualan_rp'     => ($sumVol - $sumTestPump) * $hargaJual,
            'sisa_stok_teoretis'      => max(0, (($firstStokAwal ?? 0) + $sumBbmDatang) - ($sumVol - $sumTestPump)),
            'sisa_stok_teoretis_rp'   => max(0, (($firstStokAwal ?? 0) + $sumBbmDatang) - ($sumVol - $sumTestPump)) * $hargaBeli,
            'losses_gain'             => $sumLosses,
            'losses_gain_persen'      => $sumVol > 0 ? abs(round(($sumLosses / $sumVol) * 100, 2)) : 0,
            'losses_gain_rp'          => $sumLossesRp,
            'stok_akhir_fisik'        => $lastStokAkhir ?? 0,
            'stok_akhir_cm'           => 0,
            'stok_akhir_fisik_rp'     => ($lastStokAkhir ?? 0) * $hargaBeli,
            'jumlah_penjualan_bersih' => $sumVol - $sumTestPump,
            'laba_kotor'              => $labaKotor,
            'start_datetime_label'    => 'Awal Bulan',
            'end_datetime_label'      => 'Akhir Bulan',
        ];

        // Ambil Capital Recaps dari DB jika ada
        $capitalRecaps = [];
        if ($targetShop) {
            try {
                $dbRecaps = \App\Models\CapitalRecap::where('shop_id', $targetShop->id)->orderBy('tahun')->orderBy('bulan')->get();
                foreach ($dbRecaps as $dr) {
                    $capitalRecaps[] = [
                        'tahun_ke'                        => $dr->tahun_ke ?? 1,
                        'bulan'                           => $dr->bulan,
                        'tahun'                           => $dr->tahun,
                        'nilai_modal_awal'                => (float)($dr->nilai_modal_awal ?? 60000000),
                        'penyusutan_rugi'                 => (float)($dr->penyusutan_rugi ?? 0),
                        'penyusutan_pajak_bank'           => (float)($dr->penyusutan_pajak_bank ?? 0),
                        'penambahan_keuntungan'           => (float)($dr->penambahan_keuntungan ?? 0),
                        'penambahan_bunga_bank'           => (float)($dr->penambahan_bunga_bank ?? 0),
                        'nilai_penambahan_penyusutan'     => (float)($dr->nilai_penambahan_penyusutan ?? 0),
                        'akumulasi_penambahan_penyusutan' => (float)($dr->akumulasi_penambahan_penyusutan ?? 0),
                        'posisi_akhir_modal'              => (float)($dr->posisi_akhir_modal ?? 60000000),
                        'harga_beli_pertamax'             => (float)($dr->harga_beli_pertamax ?? $hargaBeli),
                        'konversi_liter'                  => (float)($dr->konversi_liter ?? 0),
                    ];
                }
            } catch (\Throwable $e) {}
        }

        $modalAwalDasar = $targetShop && $targetShop->modal_awal > 0 ? (float)$targetShop->modal_awal : 60000000;

        return [
            'hal1' => [
                'segments'               => [$segment],
                'grand_total_laba_kotor' => $labaKotor,
                'total_liter_terjual'    => $sumVol - $sumTestPump,
                'rata_rata_omset_harian' => $activeDays > 0 ? round(($sumVol - $sumTestPump) / $activeDays, 2) : 0,
                'sisa_do_mees'           => [
                    'stok_awal_kl' => 0, 'setor_kl' => 5.0, 'setoran_tunai' => 0,
                    'jumlah_kl' => 5.0, 'datang_kl' => 5.0, 'sisa_kl' => 0, 'harga_beli_1kl' => $hargaBeli * 1000
                ],
                'margin_history'         => $targetShop ? self::getMarginHistory($targetShop, $periodObj ?? Carbon::now()) : [],
                'final_stok_liter'       => $lastStokAkhir ?? 0,
                'final_stok_rp'          => ($lastStokAkhir ?? 0) * $hargaBeli,
                'final_harga_beli'       => $hargaBeli,
            ],
            'hal2' => [
                'pengeluaran_details'    => [
                    'gaji_operator'   => $gajiOperator,
                    'gaji_admin'      => 0,
                    'biaya_curah'     => $sumOngkosBongkar,
                    'biaya_tf'        => $sumBiayaTf,
                    'listrik'         => $sumListrik,
                    'air'             => $sumAir,
                    'cashback'        => $sumCashback,
                    'internet'        => $sumInternet,
                    'atk'             => $sumAtk,
                    'lain_lain'       => $sumLainLain,
                    'lain_lain_notes' => '',
                    'total_biaya'     => $totalBiaya,
                ],
                'total_biaya'            => $totalBiaya,
                'laba_bersih'            => $labaBersih,
                'alokasi_penambahan_modal' => $alokasiModal10,
                'saldo_laba_bersih_90'   => $labaDibagi90,
                'saldo_laba_sebelumnya'  => 0,
                'total_saldo_laba_dibagi' => $totalSaldoLabaDibagi,
                'investor_distributions' => $investors,
            ],
            'hal3' => [
                'saldo_awal_modal'        => $modalAwalDasar,
                'do_di_pertamina'         => 0,
                'uang_di_bank'            => 0,
                'kas_kecil'               => 0,
                'sisa_stok_pertashop_rp'  => ($lastStokAkhir ?? 0) * $hargaBeli,
                'hasil_belum_disetor'     => 0,
                'piutang'                 => 0,
                'subtotal_a'              => $modalAwalDasar,
                'bunga_bank'              => 0,
                'pajak_bank'              => 0,
                'profit_sharing_dibagi'   => $labaDibagi90,
                'penambahan_keuntungan'   => $alokasiModal10,
                'subtotal_b'              => $alokasiModal10 - $labaDibagi90,
                'subtotal_c'              => $modalAwalDasar + $alokasiModal10,
                'total_saldo_akhir_modal' => $modalAwalDasar + $alokasiModal10,
            ],
            'hal4' => [
                'capital_recaps'          => $capitalRecaps,
                'modal_awal_dasar'        => $modalAwalDasar,
                'total_akumulasi_modal'   => $alokasiModal10,
                'persen_penambahan_modal' => $modalAwalDasar > 0 ? round(($alokasiModal10 / $modalAwalDasar) * 100, 2) : 0,
                'grand_total_modal'       => $modalAwalDasar + $alokasiModal10,
                'persen_grand_total'      => 100,
            ],
            'totalisator_awal'       => $totAwalFirst ?? 0,
            'totalisator_akhir'      => $totAkhirLast ?? 0,
            'jumlah_liter_terjual'   => $sumVol - $sumTestPump,
            'test_pump'              => ['total_volume' => $sumTestPump, 'total_rp' => 0, 'details' => []],
            'pembelian_bbm'          => ['total_volume_kl' => $sumBbmDatang / 1000, 'total_volume_liter' => $sumBbmDatang, 'total_nominal' => $sumBbmDatang * $hargaBeli, 'details' => []],
            'stok_akhir'             => $lastStokAkhir ?? 0,
            'total_pengeluaran'      => ['total_rp' => $totalBiaya, 'category_totals' => [], 'details' => []],
            'total_belum_disetorkan' => ['total_rp' => 0, 'details' => []],
            'matched_sheet_name'     => $dailySheet->getTitle(),
        ];
    }
}


