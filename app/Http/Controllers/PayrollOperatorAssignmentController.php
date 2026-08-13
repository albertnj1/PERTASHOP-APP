<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\PayrollOperatorAssignment;
use App\Models\PayrollSystem;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollOperatorAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $shops = $this->getAccessibleShops();

        $query = PayrollOperatorAssignment::with(['operator.user', 'shop', 'payrollSystem'])
            ->latest();

        if (Auth::user()->role === 'admin') {
            $query->where('shop_id', Auth::user()->admin->shop_id);
        } elseif ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        $assignments = $query->get();

        return view('payroll_operator_assignments.index', compact('assignments', 'shops'));
    }

    public function create()
    {
        $shops    = $this->getAccessibleShops();
        $operators = collect();
        $systems  = collect();

        if (Auth::user()->role === 'admin') {
            $shopId    = Auth::user()->admin->shop_id;
            $operators = Operator::with('user')->where('shop_id', $shopId)->get();
            $systems   = PayrollSystem::where('shop_id', $shopId)->where('aktif', true)->get();
        }

        return view('payroll_operator_assignments.create', compact('shops', 'operators', 'systems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'operator_id'       => 'required|exists:operators,id',
            'shop_id'           => 'required|exists:shops,id',
            'payroll_system_id' => 'required|exists:payroll_systems,id',
            'tanggal_mulai'     => 'required|date',
            'tanggal_selesai'   => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        // Pastikan sistem penggajian yang dipilih milik toko yang sama
        $system = PayrollSystem::find($validated['payroll_system_id']);
        if ($system->shop_id != $validated['shop_id']) {
            return back()->withErrors(['payroll_system_id' => 'Sistem penggajian tidak sesuai dengan toko yang dipilih.'])->withInput();
        }

        // Tutup assignment aktif sebelumnya (jika ada) agar tidak overlap
        PayrollOperatorAssignment::where('operator_id', $validated['operator_id'])
            ->where('shop_id', $validated['shop_id'])
            ->whereNull('tanggal_selesai')
            ->update(['tanggal_selesai' => date('Y-m-d', strtotime($validated['tanggal_mulai'] . ' -1 day'))]);

        PayrollOperatorAssignment::create($validated);

        return redirect()->route('payroll-operator-assignments.index')
            ->with('success', 'Operator berhasil di-assign ke sistem penggajian.');
    }

    public function destroy(PayrollOperatorAssignment $payrollOperatorAssignment)
    {
        $payrollOperatorAssignment->delete();
        return response()->json(['message' => 'Assignment dihapus.']);
    }

    /**
     * AJAX: Ambil operator per toko (untuk dropdown dinamis di form create).
     */
    public function operatorsByShop(Shop $shop)
    {
        $operators = Operator::with('user')
            ->where('shop_id', $shop->id)
            ->get()
            ->map(fn($op) => ['id' => $op->id, 'name' => $op->user?->name ?? '-']);

        return response()->json($operators);
    }

    private function getAccessibleShops()
    {
        if (Auth::user()->role === 'admin') {
            return Shop::where('id', Auth::user()->admin->shop_id)->get();
        }
        return Shop::all();
    }
}
