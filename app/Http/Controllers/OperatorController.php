<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                    $isActive = $row->user ? $row->user->is_active : true;
                    
                    $button = '<a href="' . route('operators.edit', $row->id) . '" class="btn-action-modern btn-edit-modern" title="Edit"><i class="fa fa-edit"></i></a>';
                    
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
        $validated = $request->validate([
            'shop_id' => 'required',
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'alamat' => 'nullable',
            'no_hp' => 'nullable',
            'no_rekening' => 'nullable',
            'nama_bank' => 'nullable',
            'atas_nama_rekening' => 'nullable',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'operator',
            'password' => Hash::make(123),
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
        //
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
        $validated = $request->validate([
            'shop_id' => 'required',
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $operator->user_id,
            'alamat' => 'nullable',
            'no_hp' => 'nullable',
            'no_rekening' => 'nullable',
            'nama_bank' => 'nullable',
            'pasasword' => 'nullable',
            'atas_nama_rekening' => 'nullable',
        ]);

        $operator->update($validated);
        $operator->user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->password) {
            $operator->user->update([
                'password' => Hash::make($request->password),
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
