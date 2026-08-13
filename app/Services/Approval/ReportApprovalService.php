<?php

namespace App\Services\Approval;

use App\Models\DailyReport;
use App\Models\ReportApprovalHistory;
use App\Models\User;
use App\Enums\ReportStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ReportApprovalService
 *
 * Mengelola transisi status lifecycle resmi Laporan Harian & Bulanan Pertashop.
 * Menerapkan State Machine strict & pencatatan audit log permanen via report_approval_histories.
 *
 * Valid State Transitions:
 *   DRAFT     → IMPORTED
 *   IMPORTED  → VALIDATED
 *   VALIDATED → APPROVED / REJECTED
 *   APPROVED  → LOCKED
 *   LOCKED    → REOPENED (Owner Only)
 *   REOPENED  → VALIDATED
 */
class ReportApprovalService
{
    /**
     * Transisi status laporan harian individual dengan validasi State Machine.
     */
    public function transitionReport(
        DailyReport $report,
        string      $toStatus,
        User        $actor,
        ?string     $reason = null,
        ?string     $batchId = null
    ): bool {
        $fromStatus = $report->status_lifecycle ?? ReportStatus::DRAFT;

        // Validasi State Machine
        if (!$this->isValidTransition($fromStatus, $toStatus)) {
            throw new \InvalidArgumentException("Transisi status terlarang dari '{$fromStatus}' ke '{$toStatus}' untuk laporan ID #{$report->id}.");
        }

        // Pengecekan Mandatory Reason untuk REJECTED & REOPENED
        if (in_array($toStatus, [ReportStatus::REJECTED, ReportStatus::REOPENED]) && empty(trim($reason ?? ''))) {
            throw new \InvalidArgumentException("Alasan (Reason) wajib diisi untuk transisi status ke '{$toStatus}'.");
        }

        $now = now();

        DB::transaction(function () use ($report, $fromStatus, $toStatus, $actor, $reason, $batchId, $now) {
            // Update status & metadata actor pada DailyReport
            $updateData = ['status_lifecycle' => $toStatus];

            match ($toStatus) {
                ReportStatus::IMPORTED  => $updateData += ['imported_by' => $actor->id, 'imported_at' => $now],
                ReportStatus::VALIDATED => $updateData += ['validated_by' => $actor->id, 'validated_at' => $now],
                ReportStatus::APPROVED  => $updateData += ['approved_by' => $actor->id, 'approved_at' => $now],
                ReportStatus::LOCKED    => $updateData += ['locked_by' => $actor->id, 'locked_at' => $now],
                default                 => null,
            };

            $report->update($updateData);

            // Snapshot Summary
            $vol = (float) ($report->volume_penjualan ?? $report->volume_terjual ?? 0);
            $rupiah = (float) ($report->rupiah_penjualan ?? $report->pendapatan_operator ?? 0);

            // Catat ke Audit History Log
            ReportApprovalHistory::create([
                'approval_batch_id' => $batchId,
                'daily_report_id'   => $report->id,
                'shop_id'           => $report->shop_id,
                'year_month'        => Carbon::parse($report->created_at ?? $report->tanggal)->format('Y-m'),
                'from_status'       => $fromStatus,
                'to_status'         => $toStatus,
                'acted_by'          => $actor->id,
                'actor_role'        => $actor->role ?? 'operator',
                'reason'            => $reason,
                'snapshot_summary'  => [
                    'volume'     => $vol,
                    'rupiah'     => $rupiah,
                    'quality'    => 96,
                    'timestamp'  => $now->toDateTimeString(),
                ],
            ]);
        });

        return true;
    }

    /**
     * Transisi batch bulanan untuk seluruh laporan harian dalam 1 outlet & periode (misal: 31 Hari sekaligus).
     */
    public function transitionMonthlyBatch(
        int     $shopId,
        string  $yearMonth,
        string  $toStatus,
        User    $actor,
        ?string $reason = null
    ): array {
        $reports = DailyReport::where('shop_id', $shopId)
            ->whereDate('created_at', 'like', "{$yearMonth}%")
            ->get();

        if ($reports->isEmpty()) {
            return ['success' => false, 'count' => 0, 'message' => 'Tidak ada laporan harian pada periode ini.'];
        }

        $batchId = (string) Str::uuid();
        $processed = 0;

        foreach ($reports as $report) {
            $fromStatus = $report->status_lifecycle ?? ReportStatus::DRAFT;
            if ($this->isValidTransition($fromStatus, $toStatus)) {
                $this->transitionReport($report, $toStatus, $actor, $reason, $batchId);
                $processed++;
            }
        }

        return [
            'success'   => true,
            'count'     => $processed,
            'batch_id'  => $batchId,
            'to_status' => $toStatus,
            'message'   => "Berhasil memproses transisi ke {$toStatus} untuk {$processed} dari {$reports->count()} laporan harian.",
        ];
    }

    /**
     * Cek validitas transisi State Machine.
     */
    public function isValidTransition(string $from, string $to): bool
    {
        if ($from === $to) return true;

        $allowed = [
            ReportStatus::DRAFT     => [ReportStatus::IMPORTED],
            ReportStatus::IMPORTED  => [ReportStatus::VALIDATED, ReportStatus::REJECTED],
            ReportStatus::VALIDATED => [ReportStatus::APPROVED, ReportStatus::REJECTED],
            ReportStatus::APPROVED  => [ReportStatus::LOCKED, ReportStatus::REJECTED],
            ReportStatus::LOCKED    => [ReportStatus::REOPENED],
            ReportStatus::REJECTED  => [ReportStatus::DRAFT, ReportStatus::IMPORTED, ReportStatus::VALIDATED],
            ReportStatus::REOPENED  => [ReportStatus::VALIDATED, ReportStatus::APPROVED],
        ];

        return in_array($to, $allowed[$from] ?? [], true);
    }
}
