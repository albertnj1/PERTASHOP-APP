<?php

namespace App\Http\Controllers;

use App\Models\Price;
use App\Models\PriceAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Price::with('shop')->orderBy('effective_at', 'desc');
            if (Auth::user()->role == 'operator') {
                $query->where('shop_id', Auth::user()->operator->shop_id);
            }
            $data = $query->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('shop', function ($row) {
                    return $row->shop ? $row->shop->nama : null;
                })
                ->addColumn('action', function ($row) {
                    $isOperator = Auth::user()->role == 'operator';
                    $createdDate = \Carbon\Carbon::parse($row->created_at)->startOfDay();
                    $today = \Carbon\Carbon::now()->timezone('Asia/Jakarta')->startOfDay();
                    
                    $canEdit = true;
                    if ($isOperator && $createdDate->lt($today)) {
                        $canEdit = false;
                    }

                    if ($canEdit) {
                        $button  = '<a href="' . route('prices.edit', $row->id) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';
                        $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="Hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                        return $button;
                    } else {
                        return '<span class="badge badge-secondary" title="Terkunci (Lewat jam 00:00)"><i class="fas fa-lock"></i> Terkunci</span>';
                    }
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('price.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shops = \App\Models\Shop::all();
        return view('price.create', compact('shops'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'created_at' => 'required|date',
            'jam' => 'required|date_format:H:i',
            'harga_beli' => 'nullable|numeric',
            'harga_jual' => 'required|numeric',
            'shop_id' => 'required|exists:shops,id',
            'totalisator_perubahan' => 'required|numeric',
            'lokasi_device' => 'nullable|string',
        ]);

        $validated['effective_at'] = \Illuminate\Support\Carbon::parse($validated['created_at'] . ' ' . $validated['jam'])->format('Y-m-d H:i:s');

        // Jika operator tidak mengisi harga beli (karena readonly), ambil harga beli dari harga aktif sebelumnya
        if (empty($validated['harga_beli'])) {
            $lastPrice = Price::where('shop_id', $validated['shop_id'])
                ->where('effective_at', '<=', $validated['effective_at'])
                ->orderBy('effective_at', 'desc')->first();
            $validated['harga_beli'] = $lastPrice ? $lastPrice->harga_beli : 0;
        }

        $price = Price::create($validated);

        PriceAuditLog::create([
            'user_id' => Auth::id(),
            'shop_id' => $validated['shop_id'] ?? null,
            'action' => 'CREATE',
            'harga_beli_lama' => null,
            'harga_jual_lama' => null,
            'harga_beli_baru' => $validated['harga_beli'],
            'harga_jual_baru' => $validated['harga_jual']
        ]);

        return redirect()->route('prices.index')->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Price $price)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Price $price)
    {
        if (Auth::user()->role == 'operator') {
            $createdDate = \Carbon\Carbon::parse($price->created_at)->startOfDay();
            $today = \Carbon\Carbon::now()->timezone('Asia/Jakarta')->startOfDay();
            if ($createdDate->lt($today)) {
                abort(403, 'Anda tidak dapat mengedit data perubahan harga yang sudah lewat hari (lewat jam 00:00).');
            }
        }

        $shops = \App\Models\Shop::all();
        return view('price.edit', compact('price', 'shops'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Price $price)
    {
        if (Auth::user()->role == 'operator') {
            $createdDate = \Carbon\Carbon::parse($price->created_at)->startOfDay();
            $today = \Carbon\Carbon::now()->timezone('Asia/Jakarta')->startOfDay();
            if ($createdDate->lt($today)) {
                abort(403, 'Anda tidak dapat mengubah data yang sudah lewat hari.');
            }
        }

        $validated = $request->validate([
            'created_at' => 'required|date',
            'jam' => 'required|date_format:H:i',
            'harga_beli' => 'nullable|numeric',
            'harga_jual' => 'required|numeric',
            'shop_id' => 'required|exists:shops,id',
            'totalisator_perubahan' => 'required|numeric'
        ]);

        $validated['effective_at'] = \Illuminate\Support\Carbon::parse($validated['created_at'] . ' ' . $validated['jam'])->format('Y-m-d H:i:s');

        if (empty($validated['harga_beli'])) {
            $lastPrice = Price::where('shop_id', $validated['shop_id'])
                ->where('effective_at', '<=', $validated['effective_at'])
                ->orderBy('effective_at', 'desc')->first();
            $validated['harga_beli'] = $lastPrice ? $lastPrice->harga_beli : 0;
        }

        PriceAuditLog::create([
            'user_id' => Auth::id(),
            'shop_id' => $validated['shop_id'] ?? null,
            'action' => 'UPDATE',
            'harga_beli_lama' => $price->harga_beli,
            'harga_jual_lama' => $price->harga_jual,
            'harga_beli_baru' => $validated['harga_beli'],
            'harga_jual_baru' => $validated['harga_jual']
        ]);

        $price->update($validated);

        return redirect()->route('prices.index')->with('success', 'Data berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Price $price)
    {
        if (Auth::user()->role == 'operator') {
            $createdDate = \Carbon\Carbon::parse($price->created_at)->startOfDay();
            $today = \Carbon\Carbon::now()->timezone('Asia/Jakarta')->startOfDay();
            if ($createdDate->lt($today)) {
                return response()->json(['message' => 'Anda tidak dapat menghapus data yang sudah lewat hari.'], 403);
            }
        }

        $harga_beli_lama = $price->harga_beli;
        $harga_jual_lama = $price->harga_jual;
        $shop_id = $price->shop_id;

        $price->delete();

        PriceAuditLog::create([
            'user_id' => Auth::id(),
            'shop_id' => $shop_id,
            'action' => 'DELETE',
            'harga_beli_lama' => $harga_beli_lama,
            'harga_jual_lama' => $harga_jual_lama,
            'harga_beli_baru' => 0,
            'harga_jual_baru' => 0
        ]);

        return response()->json([
            'message' => 'Data berhasil dihapus.'
        ]);
    }

    /**
     * Store or update price for a shop from the admin dashboard widget.
     * Accepts: shop_id, harga_beli, harga_jual, jam_berlaku (HH:MM), tanggal_berlaku (Y-m-d)
     */
    public function storeFromDashboard(Request $request)
    {
        $validated = $request->validate([
            'shop_id'         => 'required|exists:shops,id',
            'harga_beli'      => 'required|numeric|min:1',
            'harga_jual'      => 'required|numeric|min:1',
            'tanggal_berlaku' => 'required|date',
            'jam_berlaku'     => 'required|date_format:H:i',
        ]);

        $effectiveAt = \Carbon\Carbon::parse(
            $validated['tanggal_berlaku'] . ' ' . $validated['jam_berlaku'] . ':00'
        )->format('Y-m-d H:i:s');

        // Get previous price for audit log
        $prevPrice = Price::where('shop_id', $validated['shop_id'])
            ->where('effective_at', '<=', $effectiveAt)
            ->orderBy('effective_at', 'desc')
            ->first();

        $price = Price::create([
            'shop_id'      => $validated['shop_id'],
            'harga_beli'   => $validated['harga_beli'],
            'harga_jual'   => $validated['harga_jual'],
            'effective_at' => $effectiveAt,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        PriceAuditLog::create([
            'user_id'        => Auth::id(),
            'shop_id'        => $validated['shop_id'],
            'action'         => 'CREATE',
            'harga_beli_lama'=> $prevPrice ? $prevPrice->harga_beli : null,
            'harga_jual_lama'=> $prevPrice ? $prevPrice->harga_jual : null,
            'harga_beli_baru'=> $validated['harga_beli'],
            'harga_jual_baru'=> $validated['harga_jual'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Harga berhasil diperbarui untuk ' . \App\Models\Shop::find($validated['shop_id'])->nama,
            'price'   => $price,
        ]);
    }
}
