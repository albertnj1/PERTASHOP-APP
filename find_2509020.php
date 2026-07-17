<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = App\Models\MonthlyReport::all();
foreach ($reports as $mr) {
    if (str_contains($mr->data, '2509020') || str_contains($mr->data, '2.509.020')) {
        echo "Found 2509020 in MonthlyReport " . $mr->id . " Shop: " . $mr->shop_id . "\n";
    }
}
$drs = App\Models\DailyReport::all();
foreach ($drs as $dr) {
    if ($dr->rupiah_penjualan_teoritis == 2509020 || str_contains($dr->rupiah_penjualan_teoritis, '250902')) {
        echo "Found 2509020 in DailyReport " . $dr->id . " Shop: " . $dr->shop_id . "\n";
    }
}
