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

        // Load files grouped by shop
        $shops->load(['backdateExcelFiles' => function ($q) {
            $q->orderBy('bulan_tahun', 'desc')->orderBy('created_at', 'desc');
        }]);

        return view('backdate_excel.index', compact('shops'));
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
                        BackdateExcelFile::create([
                            'shop_id'           => $shop->id,
                            'bulan_tahun'       => $periodStr,
                            'original_filename' => $originalFilename,
                            'file_path'         => $storedPath,
                            'file_size'         => $file->getSize(),
                            'keterangan'        => ($request->keterangan ? $request->keterangan . ' — ' : '') . '[Auto-Split Periode]',
                            'user_id'           => Auth::id(),
                        ]);
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
                        BackdateExcelFile::create([
                            'shop_id'           => $shop->id,
                            'bulan_tahun'       => $periodStr,
                            'original_filename' => $originalFilename,
                            'file_path'         => $storedPath,
                            'file_size'         => $file->getSize(),
                            'keterangan'        => ($request->keterangan ? $request->keterangan . ' — ' : '') . '[Auto-Split Periode]',
                            'user_id'           => Auth::id(),
                        ]);
                    }
                }
            }

            $shopNamesStr = $matchedShops->pluck('nama')->implode(', ');
            return redirect()->route('backdate-excel-files.index')
                ->with('success', "File Excel Master '{$originalFilename}' berhasil diuraikan menjadi " . count($periodsToSave) . " periode bulan dan didistribusikan ke {$matchedShops->count()} Pertashop: {$shopNamesStr}.");
        }

        // 2. OPSI SINGLE PERTASHOP SPESIFIK
        $shop = Shop::findOrFail($request->shop_id);
        $folderPath = 'backdate_excel/pertashop_' . $shop->id;
        $firstPeriod = $periodsToSave[0];
        $savedFilename = str_replace([' ', '/'], '_', $firstPeriod) . '_' . time() . '.' . $extension;
        $storedPath = $file->storeAs($folderPath, $savedFilename, 'public');

        foreach ($periodsToSave as $periodStr) {
            BackdateExcelFile::create([
                'shop_id'           => $shop->id,
                'bulan_tahun'       => $periodStr,
                'original_filename' => $originalFilename,
                'file_path'         => $storedPath,
                'file_size'         => $file->getSize(),
                'keterangan'        => $request->keterangan,
                'user_id'           => Auth::id(),
            ]);
        }

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "File Excel Backdate '{$originalFilename}' untuk toko {$shop->nama} berhasil diurai menjadi " . count($periodsToSave) . " periode bulanan dan disimpan ke arsip.");
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

        return view('backdate_excel.show', compact('backdateExcelFile', 'fileBase64', 'summary'));
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
     * Hapus Berkas Excel Backdate Dari Arsip
     */
    public function destroy(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);

        $filename = $backdateExcelFile->original_filename;
        $fullPath = storage_path('app/public/' . $backdateExcelFile->file_path);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        } elseif (Storage::disk('public')->exists($backdateExcelFile->file_path)) {
            Storage::disk('public')->delete($backdateExcelFile->file_path);
        }

        $backdateExcelFile->delete();

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "File Excel '{$filename}' berhasil dihapus dari arsip.");
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
