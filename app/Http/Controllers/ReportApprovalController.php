<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\DailyReport;
use App\Models\PeriodLock;
use App\Models\ReportApprovalHistory;
use App\Services\Approval\ReportApprovalService;
use App\Services\Lock\PeriodLockService;
use App\Enums\ReportStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportApprovalController extends Controller
{
    public function __construct(
        private readonly ReportApprovalService $approvalService,
        private readonly PeriodLockService     $lockService
    ) {}

    /**
     * Approval Dashboard & Audit Timeline Widget.
     */
    public function index(Request $request)
    {
        $shops = Shop::all();
        $selectedShopId = $request->shop_id ?? ($shops->first()?->id ?? 1);
        $selectedMonth  = $request->year_month ?? now()->format('Y-m');

        $shop = Shop::findOrFail($selectedShopId);

        // Ambil daily reports periode ini
        $reports = DailyReport::where('shop_id', $shop->id)
            ->whereDate('created_at', 'like', "{$selectedMonth}%")
            ->orderBy('created_at', 'asc')
            ->get();

        // Lock status
        $isLocked = PeriodLock::isLocked($shop->id, $selectedMonth);
        $periodLockObj = PeriodLock::where('shop_id', $shop->id)->where('year_month', $selectedMonth)->first();

        // Audit Histories Timeline
        $approvalHistories = ReportApprovalHistory::with('actor')
            ->where('shop_id', $shop->id)
            ->where('year_month', $selectedMonth)
            ->orderBy('created_at', 'desc')
            ->get();

        // Ringkasan Agregat
        $totalVol    = $reports->sum(fn($r) => (float) ($r->volume_penjualan ?? $r->volume_terjual ?? 0));
        $totalRupiah = $reports->sum(fn($r) => (float) ($r->rupiah_penjualan ?? $r->pendapatan_operator ?? 0));
        $estPayroll  = $totalVol * 200; // Est rate 200

        // Overall Lifecycle Status
        $firstReport = $reports->first();
        $currentStatus = $firstReport->status_lifecycle ?? ReportStatus::DRAFT;

        return view('reports.approval.index', compact(
            'shops', 'shop', 'selectedShopId', 'selectedMonth',
            'reports', 'isLocked', 'periodLockObj', 'approvalHistories',
            'totalVol', 'totalRupiah', 'estPayroll', 'currentStatus'
        ));
    }

    /**
     * Batch Transisi Status (Validate / Approve).
     */
    public function transitionBatch(Request $request)
    {
        $request->validate([
            'shop_id'    => 'required|exists:shops,id',
            'year_month' => 'required|string',
            'to_status'  => 'required|string',
            'reason'     => 'nullable|string',
        ]);

        try {
            $actor = Auth::user();
            $res = $this->approvalService->transitionMonthlyBatch(
                $request->shop_id,
                $request->year_month,
                $request->to_status,
                $actor,
                $request->reason
            );

            return back()->with('success', $res['message']);
        } catch (\Throwable $e) {
            return back()->withErrors(['approval' => $e->getMessage()]);
        }
    }

    /**
     * Lock Period (Owner / Supervisor).
     */
    public function lockPeriod(Request $request)
    {
        $request->validate([
            'shop_id'    => 'required|exists:shops,id',
            'year_month' => 'required|string',
        ]);

        try {
            $actor = Auth::user();
            $this->lockService->lockPeriod($request->shop_id, $request->year_month, $actor);

            return back()->with('success', "🔒 Periode {$request->year_month} berhasil TERKUNCI (LOCKED) secara resmi.");
        } catch (\Throwable $e) {
            return back()->withErrors(['lock' => $e->getMessage()]);
        }
    }

    /**
     * Reopen Period (Owner Only with Mandatory Reason).
     */
    public function reopenPeriod(Request $request)
    {
        $request->validate([
            'shop_id'    => 'required|exists:shops,id',
            'year_month' => 'required|string',
            'reason'     => 'required|string|min:5',
        ]);

        try {
            $actor = Auth::user();
            $this->lockService->reopenPeriod($request->shop_id, $request->year_month, $actor, $request->reason);

            return back()->with('success', "🔓 Kunci periode {$request->year_month} dibuka kembali (REOPENED) untuk revisi.");
        } catch (\Throwable $e) {
            return back()->withErrors(['reopen' => $e->getMessage()]);
        }
    }
}
