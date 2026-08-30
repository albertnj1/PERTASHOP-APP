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

        // Load active files grouped by shop ordered chronologically (oldest to newest)
        $shops->load(['backdateExcelFiles' => function ($q) {
            $q->orderBy('bulan_tahun', 'asc')->orderBy('created_at', 'asc');
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

        $currentShopAliasesData = BackdateExcelSummaryService::getShopAliases($backdateExcelFile->shop);
        $allOtherShopsData = Shop::where('id', '!=', $backdateExcelFile->shop_id)->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'nama' => $s->nama,
                'kode' => $s->kode,
                'aliases' => BackdateExcelSummaryService::getShopAliases($s),
            ];
        });

        $monthlyReport = \App\Models\MonthlyReport::where('shop_id', $backdateExcelFile->shop_id)
            ->where('bulan_tahun', $backdateExcelFile->bulan_tahun)
            ->first();

        return view('backdate_excel.show', compact('backdateExcelFile', 'fileBase64', 'summary', 'monthlyReport', 'currentShopAliasesData', 'allOtherShopsData'));
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

    private function getTrashedQuery()
    {
        return BackdateExcelFile::onlyTrashed();
    }

    /**
     * Pulihkan Berkas Excel Dari Tempat Sampah
     */
    public function restore($id)
    {
        $file = $this->getTrashedQuery()->findOrFail($id);
        $this->authorizeShopAccess($file->shop_id);

        if (method_exists($file, 'restore')) {
            $file->restore();
        } else {
            $file->update(['deleted_at' => null]);
        }

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

        $query = $this->getTrashedQuery();
        if ($user->role !== 'superadmin' && !empty($shopIds)) {
            $query->whereIn('shop_id', $shopIds);
        }

        $count = $query->count();
        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada berkas di Tempat Sampah untuk dipulihkan.');
        }

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(BackdateExcelFile::class))) {
            $query->restore();
        } else {
            $query->update(['deleted_at' => null]);
        }

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "Berhasil memulihkan {$count} berkas dari Tempat Sampah.");
    }

    /**
     * Hapus Permanen Berkas Excel Dari Tempat Sampah (Hapus DB & File Fisik)
     */
    public function forceDelete($id)
    {
        $file = $this->getTrashedQuery()->findOrFail($id);
        $this->authorizeShopAccess($file->shop_id);

        $filename = $file->original_filename;
        $fullPath = storage_path('app/public/' . $file->file_path);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        } elseif (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        if (method_exists($file, 'forceDelete')) {
            $file->forceDelete();
        } else {
            $file->delete();
        }

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

        $query = $this->getTrashedQuery();
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
            if (method_exists($file, 'forceDelete')) {
                $file->forceDelete();
            } else {
                $file->delete();
            }
        }

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "Tempat Sampah berhasil dikosongkan ({$count} berkas dihapus permanen).");
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  BACKDATE V2: Multi-File Upload, Processing Engine & Dual Export
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * AJAX Multi-File Upload & Processing (1–12 files sekaligus).
     * Mengembalikan JSON response dengan status per-file dan per-outlet.
     */
    public function storeMulti(Request $request)
    {
        // Mengabaikan batas 60 detik khusus untuk proses multi-upload ini (0 = unlimited)
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '1024M');

        $request->validate([
            'files'     => 'required|array|min:1|max:12',
            'files.*'   => 'file|mimes:xlsx,xls|max:20480',
        ], [
            'files.required' => 'Silakan pilih minimal 1 file Excel.',
            'files.max'      => 'Maksimal 12 file dalam satu kali upload.',
            'files.*.mimes'  => 'Format file harus .xlsx atau .xls.',
            'files.*.max'    => 'Ukuran per file maksimal 20 MB.',
        ]);

        $user = Auth::user();
        $shops = $this->getAccessibleShops();
        $uploadedFiles = $request->file('files');
        $totalFiles = count($uploadedFiles);

        $storedPaths = [];
        $fileRecords = [];
        $errors = [];

        // Fase 1: Simpan semua file ke storage
        foreach ($uploadedFiles as $idx => $file) {
            try {
                $originalFilename = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $savedFilename = 'multi_' . time() . '_' . $idx . '.' . $extension;
                $folderPath = 'backdate_excel/multi_upload';
                $storedPath = Storage::disk('public')->putFileAs($folderPath, $file, $savedFilename);
                $fullPath = Storage::disk('public')->path($storedPath);

                $storedPaths[] = [
                    'fullPath' => $fullPath,
                    'storedPath' => $storedPath,
                    'originalFilename' => $originalFilename,
                    'fileSize' => $file->getSize(),
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => 'Gagal menyimpan file: ' . $e->getMessage(),
                ];
            }
        }

        // Fase 2: Jalankan extraction engine pada semua file (beserta metadata nama file)
        $processingResults = [];

        try {
            $processingResults = BackdateExcelSummaryService::processMultipleFiles($storedPaths, $shops);
        } catch (\Throwable $e) {
            \Log::error("storeMulti processing error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses file: ' . $e->getMessage(),
                'errors' => $errors,
            ], 500);
        }

        // Fase 3: Simpan hasil ke database per file/outlet
        $successOutlets = [];

        foreach ($processingResults as $result) {
            $shop = $result['shop'];
            $shopId = $result['shop_id'] ?? $shop->id;
            $period = $result['period'] ?? 'Multi-Periode';
            $summary = $result['summary'] ?? [];

            // Gunakan storedPath dan originalFilename khusus berkas ini
            $outletStoredPath = $result['stored_path'] ?? ($storedPaths[0]['storedPath'] ?? '');
            $outletOriginalFilename = $result['original_filename'] ?? implode(', ', array_column($storedPaths, 'originalFilename'));
            $outletFileSize = $result['file_size'] ?? ($storedPaths[0]['fileSize'] ?? 0);

            try {
                $bef = BackdateExcelFile::create([
                    'shop_id'            => $shopId,
                    'bulan_tahun'        => $period,
                    'original_filename'  => $outletOriginalFilename,
                    'file_path'          => $outletStoredPath,
                    'file_size'          => $outletFileSize,
                    'keterangan'         => '[Backdate v2 Multi-Upload] Berkas diproses otomatis',
                    'user_id'            => Auth::id(),
                    'processing_status'  => 'completed',
                    'processing_result'  => $summary,
                    'processed_at'       => now(),
                ]);

                // Auto-sync ke MonthlyReport & CapitalRecap
                try {
                    \App\Services\MonthlyReportCalculationService::syncFromBackdateExcel($bef);
                } catch (\Throwable $e) {
                    \Log::warning("Auto-sync backdate v2 failed for shop {$shopId}: " . $e->getMessage());
                }

                $hal1 = $summary['hal1'] ?? [];
                $hal2 = $summary['hal2'] ?? [];

                $successOutlets[] = [
                    'shop_id'       => $shopId,
                    'shop_nama'     => $shop->nama,
                    'shop_kode'     => $shop->kode ?? '',
                    'period'        => $period,
                    'period_label'  => $this->formatPeriodLabel($period),
                    'record_id'     => $bef->id,
                    'original_filename' => $outletOriginalFilename,
                    'matched_sheets' => $result['matched_sheets'] ?? [],
                    'source_files'  => $result['source_files'] ?? [$outletOriginalFilename],
                    'ringkasan'     => [
                        'total_liter'      => $hal1['total_liter_terjual'] ?? 0,
                        'laba_kotor'       => $hal1['grand_total_laba_kotor'] ?? 0,
                        'total_biaya'      => $hal2['total_biaya'] ?? 0,
                        'laba_bersih'      => $hal2['laba_bersih'] ?? 0,
                        'segments_count'   => count($hal1['segments'] ?? []),
                    ],
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'file' => $shop->nama . ' (' . $outletOriginalFilename . ')',
                    'error' => 'Gagal menyimpan hasil: ' . $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success'    => true,
            'message'    => count($successOutlets) . ' Pertashop berhasil diproses dari ' . $totalFiles . ' file.',
            'outlets'    => $successOutlets,
            'errors'     => $errors,
            'total_files_uploaded' => $totalFiles,
            'total_outlets_processed' => count($successOutlets),
        ]);
    }

    /**
     * Download Laporan PDF Resmi (5 halaman A4) dari data processing result.
     */
    public function downloadPdf(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);
        $backdateExcelFile->load('shop');

        $shop = $backdateExcelFile->shop;
        $period = $backdateExcelFile->bulan_tahun;
        $summary = $backdateExcelFile->processing_result;

        // Fallback: jika processing_result kosong, re-parse dari file
        if (empty($summary)) {
            $fullPath = Storage::disk('public')->path($backdateExcelFile->file_path);
            if (file_exists($fullPath)) {
                $summary = BackdateExcelSummaryService::extract($fullPath, $shop, $period);

                // Simpan untuk next time
                $backdateExcelFile->update([
                    'processing_result' => $summary,
                    'processing_status' => 'completed',
                    'processed_at' => now(),
                ]);
            }
        }

        if (empty($summary)) {
            return redirect()->back()->with('error', 'Data laporan belum tersedia. Silakan upload ulang atau sinkronkan file.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.backdate_report', compact('shop', 'period', 'summary'))
            ->setPaper('a4', 'portrait');

        $shopSlug = \Illuminate\Support\Str::slug($shop->nama ?? 'pertashop');
        $dateSlug = \Illuminate\Support\Str::slug($period);
        $filename = "Laporan_Resmi_{$shopSlug}_{$dateSlug}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Download Master Excel bersih & terstandarisasi dari data processing result.
     */
    public function downloadReportExcel(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);
        $backdateExcelFile->load('shop');

        $shop = $backdateExcelFile->shop;
        $period = $backdateExcelFile->bulan_tahun;
        $summary = $backdateExcelFile->processing_result;

        // Fallback: re-parse jika kosong
        if (empty($summary)) {
            $fullPath = Storage::disk('public')->path($backdateExcelFile->file_path);
            if (file_exists($fullPath)) {
                $summary = BackdateExcelSummaryService::extract($fullPath, $shop, $period);
                $backdateExcelFile->update([
                    'processing_result' => $summary,
                    'processing_status' => 'completed',
                    'processed_at' => now(),
                ]);
            }
        }

        if (empty($summary)) {
            return redirect()->back()->with('error', 'Data laporan belum tersedia.');
        }

        $excelPath = \App\Services\BackdateExcelExportService::generate($summary, $shop, $period);

        $shopSlug = \Illuminate\Support\Str::slug($shop->nama ?? 'pertashop');
        $dateSlug = \Illuminate\Support\Str::slug($period);
        $downloadName = "Master_Excel_{$shopSlug}_{$dateSlug}.xlsx";

        return response()->download($excelPath, $downloadName)->deleteFileAfterSend(true);
    }

    private function formatPeriodLabel(string $period): string
    {
        try {
            return \Carbon\Carbon::parse($period . '-01')->translatedFormat('F Y');
        } catch (\Throwable $e) {
            return $period;
        }
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
