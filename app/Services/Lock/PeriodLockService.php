<?php

namespace App\Services\Lock;

use App\Models\PeriodLock;
use App\Models\DailyReport;
use App\Models\User;
use App\Enums\ReportStatus;
use App\Services\Approval\ReportApprovalService;
use Illuminate\Support\Facades\DB;

/**
 * PeriodLockService
 *
 * Mengelola Kunci Imutabilitas Periode Bulanan Per-Outlet.
 * Kunci hard-lock memastikan data pasca-approval menjadi 100% read-only.
 */
class PeriodLockService
{
    public function __construct(
        private readonly ReportApprovalService $approvalService
    ) {}

    /**
     * Kunci resmi periode bulanan oleh Owner/System.
     */
    public function lockPeriod(int $shopId, string $yearMonth, User $owner, string $lockType = 'hard'): PeriodLock
    {
        return DB::transaction(function () use ($shopId, $yearMonth, $owner, $lockType) {
            $lock = PeriodLock::updateOrCreate(
                ['shop_id' => $shopId, 'year_month' => $yearMonth],
                [
                    'lock_type' => $lockType,
                    'is_locked' => true,
                    'locked_by' => $owner->id,
                    'locked_at' => now(),
                    'reopen_reason' => null,
                ]
            );

            // Batch transition semua laporan ke status LOCKED
            $this->approvalService->transitionMonthlyBatch($shopId, $yearMonth, ReportStatus::LOCKED, $owner);

            return $lock;
        });
    }

    /**
     * Buka kembali kunci periode bulanan (Owner Only dengan Alasan Mandatory).
     */
    public function reopenPeriod(int $shopId, string $yearMonth, User $owner, string $reason): PeriodLock
    {
        if (empty(trim($reason))) {
            throw new \InvalidArgumentException("Alasan (Reason) wajib diisi untuk membuka kembali kunci periode (Reopen Period).");
        }

        return DB::transaction(function () use ($shopId, $yearMonth, $owner, $reason) {
            $lock = PeriodLock::where('shop_id', $shopId)
                ->where('year_month', $yearMonth)
                ->firstOrFail();

            $lock->update([
                'is_locked' => false,
                'reopen_reason' => $reason,
            ]);

            // Batch transition semua laporan ke status REOPENED
            $this->approvalService->transitionMonthlyBatch($shopId, $yearMonth, ReportStatus::REOPENED, $owner, $reason);

            return $lock;
        });
    }

    /**
     * Helper: Cek apakah periode sedang terkunci.
     */
    public function isLocked(int $shopId, string $yearMonth): bool
    {
        return PeriodLock::isLocked($shopId, $yearMonth);
    }
}
