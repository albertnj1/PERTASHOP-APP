<?php

namespace App\Http\Controllers;

use App\Models\PayrollDetail;
use App\Models\PayrollPeriod;
use App\Models\Shop;
use App\Services\PayrollCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    public function __construct(private PayrollCalculationService $calculationService) {}

    /**
     * Halaman utama Penggajian: list periode + form generate.
     */
    public function index(Request $request)
    {
        $shops = $this->getAccessibleShops();

        $selectedShopId = $request->input('shop_id', $shops->first()?->id);
        $selectedTahun  = $request->input('tahun', now()->year);

        $periods = PayrollPeriod::with(['shop', 'payrollSystem', 'details.operator.user', 'generatedBy'])
            ->where('shop_id', $selectedShopId)
            ->where('tahun', $selectedTahun)
            ->orderByDesc('bulan')
            ->get();

        $availableYears = range(now()->year, now()->year - 3);

        // Map bulan => status, untuk cek di form Generate apakah bulan tsb sudah final
        $periodStatusMap = $periods->pluck('status', 'bulan');

        return view('payroll.index', compact(
            'shops', 'periods', 'selectedShopId', 'selectedTahun', 'availableYears', 'periodStatusMap'
        ));
    }

    /**
     * Generate payroll untuk toko & bulan yang dipilih.
     * Memanggil PayrollCalculationService.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'bulan'   => 'required|integer|between:1,12',
            'tahun'   => 'required|integer|min:2020|max:2099',
        ]);

        try {
            $period = $this->calculationService->generate(
                (int) $validated['shop_id'],
                (int) $validated['bulan'],
                (int) $validated['tahun'],
                Auth::id()
            );

            if ($request->has('custom_items') && is_array($request->input('custom_items'))) {
                foreach ($request->input('custom_items') as $item) {
                    if (empty($item['nama_item']) || empty($item['jumlah']) || floatval($item['jumlah']) <= 0) {
                        continue;
                    }
                    $operatorId = !empty($item['operator_id']) ? (int)$item['operator_id'] : null;

                    $details = $operatorId
                        ? $period->details->where('operator_id', $operatorId)
                        : $period->details;

                    foreach ($details as $detail) {
                        $detail->items()->create([
                            'tipe'      => in_array($item['tipe'] ?? 'tambahan', ['tambahan', 'potongan']) ? $item['tipe'] : 'tambahan',
                            'nama_item' => trim($item['nama_item']),
                            'jumlah'    => floatval($item['jumlah']),
                        ]);
                        $detail->recalculateTHP();
                    }
                }
            }

            return redirect()
                ->route('payroll.show', $period->id)
                ->with('success', "Penggajian berhasil di-generate untuk periode {$period->periode_label}.");

        } catch (\Exception $e) {
            return back()->withErrors(['generate' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Bulk generate penggajian untuk SELURUH toko aktif dalam 1 klik.
     * Mengabaikan/melewati toko yang status penggajian bulan tersebut sudah FINAL.
     */
    public function bulkGenerate(Request $request)
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2099',
        ]);

        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];

        $shops = Shop::query();
        if (Auth::user()->role === 'admin') {
            $shops->where('id', Auth::user()->admin->shop_id);
        }
        $activeShops = $shops->get();

        $generatedCount = 0;
        $skippedShops = [];
        $generatedShops = [];

        foreach ($activeShops as $shop) {
            $existing = PayrollPeriod::where('shop_id', $shop->id)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();

            if ($existing && $existing->isFinal()) {
                $skippedShops[] = $shop->nama;
                continue;
            }

            try {
                $period = $this->calculationService->generate($shop->id, $bulan, $tahun, Auth::id());
                $generatedCount++;
                $generatedShops[] = $shop->nama;
            } catch (\Throwable $e) {
                // Catat jika ada error toko
            }
        }

        $msg = "Bulk generate selesai! {$generatedCount} toko berhasil di-generate sebagai draft.";
        if (count($skippedShops) > 0) {
            $msg .= " (Toko dilewati karena sudah Final: " . implode(', ', $skippedShops) . ")";
        }

        return redirect()->route('payroll.index', ['bulan' => $bulan, 'tahun' => $tahun])
            ->with('success', $msg);
    }

    /**
     * Tampilkan detail periode gaji (tabel per operator, inline editable).
     */
    public function show(PayrollPeriod $payroll)
    {
        $payroll->load([
            'shop',
            'payrollSystem',
            'generatedBy',
            'details.operator.user',
            'dailySplits.operator.user',
        ]);

        return view('payroll.show', compact('payroll'));
    }

    /**
     * Update satu komponen manual di payroll_detail (inline edit via AJAX).
     * Tidak bisa dilakukan jika periode sudah final.
     */
    public function updateDetail(Request $request, PayrollPeriod $payroll, PayrollDetail $detail)
    {
        if ($payroll->isFinal()) {
            return response()->json(['error' => 'Periode sudah difinalisasi, tidak bisa diedit.'], 403);
        }

        $validated = $request->validate([
            'lembur'               => 'nullable|numeric|min:0',
            'lembur_hari_raya'     => 'nullable|numeric|min:0',
            'bonus'                => 'nullable|numeric|min:0',
            'thr'                  => 'nullable|numeric|min:0',
            'potongan_tidak_masuk' => 'nullable|numeric|min:0',
            'kurang_setoran'       => 'nullable|numeric|min:0',
            'tabungan_gaji'        => 'nullable|numeric|min:0',
            'tabungan_setoran'     => 'nullable|numeric|min:0',
            'potongan_hutang'      => 'nullable|numeric|min:0',
            'catatan'              => 'nullable|string|max:500',
        ]);

        $detail->fill($validated);
        $detail->fill($validated);
        $detail->recalculateTHP();

        return response()->json([
            'success'           => true,
            'take_home_pay'     => $detail->take_home_pay,
            'sisa_kurang_bayar' => $detail->sisa_kurang_bayar,
            'total_gaji_kotor'  => $detail->total_gaji_kotor,
            'total_potongan'    => $detail->total_potongan,
        ]);
    }

    /**
     * Tambah komponen item tambahan/potongan baru secara dinamis (Keterangan + Nominal).
     */
    public function addItem(Request $request, PayrollPeriod $payroll, PayrollDetail $detail)
    {
        if ($payroll->isFinal()) {
            return response()->json(['error' => 'Periode sudah difinalisasi, tidak bisa menambah item.'], 403);
        }

        $validated = $request->validate([
            'tipe'      => 'required|in:tambahan,potongan',
            'nama_item' => 'required|string|max:255',
            'jumlah'    => 'required|numeric|min:0',
            'keterangan'=> 'nullable|string|max:500',
        ]);

        $item = $detail->items()->create($validated);
        $detail->load('items');
        $detail->recalculateTHP();

        return response()->json([
            'success'           => true,
            'item'              => $item,
            'take_home_pay'     => $detail->take_home_pay,
            'total_gaji_kotor'  => $detail->total_gaji_kotor,
            'total_potongan'    => $detail->total_potongan,
        ]);
    }

    /**
     * Hapus komponen item tambahan/potongan dinamis.
     */
    public function deleteItem(PayrollPeriod $payroll, PayrollDetail $detail, \App\Models\PayrollDetailItem $item)
    {
        if ($payroll->isFinal()) {
            return response()->json(['error' => 'Periode sudah difinalisasi, tidak bisa menghapus item.'], 403);
        }

        $item->delete();
        $detail->load('items');
        $detail->recalculateTHP();

        return response()->json([
            'success'           => true,
            'take_home_pay'     => $detail->take_home_pay,
            'total_gaji_kotor'  => $detail->total_gaji_kotor,
            'total_potongan'    => $detail->total_potongan,
        ]);
    }

    /**
     * Finalisasi periode gaji: mengunci data, tidak bisa di-generate ulang.
     */
    public function finalize(PayrollPeriod $payroll)
    {
        if ($payroll->isFinal()) {
            return back()->withErrors(['finalize' => 'Periode sudah difinalisasi.']);
        }

        $payroll->update(['status' => 'final']);

        return back()->with('success', "Penggajian periode {$payroll->periode_label} berhasil difinalisasi.");
    }

    /**
     * Export slip gaji semua operator dalam 1 periode ke PDF.
     */
    public function exportPdf(PayrollPeriod $payroll)
    {
        $payroll->load([
            'shop',
            'payrollSystem',
            'details.operator.user',
        ]);

        $pdf = Pdf::loadView('pdf.payroll_slip', compact('payroll'))
            ->setPaper('a4', 'portrait');

        $filename = 'slip-gaji-' . strtolower(str_replace(' ', '-', $payroll->shop->nama))
            . '-' . $payroll->bulan . '-' . $payroll->tahun . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Hapus periode draft (periode final tidak bisa dihapus).
     */
    public function destroy(PayrollPeriod $payroll)
    {
        if ($payroll->isFinal()) {
            return response()->json(['error' => 'Periode final tidak bisa dihapus.'], 403);
        }

        $payroll->dailySplits()->delete();
        $payroll->details()->delete();
        $payroll->delete();

        return response()->json(['message' => 'Draft penggajian dihapus.']);
    }

    private function getAccessibleShops()
    {
        if (Auth::user()->role === 'admin') {
            return Shop::where('id', Auth::user()->admin->shop_id)->get();
        }
        return Shop::all();
    }
}
