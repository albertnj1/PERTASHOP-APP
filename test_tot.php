<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = App\Models\MonthlyReport::latest()->first();
$p = $report->data_parsed['periods'][1] ?? null;
if ($p) {
    echo "Stok Awal Fisik: " . ($report->stok_awal_fisik ?? 0) . "\n";
    echo "Total Awal (Totalisator Awal): " . ($p['tot_awal'] ?? 0) . "\n";
    echo "Total Jual (Totalisator Akhir): " . ($p['tot_jual'] ?? 0) . "\n";
}
