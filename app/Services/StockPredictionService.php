<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\DailyReport;
use Illuminate\Support\Carbon;

class StockPredictionService
{
    /**
     * Prediksi kebutuhan BBM dan sisa hari stok untuk toko tertentu.
     * 
     * @param int $shopId
     * @return array
     */
    public static function predictForShop(int $shopId): array
    {
        $shop = Shop::findOrFail($shopId);

        // Fetch last 14 days of daily reports
        $recentReports = DailyReport::where('shop_id', $shopId)
            ->latest()
            ->take(14)
            ->get();

        if ($recentReports->isEmpty()) {
            return [
                'avg_daily_sales'    => 0,
                'days_remaining'     => 999,
                'current_stok'       => 0,
                'is_critical'        => false,
                'recommended_order_date' => null,
                'suggested_volume'   => 0,
            ];
        }

        $avgDailySales = $recentReports->avg('volume_penjualan_teoritis');
        $latestReport = $recentReports->first();
        $currentStok = (float) $latestReport->stok_akhir_aktual;

        $daysRemaining = $avgDailySales > 0 ? floor($currentStok / $avgDailySales) : 999;
        $isCritical = $daysRemaining <= 3 || $currentStok < 1500;

        // Lead time delivery Pertamina (asumsi 1 hari)
        $leadTimeDays = 1;
        $orderDaysOffset = max(0, $daysRemaining - $leadTimeDays);
        $recommendedOrderDate = Carbon::today()->addDays($orderDaysOffset);

        // Target kapasitas tangki standar Pertashop (3.000 - 5.000 L)
        $targetCapacity = 4000;
        $suggestedVolume = max(0, round($targetCapacity - $currentStok, -2)); // bulatkan ke 100 terdekat

        return [
            'avg_daily_sales'        => round($avgDailySales, 1),
            'days_remaining'         => $daysRemaining,
            'current_stok'           => $currentStok,
            'is_critical'            => $isCritical,
            'recommended_order_date' => $recommendedOrderDate->translatedFormat('d M Y'),
            'suggested_volume'       => $suggestedVolume,
        ];
    }
}
