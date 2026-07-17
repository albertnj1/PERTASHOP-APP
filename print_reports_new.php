<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = \App\Models\MonthlyReport::all();
foreach ($reports as $r) {
    echo "ID: {$r->id}, shop_id: {$r->shop_id}, nama: " . ($r->shop ? $r->shop->nama : 'Unknown') . ", bulan_tahun: {$r->bulan_tahun}, file_path: '{$r->file_path}'\n";
}
