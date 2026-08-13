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

        $categories = SpendingCategory::all()->keyBy('id');

        //spendings to array
        $spendings = $spendings->map(function ($value, $key) use ($categories) {
            $category = $categories->has($key) ? $categories[$key]->nama : 'Lainnya';
            return ['pengeluaran' => $category, 'jumlah' => $value->sum('jumlah')];
        })->values();

        // Tambahkan Beban dari Laba Kotor
        if (isset($summary['beban_losses_rp']) && $summary['beban_losses_rp'] > 0) {
            $spendings->push(['pengeluaran' => 'Beban Losses Fisik BBM', 'jumlah' => $summary['beban_losses_rp']]);
        }
        if (isset($summary['beban_test_pump_rp']) && $summary['beban_test_pump_rp'] > 0) {
            $spendings->push(['pengeluaran' => 'Beban Test Pump', 'jumlah' => $summary['beban_test_pump_rp']]);
        }
        if (isset($summary['beban_keluar_lain_rp']) && $summary['beban_keluar_lain_rp'] > 0) {
            $spendings->push(['pengeluaran' => 'Beban BBM Keluar Lain', 'jumlah' => $summary['beban_keluar_lain_rp']]);
        }

        $laba_bersih = LabaBersih::where('shop_id', $shop_id)->whereYear('created_at', $year)->whereMonth('created_at', $month)->first();

        $persentase_alokasi_modal = $laba_bersih ? $laba_bersih->persentase_alokasi_modal : 10;

        $total_biaya = $spendings->sum('jumlah');
        $laba_bersih_val = $laba_kotor - $total_biaya;
        $alokasi_modal = $persentase_alokasi_modal / 100 * $laba_bersih_val;
        $laba_bersih_dibagi = $laba_bersih_val - $alokasi_modal;

        return [
            'laba_kotor' => $laba_kotor,
            'total_biaya' => $total_biaya,
            'laba_bersih' => $laba_bersih_val,
            'persentase_alokasi_modal' => $persentase_alokasi_modal,
            'alokasi_modal' => $alokasi_modal,
            'laba_bersih_dibagi' => $laba_bersih_dibagi,
        ];
    }


    public function index(Request $request)
    {

        if ($request->ajax()) {
            $shop_id = $request->input('shop_id', 1);

            $salesQuery = \Illuminate\Support\Facades\DB::table('daily_reports')
                ->where('shop_id', $shop_id)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan")
                ->groupBy('bulan');

            $dt = DataTables::of($salesQuery)->addIndexColumn();

            $columnsToIgnore = ['laba_kotor', 'total_biaya', 'laba_bersih', 'persentase_alokasi_modal', 'alokasi_modal', 'laba_bersih_dibagi'];
            foreach ($columnsToIgnore as $col) {
                $dt->filterColumn($col, function($query, $keyword) {});
                $dt->orderColumn($col, function($query, $order) {});
            }

            $dt->orderColumn('DT_RowIndex', function($query, $order) {
                $query->orderBy('bulan', $order);
            });

            $response = $dt->make(true)->getData(true);

            foreach ($response['data'] as &$row) {
                $report = self::getLabaBersih($shop_id, $row['bulan']);
                foreach ($report as $key => $value) {
                    $row[$key] = $value;
                }
                $row['shop_id'] = $shop_id;
                $row['action'] = '<a href="' . route('laba-bersih.edit', ['shop_id' => $shop_id, 'year_month' => $row['bulan']]) . '" class="btn btn-sm btn-info" title="Detail"><i class="fa fa-list mr-1"></i> Detail</a>';
            }

            return response()->json($response);
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
