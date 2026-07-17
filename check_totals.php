<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = App\Models\MonthlyReport::all();
foreach ($reports as $mr) {
    $data = json_decode($mr->data, true);
    if (!isset($data['daily'])) continue;
    $sumVol = array_sum(array_column($data['daily'], 'volume_jual_teoritis'));
    $sumRp = array_sum(array_column($data['daily'], 'rupiah_jual_teoritis'));
    if ($sumRp > 100000000 && $sumRp < 110000000) {
        echo "Report ID " . $mr->id . " Shop: " . $mr->shop_id . " Bulan: " . $mr->bulan_tahun . " Total Teoritis: " . number_format($sumRp, 2) . "\n";
    }
}
