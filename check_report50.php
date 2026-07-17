<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = \App\Models\MonthlyReport::find(50);
if (!$report) { echo "Report 50 not found\n"; exit; }

$dataParsed = $report->data_parsed;
$segments = $dataParsed['segments'] ?? [];
$dailyData = $dataParsed['daily_data'] ?? [];

echo "Report 50 | Shop ID: " . $report->shop_id . " | " . $report->bulan_tahun . "\n";
echo "file_path: " . ($report->file_path ?: '(empty)') . "\n";
echo "saldo_awal_modal: " . number_format($report->saldo_awal_modal) . "\n";
echo "saldo_akhir_modal: " . number_format($report->saldo_akhir_modal) . "\n";
echo "\nSegments count: " . count($segments) . "\n";
foreach ($segments as $seg) {
    echo "  Segment: tot_awal=" . ($seg['totalisator_awal'] ?? 'n/a')
        . " | tot_akhir=" . ($seg['totalisator_akhir'] ?? 'n/a')
        . " | jumlah_penjualan=" . number_format($seg['jumlah_penjualan'] ?? 0, 2)
        . " | laba_kotor=" . number_format($seg['laba_kotor'] ?? 0, 0) . "\n";
}

echo "\nDaily data count: " . count($dailyData) . "\n";
echo "Total volume (daily): " . number_format(collect($dailyData)->sum('volume_jual_aktual'), 2) . "\n";
echo "Total biaya: " . number_format($dataParsed['total_biaya'] ?? 0, 0) . "\n";

// Check daily_reports in DB
$dailyReports = \App\Models\DailyReport::where('shop_id', $report->shop_id)
    ->whereMonth('created_at', 6)
    ->whereYear('created_at', 2026)
    ->orderBy('created_at')
    ->count();
echo "\nDaily reports in DB for June 2026: $dailyReports\n";
