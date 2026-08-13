<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\AttendanceRecap;
use App\Models\ShiftSchedule;
use App\Models\PayrollDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class OperatorProfileController extends Controller
{
    public function index(Request $request)
    {
        $operator = Auth::user()->operator;

        if (!$operator) {
            return redirect()->route('dashboard')->with('error', 'Hanya akun operator yang dapat mengakses halaman ini.');
        }

        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        [$year, $month] = explode('-', $selectedMonth);

        // Rekap Kehadiran
        $recap = AttendanceRecap::where('operator_id', $operator->id)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->first();

        // Data Laporan Harian Operator Bulan Ini
        $reports = DailyReport::where('operator_id', $operator->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalReports = $reports->count();
        $avgLosses = $totalReports > 0 ? $reports->avg('losses_gain') : 0;
        $totalLosses = $reports->sum('losses_gain');
        $totalSalesVol = $reports->sum('volume_penjualan_teoritis');
        $accurateSetoranCount = $reports->filter(fn ($r) => abs((float) $r->selisih_setoran) < 1000)->count();
        $setoranAccuracyPct = $totalReports > 0 ? round(($accurateSetoranCount / $totalReports) * 100, 1) : 0;

        // Jadwal Shift Mendatang
        $upcomingShifts = ShiftSchedule::where('operator_id', $operator->id)
            ->where('tanggal', '>=', Carbon::today())
            ->orderBy('tanggal', 'asc')
            ->take(7)
            ->get();

        // Slip Gaji Operator (Bulan Ini / Histori)
        $payrollDetails = PayrollDetail::with(['period.shop', 'period.payrollSystem'])
            ->where('operator_id', $operator->id)
            ->whereHas('period', function ($q) use ($year, $month) {
                $q->where('tahun', $year)->where('bulan', (int)$month);
            })
            ->get();

        return view('operator.performa', compact(
            'operator',
            'recap',
            'reports',
            'totalReports',
            'avgLosses',
            'totalLosses',
            'totalSalesVol',
            'setoranAccuracyPct',
            'upcomingShifts',
            'payrollDetails',
            'selectedMonth',
            'year',
            'month'
        ));
    }

    public function exportSlipPdf(PayrollDetail $payrollDetail)
    {
        $operator = Auth::user()->operator;

        if (!$operator || $payrollDetail->operator_id !== $operator->id) {
            abort(403, 'Anda tidak memiliki akses ke slip gaji ini.');
        }

        $payroll = $payrollDetail->period->load(['shop', 'payrollSystem']);
        $payroll->setRelation('details', collect([$payrollDetail]));

        $pdf = Pdf::loadView('pdf.payroll_slip', compact('payroll'))
            ->setPaper('a4', 'portrait');

        $filename = 'slip-gaji-' . strtolower(str_replace(' ', '-', $payroll->shop->nama))
            . '-' . $payroll->bulan . '-' . $payroll->tahun . '.pdf';

        return $pdf->download($filename);
    }
}
