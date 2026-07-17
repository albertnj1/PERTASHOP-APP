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

            $sales = DailyReport::where('shop_id', $shop_id)->get()->groupBy(function ($item) {
                return $item->created_at->format('Y-m');
            });

            $shop = Shop::find($shop_id);

            $data = $sales->map(function ($value, $bulan) use ($shop) {

                $laba_bersih = LabaBersihController::getLabaBersih($shop->id, $bulan);

                $investor_profit = [];

                foreach ($shop->investors as $key => $investor) {
                    $investor_profit[strtolower(str_replace(' ', '_', $investor->user->name))] = $laba_bersih['laba_bersih_dibagi'] * $investor->pivot->persentase_keuntungan / 100;
                }

                $data = [
                    'shop_id' => $shop->id,
                    'bulan' => $bulan,
                    'alokasi_modal' => 0,
                    'profit_sharing' => 0,
                    'sisa_keuntungan' => 0,
                    'roi' => 0,
                ];

                return collect($data)->merge($investor_profit)->toArray();
            });

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $button = '<a href="' . route('profit-sharing.edit', ['shop_id' => $row['shop_id'], 'year_month' => $row['bulan']]) . '" class="btn btn-sm btn-info" title="Detail"><i class="fa fa-list mr-1"></i> Detail</a>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $shops = Shop::all();
        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor->shops;
        }

        if (Auth::user()->role == 'admin') {
            $shop_id = Auth::user()->admin->shop_id;
        } {
            $shop_id = $request->input('shop_id', 1);
        }

        $investors = Shop::find($shop_id)->investors->load('user');
        return view('profit_sharing.index', compact('shops', 'investors'));
    }
}
