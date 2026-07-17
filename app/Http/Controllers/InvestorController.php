<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class InvestorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (Auth::user()->role == 'admin') {
                $shop_id = Auth::user()->admin->shop_id;
                $investors = Investor::with(['user', 'shops'])->whereRelation('shops', 'shops.id', $shop_id)->latest()->get();
            } else {
                $shop_id = $request->input('shop_id');
                if ($shop_id) {
                    $investors = Investor::with(['user', 'shops'])->whereRelation('shops', 'shops.id', $shop_id)->latest()->get();
                } else {
                    $investors = Investor::with(['user', 'shops'])->latest()->get();
                }
            }

            return DataTables::of($investors)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $button = '<a href="' . route('investors.show', $row->id) . '" class="btn btn-sm btn-info" title="View"><i class="fa fa-eye"></i> Detail</a>';
                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $shops = Shop::with('investors.user')->get();

        return view('investor.index', compact('shops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shops = Shop::all();
        return view('investor.create', compact('shops'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'no_hp'                 => 'nullable|string|max:255',
            'nama_bank'             => 'required|string|max:255',
            'no_rekening'           => 'required|string|max:255',
            'atas_nama_rekening'    => 'required|string|max:255',
            'shop_id'               => 'required|exists:shops,id',
            'nominal'               => 'required|numeric|min:1',
        ]);

        $validated['password'] = Hash::make(123);
        $validated['role'] = 'investor';

        $user = User::create($validated);

        $validated['user_id'] = $user->id;

        $investor = Investor::create($validated);

        // Calculate persentase and attach to shop
        $shop = Shop::find($request->shop_id);
        $persentase = 0;
        if ($shop && $shop->total_investasi > 0) {
            $persentase = ($request->nominal / $shop->total_investasi) * 100;
        }

        $investor->shops()->attach($request->shop_id, [
            'nominal' => $request->nominal,
            'persentase' => round($persentase, 2)
        ]);

        return redirect()->route('investors.index')->with('success', 'Investor berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Investor $investor)
    {
        $investor->load(['user', 'shops']);
        
        $totalInvestasi = 0;
        foreach ($investor->shops as $shop) {
            $totalInvestasi += $shop->pivot->nominal ?? 0;
        }

        return view('investor.show', compact('investor', 'totalInvestasi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Investor $investor)
    {
        return view('investor.edit', compact('investor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email,' . $investor->user_id,
            'no_hp'                 => 'nullable|string|max:255',
            'nama_bank'             => 'nullable|string|max:255',
            'no_rekening'           => 'nullable|string|max:255',
            'atas_nama_rekening'    => 'nullable|string|max:255',
            'password'              => 'nullable|string|max:255'
        ]);

        $investor->update($validated);

        $investor->user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->password) {
            $investor->user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        return redirect()->route('investors.index')->with('success', 'Investor berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Investor $investor)
    {
        $investor->delete();
        $investor->user->delete();

        return response()->json([
            'message' => 'Investor berhasil dihapus.',
        ]);
    }

    
}
