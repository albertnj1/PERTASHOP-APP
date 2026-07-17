<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = \App\Models\MonthlyReport::find(42);
$dataParsed = $report->data_parsed;
$dailyData = $dataParsed['daily_data'] ?? [];

$total_biaya_from_json = 0;
foreach ($dailyData as $row) {
    $total_biaya_from_json += floatval($row['biaya']['total'] ?? 0);
}
echo "Total Biaya from JSON (daily_data loop): $total_biaya_from_json\n";
