<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\PayrollPeriod;
use App\Models\PayrollDetail;
use App\Models\PeriodLock;
use App\Services\Payroll\PayrollAggregationService;
use App\Services\Payroll\PayrollEligibilityChecker;
use App\Services\Registry\BusinessRuleRegistryService;
use App\Enums\ReportStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PayrollAggregationController extends Controller
{
    public function __construct(
        private readonly PayrollAggregationService  $aggregationService,
        private readonly PayrollEligibilityChecker  $eligibilityChecker,
        private readonly BusinessRuleRegistryService $registryService
    ) {}

    /**
     * Dashboard Rekap Payroll & THP Aggregation Center.
     */
    public function index(Request $request)
    {
        $shops = Shop::all();
        $selectedShopId = $request->shop_id ?? ($shops->first()?->id ?? 1);
        $selectedMonth  = $request->year_month ?? now()->format('Y-m');

        $evalDate = Carbon::parse($selectedMonth . '-01');
        $tahun = (int) $evalDate->format('Y');
        $bulan = (int) $evalDate->format('m');

        $shop = Shop::findOrFail($selectedShopId);

        // Period lock status dari Fase C.1
        $isLocked = PeriodLock::isLocked($shop->id, $selectedMonth);

        // Governance Eligibility Checker
        $eligibility = $this->eligibilityChecker->checkEligibility($shop->id, $selectedMonth);

        // Payroll period teragregasi
        $payrollPeriod = PayrollPeriod::with(['details.operator', 'approver'])
            ->where('shop_id', $shop->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        // Snapshot aturan bisnis terversi dari Fase C.2
        $ruleSnapshot = $payrollPeriod?->rule_version_snapshot ?? $this->registryService->getActiveSnapshot($evalDate);
        $payrollRate  = (float) ($ruleSnapshot['snapshot']['PAYROLL_RATE']['value'] ?? 200.0);

        return view('payrolls.aggregation.index', compact(
            'shops', 'shop', 'selectedShopId', 'selectedMonth',
            'isLocked', 'eligibility', 'payrollPeriod', 'ruleSnapshot', 'payrollRate'
        ));
    }

    /**
     * Trigger Komputasi & Agregasi THP Operator (Super Admin Only).
     */
    public function generate(Request $request)
    {
        $request->validate([
            'shop_id'    => 'required|exists:shops,id',
            'year_month' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            $period = $this->aggregationService->generateMonthlyPayroll(
                $request->shop_id,
                $request->year_month,
                $user->id
            );

            return back()->with('success', "⚡ Rekap Payroll & THP Operator periode {$request->year_month} berhasil dihitung secara otomatis.");
        } catch (\Throwable $e) {
            return back()->withErrors(['payroll' => $e->getMessage()]);
        }
    }

    /**
     * Pengesahan Rekap Payroll oleh Super Admin (Approve).
     */
    public function approve(Request $request)
    {
        $request->validate([
            'payroll_period_id' => 'required|exists:payroll_periods,id',
        ]);

        try {
            $user = Auth::user();
            $this->aggregationService->approvePayroll($request->payroll_period_id, $user->id);

            return back()->with('success', "✅ Rekap Payroll resmi DISAHKAN (APPROVED) oleh Super Admin.");
        } catch (\Throwable $e) {
            return back()->withErrors(['payroll' => $e->getMessage()]);
        }
    }

    /**
     * Export / Preview Slip Gaji PDF Operator.
     */
    public function exportSlipPdf($detailId)
    {
        $detail = PayrollDetail::with(['operator', 'period.shop'])->findOrFail($detailId);
        
        return view('payrolls.aggregation.slip_pdf', compact('detail'));
    }
}
