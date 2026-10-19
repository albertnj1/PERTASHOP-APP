<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLoan;
use App\Models\Operator;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeLoanController extends Controller
{
    public function __construct()
    {
        $this->ensureLoanSchema();
    }

    /**
     * Pastikan kolom cicilan ada di tabel employee_loans.
     * Database server produksi ternyata belum menjalankan migrasi tenor, sehingga
     * kolom-kolom ini ditambahkan otomatis (masing-masing independen agar satu
     * kegagalan tidak membatalkan yang lain).
     */
    protected function ensureLoanSchema(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $columns = [
            'tenor_bulan'      => "INT UNSIGNED NOT NULL DEFAULT 1",
            'nominal_angsuran' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
            'angsuran_ke'      => "INT UNSIGNED NOT NULL DEFAULT 0",
            'sisa_pokok'       => "DECIMAL(12,2) NOT NULL DEFAULT 0",
        ];

        $added = [];
        foreach ($columns as $name => $definition) {
            try {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('employee_loans', $name)) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `employee_loans` ADD COLUMN `{$name}` {$definition}");
                    $added[] = $name;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Gagal menambah kolom employee_loans.{$name}: " . $e->getMessage());
            }
        }

        // Status lama berupa ENUM tanpa 'active'/'lunas' → ubah ke VARCHAR
        if (!empty($added)) {
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `employee_loans` MODIFY `status` VARCHAR(30) NOT NULL DEFAULT 'pending'");
            } catch (\Throwable $e) {}

            // Isi data lama: anggap belum dicicil, sisa = nominal pinjaman
            try {
                \Illuminate\Support\Facades\DB::statement("UPDATE `employee_loans` SET `nominal_angsuran` = ROUND(`jumlah` / GREATEST(`tenor_bulan`, 1), 2) WHERE `nominal_angsuran` <= 0");
                \Illuminate\Support\Facades\DB::statement("UPDATE `employee_loans` SET `sisa_pokok` = `jumlah` WHERE `sisa_pokok` <= 0 AND `angsuran_ke` = 0");
            } catch (\Throwable $e) {}
        }
    }
    public function index(Request $request)
    {
        $user = Auth::user();
        $shops = Shop::all();

        // Auto-heal universal:
        // Kasbon yang belum pernah dicicil (angsuran_ke = 0) tetapi statusnya 'lunas' atau sisa_pokok-nya 0
        // otomatis diperbaiki agar statusnya kembali 'approved' dan sisa_pokok sama dengan nominal pinjaman awal.
        try {
            EmployeeLoan::where('status', '!=', 'rejected')
                ->where(function ($q) {
                    $q->where('angsuran_ke', 0)->orWhereNull('angsuran_ke');
                })
                ->where(function ($q) {
                    $q->where('sisa_pokok', '<=', 0)
                      ->orWhereNull('sisa_pokok')
                      ->orWhere('status', 'lunas');
                })
                ->each(function ($loan) {
                    $tenor = max(1, (int) ($loan->tenor_bulan ?: 1));
                    $jumlah = floatval($loan->jumlah);
                    $loan->update([
                        'status'           => 'approved',
                        'sisa_pokok'       => $jumlah,
                        'nominal_angsuran' => round($jumlah / $tenor, 2),
                    ]);
                });
        } catch (\Throwable $e) {}

        $query = EmployeeLoan::with(['operator.user', 'operator.shop', 'approver'])->latest();

        if ($user->role === 'admin') {
            $adminShopId = $user->admin ? $user->admin->shop_id : null;
            $query->whereHas('operator', fn($q) => $q->where('shop_id', $adminShopId));
            $operators = Operator::with(['user', 'shop'])->where('shop_id', $adminShopId)->get();
        } elseif ($user->role === 'operator') {
            $operator = Operator::where('user_id', $user->id)->first();
            $query->where('operator_id', $operator ? $operator->id : 0);
            $operators = $operator ? collect([$operator]) : collect();
        } elseif ($user->role === 'investor') {
            $investor = $user->investor;
            $investorShops = ($investor && $investor->shops) ? $investor->shops : collect();
            $investorShopIds = $investorShops->pluck('id')->toArray();
            $shops = $investorShops;
            $query->whereHas('operator', fn($q) => $q->whereIn('shop_id', $investorShopIds));
            $operators = Operator::with(['user', 'shop'])->whereIn('shop_id', $investorShopIds)->get();
        } else {
            $operators = Operator::with(['user', 'shop'])->get();
        }

        $loans = $query->get();

        // Ringkasan finansial khusus Investor (read-only, hanya outlet miliknya)
        $investorSummary = null;
        if ($user->role === 'investor') {
            $piutangAktif = 0;
            $cicilanBulanIni = 0;
            $totalLunas = 0;
            $jumlahLunas = 0;

            foreach ($loans as $loan) {
                if ($loan->status === 'rejected' || $loan->status === 'pending') {
                    continue;
                }
                $ke = (int) ($loan->angsuran_ke ?? 0);
                $tenor = max(1, (int) ($loan->tenor_bulan ?: 1));
                $isLunas = $loan->status === 'lunas' || ($ke > 0 && floatval($loan->sisa_pokok) <= 0);

                if ($isLunas) {
                    $totalLunas += floatval($loan->jumlah);
                    $jumlahLunas++;
                    continue;
                }

                $sisa = floatval($loan->sisa_pokok > 0 ? $loan->sisa_pokok : $loan->jumlah);
                $angsuran = floatval($loan->nominal_angsuran > 0 ? $loan->nominal_angsuran : round($loan->jumlah / $tenor, 2));
                $piutangAktif += $sisa;
                $cicilanBulanIni += min($angsuran, $sisa);
            }

            $investorSummary = [
                'piutang_aktif'    => $piutangAktif,
                'cicilan_bulan_ini' => $cicilanBulanIni,
                'total_lunas'      => $totalLunas,
                'jumlah_lunas'     => $jumlahLunas,
            ];
        }

        return view('employee_loans.index', compact('loans', 'shops', 'operators', 'investorSummary'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'investor') {
            abort(403, 'Investor hanya memiliki akses baca (read-only) pada data kasbon.');
        }

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

        $loan = EmployeeLoan::create($validated);

        // Verifikasi: pastikan tenor benar-benar tersimpan di database
        $savedTenor = (int) \Illuminate\Support\Facades\DB::table('employee_loans')->where('id', $loan->id)->value('tenor_bulan');
        if ($savedTenor !== $tenor) {
            return back()->withErrors(['loan' => "Kasbon tersimpan tetapi tenor tercatat {$savedTenor} bulan (seharusnya {$tenor}). Jalankan /migrasi-server lalu sesuaikan tenor."]);
        }

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

        $operatorName = ($employeeLoan->operator && $employeeLoan->operator->user) ? $employeeLoan->operator->user->name : 'Operator';
        $msg = "Pembayaran cicilan ke-{$newAngsuran} dari {$tenor} untuk {$operatorName} berhasil dicatat (Rp " . number_format($potongan, 0, ',', '.') . "). Sisa tagihan: Rp " . number_format($newSisa, 0, ',', '.');
        $freshLoan = $employeeLoan->fresh();
        if ($newStatus === 'lunas' || ($freshLoan && $freshLoan->isLunas())) {
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
            $ownOperatorId = $user->operator ? $user->operator->id : null;
            if ($user->role !== 'operator' || $employeeLoan->operator_id !== $ownOperatorId || $employeeLoan->status !== 'pending') {
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

        // Verifikasi: pastikan tenor benar-benar tersimpan di database
        $savedTenor = (int) \Illuminate\Support\Facades\DB::table('employee_loans')->where('id', $employeeLoan->id)->value('tenor_bulan');
        if ($savedTenor !== $newTenor) {
            return back()->withErrors(['loan' => "Tenor gagal tersimpan (database masih mencatat {$savedTenor} bulan). Jalankan /migrasi-server lalu coba lagi."]);
        }

        return back()->with('success', "Tenor kasbon berhasil disesuaikan menjadi {$newTenor} bulan (Rp " . number_format($newNominalAngsuran, 0, ',', '.') . " / bulan).");
    }
}
