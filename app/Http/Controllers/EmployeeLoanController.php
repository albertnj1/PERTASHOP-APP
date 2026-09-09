<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLoan;
use App\Models\Operator;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeLoanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $shops = Shop::all();

        $query = EmployeeLoan::with(['operator.user', 'operator.shop', 'approver'])->latest();

        if ($user->role === 'admin') {
            $query->whereHas('operator', fn($q) => $q->where('shop_id', $user->admin?->shop_id));
            $operators = Operator::with(['user', 'shop'])->where('shop_id', $user->admin?->shop_id)->get();
        } elseif ($user->role === 'operator') {
            $operator = Operator::where('user_id', $user->id)->first();
            $query->where('operator_id', $operator?->id ?? 0);
            $operators = $operator ? collect([$operator]) : collect();
        } elseif ($user->role === 'investor') {
            $investorShopIds = $user->investor?->shops->pluck('id')->toArray() ?? [];
            $shops = $user->investor?->shops ?? collect();
            $query->whereHas('operator', fn($q) => $q->whereIn('shop_id', $investorShopIds));
            $operators = Operator::with(['user', 'shop'])->whereIn('shop_id', $investorShopIds)->get();
        } else {
            $operators = Operator::with(['user', 'shop'])->get();
        }

        $loans = $query->get();

        return view('employee_loans.index', compact('loans', 'shops', 'operators'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'operator') {
            $operator = Operator::where('user_id', $user->id)->first();
            if (!$operator) {
                return back()->withErrors(['operator' => 'Data profil operator Anda tidak ditemukan.']);
            }
            $request->merge(['operator_id' => $operator->id]);
        }

        $validated = $request->validate([
            'operator_id' => 'required|exists:operators,id',
            'tanggal'     => 'required|date',
            'jumlah'      => 'required|numeric|min:1000',
            'tenor_bulan' => 'nullable|integer|between:1,12',
            'keterangan'  => 'nullable|string|max:500',
        ]);

        $tenor = !empty($validated['tenor_bulan']) ? (int) $validated['tenor_bulan'] : 1;
        $jumlah = floatval($validated['jumlah']);
        $nominalAngsuran = round($jumlah / $tenor, 2);

        $validated['tenor_bulan']      = $tenor;
        $validated['nominal_angsuran'] = $nominalAngsuran;
        $validated['angsuran_ke']      = 0;
        $validated['sisa_pokok']       = $jumlah;
        $validated['status']           = 'pending';

        EmployeeLoan::create($validated);

        return back()->with('success', 'Pengajuan kasbon/hutang berhasil disimpan. Menunggu persetujuan Admin.');
    }

    public function approve(EmployeeLoan $employeeLoan)
    {
        if (!collect(['super-admin', 'admin'])->contains(Auth::user()->role)) {
            abort(403, 'Hanya Admin/Super-Admin yang dapat menyetujui kasbon.');
        }

        if ($employeeLoan->status !== 'pending') {
            return back()->withErrors(['loan' => 'Pengajuan kasbon ini sudah diproses sebelumnya.']);
        }

        $tenor = max(1, (int) ($employeeLoan->tenor_bulan ?: 1));
        $sisaPokok = floatval($employeeLoan->sisa_pokok > 0 ? $employeeLoan->sisa_pokok : $employeeLoan->jumlah);
        $nominalAngsuran = floatval($employeeLoan->nominal_angsuran > 0 ? $employeeLoan->nominal_angsuran : round($employeeLoan->jumlah / $tenor, 2));

        $employeeLoan->update([
            'status'           => 'approved',
            'tenor_bulan'      => $tenor,
            'nominal_angsuran' => $nominalAngsuran,
            'sisa_pokok'       => $sisaPokok,
            'approved_by'      => Auth::id(),
            'approved_at'      => now(),
        ]);

        return back()->with('success', 'Pengajuan kasbon berhasil disetujui.');
    }

    public function reject(EmployeeLoan $employeeLoan)
    {
        if (!collect(['super-admin', 'admin'])->contains(Auth::user()->role)) {
            abort(403, 'Hanya Admin/Super-Admin yang dapat menolak kasbon.');
        }

        if ($employeeLoan->status !== 'pending') {
            return back()->withErrors(['loan' => 'Pengajuan kasbon ini sudah diproses sebelumnya.']);
        }

        $employeeLoan->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan kasbon telah ditolak.');
    }
}
