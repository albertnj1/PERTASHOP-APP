<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (Auth::user()->role == 'admin') {
                $shop_id = Auth::user()->admin->shop_id;
            } else {
                $shop_id = $request->input('shop_id', 1);
            }

            $data = Purchase::where('shop_id', $shop_id)->with(['supplier', 'incomings'])->latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    if (Auth::user()->role == 'investor') return '';

                    $button = '<a href="' . route('purchases.edit', $row->id) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';
                    $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }


        $shops = Shop::all();
        $suppliers = Supplier::all();
        return view('purchase.index', compact('shops', 'suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::all();
        $shops = Shop::all();

        return view('purchase.create', compact('suppliers', 'shops'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->has('volume') && !$request->has('jumlah_kl')) {
            $request->merge([
                'jumlah_kl' => $request->volume / 1000,
                'total_nominal' => $request->total_bayar,
                'persen_net' => 85.06,
                'persen_ppn' => 10.11,
                'persen_pph' => 0.23,
                'persen_pbbkb' => 4.60,
                'catatan_debit_credit' => 0,
            ]);
        }

        $customMessages = [
            'required' => ':attribute wajib diisi.',
        ];

        $validatedData = $request->validate([
            'purchase_date' => 'required|date',
            'supplier_id' => 'required|numeric',
            'no_so' => 'required|string',
            'no_lo' => 'required|string',
            'trip' => 'nullable|string',
            'jumlah_kl' => 'required|numeric',
            'delivery_date' => 'nullable|date',
            'total_nominal' => 'required|numeric',
            'catatan_debit_credit' => 'nullable|numeric',
            'persen_net' => 'required|numeric',
            'persen_ppn' => 'required|numeric',
            'persen_pph' => 'required|numeric',
            'persen_pbbkb' => 'required|numeric',
        ], $customMessages);

        if (Auth::user()->role == 'super-admin' || Auth::user()->role == 'admin') {
            $request->validate(['shop_id' => 'required|numeric']);
            $validatedData['shop_id'] = $request->shop_id;
        } else {
            $validatedData['shop_id'] = Auth::user()->operator->shop_id ?? 1;
        }

        // Logic Perhitungan DO
        $kl = floatval($request->jumlah_kl);
        $volume_liter = $kl * 1000;
        $total_nominal = floatval($request->total_nominal);
        $catatan_debit_credit = floatval($request->catatan_debit_credit ?? 0);
        
        $total_kotor = $total_nominal - $catatan_debit_credit;
        
        // Simpan field baru
        $validatedData['volume'] = $volume_liter;
        $validatedData['total_bayar'] = $total_nominal; // Maintain total_bayar as total_nominal for legacy compatibility
        $validatedData['catatan_debit_credit'] = $catatan_debit_credit;
        $validatedData['total_kotor'] = $total_kotor;
        $validatedData['total_nett'] = $total_kotor * (floatval($request->persen_net) / 100);
        $validatedData['pajak_ppn'] = $total_kotor * (floatval($request->persen_ppn) / 100);
        $validatedData['pajak_pph'] = $total_kotor * (floatval($request->persen_pph) / 100);
        $validatedData['pajak_pbbkb'] = $total_kotor * (floatval($request->persen_pbbkb) / 100);
        $validatedData['harga_satuan'] = $volume_liter > 0 ? ($total_kotor / $volume_liter) : 0;
        
        // Remove jumlah_kl because it's not in db
        unset($validatedData['jumlah_kl']);

        $validatedData['created_at'] = now();

        Purchase::create($validatedData);

        return to_route('purchases.index')->with('success', 'Data pembelian DO berhasil disimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::all();
        $shops = Shop::all();
        return view('purchase.edit', compact('suppliers', 'shops', 'purchase'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        if ($request->has('volume') && !$request->has('jumlah_kl')) {
            $request->merge([
                'jumlah_kl' => $request->volume / 1000,
                'total_nominal' => $request->total_bayar,
                'persen_net' => 85.06,
                'persen_ppn' => 10.11,
                'persen_pph' => 0.23,
                'persen_pbbkb' => 4.60,
                'catatan_debit_credit' => 0,
            ]);
        }

        $customMessages = [
            'required' => ':attribute wajib diisi.',
        ];

        $validatedData = $request->validate([
            'purchase_date' => 'required|date',
            'supplier_id' => 'required|numeric',
            'no_so' => 'required|string',
            'no_lo' => 'required|string',
            'trip' => 'nullable|string',
            'jumlah_kl' => 'required|numeric',
            'delivery_date' => 'nullable|date',
            'total_nominal' => 'required|numeric',
            'catatan_debit_credit' => 'nullable|numeric',
            'persen_net' => 'required|numeric',
            'persen_ppn' => 'required|numeric',
            'persen_pph' => 'required|numeric',
            'persen_pbbkb' => 'required|numeric',
        ], $customMessages);

        if (Auth::user()->role == 'super-admin' || Auth::user()->role == 'admin') {
            $request->validate(['shop_id' => 'required|numeric']);
            $validatedData['shop_id'] = $request->shop_id;
        }

        // Logic Perhitungan DO
        $kl = floatval($request->jumlah_kl);
        $volume_liter = $kl * 1000;
        $total_nominal = floatval($request->total_nominal);
        $catatan_debit_credit = floatval($request->catatan_debit_credit ?? 0);
        
        $total_kotor = $total_nominal - $catatan_debit_credit;
        
        $validatedData['volume'] = $volume_liter;
        $validatedData['total_bayar'] = $total_nominal;
        $validatedData['catatan_debit_credit'] = $catatan_debit_credit;
        $validatedData['total_kotor'] = $total_kotor;
        $validatedData['total_nett'] = $total_kotor * (floatval($request->persen_net) / 100);
        $validatedData['pajak_ppn'] = $total_kotor * (floatval($request->persen_ppn) / 100);
        $validatedData['pajak_pph'] = $total_kotor * (floatval($request->persen_pph) / 100);
        $validatedData['pajak_pbbkb'] = $total_kotor * (floatval($request->persen_pbbkb) / 100);
        $validatedData['harga_satuan'] = $volume_liter > 0 ? ($total_kotor / $volume_liter) : 0;
        
        unset($validatedData['jumlah_kl']);

        $purchase->update($validatedData);

        return to_route('purchases.index')->with('success', 'Data pembelian DO berhasil diubah.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        $purchase->delete();

        return response()->json([
            'message' => 'Data pembelian telah dihapus.'
        ]);
    }
}
