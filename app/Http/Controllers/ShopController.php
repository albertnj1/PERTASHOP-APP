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
                ? Auth::user()->investor->shops()->with(['investors.user', 'operators.user', 'corporation'])->get() 
                : collect();
            return view('shop.investor_index', compact('shops'));
        }

        $shops = Shop::with(['investors.user', 'operators.user', 'corporation'])->get();

        if ($request->ajax()) {
            return DataTables::of($shops)
                ->addIndexColumn()
                ->addColumn('total_investasi', function ($row) {
                    $total = $row->investors->sum('pivot.nominal') ?: $row->modal_awal;
                    return 'Rp ' . number_format($total, 0, ',', '.');
                })
                ->addColumn('operators_list', function ($row) {
                    return $row->operators->map(fn($o) => $o->user->short_name ?? $o->user->name)->join(', ') ?: '-';
                })
                ->addColumn('action', function ($row) {
                    $button = '<div class="btn-group" role="group">';
                    $button .= '<a href="' . route('shops.edit', $row->id) . '" class="btn btn-sm btn-outline-primary" title="Edit Pertashop"><i class="fas fa-edit"></i></a>';
                    $button .= '<a href="' . route('shops.investors', $row->id) . '" class="btn btn-sm btn-outline-success ml-1" title="Komposisi Investor"><i class="fas fa-users"></i></a>';
                    $button .= '<a href="' . route('payroll-operator-assignments.index') . '" class="btn btn-sm btn-outline-info ml-1" title="Penugasan Operator"><i class="fas fa-user-tag"></i></a>';
                    $button .= '<button class="btn btn-sm btn-outline-danger ml-1 btn-delete" title="Hapus Pertashop" data-id="' . $row->id . '"><i class="fas fa-trash-alt"></i></button>';
                    $button .= '</div>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $totalShopsCount = $shops->count();
        $totalInvestasiAll = $shops->sum(function($s) {
            $invTotal = $s->investors->sum('pivot.nominal');
            return $invTotal > 0 ? $invTotal : ($s->modal_awal ?? 0);
        });
        $totalOperatorsCount = \App\Models\Operator::count();

        return view('shop.index', compact('shops', 'totalShopsCount', 'totalInvestasiAll', 'totalOperatorsCount'));
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

    public function toggleStatus(Request $request, Shop $shop)
    {
        $isActive = $request->input('is_active');
        $shop->is_active = $isActive !== null ? (bool)$isActive : !$shop->is_active;
        $shop->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'is_active' => (bool)$shop->is_active,
                'message' => 'Status Pertashop berhasil diperbarui'
            ]);
        }

        return redirect()->back()->with('success', 'Status Pertashop berhasil diubah');
    }
}
