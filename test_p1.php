<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = App\Models\MonthlyReport::latest()->first();
$p = $report->data_parsed['periods'][1] ?? null;
if ($p) {
    echo "Stok Awal: " . ($p['stok_awal'] ?? 0) . "\n";
    echo "Stok Aktual (Akhir): " . ($p['stok_aktual'] ?? 0) . "\n";
    echo "Harga Beli: " . ($p['harga_beli'] ?? 0) . "\n";
    echo "Harga Jual: " . ($p['harga_jual'] ?? 0) . "\n";
}

print_r($report->bbm_datang);
