<?php

namespace App\Observers;

use App\Models\DailyReport;
use App\Models\PeriodLock;
use App\Enums\ReportStatus;
use Illuminate\Support\Carbon;

class DailyReportObserver
{
    /**
     * Mencegah perubahan data pada DailyReport jika periode bulanan sudah ter-LOCKED.
     */
    public function updating(DailyReport $report): bool
    {
        return $this->guardImmutability($report);
    }

    /**
     * Mencegah penghapusan data pada DailyReport jika periode bulanan sudah ter-LOCKED.
     */
    public function deleting(DailyReport $report): bool
    {
        return $this->guardImmutability($report);
    }

    private function guardImmutability(DailyReport $report): bool
    {
        $date = $report->created_at ? Carbon::parse($report->created_at)->format('Y-m') : now()->format('Y-m');

        if (PeriodLock::isLocked($report->shop_id, $date)) {
            // Kecuali jika transisi status disetujui melalui service resmi (misal: Reopen oleh Owner)
            if ($report->isDirty('status_lifecycle') && $report->status_lifecycle === ReportStatus::REOPENED) {
                return true;
            }

            throw new \RuntimeException("🔒 ERROR: Laporan harian untuk outlet ID {$report->shop_id} periode {$date} sudah TERKUNCI (LOCKED) secara resmi oleh Owner. Data tidak dapat diubah atau dihapus.");
        }

        return true;
    }
}
