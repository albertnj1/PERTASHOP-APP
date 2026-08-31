<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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
                $investors = Investor::with(['user', 'shops'])
                    ->whereRelation('shops', 'shops.id', $shop_id)
                    ->get()
                    ->sortByDesc(function ($investor) {
                        return $investor->shops->sum('pivot.nominal');
                    })
                    ->values();
            } else {
                $shop_id = $request->input('shop_id');
                if ($shop_id) {
                    $investors = Investor::with(['user', 'shops'])
                        ->whereRelation('shops', 'shops.id', $shop_id)
                        ->get()
                        ->sortByDesc(function ($investor) {
                            return $investor->shops->sum('pivot.nominal');
                        })
                        ->values();
                } else {
                    $investors = Investor::with(['user', 'shops'])
                        ->get()
                        ->sortByDesc(function ($investor) {
                            return $investor->shops->sum('pivot.nominal');
                        })
                        ->values();
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
        $investorsList = Investor::with(['user', 'shops'])
            ->get()
            ->sortByDesc(function ($investor) {
                return $investor->shops->sum('pivot.nominal');
            })
            ->values();

        $totalCapitalAll = $investorsList->sum(function ($inv) {
            return $inv->shops->sum('pivot.nominal');
        });

        $totalInvestorsCount = $investorsList->count();
        $totalShopsCount = $shops->count();

        return view('investor.index', compact('shops', 'investorsList', 'totalCapitalAll', 'totalInvestorsCount', 'totalShopsCount'));
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
        $rules = [
            'name'                  => 'required|string|max:255',
            'nama_lengkap_gelar'    => 'required|string|max:255',
            'nik'                   => 'nullable|string|max:255',
            'nomor_npwp'            => 'nullable|string|max:255',
            'alamat_domisili'       => 'required|string',
            'email_pribadi'         => 'nullable|email|max:255',
            'no_hp'                 => 'required|string|max:255',
            'nama_bank'             => 'required|string|max:255',
            'no_rekening'           => 'required|string|max:255',
            'atas_nama_rekening'    => 'required|string|max:255',
            'shop_id'               => 'required|exists:shops,id',
            'nominal'               => 'required|numeric|min:1',
        ];

        // Removed email pertashop rules

        $validated = $request->validate($rules);

        $baseEmail = strtolower(str_replace(' ', '.', trim($request->name))) . '@pertashop.com';
        $email = $baseEmail;
        $counter = 1;
        while (\App\Models\User::where('email', $email)->exists()) {
            $email = strtolower(str_replace(' ', '.', trim($request->name))) . $counter . '@pertashop.com';
            $counter++;
        }

        $validated['email'] = $email;
        $validated['password'] = Hash::make(123);
        $validated['encrypted_password'] = Crypt::encryptString('123');
        $validated['role'] = 'investor';

        $user = User::create($validated);

        $validated['user_id'] = $user->id;

        $investor = Investor::create($validated);

        // Calculate persentase and attach to shop
        $shop = Shop::find($request->shop_id);
        $persentase = 0;
        if ($shop && $shop->modal_awal > 0) {
            $persentase = ($request->nominal / $shop->modal_awal) * 100;
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

        $shops = Shop::all();

        return view('investor.show', compact('investor', 'totalInvestasi', 'shops'));
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
        $rules = [
            'name'                  => 'required|string|max:255',
            'nama_lengkap_gelar'    => 'required|string|max:255',
            'nik'                   => 'nullable|string|max:255',
            'nomor_npwp'            => 'nullable|string|max:255',
            'alamat_domisili'       => 'required|string',
            'email_pribadi'         => 'nullable|email|max:255',
            'no_hp'                 => 'required|string|max:255',
            'nama_bank'             => 'required|string|max:255',
            'no_rekening'           => 'required|string|max:255',
            'atas_nama_rekening'    => 'required|string|max:255',
            'password'              => 'nullable|string|max:255'
        ];

        // Removed email pertashop rules

        if (\Illuminate\Support\Facades\Auth::user()->role === 'super-admin') {
            $rules['email'] = 'required|email|unique:users,email,' . $investor->user_id;
        }

        $validated = $request->validate($rules);

        $investor->update($validated);

        $updateData = [
            'name' => $request->name,
        ];
        if (\Illuminate\Support\Facades\Auth::user()->role === 'super-admin' && $request->has('email')) {
            $updateData['email'] = $request->email;
        }
        $investor->user->update($updateData);

        if ($request->password) {
            $investor->user->update([
                'password' => Hash::make($request->password),
                'encrypted_password' => Crypt::encryptString($request->password),
                'password_changed_at' => now(),
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
            'message' => 'Data berhasil dihapus.',
        ]);
    }

    public function updateCredentials(Request $request, Investor $investor)
    {
        if (\Illuminate\Support\Facades\Auth::user()->role !== 'super-admin') {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email,' . $investor->user_id,
            'password' => 'nullable|string|min:3',
        ]);

        $updateData = ['email' => $validated['email']];
        if (!empty($validated['password'])) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $investor->user->update($updateData);

        return redirect()->back()->with('success', 'Kredensial login berhasil diperbarui');
    }

    /**
     * Add investment to a shop for this investor
     */
    public function addInvestment(Request $request, Investor $investor)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'nominal' => 'required|numeric|min:1',
        ]);

        $shop = Shop::find($request->shop_id);
        $persentase = 0;
        if ($shop && $shop->modal_awal > 0) {
            $persentase = ($request->nominal / $shop->modal_awal) * 100;
        }

        // Check if already invested
        if ($investor->shops()->where('shop_id', $request->shop_id)->exists()) {
            return redirect()->back()->with('error', 'Investor sudah berinvestasi di Pertashop ini. Jika ingin mengubah, silakan edit data investasi.');
        }

        $investor->shops()->attach($request->shop_id, [
            'nominal' => $request->nominal,
            'persentase' => round($persentase, 2)
        ]);

        return redirect()->back()->with('success', 'Investasi baru berhasil ditambahkan.');
    }

    /**
     * Export laporan/ringkasan investasi ke PDF siap kirim.
     */
    public function exportPdf(Investor $investor)
    {
        $investor->load(['user', 'shops']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.investor_report', compact('investor'))
            ->setPaper('a4', 'portrait');

        $nameSlug = \Illuminate\Support\Str::slug($investor->name);
        return $pdf->download("Ringkasan_Investasi_{$nameSlug}.pdf");
    }

    public function toggleStatus(Request $request, Investor $investor)
    {
        $isActive = $request->input('is_active');
        $investor->is_active = $isActive !== null ? (bool)$isActive : !$investor->is_active;
        $investor->save();

        if ($investor->user) {
            $investor->user->is_active = $investor->is_active;
            $investor->user->save();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'is_active' => (bool)$investor->is_active,
                'message' => 'Status investor berhasil diperbarui'
            ]);
        }

        return redirect()->back()->with('success', 'Status investor berhasil diubah');
    }
}
