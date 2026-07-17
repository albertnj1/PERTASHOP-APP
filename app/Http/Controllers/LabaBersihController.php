<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Spending;
use App\Models\LabaBersih;
use App\Models\DailyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\SpendingCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\LabaKotorController;

class LabaBersihController extends Controller
{
    public static function getLabaBersih(string $shop_id, string $year_month)
    {
        $summary = LabaKotorController::getSummary($shop_id, $year_month);

        $laba_kotor = $summary['laba_kotor'];

        list($year, $month) = explode("-", $year_month);
        $spendings = Spending::with(['shop'])
            ->where('shop_id', $shop_id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)->orderBy('spending_category_id')
            ->get()->groupBy('spending_category_id');

        //spendings to array
        $spendings = $spendings->map(function ($value, $key) {
            $category = SpendingCategory::find($key)->nama;
            return ['pengeluaran' => $category, 'jumlah' => $value->sum('jumlah')];
        });


        $laba_bersih = LabaBersih::where('shop_id', $shop_id)->whereYear('created_at', $year)->whereMonth('created_at', $month)->first();

        $persentase_alokasi_modal = $laba_bersih ? $laba_bersih->persentase_alokasi_modal : 10;

        $total_biaya = $spendings->sum('jumlah');
        $laba_bersih = $laba_kotor - $total_biaya;
        $alokasi_modal = $persentase_alokasi_modal / 100 * $laba_bersih;
        $laba_bersih_dibagi = $laba_bersih - $alokasi_modal;

        return [
            'laba_kotor' => $laba_kotor,
            'total_biaya' => $total_biaya,
            'laba_bersih' => $laba_bersih,
            'persentase_alokasi_modal' => $persentase_alokasi_modal,
            'alokasi_modal' => $alokasi_modal,
            'laba_bersih_dibagi' => $laba_bersih_dibagi,
        ];
    }


    public function index(Request $request)
    {

        if ($request->ajax()) {
            $shop_id = $request->input('shop_id', 1);

            $sales = DailyReport::where('shop_id', $shop_id)->get()->groupBy(function ($item) {
                return $item->created_at->format('Y-m');
            });

            $data = $sales->map(function ($value, $key) use ($shop_id) {

                $report = self::getLabaBersih($shop_id, $key);
                $report['shop_id'] = $shop_id;
                $report['bulan'] = $key;

                return $report;
            });

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $button = '<a href="' . route('laba-bersih.edit', ['shop_id' => $row['shop_id'], 'year_month' => $row['bulan']]) . '" class="btn btn-sm btn-info" title="Detail"><i class="fa fa-list mr-1"></i> Detail</a>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $shops = Shop::all();
        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor->shops;
        }

        return view('laba_bersih.index', compact('shops'));
    }

    public function edit(string $shop_id, string $year_month)
    {

        $report = self::getLabaBersih($shop_id, $year_month);
        $shop =  Shop::with(['investors.user'])->find($shop_id);
        $date =  Carbon::createFromFormat('Y-m', $year_month);

        list($year, $month) = explode("-", $year_month);

        $spendings = Spending::with(['shop'])
            ->where('shop_id', $shop_id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)->orderBy('spending_category_id')
            ->get()->groupBy('spending_category_id');

        //spendings to array
        $spendings = $spendings->map(function ($value, $key) {
            $category = SpendingCategory::find($key)->nama;
            return ['pengeluaran' => $category, 'jumlah' => $value->sum('jumlah')];
        });

        return view('laba_bersih.edit', compact('report', 'shop', 'date', 'spendings'));
    }

    public function alokasi_modal(Request $request, string $shop_id, string $year_month)
    {
        list($year, $month) = explode("-", $year_month);

        $laba_bersih = LabaBersih::where('shop_id', $shop_id)->whereYear('created_at', $year)->whereMonth('created_at', $month)->first();

        if ($laba_bersih) {
            $laba_bersih->update(['persentase_alokasi_modal' => $request->input('persentase_alokasi_modal')]);
        } else {
            LabaBersih::create([
                'shop_id' => $shop_id,
                'persentase_alokasi_modal' => $request->input('persentase_alokasi_modal'),
                'created_at' => Carbon::createFromFormat('Y-m', $year_month),
            ]);
        }

        return redirect()->route('laba-bersih.edit', ['shop_id' => $shop_id, 'year_month' => $year_month]);
    }
}
