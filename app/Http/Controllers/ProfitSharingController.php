<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\DailyReport;
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

            $salesQuery = \Illuminate\Support\Facades\DB::table('daily_reports')
                ->where('shop_id', $shop_id)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan")
                ->groupBy('bulan');

            $dt = DataTables::of($salesQuery)->addIndexColumn();
            
            $shop = Shop::find($shop_id);
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
                $laba_bersih = LabaBersihController::getLabaBersih($shop->id, $row['bulan']);
                $investor_profit = [];
                
                $total_investasi_awal = 0;

                foreach ($shop->investors as $key => $investor) {
                    $investor_profit['inv_' . $investor->id] = $laba_bersih['laba_bersih_dibagi'] * $investor->pivot->persentase / 100;
                    $total_investasi_awal += $investor->pivot->nominal;
                }
                
                $accumulated_profit_sharing += $laba_bersih['laba_bersih_dibagi'];

                $data = [
                    'shop_id' => $shop->id,
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
                $row['action'] = '<a href="' . route('profit-sharing.edit', ['shop_id' => $shop->id, 'year_month' => $row['bulan']]) . '" class="btn btn-sm btn-info" title="Detail"><i class="fa fa-list mr-1"></i> Detail</a>';
            }

            return response()->json($response);
        }

        $shops = Shop::all();
        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor->shops;
        }

        if (Auth::user()->role == 'admin') {
            $shop_id = Auth::user()->admin->shop_id;
        } else {
            $shop_id = $request->input('shop_id', 1);
        }

        $investors = Shop::find($shop_id)->investors->load('user');
        return view('profit_sharing.index', compact('shops', 'investors'));
    }
}
