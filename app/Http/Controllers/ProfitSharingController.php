<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\DailyReport;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ProfitSharingController extends Controller
{
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $shop_id = $request->input('shop_id', 1);

            $dateFormat = \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql'
                ? "TO_CHAR(created_at, 'YYYY-MM')"
                : (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')");

            $salesQuery = \Illuminate\Support\Facades\DB::table('daily_reports')
                ->where('shop_id', $shop_id)
                ->selectRaw("{$dateFormat} as bulan")
                ->groupBy('bulan');

            $dt = DataTables::of($salesQuery)->addIndexColumn();
            
            $shop = Shop::find($shop_id) ?? Shop::first();
            $investorKeys = [];
            if ($shop) {
                foreach ($shop->investors as $investor) {
                    $investorKeys[] = 'inv_' . $investor->id;
                }
            }

            $columnsToIgnore = array_merge(['alokasi_modal', 'profit_sharing', 'sisa_keuntungan', 'payback_sisa', 'persentase_pengembalian'], $investorKeys);
            foreach ($columnsToIgnore as $col) {
                $dt->filterColumn($col, function($query, $keyword) {});
                $dt->orderColumn($col, function($query, $order) {});
            }

            $dt->orderColumn('DT_RowIndex', function($query, $order) {
                $query->orderBy('bulan', $order);
            });

            $response = $dt->make(true)->getData(true);
            
            $accumulated_profit_sharing = 0;

            foreach ($response['data'] as &$row) {
                $laba_bersih = LabaBersihController::getLabaBersih($shop ? $shop->id : $shop_id, $row['bulan']);
                $investor_profit = [];
                
                $total_investasi_awal = 0;

                if ($shop) {
                    foreach ($shop->investors as $key => $investor) {
                        $investor_profit['inv_' . $investor->id] = $laba_bersih['laba_bersih_dibagi'] * $investor->pivot->persentase / 100;
                        $total_investasi_awal += $investor->pivot->nominal;
                    }
                }
                
                $accumulated_profit_sharing += $laba_bersih['laba_bersih_dibagi'];

                $data = [
                    'shop_id' => $shop ? $shop->id : $shop_id,
                    'bulan' => $row['bulan'],
                    'alokasi_modal' => $laba_bersih['alokasi_modal'],
                    'profit_sharing' => $laba_bersih['laba_bersih_dibagi'],
                    'sisa_keuntungan' => 0, // Jika ada sisa
                    'payback_sisa' => max(0, $total_investasi_awal - $accumulated_profit_sharing),
                    'persentase_pengembalian' => $total_investasi_awal > 0 ? ($accumulated_profit_sharing / $total_investasi_awal * 100) : 0,
                ];

                $merged = collect($data)->merge($investor_profit)->toArray();
                foreach ($merged as $k => $v) {
                    $row[$k] = $v;
                }
                $row['action'] = '<a href="' . route('profit-sharing.edit', ['shop_id' => ($shop ? $shop->id : $shop_id), 'year_month' => $row['bulan']]) . '" class="btn btn-sm btn-info" title="Detail"><i class="fa fa-list mr-1"></i> Detail</a>';
            }

            return response()->json($response);
        }

        $shops = Shop::all();
        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor?->shops ?? collect();
        }

        if (Auth::user()->role == 'admin') {
            $shop_id = Auth::user()->admin ? Auth::user()->admin->shop_id : ($shops->first()?->id ?? 1);
        } else {
            $shop_id = $request->input('shop_id', $shops->first()?->id ?? 1);
        }

        $currentShop = Shop::find($shop_id) ?? $shops->first();
        $investors = $currentShop ? $currentShop->investors->load('user') : collect();
        return view('profit_sharing.index', compact('shops', 'investors'));
    }

    public function edit(string $shop_id, string $year_month)
    {
        $shop = Shop::find($shop_id) ?? Shop::first();
        if (!$shop) {
            return redirect()->route('profit-sharing.index')->with('error', 'Outlet tidak ditemukan.');
        }

        $date = \Carbon\Carbon::createFromFormat('Y-m', $year_month);

        $daily_reports = DailyReport::where('shop_id', $shop->id)
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->get();

        $latestReport = DailyReport::where('shop_id', $shop->id)
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->latest()->first();

        $harga_beli = 0;
        if ($latestReport) {
            if ($latestReport->periods()->exists()) {
                $latestPeriod = $latestReport->periods()->latest()->first();
                $harga_beli = $latestPeriod && $latestPeriod->price ? $latestPeriod->price->harga_beli : 0;
            } else {
                $harga_beli = $latestReport->price ? $latestReport->price->harga_beli : 0;
            }
        }

        $purchases = Purchase::where('shop_id', $shop->id)->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)->latest()->first();

        $sisa_do = $purchases ? $purchases->sisa : 0;
        $rupiah_sisa_do = $sisa_do * $harga_beli;

        $report_by_operator = $daily_reports->groupBy('operator_id')->map(function ($item) {
            return $item->last();
        });

        $belum_disetorkan = $report_by_operator->sum('belum_disetorkan');

        $laba_bersih = LabaBersihController::getLabaBersih($shop->id, $year_month);
        $alokasi_keuntungan = $laba_bersih['alokasi_modal'];
        $profit_sharing = $laba_bersih['laba_bersih_dibagi'];

        return view('profit_sharing.edit', compact('shop', 'date', 'alokasi_keuntungan', 'profit_sharing', 'belum_disetorkan', 'sisa_do', 'rupiah_sisa_do', 'harga_beli'));
    }
}
