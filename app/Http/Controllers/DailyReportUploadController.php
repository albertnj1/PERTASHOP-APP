<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyReportUpload;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;

class DailyReportUploadController extends Controller
{
    public function index()
    {
        $reports = DailyReportUpload::with('shop')->orderBy('tanggal', 'desc')->get();
        return view('daily_report_uploads.index', compact('reports'));
    }

    public function create()
    {
        $shops = Shop::all();
        // prefill default date to today
        return view('daily_report_uploads.create', compact('shops'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'tanggal' => 'required|date',
            'totalisator_akhir' => 'required|numeric',
            'test_pump' => 'nullable|numeric',
            'qris' => 'nullable|numeric',
            'keterangan_pengeluaran' => 'nullable|string|max:255',
            'pengeluaran' => 'nullable|numeric',
            'file_bukti' => 'nullable|file|mimes:jpeg,png,jpg,xlsx,xls|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            $filename = $request->tanggal . '_' . $request->shop_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('public/daily_reports', $filename);
        }

        // Cari totalisator_awal dari form, jika kosong ambil dari record hari sebelumnya
        if ($request->filled('totalisator_awal')) {
            $totalisator_awal = $request->totalisator_awal;
        } else {
            $prevReport = DailyReportUpload::where('shop_id', $request->shop_id)
                ->where('tanggal', '<', $request->tanggal)
                ->orderBy('tanggal', 'desc')
                ->first();

            if ($prevReport) {
                $totalisator_awal = $prevReport->totalisator_akhir;
            } else {
                $prevDailyReport = \App\Models\DailyReport::where('shop_id', $request->shop_id)
                    ->whereDate('created_at', '<', $request->tanggal)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $totalisator_awal = $prevDailyReport ? $prevDailyReport->totalisator_akhir : 0;
            }
        }
        
        // Validasi opsional: Jika totalisator_akhir kurang dari awal
        if ($request->totalisator_akhir < $totalisator_awal) {
            return back()->withInput()->withErrors(['totalisator_akhir' => 'Totalisator akhir tidak boleh lebih kecil dari totalisator akhir hari sebelumnya ('.$totalisator_awal.').']);
        }

        $report = DailyReportUpload::create([
            'shop_id' => $request->shop_id,
            'tanggal' => $request->tanggal,
            'totalisator_awal' => $totalisator_awal,
            'totalisator_akhir' => $request->totalisator_akhir,
            'test_pump' => $request->test_pump ?? 0,
            'qris' => $request->qris ?? 0,
            'keterangan_pengeluaran' => $request->keterangan_pengeluaran,
            'pengeluaran' => $request->pengeluaran ?? 0,
            'file_path' => $filePath,
        ]);

        return redirect()->route('daily-report-uploads.show', $report->id)
                         ->with('success', 'Laporan berhasil diupload dan dikalkulasi.');
    }

    public function show($id)
    {
        $report = DailyReportUpload::with('shop')->findOrFail($id);
        return view('daily_report_uploads.show', compact('report'));
    }

    public function destroy(DailyReportUpload $dailyReportUpload)
    {
        if ($dailyReportUpload->file_path && Storage::exists($dailyReportUpload->file_path)) {
            Storage::delete($dailyReportUpload->file_path);
        }

        $dailyReportUpload->delete();

        return redirect()->route('daily-report-uploads.index')->with('success', 'Laporan berhasil dihapus.');
    }

    public function download($id)
    {
        $report = DailyReportUpload::findOrFail($id);
        if ($report->file_path && Storage::exists($report->file_path)) {
            return Storage::download($report->file_path);
        }
        return back()->with('error', 'File tidak ditemukan.');
    }
}
