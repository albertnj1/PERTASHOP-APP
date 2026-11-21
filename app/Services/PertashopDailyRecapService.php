<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\Operator;
use App\Models\Price;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PertashopDailyRecapService
{
    /**
     * Membangun dataset rekap harian lengkap (Kolom A-AY + Baris Rekap + Gaji Operator)
     * Sesuai template Excel "REKAP KMT 01-31 Maret 2026".
     *
     * @param int $shopId
     * @param int $month
     * @param int $year
     * @param float|null $setoranClosing
     * @return array
     */
    public static function getMonthlyRecap(int $shopId, int $month, int $year, ?float $setoranClosing = 0, ?array $overrideSaldoAwal = null): array
    {
        $shop = Shop::with(['corporation', 'operators.user'])->findOrFail($shopId);

        $startDate = Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta')->startOfDay();
        $daysInMonth = $startDate->daysInMonth;
        $endDate = Carbon::createFromDate($year, $month, $daysInMonth, 'Asia/Jakarta')->endOfDay();

        // 1. Parameter Gerai per Periode
        $params = self::resolveParameters($shop, $startDate);
        $saldoAwal = self::resolveSaldoAwal($shop, $startDate, $overrideSaldoAwal);

        // 2. Ambil Semua Laporan Harian Gerai pada Bulan Terkait
        $dailyReports = DailyReport::where('shop_id', $shopId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->whereNull('report_date')
                         ->whereBetween('created_at', [$startDate, $endDate]);
                  });
            })
            ->with(['spendings.spendingCategory', 'deposits.category', 'operator.user', 'testPumps', 'incomings'])
            ->orderBy('report_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Indeks laporan berdasarkan nomor hari (1..daysInMonth)
        $reportsByDay = [];
        $reportIds = [];
        foreach ($dailyReports as $report) {
            $rDate = $report->report_date ? Carbon::parse($report->report_date) : Carbon::parse($report->created_at);
            $dayNum = $rDate->day;
            if (!isset($reportsByDay[$dayNum])) {
                $reportsByDay[$dayNum] = $report;
            }
            $reportIds[] = $report->id;
        }

        // Ambil riwayat audit log untuk input operator yang pernah diedit pada laporan bulan ini
        $editedReportIds = [];
        if (!empty($reportIds) && class_exists(\App\Models\AuditLog::class) && \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
            $editedReportIds = \App\Models\AuditLog::where('action', 'UPDATE_OPERATOR_INPUT')
                ->where('model_type', 'DailyReport')
                ->whereIn('model_id', $reportIds)
                ->pluck('model_id')
                ->unique()
                ->toArray();
        }

        // Tentukan apakah ada hari yang inputnya diedit, dan temukan hari pertama yang diedit untuk efek kaskade
        $firstEditedDay = null;
        if (!empty($editedReportIds)) {
            foreach ($reportsByDay as $dayNum => $rep) {
                if (in_array($rep->id, $editedReportIds)) {
                    if ($firstEditedDay === null || $dayNum < $firstEditedDay) {
                        $firstEditedDay = $dayNum;
                    }
                }
            }
        }

        // 3. Bangun 31 Baris Harian (Kolom A s/d AY)
        $rows = [];
        $runningTotalisator = $saldoAwal['totalisator_awal_hari_pertama'];
        $runningStok = $saldoAwal['stok_awal_hari_pertama'];
        $runningBelumDisetor = $saldoAwal['uang_belum_disetor_periode_lalu'];

        // Nama-nama hari dan bulan dalam Bahasa Indonesia
        $hariIndo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $bulanIndo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

        $reportedCount = 0;
        $today = Carbon::today('Asia/Jakarta');

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDate = Carbon::createFromDate($year, $month, $d, 'Asia/Jakarta');
            $dayEnglish = $currentDate->format('l');
            $dayIndo = $hariIndo[$dayEnglish] ?? $dayEnglish;
            $monthIndo = $bulanIndo[$month] ?? $currentDate->format('F');
            $formattedDate = sprintf('%s, %02d %s %d', $dayIndo, $d, $monthIndo, $year);

            $hasReport = isset($reportsByDay[$d]);
            /** @var DailyReport|null $report */
            $report = $hasReport ? $reportsByDay[$d] : null;

            if ($hasReport) {
                $reportedCount++;
            }

            // D: Totalisator Awal
            // Jika hari 1 dan ada report->totalisator_awal, gunakan. Kalau tidak, pakai running
            if ($hasReport && floatval($report->totalisator_awal) > 0) {
                $totAwal = floatval($report->totalisator_awal);
            } else {
                $totAwal = $runningTotalisator;
            }

            // E: Totalisator Akhir
            $totAkhir = $hasReport ? floatval($report->totalisator_akhir) : $totAwal;

            // F: Penjualan Teoritis Volume = E - D
            $teoritisVol = max(0.0, $totAkhir - $totAwal);

            // G: Penjualan Teoritis Rupiah = F * harga_jual
            $teoritisRp = $teoritisVol * $params['harga_jual'];

            // H: Test Pump Volume
            $testPumpVol = $hasReport ? floatval($report->test_pump) : 0.0;

            // I: Test Pump Rupiah = H * harga_jual
            $testPumpRp = $testPumpVol * $params['harga_jual'];

            // J: Kirim Gerai / BBM Keluar Lain Volume
            $bbmKeluarLainVol = $hasReport ? floatval($report->bbm_keluar_lain ?? 0) : 0.0;

            // K: Kirim Gerai Rupiah = J * harga_kirim
            $bbmKeluarLainRp = $bbmKeluarLainVol * $params['harga_kirim'];

            // L: Aktual Penjualan Volume = F - H - J
            $aktualVol = max(0.0, $teoritisVol - $testPumpVol - $bbmKeluarLainVol);

            // M: Aktual Penjualan Rupiah = L * harga_jual
            $aktualRp = $aktualVol * $params['harga_jual'];

            // N: Stok Awal
            if ($hasReport && floatval($report->stok_awal) > 0) {
                $stokAwalRow = floatval($report->stok_awal);
            } else {
                $stokAwalRow = $runningStok;
            }

            // O: Pembelian BBM
            $pembelianBbm = 0.0;
            // P: Terima BBM
            $terimaBbm = $hasReport ? floatval($report->penerimaan) : 0.0;

            if ($hasReport) {
                // Pembelian dari incoming / purchase jika ada
                if ($report->incomings->isNotEmpty()) {
                    $pembelianBbm = floatval($report->incomings->sum('volume'));
                } elseif ($terimaBbm > 0) {
                    $pembelianBbm = $terimaBbm;
                }
            }

            // Q: Stok Teoritis = N + P - J - L (sesuai rumus Excel: Stok Awal + Terima BBM - BBM Keluar Lain - Penjualan Aktual)
            $stokTeoritis = $stokAwalRow + $terimaBbm - $bbmKeluarLainVol - $aktualVol;

            // R: Stok Aktual CM & S: Stok Aktual Volume
            // Jika belum ada laporan ($hasReport = false), jangan dianggap tangki 0 cm (yang memicu kerugian semu sebesar isi tangki).
            // Tanpa laporan, losses/gain adalah 0 (Pas), dan stok akhir membawa stok teoritis ke hari berikutnya.
            if ($hasReport) {
                $stokAktualCm = floatval($report->stik_akhir ?? 0);
                $stokAktualVol = $stokAktualCm * $params['koefisien_tangki'];
                $lossesGainLiter = $stokAktualVol - $stokTeoritis;
                $lossesGainRp = $lossesGainLiter * $params['harga_beli_pertamina'];

                if (abs($lossesGainLiter) < 0.00001) {
                    $statusLossesGain = 'Pas';
                } elseif ($lossesGainLiter < 0) {
                    $statusLossesGain = 'Losses';
                } else {
                    $statusLossesGain = 'Gain';
                }

                $stokAkhir = $stokAktualVol;
            } else {
                $stokAktualCm = 0.0;
                $stokAktualVol = 0.0;
                $lossesGainLiter = 0.0;
                $lossesGainRp = 0.0;
                $statusLossesGain = 'Pas';
                $stokAkhir = $stokTeoritis;
            }

            // X: Jumlah Actual Penjualan = M
            $jumlahActualPenjualan = $aktualRp;

            // Y - AF: Pengeluaran 8 Kategori
            $ongkosBongkar = 0.0;
            $biayaTransfer = 0.0;
            $fotocopyAtk   = 0.0;
            $listrik       = 0.0;
            $airBersih     = 0.0;
            $cashback      = 0.0;
            $internet      = 0.0;
            $lainLain      = 0.0;
            $keteranganLain = '';

            if ($hasReport && $report->spendings->isNotEmpty()) {
                foreach ($report->spendings as $sp) {
                    $catName = strtolower($sp->spendingCategory?->nama ?? '');
                    $amt = floatval($sp->jumlah);
                    if (str_contains($catName, 'bongkar')) {
                        $ongkosBongkar += $amt;
                    } elseif (str_contains($catName, 'transfer')) {
                        $biayaTransfer += $amt;
                    } elseif (str_contains($catName, 'atk') || str_contains($catName, 'fotocopy')) {
                        $fotocopyAtk += $amt;
                    } elseif (str_contains($catName, 'listrik')) {
                        $listrik += $amt;
                    } elseif (str_contains($catName, 'air')) {
                        $airBersih += $amt;
                    } elseif (str_contains($catName, 'cashback')) {
                        $cashback += $amt;
                    } elseif (str_contains($catName, 'internet') || str_contains($catName, 'wifi')) {
                        $internet += $amt;
                    } else {
                        $lainLain += $amt;
                        if (!empty($sp->keterangan)) {
                            $keteranganLain .= ($keteranganLain ? '; ' : '') . $sp->keterangan;
                        }
                    }
                }
            }

            // AG: Total Pengeluaran = SUM(Y:AF)
            $totalPengeluaran = $ongkosBongkar + $biayaTransfer + $fotocopyAtk + $listrik + $airBersih + $cashback + $internet + $lainLain;

            // AH: Keterangan Lain-lain
            $keteranganLainLain = $keteranganLain;

            // AJ: Pendapatan Dikurangi Pengeluaran = X - AG
            $pendapatanMinusPengeluaran = $jumlahActualPenjualan - $totalPengeluaran;

            // AL - AN: Setoran (AL: BRI, AM: QRIS BRI, AN: Transfer By Operator)
            $setoranBri = 0.0;
            $setoranQrisBri = 0.0;
            $setoranTransferOperator = 0.0;

            if ($hasReport) {
                if ($report->deposits->isNotEmpty()) {
                    foreach ($report->deposits as $dep) {
                        $destName = strtolower($dep->category?->nama ?? '');
                        $amt = floatval($dep->jumlah);
                        if (str_contains($destName, 'qris')) {
                            $setoranQrisBri += $amt;
                        } elseif (str_contains($destName, 'transfer')) {
                            $setoranTransferOperator += $amt;
                        } else {
                            $setoranBri += $amt;
                        }
                    }
                } else {
                    $setoranBri = floatval($report->setor_tunai ?? $report->disetorkan ?? 0);
                    $setoranQrisBri = floatval($report->setor_qris ?? 0);
                    $setoranTransferOperator = floatval($report->setor_transfer ?? 0);
                }
            }

            // AO: Selisih Uang Yang Disetorkan dg Setoran = (AG + AM + AL + AN) - X
            // Negatif = Kurang Setor
            $selisihSetoran = ($totalPengeluaran + $setoranBri + $setoranQrisBri + $setoranTransferOperator) - $jumlahActualPenjualan;

            // AP: Total uang yang belum disetorkan = AO hari ini + AP kemarin
            $uangBelumDisetor = $runningBelumDisetor + $selisihSetoran;

            // AQ: Keterangan Tanggal
            $keteranganTanggal = $formattedDate;

            // AR: Tanggal Gaji
            $tanggalGaji = $formattedDate;

            // AS: Omset Kotor = L
            $omsetKotor = $aktualVol;

            // AT: Status Gaji = V
            $statusGaji = $statusLossesGain;

            // AU: Losses / Gain Gaji = T
            $lossesGainGaji = $lossesGainLiter;

            // AV: Omset + Losses = IF(AT="Losses", AS + AU, AS)  (Gain TIDAK menambah)
            if (strtoupper($statusGaji) === 'LOSSES') {
                $omsetPlusLosses = $omsetKotor + $lossesGainGaji;
            } else {
                $omsetPlusLosses = $omsetKotor;
            }

            // AW: Gaji Harian Operator 1 = AV * komisi_per_liter
            $gajiHarianOpr1 = $omsetPlusLosses * $params['komisi_operator_per_liter'];

            // AX: Gaji Harian Operator 2 (kosong di baris harian Excel, dihitung di rekap bulanan)
            $gajiHarianOpr2 = 0.0;

            // AY: Total Gaji Harian = AW + AX
            $totalGajiHarian = $gajiHarianOpr1 + $gajiHarianOpr2;

            // Operator name & assignment: prioritaskan dari jadwal shift (ShiftSchedule) jika ada
            $shiftSchedule = \App\Models\ShiftSchedule::with('operator.user')
                ->where('shop_id', $shop->id)
                ->whereDate('tanggal', $currentDate->toDateString())
                ->first();

            $operatorName = $shiftSchedule?->operator?->user?->name 
                ?? $report?->operator?->user?->name 
                ?? '-';
            $operatorId = $shiftSchedule?->operator_id ?? $report?->operator_id ?? null;

            $isInputEdited = $hasReport && in_array($report->id, $editedReportIds);
            $isCascadeAffected = !$isInputEdited && ($firstEditedDay !== null && $d > $firstEditedDay);

            $rows[$d] = [
                'A_nomor_hari'                       => $d,
                'B_no_urut'                          => $d,
                'C_tanggal'                          => $formattedDate,
                'D_totalisator_awal'                 => $totAwal,
                'E_totalisator_akhir'                => $totAkhir,
                'F_penjualan_teoritis_vol'           => $teoritisVol,
                'G_penjualan_teoritis_rp'            => $teoritisRp,
                'H_test_pump_vol'                    => $testPumpVol,
                'I_test_pump_rp'                     => $testPumpRp,
                'J_bbm_keluar_lain_vol'              => $bbmKeluarLainVol,
                'K_bbm_keluar_lain_rp'              => $bbmKeluarLainRp,
                'L_penjualan_aktual_vol'             => $aktualVol,
                'M_penjualan_aktual_rp'              => $aktualRp,
                'N_stok_awal'                        => $stokAwalRow,
                'O_pembelian_bbm'                    => $pembelianBbm,
                'P_terima_bbm'                       => $terimaBbm,
                'Q_stok_teoritis'                    => $stokTeoritis,
                'R_stok_aktual_cm'                   => $stokAktualCm,
                'S_stok_aktual_vol'                  => $stokAktualVol,
                'T_losses_gain_liter'                => $lossesGainLiter,
                'U_losses_gain_rp'                   => $lossesGainRp,
                'V_status_losses_gain'               => $statusLossesGain,
                'W_stok_akhir'                       => $stokAkhir,
                'X_jumlah_actual_penjualan'          => $jumlahActualPenjualan,
                'Y_ongkos_bongkar'                   => $ongkosBongkar,
                'Z_biaya_transfer'                   => $biayaTransfer,
                'AA_fotocopy_atk'                    => $fotocopyAtk,
                'AB_listrik'                         => $listrik,
                'AC_air_bersih'                      => $airBersih,
                'AD_cashback'                        => $cashback,
                'AE_internet'                        => $internet,
                'AF_lain_lain'                       => $lainLain,
                'AG_total_pengeluaran'               => $totalPengeluaran,
                'AH_keterangan_lain_lain'            => $keteranganLainLain,
                'AJ_pendapatan_minus_pengeluaran'    => $pendapatanMinusPengeluaran,
                'AL_setoran_bri'                     => $setoranBri,
                'AM_setoran_qris_bri'                => $setoranQrisBri,
                'AN_setoran_transfer_operator'       => $setoranTransferOperator,
                'AO_selisih_setoran'                 => $selisihSetoran,
                'AP_total_uang_belum_disetor'        => $uangBelumDisetor,
                'AQ_keterangan_tanggal'              => $keteranganTanggal,
                'AR_tanggal_gaji'                    => $tanggalGaji,
                'AS_omset_kotor'                     => $omsetKotor,
                'AT_status_gaji'                     => $statusGaji,
                'AU_losses_gain_gaji'                => $lossesGainGaji,
                'AV_omset_plus_losses'               => $omsetPlusLosses,
                'AW_gaji_harian'                     => $gajiHarianOpr1,
                'AX_gaji_harian_operator_2'          => $gajiHarianOpr2,
                'AY_total_gaji_harian'               => $totalGajiHarian,
                // Metadata Baris
                'meta' => [
                    'has_report'           => $hasReport,
                    'report_id'            => $report?->id,
                    'is_today'             => $currentDate->isToday(),
                    'is_sunday'            => $currentDate->isSunday(),
                    'raw_date'             => $currentDate->toDateString(),
                    'operator_id'          => $operatorId,
                    'operator_name'        => $operatorName,
                    'status_lifecycle'     => $report?->status_lifecycle ?? 'unreported',
                    'is_edited'            => $isInputEdited,
                    'is_input_edited'      => $isInputEdited,
                    'is_cascade_affected'  => $isCascadeAffected,
                    'updated_at'           => $report?->updated_at?->toIso8601String(),
                    'updated_at_formatted' => $report?->updated_at?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
                ],
            ];

            // Update running carry-over untuk besok
            if ($totAkhir > 0) {
                $runningTotalisator = $totAkhir;
            }
            if ($stokAkhir > 0) {
                $runningStok = $stokAkhir;
            }
            $runningBelumDisetor = $uangBelumDisetor;
        }

        // 4. Hitung Baris Rekap Bulanan (Bagian 4 Spesifikasi)
        $summary = self::calculateMonthlySummary($rows, $saldoAwal, $params, $shop, $setoranClosing);

        return [
            'shop'             => $shop,
            'month'            => $month,
            'year'             => $year,
            'month_name'       => $bulanIndo[$month] ?? $startDate->format('F'),
            'days_in_month'    => $daysInMonth,
            'rekap_title'      => sprintf('REKAP %s 01-%02d %s %d', $shop->kode ?: 'KMT', $daysInMonth, $bulanIndo[$month] ?? '', $year),
            'kirim_label'      => sprintf('Kirim %s', $shop->nama),
            'parameters'       => $params,
            'saldo_awal'       => $saldoAwal,
            'rows'             => $rows,
            'summary'          => $summary,
            'statistics'       => [
                'reported_days'    => $reportedCount,
                'total_days'       => $daysInMonth,
                'unreported_days'  => $daysInMonth - $reportedCount,
                'progress_percent' => $daysInMonth > 0 ? round(($reportedCount / $daysInMonth) * 100) : 0,
            ],
        ];
    }

    /**
     * Menghitung Baris Rekap (Total, Rata-rata, Penutupan, Gaji Operator)
     */
    protected static function calculateMonthlySummary(array $rows, array $saldoAwal, array $params, Shop $shop, ?float $setoranClosing = 0): array
    {
        $sumF = 0.0; $sumG = 0.0; $sumH = 0.0; $sumI = 0.0;
        $sumJ = 0.0; $sumK = 0.0; $sumL = 0.0; $sumM = 0.0;
        $sumO = 0.0; $sumP = 0.0;
        $sumT = 0.0; $sumU = 0.0; $sumX = 0.0;
        $sumY = 0.0; $sumZ = 0.0; $sumAA = 0.0; $sumAB = 0.0;
        $sumAC = 0.0; $sumAD = 0.0; $sumAE = 0.0; $sumAF = 0.0;
        $sumAG = 0.0; $sumAJ = 0.0;
        $sumAL = 0.0; $sumAM = 0.0; $sumAN = 0.0;
        $sumAO = 0.0; $sumAU = 0.0; $sumAV = 0.0; $sumAW = 0.0; $sumAY = 0.0;

        $lastRow = end($rows);
        $ap35 = $lastRow['AP_total_uang_belum_disetor'] ?? 0.0;

        $positiveDaysCount = 0;
        $sumPositiveL = 0.0;
        $sumPositiveM = 0.0;

        $sumPembelian1000 = 0.0;
        $sumPembelian2000 = 0.0;
        $sumTerima1000 = 0.0;
        $sumTerima2000 = 0.0;

        // Distribusi AV per operator berdasarkan tugas di harian
        $operatorAvMap = [];

        foreach ($rows as $row) {
            $sumF += $row['F_penjualan_teoritis_vol'];
            $sumG += $row['G_penjualan_teoritis_rp'];
            $sumH += $row['H_test_pump_vol'];
            $sumI += $row['I_test_pump_rp'];
            $sumJ += $row['J_bbm_keluar_lain_vol'];
            $sumK += $row['K_bbm_keluar_lain_rp'];
            $sumL += $row['L_penjualan_aktual_vol'];
            $sumM += $row['M_penjualan_aktual_rp'];
            $sumO += $row['O_pembelian_bbm'];
            $sumP += $row['P_terima_bbm'];
            $sumT += $row['T_losses_gain_liter'];
            $sumU += $row['U_losses_gain_rp'];
            $sumX += $row['X_jumlah_actual_penjualan'];
            $sumY += $row['Y_ongkos_bongkar'];
            $sumZ += $row['Z_biaya_transfer'];
            $sumAA += $row['AA_fotocopy_atk'];
            $sumAB += $row['AB_listrik'];
            $sumAC += $row['AC_air_bersih'];
            $sumAD += $row['AD_cashback'];
            $sumAE += $row['AE_internet'];
            $sumAF += $row['AF_lain_lain'];
            $sumAG += $row['AG_total_pengeluaran'];
            $sumAJ += $row['AJ_pendapatan_minus_pengeluaran'];
            $sumAL += $row['AL_setoran_bri'];
            $sumAM += $row['AM_setoran_qris_bri'];
            $sumAN += $row['AN_setoran_transfer_operator'];
            $sumAO += $row['AO_selisih_setoran'];
            $sumAU += $row['AU_losses_gain_gaji'];
            $sumAV += $row['AV_omset_plus_losses'];
            $sumAW += $row['AW_gaji_harian'];
            $sumAY += $row['AY_total_gaji_harian'];

            if ($row['L_penjualan_aktual_vol'] > 0) {
                $positiveDaysCount++;
                $sumPositiveL += $row['L_penjualan_aktual_vol'];
                $sumPositiveM += $row['M_penjualan_aktual_rp'];
            }

            // Pecahan 1000 & 2000
            if (abs($row['O_pembelian_bbm'] - 1000.0) < 1.0) $sumPembelian1000 += $row['O_pembelian_bbm'];
            if (abs($row['O_pembelian_bbm'] - 2000.0) < 1.0) $sumPembelian2000 += $row['O_pembelian_bbm'];
            if (abs($row['P_terima_bbm'] - 1000.0) < 1.0) $sumTerima1000 += $row['P_terima_bbm'];
            if (abs($row['P_terima_bbm'] - 2000.0) < 1.0) $sumTerima2000 += $row['P_terima_bbm'];

            // Petakan AV ke operator
            $opId = $row['meta']['operator_id'];
            if ($opId) {
                if (!isset($operatorAvMap[$opId])) {
                    $operatorAvMap[$opId] = 0.0;
                }
                $operatorAvMap[$opId] += $row['AV_omset_plus_losses'];
            }
        }

        // Total Pembelian & Terima baris 35 (termasuk baris 3 saldo awal pembelian jika ada)
        $totalPembelianBbmO35 = ($saldoAwal['pembelian_lintas_bulan_lalu'] ?? 0) + $sumO;
        $totalTerimaBbmP35    = $sumP;

        // V35: status bulanan
        if (abs($sumT) < 0.00001) {
            $v35Status = 'Pas';
        } elseif ($sumT < 0) {
            $v35Status = 'Losses';
        } else {
            $v35Status = 'Gain';
        }

        // AJ35 = SUM(AJ4:AK34) - AJ3 (di Excel AJ3 bernilai negatif, misal -26020818, sehingga dikurangi negatif = ditambah)
        $aj35 = $sumAJ - $saldoAwal['uang_belum_disetor_periode_lalu'];

        // AO35 = SUM(AO3:AO34) = saldo_awal + sum(AO harian)
        $ao35 = $saldoAwal['uang_belum_disetor_periode_lalu'] + $sumAO;

        // Baris 36: Rata-rata Penjualan Aktual (hanya hari > 0)
        $avgLiterF36 = $positiveDaysCount > 0 ? ($sumPositiveL / $positiveDaysCount) : 0.0;
        $avgRupiahG36 = $positiveDaysCount > 0 ? ($sumPositiveM / $positiveDaysCount) : 0.0;

        // AL36: Total Seluruh Setoran = AL35 + AM35 + AN35
        $totalSetoranAL36 = $sumAL + $sumAM + $sumAN;

        // AJ36: Selisih Setoran Bulanan = AL36 - AJ35 (Harus sama dengan AP35 dan AO35!)
        $selisihBulananAJ36 = $totalSetoranAL36 - $aj35;

        // AJ37: Setoran Closing
        $closingAJ37 = floatval($setoranClosing ?? 0);

        // AJ38: Sisa Setoran yang Belum Disetor = AJ36 + AJ37
        $sisaSetoranAJ38 = $selisihBulananAJ36 + $closingAJ37;

        // Gaji Operator (Baris 37-39)
        $shopOperators = $shop->operators()->with('user')->get();
        $operatorSalaries = [];
        $totalOperatorAv = 0.0;
        $totalOperatorGaji = 0.0;

        foreach ($shopOperators as $op) {
            $avForOp = $operatorAvMap[$op->id] ?? 0.0;
            $gajiOp = $avForOp * $params['komisi_operator_per_liter'];
            $operatorSalaries[] = [
                'operator_id'   => $op->id,
                'operator_name' => $op->user?->name ?? 'Operator ' . $op->id,
                'total_av'      => $avForOp,
                'rate_komisi'   => $params['komisi_operator_per_liter'],
                'gaji'          => $gajiOp,
            ];
            $totalOperatorAv += $avForOp;
            $totalOperatorGaji += $gajiOp;
        }

        // Cek Silang Otomatis
        $crossCheckBalance = (abs($selisihBulananAJ36 - $ap35) < 1.0);
        $crossCheckAv = (abs($totalOperatorAv - $sumAV) < 0.01 || empty($shopOperators));
        $crossCheckGaji = (abs($totalOperatorGaji - $sumAW) < 1.0 || empty($shopOperators));

        return [
            'row_total_35' => [
                'F_penjualan_teoritis_vol'        => $sumF,
                'G_penjualan_teoritis_rp'         => $sumG,
                'H_test_pump_vol'                 => $sumH,
                'I_test_pump_rp'                  => $sumI,
                'J_bbm_keluar_lain_vol'           => $sumJ,
                'K_bbm_keluar_lain_rp'           => $sumK,
                'L_penjualan_aktual_vol'          => $sumL,
                'M_penjualan_aktual_rp'           => $sumM,
                'O_pembelian_bbm'                 => $totalPembelianBbmO35,
                'P_terima_bbm'                    => $totalTerimaBbmP35,
                'T_losses_gain_liter'             => $sumT,
                'U_losses_gain_rp'                => $sumU,
                'V_status_losses_gain'            => $v35Status,
                'X_jumlah_actual_penjualan'       => $sumX,
                'Y_ongkos_bongkar'                => $sumY,
                'Z_biaya_transfer'                => $sumZ,
                'AA_fotocopy_atk'                 => $sumAA,
                'AB_listrik'                      => $sumAB,
                'AC_air_bersih'                   => $sumAC,
                'AD_cashback'                     => $sumAD,
                'AE_internet'                     => $sumAE,
                'AF_lain_lain'                    => $sumAF,
                'AG_total_pengeluaran'            => $sumAG,
                'AJ_pendapatan_minus_pengeluaran' => $aj35,
                'AL_setoran_bri'                  => $sumAL,
                'AM_setoran_qris_bri'             => $sumAM,
                'AN_setoran_transfer_operator'    => $sumAN,
                'AO_selisih_setoran'              => $ao35,
                'AP_total_uang_belum_disetor'     => $ap35,
                'AU_losses_gain_gaji'             => $sumAU,
                'AV_omset_plus_losses'            => $sumAV,
                'AW_gaji_harian'                  => $sumAW,
                'AY_total_gaji_harian'            => $sumAY,
            ],
            'row_averages_closing_36_39' => [
                'F36_avg_liter'        => $avgLiterF36,
                'G36_avg_rupiah'       => $avgRupiahG36,
                'AL36_total_disetor'   => $totalSetoranAL36,
                'AJ36_selisih_disetor' => $selisihBulananAJ36,
                'AJ37_setoran_closing' => $closingAJ37,
                'AJ38_sisa_setoran'    => $sisaSetoranAJ38,
                'O38_pembelian_1kl'    => $sumPembelian1000,
                'P38_terima_1kl'       => $sumTerima1000,
                'O39_pembelian_2kl'    => $sumPembelian2000,
                'P39_terima_2kl'       => $sumTerima2000,
            ],
            'operator_salaries' => $operatorSalaries,
            'operator_salaries_summary' => [
                'total_av'   => $totalOperatorAv,
                'total_gaji' => $totalOperatorGaji,
            ],
            'cross_checks' => [
                'aj36_equals_ap35'             => $crossCheckBalance,
                'operator_liters_match_av35'   => $crossCheckAv,
                'operator_salaries_match_aw35' => $crossCheckGaji,
                'pembelian_equals_terima'      => (abs($totalPembelianBbmO35 - $totalTerimaBbmP35) < 1.0),
            ],
        ];
    }

    /**
     * Resolusi Parameter per Gerai berdasarkan tanggal efektif
     */
    public static function resolveParameters(Shop $shop, Carbon $date): array
    {
        $evalDate = $date->toDateString();

        $price = Price::where(function ($q) use ($shop) {
                $q->where('shop_id', $shop->id)->orWhereNull('shop_id');
            })
            ->where('effective_at', '<=', $evalDate . ' 23:59:59')
            ->orderBy('effective_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $hargaJual = $price ? floatval($price->harga_jual) : 12200.0;
        $hargaBeli = $price ? floatval($price->harga_beli) : 11276.08;
        $hargaKirim = 12950.0; // Default Kemutug / parameter

        $koefisienTangki = floatval($shop->skala) > 0 ? floatval($shop->skala) : 21.49;

        // Komisi Operator per Liter dari Payroll System atau Default
        $komisiOpr = 200.0;
        $ps = $shop->payrollSystems()->where('aktif', true)->first();
        if ($ps && $ps->ada_rate_per_liter) {
            $komisiOpr = floatval($ps->rate_per_liter);
        }

        return [
            'harga_jual'                => $hargaJual,
            'harga_kirim'               => $hargaKirim,
            'harga_beli_pertamina'      => $hargaBeli,
            'koefisien_tangki'          => $koefisienTangki,
            'komisi_operator_per_liter' => $komisiOpr,
        ];
    }

    /**
     * Resolusi Saldo Awal Bulan (diambil dari akhir bulan sebelumnya, tabel starting balance, atau master shop)
     */
    public static function resolveSaldoAwal(Shop $shop, Carbon $monthStart, ?array $overrideSaldoAwal = null): array
    {
        // 1. Override langsung via argumen / request
        if ($overrideSaldoAwal !== null) {
            return [
                'totalisator_awal_hari_pertama'   => floatval($overrideSaldoAwal['totalisator_awal_hari_pertama'] ?? $shop->totalisator_awal ?? 0),
                'stok_awal_hari_pertama'          => floatval($overrideSaldoAwal['stok_awal_hari_pertama'] ?? $shop->stok_awal ?? ($shop->stik_awal * ($shop->skala ?: 21.49))),
                'uang_belum_disetor_periode_lalu' => floatval($overrideSaldoAwal['uang_belum_disetor_periode_lalu'] ?? $shop->uang_belum_disetor_awal ?? 0),
                'pembelian_lintas_bulan_lalu'     => floatval($overrideSaldoAwal['pembelian_lintas_bulan_lalu'] ?? 0),
            ];
        }

        $year = (int) $monthStart->year;
        $month = (int) $monthStart->month;

        // 2. Cek apakah ada konfigurasi manual saldo awal di tabel shop_starting_balances
        if (\Illuminate\Support\Facades\Schema::hasTable('shop_starting_balances')) {
            $manualBalance = \App\Models\ShopStartingBalance::where('shop_id', $shop->id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            if ($manualBalance) {
                // Periksa apakah ada laporan di bulan sebelumnya untuk membandingkan estafet
                $prevMonthEnd = $monthStart->copy()->subDay()->endOfDay();
                $prevReport = DailyReport::where('shop_id', $shop->id)
                    ->where(function ($q) use ($prevMonthEnd) {
                        $q->where('report_date', '<=', $prevMonthEnd->toDateString())
                          ->orWhere(function ($q2) use ($prevMonthEnd) {
                              $q2->whereNull('report_date')->where('created_at', '<=', $prevMonthEnd);
                          });
                    })
                    ->orderBy('report_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();

                $warningMessage = null;
                if ($prevReport) {
                    $estafetTot = floatval($prevReport->totalisator_akhir);
                    $estafetStok = floatval($prevReport->stok_akhir_aktual);
                    $estafetUang = floatval($prevReport->running_balance_setoran);

                    $diffTot = abs(floatval($manualBalance->totalisator_awal) - $estafetTot);
                    $diffStok = abs(floatval($manualBalance->stok_awal) - $estafetStok);
                    $diffUang = abs(floatval($manualBalance->uang_belum_disetor) - $estafetUang);

                    if ($diffTot > 0.001 || $diffStok > 0.001 || $diffUang > 0.01) {
                        $warningMessage = sprintf(
                            "PERINGATAN: Input manual saldo awal periode %02d/%d berbeda dari estafet bulan lalu. Manual (Tot: %s, Stok: %s, Uang: %s) vs Estafet (Tot: %s, Stok: %s, Uang: %s).",
                            $month, $year,
                            $manualBalance->totalisator_awal, $manualBalance->stok_awal, $manualBalance->uang_belum_disetor,
                            $estafetTot, $estafetStok, $estafetUang
                        );
                        \Illuminate\Support\Facades\Log::warning($warningMessage);
                    }
                }

                return [
                    'totalisator_awal_hari_pertama'   => floatval($manualBalance->totalisator_awal),
                    'stok_awal_hari_pertama'          => floatval($manualBalance->stok_awal),
                    'uang_belum_disetor_periode_lalu' => floatval($manualBalance->uang_belum_disetor),
                    'pembelian_lintas_bulan_lalu'     => 0.0,
                    'is_manual_override'              => true,
                    'warning'                         => $warningMessage,
                ];
            }
        }

        // 3. Otomatis estafet dari hari terakhir bulan sebelumnya
        $prevMonthEnd = $monthStart->copy()->subDay()->endOfDay();

        $prevReport = DailyReport::where('shop_id', $shop->id)
            ->where(function ($q) use ($prevMonthEnd) {
                $q->where('report_date', '<=', $prevMonthEnd->toDateString())
                  ->orWhere(function ($q2) use ($prevMonthEnd) {
                      $q2->whereNull('report_date')->where('created_at', '<=', $prevMonthEnd);
                  });
            })
            ->orderBy('report_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($prevReport) {
            return [
                'totalisator_awal_hari_pertama'   => floatval($prevReport->totalisator_akhir),
                'stok_awal_hari_pertama'          => floatval($prevReport->stok_akhir_aktual),
                'uang_belum_disetor_periode_lalu' => floatval($prevReport->running_balance_setoran),
                'pembelian_lintas_bulan_lalu'     => 0.0,
            ];
        }

        // 4. Fallback jika bulan pertama gerai dan tidak ada laporan bulan lalu:
        // Cek data hari pertama di bulan terkait bila rekalkulasi berantai telah mengisi running_balance
        $firstMonthReport = DailyReport::where('shop_id', $shop->id)
            ->where(function ($q) use ($monthStart) {
                $monthEnd = $monthStart->copy()->endOfMonth();
                $q->whereBetween('report_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                  ->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                      $q2->whereNull('report_date')->whereBetween('created_at', [$monthStart, $monthEnd]);
                  });
            })
            ->orderBy('report_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->first();

        $totalisatorAwal = floatval($shop->totalisator_awal ?: 0);
        $stokAwal = floatval($shop->stok_awal ?: ($shop->stik_awal * ($shop->skala ?: 21.49)));
        $uangBelumDisetor = floatval($shop->uang_belum_disetor_awal ?: 0);

        if ($firstMonthReport && $firstMonthReport->running_balance_setoran !== null) {
            if ($uangBelumDisetor == 0.0) {
                $uangBelumDisetor = floatval($firstMonthReport->running_balance_setoran) - floatval($firstMonthReport->selisih_setoran);
            }
            if ($totalisatorAwal == 0.0 && floatval($firstMonthReport->totalisator_awal) > 0) {
                $totalisatorAwal = floatval($firstMonthReport->totalisator_awal);
            }
            if ($stokAwal == 0.0 && floatval($firstMonthReport->stok_awal) > 0) {
                $stokAwal = floatval($firstMonthReport->stok_awal);
            }
        }

        return [
            'totalisator_awal_hari_pertama'   => $totalisatorAwal,
            'stok_awal_hari_pertama'          => $stokAwal,
            'uang_belum_disetor_periode_lalu' => $uangBelumDisetor,
            'pembelian_lintas_bulan_lalu'     => 0.0,
        ];
    }

    /**
     * Ringkasan Pemantauan Seluruh Gerai Hari Ini untuk Dashboard Superadmin (WIB / Asia/Jakarta)
     */
    public static function getMultiShopTodayOverview(): Collection
    {
        $shops = Shop::with('corporation')->where('is_active', true)->get();
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        return $shops->map(function ($shop) use ($today) {
            $todayReport = DailyReport::where('shop_id', $shop->id)
                ->where(function ($q) use ($today) {
                    $q->where('report_date', $today)
                      ->orWhere(function ($q2) use ($today) {
                          $q2->whereNull('report_date')->whereDate('created_at', $today);
                      });
                })
                ->latest()
                ->first();

            $latestReport = DailyReport::where('shop_id', $shop->id)->latest('created_at')->first();

            return [
                'shop_id'             => $shop->id,
                'shop_nama'           => $shop->nama,
                'shop_kode'           => $shop->kode,
                'corporation_nama'    => $shop->corporation?->nama ?? '-',
                'has_today_report'    => !is_null($todayReport),
                'status_lifecycle'    => $todayReport?->status_lifecycle ?? 'Belum Lapor',
                'today_liter_jual'    => $todayReport ? floatval($todayReport->volume_penjualan_aktual) : 0.0,
                'today_rupiah_jual'   => $todayReport ? floatval($todayReport->rupiah_penjualan_aktual) : 0.0,
                'latest_stok_liter'   => $latestReport ? floatval($latestReport->stok_akhir_aktual) : 0.0,
                'uang_belum_disetor'  => $latestReport ? floatval($latestReport->running_balance_setoran ?? $shop->uang_belum_disetor_awal ?? 0) : floatval($shop->uang_belum_disetor_awal ?? 0),
                'last_reported_date'  => $latestReport ? ($latestReport->report_date ? $latestReport->report_date->format('d/m/Y') : $latestReport->created_at->format('d/m/Y')) : '-',
            ];
        });
    }
}

