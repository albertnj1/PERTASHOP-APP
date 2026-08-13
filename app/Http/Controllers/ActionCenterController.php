<?php

namespace App\Http\Controllers;

use App\Models\ShiftSwap;
use App\Models\DailyReport;
use App\Models\EmployeeLoan;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActionCenterController extends Controller
{
    /**
     * Tampilkan Pusat Tindakan (Action Center) terpadu dengan scoping role & shop.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $shopId = null;

        if ($user->role === 'admin') {
            $shopId = $user->admin?->shop_id;
        } elseif ($request->filled('shop_id') && $user->role === 'super-admin') {
            $shopId = $request->shop_id;
        }

        $shops = Shop::all();

        // 1. Shift Swaps Pending
        $swapQuery = ShiftSwap::with(['shiftSchedule.shop', 'operatorAsal.user', 'operatorPengganti.user'])
            ->where('status', 'pending');

        if ($shopId) {
            $swapQuery->whereHas('shiftSchedule', fn($q) => $q->where('shop_id', $shopId));
        }
        $pendingSwaps = $swapQuery->latest()->get();

        // 2. Daily Reports Unverified
        $unverifiedQuery = DailyReport::with(['shop', 'operator.user'])
            ->where('diverifikasi', false);

        if ($shopId) {
            $unverifiedQuery->where('shop_id', $shopId);
        }
        $unverifiedReports = $unverifiedQuery->latest()->limit(20)->get();

        // 3. Daily Reports dengan Selisih Setoran Kurang (Shortfall)
        $shortfallQuery = DailyReport::with(['shop', 'operator.user'])
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->get()
            ->filter(fn($dr) => $dr->selisih_setoran < 0);

        if ($shopId) {
            $shortfallReports = $shortfallQuery->filter(fn($dr) => $dr->shop_id == $shopId)->values();
        } else {
            $shortfallReports = $shortfallQuery->values();
        }

        // 4. Employee Loans Pending Approval
        $loanQuery = EmployeeLoan::with(['operator.user', 'operator.shop'])
            ->where('status', 'pending');

        if ($shopId) {
            $loanQuery->whereHas('operator', fn($q) => $q->where('shop_id', $shopId));
        }
        $pendingLoans = $loanQuery->latest()->get();

        $totalActionItems = $pendingSwaps->count() + $unverifiedReports->count() + $shortfallReports->count() + $pendingLoans->count();

        return view('action_center.index', compact(
            'shops',
            'shopId',
            'pendingSwaps',
            'unverifiedReports',
            'shortfallReports',
            'pendingLoans',
            'totalActionItems'
        ));
    }
}
