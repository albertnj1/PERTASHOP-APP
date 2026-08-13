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
            'bulan_tahun' => 'required|string',
            'file_excel'  => 'required|file|mimes:xlsx,xls|max:20480',
            'keterangan'  => 'nullable|string|max:500',
        ], [
            'shop_id.required'     => 'Silakan pilih Pertashop atau opsi Otomatis Semua Pertashop.',
            'bulan_tahun.required' => 'Silakan pilih Periode Bulan & Tahun.',
            'file_excel.required'  => 'Silakan pilih file Excel (.xlsx / .xls).',
            'file_excel.mimes'     => 'Format file harus berupa Excel (.xlsx atau .xls).',
            'file_excel.max'       => 'Ukuran file maksimal adalah 20 MB.',
        ]);

        $file = $request->file('file_excel');
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $accessibleShops = $this->getAccessibleShops();

        // 1. OPSI OTOMATIS BANYAK PERTASHOP (MULTI-SHEET MASTER FILE)
        if ($request->shop_id === 'auto_multi') {
            $sheetNames = [];
            try {
                $ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                foreach ($ss->getAllSheets() as $sh) {
                    $sheetNames[] = $sh->getTitle();
                }
            } catch (\Throwable $e) {
                // If sheet reading fails, fallback to distributing to all accessible shops
            }

            $matchedShops = collect();

            foreach ($accessibleShops as $shop) {
                $shopNameClean = strtolower(trim($shop->nama));
                $shopKodeClean = strtolower(str_replace('.', '', trim($shop->kode)));
                $shopKeywords  = array_filter(explode(' ', $shopNameClean), fn($k) => strlen($k) > 2);

                $isMatch = false;
                if (empty($sheetNames)) {
                    $isMatch = true; // Fallback: assign to all if sheet reading failed
                } else {
                    foreach ($sheetNames as $sName) {
                        $sNameLower = strtolower($sName);
                        $sNameNoDot = str_replace('.', '', $sNameLower);
                        if (str_contains($sNameLower, $shopNameClean) || ($shopKodeClean && str_contains($sNameNoDot, $shopKodeClean))) {
                            $isMatch = true;
                            break;
                        }
                        foreach ($shopKeywords as $kw) {
                            if (str_contains($sNameLower, $kw)) {
                                $isMatch = true;
                                break 2;
                            }
                        }
                    }
                }

                // Match found or master file distribution
                if ($isMatch) {
                    $matchedShops->push($shop);
                    $folderPath = 'backdate_excel/pertashop_' . $shop->id;
                    $savedFilename = $request->bulan_tahun . '_' . time() . '_' . $shop->id . '.' . $extension;
                    $storedPath = Storage::disk('public')->putFileAs($folderPath, $file, $savedFilename);

                    BackdateExcelFile::create([
                        'shop_id'           => $shop->id,
                        'bulan_tahun'       => $request->bulan_tahun,
                        'original_filename' => $originalFilename,
                        'file_path'         => $storedPath,
                        'file_size'         => $file->getSize(),
                        'keterangan'        => ($request->keterangan ? $request->keterangan . ' — ' : '') . '[Master File Multi-Pertashop]',
                        'user_id'           => Auth::id(),
                    ]);
                }
            }

            // Fallback: If no specific sheet name matched, assign master file to all accessible shops
            if ($matchedShops->isEmpty()) {
                foreach ($accessibleShops as $shop) {
                    $matchedShops->push($shop);
                    $folderPath = 'backdate_excel/pertashop_' . $shop->id;
                    $savedFilename = $request->bulan_tahun . '_' . time() . '_' . $shop->id . '.' . $extension;
                    $storedPath = Storage::disk('public')->putFileAs($folderPath, $file, $savedFilename);

                    BackdateExcelFile::create([
                        'shop_id'           => $shop->id,
                        'bulan_tahun'       => $request->bulan_tahun,
                        'original_filename' => $originalFilename,
                        'file_path'         => $storedPath,
                        'file_size'         => $file->getSize(),
                        'keterangan'        => ($request->keterangan ? $request->keterangan . ' — ' : '') . '[Master File Multi-Pertashop]',
                        'user_id'           => Auth::id(),
                    ]);
                }
            }

            $shopNamesStr = $matchedShops->pluck('nama')->implode(', ');
            return redirect()->route('backdate-excel-files.index')
                ->with('success', "File Excel Master '{$originalFilename}' ({$request->bulan_tahun}) berhasil diuraikan dan didistribusikan ke {$matchedShops->count()} Pertashop: {$shopNamesStr}.");
        }

        // 2. OPSI SINGLE PERTASHOP SPESIFIK
        $shop = Shop::findOrFail($request->shop_id);
        $folderPath = 'backdate_excel/pertashop_' . $shop->id;
        $savedFilename = $request->bulan_tahun . '_' . time() . '.' . $extension;
        $storedPath = $file->storeAs($folderPath, $savedFilename, 'public');

        BackdateExcelFile::create([
            'shop_id'           => $shop->id,
            'bulan_tahun'       => $request->bulan_tahun,
            'original_filename' => $originalFilename,
            'file_path'         => $storedPath,
            'file_size'         => $file->getSize(),
            'keterangan'        => $request->keterangan,
            'user_id'           => Auth::id(),
        ]);

        return redirect()->route('backdate-excel-files.index')
            ->with('success', "File Excel Backdate '{$originalFilename}' untuk toko {$shop->nama} ({$request->bulan_tahun}) berhasil disimpan ke arsip.");
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
        if (Storage::disk('public')->exists($backdateExcelFile->file_path)) {
            $fullPath = Storage::disk('public')->path($backdateExcelFile->file_path);
            $fileBase64 = base64_encode(Storage::disk('public')->get($backdateExcelFile->file_path));
            $summary = BackdateExcelSummaryService::extract($fullPath, $backdateExcelFile->shop);
        }

        return view('backdate_excel.show', compact('backdateExcelFile', 'fileBase64', 'summary'));
    }

    /**
     * Stream File Binary Excel dengan Content-Type Resmi
     */
    public function stream(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);

        if (!Storage::disk('public')->exists($backdateExcelFile->file_path)) {
            abort(404, 'Berkas fisik tidak ditemukan di server.');
        }

        $fullPath = Storage::disk('public')->path($backdateExcelFile->file_path);
        
        return response()->file($fullPath, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'inline; filename="' . $backdateExcelFile->original_filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Unduh File Excel Asli
     */
    public function download(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);

        if (!Storage::disk('public')->exists($backdateExcelFile->file_path)) {
            return redirect()->back()->with('error', 'Berkas fisik tidak ditemukan di server.');
        }

        return Storage::disk('public')->download($backdateExcelFile->file_path, $backdateExcelFile->original_filename);
    }

    /**
     * Hapus Berkas Excel Backdate Dari Arsip
     */
    public function destroy(BackdateExcelFile $backdateExcelFile)
    {
        $this->authorizeShopAccess($backdateExcelFile->shop_id);

        $filename = $backdateExcelFile->original_filename;

        if (Storage::disk('public')->exists($backdateExcelFile->file_path)) {
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
