<?php

namespace App\Http\Controllers;

use App\Models\Kolektan;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class KolektanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'super-admin') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        if ($request->ajax()) {
            $data = Kolektan::with('shop')->latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<a href="' . route('kolektans.edit', $row->id) . '" class="btn btn-info btn-sm" title="Edit"><i class="fa fa-edit"></i></a> ';
                    $btn .= '<button class="btn btn-danger btn-sm btn-delete" data-id="' . $row->id . '" title="Hapus"><i class="fa fa-trash"></i></button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('kolektan.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'super-admin') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }
        $shops = Shop::all();
        return view('kolektan.create', compact('shops'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'super-admin') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'nama_kolektan' => 'required|string|max:255',
            'pin' => 'required|string|min:6|max:6',
        ], [
            'shop_id.required' => 'Pertashop wajib dipilih.',
            'nama_kolektan.required' => 'Nama Kolektan wajib diisi.',
            'pin.required' => 'PIN wajib diisi.',
            'pin.min' => 'PIN harus 6 digit.',
            'pin.max' => 'PIN harus 6 digit.',
        ]);

        Kolektan::create($validated);
        return redirect()->route('kolektans.index')->with('success', 'Data Kolektan berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Kolektan $kolektan)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'super-admin') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }
        $shops = Shop::all();
        return view('kolektan.edit', compact('kolektan', 'shops'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kolektan $kolektan)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'super-admin') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'nama_kolektan' => 'required|string|max:255',
            'pin' => 'required|string|min:6|max:6',
        ], [
            'shop_id.required' => 'Pertashop wajib dipilih.',
            'nama_kolektan.required' => 'Nama Kolektan wajib diisi.',
            'pin.required' => 'PIN wajib diisi.',
            'pin.min' => 'PIN harus 6 digit.',
            'pin.max' => 'PIN harus 6 digit.',
        ]);

        $kolektan->update($validated);
        return redirect()->route('kolektans.index')->with('success', 'Data Kolektan berhasil diubah.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kolektan $kolektan)
    {
        if (Auth::user()->role !== 'admin' && Auth::user()->role !== 'super-admin') {
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }
        $kolektan->delete();
        return response()->json(['message' => 'Data Kolektan berhasil dihapus.']);
    }
}
