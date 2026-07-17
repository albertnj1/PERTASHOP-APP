<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find the latest monthly report
$reports = \App\Models\MonthlyReport::with('shop')
    ->orderByDesc('id')
    ->take(5)
    ->get();

foreach ($reports as $report) {
    $dataParsed = $report->data_parsed;
    $segments = $dataParsed['segments'] ?? [];
    $dailyData = $dataParsed['daily_data'] ?? [];
    
    $totalVolSegs = collect($segments)->sum('jumlah_penjualan');
    $totalVolDaily = collect($dailyData)->sum('volume_jual_aktual');
    $totalLabaKotor = collect($segments)->sum('laba_kotor');
    $totalBiaya = $dataParsed['total_biaya'] ?? 0;
    
    echo "=== Report ID: {$report->id} | {$report->shop->nama} | {$report->bulan_tahun} ===\n";
    echo "  Segments count: " . count($segments) . "\n";
    echo "  Daily data count: " . count($dailyData) . "\n";
    echo "  Volume (segments): " . number_format($totalVolSegs, 2) . "\n";
    echo "  Volume (daily): " . number_format($totalVolDaily, 2) . "\n";
    echo "  Laba Kotor (segments): " . number_format($totalLabaKotor, 0) . "\n";
    echo "  Total Biaya: " . number_format($totalBiaya, 0) . "\n";
    
    if (count($segments) > 0) {
        $s = $segments[0];
        echo "  First segment: totalisator_awal=" . ($s['totalisator_awal'] ?? 'n/a') 
            . " totalisator_akhir=" . ($s['totalisator_akhir'] ?? 'n/a')
            . " jumlah_penjualan=" . ($s['jumlah_penjualan'] ?? 'n/a') . "\n";
    }
    echo "\n";
}
