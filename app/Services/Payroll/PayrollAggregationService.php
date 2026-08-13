<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\PayrollDetail;
use App\Models\PayrollSystem;
use App\Models\DailyReport;
use App\Models\Operator;
use App\Models\Shop;
use App\Models\EmployeeLoan;
use App\Models\Receivable;
use App\Services\Registry\BusinessRuleRegistryService;
use App\Services\Lock\PeriodLockService;
use App\Enums\ReportStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PayrollAggregationService — Phase C.3
 *
 * Agregator otomatis perhitungan THP (Take Home Pay) Operator.
 * Hanya membaca data dari Laporan Harian yang ter-APPROVED / LOCKED (Fase C.1)
 * dan tarif komisi liter dari BusinessRuleRegistry (Fase C.2).
 */
class PayrollAggregationService
{
    public function __construct(
        private readonly BusinessRuleRegistryService $registryService,
        private readonly PeriodLockService           $lockService,
        private readonly PayrollEligibilityChecker  $eligibilityChecker
    ) {}

    /**
     * Generate / Update Agregasi Payroll Operator Bulanan.
     */
    public function generateMonthlyPayroll(int $shopId, string $yearMonth, int $superAdminUserId): PayrollPeriod
    {
        $shop = Shop::findOrFail($shopId);
        $evalDate = Carbon::parse($yearMonth . '-01');
        $tahun = (int) $evalDate->format('Y');
        $bulan = (int) $evalDate->format('m');

        // Check Governance Eligibility (Phase C.1 Requirements)
        $eligibility = $this->eligibilityChecker->checkEligibility($shop->id, $yearMonth);
        if (!$eligibility['eligible']) {
            throw new \RuntimeException($eligibility['message']);
        }

        // Pastikan PayrollSystem ada untuk shop ini
        $system = PayrollSystem::firstOrCreate(
            ['shop_id' => $shop->id],
            [
                'nama_sistem' => 'Sistem Payroll Standar Pertashop',
                'gaji_pokok' => 1500000,
                'uang_transport' => 150000,
                'metode_komisi' => 'per_liter',
            ]
        );

        // 1. Ambil Laporan Harian Ter-Approved / Locked
        $reports = DailyReport::where('shop_id', $shop->id)
            ->whereDate('created_at', 'like', "{$yearMonth}%")
            ->whereIn('status_lifecycle', [ReportStatus::APPROVED, ReportStatus::LOCKED])
            ->get();

        // 2. Ambil Rate Komisi Liter & Rule Snapshot dari Business Rule Registry
        $ruleSnapshot = $this->registryService->getActiveSnapshot($evalDate);
        $payrollRate  = (float) ($ruleSnapshot['snapshot']['PAYROLL_RATE']['value'] ?? 200.0);

        return DB::transaction(function () use ($shop, $tahun, $bulan, $system, $reports, $ruleSnapshot, $payrollRate, $superAdminUserId) {
            // Cari / Buat Payroll Period
            $period = PayrollPeriod::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'tahun'   => $tahun,
                    'bulan'   => $bulan,
                ],
                [
                    'payroll_system_id'      => $system->id,
                    'status'                 => 'draft',
                    'approval_status'        => 'draft',
                    'rule_version_snapshot' => $ruleSnapshot,
                    'generated_by'           => $superAdminUserId,
                    'generated_at'           => now(),
                ]
            );

            // Ambil seluruh operator aktif di shop ini
            $operators = Operator::whereHas('shop', fn($q) => $q->where('shops.id', $shop->id))->get();
            if ($operators->isEmpty()) {
                $operators = Operator::all(); // Fallback jika belum di-assign shop spesifik
            }

            $totalVolShop = $reports->sum(fn($r) => (float) ($r->volume_penjualan ?? $r->volume_terjual ?? 0));

            foreach ($operators as $op) {
                // Alokasikan volume proporsional per operator
                $opVolume = $operators->count() > 0 ? ($totalVolShop / $operators->count()) : 0;
                $komisiLiter = $opVolume * $payrollRate;

                // Ambil Gaji Pokok & Transport
                $gajiPokok = (float) ($system->gaji_pokok ?? 1500000);
                $uangTransport = (float) ($system->uang_transport ?? 150000);

                // Potongan Kasbon Pinjaman Operator (EmployeeLoan)
                $loanDeduction = (float) EmployeeLoan::where('operator_id', $op->id)
                    ->sum('jumlah');

                // Potongan Kurang Setoran
                $shortageDeduction = 0.0;

                $gajiGross = $gajiPokok + $komisiLiter + $uangTransport;
                $thpBersih = max(0, $gajiGross - $loanDeduction - $shortageDeduction);

                PayrollDetail::updateOrCreate(
                    [
                        'payroll_period_id' => $period->id,
                        'operator_id'        => $op->id,
                    ],
                    [
                        'gaji_pokok'       => $gajiPokok,
                        'total_bonus'      => $komisiLiter,
                        'uang_transport'   => $uangTransport,
                        'gaji_kotor'       => $gajiGross,
                        'potongan_kasbon'  => $loanDeduction,
                        'kurang_setoran'   => $shortageDeduction,
                        'thp'              => $thpBersih,
                        'thp_pembulatan'   => round($thpBersih, -3),
                    ]
                );
            }

            return $period;
        });
    }

    /**
     * Sahkan (Approve) Rekap Payroll oleh Super Admin.
     */
    /**
     * Sahkan (Approve) Rekap Payroll oleh Super Admin.
     */
    public function approvePayroll(int $payrollPeriodId, int $superAdminUserId): PayrollPeriod
    {
        $period = PayrollPeriod::findOrFail($payrollPeriodId);

        $period->update([
            'status'          => 'final',
            'approval_status' => 'approved',
            'approved_by'     => $superAdminUserId,
            'approved_at'     => now(),
        ]);

        return $period;
    }

    /**
     * Catat Pembayaran Aktual THP Operator (Bank Transfer / Cash).
     */
    public function recordPayment(
        int    $payrollPeriodId,
        int    $operatorId,
        float  $amount,
        string $method,
        ?string $reference,
        int    $paidByUserId
    ): \App\Models\PayrollPayment {
        $period = PayrollPeriod::findOrFail($payrollPeriodId);

        $payment = \App\Models\PayrollPayment::create([
            'payroll_period_id' => $period->id,
            'operator_id'        => $operatorId,
            'payment_method'    => $method,
            'payment_reference' => $reference,
            'paid_amount'       => $amount,
            'paid_by'           => $paidByUserId,
            'paid_at'           => now(),
            'status'            => 'paid',
        ]);

        // Cek jika seluruh operator di detail sudah terbayar
        $totalDetails  = $period->details()->count();
        $totalPayments = \App\Models\PayrollPayment::where('payroll_period_id', $period->id)->count();

        if ($totalPayments >= $totalDetails) {
            $period->update([
                'status'          => 'final',
                'approval_status' => 'paid',
            ]);
        }

        return $payment;
    }
}
