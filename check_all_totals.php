<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = App\Models\MonthlyReport::all();
foreach ($reports as $mr) {
    $data = json_decode($mr->data, true);
    if (!$data || !isset($data['daily'])) continue;
    $sumRp = array_sum(array_column($data['daily'], 'rupiah_jual_teoritis'));
    echo "Report ID " . $mr->id . " Shop: " . $mr->shop_id . " Bulan: " . $mr->bulan_tahun . " Total Teoritis: " . number_format($sumRp, 2) . "\n";
}
