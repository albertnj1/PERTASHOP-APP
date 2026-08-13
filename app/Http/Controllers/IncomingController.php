<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Incoming;
use App\Models\Operator;
use App\Models\Purchase;
use App\Helpers\AstmTable53;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;

class IncomingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            if (Auth::user()->role == 'admin') {
                $shop_id = Auth::user()->admin->shop_id;
            } elseif (Auth::user()->role == 'operator') {
                $shop_id = Auth::user()->operator->shop_id;
            } elseif (Auth::user()->role == 'investor') {
                $investor_shops = Auth::user()->investor?->shops->pluck('id')->toArray() ?? [];
                $shop_id = $request->input('shop_id');
                if (!$shop_id || !in_array($shop_id, $investor_shops)) {
                    $shop_id = reset($investor_shops) ?: 1;
                }
            } else {
                $shop_id = $request->input('shop_id', 1);
            }

            $data = Incoming::with(['purchase.supplier', 'operator.user', 'shop'])->where('shop_id', $shop_id)->latest()->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($data) {
                    if (Auth::user()->role == 'investor') return '';

                    if (Auth::user()->role == 'operator') {
                        $lastRow = $data->first(); // Mendapatkan data terakhir dari koleksi
                        $button = '';

                        if ($row->id === $lastRow->id && $row->operator_id === Auth::user()->operator->id) { // Menambahkan tombol hanya pada data terakhir
                            $button = '<a href="' . route('incomings.edit', $row->id) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';
                            $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                        }
                    } else {
                        $button = '<a href="' . route('incomings.edit', $row->id) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';
                        $button .= ' <button class="btn btn-sm btn-danger btn-delete" title="hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    }

                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $shops = Shop::all();
        if (Auth::user()->role == 'investor') {
            $shops = Auth::user()->investor?->shops ?? Shop::all();
        }
        return view('incoming.index', compact('shops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $operator = Auth::user()->operator;
        $shop = $operator->shop;
        $purchases = Purchase::with('supplier')
            ->where('shop_id', $shop->id)
            ->get()->where('sisa', '>', 0);
        $incoming = Incoming::where('operator_id', $operator->id)
            ->whereDate('created_at', Carbon::now()->format('Y-m-d'))->first();
        return view('incoming.create', compact('purchases', 'shop'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $customMessages = [
            'required' => ':attribute wajib diisi.',
        ];
        $validated = $request->validate([
            'incoming_date' => 'required|date',
            'purchase_id' => 'required|numeric',
            'sopir' => 'required|string',
            'no_polisi' => 'required|string',
            'asal_pengirim' => 'nullable|string',
            'maos_volume' => 'nullable|numeric',
            'maos_suhu' => 'nullable|numeric',
            'maos_density' => 'nullable|numeric',
            'jam_tiba' => 'nullable',
            'jam_berangkat' => 'nullable',
            'stock_terima_bbm' => 'nullable|numeric',
            'dens_temp' => 'nullable|string',
            'stik_awal' => 'required|numeric',
            'stik_akhir' => 'required|numeric',
            'penerimaan_real' => 'nullable|numeric',
            'terima_volume' => 'nullable|numeric',
            'terima_suhu' => 'nullable|numeric',
            'terima_density' => 'nullable|numeric',
        ], $customMessages);

        $validated['created_at'] = Carbon::now()->format('Y-m-d H:i');
        $validated['shop_id'] = Auth::user()->operator->shop_id;
        $validated['operator_id'] = Auth::user()->operator->id;

        $penerimaan_real = $request->penerimaan_real;
        $purchase = Purchase::find($request->purchase_id);
        
        // Ensure volume is set so the Purchase SO is marked as fully received and disappears from dropdown
        if ($purchase) {
            $validated['volume'] = $purchase->volume;
        }

        $incoming = Incoming::create($validated);

        // Calculate losses/gain
        if ($purchase && $penerimaan_real !== null) {
            $incoming->losses_gain = $penerimaan_real - $purchase->volume;
        }

        // Calculate density at 15C and Pertamax validity
        if ($request->terima_density !== null && $request->terima_suhu !== null) {
            $density15c = AstmTable53::getDensity15C($request->terima_density, $request->terima_suhu);
            $incoming->density_15c = $density15c;
            $incoming->is_pertamax = ($request->terima_density >= 0.700 && $request->terima_density <= 0.759);
        }

        $incoming->save();

        // Invalidate dashboard cache agar data stok langsung ter-update
        $this->invalidateDashboardCache($validated['shop_id']);

        return to_route('incomings.index')->with('success', 'Data penerimaan berhasil disimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Incoming $incoming)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Incoming $incoming)
    {
        $operator = $incoming->operator;
        $shop = $operator->shop;
        $purchases = Purchase::with('supplier')->where('shop_id', $shop->id)->get()->filter(function ($value, int $key) use ($incoming) {
            return $value->sisa > 0 || $value->id == $incoming->purchase_id;
        });

        return view('incoming.edit', compact('purchases', 'shop', 'incoming'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Incoming $incoming)
    {
        $customMessages = [
            'required' => ':attribute wajib diisi.',
        ];
        $validated = $request->validate([
            'incoming_date' => 'required|date',
            'purchase_id' => 'required|numeric',
            'sopir' => 'required|string',
            'no_polisi' => 'required|string',
            'asal_pengirim' => 'nullable|string',
            'maos_volume' => 'nullable|numeric',
            'maos_suhu' => 'nullable|numeric',
            'maos_density' => 'nullable|numeric',
            'jam_tiba' => 'nullable',
            'jam_berangkat' => 'nullable',
            'stock_terima_bbm' => 'nullable|numeric',
            'dens_temp' => 'nullable|string',
            'stik_awal' => 'required|numeric',
            'stik_akhir' => 'required|numeric',
            'penerimaan_real' => 'nullable|numeric',
            'terima_volume' => 'nullable|numeric',
            'terima_suhu' => 'nullable|numeric',
            'terima_density' => 'nullable|numeric',
        ], $customMessages);

        $penerimaan_real = $request->penerimaan_real;

        // Calculate losses/gain & update volume to close SO
        $purchase = Purchase::find($request->purchase_id);
        if ($purchase) {
            $validated['volume'] = $purchase->volume;
            if ($penerimaan_real !== null) {
                $validated['losses_gain'] = $penerimaan_real - $purchase->volume;
            }
        }

        // Calculate density at 15C and Pertamax validity
        if ($request->terima_density !== null && $request->terima_suhu !== null) {
            $density15c = AstmTable53::getDensity15C($request->terima_density, $request->terima_suhu);
            $validated['density_15c'] = $density15c;
            $validated['is_pertamax'] = ($request->terima_density >= 0.700 && $request->terima_density <= 0.759);
        }

        $incoming->update($validated);

        // Invalidate dashboard cache agar data stok langsung ter-update
        $this->invalidateDashboardCache($incoming->shop_id);

        return to_route('incomings.index')->with('success', 'Data penerimaan berhasil diubah.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Incoming $incoming)
    {
        $shopId = $incoming->shop_id;
        $incoming->delete();
        $this->invalidateDashboardCache($shopId);

        return response()->json([
            'message' => 'Data penerimaan telah dihapus.'
        ]);
    }

    /**
     * Invalidate semua cache dashboard terkait shop tertentu.
     */
    private function invalidateDashboardCache(int $shopId): void
    {
        foreach (['month', 'week', 'day'] as $filter) {
            Cache::forget('dashboard_data_' . $shopId . '_' . $filter);
            Cache::forget('dashboard_data_all_' . $filter);
        }
    }
}
