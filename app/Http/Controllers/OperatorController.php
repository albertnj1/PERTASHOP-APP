<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class OperatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            if (Auth::user()->role == 'admin') {
                $shop_id = Auth::user()->admin->shop_id;
                $operators = Operator::with(['user', 'shop'])->where('shop_id', $shop_id)->latest()->get();
            } elseif (Auth::user()->role == 'investor') {
                $investor_shops = Auth::user()->investor->shops->pluck('id');
                $operators = Operator::with(['user', 'shop'])->whereIn('shop_id', $investor_shops)->latest()->get();
            } else {
                $shop_id = $request->input('shop_id');
                if ($shop_id) {
                    $operators = Operator::with(['user', 'shop'])->where('shop_id', $shop_id)->latest()->get();
                } else {
                    $operators = Operator::with(['user', 'shop'])->latest()->get();
                }
            }

            return DataTables::of($operators)
                ->addIndexColumn()
                ->addColumn('status', function ($row) {
                    if ($row->user && $row->user->is_active) {
                        return '<span class="badge-modern-success"><i class="fas fa-check-circle mr-1"></i> AKTIF</span>';
                    } else {
                        return '<span class="badge-modern-secondary"><i class="fas fa-lock mr-1"></i> NON-AKTIF</span>';
                    }
                })
                ->addColumn('action', function ($row) {
                    $button = '<a href="' . route('operators.show', $row->id) . '" class="btn-action-modern btn-info-modern" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white;" title="Preview"><i class="fa fa-eye"></i></a>';

                    if (Auth::user()->role == 'investor') return '<div class="d-flex align-items-center">' . $button . '</div>';
                    
                    $isActive = $row->user ? $row->user->is_active : true;
                    
                    $button .= '<a href="' . route('operators.edit', $row->id) . '" class="btn-action-modern btn-edit-modern" title="Edit"><i class="fa fa-edit"></i></a>';
                    
                    if ($isActive) {
                        $button .= '<button class="btn-action-modern btn-lock-modern btn-toggle-status" title="Nonaktifkan (Kunci)" data-id="' . $row->id . '"><i class="fas fa-lock"></i></button>';
                    } else {
                        $button .= '<button class="btn-action-modern btn-unlock-modern btn-toggle-status" title="Aktifkan Kembali" data-id="' . $row->id . '"><i class="fas fa-unlock"></i></button>';
                    }

                    $button .= '<button class="btn-action-modern btn-delete-modern btn-delete" title="Hapus Permanen" data-id="' . $row->id . '"><i class="fa fa-trash"></i></button>';

                    return '<div class="d-flex align-items-center">' . $button . '</div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $shops = Shop::all();

        return view('operator.index', compact('shops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shops = Shop::all();

        return view('operator.create', compact('shops'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'shop_id' => 'required',
            'name' => 'required',
            'alamat' => 'required',
            'no_hp' => 'required',
            'no_rekening' => 'required',
            'nama_bank' => 'required',
            'atas_nama_rekening' => 'required',
            'nik' => 'required',
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required',
            'agama' => 'required',
            'status_perkawinan' => 'required',
            'pekerjaan' => 'required',
            'kewarganegaraan' => 'required',
            'no_kk' => 'required',
            'nama_ayah' => 'required',
            'nama_ibu' => 'required',
            'status_hubungan' => 'required',
            'email_pribadi' => 'nullable|email',
            'akun_medsos' => 'nullable',
            'pendidikan_terakhir' => 'required',
            'asal_sekolah' => 'nullable',
            'golongan_darah' => 'required',
            'nomor_paspor' => 'nullable',
            'nomor_sim' => 'nullable',
            'jenis_sim' => 'nullable',
            'nomor_bpjs' => 'nullable',
            'nomor_npwp' => 'required',
            'pas_foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

        // Removed email pertashop rules

        $validated = $request->validate($rules);
        
        if ($request->hasFile('pas_foto')) {
            $validated['pas_foto'] = $request->file('pas_foto')->store('pas_foto', 'public');
        }

        $baseEmail = strtolower(str_replace(' ', '.', trim($request->name))) . '@pertashop.com';
        $email = $baseEmail;
        $counter = 1;
        while (\App\Models\User::where('email', $email)->exists()) {
            $email = strtolower(str_replace(' ', '.', trim($request->name))) . $counter . '@pertashop.com';
            $counter++;
        }

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make('123'),
            'encrypted_password' => Crypt::encryptString('123'),
            'role' => 'operator',
            'shop_id' => $request->shop_id,
        ]);

        $validated['user_id'] = $user->id;

        Operator::create($validated);

        return redirect()->route('operators.index')->with('success', 'Operator berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Operator $operator)
    {
        return view('operator.show', compact('operator'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Operator $operator)
    {
        $shops = Shop::all();

        return view('operator.edit', compact('operator', 'shops'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Operator $operator)
    {
        $rules = [
            'shop_id' => 'required',
            'name' => 'required',
            'alamat' => 'required',
            'no_hp' => 'required',
            'no_rekening' => 'required',
            'nama_bank' => 'required',
            'password' => 'nullable',
            'atas_nama_rekening' => 'required',
            'nik' => 'required',
            'tempat_lahir' => 'required',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required',
            'agama' => 'required',
            'status_perkawinan' => 'required',
            'pekerjaan' => 'required',
            'kewarganegaraan' => 'required',
            'no_kk' => 'required',
            'nama_ayah' => 'required',
            'nama_ibu' => 'required',
            'status_hubungan' => 'required',
            'email_pribadi' => 'nullable|email',
            'akun_medsos' => 'nullable',
            'pendidikan_terakhir' => 'required',
            'asal_sekolah' => 'nullable',
            'golongan_darah' => 'required',
            'nomor_paspor' => 'nullable',
            'nomor_sim' => 'nullable',
            'jenis_sim' => 'nullable',
            'nomor_bpjs' => 'nullable',
            'nomor_npwp' => 'required',
            'pas_foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

        // Removed email pertashop rules

        if (\Illuminate\Support\Facades\Auth::user()->role === 'super-admin') {
            $rules['email'] = 'required|email|unique:users,email,' . $operator->user_id;
        }

        $validated = $request->validate($rules);
        
        if ($request->hasFile('pas_foto')) {
            if ($operator->pas_foto) {
                Storage::disk('public')->delete($operator->pas_foto);
            }
            $validated['pas_foto'] = $request->file('pas_foto')->store('pas_foto', 'public');
        }

        $operator->update($validated);
        $updateData = [
            'name' => $request->name,
        ];
        if (\Illuminate\Support\Facades\Auth::user()->role === 'super-admin' && $request->has('email')) {
            $updateData['email'] = $request->email;
        }
        $operator->user->update($updateData);

        if ($request->password) {
            $operator->user->update([
                'password' => bcrypt($request->password),
                'encrypted_password' => Crypt::encryptString($request->password),
                'password_changed_at' => now(),
            ]);
        }

        return redirect()->route('operators.index')->with('success', 'Operator berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Operator $operator)
    {
        $operator->delete();
        $operator->user->delete();
        return response()->json([
            'message' => 'Data berhasil dihapus.',
        ]);
    }

    public function updateCredentials(Request $request, Operator $operator)
    {
        if (\Illuminate\Support\Facades\Auth::user()->role !== 'super-admin') {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email,' . $operator->user_id,
            'password' => 'nullable|string|min:3',
        ]);

        $updateData = ['email' => $validated['email']];
        if (!empty($validated['password'])) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $operator->user->update($updateData);

        return redirect()->back()->with('success', 'Kredensial login berhasil diperbarui');
    }

    public function toggleStatus(Operator $operator)
    {
        $user = $operator->user;
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return response()->json([
            'message' => "Akun operator berhasil $status.",
            'is_active' => $user->is_active
        ]);
    }
}
