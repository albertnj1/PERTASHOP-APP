<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\PayrollDailySplit;
use App\Models\PayrollDetail;
use App\Models\PayrollOperatorAssignment;
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
        // 1. Ambil sistem penggajian aktif untuk toko ini
        $payrollSystem = PayrollSystem::where('shop_id', $shopId)
            ->where('aktif', true)
            ->latest()
            ->firstOrFail();

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

        // 4. Ambil semua operator yang di-assign ke sistem penggajian ini di bulan tsb
        $periodeAwal  = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->toDateString();
        $periodeAkhir = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();

        $operatorIds = PayrollOperatorAssignment::where('payroll_system_id', $payrollSystem->id)
            ->aktifPadaTanggal($periodeAwal)
            ->pluck('operator_id')
            ->unique();

        if ($operatorIds->isEmpty()) {
            throw new \Exception("Belum ada operator yang di-assign ke sistem penggajian toko ini.");
        }

        // 5. Ambil shift schedules bulan ini untuk tahu siapa bertugas kapan
        $shiftSchedules = ShiftSchedule::where('shop_id', $shopId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->whereIn('operator_id', $operatorIds)
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
            $volDihitung   = $payrollSystem->hitungVolumeDihitung($volumeAktual, $lossesGain);
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

        $loanTotals = DB::table('employee_loans')
            ->whereIn('operator_id', $operatorIds)
            ->where('status', 'approved')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->groupBy('operator_id')
            ->select('operator_id', DB::raw('SUM(jumlah) as total'))
            ->get()
            ->keyBy('operator_id');

        // 8. Wrap dalam DB transaction
        return DB::transaction(function () use (
            $existingPeriod, $shopId, $bulan, $tahun, $payrollSystem,
            $operatorTotals, $dailySplitsData, $totalPenjualanLiter,
            $attendanceRecaps, $savingsTotals, $loanTotals, $generatedBy
        ) {
            // Hapus periode lama jika ada (draft)
            if ($existingPeriod) {
                $existingPeriod->dailySplits()->delete();
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
                $literBagian    = round($totals['liter'], 2);
                $ratePerLiter   = ($payrollSystem->ada_rate_per_liter ?? true) ? floatval($payrollSystem->rate_per_liter) : 0.0;
                $gajiVariable   = round($literBagian * $ratePerLiter, 2);

                if ($payrollSystem->metode_split === 'flat_bulanan_prorata_hari') {
                    $standarHariKerja = $payrollSystem->standar_hari_kerja ?: 26;
                    $nominalGajiPokok = $payrollSystem->ada_gaji_pokok ? floatval($payrollSystem->nominal_gaji_pokok) : 0.0;
                    $rateHarian       = $standarHariKerja > 0 ? ($nominalGajiPokok / $standarHariKerja) : 0.0;
                    $gajiPokok        = round($rateHarian * $totals['hari'], 2);
                } else {
                    $gajiPokok        = $payrollSystem->ada_gaji_pokok ? floatval($payrollSystem->nominal_gaji_pokok) : 0.0;
                }

                // === Uang Transport ===
                // Dihitung per hari kerja, TIDAK masuk basis prorata potongan alpha.
                $rateTransport = floatval($payrollSystem->rate_transport_per_hari ?? 0);
                $uangTransport = round($totals['hari'] * $rateTransport, 2);

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
                    $potonganAlpha     = round($jumlahAlpha * $rateHarianDinamis, 2);
                } else {
                    // Potongan Alpha Nominal Tetap (default, backward-compatible)
                    $potonganAlpha = $jumlahAlpha * floatval($payrollSystem->potongan_per_hari_alpha);
                }

                // Auto-pull kurang setoran
                $kurangSetoran = round($kurangSetoranPerOperator[$opId] ?? 0.0, 2);

                // Auto-pull tabungan setoran
                $tabunganSetoran = $savingsTotals->get($opId) ? floatval($savingsTotals->get($opId)->total) : 0.0;

                // Auto-pull potongan hutang / kasbon yang sudah APPROVED
                $potonganHutang  = $loanTotals->get($opId) ? floatval($loanTotals->get($opId)->total) : 0.0;

                // THP = (Gaji_Pokok + Gaji_Variable + Uang_Transport) - Total_Potongan
                // Uang transport masuk Total_Tambahan, TIDAK masuk basis prorata alpha
                $gajiKotor     = $gajiPokok + $gajiVariable + $uangTransport;
                $totalPotongan = $potonganAlpha + $kurangSetoran + $tabunganSetoran + $potonganHutang;
                $thp           = round($gajiKotor - $totalPotongan, 2);
                $sisaKurangBayar = $thp < 0 ? abs($thp) : 0.0;

                PayrollDetail::create([
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
            }

            return $period->load(['details.operator.user', 'payrollSystem', 'shop']);
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
