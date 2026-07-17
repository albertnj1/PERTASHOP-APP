<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = App\Models\MonthlyReport::all();
foreach($reports as $r) {
    echo "ID: " . $r->id . " | Bulan: " . $r->bulan_tahun . " | Periods: " . count($r->data_parsed['periods'] ?? []) . "\n";
}
