<?php

namespace App\Services\Organization;

use App\Models\User;
use App\Models\Shop;
use App\Models\InvestorOutletAssignment;
use Illuminate\Support\Collection;

class InvestorOwnershipService
{
    /**
     * Dapatkan daftar Outlet yang diinvestasikan oleh Investor tertentu.
     * Jika user adalah Super Admin, kembalikan seluruh Outlet.
     *
     * @param User $user
     * @return Collection Collection dari Shop model
     */
    public function getAccessibleShops(User $user): Collection
    {
        if (in_array($user->role, ['super-admin', 'super_admin', 'admin'])) {
            return Shop::all();
        }

        // 1. Cek relasi bawaan Investor -> Shops (tabel investor_shop)
        if ($user->role === 'investor' && $user->investor) {
            $investorShops = $user->investor->shops;
            if ($investorShops && $investorShops->count() > 0) {
                return $investorShops;
            }
        }

        // 2. Cek relasi investor_outlet_assignments
        $assignedShopIds = InvestorOutletAssignment::where('investor_id', $user->id)
            ->pluck('shop_id');

        if ($assignedShopIds->isNotEmpty()) {
            return Shop::whereIn('id', $assignedShopIds)->get();
        }

        // Fallback jika belum di-assign
        return Shop::all();
    }

    /**
     * Dapatkan persentase kepemilikan Investor pada Outlet tertentu.
     */
    public function getOwnershipPercentage(int $investorUserId, int $shopId): float
    {
        $assignment = InvestorOutletAssignment::where('investor_id', $investorUserId)
            ->where('shop_id', $shopId)
            ->first();

        return (float) ($assignment?->ownership_percentage ?? 100.0);
    }
}
