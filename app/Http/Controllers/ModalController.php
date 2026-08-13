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

            $salesQuery = \Illuminate\Support\Facades\DB::table('daily_reports')
                ->where('shop_id', $shop_id)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan")
                ->groupBy('bulan');

            $dt = DataTables::of($salesQuery)->addIndexColumn();

            $columnsToIgnore = ['modal_akhir', 'ekuitas_aktual', 'selisih', 'status_balance'];
            foreach ($columnsToIgnore as $col) {
                $dt->filterColumn($col, function($query, $keyword) {});
                $dt->orderColumn($col, function($query, $order) {});
            }

            $dt->orderColumn('DT_RowIndex', function($query, $order) {
                $query->orderBy('bulan', $order);
            });

            $response = $dt->make(true)->getData(true);

            foreach ($response['data'] as &$row) {
                list($year, $month) = explode('-', $row['bulan']);
                $recap = \App\Models\CapitalRecap::where('shop_id', $shop_id)
                         ->where('tahun', $year)
                         ->where('bulan', $month)->first();
                         
                $row['modal_akhir'] = $recap ? $recap->posisi_akhir_modal : 0;
                
                // ASET
                $summary = \App\Http\Controllers\LabaKotorController::getSummary($shop_id, $row['bulan']);
                $sisa_stok_aktual_rp = $summary['sisa_stok_akhir_rp'] ?? 0;
                
                $reports = \App\Models\DailyReport::where('shop_id', $shop_id)
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)->get();
                $kas_tangan = $reports->groupBy('operator_id')->map(function($item) { return $item->last(); })->sum('belum_disetorkan');
                
                $endOfMonth = Carbon::create($year, $month)->endOfMonth();
                
                $piutang = \Illuminate\Support\Facades\DB::table('receivables')
                            ->where('shop_id', $shop_id)
                            ->where('tanggal', '<=', $endOfMonth)
                            ->selectRaw('SUM(jumlah_piutang - jumlah_dibayar) as total')->first()->total ?? 0;
                            
                $operator_ids = \App\Models\Operator::where('shop_id', $shop_id)->pluck('id');
                $kasbon = \Illuminate\Support\Facades\DB::table('employee_loans')
                            ->whereIn('operator_id', $operator_ids)
                            ->where('tanggal', '<=', $endOfMonth)
                            ->sum('jumlah');
                            
                // KEWAJIBAN
                $purchases = \App\Models\Purchase::where('shop_id', $shop_id)
                            ->where('created_at', '<=', $endOfMonth)
                            ->get();
                $hutang_do = 0;
                foreach ($purchases as $p) {
                    $paid = \Illuminate\Support\Facades\DB::table('purchase_payments')->where('purchase_id', $p->id)->where('tanggal_bayar', '<=', $endOfMonth)->sum('jumlah_bayar');
                    $hutang_do += ($p->total_bayar - $paid);
                }
                            
                $tabungan = \Illuminate\Support\Facades\DB::table('employee_savings')
                            ->whereIn('operator_id', $operator_ids)
                            ->where('tanggal', '<=', $endOfMonth)
                            ->selectRaw("SUM(CASE WHEN jenis = 'setoran' THEN jumlah ELSE -jumlah END) as total")->first()->total ?? 0;
                            
                $row['ekuitas_aktual'] = ($kas_tangan + $sisa_stok_aktual_rp + $piutang + $kasbon) - ($hutang_do + $tabungan);
                $row['selisih'] = $row['ekuitas_aktual'] - $row['modal_akhir'];
                
                if (round($row['selisih'], 2) == 0) {
                    $row['status_balance'] = '<span class="badge badge-success">BALANCE</span>';
                } else {
                    $row['status_balance'] = '<span class="badge badge-danger">TIDAK BALANCE</span>';
                }

                $row['shop_id'] = $shop_id;
                $row['action'] = '<a href="' . route('modal.edit', ['shop_id' => $shop_id, 'year_month' => $row['bulan']]) . '" class="btn btn-sm btn-info" title="Detail"><i class="fa fa-list mr-1"></i> Detail</a>';
            }

            return response()->json($response);
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
