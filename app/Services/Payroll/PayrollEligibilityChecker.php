<?php

namespace App\Services\Payroll;

use App\Models\DailyReport;
use App\Enums\ReportStatus;

class PayrollEligibilityChecker
{
    /**
     * Periksa apakah periode laporan harian toko ini ELIGIBLE (layak) untuk di-generate Payroll.
     * Syarat: Seluruh Laporan Harian periode ini harus berstatus APPROVED atau LOCKED.
     *
     * @return array ['eligible' => bool, 'unapproved_count' => int, 'message' => string]
     */
    public function checkEligibility(int $shopId, string $yearMonth): array
    {
        $reports = DailyReport::where('shop_id', $shopId)
            ->whereDate('created_at', 'like', "{$yearMonth}%")
            ->get();

        if ($reports->isEmpty()) {
            return [
                'eligible'         => false,
                'unapproved_count' => 0,
                'message'          => "❌ Tidak ada laporan harian yang ditemukan untuk outlet ini pada periode {$yearMonth}.",
            ];
        }

        $unapprovedReports = $reports->filter(function ($r) {
            return !in_array($r->status_lifecycle, [ReportStatus::APPROVED, ReportStatus::LOCKED]);
        });

        if ($unapprovedReports->count() > 0) {
            return [
                'eligible'         => false,
                'unapproved_count' => $unapprovedReports->count(),
                'message'          => "🚫 DITOLAK: Terdapat {$unapprovedReports->count()} laporan harian yang belum di-APPROVE atau LOCKED oleh Super Admin pada periode {$yearMonth}. Seluruh laporan harus disahkan sebelum payroll dapat dihitung.",
            ];
        }

        return [
            'eligible'         => true,
            'unapproved_count' => 0,
            'message'          => "🟢 ELIGIBLE: Seluruh laporan harian periode {$yearMonth} telah ter-APPROVED / LOCKED resmi.",
        ];
    }
}
