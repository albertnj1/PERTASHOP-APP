<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MonthlyReport;

$report = MonthlyReport::latest()->first();
if ($report) {
    echo "ID: " . $report->id . "\n";
    echo "File Excel: " . $report->excel_file . "\n";
    $daily = $report->data_parsed['daily_data'] ?? [];
    $count = count($daily);
    echo "Total daily rows: $count\n";
    if ($count >= 5) {
        for ($i = $count - 5; $i < $count; $i++) {
            print_r($daily[$i]);
        }
    }
}
