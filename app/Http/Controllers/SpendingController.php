<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Spending;
use App\Models\SpendingCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SpendingController extends Controller
{
    /**
     * Memastikan 8 kategori pengeluaran standar tersedia di tabel database.
     */
    public static function ensureCategories(): void
    {
        static $ensured = false;
        if ($ensured) return;
        $ensured = true;

        $categories = [
            1  => 'Ongkos Bongkar',
            2  => 'Biaya Transfer',
            3  => 'Fotocopy & ATK',
            4  => 'Listrik',
            5  => 'Air Bersih',
            6  => 'Cashback',
            7  => 'Internet',
            99 => 'Lain-lain',
        ];

        foreach ($categories as $id => $name) {
            try {
                $cat = SpendingCategory::find($id);
                if ($cat) {
                    if ($cat->nama !== $name) {
                        $cat->update(['nama' => $name]);
                    }
                } else {
                    SpendingCategory::create([
                        'id'   => $id,
                        'nama' => $name,
                    ]);
                }
            } catch (\Throwable $e) {
                try {
                    SpendingCategory::firstOrCreate(['nama' => $name]);
                } catch (\Throwable $err) {}
            }
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        self::ensureCategories();

        if ($request->ajax()) {
            if (Auth::user()->role == 'admin') {
                $shop_id = Auth::user()->admin ? Auth::user()->admin->shop_id : 1;
            } elseif (Auth::user()->role == 'operator') {
                $shop_id = Auth::user()->operator ? Auth::user()->operator->shop_id : 1;
            } elseif (Auth::user()->role == 'investor') {
                $investor = Auth::user()->investor;
                $investor_shops = ($investor && $investor->shops) ? $investor->shops->pluck('id')->toArray() : [];
                $shop_id = $request->input('shop_id');
                if (!$shop_id || !in_array($shop_id, $investor_shops)) {
                    $shop_id = reset($investor_shops) ?: 1;
                }
            } else {
                $shop_id = $request->input('shop_id', 1);
            }

            $spendings = Spending::with(['category', 'operator.user'])->where('shop_id', $shop_id)
                ->latest()
                ->get();

            if (Auth::user()->role == 'operator') {
                $spendings = Spending::with(['category', 'operator.user'])->where('shop_id', $shop_id)
                    ->where('operator_id', '!=', null)
                    ->latest()
                    ->get();
            }

            return DataTables::of($spendings)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($spendings) {
                    if (Auth::user()->role == 'investor') return '';

                    if (Auth::user()->role == 'operator') {
                        $lastRow = $spendings->first();
                        $button = '';

                        if ($row->id === $lastRow->id && $row->operator_id === Auth::user()->operator->id) {
                            $button = '<button class="btn btn-sm btn-info btn-edit" title="edit" data-id="' . $row->id . '"><i class="fa fa-edit"></i></button>';
                            $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                        }
                    } else {
                        $button = '<button class="btn btn-sm btn-info btn-edit" title="edit" data-id="' . $row->id . '"><i class="fa fa-edit"></i></button>';
                        $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    }

                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $shops = Shop::all();
        if (Auth::user()->role == 'investor') {
            $investor = Auth::user()->investor;
            $shops = ($investor && $investor->shops) ? $investor->shops : Shop::all();
        }

        // Tampilkan 8 kategori lengkap untuk semua role (dengan Lain-lain selalu di urutan terakhir)
        $spendingCategories = SpendingCategory::orderByRaw("CASE WHEN id = 99 OR LOWER(nama) = 'lain-lain' THEN 999 ELSE id END ASC")->get();

        return view('spending.index', compact('shops', 'spendingCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        self::ensureCategories();

        if (Auth::user()->role == 'admin') {
            $shop_id = Auth::user()->admin ? Auth::user()->admin->shop_id : 1;
        } elseif (Auth::user()->role == 'operator') {
            $shop_id = Auth::user()->operator ? Auth::user()->operator->shop_id : 1;
        } else {
            $shop_id = $request->input('shop_id', 1);
        }

        // Tangkap spending_category_id atau kategori dari request
        $categoryId = $request->input('spending_category_id') ?? $request->input('kategori');
        if (!is_numeric($categoryId) && !empty($categoryId)) {
            $foundCat = SpendingCategory::where('nama', $categoryId)->first();
            if ($foundCat) {
                $categoryId = $foundCat->id;
            } elseif (strtolower($categoryId) === 'lain-lain') {
                $categoryId = 99;
            }
        }

        $request->merge(['spending_category_id' => $categoryId]);

        // Cek apakah kategori adalah Lain-lain (ID 99 atau nama 'Lain-lain')
        $isLainLain = ((int)$categoryId === 99) || 
                      (SpendingCategory::where('id', $categoryId)->value('nama') === 'Lain-lain');

        $validated = $request->validate([
            'created_at'           => 'required',
            'spending_category_id' => 'required',
            'jumlah'               => 'required|numeric|min:0',
            'keterangan'           => $isLainLain ? 'required|string|max:255' : 'nullable|string|max:255',
        ], [
            'keterangan.required'  => 'Kolom keterangan wajib diisi khusus untuk pengeluaran kategori Lain-lain.',
            'spending_category_id.required' => 'Pilih kategori pengeluaran terlebih dahulu.',
        ]);

        try {
            $parsedDate = Carbon::parse($validated['created_at'])->format('Y-m-d');
        } catch (\Throwable $e) {
            $parsedDate = date('Y-m-d');
        }

        Spending::create([
            'shop_id'              => $shop_id,
            'operator_id'          => (Auth::user()->role == 'operator' && Auth::user()->operator) ? Auth::user()->operator->id : null,
            'created_at'           => Auth::user()->role == 'operator' ? Carbon::now()->format('Y-m-d H:i:s') : $parsedDate . ' ' . date('H:i:s'),
            'spending_category_id' => $validated['spending_category_id'],
            'keterangan'           => $validated['keterangan'] ?? null,
            'jumlah'               => $validated['jumlah'],
        ]);

        return response()->json([
            'message' => 'Data pengeluaran berhasil disimpan.',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Spending $spending)
    {
        return response()->json(compact('spending'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Spending $spending)
    {
        self::ensureCategories();

        $categoryId = $request->input('spending_category_id') ?? $request->input('kategori');
        if (!is_numeric($categoryId) && !empty($categoryId)) {
            $foundCat = SpendingCategory::where('nama', $categoryId)->first();
            if ($foundCat) {
                $categoryId = $foundCat->id;
            } elseif (strtolower($categoryId) === 'lain-lain') {
                $categoryId = 99;
            }
        }

        $request->merge(['spending_category_id' => $categoryId]);

        $isLainLain = ((int)$categoryId === 99) || 
                      (SpendingCategory::where('id', $categoryId)->value('nama') === 'Lain-lain');

        $validated = $request->validate([
            'created_at'           => 'required',
            'spending_category_id' => 'required',
            'jumlah'               => 'required|numeric|min:0',
            'keterangan'           => $isLainLain ? 'required|string|max:255' : 'nullable|string|max:255',
        ], [
            'keterangan.required'  => 'Kolom keterangan wajib diisi khusus untuk pengeluaran kategori Lain-lain.',
            'spending_category_id.required' => 'Pilih kategori pengeluaran terlebih dahulu.',
        ]);

        try {
            $parsedDate = Carbon::parse($validated['created_at'])->format('Y-m-d');
        } catch (\Throwable $e) {
            $parsedDate = $spending->created_at?->format('Y-m-d') ?? date('Y-m-d');
        }

        $spending->update([
            'created_at'           => Auth::user()->role == 'operator' ? $spending->created_at : $parsedDate . ' ' . date('H:i:s'),
            'spending_category_id' => $validated['spending_category_id'],
            'keterangan'           => $validated['keterangan'] ?? null,
            'jumlah'               => $validated['jumlah'],
        ]);

        return response()->json([
            'message' => 'Data pengeluaran berhasil diupdate.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Spending $spending)
    {
        //delete and return json response
        $spending->delete();
        return response()->json([
            'message' => 'Data berhasil dihapus.',
        ]);
    }
}
