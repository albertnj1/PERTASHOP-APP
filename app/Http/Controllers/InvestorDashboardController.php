<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\DailyReport;
use App\Models\PayrollPeriod;
use App\Services\Lock\PeriodLockService;
use App\Services\Organization\InvestorOwnershipService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class InvestorDashboardController extends Controller
{
    public function __construct(
        private readonly PeriodLockService         $lockService,
        private readonly InvestorOwnershipService $ownershipService
    ) {}

    /**
     * Executive Investor Financial Dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user() ?? \App\Models\User::first();
        $shops = $this->ownershipService->getAccessibleShops($user);
        $accessibleShopIds = $shops->pluck('id')->toArray();

        $selectedShopId = $request->shop_id ?? 'all';
        $selectedMonth  = $request->year_month ?? now()->format('Y-m');

        $evalDate = Carbon::parse($selectedMonth . '-01');
        $tahun = (int) $evalDate->format('Y');
        $bulan = (int) $evalDate->format('m');

        // Filter Daily Reports terbatas pada outlet yang di-assign ke investor ini
        $queryReports = DailyReport::whereDate('created_at', 'like', "{$selectedMonth}%")
            ->whereIn('shop_id', $accessibleShopIds);

        if ($selectedShopId !== 'all' && in_array((int)$selectedShopId, $accessibleShopIds)) {
            $queryReports->where('shop_id', $selectedShopId);
        }
        $reports = $queryReports->get();

        // Agregasi Finansial Utama untuk Investor
        $totalVolume  = $reports->sum(fn($r) => (float) ($r->volume_penjualan ?? $r->volume_terjual ?? 0));
        $totalRevenue = $reports->sum(fn($r) => (float) ($r->rupiah_penjualan ?? $r->pendapatan_operator ?? 0));

        // Agregasi Payroll Periods
        $queryPayroll = PayrollPeriod::with('details')
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->whereIn('shop_id', $accessibleShopIds);

        if ($selectedShopId !== 'all' && in_array((int)$selectedShopId, $accessibleShopIds)) {
            $queryPayroll->where('shop_id', $selectedShopId);
        }
        $payrollPeriods = $queryPayroll->get();

        $totalPayrollTHP = $payrollPeriods->sum(function ($p) {
            return $p->details->sum('thp');
        });

        // Estimasi Gross Margin (Est 8% dari Omset)
        $estMargin = $totalRevenue * 0.08;
        $estNettProfit = max(0, $estMargin - $totalPayrollTHP);

        // Status Penguncian Periode
        $isPeriodLocked = ($selectedShopId !== 'all') 
            ? PeriodLock::isLocked((int)$selectedShopId, $selectedMonth)
            : false;

        return view('investor.dashboard', compact(
            'shops', 'selectedShopId', 'selectedMonth',
            'totalVolume', 'totalRevenue', 'totalPayrollTHP',
            'estMargin', 'estNettProfit', 'isPeriodLocked', 'payrollPeriods'
        ));
    }
}
