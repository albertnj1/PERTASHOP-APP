<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\MonthlyReport;
use App\Models\CapitalRecap;
use App\Models\DailyReport;
use App\Models\Price;
use App\Models\Spending;
use App\Models\Investor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyReportCalculationService
{
    /**
     * Membangun data lengkap 4 Halaman Laporan Bulanan untuk toko & periode tertentu.
     */
    public static function buildReportData(MonthlyReport $report): array
    {
        $shop = $report->shop ?? Shop::findOrFail($report->shop_id);
        $period = Carbon::parse($report->bulan_tahun);
        $month = $period->month;
        $year = $period->year;
        $daysInMonth = $period->daysInMonth;

        $dataParsed = is_array($report->data_parsed) ? $report->data_parsed : [];

        // ─────────────────────────────────────────────────────────────────
        // 1. HALAMAN 1: Laporan Stok, Penjualan & Laba Kotor (Per Segmen Harga)
        // ─────────────────────────────────────────────────────────────────
        $segments = $dataParsed['segments'] ?? [];
        if (empty($segments)) {
            $segments = self::buildSegmentsFromDaily($shop, $month, $year, $dataParsed);
        }

        $grandTotalLabaKotor = 0;
        $totalLiterTerjual = 0;
        $activeDays = 0;
        $finalStokFisikLiter = 0;
        $finalStokFisikRp = 0;
        $finalHargaBeli = 0;

        foreach ($segments as $seg) {
            $grandTotalLabaKotor += floatval($seg['laba_kotor'] ?? 0);
            $totalLiterTerjual += floatval($seg['jumlah_penjualan'] ?? 0);
            $finalStokFisikLiter = floatval($seg['stok_akhir_fisik'] ?? $seg['stok_akhir'] ?? 0);
            $finalStokFisikRp = floatval($seg['stok_akhir_fisik_rp'] ?? 0);
            $finalHargaBeli = floatval($seg['harga_beli'] ?? 0);
        }

        if (isset($dataParsed['grand_laba_kotor']) && $dataParsed['grand_laba_kotor'] > 0) {
            $grandTotalLabaKotor = floatval($dataParsed['grand_laba_kotor']);
        }

        // Daily row count for average calculation
        $dailyData = $dataParsed['daily_data'] ?? [];
        $activeDays = count($dailyData) > 0 ? count($dailyData) : $daysInMonth;
        $rataRataOmsetHarian = $activeDays > 0 ? ($totalLiterTerjual / $activeDays) : 0;

        // DO di Mees / Pertamina
        $sisaDoMees = $dataParsed['sisa_do_mees'] ?? [
            'stok_awal_kl'   => 0,
            'setor_kl'       => round(floatval($report->do_di_pertamina) / max(1, ($finalHargaBeli > 0 ? $finalHargaBeli * 1000 : 15334810)), 2),
            'setoran_tunai'  => 0,
            'jumlah_kl'      => 0,
            'datang_kl'      => 0,
            'sisa_kl'        => 0,
            'harga_beli_1kl' => $finalHargaBeli * 1000,
        ];

        // Margin History Footnote
        $marginHistory = self::getMarginHistory($shop, $period);

        // ─────────────────────────────────────────────────────────────────
        // 2. HALAMAN 2: Laporan Laba Bersih & Profit Sharing
        // ─────────────────────────────────────────────────────────────────
        $operatorSalaries = $dataParsed['operator_salaries'] ?? [];
        $totalGajiOperator = collect($operatorSalaries)->sum('gaji');
        if ($totalGajiOperator <= 0 && isset($dataParsed['total_gaji_karyawan_excel'])) {
            $totalGajiOperator = floatval($dataParsed['total_gaji_karyawan_excel']);
        }

        // Biaya Operasional 10 Kategori
        $pengeluaranDetails = self::buildPengeluaranDetails($shop, $month, $year, $dataParsed, $totalGajiOperator, $report);
        $totalBiaya = $pengeluaranDetails['total_biaya'];

        $labaBersih = $grandTotalLabaKotor - $totalBiaya;
        $alokasiPenambahanModal10 = $labaBersih > 0 ? round($labaBersih * 0.10) : 0;
        $saldoLabaBersih90 = $labaBersih > 0 ? round($labaBersih * 0.90) : 0;
        $saldoLabaSebelumnya = floatval($dataParsed['saldo_laba_sebelumnya'] ?? 0);
        $totalSaldoLabaDibagi = $saldoLabaBersih90 + $saldoLabaSebelumnya;

        // Distribusi Investor & Checklist Rekening Transfer
        $investorDistributions = self::buildInvestorDistributions($shop, $totalSaldoLabaDibagi, $dataParsed);

        // ─────────────────────────────────────────────────────────────────
        // 3. HALAMAN 3: Posisi Modal Kerja (Neraca Likuiditas)
        // ─────────────────────────────────────────────────────────────────
        // Saldo Awal Modal diambil dari bulan sebelumnya
        $saldoAwalModal = self::resolveSaldoAwalModal($shop, $month, $year, $report);

        $doDiPertamina = floatval($report->do_di_pertamina ?? 0);
        $uangDiBank = floatval($report->uang_di_bank ?? 0);
        $kasKecil = floatval($report->kas_kecil ?? 0);
        $sisaStokPertashopRp = $finalStokFisikRp > 0 ? $finalStokFisikRp : floatval($dataParsed['sisa_stok_rp'] ?? ($finalStokFisikLiter * $finalHargaBeli));
        $hasilBelumDisetor = floatval($dataParsed['belum_disetorkan_rp'] ?? 0);
        if ($hasilBelumDisetor <= 0 && count($dailyData) > 0) {
            $lastRow = end($dailyData);
            $hasilBelumDisetor = floatval($lastRow['setoran']['belum_setor'] ?? 0);
        }
        $piutang = floatval($report->piutang ?? 0);

        // Sub Total A: Saldo Awal Modal Bulan Sebelumnya (= Total Aset Lancar Awal/Akhir)
        $subtotalASaldoAkhirModal = $saldoAwalModal;

        $bungaBank = floatval($report->bunga_bank ?? 0);
        $pajakBank = abs(floatval($report->pajak_bank ?? 0));
        $profitSharingDibagi = $totalSaldoLabaDibagi;
        $penambahanModalKeuntungan = $labaBersih >= 0 ? $alokasiPenambahanModal10 : $labaBersih;

        $subtotalBPenambahan = $bungaBank - $pajakBank + $profitSharingDibagi + $penambahanModalKeuntungan;
        $subtotalCSaldoModal = $subtotalASaldoAkhirModal + $subtotalBPenambahan;
        $totalSaldoAkhirModal = $subtotalCSaldoModal - $profitSharingDibagi;

        // ─────────────────────────────────────────────────────────────────
        // 4. HALAMAN 4: Rekapitulasi Nilai Modal Historis (Multi-Bulan)
        // ─────────────────────────────────────────────────────────────────
        $capitalRecaps = CapitalRecap::where('shop_id', $shop->id)
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();

        $modalAwalDasar = floatval($shop->modal_awal > 0 ? $shop->modal_awal : 60000000);
        $latestRecap = $capitalRecaps->last();
        $posisiAkhirModalTerakhir = $latestRecap ? floatval($latestRecap->posisi_akhir_modal) : $totalSaldoAkhirModal;
        $totalAkumulasiPenambahan = $latestRecap ? floatval($latestRecap->akumulasi_penambahan_penyusutan) : ($totalSaldoAkhirModal - $modalAwalDasar);
        $persenPenambahan = $modalAwalDasar > 0 ? ($totalAkumulasiPenambahan / $modalAwalDasar) * 100 : 0;
        $persenTotalModal = 100 + $persenPenambahan;

        return [
            'shop'                     => $shop,
            'period'                   => $period,
            'report'                   => $report,
            'monthName'                => $period->isoFormat('MMMM YYYY'),
            
            // Hal 1
            'segments'                 => $segments,
            'grand_total_laba_kotor'   => $grandTotalLabaKotor,
            'total_liter_terjual'      => $totalLiterTerjual,
            'rata_rata_omset_harian'   => $rataRataOmsetHarian,
            'active_days'              => $activeDays,
            'sisa_do_mees'             => $sisaDoMees,
            'margin_history'           => $marginHistory,
            'final_stok_liter'         => $finalStokFisikLiter,
            'final_stok_rp'            => $finalStokFisikRp,
            'final_harga_beli'         => $finalHargaBeli,

            // Hal 2
            'pengeluaran_details'      => $pengeluaranDetails,
            'total_biaya'              => $totalBiaya,
            'laba_bersih'              => $labaBersih,
            'alokasi_penambahan_modal' => $alokasiPenambahanModal10,
            'saldo_laba_bersih_90'     => $saldoLabaBersih90,
            'saldo_laba_sebelumnya'    => $saldoLabaSebelumnya,
            'total_saldo_laba_dibagi'  => $totalSaldoLabaDibagi,
            'investor_distributions'   => $investorDistributions,
            'operator_salaries'        => $operatorSalaries,

            // Hal 3
            'saldo_awal_modal'         => $saldoAwalModal,
            'do_di_pertamina'          => $doDiPertamina,
            'uang_di_bank'             => $uangDiBank,
            'kas_kecil'                => $kasKecil,
            'sisa_stok_pertashop_rp'   => $sisaStokPertashopRp,
            'hasil_belum_disetor'      => $hasilBelumDisetor,
            'piutang'                  => $piutang,
            'subtotal_a'               => $subtotalASaldoAkhirModal,
            'bunga_bank'               => $bungaBank,
            'pajak_bank'               => $pajakBank,
            'profit_sharing_dibagi'    => $profitSharingDibagi,
            'penambahan_keuntungan'    => $penambahanModalKeuntungan,
            'subtotal_b'               => $subtotalBPenambahan,
            'subtotal_c'               => $subtotalCSaldoModal,
            'total_saldo_akhir_modal'  => $totalSaldoAkhirModal,

            // Hal 4
            'capital_recaps'           => $capitalRecaps,
            'modal_awal_dasar'         => $modalAwalDasar,
            'total_akumulasi_modal'    => $totalAkumulasiPenambahan,
            'persen_penambahan_modal'  => $persenPenambahan,
            'grand_total_modal'        => $posisiAkhirModalTerakhir,
            'persen_grand_total'       => $persenTotalModal,
        ];
    }

    /**
     * Membangun rincian 10 pengeluaran operasional (Hal 2).
     */
    private static function buildPengeluaranDetails(Shop $shop, int $month, int $year, array $dataParsed, float $totalGajiOperator, MonthlyReport $report): array
    {
        $dailyData = $dataParsed['daily_data'] ?? [];
        $extraList = $dataParsed['pengeluaran_extra'] ?? $report->pengeluaran_extra ?? [];

        $gajiOperator = $totalGajiOperator;
        $gajiAdmin    = 500000; // Standar default PT SAM atau dari excel/daily
        $biayaCurah   = 0;
        $biayaTf      = 0;
        $listrik      = 0;
        $air          = 0;
        $cashback     = 0;
        $internet     = 0;
        $atk          = 0;
        $lainLain     = 0;
        $lainLainNotes = [];

        // Sum up from daily report rows
        foreach ($dailyData as $row) {
            $b = $row['biaya'] ?? [];
            $biayaCurah += floatval($b['bongkar'] ?? 0);
            $biayaTf    += floatval($b['tf'] ?? 0);
            $atk        += floatval($b['atk'] ?? 0);
            $listrik    += floatval($b['listrik'] ?? 0);
            $air        += floatval($b['air'] ?? 0);
            $cashback   += floatval($b['cashback'] ?? 0);
            $internet   += floatval($b['internet'] ?? 0);
            $lainLain   += floatval($b['lain_lain_rp'] ?? 0);
            if (!empty($b['lain_lain_ket'])) {
                $lainLainNotes[] = $b['lain_lain_ket'];
            }
        }

        // Add from extra spendings if defined
        if (is_array($extraList)) {
            foreach ($extraList as $ext) {
                $ket = strtolower($ext['keterangan'] ?? '');
                $nom = floatval($ext['nominal'] ?? 0);
                if (str_contains($ket, 'admin')) {
                    $gajiAdmin = $nom;
                } elseif (str_contains($ket, 'curah') || str_contains($ket, 'bongkar')) {
                    $biayaCurah += $nom;
                } elseif (str_contains($ket, 'transfer')) {
                    $biayaTf += $nom;
                } elseif (str_contains($ket, 'listrik')) {
                    $listrik += $nom;
                } elseif (str_contains($ket, 'air')) {
                    $air += $nom;
                } elseif (str_contains($ket, 'cashback')) {
                    $cashback += $nom;
                } elseif (str_contains($ket, 'internet') || str_contains($ket, 'wifi')) {
                    $internet += $nom;
                } elseif (str_contains($ket, 'atk') || str_contains($ket, 'fotocopy')) {
                    $atk += $nom;
                } else {
                    $lainLain += $nom;
                    if (!empty($ext['keterangan'])) {
                        $lainLainNotes[] = $ext['keterangan'];
                    }
                }
            }
        }

        $totalBiaya = $gajiOperator + $gajiAdmin + $biayaCurah + $biayaTf + $listrik + $air + $cashback + $internet + $atk + $lainLain;

        if (isset($dataParsed['total_biaya']) && $dataParsed['total_biaya'] > 0 && abs($totalBiaya - floatval($dataParsed['total_biaya'])) > 1000) {
            $totalBiaya = floatval($dataParsed['total_biaya']);
        }

        return [
            'gaji_operator'   => $gajiOperator,
            'gaji_admin'      => $gajiAdmin,
            'biaya_curah'     => $biayaCurah,
            'biaya_tf'        => $biayaTf,
            'listrik'         => $listrik,
            'air'             => $air,
            'cashback'        => $cashback,
            'internet'        => $internet,
            'atk'             => $atk,
            'lain_lain'       => $lainLain,
            'lain_lain_notes' => implode(', ', array_unique($lainLainNotes)),
            'total_biaya'     => $totalBiaya,
        ];
    }

    /**
     * Membangun daftar distribusi investor, persentase saham, dan nomor rekening (Hal 2).
     */
    private static function buildInvestorDistributions(Shop $shop, float $totalLabaDibagi, array $dataParsed): array
    {
        $distributions = [];
        $parsedInvestors = $dataParsed['investors'] ?? [];

        if (!empty($parsedInvestors)) {
            foreach ($parsedInvestors as $inv) {
                $nama = $inv['nama'] ?? 'Investor';
                $persen = floatval($inv['persen'] ?? 0);
                $nominal = isset($inv['nominal']) ? floatval($inv['nominal']) : round($totalLabaDibagi * ($persen / 100));

                // Find matching Investor in database for account info
                $dbInv = Investor::whereHas('user', function($q) use ($nama) {
                    $q->where('name', 'like', '%' . $nama . '%');
                })->first();

                $distributions[] = [
                    'nama'                => $nama,
                    'persen'              => $persen,
                    'nominal'             => $nominal,
                    'nama_bank'           => $dbInv->nama_bank ?? 'Mandiri',
                    'no_rekening'         => $dbInv->no_rekening ?? '-',
                    'atas_nama_rekening'  => $dbInv->atas_nama_rekening ?? $nama,
                    'transfer_status'     => true,
                ];
            }
            return $distributions;
        }

        // Fallback to Shop -> Investors database relation
        $shopInvestors = $shop->investors()->with('user')->get();
        if ($shopInvestors->count() > 0) {
            foreach ($shopInvestors as $inv) {
                $persen = floatval($inv->pivot->persentase ?? 0);
                $nominal = round($totalLabaDibagi * ($persen / 100));
                $nama = $inv->user->name ?? 'Investor';

                $distributions[] = [
                    'nama'                => $nama,
                    'persen'              => $persen,
                    'nominal'             => $nominal,
                    'nama_bank'           => $inv->nama_bank ?? 'Mandiri',
                    'no_rekening'         => $inv->no_rekening ?? '-',
                    'atas_nama_rekening'  => $inv->atas_nama_rekening ?? $nama,
                    'transfer_status'     => true,
                ];
            }
            return $distributions;
        }

        // Corporate Default Fallback (PT SAM standard)
        return [
            ['nama' => 'PT. SAM', 'persen' => 70, 'nominal' => round($totalLabaDibagi * 0.70), 'nama_bank' => 'Mandiri', 'no_rekening' => '13900 2109 0000', 'atas_nama_rekening' => 'ADLAI BUDIARTO TJIPTO', 'transfer_status' => true],
            ['nama' => 'Pak Victor Edward Asrikin', 'persen' => 15, 'nominal' => round($totalLabaDibagi * 0.15), 'nama_bank' => 'Mandiri', 'no_rekening' => '13900 1724 2391', 'atas_nama_rekening' => 'MARLINA NATALIA SETIAWAN', 'transfer_status' => true],
            ['nama' => 'Pak Koko', 'persen' => 5, 'nominal' => round($totalLabaDibagi * 0.05), 'nama_bank' => 'Mandiri', 'no_rekening' => '90000 0679 3138', 'atas_nama_rekening' => 'KOKO ARIBOWO', 'transfer_status' => true],
            ['nama' => 'Pak Sugiyanto Kosim', 'persen' => 5, 'nominal' => round($totalLabaDibagi * 0.05), 'nama_bank' => 'Mandiri', 'no_rekening' => '13900 9204 6840', 'atas_nama_rekening' => 'SUGIYANTO KOSIM SINDU', 'transfer_status' => true],
            ['nama' => 'Pak Kaswari', 'persen' => 5, 'nominal' => round($totalLabaDibagi * 0.05), 'nama_bank' => 'BNI', 'no_rekening' => '0436 8454 88', 'atas_nama_rekening' => 'KASWARI', 'transfer_status' => true],
        ];
    }

    /**
     * Mengambil saldo awal modal dari bulan sebelumnya pada CapitalRecap atau fallback ke modal awal toko.
     */
    private static function resolveSaldoAwalModal(Shop $shop, int $month, int $year, MonthlyReport $report): float
    {
        $prevPeriod = Carbon::createFromDate($year, $month, 1)->subMonth();
        
        $prevRecap = CapitalRecap::where('shop_id', $shop->id)
            ->where('tahun', $prevPeriod->year)
            ->where('bulan', $prevPeriod->month)
            ->first();

        if ($prevRecap && floatval($prevRecap->posisi_akhir_modal) > 0) {
            return floatval($prevRecap->posisi_akhir_modal);
        }

        if (floatval($report->saldo_awal_modal) > 0) {
            return floatval($report->saldo_awal_modal);
        }

        return floatval($shop->modal_awal > 0 ? $shop->modal_awal : 60000000);
    }

    /**
     * Membangun segmen harga dari daily reports jika belum ada di data_parsed.
     */
    private static function buildSegmentsFromDaily(Shop $shop, int $month, int $year, array $dataParsed): array
    {
        $dailyReports = DailyReport::where('shop_id', $shop->id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($dailyReports->isEmpty()) {
            return [];
        }

        $firstRep = $dailyReports->first();
        $lastRep = $dailyReports->last();

        $price = Price::where('shop_id', $shop->id)->latest('effective_at')->first();
        $hargaBeli = $price ? floatval($price->harga_beli) : 15334.81;
        $hargaJual = $price ? floatval($price->harga_jual) : 16150;

        $stokAwal = floatval($firstRep->stok_awal ?? 0);
        $totalTerimaBbm = $dailyReports->sum('penerimaan');
        $jumlahPembelian = $stokAwal + $totalTerimaBbm;
        $jumlahPembelianRp = $jumlahPembelian * $hargaBeli;

        $totAwal = floatval($firstRep->totalisator_awal ?? 0);
        $totAkhir = floatval($lastRep->totalisator_akhir ?? 0);
        $totalPenjualanFisik = max(0, $totAkhir - $totAwal);
        $testPump = $dailyReports->sum('test_pump');
        $jumlahPenjualan = max(0, $totalPenjualanFisik - $testPump);
        $jumlahPenjualanRp = $jumlahPenjualan * $hargaJual;

        $sisaStokTeoretis = max(0, $jumlahPembelian - $jumlahPenjualan);
        $sisaStokTeoretisRp = $sisaStokTeoretis * $hargaBeli;

        $stokAkhirFisik = floatval($lastRep->stok_akhir_aktual ?? $sisaStokTeoretis);
        $stokAkhirFisikRp = $stokAkhirFisik * $hargaBeli;

        $lossesGain = $stokAkhirFisik - $sisaStokTeoretis;
        $lossesGainPersen = $jumlahPenjualan > 0 ? ($lossesGain / $jumlahPenjualan) * 100 : 0;
        $lossesGainRp = $lossesGain * $hargaBeli;

        $penjualanBersih = ($jumlahPenjualanRp + $sisaStokTeoretisRp) + $lossesGainRp;
        $labaKotor = $penjualanBersih - $jumlahPembelianRp;

        return [
            [
                'segmen_index'             => 1,
                'start_date'               => $firstRep->created_at->format("d M'y"),
                'end_date'                 => $lastRep->created_at->format("d M'y"),
                'start_datetime_label'     => $firstRep->created_at->format("d M'y") . " Jam 06.00",
                'end_datetime_label'       => $lastRep->created_at->format("d M'y") . " Jam 18.00",
                'harga_beli'               => $hargaBeli,
                'harga_jual'               => $hargaJual,
                'stok_awal'                => $stokAwal,
                'stok_awal_rp'             => $stokAwal * $hargaBeli,
                'bbm_datang'               => $totalTerimaBbm,
                'bbm_datang_rp'            => $totalTerimaBbm * $hargaBeli,
                'jumlah_pembelian'         => $jumlahPembelian,
                'jumlah_pembelian_rp'      => $jumlahPembelianRp,
                'totalisator_awal'         => $totAwal,
                'totalisator_akhir'        => $totAkhir,
                'total_penjualan'          => $totalPenjualanFisik,
                'test_pump'                => $testPump,
                'jumlah_penjualan'         => $jumlahPenjualan,
                'jumlah_penjualan_rp'      => $jumlahPenjualanRp,
                'sisa_stok_teoretis'       => $sisaStokTeoretis,
                'sisa_stok_teoretis_rp'    => $sisaStokTeoretisRp,
                'losses_gain'              => $lossesGain,
                'losses_gain_persen'       => $lossesGainPersen,
                'losses_gain_rp'           => $lossesGainRp,
                'stok_akhir_fisik'         => $stokAkhirFisik,
                'stok_akhir_cm'            => $lastRep->stik_akhir ?? 0,
                'stok_akhir_fisik_rp'      => $stokAkhirFisikRp,
                'jumlah_penjualan_bersih'  => $penjualanBersih,
                'laba_kotor'               => $labaKotor,
            ]
        ];
    }

    /**
     * Mengambil riwayat margin fluktuasi Pertamax untuk Footnote Hal 1.
     */
    private static function getMarginHistory(Shop $shop, Carbon $period): array
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
     * SINKRONISASI & CASCADING RECALCULATE HISTORIS (BACKDATE TRIGGER)
     * Menghitung ulang seluruh saldo awal & akhir berantai dari bulan terawal hingga bulan sekarang.
     */
    public static function syncAndRecalculate(Shop $shop): void
    {
        DB::transaction(function() use ($shop) {
            $recaps = CapitalRecap::where('shop_id', $shop->id)
                ->orderBy('tahun', 'asc')
                ->orderBy('bulan', 'asc')
                ->get();

            $modalAwalDasar = floatval($shop->modal_awal > 0 ? $shop->modal_awal : 60000000);
            $runningAccumulated = 0;
            $prevPosisiAkhir = null;

            foreach ($recaps as $index => $recap) {
                // Cari MonthlyReport terkait untuk sinkronisasi nilai laba, bunga, dan pajak
                $mReport = MonthlyReport::where('shop_id', $shop->id)
                    ->whereYear('bulan_tahun', $recap->tahun)
                    ->whereMonth('bulan_tahun', $recap->bulan)
                    ->first();

                if ($mReport) {
                    $labaBersih = floatval($mReport->data_parsed['laba_bersih'] ?? 0);
                    $recap->penambahan_keuntungan = $labaBersih > 0 ? round($labaBersih * 0.10) : 0;
                    $recap->penyusutan_rugi = $labaBersih < 0 ? $labaBersih : 0;
                    $recap->penambahan_bunga_bank = floatval($mReport->bunga_bank ?? 0);
                    $recap->penyusutan_pajak_bank = -abs(floatval($mReport->pajak_bank ?? 0));
                }

                // Modal Awal Berantai
                if ($index === 0) {
                    $modalAwal = floatval($recap->nilai_modal_awal > 0 ? $recap->nilai_modal_awal : $modalAwalDasar);
                } else {
                    $modalAwal = $prevPosisiAkhir !== null ? $prevPosisiAkhir : floatval($recap->nilai_modal_awal);
                    $recap->nilai_modal_awal = $modalAwal;
                }

                // Hitung Penambahan Bersih Bulan Ini
                $netChange = floatval($recap->penyusutan_rugi)
                           + floatval($recap->penyusutan_pajak_bank)
                           + floatval($recap->penambahan_keuntungan)
                           + floatval($recap->penambahan_bunga_bank);

                $recap->nilai_penambahan_penyusutan = $netChange;
                $runningAccumulated += $netChange;
                $recap->akumulasi_penambahan_penyusutan = $runningAccumulated;

                $posisiAkhir = $modalAwal + $netChange;
                $recap->posisi_akhir_modal = $posisiAkhir;
                $prevPosisiAkhir = $posisiAkhir;

                if (floatval($recap->harga_beli_pertamax) > 0) {
                    $recap->konversi_liter = $posisiAkhir / floatval($recap->harga_beli_pertamax);
                } else {
                    $recap->konversi_liter = 0;
                }

                $recap->saveQuietly();

                // Sinkronkan ke MonthlyReport agar Hal 3 matching 100% dengan Hal 4
                if ($mReport) {
                    $mReport->saldo_awal_modal = $modalAwal;
                    $mReport->saldo_akhir_modal = $posisiAkhir;
                    $mReport->saveQuietly();
                }
            }
        });
    }

    /**
     * Mensinkronkan data berkas Excel Backdate ke MonthlyReport dan CapitalRecap.
     *
     * @param \App\Models\BackdateExcelFile $backdateFile
     * @return MonthlyReport
     */
    public static function syncFromBackdateExcel(\App\Models\BackdateExcelFile $backdateFile): MonthlyReport
    {
        $shop = $backdateFile->shop ?? Shop::findOrFail($backdateFile->shop_id);
        $fullPath = storage_path('app/public/' . $backdateFile->file_path);
        if (!file_exists($fullPath) && \Illuminate\Support\Facades\Storage::disk('public')->exists($backdateFile->file_path)) {
            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($backdateFile->file_path);
        }

        $summary = BackdateExcelSummaryService::extract($fullPath, $shop, $backdateFile->bulan_tahun);
        $h1 = $summary['hal1'] ?? [];
        $h2 = $summary['hal2'] ?? [];
        $h3 = $summary['hal3'] ?? [];
        $h4 = $summary['hal4'] ?? [];

        $periodStr = $backdateFile->bulan_tahun;
        if (empty($periodStr) || $periodStr === 'Multi-Periode') {
            $periodStr = Carbon::now()->format('Y-m');
        }
        $period = Carbon::parse($periodStr . '-01');
        $month = $period->month;
        $year = $period->year;

        // Structured Data Parsed
        $structuredData = [
            'segments'                  => $h1['segments'] ?? [],
            'grand_laba_kotor'          => $h1['grand_total_laba_kotor'] ?? 0,
            'total_biaya'               => $h2['total_biaya'] ?? 0,
            'laba_bersih'               => $h2['laba_bersih'] ?? 0,
            'penambahan_modal_10'       => $h2['alokasi_penambahan_modal'] ?? 0,
            'laba_dibagi_90'            => $h2['saldo_laba_bersih_90'] ?? 0,
            'total_laba_dibagi'         => $h2['total_saldo_laba_dibagi'] ?? 0,
            'saldo_laba_sebelumnya'     => $h2['saldo_laba_sebelumnya'] ?? 0,
            'investors'                 => $h2['investor_distributions'] ?? [],
            'pengeluaran_details'       => $h2['pengeluaran_details'] ?? [],
            'rata_rata_penjualan'       => $h1['rata_rata_omset_harian'] ?? 0,
            'sisa_stok_rp'              => $h1['final_stok_rp'] ?? 0,
            'sisa_do_volume'            => $h1['sisa_do_mees']['sisa_kl'] ?? 0,
            'operator_salaries'         => [],
            'daily_data'                => [],
            'margin_history'            => $h1['margin_history'] ?? [],
        ];

        // 1. Create or Update Monthly Report
        $report = MonthlyReport::updateOrCreate(
            [
                'shop_id'     => $shop->id,
                'bulan_tahun' => $periodStr,
            ],
            [
                'data_parsed'       => $structuredData,
                'file_path'         => $backdateFile->file_path,
                'grand_totals'      => [
                    'total_volume'            => $h1['total_liter_terjual'] ?? 0,
                    'total_rupiah_penjualan'  => $h1['grand_total_laba_kotor'] ?? 0,
                    'total_pengeluaran'       => $h2['total_biaya'] ?? 0,
                    'total_pendapatan'        => $h2['laba_bersih'] ?? 0,
                ],
                'saldo_awal_modal'  => $h3['saldo_awal_modal'] ?? 60000000,
                'do_di_pertamina'   => $h3['do_di_pertamina'] ?? 0,
                'uang_di_bank'      => $h3['uang_di_bank'] ?? 0,
                'kas_kecil'         => $h3['kas_kecil'] ?? 0,
                'piutang'           => $h3['piutang'] ?? 0,
                'bunga_bank'        => $h3['bunga_bank'] ?? 0,
                'pajak_bank'        => $h3['pajak_bank'] ?? 0,
                'penyusutan_modal'  => ($h2['laba_bersih'] ?? 0) < 0 ? ($h2['laba_bersih'] ?? 0) : 0,
                'penambahan_modal'  => $h2['alokasi_penambahan_modal'] ?? 0,
                'saldo_akhir_modal' => $h3['total_saldo_akhir_modal'] ?? 60000000,
            ]
        );

        // 2. Import / Sync CapitalRecap rows from Excel if present in Hal 4
        if (!empty($h4['capital_recaps'])) {
            foreach ($h4['capital_recaps'] as $r) {
                CapitalRecap::updateOrCreate(
                    [
                        'shop_id' => $shop->id,
                        'bulan'   => $r['bulan'],
                        'tahun'   => $r['tahun'],
                    ],
                    [
                        'tahun_ke'                        => $r['tahun_ke'] ?? 1,
                        'nilai_modal_awal'                => $r['nilai_modal_awal'] ?? 0,
                        'penyusutan_rugi'                 => $r['penyusutan_rugi'] ?? 0,
                        'penyusutan_pajak_bank'           => $r['penyusutan_pajak_bank'] ?? 0,
                        'penambahan_keuntungan'           => $r['penambahan_keuntungan'] ?? 0,
                        'penambahan_bunga_bank'           => $r['penambahan_bunga_bank'] ?? 0,
                        'nilai_penambahan_penyusutan'     => $r['nilai_penambahan_penyusutan'] ?? 0,
                        'akumulasi_penambahan_penyusutan' => $r['akumulasi_penambahan_penyusutan'] ?? 0,
                        'posisi_akhir_modal'              => $r['posisi_akhir_modal'] ?? 0,
                        'harga_beli_pertamax'             => $r['harga_beli_pertamax'] ?? 0,
                        'konversi_liter'                  => $r['konversi_liter'] ?? 0,
                    ]
                );
            }
        } else {
            // Ensure this month's row exists in CapitalRecap
            $startDate = $shop->tanggal_mulai_operasional ?: $period->startOfMonth()->toDateString();
            $start = Carbon::parse($startDate)->startOfMonth();
            $diffInMonths = $start->diffInMonths($period->copy()->startOfMonth());
            $tahun_ke = floor($diffInMonths / 12) + 1;
            $hBeli = $h1['final_harga_beli'] ?? 15334.81;

            CapitalRecap::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'bulan'   => $month,
                    'tahun'   => $year,
                ],
                [
                    'tahun_ke'                        => $tahun_ke,
                    'nilai_modal_awal'                => $h3['saldo_awal_modal'] ?? 60000000,
                    'penyusutan_rugi'                 => ($h2['laba_bersih'] ?? 0) < 0 ? ($h2['laba_bersih'] ?? 0) : 0,
                    'penyusutan_pajak_bank'           => -abs($h3['pajak_bank'] ?? 0),
                    'penambahan_keuntungan'           => $h2['alokasi_penambahan_modal'] ?? 0,
                    'penambahan_bunga_bank'           => $h3['bunga_bank'] ?? 0,
                    'harga_beli_pertamax'             => $hBeli,
                ]
            );
        }

        // 3. Trigger Cascading Recalculation across all months for this shop
        self::syncAndRecalculate($shop);

        return $report->fresh();
    }
}
