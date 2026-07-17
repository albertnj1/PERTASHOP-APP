<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dr = App\Models\DailyReport::find(243); // shop 1
$month = $dr->created_at->format('m');
$year = $dr->created_at->format('Y');

$reports = App\Models\DailyReport::where('shop_id', 1)
    ->whereMonth('created_at', $month)
    ->whereYear('created_at', $year)
    ->orderBy('created_at')
    ->get();

$sum = 0;
foreach ($reports as $r) {
    echo $r->created_at->format('Y-m-d') . ' : Vol = ' . $r->volume_penjualan_teoritis . ' Rp = ' . $r->rupiah_penjualan_teoritis . "\n";
    $sum += $r->rupiah_penjualan_teoritis;
}
echo "TOTAL: " . $sum . "\n";
