<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\PeriodLock;
use App\Models\Shop;
use App\Services\PertashopDailyRecapExcelExportService;
use App\Services\PertashopDailyRecapService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyReportMonitoringController extends Controller
{
    /**
     * Pastikan hanya Super Admin atau Admin yang berhak memantau
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!in_array($user->role, ['super-admin', 'super_admin', 'admin'])) {
                abort(403, 'Akses terbatas untuk Super Admin.');
            }
            return $next($request);
        });
    }

    /**
     * Halaman Utama Pantauan Laporan Harian Super Admin
     */
    public function index(Request $request)
    {
        $shops = Shop::where('is_active', true)->orderBy('nama')->get();

        if ($shops->isEmpty()) {
            return view('monitoring.daily_reports', [
                'shops'             => collect(),
                'selectedShopId'    => null,
                'selectedMonth'     => Carbon::now()->month,
                'selectedYear'      => Carbon::now()->year,
                'recapData'         => null,
                'multiShopOverview' => collect(),
            ]);
        }

        $selectedShopId = (int) $request->input('shop_id', $shops->first()->id);
        $selectedMonth  = (int) $request->input('month', Carbon::now()->month);
        $selectedYear   = (int) $request->input('year', Carbon::now()->year);

        $recapData = PertashopDailyRecapService::getMonthlyRecap($selectedShopId, $selectedMonth, $selectedYear);
        $multiShopOverview = PertashopDailyRecapService::getMultiShopTodayOverview();

        $yearMonth = sprintf('%04d-%02d', $selectedYear, $selectedMonth);
        $periodLock = PeriodLock::where('shop_id', $selectedShopId)
            ->where('year_month', $yearMonth)
            ->with('locker')
            ->first();
        $isLocked = $periodLock ? (bool) $periodLock->is_locked : false;

        $startDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfDay();
        $endDate   = Carbon::createFromDate($selectedYear, $selectedMonth, $startDate->daysInMonth)->endOfDay();

        $dailyReports = DailyReport::with(['operator.user', 'spendings', 'incomings', 'testPumps'])
            ->where('shop_id', $selectedShopId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->whereNull('report_date')->whereBetween('created_at', [$startDate, $endDate]);
                  });
            })
            ->orderByRaw('COALESCE(report_date, DATE(created_at)) ASC')
            ->get();

        return view('monitoring.daily_reports', [
            'shops'             => $shops,
            'selectedShopId'    => $selectedShopId,
            'selectedMonth'     => $selectedMonth,
            'selectedYear'      => $selectedYear,
            'recapData'         => $recapData,
            'multiShopOverview' => $multiShopOverview,
            'isLocked'          => $isLocked,
            'periodLock'        => $periodLock,
            'dailyReports'      => $dailyReports,
            'yearMonth'         => $yearMonth,
        ]);
    }

    /**
     * Endpoint Ringan JSON untuk Polling Real-time (15-30 detik)
     */
    public function getData(Request $request): JsonResponse
    {
        $shopId = (int) $request->input('shop_id');
        $month  = (int) $request->input('month', Carbon::now()->month);
        $year   = (int) $request->input('year', Carbon::now()->year);
        $lastClientTimestamp = $request->input('last_timestamp');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate   = Carbon::createFromDate($year, $month, $startDate->daysInMonth)->endOfDay();

        // Cek timestamp pembaruan terakhir di database
        $latestReportUpdate = DailyReport::where('shop_id', $shopId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->whereNull('report_date')->whereBetween('created_at', [$startDate, $endDate]);
                  });
            })
            ->max('updated_at');

        $latestTimestampStr = $latestReportUpdate ? Carbon::parse($latestReportUpdate)->toIso8601String() : null;

        // Jika client sudah memiliki data paling baru, kirim respons ringan tanpa hitung ulang
        if ($lastClientTimestamp && $latestTimestampStr && $lastClientTimestamp === $latestTimestampStr) {
            return response()->json([
                'has_changes' => false,
                'timestamp'   => $latestTimestampStr,
            ]);
        }

        // Hitung data penuh dari layanan terpusat
        $recapData = PertashopDailyRecapService::getMonthlyRecap($shopId, $month, $year);

        return response()->json([
            'has_changes' => true,
            'timestamp'   => $latestTimestampStr ?: now()->toIso8601String(),
            'recap'       => $recapData,
        ]);
    }

    /**
     * Endpoint JSON Ringkasan Semua Gerai Hari Ini
     */
    public function getOverview(): JsonResponse
    {
        return response()->json(PertashopDailyRecapService::getMultiShopTodayOverview());
    }

    /**
     * Ekspor Rekap Lengkap ke Berkas Excel (.xlsx)
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $shopId = (int) $request->input('shop_id');
        $month  = (int) $request->input('month', Carbon::now()->month);
        $year   = (int) $request->input('year', Carbon::now()->year);

        $recapData = PertashopDailyRecapService::getMonthlyRecap($shopId, $month, $year);
        $spreadsheet = PertashopDailyRecapExcelExportService::generateSpreadsheet($recapData);

        $shop = $recapData['shop'];
        $cleanKode = preg_replace('/[^a-zA-Z0-9]/', '', $shop->kode ?: 'KMT');
        $filename = sprintf('REKAP_%s_%02d_%d.xlsx', $cleanKode, $month, $year);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Validasi dan Kunci / Buka Kunci Periode Rekap Bulanan
     */
    public function validatePeriod(Request $request)
    {
        $shopId = (int) $request->input('shop_id');
        $month  = (int) $request->input('month');
        $year   = (int) $request->input('year');
        $action = $request->input('action', 'lock'); // 'lock' or 'unlock'

        $yearMonth = sprintf('%04d-%02d', $year, $month);

        if ($action === 'lock') {
            PeriodLock::updateOrCreate(
                ['shop_id' => $shopId, 'year_month' => $yearMonth],
                [
                    'is_locked' => true,
                    'locked_at' => now(),
                    'locked_by' => Auth::id(),
                ]
            );

            // Perbarui status laporan harian di bulan tersebut menjadi verified
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $endDate   = Carbon::createFromDate($year, $month, $startDate->daysInMonth)->endOfDay();

            DailyReport::where('shop_id', $shopId)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->whereNull('report_date')->whereBetween('created_at', [$startDate, $endDate]);
                      });
                })
                ->update(['status_lifecycle' => 'verified']);

            return redirect()->route('monitoring.daily-reports', [
                'shop_id' => $shopId,
                'month'   => $month,
                'year'    => $year,
            ])->with('success', "Rekap bulanan periode {$yearMonth} berhasil divalidasi dan dikunci.");
        } else {
            PeriodLock::updateOrCreate(
                ['shop_id' => $shopId, 'year_month' => $yearMonth],
                [
                    'is_locked' => false,
                    'locked_at' => null,
                    'locked_by' => null,
                ]
            );

            return redirect()->route('monitoring.daily-reports', [
                'shop_id' => $shopId,
                'month'   => $month,
                'year'    => $year,
            ])->with('success', "Kunci validasi periode {$yearMonth} berhasil dibuka kembali (Reopened).");
        }
    }

    /**
     * Ekspor Rekap Bulanan Resmi ke PDF (Setelah Divalidasi)
     */
    public function exportPdf(Request $request)
    {
        $shopId = (int) $request->input('shop_id');
        $month  = (int) $request->input('month', Carbon::now()->month);
        $year   = (int) $request->input('year', Carbon::now()->year);

        $yearMonth = sprintf('%04d-%02d', $year, $month);
        $isLocked = PeriodLock::isLocked($shopId, $yearMonth);

        if (!$isLocked) {
            return redirect()->route('monitoring.daily-reports', [
                'shop_id' => $shopId,
                'month'   => $month,
                'year'    => $year,
            ])->with('error', 'Rekap bulanan harus divalidasi terlebih dahulu sebelum mengunduh PDF resmi.');
        }

        $recapData = PertashopDailyRecapService::getMonthlyRecap($shopId, $month, $year);
        $periodLock = PeriodLock::where('shop_id', $shopId)
            ->where('year_month', $yearMonth)
            ->with('locker')
            ->first();

        $shop = $recapData['shop'];
        $cleanKode = preg_replace('/[^a-zA-Z0-9]/', '', $shop->kode ?: 'PS');
        $filename = sprintf('REKAP_RESMI_%s_%02d_%d.pdf', $cleanKode, $month, $year);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.monthly_recap_official', [
            'recapData'  => $recapData,
            'periodLock' => $periodLock,
            'month'      => $month,
            'year'       => $year,
            'shop'       => $shop,
        ])->setPaper('A4', 'landscape');

        return $pdf->download($filename);
    }
}
