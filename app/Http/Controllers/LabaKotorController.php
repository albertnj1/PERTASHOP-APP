<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Price;
use App\Models\DailyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\ReportController;
use App\Models\DailyReportPeriod;

class LabaKotorController extends Controller
{
    public static function getLabaKotor($shop_id = 1, $year_month = null)
    {
        if ($year_month == null) {
            $year_month = Carbon::now()->format('Y-m');
        }

        list($year, $month) = explode("-", $year_month);

        $segments = collect();
        $daily_reports = DailyReport::with(['periods.price', 'price'])
            ->where('shop_id', $shop_id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($daily_reports as $report) {
            if ($report->periods->isNotEmpty()) {
                $periodsCount = count($report->periods);
                foreach ($report->periods as $index => $period) {
                    $isLast = ($index === $periodsCount - 1);
                    $segments->push([
                        'report_id' => $report->id,
                        'price_id' => $period->price_id,
                        'harga_beli' => $period->price ? $period->price->harga_beli : 0,
                        'harga_jual' => $period->price ? $period->price->harga_jual : 0,
                        'totalisator_awal' => $period->totalisator_awal,
                        'totalisator_akhir' => $period->totalisator_akhir,
                        'volume_penjualan' => $period->totalisator_akhir - $period->totalisator_awal,
                        'test_pump' => $isLast ? $report->test_pump : 0,
                        'bbm_keluar_lain' => $isLast ? $report->bbm_keluar_lain : 0,
                        'penerimaan' => $isLast ? $report->penerimaan : 0,
                        'stik_akhir' => $isLast ? $report->stik_akhir : null,
                        'stok_akhir_aktual' => $isLast ? $report->stok_akhir_aktual : null,
                        'report' => $report
                    ]);
                }
            } else {
                $segments->push([
                    'report_id' => $report->id,
                    'price_id' => $report->price_id,
                    'harga_beli' => $report->price ? $report->price->harga_beli : 0,
                    'harga_jual' => $report->price ? $report->price->harga_jual : 0,
                    'totalisator_awal' => $report->totalisator_awal,
                    'totalisator_akhir' => $report->totalisator_akhir,
                    'volume_penjualan' => $report->volume_penjualan,
                    'test_pump' => $report->test_pump,
                    'bbm_keluar_lain' => $report->bbm_keluar_lain,
                    'penerimaan' => $report->penerimaan,
                    'stik_akhir' => $report->stik_akhir,
                    'stok_akhir_aktual' => $report->stok_akhir_aktual,
                    'report' => $report
                ]);
            }
        }

        $segments_group = $segments->groupBy('price_id');

        $reports = [];
        $i = 0;
        $stok_awal = Shop::find($shop_id)->kapasitas;

        foreach ($segments_group as $price_id => $group_segments) {
            $harga = Price::find($price_id);
            $harga_beli = $harga->harga_beli;
            $harga_jual = $harga->harga_jual;

            $sisa_stok_akhir = collect($group_segments)->whereNotNull('stok_akhir_aktual')->last() ? collect($group_segments)->whereNotNull('stok_akhir_aktual')->last()['stok_akhir_aktual'] : $stok_awal;
            $stik_akhir = collect($group_segments)->whereNotNull('stik_akhir')->last() ? collect($group_segments)->whereNotNull('stik_akhir')->last()['stik_akhir'] : 0;

            if ($i == 0) {
                $daily_report_sebelumnya = DailyReport::where('shop_id', $shop_id)->where('created_at', '<', collect($group_segments)->first()['report']->created_at)->latest()->first();
                $stok_awal = $daily_report_sebelumnya ? $daily_report_sebelumnya->stok_akhir_aktual : Shop::find($shop_id)->kapasitas;
                
                $stok_awal_harga_beli = $daily_report_sebelumnya ? $daily_report_sebelumnya->price->harga_beli : (Price::where('created_at', '<', collect($group_segments)->first()['report']->created_at)->latest()->first() ? Price::where('created_at', '<', collect($group_segments)->first()['report']->created_at)->latest()->first()->harga_beli : $harga_beli);
            } else {
                $stok_awal_harga_beli = Price::where('created_at', '<', collect($group_segments)->first()['report']->created_at)->latest()->first() ? Price::where('created_at', '<', collect($group_segments)->first()['report']->created_at)->latest()->first()->harga_beli : $harga_beli;
            }

            $stok_awal_rp = $stok_awal * $stok_awal_harga_beli;

            $datang = collect($group_segments)->sum('penerimaan');
            $count_datang = collect($group_segments)->where('penerimaan', '>', 0)->count();

            $test_pump = collect($group_segments)->sum('test_pump');
            $bbm_keluar_lain = collect($group_segments)->sum('bbm_keluar_lain');
            $volume_nosel = collect($group_segments)->sum('volume_penjualan');
            
            $penjualan_aktual = $volume_nosel - $test_pump - $bbm_keluar_lain;

            $omzet = $penjualan_aktual * $harga_jual;
            $hpp = $penjualan_aktual * $harga_beli;
            
            $laba_kotor = $omzet - $hpp;

            $stok_teoritis = $stok_awal + $datang - $volume_nosel;
            $losses_gain = $sisa_stok_akhir - $stok_teoritis;
            
            // Stok Fisik < Stok Teoritis -> Beban Losses
            $beban_losses_rp = $losses_gain < 0 ? abs($losses_gain) * $harga_beli : 0;
            // Stok Fisik > Stok Teoritis -> Pendapatan Gain
            $pendapatan_gain_rp = $losses_gain > 0 ? $losses_gain * $harga_beli : 0;
            
            $beban_test_pump_rp = $test_pump * $harga_beli;
            $beban_keluar_lain_rp = $bbm_keluar_lain * $harga_beli;

            // Gain dicatat secara terpisah, tidak otomatis mengurangi HPP atau menambah Laba Kotor
            // sesuai dengan arahan audit untuk diselaraskan dengan metode manual Excel.
            $laba_kotor = $omzet - $hpp;

            $persen_losses_gain = $penjualan_aktual != 0 ? $losses_gain / $penjualan_aktual * 100 : 0;

            $jumlah_hari = Carbon::createFromFormat('Y-m', $year_month)->daysInMonth;
            $rata_rata_omset_harian = $jumlah_hari > 0 ? $omzet / $jumlah_hari : 0;

            $reports[] = [
                'harga_beli' => $harga_beli,
                'harga_jual' => $harga_jual,
                'stok_awal' => $stok_awal,
                'stok_awal_harga_beli' => $stok_awal_harga_beli,
                'datang' => $datang,
                'count_datang' => $count_datang,
                'totalisator_akhir' => round(collect($group_segments)->last()['totalisator_akhir'], 2),
                'totalisator_awal' => round(collect($group_segments)->first()['totalisator_awal'], 2),
                'volume_nosel' => round($volume_nosel, 2),
                'test_pump' => round($test_pump, 2),
                'bbm_keluar_lain' => round($bbm_keluar_lain, 2),
                'penjualan_aktual' => round($penjualan_aktual, 2),
                'omzet' => round($omzet, 2),
                'hpp' => round($hpp, 2),
                'stok_teoritis' => round($stok_teoritis, 2),
                'persen_losses_gain' => abs(round($persen_losses_gain, 3)),
                'losses_gain' => round($losses_gain, 2),
                'beban_losses_rp' => round($beban_losses_rp, 2),
                'pendapatan_gain_rp' => round($pendapatan_gain_rp, 2),
                'beban_test_pump_rp' => round($beban_test_pump_rp, 2),
                'beban_keluar_lain_rp' => round($beban_keluar_lain_rp, 2),
                'stik_akhir' => round($stik_akhir, 2),
                'sisa_stok_akhir' => round($sisa_stok_akhir, 2),
                'laba_kotor' => round($laba_kotor, 2),
                'rata_rata_omset_harian' => round($rata_rata_omset_harian, 2),
            ];

            $stok_awal = $sisa_stok_akhir;
            $i++;
        }
        return collect($reports);
    }

    public static function getSummary($shop_id, $year_month = null)
    {

        if ($year_month == null) {
            $year_month = Carbon::now()->format('Y-m');
        }

        $reports = self::getLabaKotor($shop_id, $year_month);

        $sum_omzet = 0;
        $sum_hpp = 0;
        $sum_laba_kotor = 0;
        $total_penjualan_aktual = 0;
        $sum_beban_losses = 0;
        $sum_pendapatan_gain = 0;
        $sum_beban_test_pump = 0;
        $sum_beban_keluar_lain = 0;

        if ($reports->count() > 0) {
            $sum_omzet = $reports->sum('omzet');
            $sum_hpp = $reports->sum('hpp');
            $sum_laba_kotor = $reports->sum('laba_kotor');
            $total_penjualan_aktual = $reports->sum('penjualan_aktual');
            $sum_beban_losses = $reports->sum('beban_losses_rp');
            $sum_pendapatan_gain = $reports->sum('pendapatan_gain_rp');
            $sum_beban_test_pump = $reports->sum('beban_test_pump_rp');
            $sum_beban_keluar_lain = $reports->sum('beban_keluar_lain_rp');
        }

        $summary = [
            'sisa_stok_akhir' => $reports->last() ? $reports->last()['sisa_stok_akhir'] : Shop::find($shop_id)->kapasitas,
            'sisa_stok_akhir_rp' => $reports->last() ? ($reports->last()['sisa_stok_akhir'] * $reports->last()['harga_beli']) : 0,
            'omzet' => $sum_omzet,
            'hpp' => $sum_hpp,
            'laba_kotor' => $sum_laba_kotor,
            'rata_rata_omset_harian' => $reports->count() > 0 ? $reports->sum('rata_rata_omset_harian') / $reports->count() : 0,
            'penjualan_aktual' => $total_penjualan_aktual,
            'beban_losses_rp' => $sum_beban_losses,
            'pendapatan_gain_rp' => $sum_pendapatan_gain,
            'beban_test_pump_rp' => $sum_beban_test_pump,
            'beban_keluar_lain_rp' => $sum_beban_keluar_lain,
        ];

        return $summary;
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $shop_id = $request->input('shop_id');
            if (Auth::user()->role == 'investor') {
                $investor_shops = Auth::user()->investor?->shops->pluck('id')->toArray() ?? [];
                if (!$shop_id || !in_array($shop_id, $investor_shops)) {
                    $shop_id = reset($investor_shops) ?: 1;
                }
            } else {
                $shop_id = $shop_id ?: 1;
            }

            $salesQuery = \Illuminate\Support\Facades\DB::table('daily_reports')
                ->where('shop_id', $shop_id)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan")
                ->groupBy('bulan');

            $dt = DataTables::of($salesQuery)->addIndexColumn();

            $columnsToIgnore = ['hpp', 'omzet', 'rata_rata_omset_harian', 'sisa_stok_akhir', 'laba_kotor', 'beban_losses_rp', 'pendapatan_gain_rp', 'beban_test_pump_rp', 'beban_keluar_lain_rp'];
            foreach ($columnsToIgnore as $col) {
                $dt->filterColumn($col, function($query, $keyword) {});
                $dt->orderColumn($col, function($query, $order) {});
            }

            $dt->orderColumn('DT_RowIndex', function($query, $order) {
                $query->orderBy('bulan', $order);
            });

            $response = $dt->make(true)->getData(true);

            foreach ($response['data'] as &$row) {
                $summary = self::getSummary($shop_id, $row['bulan']);
                foreach ($summary as $key => $value) {
                    $row[$key] = $value;
                }
                $row['shop_id'] = $shop_id;
                $row['action'] = '<a href="' . route('laba-kotor.edit', ['shop_id' => $shop_id, 'year_month' => $row['bulan']]) . '" class="btn btn-sm btn-info" title="Detail"><i class="fa fa-list mr-1"></i> Detail</a>';
            }

            return response()->json($response);
        }

        $shops = Shop::all();
        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor->shops;
        }

        return view('laba_kotor.index', compact('shops'));
    }

    public function edit(string $shop_id, string $year_month)
    {

        $reports = self::getLabaKotor($shop_id, $year_month);

        $shop = Shop::find($shop_id);

        $date = Carbon::createFromFormat('Y-m', $year_month);

        return view('laba_kotor.edit', compact('shop', 'reports', 'date'));
    }
}
