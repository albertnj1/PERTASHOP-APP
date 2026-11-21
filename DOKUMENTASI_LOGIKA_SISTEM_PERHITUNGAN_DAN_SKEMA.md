# DOKUMENTASI LENGKAP LOGIKA SISTEM, SERVICE PERHITUNGAN, SKEMA TABEL & DATASET UJI EXCEL KEMUTUG
> Dokumen ini menggabungkan seluruh file sumber logika perhitungan utama, model, service akuntansi & penggajian, dump skema tabel database PostgreSQL 18, seluruh test suite pengujian, serta dataset acuan resmi Excel Kemutug Maret 2026 ke dalam 1 file Markdown utuh.

## 1. DUMP SKEMA TABEL DATABASE POSTGRESQL 18 (daily_reports, shops, shift_schedules)

```sql
======================================================
TABLE: daily_reports
======================================================
id                             bigint(64,0)              NOT NULL   DEFAULT nextval('daily_reports_id_seq'::regclass)
shop_id                        bigint(64,0)              NOT NULL
operator_id                    bigint(64,0)              NOT NULL
price_id                       bigint(64,0)              NOT NULL
totalisator_akhir              numeric(15,4)             NOT NULL
stik_akhir                     numeric(10,4)             NOT NULL
disetorkan                     numeric(18,4)             NOT NULL   DEFAULT '0'::numeric
diverifikasi                   boolean                   NOT NULL   DEFAULT false
created_at                     timestamp without time zone NULL
updated_at                     timestamp without time zone NULL
test_pump_volume               numeric(15,4)             NOT NULL   DEFAULT '0'::numeric
penerimaan_volume              numeric(15,4)             NOT NULL   DEFAULT '0'::numeric
kolektan_id                    bigint(64,0)              NULL
waktu_kolektan                 timestamp without time zone NULL
setor_kolektan                 numeric(15,2)             NOT NULL   DEFAULT '0'::numeric
totalisator_awal               double precision(53,)     NULL
stok_awal                      double precision(53,)     NULL
bbm_keluar_lain                numeric(15,4)             NOT NULL   DEFAULT '0'::numeric
foto_stik                      character varying(255)    NULL
klarifikasi_selisih            text                      NULL
excel_upload_id                bigint(64,0)              NULL
running_balance_setoran        numeric(18,4)             NOT NULL   DEFAULT '0'::numeric
status_lifecycle               character varying(20)     NOT NULL   DEFAULT 'draft'::character varying
imported_by                    bigint(64,0)              NULL
imported_at                    timestamp without time zone NULL
validated_by                   bigint(64,0)              NULL
validated_at                   timestamp without time zone NULL
approved_by                    bigint(64,0)              NULL
approved_at                    timestamp without time zone NULL
locked_by                      bigint(64,0)              NULL
locked_at                      timestamp without time zone NULL
business_rule_snapshot         json                      NULL
business_rule_version_ids      json                      NULL
sumber_data                    character varying(255)    NOT NULL   DEFAULT 'input_manual'::character varying
sumber_file_excel              character varying(255)    NULL
backdate_file_id               bigint(64,0)              NULL
stik_awal                      double precision(53,)     NULL
report_date                    date                      NULL
harga_jual_snapshot            numeric(14,4)             NULL
harga_beli_snapshot            numeric(14,4)             NULL
hutang_bakulan                 numeric(12,2)             NOT NULL   DEFAULT '0'::numeric
pelunasan_bakulan              numeric(12,2)             NOT NULL   DEFAULT '0'::numeric

======================================================
TABLE: shops (Gerai)
======================================================
id                             bigint(64,0)              NOT NULL   DEFAULT nextval('shops_id_seq'::regclass)
corporation_id                 bigint(64,0)              NOT NULL
nama                           character varying(255)    NOT NULL
kode                           character varying(255)    NOT NULL
alamat                         character varying(255)    NOT NULL
modal_awal                     numeric(15,2)             NOT NULL   DEFAULT '0'::numeric
kapasitas                      numeric(10,2)             NOT NULL   DEFAULT '3500'::numeric
stik_awal                      numeric(10,4)             NOT NULL   DEFAULT '0'::numeric
totalisator_awal               numeric(15,4)             NOT NULL   DEFAULT '0'::numeric
skala                          numeric(10,4)             NOT NULL   DEFAULT 21.49
created_at                     timestamp without time zone NULL
updated_at                     timestamp without time zone NULL
total_investasi                numeric(15,2)             NOT NULL   DEFAULT '0'::numeric
tanggal_mulai_operasional      date                      NULL
is_active                      boolean                   NOT NULL   DEFAULT true

======================================================
TABLE: shift_schedules (Jadwal Shift)
======================================================
id                             bigint(64,0)              NOT NULL   DEFAULT nextval('shift_schedules_id_seq'::regclass)
shop_id                        bigint(64,0)              NOT NULL
operator_id                    bigint(64,0)              NOT NULL
tanggal                        date                      NOT NULL
shift_ke                       smallint(16,0)            NOT NULL   DEFAULT '1'::smallint
status                         character varying(255)    NOT NULL   DEFAULT 'dijadwalkan'::character varying
keterangan                     text                      NULL
created_at                     timestamp without time zone NULL
updated_at                     timestamp without time zone NULL
jam_mulai                      time without time zone    NULL
jam_selesai                    time without time zone    NULL
```



# =============================================================================
# 2. CORE SERVICE: Rekap Harian & Bulanan 49 Kolom
# File: app/Services/PertashopDailyRecapService.php
# Single Source of Truth untuk seluruh rumus rekap harian, bulanan, komisi/gaji, dan saldo awal estafet
# =============================================================================

```php
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

            // R: Stok Aktual CM
            $stokAktualCm = $hasReport ? floatval($report->stik_akhir ?? 0) : 0.0;

            // S: Stok Aktual Volume = R * koefisien_tangki
            $stokAktualVol = $stokAktualCm * $params['koefisien_tangki'];

            // T: Nominal Losses / Gain Liter = S - Q
            $lossesGainLiter = $stokAktualVol - $stokTeoritis;

            // U: Nominal Rupiah = T * harga_beli_pertamina
            $lossesGainRp = $lossesGainLiter * $params['harga_beli_pertamina'];

            // V: Losses / Gain Status = IF(T=0, "Pas", IF(T<0, "Losses", "Gain"))
            if (abs($lossesGainLiter) < 0.00001) {
                $statusLossesGain = 'Pas';
            } elseif ($lossesGainLiter < 0) {
                $statusLossesGain = 'Losses';
            } else {
                $statusLossesGain = 'Gain';
            }

            // W: Stok Akhir = S
            $stokAkhir = $stokAktualVol;

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

```



# =============================================================================
# 3. MODEL ELOQUENT: DailyReport
# File: app/Models/DailyReport.php
# Model laporan harian, accessor atribut turunan, audit log input operator
# =============================================================================

```php
<?php

namespace App\Models;

use App\Models\Shop;
use App\Models\Price;
use App\Models\Incoming;
use App\Models\Operator;
use App\Models\Spending;
use App\Models\TestPump;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyReport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'diverifikasi'       => 'boolean',
        'report_date'        => 'date',
        'hutang_bakulan'     => 'float',
        'pelunasan_bakulan'  => 'float',
    ];

    protected static function booted()
    {
        static::saved(function ($report) {
            if ($report->sumber_data === 'import_excel_arsip') {
                return; // Data arsip backdate, menghentikan seluruh proses operasional lanjutan
            }
        });

        static::updated(function ($report) {
            if ($report->wasChanged()) {
                // Catat HANYA perubahan kolom input operator murni (Bagian 2 Spesifikasi),
                // BUKAN kolom turunan hasil hitung ulang berantai (totalisator_awal, stok_awal, running_balance_setoran, dll).
                $operatorInputColumns = [
                    'totalisator_akhir',
                    'stik_akhir',
                    'test_pump_volume',
                    'bbm_keluar_lain',
                    'penerimaan_volume',
                    'setor_tunai',
                    'setor_qris',
                    'setor_transfer',
                    'disetorkan',
                    'operator_id',
                    'keterangan_lain_lain',
                ];

                $dirty = array_intersect_key($report->getDirty(), array_flip($operatorInputColumns));

                if (!empty($dirty) && class_exists(\App\Models\AuditLog::class) && \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
                    $original = array_intersect_key($report->getOriginal(), $dirty);
                    \App\Models\AuditLog::log(
                        action: 'UPDATE_OPERATOR_INPUT',
                        modelType: 'DailyReport',
                        modelId: $report->id,
                        description: "Perubahan input operator laporan harian tanggal " . ($report->report_date ? $report->report_date->toDateString() : $report->created_at),
                        oldValues: $original,
                        newValues: $dirty
                    );
                }
            }
        });
    }

    protected $appends = [
        'totalisator_awal',
        'stik_awal',
        'stok_awal',
        'stok_akhir_aktual',
        'test_pump',
        'penerimaan',
        'volume_penjualan',
        'stok_akhir_teoritis',
        'losses_gain',
        'losses_gain_rupiah',
        'losses_gain_kategori',
        'pengeluaran',
        'rupiah_penjualan',
        'pendapatan',
        'pendapatan_akhir',
        'disetorkan',
        'selisih_setoran',
        'belum_disetorkan',
        'volume_penjualan_teoritis',
        'rupiah_penjualan_teoritis',
        'volume_penjualan_aktual',
        'rupiah_penjualan_aktual',
        'omset_plus_losses'
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function incomings()
    {
        return $this->hasMany(Incoming::class);
    }

    public function soOrderTrips()
    {
        return $this->hasMany(SoOrderTrip::class);
    }

    public function testPumps()
    {
        return $this->hasMany(TestPump::class);
    }

    public function spendings()
    {
        return $this->hasMany(Spending::class);
    }

    public function price()
    {
        return $this->belongsTo(Price::class);
    }

    public function excelUpload()
    {
        return $this->belongsTo(ExcelUpload::class, 'excel_upload_id');
    }

    public function backdateFile()
    {
        return $this->belongsTo(BackdateExcelFile::class, 'backdate_file_id');
    }

    public function periods()
    {
        return $this->hasMany(DailyReportPeriod::class);
    }

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }

    public function receivablePayments()
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    protected static $allReportsByShop = [];
    protected static $allReportsByOperator = [];

    public static function clearInternalCache(): void
    {
        self::$allReportsByShop = [];
        self::$allReportsByOperator = [];
    }

    public function latestByShop()
    {
        if (!isset(self::$allReportsByShop[$this->shop_id])) {
            self::$allReportsByShop[$this->shop_id] = self::where('shop_id', $this->shop_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $currentTimestamp = strtotime($this->created_at);
        return self::$allReportsByShop[$this->shop_id]
            ->first(function ($report) use ($currentTimestamp) {
                return strtotime($report->created_at) < $currentTimestamp;
            });
    }

    public function latestByOperator()
    {
        if (!isset(self::$allReportsByOperator[$this->operator_id])) {
            self::$allReportsByOperator[$this->operator_id] = self::where('operator_id', $this->operator_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $currentTimestamp = strtotime($this->created_at);
        return self::$allReportsByOperator[$this->operator_id]
            ->first(function ($report) use ($currentTimestamp) {
                return strtotime($report->created_at) < $currentTimestamp;
            });
    }

    public function getTotalisatorAwalAttribute()
    {
        if (isset($this->attributes['totalisator_awal']) && $this->attributes['totalisator_awal'] !== null) {
            return floatval($this->attributes['totalisator_awal']);
        }
        return $this->latestByShop() ?  $this->latestByShop()->totalisator_akhir : ($this->shop ? $this->shop->totalisator_awal : 0);
    }

    public function getStikAwalAttribute()
    {
        if (isset($this->attributes['stik_awal']) && $this->attributes['stik_awal'] !== null) {
            return floatval($this->attributes['stik_awal']);
        }
        return  $this->latestByShop() ?  $this->latestByShop()->stik_akhir : ($this->shop ? $this->shop->stik_awal : 0);
    }

    public function getStokAwalAttribute()
    {
        if (isset($this->attributes['stok_awal']) && $this->attributes['stok_awal'] !== null && floatval($this->attributes['stok_awal']) > 0) {
            return round(floatval($this->attributes['stok_awal']), 3);
        }
        $prev = $this->latestByShop();
        if ($prev) {
            return round(floatval($prev->stok_akhir_aktual), 3);
        }
        $stikAwal = floatval($this->stik_awal);
        if ($stikAwal > 200) {
            return round($stikAwal, 3);
        }
        $skala = ($this->shop && floatval($this->shop->skala) > 0) ? floatval($this->shop->skala) : 21.49;
        return round($stikAwal * $skala, 3);
    }

    public function getStokAkhirAktualAttribute()
    {
        $stik = floatval($this->attributes['stik_akhir'] ?? 0);
        if ($stik > 200) {
            return round($stik, 3);
        }
        $skala = ($this->shop && floatval($this->shop->skala) > 0) ? floatval($this->shop->skala) : 21.49;
        return round($stik * $skala, 3);
    }

    public function getTestPumpAttribute()
    {
        if (isset($this->attributes['test_pump_volume']) && floatval($this->attributes['test_pump_volume']) > 0) {
            return floatval($this->attributes['test_pump_volume']);
        }
        return $this->testPumps->sum('volume');
    }

    public function getPenerimaanAttribute()
    {
        if (isset($this->attributes['penerimaan_volume']) && floatval($this->attributes['penerimaan_volume']) > 0) {
            return floatval($this->attributes['penerimaan_volume']);
        }
        return $this->incomings->sum('volume');
    }

    public function getVolumePenjualanTeoritisAttribute()
    {
        $ta = floatval($this->totalisator_akhir);
        if ($ta <= 0) {
            return 0;
        }
        $awal = floatval($this->totalisator_awal);
        
        if ($this->periods()->exists()) {
            $totalVolume = 0;
            foreach ($this->periods as $period) {
                $totalVolume += (floatval($period->totalisator_akhir) - floatval($period->totalisator_awal));
            }
            return $totalVolume;
        }
        return max(0.0, $ta - $awal);
    }

    public function getRupiahPenjualanTeoritisAttribute()
    {
        if ($this->periods()->exists()) {
            $totalRupiah = 0;
            foreach ($this->periods as $period) {
                $vol = floatval($period->totalisator_akhir) - floatval($period->totalisator_awal);
                $totalRupiah += ($vol * ($period->price ? floatval($period->price->harga_jual) : 0));
            }
            return round($totalRupiah, 0);
        }
        $priceJual = (isset($this->attributes['harga_jual_snapshot']) && floatval($this->attributes['harga_jual_snapshot']) > 0)
            ? floatval($this->attributes['harga_jual_snapshot'])
            : ($this->price ? floatval($this->price->harga_jual) : 0);

        if ($priceJual <= 0) {
            $evalDate = $this->report_date ? $this->report_date->toDateString() : Carbon::parse($this->created_at)->toDateString();
            $fallbackPrice = Price::where(function($q) {
                $q->where('shop_id', $this->shop_id)->orWhereNull('shop_id');
            })->where('effective_at', '<=', $evalDate . ' 23:59:59')
              ->latest('effective_at')
              ->first();
            $priceJual = $fallbackPrice ? floatval($fallbackPrice->harga_jual) : 0;
        }

        return round($this->volume_penjualan_teoritis * $priceJual, 0);
    }

    public function getVolumePenjualanAktualAttribute()
    {
        // Volume Aktual = Volume_Teoritis - Test_Pump - BBM_Lain2
        // BBM_Lain2 = BBM keluar tapi bukan penjualan (jarang, default 0)
        $bbmLain2 = floatval($this->attributes['bbm_keluar_lain'] ?? 0);
        return round(max(0.0, $this->volume_penjualan_teoritis - $this->test_pump - $bbmLain2), 3);
    }

    public function getRupiahPenjualanAktualAttribute()
    {
        if ($this->periods()->exists()) {
            $totalRupiah = 0;
            $lastPriceJual = 0;
            foreach ($this->periods as $period) {
                $vol = floatval($period->totalisator_akhir) - floatval($period->totalisator_awal);
                $totalRupiah += ($vol * ($period->price ? floatval($period->price->harga_jual) : 0));
                $lastPriceJual = $period->price ? floatval($period->price->harga_jual) : 0;
            }
            return round(max(0.0, $totalRupiah - ($this->test_pump * $lastPriceJual)), 0);
        }
        // Prioritaskan snapshot harga untuk integritas data historis
        $priceJual = (isset($this->attributes['harga_jual_snapshot']) && floatval($this->attributes['harga_jual_snapshot']) > 0)
            ? floatval($this->attributes['harga_jual_snapshot'])
            : ($this->price ? floatval($this->price->harga_jual) : 0);

        // Fallback sekunder jika snapshot dan price_id kosong: ambil harga aktif outlet berdasarkan effective_at
        if ($priceJual <= 0) {
            $evalDate = $this->report_date ? $this->report_date->toDateString() : Carbon::parse($this->created_at)->toDateString();
            $fallbackPrice = Price::where(function($q) {
                $q->where('shop_id', $this->shop_id)->orWhereNull('shop_id');
            })->where('effective_at', '<=', $evalDate . ' 23:59:59')
              ->latest('effective_at')
              ->first();
            $priceJual = $fallbackPrice ? floatval($fallbackPrice->harga_jual) : 0;
        }

        return round($this->volume_penjualan_aktual * $priceJual, 0);
    }

    public function getVolumePenjualanAttribute()
    {
        return $this->volume_penjualan_aktual;
    }

    public function getRupiahPenjualanAttribute()
    {
        return $this->rupiah_penjualan_aktual;
    }

    public function getStokAkhirTeoritisAttribute()
    {
        // Stok_Teoritis = Stok_Awal + Penerimaan_BBM - BBM_Keluar_Lain - Volume_Penjualan_Aktual
        // Rumus Excel: Q = N + P - J - L (Keluar tangki riil = L + J = F - H)
        $bbmKeluarLain = floatval($this->bbm_keluar_lain ?? 0);
        return round($this->stok_awal + $this->penerimaan - $bbmKeluarLain - $this->volume_penjualan_aktual, 3);
    }

    public function getOmsetPlusLossesAttribute()
    {
        $lg = $this->losses_gain;
        $perlakuan = 'losses_only';
        if ($this->shop && $this->shop->payrollSystems()->exists()) {
            $ps = $this->shop->payrollSystems()->first();
            $date = $this->report_date ? $this->report_date->toDateString() : Carbon::parse($this->created_at)->toDateString();
            $perlakuan = $ps->getEffectivePerlakuanLossesGain($date);
        }

        return match ($perlakuan) {
            'losses_only' => $lg < 0 ? max(0.0, round($this->volume_penjualan_aktual + $lg, 3)) : round($this->volume_penjualan_aktual, 3),
            'losses_dan_gain' => max(0.0, round($this->volume_penjualan_aktual + $lg, 3)),
            'abaikan_losses_gain' => round($this->volume_penjualan_aktual, 3),
            default => $lg < 0 ? max(0.0, round($this->volume_penjualan_aktual + $lg, 3)) : round($this->volume_penjualan_aktual, 3),
        };
    }

    public function getPenjualanLossesDAttribute()
    {
        return $this->omset_plus_losses;
    }

    public function getLossesGainAttribute()
    {
        return round($this->stok_akhir_aktual - $this->stok_akhir_teoritis, 3);
    }

    /**
     * Kategori Losses/Gain dengan 3 nilai: "Losses", "Gain", "Pas".
     * - "Pas"    : losses_gain == 0 (tepat, tidak ada selisih stok)
     * - "Losses" : losses_gain < 0  (stok aktual < teoritis — dikurangi ke gaji jika perlakuan_losses_gain = losses_only)
     * - "Gain"   : losses_gain > 0  (stok aktual > teoritis — tidak menambah gaji pada perlakuan_losses_gain = losses_only)
     */
    public function getLossesGainKategoriAttribute(): string
    {
        $lg = $this->losses_gain;
        if ($lg == 0) return 'Pas';
        return $lg < 0 ? 'Losses' : 'Gain';
    }

    public function getLossesGainRupiahAttribute()
    {
        $priceBeli = 0;
        // Prioritaskan snapshot harga agar data historis tidak berubah jika harga diedit retroaktif
        if (isset($this->attributes['harga_beli_snapshot']) && floatval($this->attributes['harga_beli_snapshot']) > 0) {
            $priceBeli = floatval($this->attributes['harga_beli_snapshot']);
        } elseif ($this->periods()->exists() && $this->periods->last() && $this->periods->last()->price) {
            $priceBeli = floatval($this->periods->last()->price->harga_beli);
        } elseif ($this->price && floatval($this->price->harga_beli) > 0) {
            $priceBeli = floatval($this->price->harga_beli);
        } else {
            $evalDate = $this->report_date ? $this->report_date->toDateString() : Carbon::parse($this->created_at)->toDateString();
            $fallbackPrice = Price::where(function($q) {
                $q->where('shop_id', $this->shop_id)->orWhereNull('shop_id');
            })->where('effective_at', '<=', $evalDate . ' 23:59:59')
              ->latest('effective_at')
              ->first();
            $priceBeli = $fallbackPrice ? floatval($fallbackPrice->harga_beli) : 0;
        }
        return round($this->losses_gain * $priceBeli, 0);
    }

    public function getPengeluaranAttribute()
    {
        return round($this->spendings->sum('jumlah'), 0);
    }

    public function getPendapatanAttribute()
    {
        return round($this->rupiah_penjualan_aktual - $this->pengeluaran, 0);
    }

    /**
     * Pendapatan Akhir Operasional Kas Harian (Spec Section 3.2):
     * Pendapatan_Akhir = Pendapatan_Bersih_Ops - Hutang_Bakulan_Baru + Pelunasan_Bakulan
     * Merefleksikan kas riil yang wajib disetorkan operator hari ini.
     */
    public function getPendapatanAkhirAttribute()
    {
        $hutangBaru = floatval($this->attributes['hutang_bakulan'] ?? 0);
        $pelunasan  = floatval($this->attributes['pelunasan_bakulan'] ?? 0);
        return round($this->pendapatan - $hutangBaru + $pelunasan, 0);
    }

    public function kolektan()
    {
        return $this->belongsTo(Kolektan::class);
    }

    public function deposits()
    {
        return $this->hasMany(DailyReportDeposit::class);
    }

    public function getDisetorkanAttribute()
    {
        $hasDeposits = $this->relationLoaded('deposits') ? $this->deposits->isNotEmpty() : $this->deposits()->exists();
        if ($hasDeposits) {
            $deposits = $this->relationLoaded('deposits') ? $this->deposits : $this->deposits()->with('category')->get();
            return round(floatval($deposits->filter(fn($d) => $d->category && $d->category->termasuk_dalam_setoran)->sum('jumlah')), 0);
        }
        $sum = floatval($this->setor_tunai ?? 0) + floatval($this->setor_qris ?? 0) + floatval($this->setor_transfer ?? 0) + floatval($this->setor_kolektan ?? 0);
        if ($sum > 0) {
            return round($sum, 0);
        }
        return round(floatval($this->attributes['disetorkan'] ?? 0), 0);
    }

    public function getSelisihSetoranAttribute()
    {
        // Selisih_Setoran = Total_Setoran - Pendapatan_Akhir (Kas yang wajib disetor)
        // Positif = lebih setor, Negatif = kurang setor.
        return round($this->disetorkan - $this->pendapatan_akhir, 0);
    }

    public function getBelumDisetorkanAttribute()
    {
        if (isset($this->attributes['running_balance_setoran']) && $this->attributes['running_balance_setoran'] !== null) {
            return round(floatval($this->attributes['running_balance_setoran']), 0);
        }
        $kemarin = $this->latestByShop() ? floatval($this->latestByShop()->belum_disetorkan) : ($this->shop ? floatval($this->shop->uang_belum_disetor_awal ?? 0) : 0);
        return round($kemarin + $this->selisih_setoran, 0);
    }
}
```



# =============================================================================
# 4. SERVICE REKALKULASI BERANTAI: DailyReportCalculationService
# File: app/Services/DailyReportCalculationService.php
# Rekalkulasi berantai (kaskade) saat data diedit retroaktif
# =============================================================================

```php
<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\Price;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyReportCalculationService
{
    /**
     * Rekalkulasi berantai (cascade recalculation) untuk satu gerai
     * mulai dari tanggal tertentu hingga akhir bulan/periode.
     *
     * Berantai:
     * - totalisator_awal = totalisator_akhir hari sebelumnya
     * - stok_awal        = stok_akhir_aktual hari sebelumnya
     * - running_balance  = running_balance hari sebelumnya + selisih_setoran
     */
    public static function recalculateCascade(
        int $shopId,
        ?Carbon $fromDate = null,
        ?float $initialStartingBalance = null,
        ?float $initialStartingTotalisator = null,
        ?float $initialStartingStok = null
    ): void {
        DailyReport::clearInternalCache();

        $query = DailyReport::where('shop_id', $shopId)->orderBy('created_at', 'asc');
        $allReports = $query->get();

        if ($allReports->isEmpty()) {
            return;
        }

        $shop = Shop::find($shopId);

        if ($initialStartingBalance === null && $shop && $allReports->isNotEmpty()) {
            $firstReport = $allReports->first();
            $firstDate = Carbon::parse($firstReport->report_date ?? $firstReport->created_at);
            $saldoAwal = PertashopDailyRecapService::resolveSaldoAwal($shop, $firstDate->copy()->startOfMonth());
            $runningBalance = $saldoAwal['uang_belum_disetor_periode_lalu'];
            $prevTotalisatorAkhir = $initialStartingTotalisator ?? $saldoAwal['totalisator_awal_hari_pertama'];
            $prevStokAkhir = $initialStartingStok ?? $saldoAwal['stok_awal_hari_pertama'];
        } else {
            $runningBalance = $initialStartingBalance ?? floatval($shop?->uang_belum_disetor_awal ?? 0);
            $prevTotalisatorAkhir = $initialStartingTotalisator ?? floatval($shop?->totalisator_awal ?? 0);
            $prevStokAkhir = $initialStartingStok ?? floatval($shop?->stok_awal ?? ($shop ? $shop->stik_awal * ($shop->skala ?: 21.49) : 0));
        }

        $isFirst = true;

        foreach ($allReports as $report) {
            $reportDate = $report->report_date ? $report->report_date->toDateString() : Carbon::parse($report->created_at)->toDateString();

            // 1. Totalisator Awal & Stok Awal
            if ($isFirst) {
                $totalisatorAwal = (floatval($report->totalisator_awal) > 0) ? floatval($report->totalisator_awal) : $prevTotalisatorAkhir;
                $stokAwal = (floatval($report->stok_awal) > 0) ? floatval($report->stok_awal) : $prevStokAkhir;
                $isFirst = false;
            } else {
                $totalisatorAwal = $prevTotalisatorAkhir;
                $stokAwal = $prevStokAkhir;
            }

            // 2. Snapshot Harga (jika belum ada atau 0)
            $hargaJual = floatval($report->harga_jual_snapshot ?? 0);
            $hargaBeli = floatval($report->harga_beli_snapshot ?? 0);

            if ($hargaJual <= 0 || $hargaBeli <= 0) {
                $price = Price::where('shop_id', $shopId)
                    ->where('effective_at', '<=', Carbon::parse($reportDate)->endOfDay())
                    ->orderBy('effective_at', 'desc')
                    ->first();

                if (!$price) {
                    $price = Price::whereNull('shop_id')
                        ->where('effective_at', '<=', Carbon::parse($reportDate)->endOfDay())
                        ->orderBy('effective_at', 'desc')
                        ->first();
                }

                if ($price) {
                    $hargaJual = $hargaJual > 0 ? $hargaJual : floatval($price->harga_jual);
                    $hargaBeli = $hargaBeli > 0 ? $hargaBeli : floatval($price->harga_beli);
                }
            }

            // Temporarily set attributes on the model to calculate selisih_setoran
            $report->totalisator_awal = $totalisatorAwal;
            $report->stok_awal = $stokAwal;
            $report->harga_jual_snapshot = $hargaJual;
            $report->harga_beli_snapshot = $hargaBeli;

            // 3. Selisih Setoran & Running Balance
            // selisih_setoran = disetorkan - pendapatan_akhir
            // running_balance = running_balance_sebelumnya + selisih_setoran
            $selisihSetoran = $report->selisih_setoran;
            $runningBalance = round($runningBalance + $selisihSetoran, 0);

            // 4. Update data ke database
            DB::table('daily_reports')
                ->where('id', $report->id)
                ->update([
                    'totalisator_awal'       => $totalisatorAwal,
                    'stok_awal'              => $stokAwal,
                    'harga_jual_snapshot'    => $hargaJual,
                    'harga_beli_snapshot'    => $hargaBeli,
                    'running_balance_setoran' => $runningBalance,
                    'updated_at'             => now(),
                ]);

            // Untuk hari berikutnya
            $prevTotalisatorAkhir = floatval($report->totalisator_akhir);
            $prevStokAkhir = floatval($report->stok_akhir_aktual);
        }

        DailyReport::clearInternalCache();
    }
}

```



# =============================================================================
# 5. MODEL SISTEM PENGGAJIAN: PayrollSystem
# File: app/Models/PayrollSystem.php
# Sistem penggajian, aturan perlakuan losses/gain, split shift
# =============================================================================

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollSystem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tipe_skema'              => 'string',
        'ada_rate_per_liter'      => 'boolean',
        'ada_gaji_pokok'          => 'boolean',
        'aktif'                   => 'boolean',
        'rate_per_liter'          => 'decimal:2',
        'nominal_gaji_pokok'      => 'decimal:2',
        'potongan_per_hari_alpha'  => 'decimal:2',
        'rate_transport_per_hari' => 'decimal:2',
        'metode_potongan_alpha'   => 'string',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            // Sinkronisasi boolean flag berdasarkan tipe_skema
            if ($model->tipe_skema === 'komisi_murni') {
                $model->ada_rate_per_liter = true;
                $model->ada_gaji_pokok     = false;
                $model->nominal_gaji_pokok = null;
            } elseif ($model->tipe_skema === 'gaji_pokok_murni') {
                $model->ada_rate_per_liter = false;
                $model->rate_per_liter     = 0;
                $model->ada_gaji_pokok     = true;
            } elseif ($model->tipe_skema === 'hibrid') {
                $model->ada_rate_per_liter = true;
                $model->ada_gaji_pokok     = true;
            } else {
                if ($model->ada_gaji_pokok && $model->ada_rate_per_liter) {
                    $model->tipe_skema = 'hibrid';
                } elseif ($model->ada_gaji_pokok) {
                    $model->tipe_skema = 'gaji_pokok_murni';
                } else {
                    $model->tipe_skema = 'komisi_murni';
                }
            }
        });
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function assignments()
    {
        return $this->hasMany(PayrollOperatorAssignment::class);
    }

    public function periods()
    {
        return $this->hasMany(PayrollPeriod::class);
    }

    /**
     * Dapatkan perlakuan losses/gain aktif untuk gerai ini pada tanggal tertentu,
     * dengan hierarki prioritas:
     * 1. Override spesifik gerai di BusinessRule registry (value_json[shop_id])
     * 2. Konfigurasi per gerai di tabel payroll_systems (perlakuan_losses_gain)
     * 3. Fallback default BusinessRule terpusat (value_string / value_json['default'])
     */
    public function getEffectivePerlakuanLossesGain(?string $date = null): string
    {
        $ruleValue = null;
        try {
            $registry = app(\App\Services\Registry\BusinessRuleRegistryService::class);
            $ruleValue = $registry->getValue('PERLAKUAN_LOSSES_GAIN', $date);
            if (is_array($ruleValue)) {
                $shopKey = (string) $this->shop_id;
                if (isset($ruleValue[$shopKey])) {
                    return $ruleValue[$shopKey];
                }
            }
        } catch (\Throwable $e) {
            // Fallback ke kolom payroll_system
        }

        // Pengaturan per gerai di payroll_system
        if (!empty($this->perlakuan_losses_gain)) {
            return $this->perlakuan_losses_gain;
        }

        // Fallback default business rule
        if (is_string($ruleValue) && !empty($ruleValue)) {
            return $ruleValue;
        }
        if (is_array($ruleValue) && isset($ruleValue['default'])) {
            return $ruleValue['default'];
        }

        return 'losses_only';
    }

    /**
     * Hitung volume_dihitung berdasarkan konfigurasi perlakuan_losses_gain.
     *
     * Sesuai spesifikasi:
     * - "Losses" (losses_gain < 0): Volume_Dihitung = Volume_Aktual + losses_gain (losses_gain negatif, jadi mengurangi)
     * - "Gain"   (losses_gain > 0): Volume_Dihitung = Volume_Aktual (gain diabaikan pada losses_only)
     * - "Pas"    (losses_gain = 0): Volume_Dihitung = Volume_Aktual (Pas diperlakukan sama dengan Gain — tidak mengurangi)
     *
     * Catatan: Kondisi `$lossesGain < 0` sudah secara otomatis mengeksklusi kasus "Pas" (== 0)
     * sehingga tidak ada perubahan logika yang diperlukan — hanya dokumentasi diperjelas.
     *
     * @param float $volumePenjualanAktual
     * @param float $lossesGain  (negatif = losses/Losses, nol = Pas, positif = Gain)
     * @param string|null $date
     * @return float
     */
    public function hitungVolumeDihitung(float $volumePenjualanAktual, float $lossesGain, ?string $date = null): float
    {
        $perlakuan = $this->getEffectivePerlakuanLossesGain($date);

        return match ($perlakuan) {
            // Losses dikurangi, Gain & Pas diabaikan
            // lossesGain < 0  → Losses: kurangi
            // lossesGain == 0 → Pas:    tidak mengurangi (masuk cabang else)
            // lossesGain > 0  → Gain:   tidak menambah (masuk cabang else)
            'losses_only' => $lossesGain < 0
                ? $volumePenjualanAktual + $lossesGain
                : $volumePenjualanAktual,
            // Keduanya berlaku (plus/minus) — Pas tetap 0 efeknya
            'losses_dan_gain' => $volumePenjualanAktual + $lossesGain,
            // Losses/gain tidak dihitung ke gaji sama sekali
            'abaikan_losses_gain' => $volumePenjualanAktual,
            default => $lossesGain < 0
                ? $volumePenjualanAktual + $lossesGain
                : $volumePenjualanAktual,
        };
    }

    public function getPerlakuanLossesGainLabelAttribute(): string
    {
        return match ($this->perlakuan_losses_gain) {
            'losses_only'          => 'Losses Saja (Gain Diabaikan)',
            'losses_dan_gain'      => 'Losses & Gain (Plus/Minus)',
            'abaikan_losses_gain'  => 'Abaikan Losses/Gain',
            default                => $this->perlakuan_losses_gain,
        };
    }

    public function getMetodeSplitLabelAttribute(): string
    {
        return match ($this->metode_split) {
            'per_hari_penuh'            => 'Satu Hari Penuh',
            'proporsional_jam_kerja'    => 'Proporsional per Shift',
            'flat_bulanan_prorata_hari' => 'Flat Bulanan Prorata Hari Kerja',
            default                     => $this->metode_split,
        };
    }

    public function isKomisiMurni(): bool
    {
        return ($this->tipe_skema ?? 'komisi_murni') === 'komisi_murni';
    }

    public function isGajiPokokMurni(): bool
    {
        return ($this->tipe_skema ?? '') === 'gaji_pokok_murni';
    }

    public function isHibrid(): bool
    {
        return ($this->tipe_skema ?? '') === 'hibrid';
    }

    public function getTipeSkemaLabelAttribute(): string
    {
        return match ($this->tipe_skema) {
            'gaji_pokok_murni' => 'Sistem Gaji Pokok Murni',
            'hibrid'           => 'Sistem Hibrid (Gaji Pokok + Komisi Liter)',
            default            => 'Sistem Komisi Murni (Total Liter × Tarif/L)',
        };
    }

    public function getTipeSkemaBadgeColorAttribute(): string
    {
        return match ($this->tipe_skema) {
            'gaji_pokok_murni' => '#8b5cf6', // purple
            'hibrid'           => '#059669', // emerald
            default            => '#0284c7', // blue
        };
    }

    public function getFormulaDescriptionAttribute(): string
    {
        return match ($this->tipe_skema) {
            'gaji_pokok_murni' => 'Gaji Pokok Rp ' . number_format($this->nominal_gaji_pokok ?? 0, 0, ',', '.'),
            'hibrid'           => 'Gaji Pokok Rp ' . number_format($this->nominal_gaji_pokok ?? 0, 0, ',', '.') . ' + (Total Liter × Rp ' . number_format($this->rate_per_liter ?? 0, 0, ',', '.') . '/L)',
            default            => 'Total Liter × Rp ' . number_format($this->rate_per_liter ?? 0, 0, ',', '.') . '/L',
        };
    }
}

```



# =============================================================================
# 6. SERVICE PENGGAJIAN: PayrollCalculationService
# File: app/Services/PayrollCalculationService.php
# Perhitungan slip gaji karyawan bulanan, cicilan pinjaman, THP
# =============================================================================

```php
<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\EmployeeLoan;
use App\Models\PayrollDailySplit;
use App\Models\PayrollDetail;
use App\Models\PayrollLoanDeduction;
use App\Models\PayrollPeriod;
use App\Models\PayrollSystem;
use App\Models\ShiftSchedule;
use App\Models\AttendanceRecap;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    /**
     * Generate atau re-generate payroll untuk 1 toko 1 bulan.
     * Jika periode sudah ada dan statusnya 'final', throw exception.
     * Jika periode sudah ada dan statusnya 'draft', hapus data lama & generate ulang.
     *
     * @return PayrollPeriod
     * @throws \Exception
     */
    public function generate(int $shopId, int $bulan, int $tahun, int $generatedBy): PayrollPeriod
    {
        // 1. Ambil sistem penggajian aktif untuk toko ini (atau buat default sesuai cabang jika belum ada)
        $payrollSystem = PayrollSystem::where('shop_id', $shopId)
            ->where('aktif', true)
            ->latest()
            ->first();

        if (!$payrollSystem) {
            $shop = Shop::findOrFail($shopId);
            $defaults = $shop->getDefaultPayrollNominals();
            $payrollSystem = PayrollSystem::create(array_merge($defaults, [
                'shop_id'     => $shopId,
                'nama_sistem' => 'Sistem ' . $shop->nama,
                'aktif'       => true,
            ]));
        }

        // 2. Cek apakah periode sudah ada
        $existingPeriod = PayrollPeriod::where('shop_id', $shopId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        if ($existingPeriod && $existingPeriod->isFinal()) {
            throw new \Exception("Periode gaji {$existingPeriod->periode_label} sudah difinalisasi dan tidak bisa di-generate ulang.");
        }

        // 3. Ambil semua daily_reports toko ini di bulan tsb
        $dailyReports = DailyReport::with(['operator', 'testPumps', 'incomings', 'spendings', 'periods'])
            ->where('shop_id', $shopId)
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->orderBy('created_at')
            ->get();

        if ($dailyReports->isEmpty()) {
            throw new \Exception("Tidak ada data laporan harian untuk toko ini di periode {$bulan}/{$tahun}.");
        }

        // 4. Ambil operator bertugas di cabang ini — otomatis dari shop_id,
        //    tidak memerlukan assignment manual per tanggal mulai/selesai.
        $operatorIds = \App\Models\Operator::where('shop_id', $shopId)->pluck('id')->unique();

        // Fallback: ambil operator yang tercatat di laporan harian bulan ini
        //           (misal operator lintas cabang yang tidak di-assign di tabel operators)
        if ($operatorIds->isEmpty()) {
            $operatorIds = $dailyReports->pluck('operator_id')->filter()->unique();
        }

        if ($operatorIds->isEmpty()) {
            throw new \Exception("Belum ada operator yang terdaftar untuk cabang ini di periode {$bulan}/{$tahun}.");
        }

        // 5. Ambil shift schedules bulan ini untuk tahu siapa bertugas kapan (kecuali yang berstatus libur)
        $shiftSchedules = ShiftSchedule::where('shop_id', $shopId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->whereIn('operator_id', $operatorIds)
            ->where('status', '!=', 'libur')
            ->get()
            ->groupBy(fn($s) => $s->tanggal->format('Y-m-d'));

        // 6. Kalkulasi per hari
        // Format: [operator_id => ['liter' => 0, 'hari' => 0]]
        $operatorTotals = [];
        foreach ($operatorIds as $opId) {
            $operatorTotals[$opId] = ['liter' => 0.0, 'hari' => 0];
        }

        $dailySplitsData = [];
        $totalPenjualanLiter = 0.0;

        foreach ($dailyReports as $report) {
            $tanggalStr = Carbon::parse($report->created_at)->format('Y-m-d');

            $volumeAktual  = floatval($report->volume_penjualan_aktual);
            $lossesGain    = floatval($report->losses_gain);
            $volDihitung   = $payrollSystem->hitungVolumeDihitung($volumeAktual, $lossesGain, $tanggalStr);
            $volDihitung   = max(0.0, $volDihitung); // tidak boleh negatif

            $totalPenjualanLiter += $volDihitung;

            // Cari operator yang bertugas hari ini di toko ini
            $shiftHariIni = $shiftSchedules->get($tanggalStr, collect());

            if ($shiftHariIni->isEmpty()) {
                // Tidak ada jadwal shift hari ini → gunakan operator dari daily_report
                $opIdHariIni = $report->operator_id;
                if (isset($operatorTotals[$opIdHariIni])) {
                    $this->tambahSplitHariPenuh($dailySplitsData, $operatorTotals, $opIdHariIni, $tanggalStr, $volumeAktual, $volDihitung);
                }
                continue;
            }

            if ($payrollSystem->metode_split === 'per_hari_penuh') {
                $this->splitPerHariPenuh($dailySplitsData, $operatorTotals, $shiftHariIni, $tanggalStr, $volumeAktual, $volDihitung);
            } else {
                $this->splitProporsionalJam($dailySplitsData, $operatorTotals, $shiftHariIni, $tanggalStr, $volumeAktual, $volDihitung);
            }
        }

        // 7. Ambil data kehadiran & savings untuk auto-suggest
        $attendanceRecaps = AttendanceRecap::where('shop_id', $shopId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereIn('operator_id', $operatorIds)
            ->get()
            ->keyBy('operator_id');

        $savingsTotals = DB::table('employee_savings')
            ->whereIn('operator_id', $operatorIds)
            ->where('jenis', 'setoran')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->groupBy('operator_id')
            ->select('operator_id', DB::raw('SUM(jumlah) as total'))
            ->get()
            ->keyBy('operator_id');

        // 7. Ambil kasbon aktif operator yang berstatus approved / active dan sisa_pokok > 0
        $activeLoansByOperator = EmployeeLoan::whereIn('operator_id', $operatorIds)
            ->whereIn('status', ['approved', 'active'])
            ->where('sisa_pokok', '>', 0)
            ->orderBy('tanggal', 'asc')
            ->get()
            ->groupBy('operator_id');

        $loanDeductionsPlan = [];
        $loanTotals = [];

        foreach ($operatorIds as $opId) {
            $loans = $activeLoansByOperator->get($opId, collect());
            $opDeductionList = [];
            $totalOpDeduction = 0.0;

            foreach ($loans as $loan) {
                $sisaPokok = floatval($loan->sisa_pokok > 0 ? $loan->sisa_pokok : $loan->jumlah);
                if ($sisaPokok <= 0) {
                    continue;
                }

                $tenor = max(1, (int) ($loan->tenor_bulan ?: 1));
                $nominalAngsuran = floatval($loan->nominal_angsuran > 0 ? $loan->nominal_angsuran : round($loan->jumlah / $tenor, 2));
                $potongan = min($nominalAngsuran, $sisaPokok);
                $cicilanKe = min($tenor, (int) $loan->angsuran_ke + 1);
                $sisaSetelah = max(0, $sisaPokok - $potongan);

                $opDeductionList[] = [
                    'loan'               => $loan,
                    'cicilan_ke'         => $cicilanKe,
                    'total_tenor'        => $tenor,
                    'nominal_potongan'   => $potongan,
                    'sisa_pokok_sebelum' => $sisaPokok,
                    'sisa_pokok_setelah' => $sisaSetelah,
                ];

                $totalOpDeduction += $potongan;
            }

            $loanDeductionsPlan[$opId] = $opDeductionList;
            $loanTotals[$opId] = $totalOpDeduction;
        }

        // 8. Wrap dalam DB transaction
        return DB::transaction(function () use (
            $existingPeriod, $shopId, $bulan, $tahun, $payrollSystem,
            $operatorTotals, $dailySplitsData, $totalPenjualanLiter,
            $attendanceRecaps, $savingsTotals, $loanTotals, $loanDeductionsPlan, $generatedBy
        ) {
            // Hapus periode lama jika ada (draft)
            if ($existingPeriod) {
                $existingPeriod->dailySplits()->delete();
                $existingPeriod->loanDeductions()->delete();
                $existingPeriod->details()->delete();
                $existingPeriod->delete();
            }

            // Buat payroll_period baru
            $period = PayrollPeriod::create([
                'shop_id'               => $shopId,
                'payroll_system_id'     => $payrollSystem->id,
                'bulan'                 => $bulan,
                'tahun'                 => $tahun,
                'status'                => 'draft',
                'total_penjualan_liter' => $totalPenjualanLiter,
                'generated_at'          => now(),
                'generated_by'          => $generatedBy,
            ]);

            // Simpan daily splits
            foreach ($dailySplitsData as $splitRow) {
                PayrollDailySplit::create(array_merge($splitRow, [
                    'payroll_period_id' => $period->id,
                ]));
            }

            // Auto-pull kurang setoran per operator dari daily_reports di bulan & tahun berjalan
            $dailyReportsInPeriod = DailyReport::where('shop_id', $shopId)
                ->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->get();

            $kurangSetoranPerOperator = [];
            foreach ($dailyReportsInPeriod as $dr) {
                if ($dr->operator_id && $dr->selisih_setoran < 0) {
                    $opId = $dr->operator_id;
                    $kurangSetoranPerOperator[$opId] = ($kurangSetoranPerOperator[$opId] ?? 0) + abs($dr->selisih_setoran);
                }
            }

            // Buat payroll_detail per operator
            foreach ($operatorTotals as $opId => $totals) {
                $literBagian = round($totals['liter'], 3);
                $tipeSkema   = $payrollSystem->tipe_skema ?? (
                    ($payrollSystem->ada_gaji_pokok && $payrollSystem->ada_rate_per_liter) ? 'hibrid' :
                    ($payrollSystem->ada_gaji_pokok ? 'gaji_pokok_murni' : 'komisi_murni')
                );

                // 1. Komisi per Liter (Gaji Variable)
                // Berlaku untuk: 'komisi_murni' dan 'hibrid'
                if (in_array($tipeSkema, ['komisi_murni', 'hibrid'])) {
                    $ratePerLiter = ($payrollSystem->ada_rate_per_liter ?? true) ? floatval($payrollSystem->rate_per_liter) : 0.0;
                    $gajiVariable = round($literBagian * $ratePerLiter, 0);
                } else {
                    // Skema 'gaji_pokok_murni': Tanpa komisi liter
                    $ratePerLiter = 0.0;
                    $gajiVariable = 0.0;
                }

                // 2. Gaji Pokok
                // Berlaku untuk: 'gaji_pokok_murni' dan 'hibrid'
                if (in_array($tipeSkema, ['gaji_pokok_murni', 'hibrid']) && ($payrollSystem->ada_gaji_pokok ?? true)) {
                    $nominalGajiPokok = floatval($payrollSystem->nominal_gaji_pokok ?? 0);
                    if ($payrollSystem->metode_split === 'flat_bulanan_prorata_hari') {
                        $standarHariKerja = $payrollSystem->standar_hari_kerja ?: 26;
                        $rateHarian       = $standarHariKerja > 0 ? ($nominalGajiPokok / $standarHariKerja) : 0.0;
                        $gajiPokok        = round($rateHarian * $totals['hari'], 0);
                    } else {
                        $gajiPokok        = round($nominalGajiPokok, 0);
                    }
                } else {
                    // Skema 'komisi_murni': Tanpa gaji pokok
                    $gajiPokok = 0.0;
                }

                // === Uang Transport ===
                // Dihitung per hari kerja, TIDAK masuk basis prorata potongan alpha.
                $rateTransport = floatval($payrollSystem->rate_transport_per_hari ?? 0);
                $uangTransport = round($totals['hari'] * $rateTransport, 0);

                // === Potongan Alpha (Dinamis atau Nominal Tetap) ===
                $recap = $attendanceRecaps->get($opId);
                $jumlahAlpha = $recap ? intval($recap->total_alpha) : 0;

                $metodePotonganAlpha = $payrollSystem->metode_potongan_alpha ?? 'nominal_tetap';
                if ($metodePotonganAlpha === 'prorata_gaji_pokok') {
                    // Potongan Alpha Dinamis:
                    // Rate_Harian = (Gaji_Pokok + Gaji_Variable) / Standar_Hari_Kerja
                    // Potongan_Alpha = Jumlah_Hari_Alpha × Rate_Harian
                    // Catatan: uang_transport TIDAK ikut ke basis prorata (komponen terpisah)
                    $standarHariKerja  = $payrollSystem->standar_hari_kerja ?: 26;
                    $basisGaji         = $gajiPokok + $gajiVariable; // Tanpa transport
                    $rateHarianDinamis = $standarHariKerja > 0 ? ($basisGaji / $standarHariKerja) : 0.0;
                    $potonganAlpha     = round($jumlahAlpha * $rateHarianDinamis, 0);
                } else {
                    // Potongan Alpha Nominal Tetap (default, backward-compatible)
                    $potonganAlpha = round($jumlahAlpha * floatval($payrollSystem->potongan_per_hari_alpha), 0);
                }

                // Auto-pull kurang setoran
                $kurangSetoran = round($kurangSetoranPerOperator[$opId] ?? 0.0, 0);

                // Auto-pull tabungan setoran
                $tabunganSetoran = $savingsTotals->get($opId) ? round(floatval($savingsTotals->get($opId)->total), 0) : 0.0;

                // Auto-pull potongan hutang / kasbon yang sudah APPROVED / ACTIVE
                $potonganHutang  = round(floatval($loanTotals[$opId] ?? 0.0), 0);

                // THP = (Gaji_Pokok + Gaji_Variable + Uang_Transport) - Total_Potongan
                // Uang transport masuk Total_Tambahan, TIDAK masuk basis prorata alpha
                $gajiKotor     = $gajiPokok + $gajiVariable + $uangTransport;
                $totalPotongan = $potonganAlpha + $kurangSetoran + $tabunganSetoran + $potonganHutang;
                $thp           = round($gajiKotor - $totalPotongan, 0);
                $sisaKurangBayar = $thp < 0 ? abs($thp) : 0.0;

                $detail = PayrollDetail::create([
                    'payroll_period_id'     => $period->id,
                    'operator_id'           => $opId,
                    'total_hari_kerja'      => $totals['hari'],
                    'liter_bagian'          => $literBagian,
                    'gaji_variable'         => $gajiVariable,
                    'gaji_pokok'            => $gajiPokok,
                    'lembur'                => 0,
                    'lembur_hari_raya'      => 0,
                    'bonus'                 => 0,
                    'thr'                   => 0,
                    'uang_transport'        => $uangTransport,
                    'potongan_tidak_masuk'  => $potonganAlpha,
                    'kurang_setoran'        => $kurangSetoran,
                    'tabungan_gaji'         => 0,
                    'tabungan_setoran'      => $tabunganSetoran,
                    'potongan_hutang'       => $potonganHutang,
                    'take_home_pay'         => $thp,
                    'sisa_kurang_bayar'     => $sisaKurangBayar,
                ]);

                // Simpan snapshot pemotongan cicilan kasbon (draft)
                if (!empty($loanDeductionsPlan[$opId])) {
                    foreach ($loanDeductionsPlan[$opId] as $plan) {
                        PayrollLoanDeduction::create([
                            'payroll_period_id'  => $period->id,
                            'payroll_detail_id'  => $detail->id,
                            'employee_loan_id'   => $plan['loan']->id,
                            'cicilan_ke'         => $plan['cicilan_ke'],
                            'total_tenor'        => $plan['total_tenor'],
                            'nominal_potongan'   => $plan['nominal_potongan'],
                            'sisa_pokok_sebelum' => $plan['sisa_pokok_sebelum'],
                            'sisa_pokok_setelah' => $plan['sisa_pokok_setelah'],
                            'is_finalized'       => false,
                        ]);
                    }
                }
            }

            return $period->load(['details.operator.user', 'payrollSystem', 'shop']);
        });
    }

    /**
     * Kunci dan realisasikan pemotongan cicilan kasbon saat Payroll di-finalisasi.
     * Bersifat idempotent: hanya memproses record yang belum di-finalisasi.
     */
    public function finalizePayrollLoans(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            $deductions = PayrollLoanDeduction::where('payroll_period_id', $period->id)
                ->where('is_finalized', false)
                ->with('employeeLoan')
                ->get();

            foreach ($deductions as $deduction) {
                $loan = $deduction->employeeLoan;
                if ($loan) {
                    $newSisaPokok = max(0, floatval($loan->sisa_pokok) - floatval($deduction->nominal_potongan));
                    $newAngsuranKe = (int) $deduction->cicilan_ke;
                    $newStatus = $newSisaPokok <= 0 ? 'lunas' : 'active';

                    $loan->update([
                        'angsuran_ke' => $newAngsuranKe,
                        'sisa_pokok'  => $newSisaPokok,
                        'status'      => $newStatus,
                    ]);
                }

                $deduction->update(['is_finalized' => true]);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Metode split "per_hari_penuh": tiap shift di hari itu dapat 100% dari
     * porsi shift tersebut. Jika ada 2 shift berbeda di 1 hari (shift 1 & shift 2),
     * masing-masing dapat 50% (dibagi rata per slot shift aktif).
     */
    private function splitPerHariPenuh(
        array &$dailySplitsData,
        array &$operatorTotals,
        Collection $shifts,
        string $tanggal,
        float $volumeAktual,
        float $volDihitung
    ): void {
        // Kelompokkan per operator (1 operator bisa punya >1 shift slot di 1 hari)
        $operatorShifts = $shifts->groupBy('operator_id');
        $totalOperators = $operatorShifts->filter(fn($s, $opId) => isset($operatorTotals[$opId]))->count();

        if ($totalOperators === 0) return;

        $proporsiPerOperator = 1.0 / $totalOperators;

        foreach ($operatorShifts as $opId => $opShifts) {
            if (!isset($operatorTotals[$opId])) continue;

            $literBagian = $volDihitung * $proporsiPerOperator;

            $dailySplitsData[] = [
                'operator_id'             => $opId,
                'tanggal'                 => $tanggal,
                'volume_penjualan_aktual' => $volumeAktual * $proporsiPerOperator,
                'volume_dihitung'         => $volDihitung * $proporsiPerOperator,
                'liter_bagian'            => $literBagian,
                'proporsi'               => $proporsiPerOperator,
                'sumber'                 => 'otomatis',
                'keterangan'             => 'Shift ke-' . $opShifts->pluck('shift_ke')->join(','),
            ];

            $operatorTotals[$opId]['liter'] += $literBagian;
            $operatorTotals[$opId]['hari']++;  // 1 hari kerja per operator per hari
        }
    }

    /**
     * Metode split "proporsional_jam_kerja": bagi liter berdasarkan
     * proporsi jam kerja masing-masing operator di hari itu.
     *
     * Skenario Sumingkir: 2 operator bertugas bergantian/bersamaan dalam 1 hari.
     * Tiap operator punya shift record sendiri dengan jam_mulai & jam_selesai.
     * Proporsi = jam_operator / total_jam_semua_operator.
     *
     * Jika jam tidak tersedia di semua shift → fallback ke split rata (tanda 'fallback').
     * Jika jam tersedia di sebagian shift → hanya yang ada jam yang dihitung proporsional;
     * yang tidak ada jam diasumsikan sama dengan rata-rata.
     */
    private function splitProporsionalJam(
        array &$dailySplitsData,
        array &$operatorTotals,
        Collection $shifts,
        string $tanggal,
        float $volumeAktual,
        float $volDihitung
    ): void {
        // Kelompokkan per operator (1 operator bisa punya >1 shift slot di 1 hari)
        $operatorShifts = $shifts->groupBy('operator_id');

        // Hitung total jam kerja tiap operator
        $jamKerjaPerOp   = [];
        $totalJamKerja   = 0.0;
        $adaJam          = false;
        $jumlahOpAktif   = 0;

        foreach ($operatorShifts as $opId => $opShifts) {
            if (!isset($operatorTotals[$opId])) continue;
            $jumlahOpAktif++;

            $jamOp = 0.0;
            foreach ($opShifts as $shift) {
                if ($shift->jam_mulai && $shift->jam_selesai) {
                    $mulai   = Carbon::parse($tanggal . ' ' . $shift->jam_mulai);
                    $selesai = Carbon::parse($tanggal . ' ' . $shift->jam_selesai);
                    if ($selesai->lt($mulai)) $selesai->addDay(); // lintas tengah malam
                    $jamOp  += $mulai->diffInMinutes($selesai) / 60;
                    $adaJam  = true;
                } else {
                    // Tidak ada jam → akan pakai fallback 8 jam per slot
                    $jamOp += 8.0;
                }
            }

            $jamKerjaPerOp[$opId] = $jamOp;
            $totalJamKerja       += $jamOp;
        }

        if ($totalJamKerja <= 0 || $jumlahOpAktif === 0) return;

        foreach ($jamKerjaPerOp as $opId => $jam) {
            $proporsi    = $jam / $totalJamKerja;
            $literBagian = $volDihitung * $proporsi;
            $isFallback  = !$adaJam;

            $dailySplitsData[] = [
                'operator_id'             => $opId,
                'tanggal'                 => $tanggal,
                'volume_penjualan_aktual' => $volumeAktual * $proporsi,
                'volume_dihitung'         => $volDihitung * $proporsi,
                'liter_bagian'            => $literBagian,
                'proporsi'               => round($proporsi, 4),
                'sumber'                 => 'otomatis',
                'keterangan'             => $isFallback
                    ? round($jam, 1) . ' jam (fallback — isi jam_mulai/jam_selesai untuk presisi)'
                    : round($jam, 1) . ' jam kerja',
            ];

            $operatorTotals[$opId]['liter'] += $literBagian;
            $operatorTotals[$opId]['hari']++;  // 1 hari kerja per operator per hari
        }
    }

    /**
     * Tambah split untuk 1 operator dengan 100% porsi (fallback saat tidak ada shift).
     */
    private function tambahSplitHariPenuh(
        array &$dailySplitsData,
        array &$operatorTotals,
        int $opId,
        string $tanggal,
        float $volumeAktual,
        float $volDihitung
    ): void {
        $dailySplitsData[] = [
            'operator_id'            => $opId,
            'tanggal'                => $tanggal,
            'volume_penjualan_aktual' => $volumeAktual,
            'volume_dihitung'        => $volDihitung,
            'liter_bagian'           => $volDihitung,
            'proporsi'               => 1.0,
            'sumber'                 => 'otomatis',
            'keterangan'             => 'Operator dari laporan harian (tanpa jadwal shift)',
        ];

        $operatorTotals[$opId]['liter'] += $volDihitung;
        $operatorTotals[$opId]['hari']++;
    }
}
```



# =============================================================================
# 7. SERVICE LAPORAN BULANAN MANAJERIAL: MonthlyReportCalculationService
# File: app/Services/MonthlyReportCalculationService.php
# Perhitungan 4 halaman laporan bulanan akuntansi & investor
# =============================================================================

```php
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
        if ($piutang <= 0) {
            $piutang = floatval(\App\Models\Receivable::where('shop_id', $shop->id)
                ->where('tanggal', '<=', $period->copy()->endOfMonth()->toDateString())
                ->selectRaw('SUM(jumlah_piutang - jumlah_dibayar) as total')
                ->value('total') ?? 0);
        }

        // ─────────────────────────────────────────────────────────────────
        // Agregasi Hutang Bakulan & Nett Cashflow (Spec Section 4.1)
        // Nett Cashflow = Omset_Gross - Biaya_Ops - Hutang_Bakulan + Pelunasan_Hutang
        // ─────────────────────────────────────────────────────────────────
        $monthDailyReports = DailyReport::where('shop_id', $shop->id)
            ->whereMonth('report_date', $month)
            ->whereYear('report_date', $year)
            ->get();

        $totalHutangBakulan = floatval($monthDailyReports->sum('hutang_bakulan'));
        $totalPelunasanBakulan = floatval($monthDailyReports->sum('pelunasan_bakulan'));

        $totalOmsetGross = floatval($monthDailyReports->sum('rupiah_penjualan_aktual'));
        if ($totalOmsetGross <= 0) {
            $totalOmsetGross = collect($segments)->sum('jumlah_penjualan_rp');
        }

        $nettCashflow = $totalOmsetGross - $totalBiaya - $totalHutangBakulan + $totalPelunasanBakulan;

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

            // Metrik Arus Kas Bersih (Nett Cashflow) - Spec Section 4.1
            'total_omset_gross'        => $totalOmsetGross,
            'total_hutang_bakulan'     => $totalHutangBakulan,
            'total_pelunasan_bakulan'  => $totalPelunasanBakulan,
            'nett_cashflow'            => $nettCashflow,

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
        $dailyReports = DailyReport::with(['price', 'periods.price'])
            ->where('shop_id', $shop->id)
            ->whereMonth('report_date', $month)
            ->whereYear('report_date', $year)
            ->orderBy('report_date', 'asc')
            ->get();

        if ($dailyReports->isEmpty()) {
            return [];
        }

        // Ambil riwayat harga untuk toko ini
        $allPrices = Price::where(function($q) use ($shop) {
                $q->where('shop_id', $shop->id)->orWhereNull('shop_id');
            })
            ->orderBy('effective_at', 'asc')
            ->get();

        // Kelompokkan laporan harian menjadi segmen-segmen harga berurutan
        $segmentGroups = [];
        $currentGroup = [];
        $currentPriceKey = null;

        foreach ($dailyReports as $rep) {
            $repDate = Carbon::parse($rep->report_date)->format('Y-m-d');
            $hBeli = 0;
            $hJual = 0;

            if (floatval($rep->harga_jual_snapshot) > 0 && floatval($rep->harga_beli_snapshot) > 0) {
                $hBeli = floatval($rep->harga_beli_snapshot);
                $hJual = floatval($rep->harga_jual_snapshot);
            } elseif ($rep->price) {
                $hBeli = floatval($rep->price->harga_beli);
                $hJual = floatval($rep->price->harga_jual);
            } else {
                $effectivePrice = $allPrices->filter(function($p) use ($repDate) {
                    return $p->effective_at <= $repDate;
                })->last();

                if ($effectivePrice) {
                    $hBeli = floatval($effectivePrice->harga_beli);
                    $hJual = floatval($effectivePrice->harga_jual);
                } else {
                    $fallbackPrice = $allPrices->last();
                    $hBeli = $fallbackPrice ? floatval($fallbackPrice->harga_beli) : 11376.29;
                    $hJual = $fallbackPrice ? floatval($fallbackPrice->harga_jual) : 12200;
                }
            }

            $priceKey = number_format($hBeli, 2, '.', '') . '_' . number_format($hJual, 2, '.', '');

            if ($currentPriceKey !== null && $priceKey !== $currentPriceKey) {
                $segmentGroups[] = [
                    'price_key'  => $currentPriceKey,
                    'harga_beli' => $currentGroup[0]['harga_beli'],
                    'harga_jual' => $currentGroup[0]['harga_jual'],
                    'reports'    => collect(array_column($currentGroup, 'report')),
                ];
                $currentGroup = [];
            }

            $currentPriceKey = $priceKey;
            $currentGroup[] = [
                'harga_beli' => $hBeli,
                'harga_jual' => $hJual,
                'report'     => $rep,
            ];
        }

        if (!empty($currentGroup)) {
            $segmentGroups[] = [
                'price_key'  => $currentPriceKey,
                'harga_beli' => $currentGroup[0]['harga_beli'],
                'harga_jual' => $currentGroup[0]['harga_jual'],
                'reports'    => collect(array_column($currentGroup, 'report')),
            ];
        }

        $segments = [];
        $prevStokAkhirFisik = null;

        foreach ($segmentGroups as $index => $group) {
            $segIndex = $index + 1;
            $reps = $group['reports'];
            $firstRep = $reps->first();
            $lastRep = $reps->last();
            $hargaBeli = $group['harga_beli'];
            $hargaJual = $group['harga_jual'];

            // Stok Awal segmen: segmen 1 ambil stok awal, segmen berikutnya estafet dari akhir fisik segmen sebelumnya
            if ($prevStokAkhirFisik !== null) {
                $stokAwal = $prevStokAkhirFisik;
            } else {
                $stokAwal = floatval($firstRep->stok_awal ?? 0);
            }

            $totalTerimaBbm = $reps->sum('penerimaan');
            $jumlahPembelian = $stokAwal + $totalTerimaBbm;
            $jumlahPembelianRp = $jumlahPembelian * $hargaBeli;

            $totAwal = floatval($firstRep->totalisator_awal ?? 0);
            $totAkhir = floatval($lastRep->totalisator_akhir ?? 0);
            $totalPenjualanFisik = max(0, $totAkhir - $totAwal);
            $testPump = $reps->sum('test_pump');
            $jumlahPenjualan = max(0, $totalPenjualanFisik - $testPump);
            $jumlahPenjualanRp = $jumlahPenjualan * $hargaJual;

            $sisaStokTeoretis = max(0, $jumlahPembelian - $jumlahPenjualan);
            $sisaStokTeoretisRp = $sisaStokTeoretis * $hargaBeli;

            $stokAkhirFisik = floatval($lastRep->stok_akhir_aktual ?? $sisaStokTeoretis);
            $stokAkhirFisikRp = $stokAkhirFisik * $hargaBeli;
            $prevStokAkhirFisik = $stokAkhirFisik;

            $lossesGain = $stokAkhirFisik - $sisaStokTeoretis;
            $lossesGainPersen = $jumlahPenjualan > 0 ? ($lossesGain / $jumlahPenjualan) * 100 : 0;
            $lossesGainRp = $lossesGain * $hargaBeli;

            // Penjualan Bersih = Penjualan Kotor + (Sisa Stok Teoritis * Harga Beli) + (Losses/Gain * Harga Beli)
            // = Penjualan Kotor + (Stok Akhir Fisik * Harga Beli)
            $penjualanBersih = ($jumlahPenjualanRp + $sisaStokTeoretisRp) + $lossesGainRp;
            // Laba Kotor = Penjualan Bersih - Total Pembelian = (Penjualan * (Harga Jual - Harga Beli)) + Losses/Gain Rp
            $labaKotor = $penjualanBersih - $jumlahPembelianRp;

            $startDateLabel = Carbon::parse($firstRep->report_date)->format("d M'y");
            $endDateLabel   = Carbon::parse($lastRep->report_date)->format("d M'y");

            $segments[] = [
                'segmen_index'             => $segIndex,
                'start_date'               => $startDateLabel,
                'end_date'                 => $endDateLabel,
                'start_datetime_label'     => $startDateLabel . " Jam 06.00",
                'end_datetime_label'       => $endDateLabel . " Jam 18.00",
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
            ];
        }

        return $segments;
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
```



# =============================================================================
# 8. ACTION GENERATOR LAPORAN BULANAN: GenerateMonthlyReport
# File: app/Actions/GenerateMonthlyReport.php
# Agregasi data daily report ke laporan bulanan
# =============================================================================

```php
<?php

namespace App\Actions;

use App\Models\Shop;
use App\Models\DailyReport;
use App\Models\Incoming;
use App\Models\MonthlyReport;
use App\Models\CapitalRecap;
use App\Models\SpendingCategory;
use Illuminate\Support\Carbon;

class GenerateMonthlyReport
{
    /**
     * Generate atau update MonthlyReport berdasarkan data DailyReports dari database.
     * 
     * @param int $shopId
     * @param string $yearMonth Format YYYY-MM (e.g. "2026-07")
     * @return MonthlyReport
     */
    public function handle(int $shopId, string $yearMonth): MonthlyReport
    {
        $date = Carbon::createFromFormat('Y-m', $yearMonth);
        $year = (int) $date->format('Y');
        $month = (int) $date->format('m');
        $bulanTahunText = $date->translatedFormat('F Y');

        $shop = Shop::findOrFail($shopId);

        // Fetch daily reports for this shop and month
        $dailyReports = DailyReport::with(['spendings.category', 'incomings', 'price'])
            ->where('shop_id', $shopId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($dailyReports->isEmpty()) {
            throw new \Exception("Tidak ada data Laporan Harian untuk {$shop->nama} pada bulan {$bulanTahunText}.");
        }

        $categories = SpendingCategory::all()->keyBy('id');

        $dataParsed = [];
        $grandTotals = [
            'total_volume'           => 0,
            'total_rupiah_penjualan' => 0,
            'total_pengeluaran'      => 0,
            'total_pendapatan'       => 0,
            'total_setor_tunai'      => 0,
            'total_setor_qris'       => 0,
            'total_setor_transfer'   => 0,
            'total_setor_kolektan'   => 0,
            'total_disetorkan'       => 0,
            'total_losses_gain'      => 0,
            'pengeluaran_per_kategori' => [],
        ];

        foreach ($categories as $catId => $cat) {
            $grandTotals['pengeluaran_per_kategori'][$catId] = 0;
        }

        $firstReport = $dailyReports->first();
        $lastReport = $dailyReports->last();

        foreach ($dailyReports as $dr) {
            $vol = (float) $dr->volume_penjualan_teoritis;
            $rupiahPenjualan = (float) $dr->rupiah_penjualan;
            $pengeluaran = (float) $dr->total_spendings;
            $pendapatan = (float) $dr->pendapatan;

            $setorTunai = (float) $dr->setor_tunai;
            $setorQris = (float) $dr->setor_qris;
            $setorTransfer = (float) $dr->setor_transfer;
            $setorKolektan = (float) $dr->setor_kolektan;
            $disetorkan = (float) $dr->disetorkan;
            $losses = (float) $dr->losses_gain;

            // Grand totals accumulation
            $grandTotals['total_volume'] += $vol;
            $grandTotals['total_rupiah_penjualan'] += $rupiahPenjualan;
            $grandTotals['total_pengeluaran'] += $pengeluaran;
            $grandTotals['total_pendapatan'] += $pendapatan;
            $grandTotals['total_setor_tunai'] += $setorTunai;
            $grandTotals['total_setor_qris'] += $setorQris;
            $grandTotals['total_setor_transfer'] += $setorTransfer;
            $grandTotals['total_setor_kolektan'] += $setorKolektan;
            $grandTotals['total_disetorkan'] += $disetorkan;
            $grandTotals['total_losses_gain'] += $losses;

            $spendingMap = [];
            foreach ($dr->spendings as $sp) {
                $amt = (float) $sp->jumlah;
                $spendingMap[$sp->spending_category_id] = $amt;
                if (isset($grandTotals['pengeluaran_per_kategori'][$sp->spending_category_id])) {
                    $grandTotals['pengeluaran_per_kategori'][$sp->spending_category_id] += $amt;
                }
            }

            $dataParsed[] = [
                'tanggal'                  => Carbon::parse($dr->created_at)->format('Y-m-d'),
                'totalisator_awal'         => (float) $dr->totalisator_awal,
                'totalisator_akhir'        => (float) $dr->totalisator_akhir,
                'volume_penjualan_teoritis'=> $vol,
                'harga_jual'               => (float) ($dr->price?->harga_jual ?? 0),
                'rupiah_penjualan'         => $rupiahPenjualan,
                'stik_awal'                => (float) $dr->stik_awal,
                'stok_awal'                => (float) $dr->stok_awal,
                'penerimaan_volume'        => (float) $dr->penerimaan_volume,
                'stik_akhir'               => (float) $dr->stik_akhir,
                'stok_akhir_aktual'        => (float) $dr->stok_akhir_aktual,
                'stok_akhir_teoritis'      => (float) $dr->stok_akhir_teoritis,
                'losses_gain'              => $losses,
                'total_pengeluaran'        => $pengeluaran,
                'pendapatan'               => $pendapatan,
                'setor_tunai'              => $setorTunai,
                'setor_qris'               => $setorQris,
                'setor_transfer'           => $setorTransfer,
                'setor_kolektan'           => $setorKolektan,
                'disetorkan'               => $disetorkan,
                'selisih_setoran'          => (float) $dr->selisih_setoran,
                'belum_disetorkan'         => (float) $dr->belum_disetorkan,
                'spendings'                => $spendingMap,
            ];
        }

        // Fetch BBM Datang (Incomings)
        $incomings = Incoming::where('shop_id', $shopId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'asc')
            ->get();

        $bbmDatang = $incomings->map(fn ($inc) => [
            'tanggal'        => Carbon::parse($inc->created_at)->format('Y-m-d'),
            'no_polisi'      => $inc->no_polisi,
            'sopir'          => $inc->sopir,
            'volume'         => (float) $inc->volume,
            'penerimaan_real'=> (float) ($inc->penerimaan_real ?? $inc->volume),
            'losses_gain'    => (float) $inc->losses_gain,
        ])->toArray();

        // Capital Recap positions
        $capitalRecap = CapitalRecap::where('shop_id', $shopId)
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->first();

        $saldoAwalModal = $capitalRecap ? (float) $capitalRecap->saldo_awal : (float) $shop->modal_awal;
        $saldoAkhirModal = $capitalRecap ? (float) $capitalRecap->saldo_akhir : (float) $shop->modal_awal;

        return MonthlyReport::updateOrCreate(
            [
                'shop_id'     => $shopId,
                'bulan_tahun' => $bulanTahunText,
            ],
            [
                'totalisator_awal'  => (float) $firstReport->totalisator_awal,
                'totalisator_akhir' => (float) $lastReport->totalisator_akhir,
                'grand_totals'      => $grandTotals,
                'data_parsed'       => $dataParsed,
                'bbm_datang'        => $bbmDatang,
                'saldo_awal_modal'  => $saldoAwalModal,
                'saldo_akhir_modal' => $saldoAkhirModal,
                'kas_kecil'         => $grandTotals['total_disetorkan'],
            ]
        );
    }
}
```



# =============================================================================
# FOLDER TESTS/ (Seluruh Test Suite & Konfigurasi Pengujian)
# =============================================================================



# =============================================================================
# TEST: tests/TestCase.php
# File: tests/TestCase.php
# Base TestCase Antigravity/Laravel
# =============================================================================

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
```



# =============================================================================
# TEST: tests/CreatesApplication.php
# File: tests/CreatesApplication.php
# CreatesApplication Trait dengan pengaman database berakhiran _test
# =============================================================================

```php
<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $connection = $app['config']->get('database.default');
        $dbName = (string) $app['config']->get("database.connections.{$connection}.database");

        if (!str_ends_with($dbName, '_test')) {
            throw new \RuntimeException(
                "CRITICAL SAFETY VIOLATION: Test suite MUST run on a database ending with '_test'. Aborting BEFORE any migration or database operations. Current DB: '{$dbName}' on connection '{$connection}'."
            );
        }

        return $app;
    }
}
```



# =============================================================================
# TEST: tests/Feature/KemutugMarch2026ExactCellVerificationTest.php
# File: tests/Feature/KemutugMarch2026ExactCellVerificationTest.php
# Test Uji Presisi Seluruh Sel 31 Hari x 23 Kolom, Rekap, dan Ekspor Excel Kemutug Maret 2026
# =============================================================================

```php
<?php

namespace Tests\Feature;

use App\Models\Corporation;
use App\Models\DailyReport;
use App\Models\Incoming;
use App\Models\Operator;
use App\Models\PayrollSystem;
use App\Models\Price;
use App\Models\Purchase;
use App\Models\Shop;
use App\Models\Spending;
use App\Models\SpendingCategory;
use App\Models\Supplier;
use App\Models\TestPump;
use App\Models\User;
use App\Services\DailyReportCalculationService;
use App\Services\PertashopDailyRecapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KemutugMarch2026ExactCellVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected Shop $shop;
    protected User $superAdmin;
    protected Operator $operatorDendi;
    protected Operator $operatorWawan;
    protected Price $price;
    protected Purchase $purchase;
    protected array $dataset;

    protected function setUp(): void
    {
        parent::setUp();

        DailyReport::clearInternalCache();
        Schema::disableForeignKeyConstraints();
        DB::table('spendings')->delete();
        DB::table('incomings')->delete();
        DB::table('test_pumps')->delete();
        DB::table('daily_reports')->delete();
        DB::table('payroll_daily_splits')->delete();
        DB::table('payroll_details')->delete();
        DB::table('payroll_periods')->delete();
        DB::table('payroll_systems')->delete();
        DB::table('purchases')->delete();
        DB::table('suppliers')->delete();
        DB::table('prices')->delete();
        DB::table('operators')->delete();
        DB::table('users')->delete();
        DB::table('shops')->delete();
        DB::table('corporations')->delete();
        DB::table('spending_categories')->delete();
        Schema::enableForeignKeyConstraints();

        $this->seed(\Database\Seeders\BusinessRuleSeeder::class);

        // 1. User Super Admin
        $this->superAdmin = User::create([
            'name'     => 'Super Admin Rekap',
            'email'    => 'admin.rekap@pertashop.test',
            'role'     => 'super-admin',
            'password' => bcrypt('password123'),
        ]);

        // 2. Corporation & Shop
        $corp = Corporation::create([
            'nama'   => 'KPRI KOKARNABA BATURRADEN',
            'alamat' => 'Baturraden, Banyumas',
        ]);

        $this->shop = Shop::create([
            'nama'                     => 'Pertashop Kemutug Lor',
            'kode'                     => '4P.53143',
            'corporation_id'           => $corp->id,
            'alamat'                   => 'Kemutug Lor, Baturraden',
            'skala'                    => 21.49, // Koefisien tangki 21.49 L/cm
            'totalisator_awal'         => 498129.800,
            'stik_awal'                => 70.4,
            'modal_awal'               => 470490000,
            'uang_belum_disetor_awal'  => -26020818,
            'is_active'                => true,
        ]);

        // 3. Supplier & Purchase
        $supplier = Supplier::create([
            'nama'   => 'PT Pertamina Patra Niaga',
            'alamat' => 'Cilacap',
        ]);

        $this->purchase = Purchase::create([
            'no_so'       => 'SO-2026-KMT-03',
            'shop_id'     => $this->shop->id,
            'supplier_id' => $supplier->id,
            'volume'      => 30000,
            'total_bayar' => 30000 * 11276.08,
        ]);

        // 4. Operators
        $userDendi = User::create([
            'name'     => 'Dendi Supriyatno',
            'email'    => 'dendi.kmt@pertashop.test',
            'role'     => 'operator',
            'password' => bcrypt('secret'),
        ]);
        $this->operatorDendi = Operator::create([
            'user_id' => $userDendi->id,
            'shop_id' => $this->shop->id,
            'nik'     => 'OP-DENDI',
        ]);

        $userWawan = User::create([
            'name'     => 'Wawan',
            'email'    => 'wawan.kmt@pertashop.test',
            'role'     => 'operator',
            'password' => bcrypt('secret'),
        ]);
        $this->operatorWawan = Operator::create([
            'user_id' => $userWawan->id,
            'shop_id' => $this->shop->id,
            'nik'     => 'OP-WAWAN',
        ]);

        // 5. Prices
        $this->price = Price::create([
            'shop_id'      => $this->shop->id,
            'harga_jual'   => 12200,
            'harga_beli'   => 11276.08,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        // 6. Payroll System
        PayrollSystem::create([
            'shop_id'                  => $this->shop->id,
            'nama_sistem'              => 'Sistem Komisi Kemutug',
            'ada_rate_per_liter'       => true,
            'rate_per_liter'           => 200,
            'ada_gaji_pokok'           => false,
            'nominal_gaji_pokok'       => 0,
            'potongan_per_hari_alpha'  => 0,
            'perlakuan_losses_gain'    => 'losses_only',
            'metode_split'             => 'per_hari_penuh',
            'aktif'                    => true,
        ]);

        // 7. Spending Categories
        $cats = [
            1  => 'Ongkos Bongkar',
            2  => 'Biaya Transfer',
            3  => 'Fotocopy ATK',
            4  => 'Listrik',
            5  => 'Air Bersih',
            6  => 'Cashback',
            7  => 'Internet',
            99 => 'Lain-lain',
        ];
        foreach ($cats as $id => $name) {
            SpendingCategory::create(['id' => $id, 'nama' => $name]);
        }

        // 8. Load Dataset acuan nilai sel tersimpan Excel
        $datasetPath = base_path('tests/kemutug_maret_2026_expected_dari_excel.json');
        if (!file_exists($datasetPath)) {
            $datasetPath = base_path('tests/kemutug_maret_2026_dataset.json');
        }
        $this->dataset = json_decode(file_get_contents($datasetPath), true);

        // 9. Seed 31 Days
        $this->seedDailyReportsFromDataset();
    }

    protected function seedDailyReportsFromDataset(): void
    {
        foreach ($this->dataset['daily_reports'] as $row) {
            $day = $row['tgl'];
            $dateStr = sprintf('2026-03-%02d', $day);
            $opId = ($row['operator'] === 'Wawan') ? $this->operatorWawan->id : $this->operatorDendi->id;

            $disetorkan = ($row['setoran_bri'] ?? 0) + ($row['setoran_qris'] ?? 0) + ($row['setoran_transfer'] ?? 0);

            $report = DailyReport::create([
                'shop_id'             => $this->shop->id,
                'operator_id'         => $opId,
                'price_id'            => $this->price->id,
                'report_date'         => $dateStr,
                'totalisator_awal'    => $row['totalisator_awal'],
                'totalisator_akhir'   => $row['totalisator_akhir'],
                'stik_awal'           => 0,
                'stik_akhir'          => $row['stok_aktual_cm'],
                'stok_awal'           => $row['stok_awal'],
                'disetorkan'          => $disetorkan,
                'setor_tunai'         => $row['setoran_bri'] ?? 0,
                'setor_qris'          => $row['setoran_qris'] ?? 0,
                'setor_transfer'      => $row['setoran_transfer'] ?? 0,
                'test_pump_volume'    => $row['test_pump_vol'] ?? 0,
                'bbm_keluar_lain'     => $row['kirim_kemutug_vol'] ?? 0,
                'penerimaan_volume'   => $row['terima_bbm'] ?? 0,
                'harga_jual_snapshot' => 12200,
                'harga_beli_snapshot' => 11276.08,
                'status_lifecycle'    => 'approved',
                'created_at'          => "{$dateStr} 20:00:00",
                'updated_at'          => "{$dateStr} 20:00:00",
            ]);

            if ($row['test_pump_vol'] > 0) {
                TestPump::create([
                    'daily_report_id'   => $report->id,
                    'shop_id'           => $this->shop->id,
                    'operator_id'       => $opId,
                    'totalisator_awal'  => $row['totalisator_awal'],
                    'totalisator_akhir' => $row['totalisator_awal'] + $row['test_pump_vol'],
                    'volume'            => $row['test_pump_vol'],
                    'created_at'        => "{$dateStr} 10:00:00",
                ]);
            }

            if ($row['terima_bbm'] > 0) {
                Incoming::create([
                    'daily_report_id' => $report->id,
                    'purchase_id'     => $this->purchase->id,
                    'shop_id'         => $this->shop->id,
                    'operator_id'     => $opId,
                    'volume'          => $row['terima_bbm'],
                    'sopir'           => 'Supir Tangki',
                    'no_polisi'       => 'R-1234-AA',
                    'stik_awal'       => 0,
                    'stik_akhir'      => 0,
                    'created_at'      => "{$dateStr} 12:00:00",
                ]);
            }

            $spendings = [
                1 => $row['ongkos_bongkar'] ?? 0,
                2 => $row['biaya_transfer'] ?? 0,
                3 => $row['fotocopy_atk'] ?? 0,
                4 => $row['listrik'] ?? 0,
                5 => $row['air_bersih'] ?? 0,
                6 => $row['cashback'] ?? 0,
                7 => $row['internet'] ?? 0,
                99 => $row['lain_lain'] ?? 0,
            ];

            foreach ($spendings as $catId => $amt) {
                if ($amt > 0) {
                    Spending::create([
                        'daily_report_id'      => $report->id,
                        'spending_category_id' => $catId,
                        'shop_id'              => $this->shop->id,
                        'operator_id'          => $opId,
                        'jumlah'               => $amt,
                        'keterangan'           => ($catId == 99) ? ($row['keterangan_lain'] ?: 'Lain-lain') : '-',
                        'created_at'           => "{$dateStr} 18:00:00",
                    ]);
                }
            }
        }

        DailyReportCalculationService::recalculateCascade(
            $this->shop->id,
            Carbon::parse('2026-03-01'),
            -26020818,
            498129.8,
            1512.896
        );
    }

    /**
     * UJI Bagian 7.3: Verifikasi 713 Sel Harian (31 hari x 23 kolom HASIL)
     * Toleransi delta < 0.001 untuk angka volume dan < 1 rupiah untuk uang.
     * Status teks ("Pas", "Losses", "Gain") sama persis.
     */
    public function test_exact_713_daily_cells_match_excel_kemutug_maret_2026(): void
    {
        $recap = PertashopDailyRecapService::getMonthlyRecap($this->shop->id, 3, 2026);
        $rows = $recap['rows'];

        $this->assertCount(31, $rows, 'Harus ada tepat 31 hari laporan.');

        $expectedDatasetMap = collect($this->dataset['daily_reports'])->keyBy('tgl');

        $verifiedCellCount = 0;

        foreach ($rows as $day => $calculated) {
            $expected = $expectedDatasetMap->get($day);
            $this->assertNotNull($expected, "Data ekspektasi hari ke-{$day} harus ada.");

            // 1. Kolom D: Totalisator Awal
            $this->assertEqualsWithDelta($expected['totalisator_awal'], $calculated['D_totalisator_awal'], 0.000001, "Hari {$day} Kolom D (Totalisator Awal)");
            $verifiedCellCount++;

            // 2. Kolom F: Penjualan Teoritis Volume
            $this->assertEqualsWithDelta($expected['penjualan_teoritis_vol'], $calculated['F_penjualan_teoritis_vol'], 0.000001, "Hari {$day} Kolom F (Teoritis Vol)");
            $verifiedCellCount++;

            // 3. Kolom G: Penjualan Teoritis Rupiah
            $this->assertEqualsWithDelta($expected['penjualan_teoritis_rp'], $calculated['G_penjualan_teoritis_rp'], 0.000001, "Hari {$day} Kolom G (Teoritis Rp)");
            $verifiedCellCount++;

            // 4. Kolom I: Test Pump Rupiah
            $this->assertEqualsWithDelta($expected['test_pump_rp'], $calculated['I_test_pump_rp'], 0.000001, "Hari {$day} Kolom I (Test Pump Rp)");
            $verifiedCellCount++;

            // 5. Kolom K: Kirim Kemutug Rupiah
            $this->assertEqualsWithDelta($expected['kirim_kemutug_rp'], $calculated['K_bbm_keluar_lain_rp'], 0.000001, "Hari {$day} Kolom K (Kirim Rp)");
            $verifiedCellCount++;

            // 6. Kolom L: Penjualan Aktual Volume
            $this->assertEqualsWithDelta($expected['penjualan_aktual_vol'], $calculated['L_penjualan_aktual_vol'], 0.000001, "Hari {$day} Kolom L (Aktual Vol)");
            $verifiedCellCount++;

            // 7. Kolom M: Penjualan Aktual Rupiah
            $this->assertEqualsWithDelta($expected['penjualan_aktual_rp'], $calculated['M_penjualan_aktual_rp'], 0.000001, "Hari {$day} Kolom M (Aktual Rp)");
            $verifiedCellCount++;

            // 8. Kolom N: Stok Awal
            $this->assertEqualsWithDelta($expected['stok_awal'], $calculated['N_stok_awal'], 0.000001, "Hari {$day} Kolom N (Stok Awal)");
            $verifiedCellCount++;

            // 9. Kolom Q: Stok Teoritis
            $this->assertEqualsWithDelta($expected['stok_teoritis'], $calculated['Q_stok_teoritis'], 0.000001, "Hari {$day} Kolom Q (Stok Teoritis)");
            $verifiedCellCount++;

            // 10. Kolom S: Stok Aktual Volume
            $this->assertEqualsWithDelta($expected['stok_aktual_vol'], $calculated['S_stok_aktual_vol'], 0.000001, "Hari {$day} Kolom S (Stok Aktual Vol)");
            $verifiedCellCount++;

            // 11. Kolom T: Nominal Losses / Gain Liter
            $this->assertEqualsWithDelta($expected['losses_gain_liter'], $calculated['T_losses_gain_liter'], 0.000001, "Hari {$day} Kolom T (Losses/Gain L)");
            $verifiedCellCount++;

            // 12. Kolom U: Nominal Rupiah
            $this->assertEqualsWithDelta($expected['losses_gain_rp'], $calculated['U_losses_gain_rp'], 0.000001, "Hari {$day} Kolom U (Losses/Gain Rp)");
            $verifiedCellCount++;

            // 13. Kolom V: Losses / Gain Status
            $this->assertSame($expected['status_losses_gain'], $calculated['V_status_losses_gain'], "Hari {$day} Kolom V (Status)");
            $verifiedCellCount++;

            // 14. Kolom W: Stok Akhir
            $this->assertEqualsWithDelta($expected['stok_akhir'], $calculated['W_stok_akhir'], 0.000001, "Hari {$day} Kolom W (Stok Akhir)");
            $verifiedCellCount++;

            // 15. Kolom X: Jumlah Actual Penjualan
            $this->assertEqualsWithDelta($expected['penjualan_aktual_rp'], $calculated['X_jumlah_actual_penjualan'], 0.000001, "Hari {$day} Kolom X (Jumlah Aktual Penjualan)");
            $verifiedCellCount++;

            // 16. Kolom AG: Total Pengeluaran
            $this->assertEqualsWithDelta($expected['total_pengeluaran'], $calculated['AG_total_pengeluaran'], 0.000001, "Hari {$day} Kolom AG (Total Pengeluaran)");
            $verifiedCellCount++;

            // 17. Kolom AJ: Pendapatan Dikurangi Pengeluaran
            $this->assertEqualsWithDelta($expected['pendapatan_dikurangi_pengeluaran'], $calculated['AJ_pendapatan_minus_pengeluaran'], 0.000001, "Hari {$day} Kolom AJ (Pendapatan - Biaya)");
            $verifiedCellCount++;

            // 18. Kolom AO: Selisih Uang Yang Disetorkan dg Setoran
            $this->assertEqualsWithDelta($expected['selisih_setoran'], $calculated['AO_selisih_setoran'], 0.000001, "Hari {$day} Kolom AO (Selisih Setoran)");
            $verifiedCellCount++;

            // 19. Kolom AP: Total Uang Yang Belum Disetorkan
            $this->assertEqualsWithDelta($expected['total_uang_belum_disetor'], $calculated['AP_total_uang_belum_disetor'], 0.000001, "Hari {$day} Kolom AP (Uang Belum Disetor)");
            $verifiedCellCount++;

            // 20. Kolom AS: Omset Kotor (= L)
            $this->assertEqualsWithDelta($expected['penjualan_aktual_vol'], $calculated['AS_omset_kotor'], 0.000001, "Hari {$day} Kolom AS (Omset Kotor)");
            $verifiedCellCount++;

            // 21. Kolom AT: Status Gaji (= V)
            $this->assertSame($expected['status_losses_gain'], $calculated['AT_status_gaji'], "Hari {$day} Kolom AT (Status Gaji)");
            $verifiedCellCount++;

            // 22. Kolom AU: Losses / Gain Gaji (= T)
            $this->assertEqualsWithDelta($expected['losses_gain_liter'], $calculated['AU_losses_gain_gaji'], 0.000001, "Hari {$day} Kolom AU (Losses/Gain Gaji)");
            $verifiedCellCount++;

            // 23. Kolom AV: Omset + Losses
            $expectedAv = ($expected['status_losses_gain'] === 'Losses')
                ? ($expected['penjualan_aktual_vol'] + $expected['losses_gain_liter'])
                : $expected['penjualan_aktual_vol'];
            $this->assertEqualsWithDelta($expectedAv, $calculated['AV_omset_plus_losses'], 0.000001, "Hari {$day} Kolom AV (Omset + Losses)");
            $verifiedCellCount++;
        }

        // 31 hari x 23 kolom = 713 sel terverifikasi
        $this->assertEquals(713, $verifiedCellCount, 'Tepat 713 sel harian terverifikasi.');
    }

    /**
     * UJI Bagian 7.4: Verifikasi Seluruh Baris Rekap Bulanan (Baris 35, 36, 37, 38, 39)
     */
    public function test_monthly_recap_summary_rows_match_excel_specification(): void
    {
        $recap = PertashopDailyRecapService::getMonthlyRecap($this->shop->id, 3, 2026);
        $summary = $recap['summary'];
        $tot = $summary['row_total_35'];
        $avg = $summary['row_averages_closing_36_39'];

        $expectedSummary = $this->dataset['monthly_summary'];

        // F35: total penjualan teoritis volume = 23594.93
        $this->assertEqualsWithDelta($expectedSummary['F35_penjualan_teoritis_vol'], $tot['F_penjualan_teoritis_vol'], 0.000001);

        // G35: total penjualan teoritis rupiah = 287.858.146
        $this->assertEqualsWithDelta($expectedSummary['G35_penjualan_teoritis_rp'], $tot['G_penjualan_teoritis_rp'], 0.000001);

        // H35: total test pump vol = 10
        $this->assertEqualsWithDelta($expectedSummary['H35_test_pump_vol'], $tot['H_test_pump_vol'], 0.000001);

        // L35: total penjualan aktual volume = 23584.93
        $this->assertEqualsWithDelta($expectedSummary['L35_penjualan_aktual_vol'], $tot['L_penjualan_aktual_vol'], 0.000001);

        // M35: total penjualan aktual rupiah = 287.736.146
        $this->assertEqualsWithDelta($expectedSummary['M35_penjualan_aktual_rp'], $tot['M_penjualan_aktual_rp'], 0.000001);

        // T35: losses_gain bersih = -355.613
        $this->assertEqualsWithDelta($expectedSummary['T35_losses_gain_liter'], $tot['T_losses_gain_liter'], 0.000001);

        // U35: nominal rupiah losses/gain dari dataset tersimpan = -4009920.6370400735
        $this->assertEqualsWithDelta($expectedSummary['U35_losses_gain_rp'], $tot['U_losses_gain_rp'], 0.000001);

        // V35: status bulanan = Losses
        $this->assertSame($expectedSummary['V35_status_losses_gain'], $tot['V_status_losses_gain']);

        // AG35: total pengeluaran = 5.388.900
        $this->assertEquals($expectedSummary['AG35_total_pengeluaran'], $tot['AG_total_pengeluaran']);

        // AL36: total setoran (AL+AM+AN) = 276.754.938
        $this->assertEquals($expectedSummary['AL36_total_disetor'], $avg['AL36_total_disetor']);

        // AP35: saldo belum disetor hari terakhir dari dataset tersimpan = -31613125.999999918
        $this->assertEqualsWithDelta($expectedSummary['AP35_total_uang_belum_disetor'], $tot['AP_total_uang_belum_disetor'], 0.000001);

        // AV35: total omset + losses = 23217.367
        $this->assertEqualsWithDelta($expectedSummary['AV35_omset_plus_losses'], $tot['AV_omset_plus_losses'], 0.000001);

        // F36: rata-rata liter aktual per hari > 0 dari dataset tersimpan = 786.164333333333
        $this->assertEqualsWithDelta($expectedSummary['F36_avg_liter'], $avg['F36_avg_liter'], 0.000001);

        // Cross-check: AJ36 == AP35
        $this->assertEqualsWithDelta($expectedSummary['AJ36_selisih_disetor'], $avg['AJ36_selisih_disetor'], 0.000001);
        $this->assertTrue($summary['cross_checks']['aj36_equals_ap35']);

        // Cross-check: Pembelian BBM == Terima BBM
        $this->assertTrue($summary['cross_checks']['pembelian_equals_terima']);

        // Penggajian Operator (Desimal murni presisi tinggi dari dataset tersimpan):
        // Dendi: 4.209.329,60
        // Wawan: 434.143,80
        $opSalaries = collect($summary['operator_salaries'])->keyBy('operator_name');
        $this->assertEqualsWithDelta(21046.648, $opSalaries['Dendi Supriyatno']['total_av'], 0.01);
        $this->assertEqualsWithDelta($expectedSummary['dendi_gaji'], $opSalaries['Dendi Supriyatno']['gaji'], 0.000001);

        $this->assertEqualsWithDelta(2170.719, $opSalaries['Wawan']['total_av'], 0.01);
        $this->assertEqualsWithDelta($expectedSummary['wawan_gaji'], $opSalaries['Wawan']['gaji'], 0.000001);

        // Total Gaji = 4.643.473,40
        $totalOpGaji = $summary['operator_salaries_summary']['total_gaji'];
        $this->assertEqualsWithDelta($expectedSummary['total_gaji'], $totalOpGaji, 0.000001);
    }

    /**
     * UJI Fitur Pemantauan Superadmin: Akses Halaman, Polling JSON, dan Ekspor Excel
     */
    public function test_monitoring_superadmin_features(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Akses Halaman Pantauan
        $response = $this->get(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
            'month'   => 3,
            'year'    => 2026,
        ]));
        $response->assertStatus(200);
        $response->assertSee('Pantauan Laporan Harian Super Admin');
        $response->assertSee('REKAP 4P.53143');
        $response->assertSee('Dendi Supriyatno');

        // 2. Endpoint Polling Data JSON
        $jsonResp = $this->getJson(route('monitoring.daily-reports.data', [
            'shop_id' => $this->shop->id,
            'month'   => 3,
            'year'    => 2026,
        ]));
        $jsonResp->assertStatus(200);
        $jsonResp->assertJsonPath('has_changes', true);
        $this->assertCount(31, $jsonResp->json('recap.rows'));

        // 3. Endpoint Ringkasan Semua Gerai
        $overviewResp = $this->getJson(route('monitoring.daily-reports.overview'));
        $overviewResp->assertStatus(200);
        $overviewData = $overviewResp->json();
        $this->assertNotEmpty($overviewData);
        $firstShop = collect($overviewData)->firstWhere('shop_id', $this->shop->id);
        $this->assertNotNull($firstShop);
        $this->assertSame('Pertashop Kemutug Lor', $firstShop['shop_nama']);

        // 4. Ekspor Berkas Excel
        $excelResp = $this->get(route('monitoring.daily-reports.export-excel', [
            'shop_id' => $this->shop->id,
            'month'   => 3,
            'year'    => 2026,
        ]));
        $excelResp->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $excelResp->headers->get('content-type'));
        $this->assertStringContainsString('REKAP_4P53143_03_2026.xlsx', $excelResp->headers->get('content-disposition'));
    }

    /**
     * UJI Butir 1: Kolom X = M (Bukan M + K) saat Kirim Gerai / BBM Keluar Lain (J) > 0
     * Disertai verifikasi rumus manual Excel untuk AJ, AO, dan AP
     */
    public function test_kolom_x_equals_m_with_kirim_bbm_keluar_lain_greater_than_zero(): void
    {
        // 1. Buat outlet baru untuk pengujian Kirim
        $shopKirim = Shop::create([
            'nama'            => 'Pertashop Uji Kirim',
            'kode'            => '4P.99999',
            'corporation_id'  => $this->shop->corporation_id,
            'alamat'          => 'Jl. Uji Kirim',
            'skala'           => 21.49,
            'totalisator_awal'=> 100000.000,
            'stik_awal'       => 100.0,
            'modal_awal'      => 100000000,
            'is_active'       => true,
        ]);

        $userOp = User::create([
            'name'     => 'Operator Kirim',
            'email'    => 'op.kirim@pertashop.test',
            'role'     => 'operator',
            'password' => bcrypt('secret'),
        ]);
        $op = Operator::create([
            'user_id' => $userOp->id,
            'shop_id' => $shopKirim->id,
            'nik'     => 'OP-KIRIM',
        ]);

        $priceKirim = Price::create([
            'shop_id'      => $shopKirim->id,
            'harga_jual'   => 12200,
            'harga_beli'   => 11276.08,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        // Simpan saldo awal bulan di tabel shop_starting_balances
        \App\Models\ShopStartingBalance::create([
            'shop_id'            => $shopKirim->id,
            'year'               => 2026,
            'month'              => 4,
            'totalisator_awal'   => 100000.000,
            'stok_awal'          => 2000.000,
            'uang_belum_disetor' => -1000000.00, // Saldo awal: minus 1 juta
        ]);

        // Buat 1 laporan harian pada tanggal 2026-04-01 dengan:
        // - Totalisator Awal = 100000.000, Totalisator Akhir = 101000.000 (F = 1000.000 L)
        // - Test Pump = 10.000 L (H = 10, I = 10 x 12200 = 122.000)
        // - Kirim Gerai (J) = 50.000 L (K = 50 x 12950 = 647.500)
        // - Penjualan Aktual (L) = F - H - J = 1000 - 10 - 50 = 940.000 L
        // - Penjualan Aktual Rp (M) = 940 x 12200 = 11.468.000
        // - Pengeluaran (AG) = Ongkos bongkar 10.000
        // - Setoran = BRI 10.000.000 + QRIS 500.000 = 10.500.000
        $reportKirim = DailyReport::create([
            'shop_id'             => $shopKirim->id,
            'operator_id'         => $op->id,
            'price_id'            => $priceKirim->id,
            'report_date'         => '2026-04-01',
            'totalisator_awal'    => 100000.000,
            'totalisator_akhir'   => 101000.000,
            'stik_awal'           => 100.0,
            'stik_akhir'          => 50.0,
            'stok_awal'           => 2000.000,
            'test_pump_volume'    => 10.000,
            'bbm_keluar_lain'     => 50.000, // J > 0 (Kirim Kemutug / Keluar Lain)
            'penerimaan_volume'   => 0,
            'disetorkan'          => 10500000,
            'setor_tunai'         => 10000000,
            'setor_qris'          => 500000,
            'setor_transfer'      => 0,
            'harga_jual_snapshot' => 12200,
            'harga_beli_snapshot' => 11276.08,
            'status_lifecycle'    => 'approved',
            'created_at'          => '2026-04-01 20:00:00',
        ]);

        Spending::create([
            'daily_report_id'      => $reportKirim->id,
            'spending_category_id' => 1,
            'shop_id'              => $shopKirim->id,
            'operator_id'          => $op->id,
            'jumlah'               => 10000,
            'keterangan'           => 'Ongkos bongkar uji',
            'created_at'           => '2026-04-01 12:00:00',
        ]);

        $recap = PertashopDailyRecapService::getMonthlyRecap($shopKirim->id, 4, 2026);
        $day1 = $recap['rows'][1];

        // 1. Kolom J & K: Kirim Gerai
        $this->assertEqualsWithDelta(50.000, $day1['J_bbm_keluar_lain_vol'], 0.000001);
        $this->assertEqualsWithDelta(50.000 * 12950, $day1['K_bbm_keluar_lain_rp'], 0.000001);

        // 2. Kolom L & M: Aktual Penjualan
        $this->assertEqualsWithDelta(940.000, $day1['L_penjualan_aktual_vol'], 0.000001);
        $this->assertEqualsWithDelta(11468000, $day1['M_penjualan_aktual_rp'], 0.000001);

        // 3. Kolom X: JUMLAH ACTUAL PENJUALAN WAJIB SAMA DENGAN M (X = M = 11.468.000), BUKAN (M + K = 12.115.500)
        $this->assertEqualsWithDelta(11468000, $day1['X_jumlah_actual_penjualan'], 0.000001, 'Kolom X harus persis sama dengan M');
        $this->assertNotEquals(11468000 + 647500, $day1['X_jumlah_actual_penjualan'], 'Kolom X TIDAK boleh M + K');

        // 4. Kolom AG: Total Pengeluaran = 10.000
        $this->assertEqualsWithDelta(10000, $day1['AG_total_pengeluaran'], 0.000001);

        // 5. Kolom AJ: Pendapatan - Pengeluaran = X - AG = 11.468.000 - 10.000 = 11.458.000
        $this->assertEqualsWithDelta(11458000, $day1['AJ_pendapatan_minus_pengeluaran'], 0.000001);

        // --- Verifikasi Butir 1: Q, T, U, V, AV, AW dengan Kirim (J) > 0 ---
        // Parameter:
        // N (Stok Awal) = 2000.000
        // P (Terima BBM) = 0
        // J (Kirim Kemutug) = 50.000
        // L (Penjualan Aktual) = 940.000
        // Rumus Excel Stok Teoritis: Q = N + P - J - L
        // Q = 2000 + 0 - 50 - 940 = 1010.000
        $expectedQ = 2000.000 + 0 - 50.000 - 940.000;
        $this->assertEqualsWithDelta($expectedQ, $day1['Q_stok_teoritis'], 0.000001, 'Q = N + P - J - L');
        $this->assertEqualsWithDelta(1010.000, $day1['Q_stok_teoritis'], 0.000001);

        // S (Stok Aktual Fisik Tangki) = stik 50 cm * 21.49 = 1074.500
        $expectedS = 50.0 * 21.49;
        $this->assertEqualsWithDelta($expectedS, $day1['S_stok_aktual_vol'], 0.000001);

        // T (Losses / Gain Liter) = S - Q = 1074.500 - 1010.000 = +64.500 (Gain)
        $expectedT = $expectedS - $expectedQ;
        $this->assertEqualsWithDelta($expectedT, $day1['T_losses_gain_liter'], 0.000001, 'T = S - Q');
        $this->assertEqualsWithDelta(64.500, $day1['T_losses_gain_liter'], 0.000001);

        // U (Losses / Gain Rupiah) = T * harga_beli = 64.500 * 11276.08 = 727307.16
        $expectedU = $expectedT * 11276.08;
        $this->assertEqualsWithDelta($expectedU, $day1['U_losses_gain_rp'], 0.000001, 'U = T * harga_beli');

        // V (Status Losses/Gain) = T > 0 => "Gain"
        $this->assertSame('Gain', $day1['V_status_losses_gain']);

        // AV (Omset + Losses):
        // Rumus Excel: IF(V="Losses", L + T, L). Karena V="Gain", maka AV = L = 940.000 (Gain TIDAK menambah omset)
        $expectedAv = ($day1['V_status_losses_gain'] === 'Losses') ? (940.000 + $expectedT) : 940.000;
        $this->assertEqualsWithDelta(940.000, $day1['AV_omset_plus_losses'], 0.000001, 'AV = IF(V="Losses", L+T, L)');

        // AW (Komisi Operator):
        // Rumus Excel: AV * komisi_per_liter = 940.000 * 200 = 188.000
        $expectedAw = $expectedAv * 200;
        $this->assertEqualsWithDelta(188000, $day1['AW_gaji_harian'], 0.000001, 'AW = AV * komisi_per_liter');

        // 6. Kolom AO: Selisih Setoran = (AG + AL + AM + AN) - X = (10.000 + 10.000.000 + 500.000 + 0) - 11.468.000 = -958.000
        $this->assertEqualsWithDelta(-958000, $day1['AO_selisih_setoran'], 0.000001);

        // 7. Kolom AP: Total Belum Disetor = Saldo Awal (-1.000.000) + AO (-958.000) = -1.958.000
        $this->assertEqualsWithDelta(-1958000, $day1['AP_total_uang_belum_disetor'], 0.000001);
    }

    /**
     * UJI Butir 5: Middleware Peran pada /monitoring/daily-reports*
     * Bukti bahwa Guest, Operator, dan Investor DITOLAK (302/403) dan Super Admin DITERIMA (200)
     */
    public function test_monitoring_access_control_rejects_operator_and_investor(): void
    {
        // 1. Guest (Tanpa Login) -> Ditolak / Diarahkan ke login (302)
        $respGuest = $this->get(route('monitoring.daily-reports', ['shop_id' => $this->shop->id]));
        $respGuest->assertStatus(302);
        $respGuest->assertRedirect('login');

        // 2. User Operator -> Ditolak (403 Forbidden)
        $respOperator = $this->actingAs($this->operatorDendi->user)->get(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
        ]));
        $respOperator->assertStatus(403);

        // 3. User Investor -> Ditolak (403 Forbidden)
        $this->flushSession();
        $userInvestor = User::create([
            'name'     => 'Investor Harian',
            'email'    => 'investor.harian@pertashop.test',
            'role'     => 'investor',
            'password' => bcrypt('secret'),
        ]);
        \App\Models\Investor::create([
            'user_id' => $userInvestor->id,
            'nik'     => 'INV-HARIAN',
        ]);
        $respInvestor = $this->actingAs($userInvestor)->get(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
        ]));
        $respInvestor->assertStatus(403);

        // 4. Super Admin -> Diterima (200 OK)
        $this->flushSession();
        $respSuperAdmin = $this->actingAs($this->superAdmin)->get(route('monitoring.daily-reports', [
            'shop_id' => $this->shop->id,
        ]));
        $respSuperAdmin->assertStatus(200);
    }

    /**
     * UJI Butir 4: Saldo Awal Bulan (Bulan Pertama Manual via ShopStartingBalance, Bulan Ke-2 Otomatis Estafet)
     * Diuji untuk gerai selain Kemutug (Pertashop Kalitapen)
     */
    public function test_saldo_awal_bulan_pertama_manual_dan_bulan_berikutnya_otomatis_untuk_gerai_lain(): void
    {
        // 1. Buat gerai non-Kemutug (Kalitapen)
        $corp = Corporation::first();
        $shopKalitapen = Shop::create([
            'nama'            => 'Pertashop Kalitapen',
            'kode'            => '4P.53112',
            'corporation_id'  => $corp->id,
            'alamat'          => 'Kalitapen, Banyumas',
            'skala'           => 21.00,
            'totalisator_awal'=> 200000.000,
            'stik_awal'       => 80.0,
            'modal_awal'      => 60000000,
            'is_active'       => true,
        ]);

        $userOpKlt = User::create([
            'name'     => 'Operator Kalitapen',
            'email'    => 'op.kalitapen@pertashop.test',
            'role'     => 'operator',
            'password' => bcrypt('secret'),
        ]);
        $opKalitapen = Operator::create([
            'user_id' => $userOpKlt->id,
            'shop_id' => $shopKalitapen->id,
            'nik'     => 'OP-KLT',
        ]);

        $priceKalitapen = Price::create([
            'shop_id'      => $shopKalitapen->id,
            'harga_jual'   => 12200,
            'harga_beli'   => 11276.08,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        // Input saldo awal manual bulan pertama (Januari 2026) via tabel shop_starting_balances
        \App\Models\ShopStartingBalance::create([
            'shop_id'            => $shopKalitapen->id,
            'year'               => 2026,
            'month'              => 1,
            'totalisator_awal'   => 205000.000,
            'stok_awal'          => 1800.000,
            'uang_belum_disetor' => -5000000.00,
            'keterangan'         => 'Saldo awal pembukaan operasional Januari 2026',
        ]);

        // Ambil rekap Januari 2026 (Bulan Pertama)
        $recapJan = PertashopDailyRecapService::getMonthlyRecap($shopKalitapen->id, 1, 2026);
        $this->assertEqualsWithDelta(205000.000, $recapJan['saldo_awal']['totalisator_awal_hari_pertama'], 0.000001);
        $this->assertEqualsWithDelta(1800.000, $recapJan['saldo_awal']['stok_awal_hari_pertama'], 0.000001);
        $this->assertEqualsWithDelta(-5000000.00, $recapJan['saldo_awal']['uang_belum_disetor_periode_lalu'], 0.000001);

        // Buat laporan penutupan di akhir Januari (31 Januari 2026)
        $closingReportJan = DailyReport::create([
            'shop_id'                 => $shopKalitapen->id,
            'operator_id'             => $opKalitapen->id,
            'price_id'                => $priceKalitapen->id,
            'report_date'             => '2026-01-31',
            'totalisator_awal'        => 215000.000,
            'totalisator_akhir'       => 216200.000, // Totalisator akhir bulan = 216200.000
            'stik_awal'               => 70.0,
            'stik_akhir'              => 65.0,
            'stok_awal'               => 1500.000,
            'stok_akhir_aktual'       => 1365.000, // Stok akhir aktual = 1365.000
            'disetorkan'              => 0,
            'running_balance_setoran' => -3250000.00, // Saldo berjalan belum disetor akhir Januari = -3.250.000
            'status_lifecycle'        => 'approved',
            'created_at'              => '2026-01-31 20:00:00',
        ]);

        // 2. Ambil rekap Februari 2026 (Bulan Berikutnya) -> Wajib OTOMATIS estafet dari 31 Januari
        $recapFeb = PertashopDailyRecapService::getMonthlyRecap($shopKalitapen->id, 2, 2026);

        $this->assertEqualsWithDelta(216200.000, $recapFeb['saldo_awal']['totalisator_awal_hari_pertama'], 0.000001, 'Totalisator awal Feb wajib otomatis dari akhir Jan');
        $this->assertEqualsWithDelta(1365.000, $recapFeb['saldo_awal']['stok_awal_hari_pertama'], 0.000001, 'Stok awal Feb wajib otomatis dari stok akhir Jan');
        $this->assertEqualsWithDelta(-3250000.00, $recapFeb['saldo_awal']['uang_belum_disetor_periode_lalu'], 0.000001, 'Uang belum disetor Feb wajib otomatis dari running balance akhir Jan');

        // 3. Test Peringatan jika input manual di shop_starting_balances berbeda dari hasil estafet bulan sebelumnya (Butir 9)
        \App\Models\ShopStartingBalance::create([
            'shop_id'            => $shopKalitapen->id,
            'year'               => 2026,
            'month'              => 2,
            'totalisator_awal'   => 216300.000, // Berbeda dari 216200.000
            'stok_awal'          => 1400.000,   // Berbeda dari 1365.000
            'uang_belum_disetor' => -4000000.00,// Berbeda dari -3250000.00
            'keterangan'         => 'Koreksi manual saldo awal Februari',
        ]);

        $recapFebOverride = PertashopDailyRecapService::getMonthlyRecap($shopKalitapen->id, 2, 2026);
        $this->assertTrue($recapFebOverride['saldo_awal']['is_manual_override'] ?? false);
        $this->assertNotNull($recapFebOverride['saldo_awal']['warning']);
        $this->assertStringContainsString('PERINGATAN: Input manual saldo awal', $recapFebOverride['saldo_awal']['warning']);
    }

    /**
     * UJI Butir 8: Verifikasi Isi Berkas Ekspor Excel Maret 2026 Sesuai Format Acuan Excel
     */
    public function test_exported_excel_file_matches_dataset_and_template(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('monitoring.daily-reports.export-excel', [
            'shop_id' => $this->shop->id,
            'month'   => 3,
            'year'    => 2026,
        ]));

        $response->assertStatus(200);

        // Simpan output berkas sementara untuk dibaca oleh PhpSpreadsheet
        $tempPath = storage_path('framework/testing/test_export_kemutug_maret_2026.xlsx');
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }
        file_put_contents($tempPath, $response->streamedContent());

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Cek Judul Sheet / Sel Header
        $titleA1 = (string) $sheet->getCell('A1')->getValue();
        $this->assertStringContainsString('REKAP', strtoupper($titleA1));

        // 2. Cek Sampel Baris Harian Tanggal 1 (Baris 6 pada template ekspor: Header ada di baris 4 & 5)
        // Kolom D (Totalisator Awal Hari 1) = 498129.8
        $cellD6 = floatval($sheet->getCell('D6')->getValue());
        $this->assertEqualsWithDelta(498129.8, $cellD6, 0.000001);

        // Kolom E (Totalisator Akhir Hari 1) = 498926.81
        $cellE6 = floatval($sheet->getCell('E6')->getValue());
        $this->assertEqualsWithDelta(498926.81, $cellE6, 0.000001);

        // Kolom F (Teoritis Vol Hari 1) = 797.01
        $cellF6 = floatval($sheet->getCell('F6')->getValue());
        $this->assertEqualsWithDelta(797.01, $cellF6, 0.00001);

        // Kolom G (Teoritis Rp Hari 1) = 9723522
        $cellG6 = floatval($sheet->getCell('G6')->getValue());
        $this->assertEqualsWithDelta(9723522, $cellG6, 0.00001);

        // Kolom L (Aktual Penjualan Vol Hari 1) = 797.01
        $cellL6 = floatval($sheet->getCell('L6')->getValue());
        $this->assertEqualsWithDelta(797.01, $cellL6, 0.00001);

        // Kolom X (Jumlah Actual Penjualan) = 9723522
        $cellX6 = floatval($sheet->getCell('X6')->getValue());
        $this->assertEqualsWithDelta(9723522, $cellX6, 0.00001);

        // Kolom Tgl 31 (Baris 6 + 30 = Baris 36)
        $cellD36 = floatval($sheet->getCell('D36')->getValue());
        $this->assertEqualsWithDelta(520733.95, $cellD36, 0.000001);

        // Kolom AP Tgl 31 (Total Uang Belum Disetor Hari 31) = -31613125.999999918 (-31613126)
        $cellAP36 = floatval($sheet->getCell('AP36')->getValue());
        $this->assertEqualsWithDelta(-31613125.999999918, $cellAP36, 0.0001);

        // Baris 37: Baris Total (Total Penjualan Aktual Vol L) = 23584.93
        $cellL37 = floatval($sheet->getCell('L37')->getValue());
        $this->assertEqualsWithDelta(23584.93, $cellL37, 0.0001);

        // Hapus file sementara
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    /**
     * UJI Butir 5: Verifikasi Pencatatan Audit Log (Old Values, New Values, User, Waktu)
     * Mencakup DailyReport (operator input), Spending, DailyReportDeposit, dan Incoming
     */
    public function test_audit_logs_record_old_and_new_values_for_operator_input_spendings_deposits_and_incoming(): void
    {
        $this->actingAs($this->superAdmin);

        $report = DailyReport::where('shop_id', $this->shop->id)->whereDate('report_date', '2026-03-01')->first();
        $this->assertNotNull($report);

        // 1. Audit Log Perubahan DailyReport (Input Operator)
        $oldDisetorkan = $report->disetorkan;
        $report->update([
            'disetorkan' => $oldDisetorkan + 500000,
        ]);

        $logReport = \App\Models\AuditLog::where('model_type', 'DailyReport')
            ->where('model_id', $report->id)
            ->where('action', 'UPDATE_OPERATOR_INPUT')
            ->latest()
            ->first();

        $this->assertNotNull($logReport, 'AuditLog UPDATE_OPERATOR_INPUT harus tercatat');
        $this->assertEquals($this->superAdmin->id, $logReport->user_id);
        $this->assertArrayHasKey('disetorkan', $logReport->new_values);
        $this->assertEquals($oldDisetorkan + 500000, $logReport->new_values['disetorkan']);
        $this->assertNotNull($logReport->created_at);

        // 2. Audit Log Perubahan Spending
        $spending = Spending::where('daily_report_id', $report->id)->first();
        if (!$spending) {
            $spending = Spending::create([
                'daily_report_id'      => $report->id,
                'spending_category_id' => 1,
                'shop_id'              => $this->shop->id,
                'operator_id'          => $this->operatorDendi->id,
                'jumlah'               => 15000,
                'keterangan'           => 'Uji spending log',
            ]);
        }
        $spending->update(['jumlah' => 25000]);

        $logSpending = \App\Models\AuditLog::where('model_type', 'Spending')
            ->where('model_id', $spending->id)
            ->where('action', 'UPDATE_SPENDING')
            ->latest()
            ->first();

        $this->assertNotNull($logSpending, 'AuditLog UPDATE_SPENDING harus tercatat');
        $this->assertEquals($this->superAdmin->id, $logSpending->user_id);
        $this->assertEquals(25000, $logSpending->new_values['jumlah']);

        // 3. Audit Log Perubahan Incoming / Purchase
        $incoming = Incoming::where('daily_report_id', $report->id)->first();
        if ($incoming) {
            $incoming->update(['volume' => 2500]);
            $logIncoming = \App\Models\AuditLog::where('model_type', 'Incoming')
                ->where('model_id', $incoming->id)
                ->where('action', 'UPDATE_INCOMING_PURCHASE')
                ->latest()
                ->first();
            $this->assertNotNull($logIncoming, 'AuditLog UPDATE_INCOMING_PURCHASE harus tercatat');
            $this->assertEquals(2500, $logIncoming->new_values['volume']);
        }

        // 4. Audit Log Perubahan DailyReportDeposit
        $depCat = \App\Models\DepositCategory::firstOrCreate(['nama_kategori' => 'BRI']);
        $deposit = \App\Models\DailyReportDeposit::create([
            'daily_report_id' => $report->id,
            'deposit_category_id' => $depCat->id,
            'jumlah' => 1000000,
        ]);
        $deposit->update(['jumlah' => 1500000]);
        $logDeposit = \App\Models\AuditLog::where('model_type', 'DailyReportDeposit')
            ->where('model_id', $deposit->id)
            ->where('action', 'UPDATE_DEPOSIT')
            ->latest()
            ->first();
        $this->assertNotNull($logDeposit, 'AuditLog UPDATE_DEPOSIT harus tercatat');
        $this->assertEquals(1500000, $logDeposit->new_values['jumlah']);
    }
}

```



# =============================================================================
# TEST: tests/Feature/KemutugMarch2026VerificationTest.php
# File: tests/Feature/KemutugMarch2026VerificationTest.php
# Test Uji Verifikasi Awal Dataset Kemutug Maret 2026
# =============================================================================

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Shop;
use App\Models\User;
use App\Models\Price;
use App\Models\Operator;
use App\Models\Corporation;
use App\Models\DailyReport;
use App\Models\Spending;
use App\Models\SpendingCategory;
use App\Models\TestPump;
use App\Models\Incoming;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\PayrollSystem;
use App\Models\BusinessRule;
use App\Models\BusinessRuleVersion;
use App\Services\DailyReportCalculationService;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

class KemutugMarch2026VerificationTest extends TestCase
{
    protected Shop $shop;
    protected User $user;
    protected Operator $operatorDendi;
    protected Operator $operatorWawan;
    protected Price $price;
    protected PayrollSystem $payrollSystem;
    protected Purchase $purchase;
    protected array $dataset;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Bersihkan database untuk isolasi test
        DailyReport::clearInternalCache();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        DB::table('spendings')->delete();
        DB::table('incomings')->delete();
        DB::table('test_pumps')->delete();
        DB::table('daily_reports')->delete();
        DB::table('payroll_daily_splits')->delete();
        DB::table('payroll_details')->delete();
        DB::table('payroll_periods')->delete();
        DB::table('payroll_systems')->delete();
        DB::table('purchases')->delete();
        DB::table('suppliers')->delete();
        DB::table('prices')->delete();
        DB::table('operators')->delete();
        DB::table('users')->delete();
        DB::table('shops')->delete();
        DB::table('corporations')->delete();
        DB::table('spending_categories')->delete();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // 2. Seed business rules
        $this->seed(\Database\Seeders\BusinessRuleSeeder::class);

        // 3. User & Corporation
        $this->user = User::create([
            'name'     => 'Admin Test',
            'email'    => 'admin@test.pertashop.com',
            'password' => bcrypt('secret'),
        ]);

        $corp = Corporation::create([
            'nama'   => 'KPRI KOKARNABA',
            'alamat' => 'Baturraden',
        ]);

        // 4. Shop Kemutug
        $this->shop = Shop::create([
            'nama'                     => 'Pertashop Kemutug Lor',
            'kode'                     => '4P.53143',
            'corporation_id'           => $corp->id,
            'alamat'                   => 'Kemutug Lor, Baturraden',
            'skala'                    => 21.49, // Koefisien tangki cm -> liter
            'totalisator_awal'         => 498129.800,
            'stik_awal'                => 70.4,
            'modal_awal'               => 470490000,
            'uang_belum_disetor_awal'  => -26020818,
        ]);

        $supplier = Supplier::create([
            'nama'   => 'PT Pertamina Patra Niaga',
            'alamat' => 'Cilacap',
        ]);

        $this->purchase = Purchase::create([
            'no_so'       => 'SO-2026-001',
            'shop_id'     => $this->shop->id,
            'supplier_id' => $supplier->id,
            'volume'      => 30000,
            'total_bayar' => 30000 * 11276.08,
        ]);

        // 5. Operators
        $userDendi = User::create([
            'name'     => 'Dendi Supriyatno',
            'email'    => 'dendi@test.com',
            'password' => bcrypt('secret'),
        ]);
        $this->operatorDendi = Operator::create([
            'user_id' => $userDendi->id,
            'shop_id' => $this->shop->id,
            'nik'     => 'OP-01',
        ]);

        $userWawan = User::create([
            'name'     => 'Wawan',
            'email'    => 'wawan@test.com',
            'password' => bcrypt('secret'),
        ]);
        $this->operatorWawan = Operator::create([
            'user_id' => $userWawan->id,
            'shop_id' => $this->shop->id,
            'nik'     => 'OP-02',
        ]);

        // 6. Prices (effective_at snapshot per tanggal efektif)
        $this->price = Price::create([
            'shop_id'      => $this->shop->id,
            'harga_jual'   => 12200,
            'harga_beli'   => 11276.08,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        // 7. Spending Categories
        $cats = [
            1  => 'Ongkos Bongkar',
            2  => 'Biaya Transfer',
            3  => 'Fotocopy ATK',
            4  => 'Listrik',
            5  => 'Air Bersih',
            6  => 'Cashback',
            7  => 'Internet',
            99 => 'Lain-lain',
        ];
        foreach ($cats as $id => $nama) {
            SpendingCategory::create(['id' => $id, 'nama' => $nama]);
        }

        // 8. Payroll System
        $this->payrollSystem = PayrollSystem::create([
            'nama_sistem'           => 'Skema Komisi Kemutug',
            'shop_id'               => $this->shop->id,
            'rate_per_liter'        => 200,
            'komisi_per_liter'      => 200,
            'tipe_skema'            => 'komisi_murni',
            'ada_rate_per_liter'    => true,
            'ada_gaji_pokok'        => false,
            'nominal_gaji_pokok'    => 0,
            'perlakuan_losses_gain' => 'losses_only',
            'metode_split'          => 'per_hari_penuh',
        ]);

        // 9. Load dataset
        $datasetPath = base_path('tests/kemutug_maret_2026_dataset.json');
        $this->dataset = json_decode(file_get_contents($datasetPath), true);

        // 10. Seed daily reports
        $this->seedDatasetReports();
    }

    protected function seedDatasetReports(): void
    {
        foreach ($this->dataset['daily_reports'] as $row) {
            $tgl = str_pad($row['tgl'], 2, '0', STR_PAD_LEFT);
            $dateStr = "2026-03-{$tgl}";
            $opId = ($row['operator'] === 'Wawan') ? $this->operatorWawan->id : $this->operatorDendi->id;

            $setoranTotal = floatval(($row['setoran_bri'] ?? 0) + ($row['setoran_qris'] ?? 0) + ($row['setoran_transfer'] ?? 0));

            $report = DailyReport::create([
                'shop_id'             => $this->shop->id,
                'operator_id'         => $opId,
                'price_id'            => $this->price->id,
                'totalisator_awal'    => $row['totalisator_awal'],
                'totalisator_akhir'   => $row['totalisator_akhir'],
                'stik_akhir'          => $row['stok_aktual_cm'],
                'stok_awal'           => $row['stok_awal'],
                'test_pump_volume'    => $row['test_pump_vol'],
                'penerimaan_volume'   => $row['terima_bbm'],
                'bbm_keluar_lain'     => $row['kirim_kemutug_vol'] ?? 0,
                'disetorkan'          => $setoranTotal,
                'diverifikasi'        => true,
                'setor_tunai'         => floatval($row['setoran_bri'] ?? 0),
                'setor_qris'          => floatval($row['setoran_qris'] ?? 0),
                'setor_transfer'      => floatval($row['setoran_transfer'] ?? 0),
                'harga_jual_snapshot' => 12200,
                'harga_beli_snapshot' => 11276.08,
                'report_date'         => $dateStr,
                'created_at'          => "{$dateStr} 20:00:00",
                'updated_at'          => "{$dateStr} 20:00:00",
            ]);

            // Test pump
            if ($row['test_pump_vol'] > 0) {
                TestPump::create([
                    'daily_report_id'   => $report->id,
                    'shop_id'           => $this->shop->id,
                    'operator_id'       => $opId,
                    'totalisator_awal'  => $row['totalisator_awal'],
                    'totalisator_akhir' => $row['totalisator_awal'] + $row['test_pump_vol'],
                    'volume'            => $row['test_pump_vol'],
                    'created_at'        => "{$dateStr} 10:00:00",
                ]);
            }

            // Incoming
            if ($row['terima_bbm'] > 0) {
                Incoming::create([
                    'daily_report_id' => $report->id,
                    'purchase_id'     => $this->purchase->id,
                    'shop_id'         => $this->shop->id,
                    'operator_id'     => $opId,
                    'volume'          => $row['terima_bbm'],
                    'sopir'           => 'Supir Tangki',
                    'no_polisi'       => 'R-1234-AA',
                    'stik_awal'       => 0,
                    'stik_akhir'      => 0,
                    'created_at'      => "{$dateStr} 12:00:00",
                ]);
            }

            // Spendings
            $spendingMappings = [
                1 => $row['ongkos_bongkar'] ?? 0,
                2 => $row['biaya_transfer'] ?? 0,
                3 => $row['fotocopy_atk'] ?? 0,
                4 => $row['listrik'] ?? 0,
                5 => $row['air_bersih'] ?? 0,
                6 => $row['cashback'] ?? 0,
                7 => $row['internet'] ?? 0,
                99 => $row['lain_lain'] ?? 0,
            ];

            foreach ($spendingMappings as $catId => $amt) {
                if ($amt > 0) {
                    Spending::create([
                        'daily_report_id'      => $report->id,
                        'spending_category_id' => $catId,
                        'shop_id'              => $this->shop->id,
                        'operator_id'          => $opId,
                        'jumlah'               => $amt,
                        'keterangan'           => ($catId == 99) ? ($row['keterangan_lain'] ?: 'Lain-lain') : '-',
                        'created_at'           => "{$dateStr} 18:00:00",
                    ]);
                }
            }
        }

        // Lakukan rekalkulasi berantai awal dengan saldo awal Maret 2026: -26020818
        DailyReportCalculationService::recalculateCascade(
            $this->shop->id,
            Carbon::parse('2026-03-01'),
            -26020818,
            498129.8,
            1512.896
        );
    }

    /**
     * Uji 1: Verifikasi kecocokan seluruh angka Maret 2026 Kemutug dengan Bagian 6 Spesifikasi
     */
    public function test_maret_2026_kemutug_daily_and_monthly_totals_match_specification(): void
    {
        $reports = DailyReport::where('shop_id', $this->shop->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertCount(31, $reports);

        // --- Verifikasi Harian Spesifik Sesuai Bagian 6 ---

        // Tgl 1: totalisator_awal 498129.8, akhir 498926.81, stok aktual 125.2 cm
        // -> penjualan_aktual_volume 797.01 (Rp9.723.522)
        $day1 = $reports->firstWhere('report_date', Carbon::parse('2026-03-01'));
        $this->assertEqualsWithDelta(498129.800, $day1->totalisator_awal, 0.001);
        $this->assertEqualsWithDelta(498926.810, $day1->totalisator_akhir, 0.001);
        $this->assertEqualsWithDelta(797.010, $day1->volume_penjualan_aktual, 0.001);
        $this->assertEquals(9723522, $day1->rupiah_penjualan_aktual);
        $this->assertEqualsWithDelta(2690.548, $day1->stok_akhir_aktual, 0.001);

        // Tgl 2: totalisator_awal 498926.81, akhir 500334.02
        // -> penjualan_aktual_volume 1407.21 (Rp17.167.962)
        $day2 = $reports->firstWhere('report_date', Carbon::parse('2026-03-02'));
        $this->assertEqualsWithDelta(498926.810, $day2->totalisator_awal, 0.001);
        $this->assertEqualsWithDelta(500334.020, $day2->totalisator_akhir, 0.001);
        $this->assertEqualsWithDelta(1407.210, $day2->volume_penjualan_aktual, 0.001);
        $this->assertEquals(17167962, $day2->rupiah_penjualan_aktual);

        // Tgl 7: ada test_pump 10 liter (Rp122.000)
        $day7 = $reports->firstWhere('report_date', Carbon::parse('2026-03-07'));
        $this->assertEqualsWithDelta(10.0, $day7->test_pump, 0.001);
        $this->assertEqualsWithDelta(771.640, $day7->volume_penjualan_aktual, 0.001);
        $this->assertEquals(9414008, $day7->rupiah_penjualan_aktual);

        // --- Verifikasi Akumulasi Bulanan Sesuai Bagian 6 Spesifikasi ---
        $totalVolAktual       = $reports->sum('volume_penjualan_aktual');
        $totalRpAktual        = $reports->sum('rupiah_penjualan_aktual');
        $totalLossesGainLiter = $reports->sum('losses_gain');
        $totalLossesGainRp    = $reports->sum('losses_gain_rupiah');
        $totalPengeluaran     = $reports->sum('pengeluaran');
        $totalSetoran         = $reports->sum('disetorkan');
        $saldoAkhirBelumSetor = $reports->last()->running_balance_setoran;
        $totalOmsetPlusLosses = $reports->sum('omset_plus_losses');

        // Target Spesifikasi Bagian 6:
        // 1. penjualan_aktual_volume total = 23584.93 liter
        $this->assertEqualsWithDelta(23584.930, $totalVolAktual, 0.01, 'Total Volume Penjualan Aktual');

        // 2. penjualan_aktual_rupiah total = 287.736.146
        $this->assertEqualsWithDelta(287736146, $totalRpAktual, 1.0, 'Total Rupiah Penjualan Aktual');

        // 3. losses_gain_liter total (net) = -355.613
        $this->assertEqualsWithDelta(-355.613, $totalLossesGainLiter, 0.01, 'Total Net Losses/Gain Liter');

        // 4. losses_gain_rupiah total (net) = -4.009.921
        $this->assertEqualsWithDelta(-4009921, $totalLossesGainRp, 2.0, 'Total Net Losses/Gain Rupiah');

        // 5. total_pengeluaran = 5.388.900
        $this->assertEquals(5388900, $totalPengeluaran, 'Total Pengeluaran');

        // 6. total setoran (BRI+QRIS+Transfer) = 276.754.938
        $this->assertEquals(276754938, $totalSetoran, 'Total Setoran');

        // 7. total_uang_belum_disetor akhir = -31.613.126
        $this->assertEquals(-31613126, $saldoAkhirBelumSetor, 'Saldo Akhir Belum Disetor');

        // 8. omset_plus_losses total = 23217.367 liter
        $this->assertEqualsWithDelta(23217.367, $totalOmsetPlusLosses, 0.01, 'Total Omset Plus Losses');

        // --- Verifikasi Penggajian Operator via PayrollCalculationService ---
        $payrollService = app(PayrollCalculationService::class);
        $payrollPeriod = $payrollService->generate($this->shop->id, 3, 2026, $this->user->id);

        $this->assertEqualsWithDelta(23217.367, $payrollPeriod->total_penjualan_liter, 0.01);

        $detailDendi = $payrollPeriod->details->firstWhere('operator_id', $this->operatorDendi->id);
        $detailWawan = $payrollPeriod->details->firstWhere('operator_id', $this->operatorWawan->id);

        $this->assertNotNull($detailDendi);
        $this->assertNotNull($detailWawan);

        // Dendi: 21046.648 L x 200 = 4.209.330
        $this->assertEqualsWithDelta(21046.648, $detailDendi->liter_bagian, 0.01);
        $this->assertEquals(4209330, $detailDendi->gaji_variable);

        // Wawan: 2170.719 L x 200 = 434.144
        $this->assertEqualsWithDelta(2170.719, $detailWawan->liter_bagian, 0.01);
        $this->assertEquals(434144, $detailWawan->gaji_variable);

        // Total Gaji Operator = 4.209.330 + 434.144 = 4.643.474 (selisih 1 Rp akibat pembulatan per operator)
        $totalGaji = $payrollPeriod->details->sum('gaji_variable');
        $this->assertEqualsWithDelta(4643473, $totalGaji, 1.0, 'Total Gaji Operator');
    }

    /**
     * Uji 2: Rekalkulasi berantai (cascade recalculation) saat hari ke-10 diedit.
     * Mengubah data hari 10 wajib mengalirkan perubahan ke hari 11 hingga 31
     * untuk totalisator_awal, stok_awal, dan saldo belum disetor.
     */
    public function test_cascade_recalculation_when_day_10_is_edited(): void
    {
        $day10 = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-10')
            ->firstOrFail();

        $day11Before = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-11')
            ->firstOrFail();

        $day31Before = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-31')
            ->firstOrFail();

        $originalDay10Akhir = $day10->totalisator_akhir;
        $originalDay10StikAkhir = $day10->stik_akhir;
        $originalDay10RunningBalance = $day10->running_balance_setoran;
        $originalDay31RunningBalance = $day31Before->running_balance_setoran;

        // --- Skenario A: Edit Totalisator Akhir Hari 10 (+50 L) ---
        // Penjualan hari 10 bertambah 50 L (50 x 12.200 = Rp 610.000).
        // Selisih setoran hari 10 berkurang Rp 610.000.
        // Totalisator awal hari 11 harus ikut naik 50 L.
        $newTotalisatorAkhirDay10 = $originalDay10Akhir + 50.000;
        DB::table('daily_reports')->where('id', $day10->id)->update([
            'totalisator_akhir' => $newTotalisatorAkhirDay10,
        ]);

        // Jalankan rekalkulasi berantai dari hari 10
        DailyReportCalculationService::recalculateCascade(
            $this->shop->id,
            Carbon::parse('2026-03-10'),
            -26020818,
            498129.8,
            1512.896
        );

        $day10After = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-10')
            ->firstOrFail();

        $day11After = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-11')
            ->firstOrFail();

        // 1. Totalisator Awal hari 11 HARUS mengikuti Totalisator Akhir hari 10 yang baru
        $this->assertEqualsWithDelta(
            $newTotalisatorAkhirDay10,
            $day11After->totalisator_awal,
            0.001,
            'Totalisator awal Hari 11 harus ter-update mengikuti Hari 10'
        );

        // 2. Running balance Hari 10 berkurang tepat Rp 610.000 (kurang setor bertambah)
        $this->assertEquals(
            $originalDay10RunningBalance - 610000,
            $day10After->running_balance_setoran,
            'Running balance hari 10 harus ter-update mengikuti perubahan totalisator'
        );

        // Kembalikan totalisator hari 10 ke semula
        DB::table('daily_reports')->where('id', $day10->id)->update([
            'totalisator_akhir' => $originalDay10Akhir,
        ]);

        // --- Skenario B: Edit Pengeluaran Hari 10 (Tambah biaya ATK Rp 100.000) ---
        // Pengeluaran bertambah Rp 100.000 -> Pendapatan kas berkurang Rp 100.000
        // -> Selisih setoran naik Rp 100.000 -> Running balance hari 10 s.d. 31 naik Rp 100.000
        Spending::create([
            'daily_report_id'      => $day10->id,
            'spending_category_id' => 3, // ATK
            'shop_id'              => $this->shop->id,
            'operator_id'          => $this->operatorDendi->id,
            'jumlah'               => 100000,
            'keterangan'           => 'Tambahan ATK Kasbon',
            'created_at'           => '2026-03-10 18:00:00',
        ]);

        DailyReportCalculationService::recalculateCascade(
            $this->shop->id,
            Carbon::parse('2026-03-10'),
            -26020818,
            498129.8,
            1512.896
        );

        $day10AfterPengeluaran = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-10')
            ->firstOrFail();

        $day11AfterPengeluaran = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-11')
            ->firstOrFail();

        $day31AfterPengeluaran = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-31')
            ->firstOrFail();

        $this->assertEquals(
            $originalDay10RunningBalance + 100000,
            $day10AfterPengeluaran->running_balance_setoran,
            'Running balance hari 10 harus berantai ter-update (+100.000)'
        );

        $this->assertEquals(
            $originalDay31RunningBalance + 100000,
            $day31AfterPengeluaran->running_balance_setoran,
            'Running balance akhir bulan (hari 31) harus berantai ter-update (+100.000)'
        );

        // --- Skenario C: Edit Stok Aktual Akhir Hari 10 (+5 cm) ---
        // Naikkan stik akhir dari nilai lama sebesar 5 cm (5 cm * 21.49 = 107.45 Liter)
        $newStikAkhirDay10 = $originalDay10StikAkhir + 5.0;
        DB::table('daily_reports')->where('id', $day10->id)->update([
            'stik_akhir' => $newStikAkhirDay10,
        ]);

        DailyReportCalculationService::recalculateCascade(
            $this->shop->id,
            Carbon::parse('2026-03-10'),
            -26020818,
            498129.8,
            1512.896
        );

        $day11AfterStok = DailyReport::where('shop_id', $this->shop->id)
            ->whereDate('report_date', '2026-03-11')
            ->firstOrFail();

        $expectedStokAwalDay11 = round($newStikAkhirDay10 * 21.49, 3);
        $this->assertEqualsWithDelta(
            $expectedStokAwalDay11,
            $day11AfterStok->stok_awal,
            0.001,
            'Stok awal Hari 11 harus ter-update mengikuti Stok Aktual Akhir Hari 10'
        );
    }

    /**
     * Uji 3: Aturan "losses_only" merupakan konfigurasi fleksibel per gerai lewat business_rules
     */
    public function test_business_rule_perlakuan_losses_gain_per_gerai(): void
    {
        // 1. Secara default gerai Kemutug memakai losses_only:
        // Losses (-10 L) mengurangi volume, Gain (+10 L) diabaikan
        $vol1 = $this->payrollSystem->hitungVolumeDihitung(100.0, -10.0);
        $this->assertEqualsWithDelta(90.0, $vol1, 0.001);

        $vol2 = $this->payrollSystem->hitungVolumeDihitung(100.0, 10.0);
        $this->assertEqualsWithDelta(100.0, $vol2, 0.001);

        // 2. Ubah konfigurasi via PayrollSystem outlet menjadi 'losses_dan_gain'
        $this->payrollSystem->update(['perlakuan_losses_gain' => 'losses_dan_gain']);
        $vol3 = $this->payrollSystem->hitungVolumeDihitung(100.0, 10.0);
        $this->assertEqualsWithDelta(110.0, $vol3, 0.001);

        // 3. Ubah konfigurasi via BusinessRule terpusat dengan mapping per-gerai
        $rule = BusinessRule::where('code', 'PERLAKUAN_LOSSES_GAIN')->first();
        if ($rule) {
            BusinessRuleVersion::create([
                'business_rule_id' => $rule->id,
                'version_code'     => 'BR-LOSSGAIN-v2.0',
                'value_json'       => [
                    (string) $this->shop->id => 'abaikan_losses_gain',
                    'default'                => 'losses_only',
                ],
                'effective_from'   => '2026-01-01 00:00:00',
                'effective_until'  => null,
                'is_active'        => true,
                'created_by'       => $this->user->id,
                'change_reason'    => 'Uji override aturan bisnis per gerai',
            ]);

            // Pada abaikan_losses_gain, losses (-10) maupun gain (+10) tidak berpengaruh ke komisi
            $vol4 = $this->payrollSystem->hitungVolumeDihitung(100.0, -10.0);
            $this->assertEqualsWithDelta(100.0, $vol4, 0.001);

            $vol5 = $this->payrollSystem->hitungVolumeDihitung(100.0, 10.0);
            $this->assertEqualsWithDelta(100.0, $vol5, 0.001);
        }
    }
}
```



# =============================================================================
# TEST: tests/Feature/EmployeeLoanPayrollIntegrationTest.php
# File: tests/Feature/EmployeeLoanPayrollIntegrationTest.php
# Test Uji Integrasi Potongan Kasbon, Payroll, dan Idempotensi Multi-Bulan
# =============================================================================

```php
<?php

namespace Tests\Feature;

use App\Models\Corporation;
use App\Models\DailyReport;
use App\Models\EmployeeLoan;
use App\Models\Operator;
use App\Models\PayrollLoanDeduction;
use App\Models\PayrollPeriod;
use App\Models\PayrollSystem;
use App\Models\Price;
use App\Models\ShiftSchedule;
use App\Models\Shop;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeLoanPayrollIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Corporation $corp;
    protected Shop $shop;
    protected Operator $operator;
    protected User $opUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Super Admin Loan Test',
            'email'    => 'admin.loan.' . uniqid() . '@pertashop.test',
            'role'     => 'super-admin',
            'password' => Hash::make('password123'),
        ]);

        $this->corp = Corporation::create([
            'nama'   => 'PT Kasbon Test Corp',
            'alamat' => 'Purwokerto',
        ]);

        $this->shop = Shop::create([
            'corporation_id' => $this->corp->id,
            'nama'           => 'Pertashop Kemutug Test',
            'kode'           => 'KMTG-' . uniqid(),
            'alamat'         => 'Kemutug Lor',
        ]);

        $this->opUser = User::create([
            'name'     => 'Dendi Supriyatno',
            'email'    => 'dendi.' . uniqid() . '@operator.test',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);

        $this->operator = Operator::create([
            'user_id' => $this->opUser->id,
            'shop_id' => $this->shop->id,
        ]);
    }

    public function test_store_loan_with_tenor_calculates_installment_and_balance()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('employee-loans.store'), [
            'operator_id' => $this->operator->id,
            'tanggal'     => '2026-09-09',
            'jumlah'      => 600000,
            'tenor_bulan' => 3,
            'keterangan'  => 'Pinjaman darurat keluarga',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $loan = EmployeeLoan::where('operator_id', $this->operator->id)->latest()->first();
        $this->assertNotNull($loan);
        $this->assertEquals(600000, floatval($loan->jumlah));
        $this->assertEquals(3, $loan->tenor_bulan);
        $this->assertEquals(200000, floatval($loan->nominal_angsuran));
        $this->assertEquals(0, $loan->angsuran_ke);
        $this->assertEquals(600000, floatval($loan->sisa_pokok));
        $this->assertEquals('pending', $loan->status);
    }

    public function test_full_payroll_integration_multi_month_deduction_and_idempotency()
    {
        // 1. Setup sistem penggajian
        $system = PayrollSystem::create([
            'shop_id'                  => $this->shop->id,
            'nama_sistem'              => 'Sistem Gaji Pokok Murni Kemutug',
            'tipe_skema'               => 'gaji_pokok_murni',
            'nominal_gaji_pokok'       => 2000000,
            'ada_gaji_pokok'           => true,
            'ada_rate_per_liter'       => false,
            'rate_per_liter'           => 0,
            'rate_transport_per_hari'  => 0,
            'potongan_per_hari_alpha'  => 0,
            'metode_split'             => 'per_hari_penuh',
            'aktif'                    => true,
        ]);

        // 2. Setup kasbon 600.000 dengan tenor 3 bulan (200.000 / bln)
        $loan = EmployeeLoan::create([
            'operator_id'      => $this->operator->id,
            'tanggal'          => '2026-01-01',
            'jumlah'           => 600000,
            'tenor_bulan'      => 3,
            'nominal_angsuran' => 200000,
            'angsuran_ke'      => 0,
            'sisa_pokok'       => 600000,
            'status'           => 'approved',
            'approved_by'      => $this->admin->id,
            'approved_at'      => now(),
        ]);

        // Buat 1 shift dan laporan harian agar ada hari kerja
        ShiftSchedule::create([
            'shop_id'     => $this->shop->id,
            'operator_id' => $this->operator->id,
            'tanggal'     => '2026-01-10',
            'shift_ke'    => 1,
            'jam_mulai'   => '07:00:00',
            'jam_selesai' => '15:00:00',
        ]);

        $price = Price::create([
            'shop_id'      => $this->shop->id,
            'harga_beli'   => 12000,
            'harga_jual'   => 14000,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        DailyReport::create([
            'shop_id'                   => $this->shop->id,
            'operator_id'               => $this->operator->id,
            'price_id'                  => $price->id,
            'totalisator_awal'          => 10000,
            'totalisator_akhir'         => 10500,
            'stik_awal'                 => 100,
            'stik_akhir'                => 100,
            'stok_awal'                 => 2000,
            'volume_penjualan_aktual'   => 500,
            'volume_penjualan_teoritis' => 500,
            'rupiah_penjualan'          => 500 * 14000,
            'disetorkan'                => 500 * 14000,
            'selisih_setoran'           => 0,
            'created_at'                => '2026-01-10 16:00:00',
        ]);

        $service = app(PayrollCalculationService::class);

        // ===== BULAN 1: GENERATE DRAFT =====
        $period1 = $service->generate($this->shop->id, 1, 2026, $this->admin->id);
        $detail1 = $period1->details->firstWhere('operator_id', $this->operator->id);

        $this->assertEquals(200000, floatval($detail1->potongan_hutang));
        // THP = 2.000.000 (gaji pokok) - 200.000 (kasbon) = 1.800.000
        $this->assertEquals(1800000, floatval($detail1->take_home_pay));

        // Cek snapshot payroll_loan_deductions
        $deduction1 = PayrollLoanDeduction::where('payroll_period_id', $period1->id)->first();
        $this->assertNotNull($deduction1);
        $this->assertEquals(1, $deduction1->cicilan_ke);
        $this->assertEquals(3, $deduction1->total_tenor);
        $this->assertEquals(200000, floatval($deduction1->nominal_potongan));
        $this->assertEquals(400000, floatval($deduction1->sisa_pokok_setelah));
        $this->assertFalse($deduction1->is_finalized);

        // KASBON DI DB MASIH UTUH KARENA DRAFT (IDEMPOTENCY CHECK)
        $loan->refresh();
        $this->assertEquals(600000, floatval($loan->sisa_pokok));
        $this->assertEquals(0, $loan->angsuran_ke);

        // RE-GENERATE ULANG DRAFT DI BULAN YANG SAMA
        $period1Re = $service->generate($this->shop->id, 1, 2026, $this->admin->id);
        $loan->refresh();
        $this->assertEquals(600000, floatval($loan->sisa_pokok), 'Regenerate draft tidak boleh double-deduct');
        $this->assertEquals(0, $loan->angsuran_ke);

        // FINALISASI BULAN 1
        $service->finalizePayrollLoans($period1Re);
        $period1Re->update(['status' => 'final']);

        $loan->refresh();
        $this->assertEquals(400000, floatval($loan->sisa_pokok));
        $this->assertEquals(1, $loan->angsuran_ke);
        $this->assertEquals('active', $loan->status);

        // IDEMPOTENCY FINALISASI: Panggil lagi finalizePayrollLoans tidak akan mengurangi dua kali
        $service->finalizePayrollLoans($period1Re);
        $loan->refresh();
        $this->assertEquals(400000, floatval($loan->sisa_pokok), 'Finalisasi ulang tidak boleh memotong sisa pokok dua kali');

        // ===== BULAN 2: GENERATE DRAFT & FINALIZE =====
        ShiftSchedule::create([
            'shop_id'     => $this->shop->id,
            'operator_id' => $this->operator->id,
            'tanggal'     => '2026-02-10',
            'shift_ke'    => 1,
            'jam_mulai'   => '07:00:00',
            'jam_selesai' => '15:00:00',
        ]);
        DailyReport::create([
            'shop_id'                   => $this->shop->id,
            'operator_id'               => $this->operator->id,
            'price_id'                  => $price->id,
            'totalisator_awal'          => 10500,
            'totalisator_akhir'         => 11000,
            'stik_awal'                 => 100,
            'stik_akhir'                => 100,
            'stok_awal'                 => 2000,
            'volume_penjualan_aktual'   => 500,
            'volume_penjualan_teoritis' => 500,
            'rupiah_penjualan'          => 500 * 14000,
            'disetorkan'                => 500 * 14000,
            'selisih_setoran'           => 0,
            'created_at'                => '2026-02-10 16:00:00',
        ]);

        $period2 = $service->generate($this->shop->id, 2, 2026, $this->admin->id);
        $detail2 = $period2->details->firstWhere('operator_id', $this->operator->id);

        $this->assertEquals(200000, floatval($detail2->potongan_hutang));
        $deduction2 = PayrollLoanDeduction::where('payroll_period_id', $period2->id)->first();
        $this->assertEquals(2, $deduction2->cicilan_ke);
        $this->assertEquals(3, $deduction2->total_tenor);
        $this->assertEquals(200000, floatval($deduction2->sisa_pokok_setelah));

        $service->finalizePayrollLoans($period2);
        $period2->update(['status' => 'final']);

        $loan->refresh();
        $this->assertEquals(200000, floatval($loan->sisa_pokok));
        $this->assertEquals(2, $loan->angsuran_ke);
        $this->assertEquals('active', $loan->status);

        // ===== BULAN 3: ANGSURAN TERAKHIR (PELUNASAN) =====
        ShiftSchedule::create([
            'shop_id'     => $this->shop->id,
            'operator_id' => $this->operator->id,
            'tanggal'     => '2026-03-10',
            'shift_ke'    => 1,
            'jam_mulai'   => '07:00:00',
            'jam_selesai' => '15:00:00',
        ]);
        DailyReport::create([
            'shop_id'                   => $this->shop->id,
            'operator_id'               => $this->operator->id,
            'price_id'                  => $price->id,
            'totalisator_awal'          => 11000,
            'totalisator_akhir'         => 11500,
            'stik_awal'                 => 100,
            'stik_akhir'                => 100,
            'stok_awal'                 => 2000,
            'volume_penjualan_aktual'   => 500,
            'volume_penjualan_teoritis' => 500,
            'rupiah_penjualan'          => 500 * 14000,
            'disetorkan'                => 500 * 14000,
            'selisih_setoran'           => 0,
            'created_at'                => '2026-03-10 16:00:00',
        ]);

        $period3 = $service->generate($this->shop->id, 3, 2026, $this->admin->id);
        $detail3 = $period3->details->firstWhere('operator_id', $this->operator->id);

        $this->assertEquals(200000, floatval($detail3->potongan_hutang));
        $deduction3 = PayrollLoanDeduction::where('payroll_period_id', $period3->id)->first();
        $this->assertEquals(3, $deduction3->cicilan_ke);
        $this->assertEquals(0, floatval($deduction3->sisa_pokok_setelah));

        $service->finalizePayrollLoans($period3);
        $period3->update(['status' => 'final']);

        $loan->refresh();
        $this->assertEquals(0, floatval($loan->sisa_pokok));
        $this->assertEquals(3, $loan->angsuran_ke);
        $this->assertEquals('lunas', $loan->status);

        // ===== BULAN 4: KASBON SUDAH LUNAS -> TIDAK ADA POTONGAN KASBON =====
        ShiftSchedule::create([
            'shop_id'     => $this->shop->id,
            'operator_id' => $this->operator->id,
            'tanggal'     => '2026-04-10',
            'shift_ke'    => 1,
            'jam_mulai'   => '07:00:00',
            'jam_selesai' => '15:00:00',
        ]);
        DailyReport::create([
            'shop_id'                   => $this->shop->id,
            'operator_id'               => $this->operator->id,
            'price_id'                  => $price->id,
            'totalisator_awal'          => 11500,
            'totalisator_akhir'         => 12000,
            'stik_awal'                 => 100,
            'stik_akhir'                => 100,
            'stok_awal'                 => 2000,
            'volume_penjualan_aktual'   => 500,
            'volume_penjualan_teoritis' => 500,
            'rupiah_penjualan'          => 500 * 14000,
            'disetorkan'                => 500 * 14000,
            'selisih_setoran'           => 0,
            'created_at'                => '2026-04-10 16:00:00',
        ]);

        $period4 = $service->generate($this->shop->id, 4, 2026, $this->admin->id);
        $detail4 = $period4->details->firstWhere('operator_id', $this->operator->id);

        $this->assertEquals(0, floatval($detail4->potongan_hutang), 'Bulan ke-4 kasbon sudah lunas, tidak boleh dipotong');
        $this->assertEquals(2000000, floatval($detail4->take_home_pay), 'THP kembali utuh tanpa potongan kasbon');
        $this->assertEquals(0, PayrollLoanDeduction::where('payroll_period_id', $period4->id)->count());
    }
}
```



# =============================================================================
# TEST: tests/Feature/PayrollSchemeCalculationTest.php
# File: tests/Feature/PayrollSchemeCalculationTest.php
# Test Uji Skema Penggajian Cabang & Perlakuan Losses/Gain
# =============================================================================

```php
<?php

namespace Tests\Feature;

use App\Models\Corporation;
use App\Models\DailyReport;
use App\Models\Operator;
use App\Models\PayrollPeriod;
use App\Models\PayrollSystem;
use App\Models\Price;
use App\Models\Shop;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollSchemeCalculationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Corporation $corp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'     => 'Super Admin Test',
            'email'    => 'superadmin.test@pertashop.com',
            'role'     => 'super-admin',
            'password' => Hash::make('password123'),
        ]);

        $this->corp = Corporation::create([
            'nama'   => 'PT Pertashop Jaya Test',
            'alamat' => 'Banyumas',
        ]);
    }

    protected function createShop(string $nama, string $kode): Shop
    {
        return Shop::create([
            'corporation_id' => $this->corp->id,
            'nama'           => $nama,
            'kode'           => $kode,
            'alamat'         => 'Alamat ' . $nama,
        ]);
    }

    protected function createDailyReport(Shop $shop, Operator $operator, float $volume): DailyReport
    {
        $price = Price::create([
            'shop_id'      => $shop->id,
            'harga_beli'   => 12000,
            'harga_jual'   => 14000,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        $rupiah = $volume * 14000;

        return DailyReport::create([
            'shop_id'                   => $shop->id,
            'operator_id'               => $operator->id,
            'price_id'                  => $price->id,
            'volume_penjualan_aktual'   => $volume,
            'volume_penjualan_teoritis' => $volume,
            'rupiah_penjualan'          => $rupiah,
            'disetorkan'                => $rupiah,
            'setor_tunai'               => $rupiah,
            'losses_gain'               => 0,
            'selisih_setoran'           => 0,
            'totalisator_awal'          => 1000,
            'totalisator_akhir'         => 1000 + $volume,
            'stik_awal'                 => 100,
            'stik_akhir'                => 100,
            'created_at'                => '2026-06-15 10:00:00',
        ]);
    }

    /**
     * Uji pemetaan skema default berdasarkan nama cabang.
     */
    public function test_default_payroll_scheme_by_branch_name(): void
    {
        $kalitapen = $this->createShop('Kalitapen', '4P.53119');
        $kalibenda = $this->createShop('Kalibenda', '4P.53134');
        $pageralang = $this->createShop('Pageralang', '4P.53164');
        $kemutug = $this->createShop('Kemutug Lor', '4P.53143');
        $gumelar = $this->createShop('Gumelar', '4P.53158');
        $sumingkir = $this->createShop('Sumingkir', '4P.532.40');

        $this->assertEquals('komisi_murni', $kalitapen->getDefaultPayrollScheme());
        $this->assertEquals('komisi_murni', $kalibenda->getDefaultPayrollScheme());
        $this->assertEquals('komisi_murni', $pageralang->getDefaultPayrollScheme());
        $this->assertEquals('komisi_murni', $kemutug->getDefaultPayrollScheme());
        $this->assertEquals('gaji_pokok_murni', $gumelar->getDefaultPayrollScheme());
        $this->assertEquals('hibrid', $sumingkir->getDefaultPayrollScheme());
    }

    /**
     * Uji sinkronisasi otomatis boolean flag pada model PayrollSystem saat tipe_skema disimpan.
     */
    public function test_payroll_system_model_auto_sync(): void
    {
        $shop = $this->createShop('Outlet Dummy', '4P.DUMMY');

        // 1. Komisi Murni
        $ps1 = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Komisi Murni Test',
            'tipe_skema'              => 'komisi_murni',
            'rate_per_liter'          => 200,
            'nominal_gaji_pokok'      => 1500000,
            'potongan_per_hari_alpha' => 50000,
            'perlakuan_losses_gain'   => 'losses_only',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);
        $this->assertTrue($ps1->ada_rate_per_liter);
        $this->assertFalse($ps1->ada_gaji_pokok);
        $this->assertNull($ps1->nominal_gaji_pokok);
        $this->assertTrue($ps1->isKomisiMurni());

        // 2. Gaji Pokok Murni
        $ps2 = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Gaji Pokok Murni Test',
            'tipe_skema'              => 'gaji_pokok_murni',
            'rate_per_liter'          => 250,
            'nominal_gaji_pokok'      => 1800000,
            'potongan_per_hari_alpha' => 50000,
            'perlakuan_losses_gain'   => 'losses_only',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);
        $this->assertFalse($ps2->ada_rate_per_liter);
        $this->assertEquals(0, (float) $ps2->rate_per_liter);
        $this->assertTrue($ps2->ada_gaji_pokok);
        $this->assertEquals(1800000, (float) $ps2->nominal_gaji_pokok);
        $this->assertTrue($ps2->isGajiPokokMurni());

        // 3. Hibrid
        $ps3 = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Hibrid Test',
            'tipe_skema'              => 'hibrid',
            'rate_per_liter'          => 200,
            'nominal_gaji_pokok'      => 1500000,
            'potongan_per_hari_alpha' => 50000,
            'perlakuan_losses_gain'   => 'losses_only',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);
        $this->assertTrue($ps3->ada_rate_per_liter);
        $this->assertTrue($ps3->ada_gaji_pokok);
        $this->assertEquals(200, (float) $ps3->rate_per_liter);
        $this->assertEquals(1500000, (float) $ps3->nominal_gaji_pokok);
        $this->assertTrue($ps3->isHibrid());
    }

    /**
     * Uji kalkulasi penggajian untuk cabang Komisi Murni (Kalitapen):
     * Formula: Total Liter Penjualan × Nominal Komisi per Liter (Gaji Pokok = 0)
     */
    public function test_calculation_sistem_komisi_murni_kalitapen(): void
    {
        $shop = $this->createShop('Kalitapen', '4P.53119');

        $opUser = User::create([
            'name'     => 'Operator Kalitapen',
            'email'    => 'op.kalitapen@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);

        $operator = Operator::create([
            'shop_id' => $shop->id,
            'user_id' => $opUser->id,
        ]);

        $payrollSystem = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Sistem Kalitapen',
            'tipe_skema'              => 'komisi_murni',
            'rate_per_liter'          => 200,
            'perlakuan_losses_gain'   => 'abaikan_losses_gain',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);

        // Buat 1 laporan harian dengan volume 1000 liter
        $this->createDailyReport($shop, $operator, 1000);

        $service = app(PayrollCalculationService::class);
        $period = $service->generate($shop->id, 6, 2026, $this->user->id);

        $detail = $period->details->first();
        $this->assertNotNull($detail);
        $this->assertEquals(1000, (float) $detail->liter_bagian);
        $this->assertEquals(0, (float) $detail->gaji_pokok, 'Komisi Murni tidak boleh ada gaji pokok');
        $this->assertEquals(200000, (float) $detail->gaji_variable, '1000 liter * Rp 200 = Rp 200.000');
        $this->assertEquals(200000, (float) $detail->take_home_pay);
    }

    /**
     * Uji kalkulasi penggajian untuk cabang Gaji Pokok Murni (Gumelar):
     * Formula: Nominal Gaji Pokok (Gaji Variable / Komisi Liter = 0)
     */
    public function test_calculation_sistem_gaji_pokok_murni_gumelar(): void
    {
        $shop = $this->createShop('Gumelar', '4P.53158');

        $opUser = User::create([
            'name'     => 'Operator Gumelar',
            'email'    => 'op.gumelar@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);

        $operator = Operator::create([
            'shop_id' => $shop->id,
            'user_id' => $opUser->id,
        ]);

        $payrollSystem = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Sistem Gumelar',
            'tipe_skema'              => 'gaji_pokok_murni',
            'nominal_gaji_pokok'      => 1750000,
            'perlakuan_losses_gain'   => 'abaikan_losses_gain',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);

        // Buat laporan harian dengan volume 1500 liter
        $this->createDailyReport($shop, $operator, 1500);

        $service = app(PayrollCalculationService::class);
        $period = $service->generate($shop->id, 6, 2026, $this->user->id);

        $detail = $period->details->first();
        $this->assertNotNull($detail);
        $this->assertEquals(1750000, (float) $detail->gaji_pokok, 'Gaji Pokok harus sesuai nominal');
        $this->assertEquals(0, (float) $detail->gaji_variable, 'Gaji Pokok Murni tidak boleh ada komisi liter');
        $this->assertEquals(1750000, (float) $detail->take_home_pay);
    }

    /**
     * Uji kalkulasi penggajian untuk cabang Hibrid (Sumingkir):
     * Formula: Nominal Gaji Pokok + (Total Liter Penjualan × Nominal Komisi per Liter)
     */
    public function test_calculation_sistem_hibrid_sumingkir(): void
    {
        $shop = $this->createShop('Sumingkir', '4P.532.40');

        $opUser = User::create([
            'name'     => 'Operator Sumingkir',
            'email'    => 'op.sumingkir@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);

        $operator = Operator::create([
            'shop_id' => $shop->id,
            'user_id' => $opUser->id,
        ]);

        $payrollSystem = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Sistem Sumingkir',
            'tipe_skema'              => 'hibrid',
            'nominal_gaji_pokok'      => 1500000,
            'rate_per_liter'          => 200,
            'perlakuan_losses_gain'   => 'abaikan_losses_gain',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);

        // Buat laporan harian dengan volume 2000 liter
        $this->createDailyReport($shop, $operator, 2000);

        $service = app(PayrollCalculationService::class);
        $period = $service->generate($shop->id, 6, 2026, $this->user->id);

        $detail = $period->details->first();
        $this->assertNotNull($detail);
        $this->assertEquals(1500000, (float) $detail->gaji_pokok, 'Gaji Pokok harus Rp 1.500.000');
        $this->assertEquals(400000, (float) $detail->gaji_variable, '2000 liter * Rp 200 = Rp 400.000');
        $this->assertEquals(1900000, (float) $detail->take_home_pay, 'THP = Rp 1.500.000 + Rp 400.000 = Rp 1.900.000');
    }

    /**
     * Uji kalkulasi pembagian liter (split shift):
     * Memastikan liter_bagian mengambil data penjualan riil dari hari/shift di mana
     * operator benar-benar bertugas, bukan membagi rata total penjualan bulanan.
     */
    public function test_split_shift_distributes_liters_by_actual_working_days_not_flat_monthly(): void
    {
        $shop = $this->createShop('Kalitapen Shift Test', '4P.53119-ST');

        $user1 = User::create([
            'name'     => 'Operator 1 Shift',
            'email'    => 'op1.shift@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);
        $op1 = Operator::create(['shop_id' => $shop->id, 'user_id' => $user1->id]);

        $user2 = User::create([
            'name'     => 'Operator 2 Shift',
            'email'    => 'op2.shift@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);
        $op2 = Operator::create(['shop_id' => $shop->id, 'user_id' => $user2->id]);

        $payrollSystem = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Sistem Split Test',
            'tipe_skema'              => 'komisi_murni',
            'rate_per_liter'          => 200,
            'perlakuan_losses_gain'   => 'abaikan_losses_gain',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);

        // Hari 1: 1000 liter, hanya Operator 1 yang bertugas
        $price = Price::create([
            'shop_id'      => $shop->id,
            'harga_beli'   => 12000,
            'harga_jual'   => 14000,
            'effective_at' => '2026-01-01 00:00:00',
        ]);

        DailyReport::create([
            'shop_id'                   => $shop->id,
            'operator_id'               => $op1->id,
            'price_id'                  => $price->id,
            'volume_penjualan_aktual'   => 1000,
            'volume_penjualan_teoritis' => 1000,
            'rupiah_penjualan'          => 14000000,
            'disetorkan'                => 14000000,
            'losses_gain'               => 0,
            'selisih_setoran'           => 0,
            'totalisator_awal'          => 1000,
            'totalisator_akhir'         => 2000,
            'stik_awal'                 => 100,
            'stik_akhir'                => 100,
            'created_at'                => '2026-06-10 10:00:00',
        ]);

        \App\Models\ShiftSchedule::create([
            'shop_id'     => $shop->id,
            'operator_id' => $op1->id,
            'tanggal'     => '2026-06-10',
            'shift_ke'    => 1,
            'status'      => 'hadir',
        ]);

        // Hari 2: 1000 liter, Operator 1 (Shift 1) dan Operator 2 (Shift 2) bertugas bersama
        DailyReport::create([
            'shop_id'                   => $shop->id,
            'operator_id'               => $op1->id,
            'price_id'                  => $price->id,
            'volume_penjualan_aktual'   => 1000,
            'volume_penjualan_teoritis' => 1000,
            'rupiah_penjualan'          => 14000000,
            'disetorkan'                => 14000000,
            'losses_gain'               => 0,
            'selisih_setoran'           => 0,
            'totalisator_awal'          => 2000,
            'totalisator_akhir'         => 3000,
            'stik_awal'                 => 100,
            'stik_akhir'                => 100,
            'created_at'                => '2026-06-11 10:00:00',
        ]);

        \App\Models\ShiftSchedule::create([
            'shop_id'     => $shop->id,
            'operator_id' => $op1->id,
            'tanggal'     => '2026-06-11',
            'shift_ke'    => 1,
            'status'      => 'hadir',
        ]);
        \App\Models\ShiftSchedule::create([
            'shop_id'     => $shop->id,
            'operator_id' => $op2->id,
            'tanggal'     => '2026-06-11',
            'shift_ke'    => 2,
            'status'      => 'hadir',
        ]);

        $service = app(PayrollCalculationService::class);
        $period = $service->generate($shop->id, 6, 2026, $this->user->id);

        $detailOp1 = $period->details->where('operator_id', $op1->id)->first();
        $detailOp2 = $period->details->where('operator_id', $op2->id)->first();

        // Operator 1: 1000 L (Hari 1) + 500 L (Hari 2) = 1500 L -> Komisi Rp 300.000
        $this->assertEquals(1500, (float) $detailOp1->liter_bagian);
        $this->assertEquals(300000, (float) $detailOp1->gaji_variable);

        // Operator 2: 0 L (Hari 1) + 500 L (Hari 2) = 500 L -> Komisi Rp 100.000
        $this->assertEquals(500, (float) $detailOp2->liter_bagian);
        $this->assertEquals(100000, (float) $detailOp2->gaji_variable);
    }

    /**
     * Uji penanganan potongan alpha prorata & uang transport di skema Gaji Pokok Murni (Gumelar):
     * - Potongan Alpha Prorata: (Gaji_Pokok / Standar_Hari_Kerja) * Hari_Alpha
     * - Uang Transport dihitung dari hari masuk kerja dan TIDAK dimasukkan ke basis prorata alpha.
     */
    public function test_potongan_alpha_prorata_and_transport_in_gaji_pokok_murni(): void
    {
        $shop = $this->createShop('Gumelar Alpha Test', '4P.53158-AT');

        $user = User::create([
            'name'     => 'Operator Gumelar Alpha',
            'email'    => 'op.gumelar.alpha@pertashop.com',
            'role'     => 'operator',
            'password' => Hash::make('password123'),
        ]);
        $operator = Operator::create(['shop_id' => $shop->id, 'user_id' => $user->id]);

        $payrollSystem = PayrollSystem::create([
            'shop_id'                 => $shop->id,
            'nama_sistem'             => 'Sistem Gumelar Prorata',
            'tipe_skema'              => 'gaji_pokok_murni',
            'nominal_gaji_pokok'      => 2600000, // Rp 2.600.000
            'standar_hari_kerja'      => 26,      // Rate harian = 2.600.000 / 26 = Rp 100.000/hari
            'metode_potongan_alpha'   => 'prorata_gaji_pokok',
            'rate_transport_per_hari' => 20000,   // Rp 20.000/hari kerja
            'perlakuan_losses_gain'   => 'abaikan_losses_gain',
            'metode_split'            => 'per_hari_penuh',
            'aktif'                   => true,
        ]);

        // Buat 1 laporan harian kehadiran (1 hari kerja)
        $this->createDailyReport($shop, $operator, 500);

        // 2 hari alpha via ShiftSchedule (otomatis men-trigger kalkulasi AttendanceRecap)
        \App\Models\ShiftSchedule::create([
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => '2026-06-01',
            'shift_ke'    => 1,
            'status'      => 'alpha',
        ]);
        \App\Models\ShiftSchedule::create([
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => '2026-06-02',
            'shift_ke'    => 1,
            'status'      => 'alpha',
        ]);

        $service = app(PayrollCalculationService::class);
        $period = $service->generate($shop->id, 6, 2026, $this->user->id);

        $detail = $period->details->first();
        $this->assertNotNull($detail);

        // Gaji pokok Rp 2.600.000
        $this->assertEquals(2600000, (float) $detail->gaji_pokok);
        $this->assertEquals(0, (float) $detail->gaji_variable);

        // Uang transport = 1 hari kerja (dari 1 daily report yang dihitung) * 20.000 = Rp 20.000
        $this->assertEquals(20000, (float) $detail->uang_transport);

        // Potongan Alpha Prorata: 2 hari * (2.600.000 / 26) = 2 * Rp 100.000 = Rp 200.000
        $this->assertEquals(200000, (float) $detail->potongan_alpha);

        // THP = (2.600.000 + 20.000) - 200.000 = Rp 2.420.000
        $this->assertEquals(2420000, (float) $detail->take_home_pay);
    }
}

```



# =============================================================================
# TEST: tests/Feature/ShiftScheduleBranchRulesTest.php
# File: tests/Feature/ShiftScheduleBranchRulesTest.php
# Test Uji Jadwal Shift dan Assignment Operator
# =============================================================================

```php
<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Shop;
use App\Models\ShiftSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ShiftScheduleBranchRulesTest extends TestCase
{
    use DatabaseTransactions;

    private function getOrCreateSuperAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin_test_shifts@pertashop.com'],
            [
                'name'     => 'Super Admin Test',
                'password' => bcrypt('password'),
                'role'     => 'super-admin',
            ]
        );
    }

    private function getOrCreateShop(string $nama): Shop
    {
        $corp = \App\Models\Corporation::firstOrCreate(
            ['nama' => 'Test Corp'],
            ['telepon' => '08123456789', 'alamat' => 'Test Address']
        );

        return Shop::firstOrCreate(
            ['nama' => $nama],
            [
                'corporation_id' => $corp->id,
                'kode'       => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $nama), 0, 8)),
                'alamat'     => 'Jl. Raya ' . $nama,
                'lokasi'     => $nama . ' Location',
                'modal_awal' => 500000000,
            ]
        );
    }

    private function createOperatorForShop(Shop $shop, string $prefix): Operator
    {
        $user = User::create([
            'name'     => "Operator {$prefix} {$shop->nama}",
            'email'    => "op_{$prefix}_" . uniqid() . "@pertashop.com",
            'password' => bcrypt('password'),
            'role'     => 'operator',
        ]);

        return Operator::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);
    }

    public function test_shop_helper_identifies_shift_vs_non_shift_correctly(): void
    {
        $kemutug    = $this->getOrCreateShop('Kemutug Lor');
        $kalitapen  = $this->getOrCreateShop('Kalitapen');
        $kalibenda  = $this->getOrCreateShop('Kalibenda');
        $pageralang = $this->getOrCreateShop('Pageralang');
        $gumelar    = $this->getOrCreateShop('Gumelar');
        $sumingkir  = $this->getOrCreateShop('Sumingkir');

        // Non-shift checks
        $this->assertFalse($kemutug->isShiftBased());
        $this->assertFalse($kalitapen->isShiftBased());
        $this->assertFalse($kalibenda->isShiftBased());
        $this->assertFalse($pageralang->isShiftBased());

        // Shift checks
        $this->assertTrue($gumelar->isShiftBased());
        $this->assertTrue($sumingkir->isShiftBased());

        // Shift config checks for Sumingkir (2 shifts, 06:00-13:30 & 13:30-21:00)
        $sumingkirConfig = $sumingkir->getShiftConfig();
        $this->assertEquals(2, $sumingkirConfig['total_shifts']);
        $this->assertEquals('06:00:00', $sumingkirConfig['shifts'][1]['jam_mulai']);
        $this->assertEquals('13:30:00', $sumingkirConfig['shifts'][1]['jam_selesai']);
        $this->assertEquals(7.5, $sumingkirConfig['shifts'][1]['durasi_jam']);
        $this->assertEquals('13:30:00', $sumingkirConfig['shifts'][2]['jam_mulai']);
        $this->assertEquals('21:00:00', $sumingkirConfig['shifts'][2]['jam_selesai']);
        $this->assertEquals(7.5, $sumingkirConfig['shifts'][2]['durasi_jam']);
        $this->assertArrayNotHasKey(3, $sumingkirConfig['shifts']);

        // Shift config checks for Gumelar (2 shifts, 07:00-15:00 & 14:30-22:30)
        $gumelarConfig = $gumelar->getShiftConfig();
        $this->assertEquals(2, $gumelarConfig['total_shifts']);
        $this->assertEquals('07:00:00', $gumelarConfig['shifts'][1]['jam_mulai']);
        $this->assertEquals('15:00:00', $gumelarConfig['shifts'][1]['jam_selesai']);
        $this->assertEquals('14:30:00', $gumelarConfig['shifts'][2]['jam_mulai']);
        $this->assertEquals('22:30:00', $gumelarConfig['shifts'][2]['jam_selesai']);
        $this->assertArrayNotHasKey(3, $gumelarConfig['shifts']);
    }

    public function test_non_shift_branch_stores_daily_presence_and_rejects_bulk_shift_generator(): void
    {
        $admin = $this->getOrCreateSuperAdmin();
        $shop = $this->getOrCreateShop('Kemutug Lor');
        $operator = $this->createOperatorForShop($shop, 'KMTG');

        $tanggal = '2026-10-15';

        // 1. Simpan presensi harian
        $response = $this->actingAs($admin)->post(route('shift-schedules.store'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => $tanggal,
            'status'      => 'hadir',
            'keterangan'  => 'Masuk harian penuh',
        ]);

        $response->assertSessionHas('success');

        $schedule = ShiftSchedule::where('shop_id', $shop->id)
            ->where('operator_id', $operator->id)
            ->where('tanggal', $tanggal)
            ->first();

        $this->assertNotNull($schedule);
        $this->assertEquals(1, $schedule->shift_ke);
        $this->assertEquals('hadir', $schedule->status);
        $this->assertEquals('07:00:00', $schedule->jam_mulai);
        $this->assertEquals('21:00:00', $schedule->jam_selesai);

        // 2. Coba jalankan bulk generator pola shift di Kemutug Lor (harus ditolak)
        $bulkResponse = $this->actingAs($admin)->post(route('shift-schedules.bulk'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'bulan'       => '2026-10',
            'pola'        => '2p_2s_1l',
        ]);

        $bulkResponse->assertSessionHasErrors('shop_id');
    }

    public function test_sumingkir_enforces_two_shifts_hours_and_rejects_shift_3(): void
    {
        $admin = $this->getOrCreateSuperAdmin();
        $shop = $this->getOrCreateShop('Sumingkir');
        $operator = $this->createOperatorForShop($shop, 'SMGK');

        // 1. Store Shift 1 di Sumingkir (harus jam 06:00 - 13:30)
        $resp1 = $this->actingAs($admin)->post(route('shift-schedules.store'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => '2026-10-01',
            'shift_ke'    => 1,
            'status'      => 'dijadwalkan',
        ]);
        $resp1->assertSessionHas('success');

        $s1 = ShiftSchedule::where('operator_id', $operator->id)->where('tanggal', '2026-10-01')->first();
        $this->assertNotNull($s1);
        $this->assertEquals('06:00:00', $s1->jam_mulai);
        $this->assertEquals('13:30:00', $s1->jam_selesai);

        // 2. Store Shift 2 di Sumingkir (harus jam 13:30 - 21:00)
        $resp2 = $this->actingAs($admin)->post(route('shift-schedules.store'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => '2026-10-02',
            'shift_ke'    => 2,
            'status'      => 'dijadwalkan',
        ]);
        $resp2->assertSessionHas('success');

        $s2 = ShiftSchedule::where('operator_id', $operator->id)->where('tanggal', '2026-10-02')->first();
        $this->assertNotNull($s2);
        $this->assertEquals('13:30:00', $s2->jam_mulai);
        $this->assertEquals('21:00:00', $s2->jam_selesai);

        // 3. Store Shift 3 di Sumingkir (HARUS DITOLAK karena Sumingkir hanya 2 shift)
        $resp3 = $this->actingAs($admin)->post(route('shift-schedules.store'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'tanggal'     => '2026-10-03',
            'shift_ke'    => 3,
            'status'      => 'dijadwalkan',
        ]);
        $resp3->assertSessionHasErrors('shift_ke');

        // 4. Bulk generator di Sumingkir berhasil
        $bulkResp = $this->actingAs($admin)->post(route('shift-schedules.bulk'), [
            'shop_id'     => $shop->id,
            'operator_id' => $operator->id,
            'bulan'       => '2026-10',
            'pola'        => '2p_2s_1l',
        ]);
        $bulkResp->assertSessionHas('success');
    }

    public function test_ui_view_renders_conditional_elements_per_branch(): void
    {
        $admin = $this->getOrCreateSuperAdmin();
        $kemutug = $this->getOrCreateShop('Kemutug Lor');
        $sumingkir = $this->getOrCreateShop('Sumingkir');

        // 1. Check Kemutug Lor UI
        $resKemutug = $this->actingAs($admin)->get(route('shift-schedules.index', ['shop_id' => $kemutug->id]));
        $resKemutug->assertStatus(200);
        $resKemutug->assertSee('Jam Kerja Reguler Harian (Non-Shift)');
        $resKemutug->assertSee('Presensi & Jadwal Harian', false);
        $resKemutug->assertDontSee('Generate Pola Shift Otomatis');
        $resKemutug->assertDontSee('3 = Malam');

        // 2. Check Sumingkir UI
        $resSumingkir = $this->actingAs($admin)->get(route('shift-schedules.index', ['shop_id' => $sumingkir->id]));
        $resSumingkir->assertStatus(200);
        $resSumingkir->assertSee('Generate Pola Shift Otomatis');
        $resSumingkir->assertSee('06:00 - 13:30');
        $resSumingkir->assertSee('13:30 - 21:00');
        $resSumingkir->assertDontSee('3 = Malam');
    }
}
```



# =============================================================================
# TEST: tests/Feature/BackdateDailyReportIngestionTest.php
# File: tests/Feature/BackdateDailyReportIngestionTest.php
# Test Uji Ingesti Laporan Harian Backdate Arsip
# =============================================================================

```php
<?php

namespace Tests\Feature;

use App\Models\BackdateExcelFile;
use App\Models\DailyReport;
use App\Models\Operator;
use App\Models\Price;
use App\Models\Shop;
use App\Models\User;
use App\Services\BackdateDailyReportIngestionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BackdateDailyReportIngestionTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $shop;
    protected $operator;
    protected $price;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('role', 'super-admin')->first() ?? User::factory()->create(['role' => 'super-admin']);
        $corp = \App\Models\Corporation::first() ?? \App\Models\Corporation::create(['nama' => 'PT Test', 'alamat' => 'Alamat Test']);
        $this->shop = Shop::first() ?? Shop::create([
            'corporation_id'   => $corp->id,
            'kode'             => '4P.53119',
            'nama'             => 'Kalitapen',
            'alamat'           => 'Jl. Raya Kalitapen',
            'skala'            => 21.0,
            'stik_awal'        => 100,
            'totalisator_awal' => 500000,
            'modal_awal'       => 60000000,
        ]);

        $this->operator = Operator::first() ?? Operator::create([
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $this->price = Price::first() ?? Price::create([
            'shop_id'      => $this->shop->id,
            'harga_jual'   => 12200,
            'harga_beli'   => 11276.08,
            'status'       => true,
            'effective_at' => '2026-01-01',
        ]);
    }

    public function test_backdate_file_relationship_on_daily_report()
    {
        $bef = BackdateExcelFile::create([
            'shop_id'           => $this->shop->id,
            'bulan_tahun'       => '2026-06',
            'original_filename' => 'test_report.xlsx',
            'file_path'         => 'backdate_excel/test.xlsx',
            'file_size'         => 12345,
            'user_id'           => $this->user->id,
        ]);

        $dr = DailyReport::create([
            'shop_id'           => $this->shop->id,
            'operator_id'       => $this->operator->id,
            'price_id'          => $this->price->id,
            'totalisator_awal'  => 1000,
            'totalisator_akhir' => 1500,
            'stik_awal'         => 80,
            'stik_akhir'        => 75,
            'stok_awal'         => 1680,
            'disetorkan'        => 5000000,
            'sumber_data'       => 'import_excel_arsip',
            'sumber_file_excel' => $bef->original_filename,
            'backdate_file_id'  => $bef->id,
            'created_at'        => '2026-06-15 17:00:00',
        ]);

        $this->assertNotNull($dr->backdateFile);
        $this->assertEquals($bef->id, $dr->backdateFile->id);
        $this->assertTrue($bef->dailyReports->contains('id', $dr->id));
    }

    public function test_daily_report_index_filters_flexible_date_ranges()
    {
        $this->actingAs($this->user);

        // 1. Filter 1 Hari
        $res1 = $this->getJson(route('daily-reports.index', [
            'shop_id'    => $this->shop->id,
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date'   => Carbon::now()->format('Y-m-d'),
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res1->assertStatus(200);

        // 2. Filter 30 Hari
        $res30 = $this->getJson(route('daily-reports.index', [
            'shop_id'    => $this->shop->id,
            'start_date' => Carbon::now()->subDays(29)->format('Y-m-d'),
            'end_date'   => Carbon::now()->format('Y-m-d'),
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res30->assertStatus(200);

        // 3. Filter 60 Hari
        $res60 = $this->getJson(route('daily-reports.index', [
            'shop_id'    => $this->shop->id,
            'start_date' => Carbon::now()->subDays(59)->format('Y-m-d'),
            'end_date'   => Carbon::now()->format('Y-m-d'),
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res60->assertStatus(200);

        // 4. Filter 90 Hari (Maksimal)
        $res90 = $this->getJson(route('daily-reports.index', [
            'shop_id'    => $this->shop->id,
            'start_date' => Carbon::now()->subDays(89)->format('Y-m-d'),
            'end_date'   => Carbon::now()->format('Y-m-d'),
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $res90->assertStatus(200);
    }

    public function test_daily_report_index_page_contains_all_quick_filter_buttons()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('daily-reports.index'));
        $response->assertStatus(200);

        // Pastikan seluruh tombol filter cepat yang diminta ada pada view
        $response->assertSee('1 Hari (Hari Ini)');
        $response->assertSee('7 Hari');
        $response->assertSee('30 Hari');
        $response->assertSee('60 Hari');
        $response->assertSee('90 Hari (Maks)');
        $response->assertSee('Bulan Ini');
        $response->assertSee('Bulan Lalu');

        // Pastikan atribut data-period tersedia untuk interaksi JS
        $response->assertSee('data-period="30days"', false);
        $response->assertSee('data-period="60days"', false);
        $response->assertSee('data-period="90days"', false);
    }

    public function test_daily_report_action_column_renders_backdate_badge_and_file_links()
    {
        $this->actingAs($this->user);

        $bef = BackdateExcelFile::create([
            'shop_id'           => $this->shop->id,
            'bulan_tahun'       => Carbon::now()->format('Y-m'),
            'original_filename' => 'acuan_backdate.xlsx',
            'file_path'         => 'backdate_excel/acuan.xlsx',
            'file_size'         => 1024,
            'user_id'           => $this->user->id,
        ]);

        $dr = DailyReport::create([
            'shop_id'           => $this->shop->id,
            'operator_id'       => $this->operator->id,
            'price_id'          => $this->price->id,
            'totalisator_awal'  => 1000,
            'totalisator_akhir' => 1500,
            'stik_awal'         => 80,
            'stik_akhir'        => 75,
            'stok_awal'         => 1680,
            'disetorkan'        => 5000000,
            'sumber_data'       => 'import_excel_arsip',
            'sumber_file_excel' => $bef->original_filename,
            'backdate_file_id'  => $bef->id,
            'created_at'        => Carbon::now()->format('Y-m-d 17:00:00'),
        ]);

        $response = $this->getJson(route('daily-reports.index', [
            'shop_id'    => $this->shop->id,
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date'   => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $content = $response->json();

        $this->assertNotEmpty($content['data']);
        $firstRow = collect($content['data'])->firstWhere('id', $dr->id);
        $this->assertNotNull($firstRow);

        // Cek badge Backdate dan link unduh acuan file
        $this->assertStringContainsString('badge-info', $firstRow['action']);
        $this->assertStringContainsString('Backdate', $firstRow['action']);
        $this->assertStringContainsString(route('backdate-excel-files.download', $bef->id), $firstRow['action']);
        $this->assertStringContainsString(route('backdate-excel-files.download-pdf', $bef->id), $firstRow['action']);
    }

    public function test_download_original_backdate_excel_returns_binary_file()
    {
        $this->actingAs($this->user);

        // Siapkan file fisik tiruan
        $testPath = 'backdate_excel/test_raw.xlsx';
        $fullPath = storage_path('app/public/' . $testPath);
        if (!file_exists(dirname($fullPath))) {
            @mkdir(dirname($fullPath), 0777, true);
        }
        file_put_contents($fullPath, 'PK\x03\x04MockZipContentForExcel');

        $bef = BackdateExcelFile::create([
            'shop_id'           => $this->shop->id,
            'bulan_tahun'       => Carbon::now()->format('Y-m'),
            'original_filename' => 'file_mentah_asli.xlsx',
            'file_path'         => $testPath,
            'file_size'         => filesize($fullPath),
            'user_id'           => $this->user->id,
        ]);

        // 1. Unduh via rute download utama
        $response = $this->get(route('backdate-excel-files.download', $bef->id));
        $response->assertStatus(200);
        $this->assertStringContainsString('file_mentah_asli.xlsx', $response->headers->get('content-disposition'));

        // 2. Unduh via rute download-original alias
        $responseAlias = $this->get(route('backdate-excel-files.download-original', $bef->id));
        $responseAlias->assertStatus(200);
        $this->assertStringContainsString('file_mentah_asli.xlsx', $responseAlias->headers->get('content-disposition'));

        @unlink($fullPath);
    }
}
```



# =============================================================================
# TEST: tests/Feature/ShopInvestorSyncTest.php
# File: tests/Feature/ShopInvestorSyncTest.php
# Test Uji Sinkronisasi Kepemilikan Investor & Gerai
# =============================================================================

```php
<?php

namespace Tests\Feature;

use App\Models\Corporation;
use App\Models\Investor;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShopInvestorSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Corporation $corp;
    protected Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Super Admin Test',
            'email'    => 'admin.sync.' . uniqid() . '@pertashop.test',
            'role'     => 'super-admin',
            'password' => Hash::make('password123'),
        ]);

        $this->corp = Corporation::create([
            'nama'   => 'KPRI KOKARNABA BATURRADEN TEST',
            'alamat' => 'Baturraden',
        ]);

        $this->shop = Shop::create([
            'corporation_id' => $this->corp->id,
            'nama'           => 'Kemutug Lor Sync Test',
            'kode'           => 'KMTG-' . uniqid(),
            'alamat'         => 'Kemutug Lor',
            'stik_awal'      => 1000,
            'totalisator_awal' => 10000,
            'modal_awal'     => 470490000,
            'total_investasi'=> 470490000,
            'kapasitas'      => 6000,
            'skala'          => 1,
        ]);
    }

    public function test_updating_shop_modal_awal_automatically_resyncs_investor_nominals_and_total_investasi()
    {
        $this->actingAs($this->admin);

        // Buat 2 investor: 60% dan 40%
        $u1 = User::create(['name' => 'Investor 1', 'email' => 'inv1.' . uniqid() . '@test.com', 'role' => 'investor', 'password' => Hash::make('123')]);
        $inv1 = Investor::create(['user_id' => $u1->id, 'name' => 'Investor 1', 'nama_lengkap_gelar' => 'Investor 1', 'alamat_domisili' => 'Alamat', 'no_hp' => '081', 'nama_bank' => 'BCA', 'no_rekening' => '123', 'atas_nama_rekening' => 'Inv 1']);

        $u2 = User::create(['name' => 'Investor 2', 'email' => 'inv2.' . uniqid() . '@test.com', 'role' => 'investor', 'password' => Hash::make('123')]);
        $inv2 = Investor::create(['user_id' => $u2->id, 'name' => 'Investor 2', 'nama_lengkap_gelar' => 'Investor 2', 'alamat_domisili' => 'Alamat', 'no_hp' => '082', 'nama_bank' => 'BRI', 'no_rekening' => '456', 'atas_nama_rekening' => 'Inv 2']);

        $this->shop->investors()->attach([
            $inv1->id => ['persentase' => 60.00, 'nominal' => 282294000, 'investasi_awal' => 282294000],
            $inv2->id => ['persentase' => 40.00, 'nominal' => 188196000, 'investasi_awal' => 188196000],
        ]);

        // Pastikan nominal awal sesuai 470.490.000
        $this->assertEquals(470490000, $this->shop->investors->sum('pivot.nominal'));

        // Admin melakukan Edit Pertashop dan mengubah modal_awal menjadi 500.000.000
        $response = $this->put(route('shops.update', $this->shop->id), [
            'nama'                      => $this->shop->nama,
            'kode'                      => $this->shop->kode,
            'alamat'                    => $this->shop->alamat,
            'stik_awal'                 => 1000,
            'totalisator_awal'          => 10000,
            'corporation_id'            => $this->corp->id,
            'modal_awal'                => 500000000,
            'kapasitas'                 => 6000,
            'skala'                     => 1,
            'tanggal_mulai_operasional' => '2026-01-01',
        ]);

        $response->assertRedirect(route('shops.index'));

        $this->shop->refresh();

        // 1. Kolom modal_awal dan total_investasi pada tabel shops menjadi 500.000.000
        $this->assertEquals(500000000, floatval($this->shop->modal_awal));
        $this->assertEquals(500000000, floatval($this->shop->total_investasi));

        // 2. Nominal masing-masing investor pada tabel pivot otomatis terkalkulasi ulang
        $this->shop->load('investors');
        $p1 = $this->shop->investors->firstWhere('id', $inv1->id)->pivot;
        $p2 = $this->shop->investors->firstWhere('id', $inv2->id)->pivot;

        // 60% * 500.000.000 = 300.000.000
        $this->assertEquals(300000000, floatval($p1->nominal));
        $this->assertEquals(300000000, floatval($p1->investasi_awal));

        // 40% * 500.000.000 = 200.000.000
        $this->assertEquals(200000000, floatval($p2->nominal));
        $this->assertEquals(200000000, floatval($p2->investasi_awal));

        // 3. Akumulasi investor menjadi 500.000.000
        $this->assertEquals(500000000, floatval($this->shop->investors->sum('pivot.nominal')));
    }
}
```



# =============================================================================
# TEST: tests/Feature/CredentialUpdateAndAuthenticationTest.php
# File: tests/Feature/CredentialUpdateAndAuthenticationTest.php
# Test Uji Autentikasi dan Kredensial Pengguna
# =============================================================================

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CredentialPolicy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CredentialUpdateAndAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_credential_update_and_strict_login_behavior(): void
    {
        $oldEmail = 'tester.lama@pertashop.com';
        $oldPassword = 'PasswordLama!123';
        $newEmail = 'tester.baru@pertashop.com';
        $newPassword = 'PasswordBaru!456';

        $user = User::create([
            'name'      => 'Tester User',
            'email'     => $oldEmail,
            'password'  => Hash::make($oldPassword),
            'role'      => 'operator',
            'is_active' => true,
        ]);

        // 1. Verifikasi login awal berhasil
        $response = $this->post(route('authenticate'), [
            'email'    => $oldEmail,
            'password' => $oldPassword,
        ]);
        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        // 2. Terapkan pembaruan kredensial melalui CredentialPolicy
        $changes = CredentialPolicy::applyToUser($user, $newEmail, $newPassword);
        $this->assertContains('email', $changes);
        $this->assertContains('password', $changes);

        // Reload data dari database untuk memastikan perubahan tersimpan
        $user->refresh();
        $this->assertEquals($newEmail, $user->email);
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check($oldPassword, $user->password));

        // 3. Login menggunakan EMAIL LAMA harus GAGAL
        $responseFailOldEmail = $this->post(route('authenticate'), [
            'email'    => $oldEmail,
            'password' => $newPassword,
        ]);
        $responseFailOldEmail->assertSessionHasErrors('email');
        $this->assertGuest();

        // 4. Login menggunakan PASSWORD LAMA harus GAGAL
        $responseFailOldPassword = $this->post(route('authenticate'), [
            'email'    => $newEmail,
            'password' => $oldPassword,
        ]);
        $responseFailOldPassword->assertSessionHasErrors('email');
        $this->assertGuest();

        // 5. Login menggunakan EMAIL BARU dan PASSWORD BARU harus BERHASIL
        $responseSuccess = $this->post(route('authenticate'), [
            'email'    => $newEmail,
            'password' => $newPassword,
        ]);
        $responseSuccess->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }
}
```



# =============================================================================
# TEST: tests/Feature/ExampleTest.php
# File: tests/Feature/ExampleTest.php
# Test Feature Sederhana / Baseline Response
# =============================================================================

```php
<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
```



# =============================================================================
# TEST: tests/Unit/ExampleTest.php
# File: tests/Unit/ExampleTest.php
# Test Unit Sederhana / Baseline PHPUnit
# =============================================================================

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
```



# =============================================================================
# DATASET ACUAN EXCEL KEMUTUG MARET 2026: kemutug_maret_2026_expected_dari_excel.json
# File: tests/kemutug_maret_2026_expected_dari_excel.json
# Seluruh nilai sel Excel tersimpan '03 Sales Report Kemutug 01 - 31 Maret 2026' (31 hari harian + baris rekap 35-39 + saldo awal + ringkasan penggajian)
# =============================================================================

```json
{
    "saldo_awal": {
        "stok_awal_hari_pertama": 1512.896,
        "totalisator_awal_hari_pertama": 498129.8,
        "uang_belum_disetor_periode_lalu": -26020818,
        "pembelian_lintas_bulan_lalu": 2000
    },
    "parameters": {
        "harga_jual": 12200,
        "harga_kirim": 12950,
        "harga_beli_pertamina": 11276.08,
        "koefisien_tangki": 21.49,
        "komisi_operator_per_liter": 200
    },
    "daily_reports": [
        {
            "tgl": 1,
            "tanggal": "Sunday, 01 March 2026",
            "totalisator_awal": 498129.8,
            "totalisator_akhir": 498926.81,
            "penjualan_teoritis_vol": 797.0100000000093,
            "penjualan_teoritis_rp": 9723522.000000114,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 797.0100000000093,
            "penjualan_aktual_rp": 9723522.000000114,
            "stok_awal": 1512.896,
            "pembelian_bbm": 2000,
            "terima_bbm": 2000,
            "stok_teoritis": 2715.8859999999904,
            "stok_aktual_cm": 125.2,
            "stok_aktual_vol": 2690.548,
            "losses_gain_liter": -25.337999999990643,
            "losses_gain_rp": -285713.3150398945,
            "status_losses_gain": "Losses",
            "stok_akhir": 2690.548,
            "ongkos_bongkar": 10000,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 172000,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 182000,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 9541522.000000114,
            "setoran_bri": 0,
            "setoran_qris": 261820,
            "setoran_transfer": 0,
            "selisih_setoran": -9279702.000000114,
            "total_uang_belum_disetor": -35300520.00000011,
            "operator": "Dendi"
        },
        {
            "tgl": 2,
            "tanggal": "Monday, 02 March 2026",
            "totalisator_awal": 498926.81,
            "totalisator_akhir": 500334.02,
            "penjualan_teoritis_vol": 1407.210000000021,
            "penjualan_teoritis_rp": 17167962.000000257,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 1407.210000000021,
            "penjualan_aktual_rp": 17167962.000000257,
            "stok_awal": 2690.548,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 1283.3379999999788,
            "stok_aktual_cm": 59.5,
            "stok_aktual_vol": 1278.655,
            "losses_gain_liter": -4.682999999978847,
            "losses_gain_rp": -52805.882639761476,
            "status_losses_gain": "Losses",
            "stok_akhir": 1278.655,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 360000,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 360000,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 16807962.000000257,
            "setoran_bri": 8049000,
            "setoran_qris": 51942,
            "setoran_transfer": 27267500,
            "selisih_setoran": 18560479.999999743,
            "total_uang_belum_disetor": -16740040.000000369,
            "operator": "Dendi"
        },
        {
            "tgl": 3,
            "tanggal": "Tuesday, 03 March 2026",
            "totalisator_awal": 500334.02,
            "totalisator_akhir": 501272.72,
            "penjualan_teoritis_vol": 938.6999999999534,
            "penjualan_teoritis_rp": 11452139.999999432,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 938.6999999999534,
            "penjualan_aktual_rp": 11452139.999999432,
            "stok_awal": 1278.655,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 339.95500000004654,
            "stok_aktual_cm": 15.6,
            "stok_aktual_vol": 335.24399999999997,
            "losses_gain_liter": -4.7110000000465675,
            "losses_gain_rp": -53121.6128805251,
            "status_losses_gain": "Losses",
            "stok_akhir": 335.24399999999997,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 223900,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 223900,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 11228239.999999432,
            "setoran_bri": 0,
            "setoran_qris": 678842,
            "setoran_transfer": 0,
            "selisih_setoran": -10549397.999999432,
            "total_uang_belum_disetor": -27289437.9999998,
            "operator": "Dendi"
        },
        {
            "tgl": 4,
            "tanggal": "Wednesday, 04 March 2026",
            "totalisator_awal": 501272.72,
            "totalisator_akhir": 501521.78,
            "penjualan_teoritis_vol": 249.06000000005588,
            "penjualan_teoritis_rp": 3038532.0000006817,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 249.06000000005588,
            "penjualan_aktual_rp": 3038532.0000006817,
            "stok_awal": 335.24399999999997,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 86.18399999994409,
            "stok_aktual_cm": 4,
            "stok_aktual_vol": 85.96,
            "losses_gain_liter": -0.22399999994409825,
            "losses_gain_rp": -2525.8419193696473,
            "status_losses_gain": "Losses",
            "stok_akhir": 85.96,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 66000,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 66000,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 2972532.0000006817,
            "setoran_bri": 9748000,
            "setoran_qris": 0,
            "setoran_transfer": 0,
            "selisih_setoran": 6775467.999999318,
            "total_uang_belum_disetor": -20513970.00000048,
            "operator": "Dendi"
        },
        {
            "tgl": 5,
            "tanggal": "Thursday, 05 March 2026",
            "totalisator_awal": 501521.78,
            "totalisator_akhir": 501521.78,
            "penjualan_teoritis_vol": 0,
            "penjualan_teoritis_rp": 0,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 0,
            "penjualan_aktual_rp": 0,
            "stok_awal": 85.96,
            "pembelian_bbm": 0,
            "terima_bbm": 2000,
            "stok_teoritis": 2085.96,
            "stok_aktual_cm": 97.3,
            "stok_aktual_vol": 2090.977,
            "losses_gain_liter": 5.016999999999825,
            "losses_gain_rp": 56572.09335999803,
            "status_losses_gain": "Gain",
            "stok_akhir": 2090.977,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 0,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 0,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 0,
            "setoran_bri": 0,
            "setoran_qris": 0,
            "setoran_transfer": 0,
            "selisih_setoran": 0,
            "total_uang_belum_disetor": -20513970.00000048,
            "operator": "Dendi"
        },
        {
            "tgl": 6,
            "tanggal": "Friday, 06 March 2026",
            "totalisator_awal": 501521.78,
            "totalisator_akhir": 503500.4,
            "penjualan_teoritis_vol": 1978.6199999999953,
            "penjualan_teoritis_rp": 24139163.999999944,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 1978.6199999999953,
            "penjualan_aktual_rp": 24139163.999999944,
            "stok_awal": 2090.977,
            "pembelian_bbm": 2000,
            "terima_bbm": 0,
            "stok_teoritis": 112.35700000000452,
            "stok_aktual_cm": 3.9,
            "stok_aktual_vol": 83.81099999999999,
            "losses_gain_liter": -28.546000000004526,
            "losses_gain_rp": -321886.97968005104,
            "status_losses_gain": "Losses",
            "stok_akhir": 83.81099999999999,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 494500,
            "internet": 0,
            "lain_lain": 25000,
            "total_pengeluaran": 519500,
            "keterangan_lain": "BBM AMBIL SETORAN",
            "pendapatan_dikurangi_pengeluaran": 23619663.999999944,
            "setoran_bri": 2973000,
            "setoran_qris": 289258,
            "setoran_transfer": 10560500,
            "selisih_setoran": -9796905.999999944,
            "total_uang_belum_disetor": -30310876.000000425,
            "operator": "Dendi"
        },
        {
            "tgl": 7,
            "tanggal": "Saturday, 07 March 2026",
            "totalisator_awal": 503500.4,
            "totalisator_akhir": 504282.04,
            "penjualan_teoritis_vol": 781.6399999999558,
            "penjualan_teoritis_rp": 9536007.99999946,
            "test_pump_vol": 10,
            "test_pump_rp": 122000,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 771.6399999999558,
            "penjualan_aktual_rp": 9414007.99999946,
            "stok_awal": 83.81099999999999,
            "pembelian_bbm": 2000,
            "terima_bbm": 2000,
            "stok_teoritis": 1312.1710000000444,
            "stok_aktual_cm": 60.5,
            "stok_aktual_vol": 1300.145,
            "losses_gain_liter": -12.026000000044405,
            "losses_gain_rp": -135606.1380805007,
            "status_losses_gain": "Losses",
            "stok_akhir": 1300.145,
            "ongkos_bongkar": 10000,
            "biaya_transfer": 0,
            "fotocopy_atk": 5000,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 167800,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 182800,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 9231207.99999946,
            "setoran_bri": 0,
            "setoran_qris": 91600,
            "setoran_transfer": 7000000,
            "selisih_setoran": -2139607.99999946,
            "total_uang_belum_disetor": -32450483.999999885,
            "operator": "Wawan"
        },
        {
            "tgl": 8,
            "tanggal": "Sunday, 08 March 2026",
            "totalisator_awal": 504282.04,
            "totalisator_akhir": 505280.98,
            "penjualan_teoritis_vol": 998.9400000000023,
            "penjualan_teoritis_rp": 12187068.000000028,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 998.9400000000023,
            "penjualan_aktual_rp": 12187068.000000028,
            "stok_awal": 1300.145,
            "pembelian_bbm": 0,
            "terima_bbm": 2000,
            "stok_teoritis": 2301.2049999999977,
            "stok_aktual_cm": 107,
            "stok_aktual_vol": 2299.43,
            "losses_gain_liter": -1.7749999999978172,
            "losses_gain_rp": -20015.041999975387,
            "status_losses_gain": "Losses",
            "stok_akhir": 2299.43,
            "ongkos_bongkar": 10000,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 209400,
            "internet": 0,
            "lain_lain": 45100,
            "total_pengeluaran": 264500,
            "keterangan_lain": "VIXAL+TOILET BALL CAMPERS",
            "pendapatan_dikurangi_pengeluaran": 11922568.000000028,
            "setoran_bri": 0,
            "setoran_qris": 215192,
            "setoran_transfer": 0,
            "selisih_setoran": -11707376.000000028,
            "total_uang_belum_disetor": -44157859.99999991,
            "operator": "Dendi"
        },
        {
            "tgl": 9,
            "tanggal": "Monday, 09 March 2026",
            "totalisator_awal": 505280.98,
            "totalisator_akhir": 506526.95,
            "penjualan_teoritis_vol": 1245.9700000000303,
            "penjualan_teoritis_rp": 15200834.000000369,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 1245.9700000000303,
            "penjualan_aktual_rp": 15200834.000000369,
            "stok_awal": 2299.43,
            "pembelian_bbm": 2000,
            "terima_bbm": 0,
            "stok_teoritis": 1053.4599999999696,
            "stok_aktual_cm": 48.8,
            "stok_aktual_vol": 1048.7119999999998,
            "losses_gain_liter": -4.747999999969807,
            "losses_gain_rp": -53538.827839659534,
            "status_losses_gain": "Losses",
            "stok_akhir": 1048.7119999999998,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 325000,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 325000,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 14875834.000000369,
            "setoran_bri": 21790000,
            "setoran_qris": 409450,
            "setoran_transfer": 9671000,
            "selisih_setoran": 16994615.99999963,
            "total_uang_belum_disetor": -27163244.00000028,
            "operator": "Dendi"
        },
        {
            "tgl": 10,
            "tanggal": "Tuesday, 10 March 2026",
            "totalisator_awal": 506526.95,
            "totalisator_akhir": 507415.31,
            "penjualan_teoritis_vol": 888.359999999986,
            "penjualan_teoritis_rp": 10837991.999999829,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 888.359999999986,
            "penjualan_aktual_rp": 10837991.999999829,
            "stok_awal": 1048.7119999999998,
            "pembelian_bbm": 0,
            "terima_bbm": 2000,
            "stok_teoritis": 2160.3520000000135,
            "stok_aktual_cm": 100.4,
            "stok_aktual_vol": 2157.596,
            "losses_gain_liter": -2.7560000000135005,
            "losses_gain_rp": -31076.876480152234,
            "status_losses_gain": "Losses",
            "stok_akhir": 2157.596,
            "ongkos_bongkar": 10000,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 201000,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 211000,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 10626991.999999829,
            "setoran_bri": 0,
            "setoran_qris": 310874,
            "setoran_transfer": 0,
            "selisih_setoran": -10316117.999999829,
            "total_uang_belum_disetor": -37479362.000000104,
            "operator": "Dendi"
        },
        {
            "tgl": 11,
            "tanggal": "Wednesday, 11 March 2026",
            "totalisator_awal": 507415.31,
            "totalisator_akhir": 508525.9,
            "penjualan_teoritis_vol": 1110.5900000000256,
            "penjualan_teoritis_rp": 13549198.000000313,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 1110.5900000000256,
            "penjualan_aktual_rp": 13549198.000000313,
            "stok_awal": 2157.596,
            "pembelian_bbm": 2000,
            "terima_bbm": 0,
            "stok_teoritis": 1047.0059999999744,
            "stok_aktual_cm": 48.5,
            "stok_aktual_vol": 1042.2649999999999,
            "losses_gain_liter": -4.74099999997452,
            "losses_gain_rp": -53459.895279712684,
            "status_losses_gain": "Losses",
            "stok_akhir": 1042.2649999999999,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 255000,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 255000,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 13294198.000000313,
            "setoran_bri": 12970000,
            "setoran_qris": 206528,
            "setoran_transfer": 11890000,
            "selisih_setoran": 11772329.999999687,
            "total_uang_belum_disetor": -25707032.000000417,
            "operator": "Dendi"
        },
        {
            "tgl": 12,
            "tanggal": "Thursday, 12 March 2026",
            "totalisator_awal": 508525.9,
            "totalisator_akhir": 509350.89,
            "penjualan_teoritis_vol": 824.9899999999907,
            "penjualan_teoritis_rp": 10064877.999999886,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 824.9899999999907,
            "penjualan_aktual_rp": 10064877.999999886,
            "stok_awal": 1042.2649999999999,
            "pembelian_bbm": 0,
            "terima_bbm": 2000,
            "stok_teoritis": 2217.275000000009,
            "stok_aktual_cm": 100.5,
            "stok_aktual_vol": 2159.745,
            "losses_gain_liter": -57.530000000009295,
            "losses_gain_rp": -648712.8824001048,
            "status_losses_gain": "Losses",
            "stok_akhir": 2159.745,
            "ongkos_bongkar": 10000,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 152000,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 162000,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 9902877.999999886,
            "setoran_bri": 0,
            "setoran_qris": 919480,
            "setoran_transfer": 0,
            "selisih_setoran": -8983397.999999886,
            "total_uang_belum_disetor": -34690430.000000305,
            "operator": "Dendi"
        },
        {
            "tgl": 13,
            "tanggal": "Friday, 13 March 2026",
            "totalisator_awal": 509350.89,
            "totalisator_akhir": 509991.44,
            "penjualan_teoritis_vol": 640.5499999999884,
            "penjualan_teoritis_rp": 7814709.999999858,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 640.5499999999884,
            "penjualan_aktual_rp": 7814709.999999858,
            "stok_awal": 2159.745,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 1519.1950000000115,
            "stok_aktual_cm": 69.8,
            "stok_aktual_vol": 1500.0019999999997,
            "losses_gain_liter": -19.193000000011807,
            "losses_gain_rp": -216421.80344013314,
            "status_losses_gain": "Losses",
            "stok_akhir": 1500.0019999999997,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 124900,
            "internet": 0,
            "lain_lain": 25000,
            "total_pengeluaran": 149900,
            "keterangan_lain": "BBM AMBIL SETORAN",
            "pendapatan_dikurangi_pengeluaran": 7664809.999999858,
            "setoran_bri": 8587000,
            "setoran_qris": 302124,
            "setoran_transfer": 12475000,
            "selisih_setoran": 13699314.000000142,
            "total_uang_belum_disetor": -20991116.000000164,
            "operator": "Dendi"
        },
        {
            "tgl": 14,
            "tanggal": "Saturday, 14 March 2026",
            "totalisator_awal": 509991.44,
            "totalisator_akhir": 510380.53,
            "penjualan_teoritis_vol": 389.0900000000256,
            "penjualan_teoritis_rp": 4746898.000000313,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 389.0900000000256,
            "penjualan_aktual_rp": 4746898.000000313,
            "stok_awal": 1500.0019999999997,
            "pembelian_bbm": 2000,
            "terima_bbm": 0,
            "stok_teoritis": 1110.9119999999741,
            "stok_aktual_cm": 51.3,
            "stok_aktual_vol": 1102.437,
            "losses_gain_liter": -8.474999999974216,
            "losses_gain_rp": -95564.77799970926,
            "status_losses_gain": "Losses",
            "stok_akhir": 1102.437,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 103000,
            "air_bersih": 0,
            "cashback": 36800,
            "internet": 0,
            "lain_lain": 50000,
            "total_pengeluaran": 189800,
            "keterangan_lain": "PEMASANGAN METERAN PLN",
            "pendapatan_dikurangi_pengeluaran": 4557098.000000313,
            "setoran_bri": 0,
            "setoran_qris": 176000,
            "setoran_transfer": 0,
            "selisih_setoran": -4381098.000000313,
            "total_uang_belum_disetor": -25372214.000000477,
            "operator": "Wawan"
        },
        {
            "tgl": 15,
            "tanggal": "Sunday, 15 March 2026",
            "totalisator_awal": 510380.53,
            "totalisator_akhir": 510930.27,
            "penjualan_teoritis_vol": 549.7399999999907,
            "penjualan_teoritis_rp": 6706827.999999886,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 549.7399999999907,
            "penjualan_aktual_rp": 6706827.999999886,
            "stok_awal": 1102.437,
            "pembelian_bbm": 0,
            "terima_bbm": 2000,
            "stok_teoritis": 2552.697000000009,
            "stok_aktual_cm": 118.5,
            "stok_aktual_vol": 2546.5649999999996,
            "losses_gain_liter": -6.1320000000096115,
            "losses_gain_rp": -69144.92256010837,
            "status_losses_gain": "Losses",
            "stok_akhir": 2546.5649999999996,
            "ongkos_bongkar": 10000,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 10500,
            "cashback": 96500,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 117000,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 6589827.999999886,
            "setoran_bri": 0,
            "setoran_qris": 870656,
            "setoran_transfer": 0,
            "selisih_setoran": -5719171.999999886,
            "total_uang_belum_disetor": -31091386.000000365,
            "operator": "Dendi"
        },
        {
            "tgl": 16,
            "tanggal": "Monday, 16 March 2026",
            "totalisator_awal": 510930.27,
            "totalisator_akhir": 511312.08,
            "penjualan_teoritis_vol": 381.8099999999977,
            "penjualan_teoritis_rp": 4658081.999999972,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 381.8099999999977,
            "penjualan_aktual_rp": 4658081.999999972,
            "stok_awal": 2546.5649999999996,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 2164.755000000002,
            "stok_aktual_cm": 100.4,
            "stok_aktual_vol": 2157.596,
            "losses_gain_liter": -7.1590000000019245,
            "losses_gain_rp": -80725.4567200217,
            "status_losses_gain": "Losses",
            "stok_akhir": 2157.596,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 72500,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 72500,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 4585581.999999972,
            "setoran_bri": 14928000,
            "setoran_qris": 50000,
            "setoran_transfer": 2535000,
            "selisih_setoran": 12927418.000000028,
            "total_uang_belum_disetor": -18163968.000000335,
            "operator": "Dendi"
        },
        {
            "tgl": 17,
            "tanggal": "Tuesday, 17 March 2026",
            "totalisator_awal": 511312.08,
            "totalisator_akhir": 511789.32,
            "penjualan_teoritis_vol": 477.2399999999907,
            "penjualan_teoritis_rp": 5822327.999999886,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 477.2399999999907,
            "penjualan_aktual_rp": 5822327.999999886,
            "stok_awal": 2157.596,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 1680.3560000000093,
            "stok_aktual_cm": 78,
            "stok_aktual_vol": 1676.2199999999998,
            "losses_gain_liter": -4.136000000009517,
            "losses_gain_rp": -46637.866880107315,
            "status_losses_gain": "Losses",
            "stok_akhir": 1676.2199999999998,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 66500,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 66500,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 5755827.999999886,
            "setoran_bri": 0,
            "setoran_qris": 827726,
            "setoran_transfer": 0,
            "selisih_setoran": -4928101.999999886,
            "total_uang_belum_disetor": -23092070.000000224,
            "operator": "Dendi"
        },
        {
            "tgl": 18,
            "tanggal": "Wednesday, 18 March 2026",
            "totalisator_awal": 511789.32,
            "totalisator_akhir": 512450.86,
            "penjualan_teoritis_vol": 661.539999999979,
            "penjualan_teoritis_rp": 8070787.999999745,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 661.539999999979,
            "penjualan_aktual_rp": 8070787.999999745,
            "stok_awal": 1676.2199999999998,
            "pembelian_bbm": 2000,
            "terima_bbm": 0,
            "stok_teoritis": 1014.6800000000208,
            "stok_aktual_cm": 47.1,
            "stok_aktual_vol": 1012.179,
            "losses_gain_liter": -2.501000000020781,
            "losses_gain_rp": -28201.47608023433,
            "status_losses_gain": "Losses",
            "stok_akhir": 1012.179,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 114500,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 114500,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 7956287.999999745,
            "setoran_bri": 8496000,
            "setoran_qris": 260392,
            "setoran_transfer": 968000,
            "selisih_setoran": 1768104.0000002552,
            "total_uang_belum_disetor": -21323965.99999997,
            "operator": "Dendi"
        },
        {
            "tgl": 19,
            "tanggal": "Thursday, 19 March 2026",
            "totalisator_awal": 512450.86,
            "totalisator_akhir": 513260.73,
            "penjualan_teoritis_vol": 809.8699999999953,
            "penjualan_teoritis_rp": 9880413.999999944,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 809.8699999999953,
            "penjualan_aktual_rp": 9880413.999999944,
            "stok_awal": 1012.179,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 202.30900000000463,
            "stok_aktual_cm": 9.4,
            "stok_aktual_vol": 202.006,
            "losses_gain_liter": -0.30300000000463,
            "losses_gain_rp": -3416.6522400522085,
            "status_losses_gain": "Losses",
            "stok_akhir": 202.006,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 167100,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 167100,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 9713313.999999944,
            "setoran_bri": 0,
            "setoran_qris": 1094434,
            "setoran_transfer": 0,
            "selisih_setoran": -8618879.999999944,
            "total_uang_belum_disetor": -29942845.999999914,
            "operator": "Dendi"
        },
        {
            "tgl": 20,
            "tanggal": "Friday, 20 March 2026",
            "totalisator_awal": 513260.73,
            "totalisator_akhir": 514227.64,
            "penjualan_teoritis_vol": 966.9100000000326,
            "penjualan_teoritis_rp": 11796302.000000399,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 966.9100000000326,
            "penjualan_aktual_rp": 11796302.000000399,
            "stok_awal": 202.006,
            "pembelian_bbm": 0,
            "terima_bbm": 2000,
            "stok_teoritis": 1235.0959999999673,
            "stok_aktual_cm": 56.4,
            "stok_aktual_vol": 1212.0359999999998,
            "losses_gain_liter": -23.05999999996743,
            "losses_gain_rp": -260026.40479963276,
            "status_losses_gain": "Losses",
            "stok_akhir": 1212.0359999999998,
            "ongkos_bongkar": 10000,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 190200,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 200200,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 11596102.000000399,
            "setoran_bri": 14296000,
            "setoran_qris": 334134,
            "setoran_transfer": 2046000,
            "selisih_setoran": 5080031.999999601,
            "total_uang_belum_disetor": -24862814.000000313,
            "operator": "Dendi"
        },
        {
            "tgl": 21,
            "tanggal": "Saturday, 21 March 2026",
            "totalisator_awal": 514227.64,
            "totalisator_akhir": 514607.74,
            "penjualan_teoritis_vol": 380.0999999999767,
            "penjualan_teoritis_rp": 4637219.999999716,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 380.0999999999767,
            "penjualan_aktual_rp": 4637219.999999716,
            "stok_awal": 1212.0359999999998,
            "pembelian_bbm": 2000,
            "terima_bbm": 0,
            "stok_teoritis": 831.9360000000231,
            "stok_aktual_cm": 38.3,
            "stok_aktual_vol": 823.0669999999999,
            "losses_gain_liter": -8.86900000002322,
            "losses_gain_rp": -100007.55352026183,
            "status_losses_gain": "Losses",
            "stok_akhir": 823.0669999999999,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 33200,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 33200,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 4604019.999999716,
            "setoran_bri": 0,
            "setoran_qris": 150000,
            "setoran_transfer": 0,
            "selisih_setoran": -4454019.999999716,
            "total_uang_belum_disetor": -29316834.00000003,
            "operator": "Wawan"
        },
        {
            "tgl": 22,
            "tanggal": "Sunday, 22 March 2026",
            "totalisator_awal": 514607.74,
            "totalisator_akhir": 515184.1,
            "penjualan_teoritis_vol": 576.359999999986,
            "penjualan_teoritis_rp": 7031591.99999983,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 576.359999999986,
            "penjualan_aktual_rp": 7031591.99999983,
            "stok_awal": 823.0669999999999,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 246.70700000001386,
            "stok_aktual_cm": 11.6,
            "stok_aktual_vol": 249.28399999999996,
            "losses_gain_liter": 2.5769999999861,
            "losses_gain_rp": 29058.458159843263,
            "status_losses_gain": "Gain",
            "stok_akhir": 249.28399999999996,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 85000,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 85000,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 6946591.99999983,
            "setoran_bri": 0,
            "setoran_qris": 245000,
            "setoran_transfer": 0,
            "selisih_setoran": -6701591.99999983,
            "total_uang_belum_disetor": -36018425.99999986,
            "operator": "Dendi"
        },
        {
            "tgl": 23,
            "tanggal": "Monday, 23 March 2026",
            "totalisator_awal": 515184.1,
            "totalisator_akhir": 515912.88,
            "penjualan_teoritis_vol": 728.7800000000279,
            "penjualan_teoritis_rp": 8891116.00000034,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 728.7800000000279,
            "penjualan_aktual_rp": 8891116.00000034,
            "stok_awal": 249.28399999999996,
            "pembelian_bbm": 2000,
            "terima_bbm": 2000,
            "stok_teoritis": 1520.5039999999722,
            "stok_aktual_cm": 69.5,
            "stok_aktual_vol": 1493.5549999999998,
            "losses_gain_liter": -26.94899999997233,
            "losses_gain_rp": -303879.079919688,
            "status_losses_gain": "Losses",
            "stok_akhir": 1493.5549999999998,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 144500,
            "internet": 0,
            "lain_lain": 20000,
            "total_pengeluaran": 164500,
            "keterangan_lain": "1 DOS AIR MINERAL",
            "pendapatan_dikurangi_pengeluaran": 8726616.00000034,
            "setoran_bri": 21977000,
            "setoran_qris": 230000,
            "setoran_transfer": 441000,
            "selisih_setoran": 13921383.99999966,
            "total_uang_belum_disetor": -22097042.0000002,
            "operator": "Dendi"
        },
        {
            "tgl": 24,
            "tanggal": "Tuesday, 24 March 2026",
            "totalisator_awal": 515912.88,
            "totalisator_akhir": 516732.17,
            "penjualan_teoritis_vol": 819.289999999979,
            "penjualan_teoritis_rp": 9995337.999999745,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 819.289999999979,
            "penjualan_aktual_rp": 9995337.999999745,
            "stok_awal": 1493.5549999999998,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 674.2650000000208,
            "stok_aktual_cm": 31.5,
            "stok_aktual_vol": 676.935,
            "losses_gain_liter": 2.6699999999791544,
            "losses_gain_rp": 30107.133599764944,
            "status_losses_gain": "Gain",
            "stok_akhir": 676.935,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 137600,
            "internet": 0,
            "lain_lain": 100000,
            "total_pengeluaran": 237600,
            "keterangan_lain": "BONUS OPERATOR",
            "pendapatan_dikurangi_pengeluaran": 9757737.999999745,
            "setoran_bri": 0,
            "setoran_qris": 166230,
            "setoran_transfer": 0,
            "selisih_setoran": -9591507.999999745,
            "total_uang_belum_disetor": -31688549.999999948,
            "operator": "Dendi"
        },
        {
            "tgl": 25,
            "tanggal": "Wednesday, 25 March 2026",
            "totalisator_awal": 516732.17,
            "totalisator_akhir": 517329.25,
            "penjualan_teoritis_vol": 597.0800000000163,
            "penjualan_teoritis_rp": 7284376.000000199,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 597.0800000000163,
            "penjualan_aktual_rp": 7284376.000000199,
            "stok_awal": 676.935,
            "pembelian_bbm": 2000,
            "terima_bbm": 2000,
            "stok_teoritis": 2079.8549999999836,
            "stok_aktual_cm": 95.3,
            "stok_aktual_vol": 2047.9969999999998,
            "losses_gain_liter": -31.857999999983804,
            "losses_gain_rp": -359233.35663981736,
            "status_losses_gain": "Losses",
            "stok_akhir": 2047.9969999999998,
            "ongkos_bongkar": 10000,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 95500,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 105500,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 7178876.000000199,
            "setoran_bri": 17055000,
            "setoran_qris": 717504,
            "setoran_transfer": 1033500,
            "selisih_setoran": 11627127.9999998,
            "total_uang_belum_disetor": -20061422.00000015,
            "operator": "Dendi"
        },
        {
            "tgl": 26,
            "tanggal": "Thursday, 26 March 2026",
            "totalisator_awal": 517329.25,
            "totalisator_akhir": 518036.01,
            "penjualan_teoritis_vol": 706.7600000000093,
            "penjualan_teoritis_rp": 8622472.000000114,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 706.7600000000093,
            "penjualan_aktual_rp": 8622472.000000114,
            "stok_awal": 2047.9969999999998,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 1341.2369999999905,
            "stok_aktual_cm": 62.3,
            "stok_aktual_vol": 1338.8269999999998,
            "losses_gain_liter": -2.4099999999907595,
            "losses_gain_rp": -27175.352799895805,
            "status_losses_gain": "Losses",
            "stok_akhir": 1338.8269999999998,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 150800,
            "internet": 166500,
            "lain_lain": 0,
            "total_pengeluaran": 317300,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 8305172.000000114,
            "setoran_bri": 0,
            "setoran_qris": 194000,
            "setoran_transfer": 0,
            "selisih_setoran": -8111172.000000114,
            "total_uang_belum_disetor": -28172594.00000026,
            "operator": "Dendi"
        },
        {
            "tgl": 27,
            "tanggal": "Friday, 27 March 2026",
            "totalisator_awal": 518036.01,
            "totalisator_akhir": 518861.36,
            "penjualan_teoritis_vol": 825.3499999999767,
            "penjualan_teoritis_rp": 10069269.999999717,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 825.3499999999767,
            "penjualan_aktual_rp": 10069269.999999717,
            "stok_awal": 1338.8269999999998,
            "pembelian_bbm": 0,
            "terima_bbm": 2000,
            "stok_teoritis": 2513.477000000023,
            "stok_aktual_cm": 115.5,
            "stok_aktual_vol": 2482.095,
            "losses_gain_liter": -31.382000000023254,
            "losses_gain_rp": -353865.9425602622,
            "status_losses_gain": "Losses",
            "stok_akhir": 2482.095,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 134600,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 134600,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 9934669.999999717,
            "setoran_bri": 10188000,
            "setoran_qris": 360146,
            "setoran_transfer": 4385000,
            "selisih_setoran": 4998476.000000283,
            "total_uang_belum_disetor": -23174117.999999978,
            "operator": "Dendi"
        },
        {
            "tgl": 28,
            "tanggal": "Saturday, 28 March 2026",
            "totalisator_awal": 518861.36,
            "totalisator_akhir": 519523.01,
            "penjualan_teoritis_vol": 661.6500000000233,
            "penjualan_teoritis_rp": 8072130.000000284,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 661.6500000000233,
            "penjualan_aktual_rp": 8072130.000000284,
            "stok_awal": 2482.095,
            "pembelian_bbm": 0,
            "terima_bbm": 0,
            "stok_teoritis": 1820.4449999999765,
            "stok_aktual_cm": 84.6,
            "stok_aktual_vol": 1818.0539999999996,
            "losses_gain_liter": -2.3909999999768843,
            "losses_gain_rp": -26961.107279739346,
            "status_losses_gain": "Losses",
            "stok_akhir": 1818.0539999999996,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 57200,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 57200,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 8014930.000000284,
            "setoran_bri": 0,
            "setoran_qris": 328000,
            "setoran_transfer": 0,
            "selisih_setoran": -7686930.000000284,
            "total_uang_belum_disetor": -30861048.00000026,
            "operator": "Wawan"
        },
        {
            "tgl": 29,
            "tanggal": "Sunday, 29 March 2026",
            "totalisator_awal": 519523.01,
            "totalisator_akhir": 520117.82,
            "penjualan_teoritis_vol": 594.8099999999977,
            "penjualan_teoritis_rp": 7256681.999999972,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 594.8099999999977,
            "penjualan_aktual_rp": 7256681.999999972,
            "stok_awal": 1818.0539999999996,
            "pembelian_bbm": 2000,
            "terima_bbm": 0,
            "stok_teoritis": 1223.244000000002,
            "stok_aktual_cm": 57,
            "stok_aktual_vol": 1224.9299999999998,
            "losses_gain_liter": 1.6859999999978754,
            "losses_gain_rp": 19011.470879976045,
            "status_losses_gain": "Gain",
            "stok_akhir": 1224.9299999999998,
            "ongkos_bongkar": 0,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 90000,
            "internet": 0,
            "lain_lain": 24000,
            "total_pengeluaran": 114000,
            "keterangan_lain": "AIR",
            "pendapatan_dikurangi_pengeluaran": 7142681.999999972,
            "setoran_bri": 0,
            "setoran_qris": 45000,
            "setoran_transfer": 0,
            "selisih_setoran": -7097681.999999972,
            "total_uang_belum_disetor": -37958730.00000023,
            "operator": "Dendi"
        },
        {
            "tgl": 30,
            "tanggal": "Monday, 30 March 2026",
            "totalisator_awal": 520117.82,
            "totalisator_akhir": 520733.95,
            "penjualan_teoritis_vol": 616.1300000000047,
            "penjualan_teoritis_rp": 7516786.000000057,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 616.1300000000047,
            "penjualan_aktual_rp": 7516786.000000057,
            "stok_awal": 1224.9299999999998,
            "pembelian_bbm": 1000,
            "terima_bbm": 2000,
            "stok_teoritis": 2608.799999999995,
            "stok_aktual_cm": 120.5,
            "stok_aktual_vol": 2589.5449999999996,
            "losses_gain_liter": -19.25499999999556,
            "losses_gain_rp": -217120.92039994994,
            "status_losses_gain": "Losses",
            "stok_akhir": 2589.5449999999996,
            "ongkos_bongkar": 10000,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 95000,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 105000,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 7411786.000000057,
            "setoran_bri": 21719000,
            "setoran_qris": 964606,
            "setoran_transfer": 2640500,
            "selisih_setoran": 17912319.999999944,
            "total_uang_belum_disetor": -20046410.000000287,
            "operator": "Dendi"
        },
        {
            "tgl": 31,
            "tanggal": "Tuesday, 31 March 2026",
            "totalisator_awal": 520733.95,
            "totalisator_akhir": 521724.73,
            "penjualan_teoritis_vol": 990.7799999999697,
            "penjualan_teoritis_rp": 12087515.999999631,
            "test_pump_vol": 0,
            "test_pump_rp": 0,
            "kirim_kemutug_vol": 0,
            "kirim_kemutug_rp": 0,
            "penjualan_aktual_vol": 990.7799999999697,
            "penjualan_aktual_rp": 12087515.999999631,
            "stok_awal": 2589.5449999999996,
            "pembelian_bbm": 0,
            "terima_bbm": 1000,
            "stok_teoritis": 2598.76500000003,
            "stok_aktual_cm": 119.7,
            "stok_aktual_vol": 2572.353,
            "losses_gain_liter": -26.41200000002982,
            "losses_gain_rp": -297823.82496033626,
            "status_losses_gain": "Losses",
            "stok_akhir": 2572.353,
            "ongkos_bongkar": 10000,
            "biaya_transfer": 0,
            "fotocopy_atk": 0,
            "listrik": 0,
            "air_bersih": 0,
            "cashback": 195800,
            "internet": 0,
            "lain_lain": 0,
            "total_pengeluaran": 205800,
            "keterangan_lain": "",
            "pendapatan_dikurangi_pengeluaran": 11881715.999999631,
            "setoran_bri": 0,
            "setoran_qris": 315000,
            "setoran_transfer": 0,
            "selisih_setoran": -11566715.999999631,
            "total_uang_belum_disetor": -31613125.999999918,
            "operator": "Dendi"
        }
    ],
    "monthly_summary": {
        "F35_penjualan_teoritis_vol": 23594.93,
        "G35_penjualan_teoritis_rp": 287858146,
        "H35_test_pump_vol": 10.0,
        "L35_penjualan_aktual_vol": 23584.93,
        "M35_penjualan_aktual_rp": 287736146,
        "T35_losses_gain_liter": -355.613,
        "U35_losses_gain_rp": -4009920.6370400735,
        "V35_status_losses_gain": "Losses",
        "AG35_total_pengeluaran": 5388900,
        "AL36_total_disetor": 276754938,
        "AP35_total_uang_belum_disetor": -31613125.999999918,
        "AV35_omset_plus_losses": 23217.367,
        "F36_avg_liter": 786.164333333333,
        "AJ36_selisih_disetor": -31613125.999999918,
        "dendi_gaji": 4209329.60,
        "wawan_gaji": 434143.80,
        "total_gaji": 4643473.40
    }
}
```



# =============================================================================
# SPESIFIKASI ASLI ACUAN EXCEL: Spesifikasi_Template_REKAP_dan_Pemantauan.md
# File: C:/Users/user/.gemini/antigravity/brain/4d447095-7977-413d-a377-5b1309847026/.user_uploaded/media_1794629966345_175c04f3.md
# Dokumen spesifikasi template rekap bulanan dan pemantauan harian yang diunggah pengguna
# =============================================================================

```markdown
# SPESIFIKASI TEMPLATE REKAP HARIAN PERTASHOP + FITUR PEMANTAUAN SUPERADMIN

Sumber: Excel "03 Sales Report Kemutug 01 - 31 Maret 2026", sheet "REKAP KMT 01-31 Maret 2026".
Tujuan: membuat template di sistem yang menghasilkan angka **sama persis** dengan Excel.
Template ini berlaku untuk SEMUA gerai. Kemutug (KMT) hanya contoh dan data uji.

Status verifikasi: seluruh rumus kolom harian di bawah sudah diterjemahkan ulang dan
dibandingkan dengan nilai di Excel untuk 31 hari (713 sel). Hasil: 0 selisih.

---

## 0. ATURAN EMAS (WAJIB)

1. **Urutan operasi harus sama dengan rumus Excel.** Jangan menyederhanakan atau menata ulang rumus.
2. **Jangan membulatkan di tengah perhitungan.** Excel hanya membulatkan TAMPILAN, bukan nilainya.
   Simpan dan hitung dengan presisi penuh, bulatkan hanya saat ditampilkan (lihat Bagian 5).
   Di PostgreSQL pakai tipe NUMERIC (bukan float/real) untuk volume dan rupiah; di PHP boleh float
   (sama dengan double di Excel) selama urutan operasi sama.
3. **Harga, koefisien tangki, dan komisi adalah PARAMETER per gerai**, bukan angka di dalam rumus.
   Di Excel Kemutug nilainya: harga jual 12200, harga kirim 12950, harga beli Pertamina 11276.08,
   koefisien tangki 21.49, komisi 200 per liter.
4. **Hanya sel INPUT yang diisi operator.** Semua sel HASIL dihitung sistem dan tidak bisa diedit.
5. **Satu sumber angka.** Halaman laporan, rekap bulanan, pemantauan superadmin, dan ekspor Excel
   memakai layanan hitung yang sama. Tidak boleh ada perhitungan terpisah di tempat lain.
6. **Uji sel demi sel**, bukan hanya total (lihat Bagian 7).
7. Judul sheet dan judul kolom bersifat dinamis per gerai (lihat Bagian 1).

---

## 1. PENAMAAN DINAMIS PER GERAI

- Judul rekap: `REKAP {KODE_GERAI} {tgl_awal}-{tgl_akhir} {Bulan} {Tahun}`
  Contoh: `REKAP KMT 01-31 Maret 2026`. Kode gerai (KMT, dst.) diambil dari data gerai.
- Judul kolom "Kirim Kemutug" (J-K) memakai nama gerai: `Kirim {Nama Gerai}`.
  Di sistem, nilainya disimpan di field umum `bbm_keluar_lain`.
- Daftar kategori pengeluaran (kolom Y-AF) boleh berbeda per gerai.
- Kolom gaji operator (AW, AX, ...) diambil dari operator gerai itu (di Kemutug: Dendi, Wawan),
  bukan ditulis tetap.
- Locale tanggal: Indonesia ("Minggu, 01 Maret 2026").

---

## 2. STRUKTUR JUDUL (header bertingkat, seperti Excel)

Baris judul atas / sub-judul. Tanda `|` berarti sel digabung (merge) di judul atas.

| Kolom | Judul atas | Sub-judul |
|---|---|---|
| A | Tgl | (gabung 2 baris) |
| B | No | (gabung 2 baris) |
| C | Tanggal | (gabung 2 baris) |
| D | Totalisator Awal | (gabung 2 baris) |
| E | Totalisator Akhir | (gabung 2 baris) |
| F-G | Teoritis Penjualan (di Excel tertulis "Toritis", typo) | F: Volume (ℓ), G: Rupiah |
| H-I | TEST PUMP | H: Volume (ℓ), I: Rupiah |
| J-K | Kirim {Nama Gerai} | J: Volume (ℓ), K: Rupiah |
| L-M | Aktual Penjualan (L) | L: Volume (ℓ), M: Rupiah |
| N | STOK | AWAL (ℓ) |
| O | Pembelian | BBM (ℓ) |
| P | Terima | BBM (ℓ) |
| Q | STOK | TEORITIS (ℓ) |
| R-S | STOK AKTUAL AKHIR | R: CM, S: Volume (ℓ) |
| T | NOMINAL | LOSSES / GAIN (ℓ) |
| U | NOMINAL | RUPIAH |
| V | LOSSES / GAIN | (gabung 2 baris) |
| W | STOK | AKHIR (ℓ) |
| X | Jumlah Actual Penjualan | (gabung 2 baris) |
| Y-AF | (grup pengeluaran) | Ongkos Bongkar, Biaya Transfer, Fotocopy & ATK, Listrik, Air Bersih, Cashback, Internet, Lain-lain |
| AG | Total Pengeluaran | (gabung 2 baris) |
| AH-AI | Keterangan Lain2 | (gabung) |
| AJ-AK | Pendapatan (Dikurangi) Pengeluaran | (gabung) |
| AL-AN | JUMLAH DISETORKAN | AL: BRI, AM: QRIS BRI, AN: TRANSFER BY OPERATOR |
| AO | Selisih Uang Yang Disetorkan dg Setoran | (gabung 2 baris) |
| AP | Total uang yang belum disetorkan | (gabung 2 baris) |
| AQ | KETERANGAN | (gabung 2 baris) |
| AR | (tanpa judul atas) | Tanggal |
| AS | (blok gaji) | OMSET KOTOR |
| AT-AU | (blok gaji) | Losses / Gain (C) |
| AV | (blok gaji) | OMSET + LOSSES |
| AW-AX | (blok gaji) | Penjualan(D) x Rp200 (Gaji Opr.); AW = Dendi, AX = WAWAN |
| AY | (blok gaji) | Penjualan + Losses - Pengeluaran (header di Excel; isinya lihat catatan) |

Kolom BA di Excel hanya rumus bantu duplikat AV; TIDAK dipindahkan ke sistem.

---

## 3. KOLOM HARIAN: JENIS, RUMUS EXCEL, DAN ARTINYA

Jenis: INPUT = diisi operator. HASIL = dihitung sistem. PARAM = parameter gerai.
"hari ini" = baris tanggal berjalan; "kemarin" = baris tanggal sebelumnya.
Rumus Excel diambil dari baris 5 (hari ke-2) sebagai contoh.

| Kol | Key sistem | Jenis | Rumus Excel (baris 5) | Arti / rumus ringkas |
|---|---|---|---|---|
| A | nomor_hari | HASIL | (diketik 1,2,3...) | nomor urut hari dalam bulan |
| B | no_urut | HASIL | (diketik 1,2,3...) | sama dengan A |
| C | tanggal | INPUT/HASIL | C4 = tanggal awal; `=C4+1` | hari pertama diisi, hari berikutnya +1 |
| D | totalisator_awal | HASIL | D4 diketik; `=E4` | = totalisator_akhir kemarin. Hari pertama bulan diambil dari saldo awal |
| E | totalisator_akhir | INPUT | angka | hari tutup/libur: sama dengan kemarin (Excel: E8 `=E7`) |
| F | penjualan_teoritis_volume | HASIL | `=E5-D5` | totalisator_akhir − totalisator_awal |
| G | penjualan_teoritis_rupiah | HASIL | `=F5*12200` | F × harga_jual |
| H | test_pump_volume | INPUT | kosong bila tidak ada | liter test pump |
| I | test_pump_rupiah | HASIL | `=H5*12200` | H × harga_jual |
| J | bbm_keluar_lain_volume | INPUT | kosong bila tidak ada | liter "Kirim {Gerai}" |
| K | bbm_keluar_lain_rupiah | HASIL | `=J5*12950` | J × harga_kirim (hanya informasi, tidak dipakai kolom lain) |
| L | penjualan_aktual_volume | HASIL | `=F5-H5-J5` | F − H − J |
| M | penjualan_aktual_rupiah | HASIL | `=L5*12200` | L × harga_jual |
| N | stok_awal | HASIL | N4 `=N3`; `=W4` | = stok_akhir (W) kemarin. Hari pertama = stok awal bulan (N3) |
| O | pembelian_bbm | INPUT | angka (1000 atau 2000) | liter dipesan hari itu |
| P | terima_bbm | INPUT | angka | liter diterima hari itu |
| Q | stok_teoritis | HASIL | `=N5+P5-J5-L5` | lihat Catatan A di bawah |
| R | stok_aktual_cm | INPUT | angka | hasil mistar ukur (cm) |
| S | stok_aktual_volume | HASIL | `=R5*21.49` | R × koefisien_tangki |
| T | losses_gain_liter | HASIL | `=S5-Q5` | S − Q |
| U | losses_gain_rupiah | HASIL | `=T5*11276.08` | T × harga_beli_pertamina |
| V | status_losses_gain | HASIL | `=IF(T5=0,"Pas",IF(T5<0,"Losses","Gain"))` | T=0 "Pas"; T<0 "Losses"; T>0 "Gain" |
| W | stok_akhir | HASIL | `=S5` | = S (jadi stok awal besok) |
| X | jumlah_aktual_penjualan | HASIL | `=M5` | = M |
| Y | ongkos_bongkar | INPUT | angka | |
| Z | biaya_transfer | INPUT | angka | |
| AA | fotocopy_atk | INPUT | angka | |
| AB | listrik | INPUT | angka | |
| AC | air_bersih | INPUT | angka | |
| AD | cashback | INPUT | angka / penjumlahan manual | di Excel sering `=4000+4000+6000+...` (lihat Catatan C) |
| AE | internet | INPUT | angka | |
| AF | lain_lain | INPUT | angka | wajib isi keterangan (AH) bila ada nilai |
| AG | total_pengeluaran | HASIL | `=SUM(Y5:AF5)` | jumlah Y sampai AF |
| AH | keterangan_lain_lain | INPUT | teks | |
| AJ | pendapatan_dikurangi_pengeluaran | HASIL | `=X5-AG5` | X − AG |
| AL | setoran_bri | INPUT | angka | |
| AM | setoran_qris_bri | INPUT | angka | |
| AN | setoran_transfer_operator | INPUT | angka / penjumlahan manual | di Excel kadang `=22387000-12716000` |
| AO | selisih_setoran | HASIL | `=(AG5+AM5+AL5+AN5)-X5` | (pengeluaran + semua setoran) − penjualan. Negatif = kurang setor |
| AP | uang_belum_disetor | HASIL | `=AO5+AP4` | AO hari ini + AP kemarin. Hari 1: `=AO4+AP3`, AP3 `=AO3`, AO3 `=AJ3` |
| AQ | keterangan_tanggal | HASIL | AQ4 `=C4`; `=AQ4+1` | hari, tanggal, bulan, tahun. AQ3 `=AQ4-1` |
| AR | tanggal_gaji | HASIL | `=AQ5` | salinan AQ |
| AS | omset_kotor | HASIL | `=L5` | = L |
| AT | status_gaji | HASIL | `=V5` | = V |
| AU | losses_gain_gaji | HASIL | `=T5` | = T |
| AV | omset_plus_losses | HASIL | `=IF(AT5="LOSSES",SUM(AS5:AU5),SUM(AS5:AS5))` | Losses: AS + AU. Selain itu: AS saja. (Gain TIDAK menambah.) Perbandingan teks tidak peka huruf besar/kecil |
| AW | gaji_harian | HASIL | `=AV5*200` | AV × komisi per liter |
| AX | gaji_harian_operator_2 | (kosong tiap hari) | | pembagian per operator dihitung di rekap (Bagian 4) |
| AY | (lihat Catatan D) | HASIL | `=AW5+AX5` | di Excel: AW + AX (total gaji hari itu) |

**Baris 3 (saldo awal bulan), semuanya diisi/ditentukan sebelum hari pertama:**
- N3 = stok awal bulan (Kemutug: 1512.896).
- O3 = pembelian yang dipesan sebelum awal bulan dan tiba di awal bulan (Kemutug: 2000).
- AJ3 = uang belum disetor periode sebelumnya (Kemutug: -26020818).
- D4 = totalisator awal hari pertama (Kemutug: 498129.8).

**Catatan A (Q, stok teoritis):** rumus Excel mengurangi J dua kali karena L sudah mengurangi J.
Dampaknya nol bila J kosong (seperti Maret). KEPUTUSAN PEMILIK: ikuti Excel apa adanya,
atau perbaiki menjadi `N + P − L`? Rekomendasi: perbaiki, karena hasil Maret identik.
Apa pun pilihannya, tulis di sini dan masukkan dalam uji.

**Catatan B (hari tutup):** hari tanpa penjualan (Excel baris 8, tgl 5 Maret): E = E kemarin,
sehingga F = 0 dan L = 0. Di form, sediakan opsi "Tutup/Libur" yang mengisi totalisator_akhir
otomatis sama dengan totalisator_awal. Stok tetap bisa berubah karena ada penerimaan BBM.

**Catatan C (sel yang tampak seperti rumus tapi sebenarnya input):**
AD (mis. `=18000+15000+135000+4000`), AN12 `=22387000-12716000`, AN14
`=24783000-254000-12639000`, E28 `=517329.25`. Di sistem, sediakan input multi-item
(nama + nominal) lalu totalkan per kategori. Nilai total harus sama dengan hasil rumus Excel.

**Catatan D (header AY):** judul "Penjualan + Losses - Pengeluaran" tetapi rumusnya `=AW+AX`
(total gaji). Konfirmasi dulu ke pemilik: AY di sistem dibuat sebagai "Total Gaji Harian"
atau sebagai nilai bersih sesuai judul. Perhitungan total bersih (penjualan ± losses − pengeluaran)
tidak ada di sheet ini.

---

## 4. BARIS REKAP (BULANAN) DI BAWAH TABEL HARIAN

Baris total di Excel (tabel harian baris 4 sampai 34):

| Sel Excel | Rumus Excel | Arti |
|---|---|---|
| F35, G35, H35, I35, J35, K35, L35, M35 | `=SUM(F4:F34)` dst. | jumlah sebulan |
| T35, U35 | `=SUM(T4:T34)`, `=SUM(U4:U34)` | losses/gain bersih (hari Gain ikut mengurangi) |
| V35 | `=IF(T35=0,"Pas",IF(T35<0,"Losses","Gain"))` | status bulanan |
| X35 | `=SUM(X4:X34)` | jumlah penjualan aktual |
| Y35 sampai AG35 | `=SUM(Y4:Y34)` dst. | tiap kategori + total pengeluaran |
| O35, P35 | `=SUM(O3:O34)`, `=SUM(P3:P34)` | total pembelian dan terima. Mulai dari BARIS 3 (ikut pembelian lintas bulan) |
| AJ35 | `=SUM(AJ4:AK34)-AJ3` | jumlah pendapatan dikurangi pengeluaran, dikurangi saldo awal (AJ3 negatif sehingga bertambah) |
| AL35, AM35, AN35 | `=SUM(AL4:AL34)` dst. | total tiap jenis setoran |
| AO35 | `=SUM(AO3:AO34)` | = uang belum disetor akhir bulan (AO3 = saldo awal) |
| AP35 | `=AP34` | uang belum disetor di hari terakhir |
| AU35 | `=SUM(AU4:AU34)` | |
| AV35 | `=SUM(AV4:AV34)` | total omset + losses (liter) |
| AW35 | `=SUM(AW4:AW34)` | total gaji |
| AY35 | `=AW35+AX35` | |
| F36 | `=AVERAGEIF(L4:L34,">0")` | rata-rata penjualan aktual (liter) per hari, hanya hari > 0 |
| G36 | `=AVERAGEIF(M4:M34,">0")` | rata-rata penjualan aktual (rupiah) per hari, hanya hari > 0 |
| AL36 | `=AL35+AM35+AN35` | total disetorkan |
| AJ36 | `=AL36-AJ35` | selisih = total disetor − (pendapatan dikurangi pengeluaran + saldo awal). Harus sama dengan AP35 |
| AJ37 | 0 (input, "Setoran Closing") | setoran penutupan, diketik |
| AJ38 | `=AJ36+AJ37` | "sisa setoran yg belum disetor" |
| O38, P38 | `=SUMIF(O4:O34,"1000",O4:O34)` | total pembelian / terima dalam pecahan 1 KL (1000 ℓ) |
| O39, P39 | `=SUMIF(O4:O34,"2000",O4:O34)` | total dalam pecahan 2 KL (2000 ℓ) |

**Gaji per operator (baris 37-39):**

| Sel | Rumus Excel | Arti |
|---|---|---|
| AV37 (Dendi) | `=AV35-AV10-AV17-AV24-AV31` | total AV bulan dikurangi hari jatah operator lain (di Excel: baris tanggal 7, 14, 21, 28) |
| AW37 | `=AV37*200` | gaji Dendi |
| AV38 (Wawan) | `=AV35-AV37` | sisa |
| AW38 | `=AV38*200` | gaji Wawan |
| AV39 | `=SUM(AV37:AV38)` | harus sama dengan AV35 |
| AW39 | `=(AV39*200)` | harus sama dengan AW35 |

Di sistem: jumlahkan AV hanya pada hari operator tersebut bertugas (dari absensi/shift),
lalu kalikan komisi. Jangan menunjuk baris tertentu secara manual. Hasilnya harus sama dengan Excel
untuk Maret: Dendi 21046.648 ℓ → 4.209.329,6; Wawan 2170.719 ℓ → 434.143,8.

**Sel di Excel yang TIDAK boleh disalin begitu saja:**
- S35 `=R35*21` dan W35 `=S35`: rumus sisa yang menghasilkan 0 dan tidak dipakai.
- P36 `=P35*8173.4785`: maksudnya tidak jelas. Konfirmasi ke pemilik sebelum dipakai.
- Kolom BA: rumus bantu.

Cek silang yang harus lulus tiap bulan (tampilkan peringatan bila tidak cocok):
1. AJ36 = AP35 (= AO35).
2. AV37 + AV38 = AV35, dan AW37 + AW38 = AW35.
3. Total pembelian (O35) = total terima (P35), kecuali ada pengiriman lintas bulan.

---

## 5. FORMAT TAMPILAN (menyerupai Excel)

Nilai di database presisi penuh; format hanya untuk tampilan:
- Volume (ℓ): `#,##0.000` (3 desimal). Negatif ditampilkan merah (T, S, F dan sejenis).
- Rupiah: `Rp` + `#,##0` tanpa desimal; negatif merah dengan tanda minus (U, AG, AO, AP).
- Stok CM (R): 2 desimal. Omset + Losses (AV): 2 desimal.
- Gaji (AW): Rupiah dengan 2 desimal (di Excel tampil mis. Rp 4.209.329,60).
- Tanggal (C, AQ, AR): `Hari, dd Bulan yyyy` dalam bahasa Indonesia.
- Total uang belum disetor (AP): Rupiah, negatif merah.
- Beku kolom tanggal dan judul agar tetap terlihat saat digulir.

Format angka Indonesia: titik sebagai pemisah ribuan, koma sebagai desimal.

---

## 6. FITUR: PEMANTAUAN LAPORAN HARIAN DI SUPERADMIN

**Tujuan:** superadmin memantau perkembangan laporan harian setiap gerai langsung dari halaman
yang tampil seperti REKAP Excel, tetapi lebih rapi dan mudah dibaca.

**Perilaku:**
1. Halaman "Pantauan Laporan Harian": pilih gerai, bulan, dan tahun.
2. Tabel memakai kolom dan judul bertingkat sama seperti Bagian 2 dan 3, satu baris per tanggal,
   baris total/rekap di bawah (Bagian 4).
3. Saat operator menyimpan laporan harian, baris tanggal itu **muncul otomatis** di halaman
   superadmin tanpa memuat ulang halaman. Baris bertambah satu per satu seiring laporan masuk.
4. Hari yang belum ada laporan tetap tampil sebagai baris kosong dengan penanda "Belum dilaporkan".
   Hari ini diberi sorotan. Tampilkan hitungan "X dari Y hari sudah dilaporkan".
5. Bila data hari lama diedit, semua baris sesudahnya ikut dihitung ulang (cascade) dan tampilan
   ikut berubah; baris yang berubah disorot sebentar.
6. Superadmin hanya melihat (read-only). Koreksi dilakukan lewat fitur edit laporan yang sudah ada,
   dan setiap perubahan dicatat (siapa, kapan, nilai lama dan baru).
7. Opsional: ringkasan semua gerai (status laporan hari ini: sudah/belum, penjualan hari ini,
   uang belum disetor) sebelum masuk ke detail satu gerai.

**Cara kerja yang disarankan:**
- Tahap 1 (sederhana): halaman memeriksa data baru tiap 15 sampai 30 detik (polling) lewat endpoint
  ringan yang mengembalikan hanya baris yang berubah sejak waktu terakhir (berdasarkan `updated_at`).
- Tahap 2 (opsional): siaran langsung (broadcasting/WebSocket) bila nanti dibutuhkan.
- Angka di halaman diambil dari layanan hitung yang sama dengan laporan harian (Aturan Emas No. 5).
  Halaman ini tidak menghitung sendiri.
- Tidak boleh mengganggu performa input operator; query dibatasi per gerai dan per bulan.

---

## 7. UJI KECOCOKAN DENGAN EXCEL (WAJIB)

1. Simpan data Excel acuan sebagai dataset uji (nilai sel INPUT) dan nilai Excel untuk semua sel HASIL
   (diambil sebagai nilai tersimpan, bukan dihitung ulang).
2. Jalankan input itu lewat model/service aplikasi pada database PostgreSQL khusus test.
3. Bandingkan **setiap sel harian untuk setiap hari** (semua kolom HASIL di Bagian 3), bukan hanya
   total. Toleransi: selisih mutlak < 0,000001 untuk angka, sama persis untuk teks (Pas/Losses/Gain).
4. Bandingkan juga semua baris rekap Bagian 4 dan gaji per operator.
5. Ulangi untuk Excel gerai lain begitu tersedia. Satu gerai = satu dataset uji.
6. Uji perubahan berantai: ubah input hari ke-10, pastikan hari 11 sampai akhir bulan berubah dan
   tetap cocok dengan hitung ulang penuh dari awal.
7. Uji parameter yang berbeda (harga, koefisien, komisi) untuk memastikan tidak ada angka tersembunyi
   di dalam kode.
8. Bila ada satu sel saja yang beda, perbaikan wajib dan uji diulang sebelum rilis.

Catatan hasil verifikasi saya: 713 sel harian (kolom D sampai AW) cocok 100% dengan nilai Excel
setelah rumus di Bagian 3 diterapkan (tanpa pembulatan di tengah perhitungan).
```

