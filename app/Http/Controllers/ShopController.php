<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Corporation;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor 
                ? Auth::user()->investor->shops()->with('investors.user')->get() 
                : collect();
            return view('shop.investor_index', compact('shops'));
        }

        if ($request->ajax()) {

            $data = Shop::all();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $button = '<a href="' . route('shops.edit', $row->id) . '" class="btn btn-sm btn-info mr-1" title="Edit"><i class="fa fa-edit"></i></a>';
                    $button .= '<a href="' . route('shops.investors', $row->id) . '" class="btn btn-sm btn-success mr-1" title="Investors"><i class="fa fa-user"></i></a>';
                    $button .= '<button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }


        return view('shop.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $corporations = Corporation::all();
        return view('shop.create', compact('corporations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required',
            'kode' => 'required',
            'alamat' => 'required',
            'stik_awal' => 'required',
            'totalisator_awal' => 'required',
            'corporation_id' => 'required',
            'modal_awal' => 'required',
            'kapasitas' => 'required',
            'skala' => 'required',
            'tanggal_mulai_operasional' => 'nullable|date',
        ]);

        Shop::create($validated);

        return redirect()->route('shops.index')->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Shop $shop)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Shop $shop)
    {
        $corporations = Corporation::all();
        return view('shop.edit', compact('shop', 'corporations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shop $shop)
    {
        $validated = $request->validate([
            'nama' => 'required',
            'kode' => 'required',
            'alamat' => 'required',
            'stik_awal' => 'required',
            'totalisator_awal' => 'required',
            'corporation_id' => 'required',
            'modal_awal' => 'required',
            'kapasitas' => 'required',
            'skala' => 'required',
            'tanggal_mulai_operasional' => 'nullable|date',
        ]);

        $shop->update($validated);

        return redirect()->route('shops.index')->with('success', 'Data berhasil diubah');
    }

    public function getModalDetails(Shop $shop)
    {
        $lastReport = \App\Models\MonthlyReport::where('shop_id', $shop->id)
            ->orderBy('id', 'desc')
            ->first();
            
        $lastSaldoModal = $lastReport ? floatval($lastReport->saldo_akhir_modal) : floatval($shop->modal_awal);

        $activePrice = \App\Models\Price::where('shop_id', $shop->id)
            ->where('effective_at', '<=', now())
            ->orderBy('effective_at', 'desc')
            ->first();
        if (!$activePrice) {
            $activePrice = \App\Models\Price::whereNull('shop_id')
                ->where('effective_at', '<=', now())
                ->orderBy('effective_at', 'desc')
                ->first();
        }
        $hargaBeli = $activePrice ? floatval($activePrice->harga_beli) : 0;

        return response()->json([
            'saldo_akhir_modal' => $lastSaldoModal,
            'harga_beli' => $hargaBeli,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shop $shop)
    {
        $shop->delete();
        return response()->json(['message' => 'Data berhasil dihapus.']);
    }

    public function investor(Request $request, Shop $shop)
    {
        if ($request->ajax()) {

            $data = $shop->investors->load('user');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $button = '<button class="btn btn-sm btn-info btn-edit mr-1" title="edit" data-id="' . $row->id . '" data-persentase="' . $row->pivot->persentase . '" data-nama="' . $row->user->name . '"><i class="fa fa-edit"></i></button>';
                    $button .= '<button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $investors = Investor::whereDoesntHave('shops', function ($query) use ($shop) {
            $query->where('shop_id', $shop->id);
        })->get();

        return view('shop.investors', compact('shop', 'investors'));
    }

    public function investorStore(Request $request, Shop $shop)
    {
        $request->validate([
            'investor_id' => 'required',
            'persentase' => 'required'
        ]);

        //attach investors to shop


        $shop->investors()->attach([$request->investor_id => ['persentase' => $request->persentase]]);

        return response()->json(['message' => 'Investor berhasil ditambahkan ke Pertashop.']);
    }

    public function investorUpdate(Request $request, Shop $shop)
    {
        $request->validate([
            'id' => 'required',
            'persentase' => 'required'
        ]);

        $shop->investors()->detach($request->id);

        $shop->investors()->attach([$request->id => ['persentase' => $request->persentase]]);

        return response()->json(['message' => 'Persentase investasi berhasil diupdate.']);
    }

    public function investorDestroy(Request $request, Shop $shop)
    {

        $shop->investors()->detach($request->id);

        return response()->json(['message' => 'Investor berhasil dihapus dari Pertashop.']);
    }
}
