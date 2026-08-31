<?php

namespace App\Http\Controllers;

use App\Models\Corporation;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\Return_;
use Yajra\DataTables\Facades\DataTables;

class CorporationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $corporations = Corporation::with(['shops.investors'])->get();

        if ($request->ajax()) {
            return DataTables::of($corporations)
                ->addIndexColumn()
                ->addColumn('total_outlets', function ($row) {
                    return $row->shops->count() . ' Outlet';
                })
                ->addColumn('total_valuasi', function ($row) {
                    $total = $row->shops->sum(function($s) {
                        return $s->investors->sum('pivot.nominal') ?: ($s->modal_awal ?? 0);
                    });
                    return 'Rp ' . number_format($total, 0, ',', '.');
                })
                ->addColumn('outlets_list', function ($row) {
                    return $row->shops->pluck('nama')->join(', ') ?: '-';
                })
                ->addColumn('action', function ($row) {
                    $button = '<div class="btn-group" role="group">';
                    $button .= '<a href="' . route('corporations.edit', $row->id) . '" class="btn btn-sm btn-outline-primary" title="Edit Badan Usaha"><i class="fas fa-edit"></i></a>';
                    $button .= '<button class="btn btn-sm btn-outline-danger ml-1 btn-delete" title="Hapus Badan Usaha" data-id="' . $row->id . '"><i class="fas fa-trash-alt"></i></button>';
                    $button .= '</div>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $totalCorporationsCount = $corporations->count();
        $totalValuasiAll = $corporations->sum(function($c) {
            return $c->shops->sum(function($s) {
                return $s->investors->sum('pivot.nominal') ?: ($s->modal_awal ?? 0);
            });
        });
        $totalOutletsCovered = $corporations->sum(fn($c) => $c->shops->count());

        return view('corporation.index', compact('corporations', 'totalCorporationsCount', 'totalValuasiAll', 'totalOutletsCovered'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('corporation.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $valiadated = $request->validate(['nama' => 'required', 'alamat' => 'required']);

        Corporation::create($valiadated);

        return redirect()->route('corporations.index')->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Corporation $corporation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Corporation $corporation)
    {
        return view('corporation.edit', compact('corporation'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Corporation $corporation)
    {
        $valiadated = $request->validate(['nama' => 'required', 'alamat' => 'required']);

        $corporation->update($valiadated);

        return redirect()->route('corporations.index')->with('success', 'Data berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Corporation $corporation)
    {
        $corporation->delete();
        return response()->json([
            'message' => 'Data berhasil dihapus.',
        ]);
    }

    public function toggleStatus(Request $request, Corporation $corporation)
    {
        $isActive = $request->input('is_active');
        $corporation->is_active = $isActive !== null ? (bool)$isActive : !$corporation->is_active;
        $corporation->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'is_active' => (bool)$corporation->is_active,
                'message' => 'Status Badan Usaha berhasil diperbarui'
            ]);
        }

        return redirect()->back()->with('success', 'Status Badan Usaha berhasil diubah');
    }
}
