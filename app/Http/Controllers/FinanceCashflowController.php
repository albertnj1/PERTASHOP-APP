<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\DailyReport;
use App\Models\Spending;
use App\Models\CapitalRecap;
use App\Models\Purchase;
use App\Services\StockPredictionService;
use App\Services\AnomalyDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class FinanceCashflowController extends Controller
{
    public function index(Request $request)
    {
        $shops = Shop::all();
        if (Auth::user()->role === 'admin') {
            $shops = Shop::where('id', Auth::user()->admin->shop_id)->get();
        } elseif (Auth::user()->role === 'investor') {
            $shops = Auth::user()->investor?->shops ?? collect();
        }

        $selectedShopId = $request->input('shop_id', $shops->first()?->id);
        $selectedMonth  = $request->input('bulan', Carbon::now()->format('Y-m'));
        [$year, $month] = explode('-', $selectedMonth);

        // Daily Reports untuk toko & bulan terpilih
        $reports = DailyReport::where('shop_id', $selectedShopId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'asc')
            ->get();

        // Agregat Cashflow
        $totalPendapatan  = $reports->sum('pendapatan');
        $totalRupiahSales = $reports->sum('rupiah_penjualan');
        $totalSpendings   = $reports->sum('total_spendings');
        $totalDisetorkan  = $reports->sum('disetorkan');
        $totalLossesGain  = $reports->sum('losses_gain');

        // Capital Recap terkini
        $capitalRecap = CapitalRecap::where('shop_id', $selectedShopId)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->first();

        // Pembelian BBM (Purchase) bulan ini
        $purchases = Purchase::where('shop_id', $selectedShopId)
            ->whereYear('purchase_date', $year)
            ->whereMonth('purchase_date', $month)
            ->get();
        $totalPurchasesNominal = $purchases->sum('total_harga');

        // Prediksi Stok BBM & Order Date
        $stockPrediction = StockPredictionService::predictForShop($selectedShopId);

        // Anomaly Scan pada laporan bulan ini
        $anomalies = [];
        foreach ($reports as $rep) {
            $check = AnomalyDetectionService::check($rep);
            if ($check['is_anomalous']) {
                $anomalies[] = [
                    'report_id' => $rep->id,
                    'tanggal'   => Carbon::parse($rep->created_at)->format('d M Y'),
                    'reasons'   => $check['reasons'],
                ];
            }
        }

        return view('finance.cashflow_dashboard', compact(
            'shops',
            'selectedShopId',
            'selectedMonth',
            'reports',
            'totalPendapatan',
            'totalRupiahSales',
            'totalSpendings',
            'totalDisetorkan',
            'totalLossesGain',
            'capitalRecap',
            'purchases',
            'totalPurchasesNominal',
            'stockPrediction',
            'anomalies'
        ));
    }
}
