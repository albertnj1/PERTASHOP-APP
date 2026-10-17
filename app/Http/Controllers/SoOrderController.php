<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\SoOrder;
use App\Models\Supplier;
use App\Models\SoOrderTrip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SoOrderController extends Controller
{
    /**
     * Display a listing of SO Orders with DataTables.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (Auth::user()->role == 'admin') {
                $shop_id = Auth::user()->admin->shop_id;
            } else {
                $shop_id = $request->input('shop_id');
                if (!$shop_id) {
                    $shop_id = Shop::first()?->id ?? 1;
                }
            }

            $data = SoOrder::where('shop_id', $shop_id)
                ->with(['supplier', 'trips'])
                ->latest()
                ->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('trips_info', function ($row) {
                    $received = $row->trips->where('status', 'RECEIVED')->count();
                    $total = $row->total_trips;
                    $badgeClass = $received === $total ? 'badge-success' : ($received > 0 ? 'badge-warning' : 'badge-secondary');
                    return "<span class=\"badge {$badgeClass}\">{$received}/{$total} Trip</span>";
                })
                ->addColumn('status_label', function ($row) {
                    $map = [
                        'PENDING'   => '<span class="badge badge-secondary">PENDING</span>',
                        'PARTIAL'   => '<span class="badge badge-warning">PARTIAL</span>',
                        'COMPLETED' => '<span class="badge badge-success">COMPLETED</span>',
                    ];
                    return $map[$row->status] ?? $row->status;
                })
                ->addColumn('action', function ($row) {
                    if (Auth::user()->role == 'investor') return '';

                    $btn = '<a href="' . route('so-orders.show', $row->id) . '" class="btn btn-sm btn-outline-primary" title="Detail"><i class="fa fa-eye"></i></a>';
                    $btn .= ' <a href="' . route('so-orders.edit', $row->id) . '" class="btn btn-sm btn-info" title="Edit"><i class="fa fa-edit"></i></a>';

                    if ($row->status === 'PENDING') {
                        $btn .= ' <button class="btn btn-sm btn-danger btn-delete-so" title="Hapus" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';
                    }

                    return $btn;
                })
                ->rawColumns(['trips_info', 'status_label', 'action'])
                ->make(true);
        }

        $shops = Shop::all();
        $suppliers = Supplier::all();
        return view('so_orders.index', compact('shops', 'suppliers'));
    }

    /**
     * Show the form for creating a new SO Order.
     */
    public function create()
    {
        $suppliers = Supplier::all();
        $shops = Shop::all();
        return view('so_orders.create', compact('suppliers', 'shops'));
    }

    /**
     * Store a newly created SO Order with N trips inside a DB transaction.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shop_id'               => 'required|exists:shops,id',
            'order_date'            => 'required|date',
            'supplier_id'           => 'required|exists:suppliers,id',
            'so_number'             => 'required|string|max:100',
            'lo_number'             => 'required|string|max:100',
            'total_quantity_liter'  => 'required|numeric|min:1',
            'total_amount'          => 'required|numeric|min:0',
            'total_trips'           => 'required|integer|min:1|max:20',
            'trips'                 => 'required|array|min:1',
            'trips.*.estimated_delivery_date' => 'required|date',
            'trips.*.quantity_liter'          => 'required|numeric|min:1',
        ], [
            'required' => ':attribute wajib diisi.',
            'trips.required' => 'Data trip pengiriman wajib diisi.',
            'trips.*.estimated_delivery_date.required' => 'Tanggal kirim untuk setiap trip wajib diisi.',
            'trips.*.quantity_liter.required' => 'Volume liter untuk setiap trip wajib diisi.',
        ]);

        // Validasi total volume trips = total_quantity_liter
        $totalTripVolume = collect($request->trips)->sum('quantity_liter');
        $totalOrderVolume = floatval($request->total_quantity_liter);

        if (abs($totalTripVolume - $totalOrderVolume) > 0.01) {
            return back()->withErrors([
                'trips' => "Total volume seluruh trip ({$totalTripVolume} L) tidak sama dengan Total Kuantitas ({$totalOrderVolume} L)."
            ])->withInput();
        }

        DB::transaction(function () use ($request) {
            $soOrder = SoOrder::create([
                'shop_id'              => $request->shop_id,
                'order_date'           => $request->order_date,
                'supplier_id'          => $request->supplier_id,
                'so_number'            => $request->so_number,
                'lo_number'            => $request->lo_number,
                'total_quantity_liter'  => $request->total_quantity_liter,
                'total_amount'         => $request->total_amount,
                'total_trips'          => $request->total_trips,
                'status'               => 'PENDING',
            ]);

            foreach ($request->trips as $index => $tripData) {
                SoOrderTrip::create([
                    'so_order_id'            => $soOrder->id,
                    'trip_number'            => $index + 1,
                    'estimated_delivery_date' => $tripData['estimated_delivery_date'],
                    'quantity_liter'         => $tripData['quantity_liter'],
                    'status'                 => 'PENDING',
                ]);
            }
        });

        return to_route('so-orders.index')->with('success', 'Order SO berhasil dibuat dengan ' . $request->total_trips . ' trip pengiriman.');
    }

    /**
     * Display the specified SO Order detail.
     */
    public function show(SoOrder $soOrder)
    {
        $soOrder->load(['shop', 'supplier', 'trips.incoming', 'trips.dailyReport']);
        return view('so_orders.show', compact('soOrder'));
    }

    /**
     * Show the form for editing the SO Order.
     */
    public function edit(SoOrder $soOrder)
    {
        $soOrder->load('trips');
        $suppliers = Supplier::all();
        $shops = Shop::all();
        return view('so_orders.edit', compact('soOrder', 'suppliers', 'shops'));
    }

    /**
     * Update the specified SO Order.
     * Hanya bisa update jika BELUM ada trip yang RECEIVED.
     */
    public function update(Request $request, SoOrder $soOrder)
    {
        // Guard: jangan izinkan edit jika sudah ada trip RECEIVED
        $hasReceived = $soOrder->trips()->where('status', 'RECEIVED')->exists();
        if ($hasReceived) {
            return back()->with('error', 'Tidak dapat mengubah Order SO yang sudah memiliki trip diterima. Buat Order SO baru jika diperlukan.');
        }

        $request->validate([
            'shop_id'               => 'required|exists:shops,id',
            'order_date'            => 'required|date',
            'supplier_id'           => 'required|exists:suppliers,id',
            'so_number'             => 'required|string|max:100',
            'lo_number'             => 'required|string|max:100',
            'total_quantity_liter'  => 'required|numeric|min:1',
            'total_amount'          => 'required|numeric|min:0',
            'total_trips'           => 'required|integer|min:1|max:20',
            'trips'                 => 'required|array|min:1',
            'trips.*.estimated_delivery_date' => 'required|date',
            'trips.*.quantity_liter'          => 'required|numeric|min:1',
        ]);

        // Validasi total volume
        $totalTripVolume = collect($request->trips)->sum('quantity_liter');
        $totalOrderVolume = floatval($request->total_quantity_liter);

        if (abs($totalTripVolume - $totalOrderVolume) > 0.01) {
            return back()->withErrors([
                'trips' => "Total volume seluruh trip ({$totalTripVolume} L) tidak sama dengan Total Kuantitas ({$totalOrderVolume} L)."
            ])->withInput();
        }

        DB::transaction(function () use ($request, $soOrder) {
            $soOrder->update([
                'shop_id'              => $request->shop_id,
                'order_date'           => $request->order_date,
                'supplier_id'          => $request->supplier_id,
                'so_number'            => $request->so_number,
                'lo_number'            => $request->lo_number,
                'total_quantity_liter'  => $request->total_quantity_liter,
                'total_amount'         => $request->total_amount,
                'total_trips'          => $request->total_trips,
            ]);

            // Hapus trips lama & buat ulang
            $soOrder->trips()->delete();

            foreach ($request->trips as $index => $tripData) {
                SoOrderTrip::create([
                    'so_order_id'            => $soOrder->id,
                    'trip_number'            => $index + 1,
                    'estimated_delivery_date' => $tripData['estimated_delivery_date'],
                    'quantity_liter'         => $tripData['quantity_liter'],
                    'status'                 => 'PENDING',
                ]);
            }
        });

        return to_route('so-orders.index')->with('success', 'Order SO berhasil diperbarui.');
    }

    /**
     * Remove the specified SO Order.
     * Hanya bisa hapus jika status PENDING (belum ada trip diterima).
     */
    public function destroy(SoOrder $soOrder)
    {
        $hasReceived = $soOrder->trips()->where('status', 'RECEIVED')->exists();
        if ($hasReceived) {
            return response()->json([
                'message' => 'Tidak dapat menghapus Order SO yang sudah memiliki trip diterima.'
            ], 422);
        }

        $soOrder->delete(); // Cascade deletes trips

        return response()->json([
            'message' => 'Order SO berhasil dihapus.'
        ]);
    }

    /**
     * API: Mengambil daftar trip PENDING untuk Pertashop tertentu.
     * Digunakan oleh halaman Operator Penerimaan BBM.
     */
    public function pendingTripsForShop(Request $request)
    {
        $shopId = $request->input('shop_id');

        if (!$shopId) {
            return response()->json([]);
        }

        $pendingTrips = SoOrderTrip::with('soOrder.supplier')
            ->whereHas('soOrder', function ($q) use ($shopId) {
                $q->where('shop_id', $shopId)
                  ->whereIn('status', ['PENDING', 'PARTIAL']);
            })
            ->where('status', 'PENDING')
            ->orderBy('estimated_delivery_date')
            ->get()
            ->map(function ($trip) {
                return [
                    'id'              => $trip->id,
                    'so_order_id'     => $trip->so_order_id,
                    'trip_number'     => $trip->trip_number,
                    'total_trips'     => $trip->soOrder->total_trips,
                    'so_number'       => $trip->soOrder->so_number,
                    'supplier'        => $trip->soOrder->supplier->nama ?? '-',
                    'quantity_liter'  => $trip->quantity_liter,
                    'delivery_date'   => $trip->estimated_delivery_date->format('d/m/Y'),
                    'label'           => $trip->dropdown_label,
                ];
            });

        return response()->json($pendingTrips);
    }
}
