<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessRule;
use App\Models\BusinessRuleVersion;
use App\Services\Registry\BusinessRuleRegistryService;
use Illuminate\Support\Facades\Auth;

class BusinessRuleController extends Controller
{
    public function __construct(
        private readonly BusinessRuleRegistryService $registryService
    ) {}

    /**
     * Business Rule Management Center Dashboard.
     */
    public function index()
    {
        $rules = BusinessRule::with(['versions' => function ($q) {
            $q->with('creator')->orderBy('effective_from', 'desc');
        }])->get();

        return view('business_rules.index', compact('rules'));
    }

    /**
     * Rilis versi aturan bisnis baru (Owner Only).
     */
    public function storeVersion(Request $request)
    {
        $request->validate([
            'rule_code'      => 'required|exists:business_rules,code',
            'value'          => 'required',
            'effective_from' => 'required|date',
            'change_reason'  => 'required|string|min:5',
        ]);

        try {
            $user = Auth::user();
            $version = $this->registryService->createNewVersion(
                $request->rule_code,
                $request->value,
                $request->effective_from,
                $user->id,
                $request->change_reason
            );

            return back()->with('success', "✅ Versi aturan baru '{$version->version_code}' berhasil dirilis & aktif mulai " . $version->effective_from->format('d M Y H:i') . ".");
        } catch (\Throwable $e) {
            return back()->withErrors(['rule' => 'Gagal merilis versi aturan: ' . $e->getMessage()]);
        }
    }
}
