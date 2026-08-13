<?php

namespace App\Services;

use App\Models\DailyReport;
use Illuminate\Support\Carbon;

class AnomalyDetectionService
{
    /**
     * Cek apakah Laporan Harian memiliki anomali (losses/gain atau selisih setoran ekstrem).
     * 
     * @param DailyReport $report
     * @return array ['is_anomalous' => bool, 'reasons' => array]
     */
    public static function check(DailyReport $report): array
    {
        $reasons = [];

        // Fetch 30-day historical reports for baseline calculation
        $recentReports = DailyReport::where('shop_id', $report->shop_id)
            ->where('id', '!=', $report->id)
            ->where('created_at', '<', $report->created_at)
            ->latest()
            ->take(30)
            ->get();

        if ($recentReports->count() >= 5) {
            $losses = $recentReports->pluck('losses_gain')->map(fn ($v) => (float) $v);
            $mean = $losses->avg();
            
            // Standard deviation
            $variance = $losses->reduce(fn ($carry, $item) => $carry + pow($item - $mean, 2), 0) / $recentReports->count();
            $stdDev = sqrt($variance);

            $currentLoss = (float) $report->losses_gain;
            
            // Flag if current losses/gain deviates more than 2 stdDevs or 30 Liters absolute
            if ($stdDev > 0 && abs($currentLoss - $mean) > (2 * $stdDev) && abs($currentLoss - $mean) > 15) {
                $direction = $currentLoss < $mean ? 'Losses tinggi' : 'Gain tinggi';
                $reasons[] = "{$direction} terdeteksi ({$currentLoss} L vs rata-rata 30 hari: " . number_format($mean, 2) . " L).";
            }
        }

        // Flag selisih setoran mencurigakan (> Rp 50.000)
        $selisih = (float) $report->selisih_setoran;
        if (abs($selisih) >= 50000) {
            $type = $selisih < 0 ? 'Kurang' : 'Lebih';
            $reasons[] = "Selisih setoran {$type} signifikan: Rp " . number_format(abs($selisih), 0, ',', '.');
        }

        return [
            'is_anomalous' => !empty($reasons),
            'reasons'      => $reasons,
        ];
    }
}
