<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find latest monthly report for Kemutug Lor
$report = \App\Models\MonthlyReport::where('shop_id', 5)->orderByDesc('id')->first();
if (!$report) {
    echo "No report found for Kemutug Lor (shop_id=5)\n";
} else {
    echo "Found report ID: " . $report->id . " | " . $report->bulan_tahun . "\n";
    echo "File path: " . $report->file_path . "\n";
}

// Find the Excel file
$allFiles = glob(__DIR__.'/storage/app/public/monthly_reports/*.xlsx');
usort($allFiles, fn($a, $b) => filemtime($b) - filemtime($a));
echo "\nAll Excel files (newest first):\n";
foreach($allFiles as $f) {
    echo "  " . basename($f) . " (" . date('Y-m-d H:i:s', filemtime($f)) . ")\n";
}
