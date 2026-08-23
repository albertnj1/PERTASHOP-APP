<?php

namespace App\Http\Controllers;

use App\Models\BackdateExcelFile;
use App\Models\Shop;
use App\Services\BackdateExcelSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BackdateExcelFileController extends Controller
{
    /**
     * Halaman Utama Arsip Upload File Backdate (Kontainer per Pertashop)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $shops = $this->getAccessibleShops();
        $shopIds = $shops->pluck('id')->toArray();

        // Auto-ensure deleted_at column exists in database if migration hasn't been run manually
        if (!\Illuminate\Support\Facades\Schema::hasColumn('backdate_excel_files', 'deleted_at')) {
            try {
                \Illuminate\Support\Facades\Schema::table('backdate_excel_files', function ($table) {
                    $table->softDeletes();
                });
            } catch (\Throwable $e) {
                \Log::warning("Auto migration deleted_at failed: " . $e->getMessage());
            }
        }

        // Load active files grouped by shop
        $shops->load(['backdateExcelFiles' => function ($q) {
            $q->orderBy('bulan_tahun', 'desc')->orderBy('created_at', 'desc');
        }]);

        // Load trashed files for accessible shops
        $trashedQuery = BackdateExcelFile::onlyTrashed()->with(['shop', 'user']);
        if ($user->role !== 'superadmin' && !empty($shopIds)) {
            $trashedQuery->whereIn('shop_id', $shopIds);
        }
        $trashedFiles = $trashedQuery->orderBy('deleted_at', 'desc')->get();
        $trashedCount = $trashedFiles->count();

        // Total active files count across accessible shops
        $totalActiveFilesCount = $shops->sum(function ($shop) {
            return $shop->backdateExcelFiles->count();
        });

        return view('backdate_excel.index', compact('shops', 'trashedFiles', 'trashedCount', 'totalActiveFilesCount'));
    }

    /**
     * Upload File Excel Backdate Baru (Mendukung Single Shop & Multi-Shop Master File)
     */
    public function store(Request $request)
    {
        $request->validate([
            'shop_id'     => 'required|string',
            'bulan_tahun' => 'nullable|string',
            'file_excel'  => 'required|file|mimes:xlsx,xls|max:20480',
            'keterangan'  => 'nullable|string|max:500',
        ], [
            'shop_id.required'     => 'Silakan pilih Pertashop atau opsi Otomatis Semua Pertashop.',
            'file_excel.required'  => 'Silakan pilih file Excel (.xlsx / .xls).',
            'file_excel.mimes'     => 'Format file harus berupa Excel (.xlsx atau .xls).',
            'file_excel.max'       => 'Ukuran file maksimal adalah 20 MB.',
        ]);

        $file = $request->file('file_excel');
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $accessibleShops = $this->getAccessibleShops();

        // Read Sheet Names Fast
        $sheetNames = [];
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
            if (method_exists($reader, 'setReadDataOnly')) {
                $reader->setReadDataOnly(true);
            }
            $ss = $reader->load($file->getPathname());
            foreach ($ss->getAllSheets() as $sh) {
                $sheetNames[] = $sh->getTitle();
            }
        } catch (\Throwable $e) {
            // Sheet reading fallback
        }

        // Auto-detect distinct periods from sheet names if bulan_tahun is empty
        $detectedPeriods = [];
        if (empty($request->bulan_tahun) && !empty($sheetNames)) {
            foreach ($sheetNames as $sName) {
                $p = \App\Services\BackdateExcelSummaryService::parsePeriodFromSheetName($sName);
                if ($p !== 'Multi-Periode' && !in_array($p, $detectedPeriods)) {
                    $detectedPeriods[] = $p;
                }
            }
            rsort($detectedPeriods); // Sort newest to oldest
        }

        $periodsToSave = !empty($detectedPeriods) ? $detectedPeriods : [$request->bulan_tahun ?: $this->detectYearRangeFromFilename($originalFilename)];

        // 1. OPSI OTOMATIS BANYAK PERTASHOP (MULTI-SHEET MASTER FILE)
        if ($request->shop_id === 'auto_multi') {
            $matchedShops = collect();
            $genericStopwords = ['ps', 'ps.', 'pertashop', 'desa', 'kec', 'kecamatan', 'kab', 'kabupaten', 'toko', 'outlet', 'gaji', 'penjualan', 'laporan', 'daily'];

            foreach ($accessibleShops as $shop) {
                $shopAliases = \App\Services\BackdateExcelSummaryService::getShopAliases($shop);
                $validShopAliases = array_filter($shopAliases, function($alias) use ($genericStopwords) {
                    return strlen($alias) >= 2 && !in_array(strtolower($alias), $genericStopwords);
                });

                $isMatch = false;
                if (!empty($sheetNames)) {
                    foreach ($sheetNames as $sName) {
                        $sNameLower = strtolower($sName);
                        $sNameNoDot = str_replace(['.', ' ', '-'], '', $sNameLower);

                        foreach ($validShopAliases as $alias) {
                            if (str_contains($sNameLower, $alias) || str_contains($sNameNoDot, $alias)) {
                                $isMatch = true;
                                break 2;
                            }
                        }
                    }
                }

                if ($isMatch) {
                    $matchedShops->push($shop);
                    $folderPath = 'backdate_excel/pertashop_' . $shop->id;
                    $firstPeriod = $periodsToSave[0];
                    $savedFilename = str_replace([' ', '/'], '_', $firstPeriod) . '_' . time() . '_' . $shop->id . '.' . $extension;
                    $storedPath = Storage::disk('public')->putFileAs($folderPath, $file, $savedFilename);

                    foreach ($periodsToSave as $periodStr) {
                        $bef = BackdateExcelFile::create([
                            'shop_id'           => $shop->id,
                            'bulan_tahun'       => $periodStr,
                            'original_filename' => $originalFilename,
                            'file_path'         => $storedPath,
                            'file_size'         => $file->getSize(),
                            'keterangan'        => ($request->keterangan ? $request->keterangan . ' — ' : '') . '[Auto-Split Periode]',
                            'user_id'           => Auth::id(),
                        ]);

                        // Auto-sync into MonthlyReport and CapitalRecap
                        try {
                            \App\Services\MonthlyReportCalculationService::syncFromBackdateExcel($bef);
                        } catch (\Throwable $e) {
                            \Log::warning("Auto-sync backdate excel failed: " . $e->getMessage());
                        }
                    }
                }
            }

            // Fallback if no specific shop matched
            if ($matchedShops->isEmpty()) {
                foreach ($accessibleShops as $shop) {
                    $matchedShops->push($shop);
                    $folderPath = 'backdate_excel/pertashop_' . $shop->id;
                    $firstPeriod = $periodsToSave[0];
                    $savedFilename = str_replace([' ', '/'], '_', $firstPeriod) . '_' . time() . '_' . $shop->id . '.' . $extension;
                    $storedPath = Storage::disk('public')->putFileAs($folderPath, $file, $savedFilename);

                    foreach ($periodsToSave as $periodStr) {
                        $bef = BackdateExcelFile::create([
                            'shop_id'           => $shop->id,
                            'bulan_tahun'       => $periodStr,
                            'original_filename' => $originalFilename,
                            'file_path'         => $storedPath,
                            'file_size'         => $file->getSize(),
                            'keterangan'        => ($request->keterangan ? $request->keterangan . ' — ' : '') . '[Auto-Split Periode]',
                            'user_id'           => Auth::id(),
                        ]);

                        // Auto-sync into MonthlyReport and CapitalRecap
                        try {
                            \App\Services\MonthlyReportCalculationService::syncFromBackdateExcel($bef);
                        } catch (\Throwable $e) {
                            \Log::warning("Auto-sync backdate excel failed: " . $e->getMessage());
                        }
                    }
                }
            }

            $shopNamesStr = $matchedShops->pluck('nama')->implode(', ');
            return redirect()->route('backdate-excel-files.index')
                ->with('success', "File Excel Master '{$originalFilename}' berhasil diuraikan, disimpan, dan disinkronkan ke Laporan Bulanan & Rekap Modal {$matchedShops->count()} Pertashop: {$shopNamesStr}.");
        }

        // 2. OPSI SINGLE PERTASHOP SPESIFIK
        $shop = Shop::findOrFail($request->shop_id);
        $folderPath = 'backdate_excel/pertashop_' . $shop->id;
        $firstPeriod = $periodsToSave[0];
        $savedFilename = str_replace([' ', '/'], '_', $firstPeriod) . '_' . time() . '.' . $extension;
        $storedPath = $file->storeAs($folderPath, $savedFilename, 'public');

        foreach ($periodsToSave as $periodStr) {
            $bef = BackdateExcelFile::create([
                'shop_id'           => $shop->id,
                'bulan_tahun'       => $periodStr,
                'original_filename' => $originalFilename,
                'file_path'         => $storedPath,
                'file_size'         => $file->getSize(),
                'keterangan'        => $request->keterangan,
                'user_id'           => Auth::id(),
            ]);

            // Auto-sync into MonthlyReport and CapitalRecap
            try {
                \App\Services\MonthlyReportCalculationService::syncFromBackdateExcel($bef);
            } catch (\Throwable $e) {
                \Log::warning("Auto-sync backdate excel failed: " . $e->getMessage());
            }
        }

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "File Excel Backdate '{$originalFilename}' untuk toko {$shop->nama} berhasil disimpan dan disinkronkan secara otomatis ke Laporan Bulanan & Rekapitulasi Nilai Modal.");
    }

    private function detectYearRangeFromFilename(string $filename): string
    {
        if (preg_match('/(20\d{2}\s*[-_]\s*20\d{2})/i', $filename, $matches)) {
            return 'Tahun ' . str_replace('_', '-', trim($matches[1]));
        } elseif (preg_match('/(20\d{2})/i', $filename, $matches)) {
            return 'Tahun ' . $matches[1];
        }
        return 'Multi-Periode';
    }

    /**
     * Tampil Pratinjau / Detail Isi File Excel Online
     */
    public function show(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);

        $backdateExcelFile->load(['shop', 'user']);
        
        $fileBase64 = null;
        $summary = null;
        $fullPath = storage_path('app/public/' . $backdateExcelFile->file_path);

        if (file_exists($fullPath)) {
            $fileBase64 = base64_encode(file_get_contents($fullPath));
            $summary = BackdateExcelSummaryService::extract($fullPath, $backdateExcelFile->shop, $backdateExcelFile->bulan_tahun);
        } elseif (Storage::disk('public')->exists($backdateExcelFile->file_path)) {
            $fullPath = Storage::disk('public')->path($backdateExcelFile->file_path);
            $fileBase64 = base64_encode(Storage::disk('public')->get($backdateExcelFile->file_path));
            $summary = BackdateExcelSummaryService::extract($fullPath, $backdateExcelFile->shop, $backdateExcelFile->bulan_tahun);
        }

        $monthlyReport = \App\Models\MonthlyReport::where('shop_id', $backdateExcelFile->shop_id)
            ->where('bulan_tahun', $backdateExcelFile->bulan_tahun)
            ->first();

        return view('backdate_excel.show', compact('backdateExcelFile', 'fileBase64', 'summary', 'monthlyReport'));
    }

    /**
     * Sinkronkan Berkas Backdate ke Laporan Bulanan & Rekapitulasi Modal
     */
    public function sync(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);

        try {
            $report = \App\Services\MonthlyReportCalculationService::syncFromBackdateExcel($backdateExcelFile);
            return redirect()->back()->with('success', "Berkas '{$backdateExcelFile->original_filename}' berhasil disinkronkan ke Laporan Bulanan (Periode: {$backdateExcelFile->formatted_period}) dan Rekapitulasi Nilai Modal.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "Gagal melakukan sinkronisasi: " . $e->getMessage());
        }
    }

    /**
     * Stream File Binary Excel dengan Content-Type Resmi
     */
    public function stream(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);

        $fullPath = storage_path('app/public/' . $backdateExcelFile->file_path);
        if (!file_exists($fullPath) && Storage::disk('public')->exists($backdateExcelFile->file_path)) {
            $fullPath = Storage::disk('public')->path($backdateExcelFile->file_path);
        }

        if (!file_exists($fullPath)) {
            abort(404, 'Berkas fisik tidak ditemukan di server.');
        }

        $ext = strtolower(pathinfo($backdateExcelFile->original_filename, PATHINFO_EXTENSION));
        $contentType = ($ext === 'xls')
            ? 'application/vnd.ms-excel'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return response()->streamDownload(function () use ($fullPath) {
            $stream = fopen($fullPath, 'rb');
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $backdateExcelFile->original_filename, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => 'inline; filename="' . rawurlencode($backdateExcelFile->original_filename) . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ], 'inline');
    }

    /**
     * Unduh File Excel Asli
     */
    public function download(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);

        $fullPath = storage_path('app/public/' . $backdateExcelFile->file_path);
        if (!file_exists($fullPath) && Storage::disk('public')->exists($backdateExcelFile->file_path)) {
            $fullPath = Storage::disk('public')->path($backdateExcelFile->file_path);
        }

        if (!file_exists($fullPath)) {
            return redirect()->back()->with('error', 'Berkas fisik tidak ditemukan di server.');
        }

        $ext = strtolower(pathinfo($backdateExcelFile->original_filename, PATHINFO_EXTENSION));
        $contentType = ($ext === 'xls')
            ? 'application/vnd.ms-excel'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return response()->streamDownload(function () use ($fullPath) {
            $stream = fopen($fullPath, 'rb');
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $backdateExcelFile->original_filename, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Hapus Berkas Excel Backdate Dari Arsip (Soft Delete -> Masuk Tempat Sampah)
     */
    public function destroy(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);

        $filename = $backdateExcelFile->original_filename;
        $backdateExcelFile->delete();

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "File Excel '{$filename}' berhasil dipindahkan ke Tempat Sampah.");
    }

    /**
     * Hapus Semua Berkas Active (Global atau Spesifik Toko) -> Masuk Tempat Sampah
     */
    public function deleteAll(Request $request)
    {
        $user = Auth::user();
        if ($user->role === 'investor') {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus file.');
        }

        $shops = $this->getAccessibleShops();
        $shopIds = $shops->pluck('id')->toArray();

        $query = BackdateExcelFile::query();
        if ($user->role !== 'superadmin' && !empty($shopIds)) {
            $query->whereIn('shop_id', $shopIds);
        }

        if ($request->filled('shop_id')) {
            $this->authorizeShopAccess($request->shop_id);
            $query->where('shop_id', $request->shop_id);
            $shop = Shop::find($request->shop_id);
            $targetName = $shop ? "toko {$shop->nama}" : "Pertashop ini";
        } else {
            $targetName = "seluruh Pertashop";
        }

        $count = $query->count();
        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada berkas yang dapat dihapus.');
        }

        $query->delete();

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "Berhasil memindahkan {$count} berkas dari {$targetName} ke Tempat Sampah.");
    }

    /**
     * Pulihkan Berkas Excel Dari Tempat Sampah
     */
    public function restore($id)
    {
        $file = BackdateExcelFile::onlyTrashed()->findOrFail($id);
        $this->authorizeShopAccess($file->shop_id);

        $file->restore();

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "Berkas Excel '{$file->original_filename}' berhasil dipulihkan dari Tempat Sampah.");
    }

    /**
     * Pulihkan Semua Berkas Dari Tempat Sampah
     */
    public function restoreAll()
    {
        $user = Auth::user();
        $shops = $this->getAccessibleShops();
        $shopIds = $shops->pluck('id')->toArray();

        $query = BackdateExcelFile::onlyTrashed();
        if ($user->role !== 'superadmin' && !empty($shopIds)) {
            $query->whereIn('shop_id', $shopIds);
        }

        $count = $query->count();
        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada berkas di Tempat Sampah untuk dipulihkan.');
        }

        $query->restore();

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "Berhasil memulihkan {$count} berkas dari Tempat Sampah.");
    }

    /**
     * Hapus Permanen Berkas Excel Dari Tempat Sampah (Hapus DB & File Fisik)
     */
    public function forceDelete($id)
    {
        $file = BackdateExcelFile::onlyTrashed()->findOrFail($id);
        $this->authorizeShopAccess($file->shop_id);

        $filename = $file->original_filename;
        $fullPath = storage_path('app/public/' . $file->file_path);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        } elseif (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->forceDelete();

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "Berkas Excel '{$filename}' telah dihapus secara permanen dari server.");
    }

    /**
     * Kosongkan Tempat Sampah (Hapus Permanen Semua Berkas Terhapus)
     */
    public function emptyTrash()
    {
        $user = Auth::user();
        if ($user->role === 'investor') {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $shops = $this->getAccessibleShops();
        $shopIds = $shops->pluck('id')->toArray();

        $query = BackdateExcelFile::onlyTrashed();
        if ($user->role !== 'superadmin' && !empty($shopIds)) {
            $query->whereIn('shop_id', $shopIds);
        }

        $files = $query->get();
        $count = $files->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'Tempat Sampah sudah kosong.');
        }

        foreach ($files as $file) {
            $fullPath = storage_path('app/public/' . $file->file_path);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            } elseif (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
            $file->forceDelete();
        }

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "Tempat Sampah berhasil dikosongkan ({$count} berkas dihapus permanen).");
    }

    private function getAccessibleShops()
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return Shop::where('id', $user->admin?->shop_id)->get();
        } elseif ($user->role === 'investor') {
            return $user->investor?->shops ?? collect();
        }
        return Shop::all();
    }

    private function authorizeShopAccess($shopId)
    {
        $user = Auth::user();
        if ($user->role === 'admin' && $user->admin?->shop_id != $shopId) {
            abort(403, 'Anda tidak memiliki akses ke toko ini.');
        }
        if ($user->role === 'investor') {
            $investorShopIds = $user->investor?->shops->pluck('id')->toArray() ?? [];
            if (!in_array($shopId, $investorShopIds)) {
                abort(403, 'Anda tidak memiliki akses ke toko ini.');
            }
        }
    }
}
