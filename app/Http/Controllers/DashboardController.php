<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Shop;
use App\Models\Price;
use App\Http\Controllers\LabaBersihController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {

        if ($request->ajax()) {
            if (Auth::user()->role == 'admin') {
                $shop_id = Auth::user()->admin->shop_id;
            } elseif (Auth::user()->role == 'operator') {
                $shop_id = Auth::user()->operator->shop_id;
            } else {
                $shop_id = $request->input('shop_id');
            }

            $time_filter = $request->input('filter');

            $sales = $this->getSales($shop_id, $time_filter);

            $stocks = $this->getStocks($shop_id);

            $summaries = $this->getSummaries($shop_id);

            $total_penjualan_bersih_rp = 0;
            $total_pembelian_rp = 0;
            $total_laba_kotor = 0;
            $total_volume = 0;
            $total_losses_gain_vol = 0;
            $total_losses_gain_rp = 0;
            $total_laba_bersih = 0;
            $total_investor_share = 0;
            $total_corporate_share = 0;

            foreach ($summaries as $summary) {
                $total_penjualan_bersih_rp += $summary['jumlah_penjualan_bersih_rp'] ?? 0;
                $total_pembelian_rp += $summary['jumlah_pembelian_rp'] ?? 0;
                $total_laba_kotor += $summary['laba_kotor'] ?? 0;
                $total_volume += $summary['jumlah_penjualan'] ?? 0;
                $total_losses_gain_vol += $summary['total_losses_gain_vol'] ?? 0;
                $total_losses_gain_rp  += $summary['total_losses_gain_rp']  ?? 0;
                $total_laba_bersih     += $summary['laba_bersih']     ?? 0;
                $total_investor_share  += $summary['investor_share']  ?? 0;
                $total_corporate_share += $summary['corporate_share'] ?? 0;
            }

            return response()->json([
                'stocks' => $stocks,
                'sales' => $sales,
                'summaries' => $summaries,
                'totals' => [
                    'penjualan_bersih'   => $total_penjualan_bersih_rp,
                    'pembelian'          => $total_pembelian_rp,
                    'laba_kotor'         => $total_laba_kotor,
                    'volume'             => $total_volume,
                    'losses_gain_vol'    => $total_losses_gain_vol,
                    'losses_gain_rp'     => $total_losses_gain_rp,
                    'laba_bersih'        => $total_laba_bersih,
                    'investor_share'     => $total_investor_share,
                    'corporate_share'    => $total_corporate_share,
                ]
            ]);
        }

        $reports = LabaKotorController::getLabaKotor(1);

        $shops = Shop::all();
        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor->shops;
        }
        $recent_reports = DailyReport::with('shop')->latest()->limit(5)->get();
        $suppliers = \App\Models\Supplier::all();
        $data = [
            'reports' => $reports,
            'shops' => $shops,
            'recent_reports' => $recent_reports,
            'suppliers' => $suppliers
        ];

        if (Auth::user()->role === 'operator') {
            $operator_id = Auth::user()->operator->id;
            $shop_id = Auth::user()->operator->shop_id;
            $latest_report = DailyReport::where('shop_id', $shop_id)->latest()->first();
            $latest_incoming = \App\Models\Incoming::where('shop_id', $shop_id)->latest()->first();
            
            $today_report = DailyReport::where('shop_id', $shop_id)->whereDate('created_at', Carbon::today())->first();
            
            $report_time = $latest_report ? $latest_report->created_at : Carbon::minValue();
            $incoming_time = $latest_incoming ? $latest_incoming->created_at : Carbon::minValue();
            
            if ($incoming_time > $report_time) {
                $stok_akhir = $latest_incoming->stik_akhir * $latest_incoming->shop->skala;
            } else {
                $stok_akhir = $latest_report ? $latest_report->stok_akhir_aktual : 0;
            }

            $totalisator_akhir = $latest_report ? $latest_report->totalisator_akhir : 0;
            $belum_disetorkan = $latest_report ? $latest_report->belum_disetorkan : 0;
            
            $volume_penjualan = $today_report ? $today_report->volume_penjualan : 0;
            $rupiah_penjualan = $today_report ? $today_report->rupiah_penjualan : 0;

            $total_setor_kolektan = DailyReport::where('shop_id', $shop_id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('setor_kolektan');

            $active_price = \App\Models\Price::where(function($q) use ($shop_id) {
                $q->where('shop_id', $shop_id)->orWhereNull('shop_id');
            })->where('effective_at', '<=', now())
              ->orderBy('effective_at', 'desc')
              ->first();
            
            $harga_jual = $active_price ? $active_price->harga_jual : 0;

            return view('dashboard.operator', compact('belum_disetorkan', 'stok_akhir', 'totalisator_akhir', 'volume_penjualan', 'rupiah_penjualan', 'harga_jual', 'total_setor_kolektan', 'data'));
        }
        return view('dashboard.index', $data);
    }

    protected function getSummaries($shop_id)
    {
        $shops = Shop::query();

        if (\Illuminate\Support\Facades\Auth::user()->role === 'investor') {
            $shopIds = \Illuminate\Support\Facades\Auth::user()->investor->shops->pluck('id');
            $shops->whereIn('id', $shopIds);
        }

        if ($shop_id) {
            $shops->where('id', $shop_id);
        }

        $shops = $shops->get();

        $currentYearMonth = \Carbon\Carbon::now()->format('Y-m');
        $data = [];

        foreach ($shops as $index => $shop) {
            $summary = LabaKotorController::getSummary($shop->id, $currentYearMonth);
            $summary['shop'] = $shop;

            // --- Active Price (shop-specific first, then global fallback) ---
            $activePrice = Price::where('shop_id', $shop->id)
                ->where('effective_at', '<=', \Carbon\Carbon::now())
                ->orderBy('effective_at', 'desc')
                ->first();
            if (!$activePrice) {
                $activePrice = Price::whereNull('shop_id')
                    ->where('effective_at', '<=', \Carbon\Carbon::now())
                    ->orderBy('effective_at', 'desc')
                    ->first();
            }
            $summary['harga_jual_aktif']  = $activePrice ? $activePrice->harga_jual  : 0;
            $summary['harga_beli_aktif']  = $activePrice ? $activePrice->harga_beli  : 0;
            $summary['effective_at']      = $activePrice ? $activePrice->effective_at : null;

            // --- Gain / Losses: sum from getLabaKotor segments ---
            $labaKotorData = LabaKotorController::getLabaKotor($shop->id, $currentYearMonth);
            $total_losses_gain_vol  = $labaKotorData->sum('losses_gain');
            $summary['total_losses_gain_vol'] = $labaKotorData->sum(fn($r) => ($r['sisa_stok_akhir'] - $r['sisa_stok']));
            $summary['total_losses_gain_rp']  = $total_losses_gain_vol * ($activePrice ? $activePrice->harga_beli : 0);

            // --- Profit Sharing summary ---
            try {
                $labaBersih = LabaBersihController::getLabaBersih((string) $shop->id, $currentYearMonth);
                $investors = $shop->investors()->get();
                $investor_share = 0;
                foreach ($investors as $inv) {
                    $pct = $inv->pivot->persentase_keuntungan ?? 0;
                    $investor_share += $labaBersih['laba_bersih_dibagi'] * $pct / 100;
                }
                $summary['laba_bersih']       = $labaBersih['laba_bersih'];
                $summary['investor_share']    = round($investor_share, 2);
                $summary['corporate_share']   = round($labaBersih['laba_bersih_dibagi'] - $investor_share, 2);
            } catch (\Throwable $e) {
                $summary['laba_bersih']     = 0;
                $summary['investor_share']  = 0;
                $summary['corporate_share'] = 0;
            }

            // --- Kolektan & Belum Disetor ---
            $latest_report = \App\Models\DailyReport::where('shop_id', $shop->id)->latest()->first();
            $summary['belum_disetorkan'] = $latest_report ? $latest_report->belum_disetorkan : 0;

            $summary['total_setor_kolektan'] = \App\Models\DailyReport::where('shop_id', $shop->id)
                ->whereMonth('created_at', \Carbon\Carbon::now()->month)
                ->whereYear('created_at', \Carbon\Carbon::now()->year)
                ->sum('setor_kolektan');

            $data[$index] = $summary;
        }

        return $data;
    }

    protected function getStocks($shop_id)
    {
        $shops = Shop::query();

        if (\Illuminate\Support\Facades\Auth::user()->role === 'investor') {
            $shopIds = \Illuminate\Support\Facades\Auth::user()->investor->shops->pluck('id');
            $shops->whereIn('id', $shopIds);
        }

        if ($shop_id) {
            $shops->where('id', $shop_id);
        }

        $shops = $shops->get();

        $data = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Stok',
                    'data' => [],
                    'backgroundColor' => [], // Warna latar belakang untuk setiap bar
                ],
                [
                    'label' => 'Kapasitas',
                    'data' => [],
                    'backgroundColor' => [], // Warna latar belakang untuk setiap bar
                ],
            ]
        ];
        foreach ($shops as $shop) {
            $data['labels'][] = $shop->nama;
            
            $latest_report = DailyReport::where('shop_id', $shop->id)->latest()->first();
            $latest_incoming = \App\Models\Incoming::where('shop_id', $shop->id)->latest()->first();
            
            $report_time = $latest_report ? $latest_report->created_at : Carbon::minValue();
            $incoming_time = $latest_incoming ? $latest_incoming->created_at : Carbon::minValue();
            
            if ($incoming_time > $report_time) {
                $stok_akhir = $latest_incoming->stik_akhir * $latest_incoming->shop->skala;
            } elseif ($latest_report) {
                $stok_akhir = $latest_report->stok_akhir_aktual;
            } else {
                $stok_akhir = 0; // Or Shop::find($shop->id)->kapasitas if default is full capacity
            }
            
            $data['datasets'][0]['data'][] = $stok_akhir;
            $data['datasets'][1]['data'][] = Shop::find($shop->id)->kapasitas;
            // Tentukan warna berdasarkan kondisi stok kurang dari 1500
            if ($stok_akhir < 1500) {
                $data['datasets'][0]['backgroundColor'][] = '#ff4d5e'; // Warna merah jika stok kurang
            } else {
                $data['datasets'][0]['backgroundColor'][] = '#2ed573'; // Warna hijau jika stok cukup
            }
            $data['datasets'][1]['backgroundColor'][] = '#e9ecef';
        }


        return $data;
    }


    protected function getSales($shop_id, $time_filter)
    {
        $shops = Shop::query();

        if (\Illuminate\Support\Facades\Auth::user()->role === 'investor') {
            $shopIds = \Illuminate\Support\Facades\Auth::user()->investor->shops->pluck('id');
            $shops->whereIn('id', $shopIds);
        }

        if ($shop_id) {
            $shops->where('id', $shop_id);
        }

        $shops = $shops->get();

        $labels = [];
        $datasets = [];
        foreach ($shops as $index => $shop) {
            if ($time_filter === 'month') {
                $startMonth = Carbon::now()->startOfYear();
                $endMonth = Carbon::now()->endOfYear();
                $currentMonth = $startMonth->copy();

                $sales = DailyReport::where('shop_id', $shop->id)
                    ->where('created_at', '>=', $startMonth)
                    ->oldest()
                    ->get()
                    ->groupBy(function ($item) {
                        return $item->created_at->format('M Y');
                    });

                while ($currentMonth <= $endMonth) {
                    $formattedMonth = $currentMonth->format('M Y');
                    if (!isset($sales[$formattedMonth])) {
                        $sales[$formattedMonth] = [];
                    }
                    $currentMonth->addMonth();
                }

                $sortedSales = $sales->toArray();
                uksort($sortedSales, function ($a, $b) {
                    $dateA = Carbon::createFromFormat('M Y', $a);
                    $dateB = Carbon::createFromFormat('M Y', $b);
                    return $dateA < $dateB ? -1 : ($dateA > $dateB ? 1 : 0);
                });
            } elseif ($time_filter === 'week') {
                $startDateOfMonth = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $currentDate = $startDateOfMonth->copy();

                $sales = DailyReport::where('shop_id', $shop->id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->oldest()
                    ->get()
                    ->groupBy(function ($item) use ($startDateOfMonth) {
                        $daysDiff = $item->created_at->diffInDays($startDateOfMonth);
                        $weekOfMonth = ceil(($daysDiff + $startDateOfMonth->dayOfWeek) / 7);
                        return "Minggu ke-" . $weekOfMonth;
                    });

                while ($currentDate <= $endDate) {
                    $daysDiff = $currentDate->diffInDays($startDateOfMonth);
                    $weekOfMonth = ceil(($daysDiff + $startDateOfMonth->dayOfWeek) / 7);
                    $formattedDate = "Minggu ke-" . $weekOfMonth;
                    if (!isset($sales[$formattedDate])) {
                        $sales[$formattedDate] = [];
                    }
                    $currentDate->addWeek();
                }

                $sortedSales = $sales->toArray();
                ksort($sortedSales);
            } else {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $currentDate = $startDate->copy();

                $sales = DailyReport::where('shop_id', $shop->id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->oldest()
                    ->get()
                    ->groupBy(function ($item) {
                        return $item->created_at->format('d M');
                    });

                while ($currentDate <= $endDate) {
                    $formattedDate = $currentDate->format('d M');
                    if (!isset($sales[$formattedDate])) {
                        $sales[$formattedDate] = [];
                    }
                    $currentDate->addDay();
                }

                $sortedSales = $sales->toArray();
                ksort($sortedSales);
            }

            $color = ['#3867d6', '#20bf6b', '#fa8231', '#ea2027', '#8854d0'];

            $sortedSales = collect($sortedSales)->map(function ($items, $key) use (&$labels, $index) { // Menggunakan $key untuk mendapatkan kunci
                if ($index == 0) {
                    $labels[] = $key; // Menambahkan kunci ke dalam array labels
                }
                $totalAmount = collect($items)->sum('volume_penjualan'); // Hitung jumlah penjualan per bulan

                return $totalAmount;
            });
            $datasets[] = [
                'label' => $shop->nama,
                'data' => collect($sortedSales)->values(),
                'backgroundColor' => $color[$index % count($color)]
            ];
        }

        $data = [
            'labels' => $labels,
            'datasets' => $datasets,
        ];

        return $data;
    }
}
