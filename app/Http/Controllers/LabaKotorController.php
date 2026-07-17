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
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($daily_reports as $report) {
            if ($report->periods()->exists()) {
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

            $last_segment_with_stock = collect($group_segments)->whereNotNull('stok_akhir_aktual')->last();
            $sisa_stok_akhir = $last_segment_with_stock ? $last_segment_with_stock['stok_akhir_aktual'] : $stok_awal;
            $stik_akhir = $last_segment_with_stock ? $last_segment_with_stock['stik_akhir'] : 0;

            if ($i == 0) {
                $daily_report_sebelumnya = DailyReport::where('shop_id', $shop_id)
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', '<', $month)
                    ->latest()->first();
                $stok_awal = $daily_report_sebelumnya ? $daily_report_sebelumnya->stok_akhir_aktual : Shop::find($shop_id)->kapasitas;
                
                $first_segment_report = $group_segments->first()['report'];
                $stok_awal_harga_beli = $daily_report_sebelumnya ? $daily_report_sebelumnya->price->harga_beli : (Price::where('created_at', '<', $first_segment_report->created_at)->latest()->first() ? Price::where('created_at', '<', $first_segment_report->created_at)->latest()->first()->harga_beli : $harga_beli);
            } else {
                $first_segment_report = $group_segments->first()['report'];
                $stok_awal_harga_beli = Price::where('created_at', '<', $first_segment_report->created_at)->latest()->first() ? Price::where('created_at', '<', $first_segment_report->created_at)->latest()->first()->harga_beli : $harga_beli;
            }

            $stok_awal_rp = $stok_awal * $stok_awal_harga_beli;

            $datang = collect($group_segments)->sum('penerimaan');
            $count_datang = collect($group_segments)->where('penerimaan', '>', 0)->count();

            $jumlah_pembelian = $stok_awal + $datang;
            $jumlah_pembelian_rp = $stok_awal_rp + $datang * $harga_beli;

            $test_pump = collect($group_segments)->sum('test_pump');
            $jumlah_penjualan = collect($group_segments)->sum('volume_penjualan');
            $jumlah_penjualan_rp = $jumlah_penjualan * $harga_jual;

            $sisa_stok = $jumlah_pembelian - $jumlah_penjualan;
            $sisa_stok_rp = $sisa_stok * $harga_beli;

            $losses_gain = $sisa_stok_akhir - $sisa_stok;
            $persen_losses_gain = $jumlah_penjualan != 0 ? $losses_gain / $jumlah_penjualan * 100 : 0;
            $losses_gain_rp = $losses_gain * $harga_beli;

            $jumlah_penjualan_bersih_rp = $jumlah_penjualan_rp + $sisa_stok_rp + $losses_gain_rp;
            $laba_kotor = $jumlah_penjualan_bersih_rp - $jumlah_pembelian_rp;

            $jumlah_hari = Carbon::createFromFormat('Y-m', $year_month)->daysInMonth;
            $rata_rata_omset_harian = $jumlah_hari > 0 ? $jumlah_penjualan / $jumlah_hari : 0;

            $reports[] = [
                'harga_beli' => $harga_beli,
                'harga_jual' => $harga_jual,
                'stok_awal' => $stok_awal,
                'stok_awal_harga_beli' => $stok_awal_harga_beli,
                'datang' => $datang,
                'count_datang' => $count_datang,
                'jumlah_pembelian' => $jumlah_pembelian,
                'jumlah_pembelian_rp' => round($jumlah_pembelian_rp, 2),
                'totalisator_akhir' => round(collect($group_segments)->last()['totalisator_akhir'], 2),
                'totalisator_awal' => round(collect($group_segments)->first()['totalisator_awal'], 2),
                'total_penjualan' => round($jumlah_penjualan + $test_pump, 2),
                'test_pump' => round($test_pump),
                'jumlah_penjualan' => round($jumlah_penjualan, 2),
                'jumlah_penjualan_bersih_rp' => round($jumlah_penjualan_bersih_rp, 2),
                'sisa_stok' => round($sisa_stok, 2),
                'persen_losses_gain' => abs(round($persen_losses_gain, 3)),
                'losses_gain' => abs(round($losses_gain, 2)),
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

        $sum_penjualan_bersih_rp = 0;
        $sum_pembelian_rp = 0;
        $sum_laba_kotor = 0;
        $total_penjualan = 0;

        if ($reports->count() > 0) {
            $first_report = $reports->first();
            $last_report = $reports->last();

            // Total Pembelian = (Stok Awal Bulan * Harga Beli Awal) + Total Penerimaan Bulan Ini
            $total_stok_awal_rp = $first_report['stok_awal'] * $first_report['stok_awal_harga_beli'];
            $total_datang_rp = $reports->sum(function ($r) {
                return $r['datang'] * $r['harga_beli'];
            });
            $sum_pembelian_rp = $total_stok_awal_rp + $total_datang_rp;

            // Total Penjualan Bersih = Total Penjualan RP + (Stok Akhir Bulan * Harga Beli Akhir) + Total Losses Bulan Ini
            $total_penjualan_rp = $reports->sum(function ($r) {
                return $r['jumlah_penjualan'] * $r['harga_jual'];
            });
            $total_stok_akhir_rp = $last_report['sisa_stok_akhir'] * $last_report['harga_beli'];
            $total_losses_rp = $reports->sum(function ($r) {
                return ($r['sisa_stok_akhir'] - $r['sisa_stok']) * $r['harga_beli'];
            });
            $sum_penjualan_bersih_rp = $total_penjualan_rp + $total_stok_akhir_rp + $total_losses_rp;

            $sum_laba_kotor = $sum_penjualan_bersih_rp - $sum_pembelian_rp;
            $total_penjualan = $reports->sum('jumlah_penjualan');
        }

        $summary = [
            'sisa_stok_akhir' => $reports->last() ? $reports->last()['sisa_stok_akhir'] : Shop::find($shop_id)->kapasitas,
            'jumlah_penjualan_bersih_rp' => $sum_penjualan_bersih_rp,
            'jumlah_pembelian_rp' => $sum_pembelian_rp,
            'laba_kotor' => $sum_laba_kotor,
            'rata_rata_omset_harian' => $reports->count() > 0 ? $reports->sum('rata_rata_omset_harian') / $reports->count() : 0,
            'jumlah_penjualan' => $total_penjualan
        ];

        return $summary;
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $shop_id = $request->input('shop_id', 1);

            $sales = DailyReport::where('shop_id', $shop_id)->get()->groupBy(function ($item) {
                return $item->created_at->format('Y-m');
            });

            $data = $sales->map(function ($value, $key) use ($shop_id) {
                $summary = self::getSummary($shop_id, $key);

                $summary['shop_id'] = $shop_id;
                $summary['bulan'] = $key;

                return $summary;
            });

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $button = '<a href="' . route('laba-kotor.edit', ['shop_id' => $row['shop_id'], 'year_month' => $row['bulan']]) . '" class="btn btn-sm btn-info" title="Detail"><i class="fa fa-list mr-1"></i> Detail</a>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
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
