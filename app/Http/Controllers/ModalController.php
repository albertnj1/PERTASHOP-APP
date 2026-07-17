<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Purchase;
use App\Models\DailyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ModalController extends Controller
{

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $shop_id = $request->input('shop_id', 1);

            $sales = DailyReport::where('shop_id', $shop_id)->get()->groupBy(function ($item) {
                return $item->created_at->format('Y-m');
            });

            $data = $sales->map(function ($value, $key) use ($shop_id) {
                return [
                    'shop_id' => $shop_id,
                    'bulan' => $key,
                    'modal_awal' => 0,
                    'rugi' => 0,
                    'pajak_bank' => 0,
                    'alokasi_keuntungan' => 0,
                    'bunga_bank' => 0,
                    'modal_akhir' => 0,
                ];
            });

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $button = '<a href="' . route('modal.edit', ['shop_id' => $row['shop_id'], 'year_month' => $row['bulan']]) . '" class="btn btn-sm btn-info" title="Detail"><i class="fa fa-list mr-1"></i> Detail</a>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $shops = Shop::all();
        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor->shops;
        }


        return view('modal.index', compact('shops'));
    }

    public function edit(string $shop_id, string $year_month)
    {
        $shop = Shop::find($shop_id);
        $date = Carbon::createFromFormat('Y-m', $year_month);

        $daily_reports = DailyReport::where('shop_id', $shop_id)
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->get();

        $latestReport = DailyReport::where('shop_id', $shop_id)
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

        $purchases = Purchase::where('shop_id', $shop_id)->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)->latest()->first();

        $sisa_do = $purchases ? $purchases->sisa : 0;

        $rupiah_sisa_do = $sisa_do * $harga_beli;

        $report_by_operator = $daily_reports->groupBy('operator_id');

        //get latest daily report each operator
        $report_by_operator = $report_by_operator->map(function ($item) {
            return $item->last();
        });

        $belum_disetorkan = $report_by_operator->sum('belum_disetorkan');


        $laba_bersih = LabaBersihController::getLabaBersih($shop_id, $year_month);
        $alokasi_keuntungan = $laba_bersih['alokasi_modal'];
        $profit_sharing = $laba_bersih['laba_bersih_dibagi'];

        return view('modal.edit', compact('shop', 'date', 'alokasi_keuntungan', 'profit_sharing', 'belum_disetorkan', 'sisa_do', 'rupiah_sisa_do', 'harga_beli'));
    }
}
