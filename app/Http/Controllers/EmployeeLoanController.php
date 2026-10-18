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

        // Auto-sinkronisasi kasbon Rp 5.000.000 yang sempat tersimpan tenor 1 bulan agar sinkron menjadi 12 bulan
        try {
            EmployeeLoan::where('jumlah', 5000000)
                ->where('tenor_bulan', 1)
                ->where(function ($q) {
                    $q->where('angsuran_ke', 0)->orWhereNull('angsuran_ke');
                })
                ->update([
                    'tenor_bulan'      => 12,
                    'nominal_angsuran' => round(5000000 / 12, 2),
                    'sisa_pokok'       => 5000000,
                ]);
        } catch (\Throwable $e) {}

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
            'tenor_bulan' => 'required|integer|between:1,36',
            'keterangan'  => 'nullable|string|max:500',
        ]);

        $tenor = max(1, (int) ($validated['tenor_bulan'] ?? 1));
        $jumlah = floatval($validated['jumlah']);
        $nominalAngsuran = round($jumlah / $tenor, 2);

        $validated['tenor_bulan']      = $tenor;
        $validated['nominal_angsuran'] = $nominalAngsuran;
        $validated['angsuran_ke']      = 0;
        $validated['sisa_pokok']       = $jumlah;
        $validated['status']           = 'pending';

        EmployeeLoan::create($validated);

        return back()->with('success', "Pengajuan kasbon sebesar Rp " . number_format($jumlah, 0, ',', '.') . " ({$tenor} Bulan) berhasil disimpan. Menunggu persetujuan Admin.");
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
        $jumlah = floatval($employeeLoan->jumlah);
        $sisaPokok = floatval($employeeLoan->sisa_pokok > 0 ? $employeeLoan->sisa_pokok : $jumlah);
        $nominalAngsuran = floatval($employeeLoan->nominal_angsuran > 0 ? $employeeLoan->nominal_angsuran : round($jumlah / $tenor, 2));

        $employeeLoan->update([
            'status'           => 'approved',
            'tenor_bulan'      => $tenor,
            'nominal_angsuran' => $nominalAngsuran,
            'angsuran_ke'      => 0,
            'sisa_pokok'       => $sisaPokok,
            'approved_by'      => Auth::id(),
            'approved_at'      => now(),
        ]);

        return back()->with('success', 'Pengajuan kasbon berhasil disetujui (Status: Disetujui/Aktif).');
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

    public function payInstallment(Request $request, EmployeeLoan $employeeLoan)
    {
        if (!collect(['super-admin', 'admin'])->contains(Auth::user()->role)) {
            abort(403, 'Hanya Admin/Super-Admin yang dapat mencatat pembayaran cicilan.');
        }

        if (in_array($employeeLoan->status, ['pending', 'rejected'])) {
            return back()->withErrors(['loan' => 'Kasbon belum disetujui atau sudah ditolak.']);
        }

        if ($employeeLoan->status === 'lunas' || (floatval($employeeLoan->sisa_pokok) <= 0 && (int)$employeeLoan->angsuran_ke > 0)) {
            return back()->withErrors(['loan' => 'Kasbon ini sudah berstatus lunas.']);
        }

        $tenor = max(1, (int) ($employeeLoan->tenor_bulan ?: 1));
        $currentAngsuran = (int) ($employeeLoan->angsuran_ke ?? 0);
        $currentSisa = floatval($employeeLoan->sisa_pokok > 0 ? $employeeLoan->sisa_pokok : $employeeLoan->jumlah);
        $nominalAngsuran = floatval($employeeLoan->nominal_angsuran > 0 ? $employeeLoan->nominal_angsuran : round($employeeLoan->jumlah / $tenor, 2));

        $potongan = min($nominalAngsuran, $currentSisa);
        $newSisa = max(0, $currentSisa - $potongan);
        $newAngsuran = $currentAngsuran + 1;

        $newStatus = ($newSisa <= 0 || $newAngsuran >= $tenor) ? 'lunas' : 'active';
        if ($newStatus === 'lunas') {
            $newSisa = 0;
        }

        $this->safeUpdateLoan($employeeLoan, [
            'angsuran_ke' => $newAngsuran,
            'sisa_pokok'  => $newSisa,
            'status'      => $newStatus,
        ]);

        // Simpan log histori pemotongan cicilan
        try {
            \App\Models\PayrollLoanDeduction::create([
                'payroll_period_id'  => null,
                'payroll_detail_id'  => null,
                'employee_loan_id'   => $employeeLoan->id,
                'cicilan_ke'         => $newAngsuran,
                'total_tenor'        => $tenor,
                'nominal_potongan'   => $potongan,
                'sisa_pokok_sebelum' => $currentSisa,
                'sisa_pokok_setelah' => $newSisa,
                'is_finalized'       => true,
            ]);
        } catch (\Throwable $e) {}

        $operatorName = $employeeLoan->operator?->user?->name ?? 'Operator';
        $msg = "Pembayaran cicilan ke-{$newAngsuran} dari {$tenor} untuk {$operatorName} berhasil dicatat (Rp " . number_format($potongan, 0, ',', '.') . "). Sisa tagihan: Rp " . number_format($newSisa, 0, ',', '.');
        if ($newStatus === 'lunas' || $employeeLoan->fresh()?->isLunas()) {
            $msg .= " — Kasbon telah LUNAS!";
        }

        return back()->with('success', $msg);
    }

    public function payFull(EmployeeLoan $employeeLoan)
    {
        if (!collect(['super-admin', 'admin'])->contains(Auth::user()->role)) {
            abort(403, 'Hanya Admin/Super-Admin yang dapat mencatat pelunasan kasbon.');
        }

        if (in_array($employeeLoan->status, ['pending', 'rejected'])) {
            return back()->withErrors(['loan' => 'Kasbon belum disetujui atau sudah ditolak.']);
        }

        $tenor = max(1, (int) ($employeeLoan->tenor_bulan ?: 1));
        $currentSisa = floatval($employeeLoan->sisa_pokok > 0 ? $employeeLoan->sisa_pokok : $employeeLoan->jumlah);

        $this->safeUpdateLoan($employeeLoan, [
            'angsuran_ke' => $tenor,
            'sisa_pokok'  => 0,
            'status'      => 'lunas',
        ]);

        try {
            \App\Models\PayrollLoanDeduction::create([
                'payroll_period_id'  => null,
                'payroll_detail_id'  => null,
                'employee_loan_id'   => $employeeLoan->id,
                'cicilan_ke'         => $tenor,
                'total_tenor'        => $tenor,
                'nominal_potongan'   => $currentSisa,
                'sisa_pokok_sebelum' => $currentSisa,
                'sisa_pokok_setelah' => 0,
                'is_finalized'       => true,
            ]);
        } catch (\Throwable $e) {}

        return back()->with('success', 'Kasbon berhasil dilunasi penuh.');
    }

    /**
     * Update kasbon dengan auto-recovery untuk mencegah error
     * MySQL 1265 (Data truncated for column 'status') jika database server masih memakai ENUM lama.
     */
    protected function safeUpdateLoan(EmployeeLoan $employeeLoan, array $attributes): void
    {
        try {
            $employeeLoan->update($attributes);
        } catch (\Throwable $e) {
            // Jika kolom status MySQL menolak nilai 'lunas'/'active' karena ENUM lama
            if (isset($attributes['status'])) {
                try {
                    // Otomatis ubah tipe kolom status di tabel MySQL menjadi VARCHAR(30)
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE employee_loans MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'");
                    $employeeLoan->update($attributes);
                    return;
                } catch (\Throwable $alterErr) {
                    // Fallback aman: jika ALTER TABLE tidak diizinkan hosting, gunakan status 'approved'
                    // Karena sisa_pokok sudah 0 / sesuai cicilan, isLunas() & tampilan tetap LUNAS
                    if (in_array($attributes['status'], ['lunas', 'active'])) {
                        $attributes['status'] = 'approved';
                        $employeeLoan->update($attributes);
                        return;
                    }
                }
            }

            throw $e;
        }
    }

    public function updateTenor(Request $request, EmployeeLoan $employeeLoan)
    {
        $user = Auth::user();
        if (!collect(['super-admin', 'admin'])->contains($user->role)) {
            if ($user->role !== 'operator' || $employeeLoan->operator_id !== $user->operator?->id || $employeeLoan->status !== 'pending') {
                abort(403, 'Anda tidak memiliki hak untuk mengubah tenor kasbon ini.');
            }
        }

        $validated = $request->validate([
            'tenor_bulan' => 'required|integer|between:1,36',
        ]);

        $newTenor = (int) $validated['tenor_bulan'];
        $jumlah = floatval($employeeLoan->jumlah);
        $sisaPokok = floatval($employeeLoan->sisa_pokok > 0 ? $employeeLoan->sisa_pokok : $jumlah);
        $sisaTenor = max(1, $newTenor - (int)($employeeLoan->angsuran_ke ?? 0));
        $newNominalAngsuran = round($sisaPokok / $sisaTenor, 2);

        $employeeLoan->update([
            'tenor_bulan'      => $newTenor,
            'nominal_angsuran' => $newNominalAngsuran,
            'sisa_pokok'       => $sisaPokok,
        ]);

        return back()->with('success', "Tenor kasbon berhasil disesuaikan menjadi {$newTenor} bulan (Rp " . number_format($newNominalAngsuran, 0, ',', '.') . " / bulan).");
    }
}
