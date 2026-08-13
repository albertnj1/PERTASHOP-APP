<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\DailyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BenchmarkingController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        [$year, $month] = explode('-', $selectedMonth);

        $shops = Shop::all();

        $benchmarks = $shops->map(function ($shop) use ($year, $month) {
            $reports = DailyReport::where('shop_id', $shop->id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            $totalVol     = $reports->sum('volume_penjualan_teoritis');
            $totalRupiah  = $reports->sum('rupiah_penjualan');
            $totalCost    = $reports->sum('total_spendings');
            $totalProfit  = $reports->sum('pendapatan');
            $totalLosses  = $reports->sum('losses_gain');
            $avgDailyVol  = $reports->count() > 0 ? round($totalVol / $reports->count(), 1) : 0;
            $profitPerLiter = $totalVol > 0 ? round($totalProfit / $totalVol, 0) : 0;

            return [
                'shop_id'          => $shop->id,
                'nama'             => $shop->nama,
                'total_volume'     => $totalVol,
                'avg_daily_volume' => $avgDailyVol,
                'total_rupiah'     => $totalRupiah,
                'total_cost'       => $totalCost,
                'total_profit'     => $totalProfit,
                'total_losses'     => $totalLosses,
                'profit_per_liter' => $profitPerLiter,
                'total_reports'    => $reports->count(),
            ];
        })->sortByDesc('total_volume')->values();

        return view('analytics.benchmarking', compact(
            'benchmarks',
            'selectedMonth',
            'year',
            'month'
        ));
    }
}
