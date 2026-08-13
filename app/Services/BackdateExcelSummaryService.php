<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class BackdateExcelSummaryService
{
    /**
     * Mengekstrak 8 poin kesimpulan laporan dari file Excel backdate.
     *
     * @param string $filePath Absolute path file Excel (.xlsx / .xls)
     * @return array
     */
    /**
     * Mengekstrak 8 poin kesimpulan laporan dari file Excel backdate.
     *
     * @param string $filePath Absolute path file Excel (.xlsx / .xls)
     * @param \App\Models\Shop|null $targetShop Toko target opsional untuk memilih sheet spesifik toko
     * @return array
     */
    public static function extract(string $filePath, ?\App\Models\Shop $targetShop = null): array
    {
        $summary = [
            'totalisator_awal'       => 0,
            'totalisator_akhir'      => 0,
            'jumlah_liter_terjual'   => 0,
            'test_pump'              => [
                'total_volume' => 0,
                'total_rp'     => 0,
                'details'      => [],
            ],
            'pembelian_bbm'          => [
                'total_volume_kl'    => 0,
                'total_volume_liter' => 0,
                'total_nominal'      => 0,
                'details'            => [],
            ],
            'stok_akhir'             => 0,
            'total_pengeluaran'      => [
                'total_rp'        => 0,
                'category_totals' => [],
                'details'         => [],
            ],
            'total_belum_disetorkan' => [
                'total_rp' => 0,
                'details'  => [],
            ],
            'matched_sheet_name'     => null,
        ];

        if (!file_exists($filePath)) {
            return $summary;
        }

        try {
            $ss = IOFactory::load($filePath);

            // 1. Cari Sheet Pembelian DO
            $doSheet = null;
            foreach ($ss->getAllSheets() as $sh) {
                $title = strtolower($sh->getTitle());
                if (str_contains($title, 'pembelian') || str_contains($title, 'do')) {
                    $doSheet = $sh;
                    break;
                }
            }

            // Extract Pembelian DO
            if ($doSheet) {
                $doRows = $doSheet->toArray(null, true, false, false);
                foreach (array_slice($doRows, 1) as $r) {
                    $tgl = trim((string)($r[0] ?? ''));
                    $kl = floatval($r[1] ?? 0);
                    $totalKotor = floatval($r[6] ?? $r[8] ?? 0);
                    $hargaPerLiter = floatval($r[10] ?? 0);

                    if (!empty($tgl) && ($kl > 0 || $totalKotor > 0)) {
                        $summary['pembelian_bbm']['total_volume_kl'] += $kl;
                        $summary['pembelian_bbm']['total_volume_liter'] += ($kl * 1000);
                        $summary['pembelian_bbm']['total_nominal'] += $totalKotor;
                        $summary['pembelian_bbm']['details'][] = [
                            'tgl'             => $tgl,
                            'jumlah_kl'       => $kl,
                            'total_nominal'   => $totalKotor,
                            'harga_per_liter' => $hargaPerLiter,
                        ];
                    }
                }
            }

            // 2. Cari Sheet BKH / Rekap Penjualan Harian (Gunakan Smart Matching jika $targetShop dioper)
            $bkhSheet = null;
            
            if ($targetShop) {
                $targetAliases = self::getShopAliases($targetShop);

                // Priority 1: Match sheet title with target shop aliases (e.g. KMT, KLT, KLB, PGL, GML, SMK, name, code)
                foreach ($ss->getAllSheets() as $sh) {
                    $title = strtolower($sh->getTitle());
                    $titleNoDot = str_replace(['.', ' ', '-'], '', $title);
                    
                    foreach ($targetAliases as $alias) {
                        if (str_contains($title, $alias) || str_contains($titleNoDot, $alias)) {
                            $bkhSheet = $sh;
                            break 2;
                        }
                    }
                }
            }

            // Priority 2: Standard BKH Sheet Search if target match not found
            if (!$bkhSheet) {
                foreach ($ss->getAllSheets() as $sh) {
                    $title = strtolower($sh->getTitle());
                    if (str_contains($title, 'rekap') || str_contains($title, 'bkh') || str_contains($title, 'harian') || str_contains($title, 'penjualan')) {
                        $firstRows = $sh->toArray(null, true, false, false);
                        foreach (array_slice($firstRows, 0, 4) as $r) {
                            foreach ($r as $val) {
                                if (is_string($val) && str_contains(strtolower($val), 'totalisator')) {
                                    $bkhSheet = $sh;
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }

            if (!$bkhSheet && $ss->getSheetCount() > 1) {
                $bkhSheet = $ss->getSheet(1);
            } elseif (!$bkhSheet) {
                $bkhSheet = $ss->getSheet(0);
            }

            if ($bkhSheet) {
                $summary['matched_sheet_name'] = $bkhSheet->getTitle();
                $rows = $bkhSheet->toArray(null, true, false, false);

                // Mapping Kolom
                $colMapping = [];
                $colPembelianBBM = null;
                $colTerimaBBM    = null;

                for ($rIdx = 0; $rIdx < min(4, count($rows)); $rIdx++) {
                    foreach ($rows[$rIdx] as $cIdx => $val) {
                        if (!is_string($val)) continue;
                        $vClean = strtolower(trim($val));
                        if (str_contains($vClean, 'totalisator awal')) $colMapping['tot_awal'] = $cIdx;
                        if (str_contains($vClean, 'totalisator akhir')) $colMapping['tot_akhir'] = $cIdx;
                        if (str_contains($vClean, 'toritis penjualan') || (str_contains($vClean, 'volume') && str_contains($vClean, 'ℓ'))) {
                            if (!isset($colMapping['vol_terjual'])) $colMapping['vol_terjual'] = $cIdx;
                        }
                        if (str_contains($vClean, 'test pump')) $colMapping['test_pump_vol'] = $cIdx;
                        if (str_contains($vClean, 'stok') && str_contains($vClean, 'awal')) $colMapping['stok_awal'] = $cIdx;
                        
                        if (str_contains($vClean, 'pembelian') && ($colPembelianBBM === null)) {
                            $colPembelianBBM = $cIdx;
                        }
                        if (str_contains($vClean, 'terima') && ($colTerimaBBM === null)) {
                            $colTerimaBBM = $cIdx;
                        }

                        if (str_contains($vClean, 'stok') && (str_contains($vClean, 'akhir') || str_contains($vClean, 'aktual'))) $colMapping['stok_akhir'] = $cIdx;
                    }
                }

                $colTotAwal     = $colMapping['tot_awal'] ?? 3;  // Col D
                $colTotAkhir    = $colMapping['tot_akhir'] ?? 4; // Col E
                $colVolTerjual  = $colMapping['vol_terjual'] ?? 5; // Col F
                $colTestPumpVol = $colMapping['test_pump_vol'] ?? 7; // Col H
                $colTestPumpRp  = $colTestPumpVol + 1; // Col I
                $colStokAkhir   = $colMapping['stok_akhir'] ?? 18; // Col S
                if ($colPembelianBBM === null) $colPembelianBBM = 14;
                if ($colTerimaBBM === null) $colTerimaBBM = 15;

                // Pengeluaran Categories (Col Y to AF)
                $spendingCategories = [
                    24 => 'Ongkos Bongkar',
                    25 => 'Biaya Transfer',
                    26 => 'Fotocopy & ATK',
                    27 => 'Listrik',
                    28 => 'Air Bersih',
                    29 => 'Cashback',
                    30 => 'Internet',
                    31 => 'Lain-lain',
                ];

                $firstTotAwal  = null;
                $lastTotAkhir  = null;
                $lastStokAkhir = null;

                $dailyBbmDetails = [];

                foreach ($rows as $rIdx => $r) {
                    if ($rIdx < 3) continue; // Skip header
                    $tglRaw = trim((string)($r[0] ?? $r[1] ?? ''));
                    if (empty($tglRaw) || !is_numeric($tglRaw)) continue;
                    $day = intval($tglRaw);
                    if ($day < 1 || $day > 31) continue;

                    $totAwal      = floatval($r[$colTotAwal] ?? 0);
                    $totAkhir     = floatval($r[$colTotAkhir] ?? 0);
                    $volTerjual   = floatval($r[$colVolTerjual] ?? 0);
                    $tpVol        = floatval($r[$colTestPumpVol] ?? 0);
                    $tpRp         = floatval($r[$colTestPumpRp] ?? 0);
                    $stokAkhirVal = floatval($r[$colStokAkhir] ?? $r[22] ?? 0);

                    if ($firstTotAwal === null && $totAwal > 0) {
                        $firstTotAwal = $totAwal;
                    }
                    if ($totAkhir > 0) {
                        $lastTotAkhir = $totAkhir;
                    }
                    if ($stokAkhirVal > 0) {
                        $lastStokAkhir = $stokAkhirVal;
                    }

                    $summary['jumlah_liter_terjual'] += $volTerjual;

                    // Pembelian BBM (Col Pembelian & Col Terima)
                    $pembelianVal = floatval($r[$colPembelianBBM] ?? 0);
                    if ($pembelianVal > 0) {
                        $volLiter = $pembelianVal >= 100 ? $pembelianVal : ($pembelianVal * 1000);
                        $volKl    = $volLiter / 1000;
                        $dailyBbmDetails[] = [
                            'tgl'             => 'Tanggal ' . sprintf('%02d', $day),
                            'tipe'            => 'Pembelian BBM',
                            'jumlah_kl'       => $volKl,
                            'jumlah_liter'    => $volLiter,
                            'total_nominal'   => 0,
                            'harga_per_liter' => 0,
                        ];
                    }

                    $terimaVal = floatval($r[$colTerimaBBM] ?? 0);
                    if ($terimaVal > 0) {
                        $volLiter = $terimaVal >= 100 ? $terimaVal : ($terimaVal * 1000);
                        $volKl    = $volLiter / 1000;
                        $dailyBbmDetails[] = [
                            'tgl'             => 'Tanggal ' . sprintf('%02d', $day),
                            'tipe'            => 'Terima BBM',
                            'jumlah_kl'       => $volKl,
                            'jumlah_liter'    => $volLiter,
                            'total_nominal'   => 0,
                            'harga_per_liter' => 0,
                        ];
                    }

                    // Test Pump
                    if ($tpVol > 0 || $tpRp > 0) {
                        $summary['test_pump']['total_volume'] += $tpVol;
                        $summary['test_pump']['total_rp'] += $tpRp;
                        $summary['test_pump']['details'][] = [
                            'tgl'     => 'Tanggal ' . $day,
                            'volume'  => $tpVol,
                            'nominal' => $tpRp,
                        ];
                    }

                    // Pengeluaran
                    foreach ($spendingCategories as $colIdx => $catName) {
                        $val = floatval($r[$colIdx] ?? 0);
                        if ($val > 0) {
                            $summary['total_pengeluaran']['total_rp'] += $val;
                            if (!isset($summary['total_pengeluaran']['category_totals'][$catName])) {
                                $summary['total_pengeluaran']['category_totals'][$catName] = 0;
                            }
                            $summary['total_pengeluaran']['category_totals'][$catName] += $val;
                            $summary['total_pengeluaran']['details'][] = [
                                'tgl'        => 'Tanggal ' . $day,
                                'kategori'   => $catName,
                                'nominal'    => $val,
                                'keterangan' => trim((string)($r[33] ?? '')),
                            ];
                        }
                    }

                    // Belum disetorkan (Selisih)
                    $selisih = floatval($r[40] ?? $r[41] ?? 0);
                    if ($selisih != 0) {
                        $summary['total_belum_disetorkan']['total_rp'] += $selisih;
                        $summary['total_belum_disetorkan']['details'][] = [
                            'tgl'        => 'Tanggal ' . $day,
                            'nominal'    => $selisih,
                            'keterangan' => trim((string)($r[42] ?? '')),
                        ];
                    }
                }

                // If no DO sheet was present, use daily BBM details
                if (empty($summary['pembelian_bbm']['details']) && !empty($dailyBbmDetails)) {
                    foreach ($dailyBbmDetails as $bDetail) {
                        $summary['pembelian_bbm']['total_volume_kl']    += $bDetail['jumlah_kl'];
                        $summary['pembelian_bbm']['total_volume_liter'] += $bDetail['jumlah_liter'];
                        $summary['pembelian_bbm']['details'][]           = $bDetail;
                    }
                }

                $summary['totalisator_awal']  = $firstTotAwal ?? 0;
                $summary['totalisator_akhir'] = $lastTotAkhir ?? 0;
                $summary['stok_akhir']        = $lastStokAkhir ?? 0;
            }
        } catch (\Throwable $e) {
            Log::error("BackdateExcelSummaryService Error parsing {$filePath}: " . $e->getMessage());
        }

        return $summary;
    }

    /**
     * Menghasilkan daftar kata kunci, kode, dan akronim (KMT, KLT, KLB, PGL, GML, SMK, dll.) untuk Pertashop.
     *
     * @param \App\Models\Shop $shop
     * @return array
     */
    public static function getShopAliases(\App\Models\Shop $shop): array
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
