<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = App\Models\MonthlyReport::latest()->first();
echo "Report ID: " . $report->id . "\n";
echo "Bulan: " . $report->bulan_tahun . "\n";
echo "Periods count: " . count($report->data_parsed['periods'] ?? []) . "\n";
