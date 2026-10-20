<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\PayrollPeriod;
use App\Models\PayrollSystem;
use App\Models\PeriodLock;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollSystemController extends Controller
{
    /**
     * Halaman utama Sistem Penggajian — 4 tab.
     * Tab 1: Pengaturan Gaji
     * Tab 2: Proses Gaji Bulanan
     * Tab 3: Kunci & Setujui
     * Tab 4: Perbandingan Toko (super-admin & investor only)
     */
    public function index(Request $request)
    {
        $user  = Auth::user();
        $shops = $this->getAccessibleShops();

        // Mode khusus Investor: Tampilan Read-Only Beban Penggajian & Terbatas ke Outlet Milik Sendiri
        if ($user->role === 'investor') {
            return $this->investorIndex($request, $shops);
        }

        // Toko & periode yang dipilih
        $selectedShopId = $request->input('shop_id', $shops->first()?->id);
        $selectedMonth  = $request->input('year_month', now()->format('Y-m'));
        $selectedTahun  = $request->input('tahun', now()->year);
        $selectedBulan  = (int) now()->month;

        // Ekstrak tahun & bulan dari year_month
        [$ymYear, $ymMonth] = array_map('intval', explode('-', $selectedMonth));

        // ─── TAB 1: Pengaturan Gaji ────────────────────────────────────────────
        // Ambil semua sistem penggajian (filter per toko jika admin)
        $payrollSystemsQuery = PayrollSystem::with('shop')->latest();
        if ($user->role === 'admin') {
            $payrollSystemsQuery->where('shop_id', $user->admin->shop_id);
        } elseif ($request->filled('shop_id')) {
            $payrollSystemsQuery->where('shop_id', $request->shop_id);
        }
        $payrollSystems = $payrollSystemsQuery->get();

        // Sistem aktif untuk toko yang dipilih (untuk form edit ringkas di Tab 1)
        $activePayrollSystem = PayrollSystem::where('shop_id', $selectedShopId)
            ->where('aktif', true)
            ->latest()
            ->first();

        // ─── TAB 2: Proses Gaji Bulanan ────────────────────────────────────────
        $periods = PayrollPeriod::with(['shop', 'payrollSystem', 'details.operator.user', 'generatedBy'])
            ->where('shop_id', $selectedShopId)
            ->where('tahun', $selectedTahun)
            ->orderByDesc('bulan')
            ->get();

        $availableYears  = range(now()->year, now()->year - 3);
        $periodStatusMap = $periods->pluck('status', 'bulan');

        // ─── TAB 3: Kunci & Setujui ────────────────────────────────────────────
        $isLocked      = PeriodLock::isLocked($selectedShopId, $selectedMonth);
        $periodLockObj = PeriodLock::where('shop_id', $selectedShopId)
            ->where('year_month', $selectedMonth)
            ->with('locker')
            ->first();

        // Hitung jumlah hari di bulan tersebut
        $jumlahHariBulan = Carbon::createFromDate($ymYear, $ymMonth, 1)->daysInMonth;

        // Hitung laporan harian yang sudah masuk
        $jumlahLaporanMasuk = DailyReport::where('shop_id', $selectedShopId)
            ->whereYear('created_at', $ymYear)
            ->whereMonth('created_at', $ymMonth)
            ->count();

        // Cek apakah semua laporan sudah diverifikasi (tidak ada yang masih DRAFT)
        $laporanBelumVerifikasi = DailyReport::where('shop_id', $selectedShopId)
            ->whereYear('created_at', $ymYear)
            ->whereMonth('created_at', $ymMonth)
            ->where(function ($q) {
                $q->whereNull('status_lifecycle')
                    ->orWhere('status_lifecycle', 'draft')
                    ->orWhere('status_lifecycle', 'imported');
            })
            ->count();

        $semuaLaporanTerverifikasi = ($jumlahLaporanMasuk > 0) && ($laporanBelumVerifikasi === 0);

        // ─── TAB 4: Perbandingan Toko (super-admin only) ────────────
        $benchmarkMonth   = $request->input('benchmark_month', now()->format('Y-m'));
        $benchmarks       = collect();
        $canViewBenchmark = in_array($user->role, ['super-admin', 'super_admin']);

        if ($canViewBenchmark) {
            [$bYear, $bMonth] = array_map('intval', explode('-', $benchmarkMonth));
            $allShops = Shop::all();

            $benchmarks = $allShops->map(function ($shop) use ($bYear, $bMonth) {
                $reports = DailyReport::where('shop_id', $shop->id)
                    ->whereYear('created_at', $bYear)
                    ->whereMonth('created_at', $bMonth)
                    ->get();

                $totalVol    = $reports->sum('volume_penjualan_teoritis');
                $totalRupiah = $reports->sum('rupiah_penjualan');
                $totalCost   = $reports->sum('total_spendings');
                $totalProfit = $reports->sum('pendapatan');
                $totalLosses = $reports->sum('losses_gain');

                return [
                    'shop_id'      => $shop->id,
                    'nama'         => $shop->nama,
                    'total_volume' => $totalVol,
                    'total_rupiah' => $totalRupiah,
                    'total_cost'   => $totalCost,
                    'total_profit' => $totalProfit,
                    'total_losses' => $totalLosses,
                ];
            })->sortByDesc('total_volume')->values();
        }

        return view('payroll_systems.index', compact(
            'shops',
            'selectedShopId',
            'selectedMonth',
            'selectedTahun',
            // Tab 1
            'payrollSystems',
            'activePayrollSystem',
            // Tab 2
            'periods',
            'availableYears',
            'periodStatusMap',
            // Tab 3
            'isLocked',
            'periodLockObj',
            'jumlahHariBulan',
            'jumlahLaporanMasuk',
            'semuaLaporanTerverifikasi',
            // Tab 4
            'canViewBenchmark',
            'benchmarks',
            'benchmarkMonth'
        ));
    }

    /**
     * Tampilan Khusus Role Investor:
     * - Mode Read-Only (Rekap Beban Penggajian Final)
     * - Tidak ada tombol hitung, edit rumus, atau kunci periode
     * - Hanya menampilkan outlet yang dimiliki investor
     * - Menampilkan data final saja: Nama Operator, Total Jam Kerja/Kehadiran, Total Nominal Gaji, Status Pembayaran
     * - Tab Perbandingan Outlet difilter hanya ke outlet milik investor tersebut
     */
    private function investorIndex(Request $request, $shops)
    {
        $investorShopIds = $shops->pluck('id')->toArray();
        $selectedShopId  = $request->input('shop_id', $shops->first()?->id);

        // Validasi keamanan: cegah investor memilih outlet di luar kepemilikan via parameter URL
        if (!in_array($selectedShopId, $investorShopIds)) {
            $selectedShopId = reset($investorShopIds) ?: null;
        }

        $selectedTahun  = (int) $request->input('tahun', now()->year);
        $selectedBulan  = $request->input('bulan', 'all');
        $availableYears = range(now()->year, now()->year - 3);

        // Sajikan data final saja: riwayat penggajian yang sudah disetujui & dikunci Super Admin
        $periodsQuery = PayrollPeriod::with([
                'shop',
                'payrollSystem',
                'details.operator.user',
                'generatedBy',
                'approver'
            ])
            ->where('shop_id', $selectedShopId)
            ->where('tahun', $selectedTahun)
            ->where('status', 'final')
            ->orderByDesc('bulan');

        if ($selectedBulan !== 'all' && is_numeric($selectedBulan)) {
            $periodsQuery->where('bulan', (int) $selectedBulan);
        }

        $periods = $periodsQuery->get();

        // Cek apakah ada draft periode untuk bulan ini yang belum difinalisasi Super Admin
        $hasPendingDraft = false;
        if ($selectedBulan !== 'all' && is_numeric($selectedBulan)) {
            $hasPendingDraft = PayrollPeriod::where('shop_id', $selectedShopId)
                ->where('tahun', $selectedTahun)
                ->where('bulan', (int) $selectedBulan)
                ->where('status', '!=', 'final')
                ->exists();
        }

        // Kalkulasi Agregat Finansial Rekap Gaji
        $totalBebanGaji = $periods->sum(function ($p) {
            return $p->details->sum(fn($d) => $d->hitungTHP());
        });

        $totalOperatorTergaji = $periods->flatMap->details->pluck('operator_id')->unique()->count();
        $totalHariKerja       = $periods->flatMap->details->sum('total_hari_kerja');

        // Tab 2 (Investor): Perbandingan Antar Outlet (HANYA outlet kepemilikan investor)
        $benchmarkMonth = $request->input('benchmark_month', now()->format('Y-m'));
        [$bYear, $bMonth] = array_map('intval', explode('-', $benchmarkMonth));

        $benchmarks = $shops->map(function ($shop) use ($bYear, $bMonth) {
            $reports = DailyReport::where('shop_id', $shop->id)
                ->whereYear('created_at', $bYear)
                ->whereMonth('created_at', $bMonth)
                ->get();

            $totalVol    = $reports->sum('volume_penjualan_teoritis');
            $totalRupiah = $reports->sum('rupiah_penjualan');
            $totalCost   = $reports->sum('total_spendings');
            $totalProfit = $reports->sum('pendapatan');
            $totalLosses = $reports->sum('losses_gain');

            $payrollFinal = PayrollPeriod::where('shop_id', $shop->id)
                ->where('tahun', $bYear)
                ->where('bulan', $bMonth)
                ->where('status', 'final')
                ->with('details')
                ->first();

            $totalGaji = $payrollFinal ? $payrollFinal->details->sum(fn($d) => $d->hitungTHP()) : 0;

            return [
                'shop_id'      => $shop->id,
                'nama'         => $shop->nama,
                'total_volume' => $totalVol,
                'total_rupiah' => $totalRupiah,
                'total_cost'   => $totalCost,
                'total_profit' => $totalProfit,
                'total_losses' => $totalLosses,
                'total_gaji'   => $totalGaji,
            ];
        })->sortByDesc('total_volume')->values();

        $selectedShop = $shops->firstWhere('id', $selectedShopId);

        $viewName = view()->exists('payroll_systems.investor_index')
            ? 'payroll_systems.investor_index'
            : (view()->exists('payroll_systems. investor_index') ? 'payroll_systems. investor_index' : 'payroll_systems.investor_index');

        return view($viewName, compact(
            'shops',
            'selectedShop',
            'selectedShopId',
            'selectedTahun',
            'selectedBulan',
            'availableYears',
            'periods',
            'hasPendingDraft',
            'totalBebanGaji',
            'totalOperatorTergaji',
            'totalHariKerja',
            'benchmarks',
            'benchmarkMonth'
        ));
    }

    public function create()
    {
        $shops = $this->getAccessibleShops();
        return view('payroll_systems.create', compact('shops'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_id'                  => 'required|exists:shops,id',
            'nama_sistem'              => 'required|string|max:255',
            'tipe_skema'               => 'required|in:komisi_murni,gaji_pokok_murni,hibrid',
            'rate_per_liter'           => 'nullable|numeric|min:0',
            'nominal_gaji_pokok'       => 'nullable|numeric|min:0',
            'rate_transport_per_hari'  => 'nullable|numeric|min:0',
            'potongan_per_hari_alpha'  => 'nullable|numeric|min:0',
            'metode_potongan_alpha'    => 'required|in:nominal_tetap,prorata_gaji_pokok',
            'perlakuan_losses_gain'    => 'required|in:losses_only,losses_dan_gain,abaikan_losses_gain',
            'metode_split'             => 'required|in:per_hari_penuh,proporsional_jam_kerja,flat_bulanan_prorata_hari',
            'standar_hari_kerja'       => 'nullable|integer|min:1|max:31',
            'aktif'                    => 'boolean',
        ]);

        $tipe = $validated['tipe_skema'];
        $validated['aktif']                   = $request->boolean('aktif', true);
        $validated['potongan_per_hari_alpha'] = $validated['potongan_per_hari_alpha'] ?? 0;
        $validated['rate_transport_per_hari'] = $validated['rate_transport_per_hari'] ?? 0;
        $validated['standar_hari_kerja']      = $validated['standar_hari_kerja'] ?? 26;

        if ($tipe === 'komisi_murni') {
            $validated['ada_rate_per_liter'] = true;
            $validated['rate_per_liter']     = $validated['rate_per_liter'] ?? 0;
            $validated['ada_gaji_pokok']     = false;
            $validated['nominal_gaji_pokok'] = null;
        } elseif ($tipe === 'gaji_pokok_murni') {
            $validated['ada_rate_per_liter'] = false;
            $validated['rate_per_liter']     = 0;
            $validated['ada_gaji_pokok']     = true;
            $validated['nominal_gaji_pokok'] = $validated['nominal_gaji_pokok'] ?? 0;
        } else { // hibrid
            $validated['ada_rate_per_liter'] = true;
            $validated['rate_per_liter']     = $validated['rate_per_liter'] ?? 0;
            $validated['ada_gaji_pokok']     = true;
            $validated['nominal_gaji_pokok'] = $validated['nominal_gaji_pokok'] ?? 0;
        }

        PayrollSystem::create($validated);

        return redirect()->route('payroll-systems.index')
            ->with('success', 'Pengaturan gaji berhasil ditambahkan.');
    }

    public function edit(PayrollSystem $payrollSystem)
    {
        $shops = $this->getAccessibleShops();
        return view('payroll_systems.edit', compact('payrollSystem', 'shops'));
    }

    public function update(Request $request, PayrollSystem $payrollSystem)
    {
        $validated = $request->validate([
            'shop_id'                  => 'required|exists:shops,id',
            'nama_sistem'              => 'required|string|max:255',
            'tipe_skema'               => 'required|in:komisi_murni,gaji_pokok_murni,hibrid',
            'rate_per_liter'           => 'nullable|numeric|min:0',
            'nominal_gaji_pokok'       => 'nullable|numeric|min:0',
            'rate_transport_per_hari'  => 'nullable|numeric|min:0',
            'potongan_per_hari_alpha'  => 'nullable|numeric|min:0',
            'metode_potongan_alpha'    => 'required|in:nominal_tetap,prorata_gaji_pokok',
            'perlakuan_losses_gain'    => 'required|in:losses_only,losses_dan_gain,abaikan_losses_gain',
            'metode_split'             => 'required|in:per_hari_penuh,proporsional_jam_kerja,flat_bulanan_prorata_hari',
            'standar_hari_kerja'       => 'nullable|integer|min:1|max:31',
            'aktif'                    => 'boolean',
        ]);

        $tipe = $validated['tipe_skema'];
        $validated['aktif']                   = $request->boolean('aktif', true);
        $validated['potongan_per_hari_alpha'] = $validated['potongan_per_hari_alpha'] ?? 0;
        $validated['rate_transport_per_hari'] = $validated['rate_transport_per_hari'] ?? 0;
        $validated['standar_hari_kerja']      = $validated['standar_hari_kerja'] ?? 26;

        if ($tipe === 'komisi_murni') {
            $validated['ada_rate_per_liter'] = true;
            $validated['rate_per_liter']     = $validated['rate_per_liter'] ?? 0;
            $validated['ada_gaji_pokok']     = false;
            $validated['nominal_gaji_pokok'] = null;
        } elseif ($tipe === 'gaji_pokok_murni') {
            $validated['ada_rate_per_liter'] = false;
            $validated['rate_per_liter']     = 0;
            $validated['ada_gaji_pokok']     = true;
            $validated['nominal_gaji_pokok'] = $validated['nominal_gaji_pokok'] ?? 0;
        } else { // hibrid
            $validated['ada_rate_per_liter'] = true;
            $validated['rate_per_liter']     = $validated['rate_per_liter'] ?? 0;
            $validated['ada_gaji_pokok']     = true;
            $validated['nominal_gaji_pokok'] = $validated['nominal_gaji_pokok'] ?? 0;
        }

        $payrollSystem->update($validated);

        return redirect()->route('payroll-systems.index')
            ->with('success', 'Pengaturan gaji berhasil diperbarui.');
    }

    public function destroy(PayrollSystem $payrollSystem)
    {
        // Soft-delete: nonaktifkan saja, jangan hapus (bisa jadi referensi histori)
        $payrollSystem->update(['aktif' => false]);
        return response()->json(['message' => 'Pengaturan gaji dinonaktifkan.']);
    }

    /**
     * AJAX endpoint: ambil daftar sistem penggajian aktif per toko.
     * Dipakai di form Assign Operator untuk filter dropdown.
     */
    public function byShop(Shop $shop)
    {
        $systems = PayrollSystem::where('shop_id', $shop->id)
            ->where('aktif', true)
            ->get(['id', 'nama_sistem', 'tipe_skema']);

        return response()->json($systems);
    }

    /**
     * AJAX endpoint: rekomendasi parameter dan skema default per cabang.
     */
    public function defaultsByShop(Shop $shop)
    {
        return response()->json($shop->getDefaultPayrollNominals());
    }


    private function getAccessibleShops()
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return Shop::where('id', $user->admin?->shop_id)->get();
        }
        if ($user->role === 'investor') {
            return $user->investor?->shops ?? collect();
        }
        return Shop::all();
    }
}
