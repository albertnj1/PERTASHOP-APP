<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = \App\Models\MonthlyReport::find(42);
$dataParsed = $report->data_parsed;
$dailyData = $dataParsed['daily_data'] ?? [];

$t_biaya = 0;
foreach ($dailyData as $row) {
    $t_biaya += floatval($row['biaya']['total'] ?? 0);
}

$totalGajiOperator = collect($dataParsed['operator_salaries'] ?? [])->sum('gaji');
$extraSpendingsSum = collect($dataParsed['pengeluaran_extra'] ?? [])->sum('nominal');

echo "System Total Biaya (data_parsed['total_biaya']): " . floatval($dataParsed['total_biaya'] ?? 0) . "\n";
echo "Daily Biaya (t_biaya): " . $t_biaya . "\n";
echo "Gaji Operator: " . $totalGajiOperator . "\n";
echo "Extra Spendings: " . $extraSpendingsSum . "\n";
echo "Total Calculated: " . ($t_biaya + $totalGajiOperator + $extraSpendingsSum) . "\n";
echo "Difference: " . (floatval($dataParsed['total_biaya'] ?? 0) - ($t_biaya + $totalGajiOperator + $extraSpendingsSum)) . "\n";

