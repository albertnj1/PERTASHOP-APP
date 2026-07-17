<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = \App\Models\MonthlyReport::find(42);
$spendings = \App\Models\Spending::where('shop_id', $report->shop_id)
    ->whereMonth('created_at', 6)
    ->whereYear('created_at', 2026)
    ->get();

$total_spendings = $spendings->sum('jumlah');
echo "Total Spendings from table: " . $total_spendings . "\n";
echo "Total from MonthlyReport Sys: " . $report->biaya_operasional . "\n";
