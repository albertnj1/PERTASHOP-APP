<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check shop 5
$shop = \App\Models\Shop::find(5);
echo "Shop: " . $shop->nama . "\n";
echo "skala: " . $shop->skala . "\n";
echo "totalisator_awal: " . $shop->totalisator_awal . "\n";

// Check all daily reports for shop 5
$dailies = \App\Models\DailyReport::where('shop_id', 5)
    ->orderBy('created_at')
    ->get();
echo "\nAll daily reports for shop 5 (" . $dailies->count() . " total):\n";
foreach ($dailies as $d) {
    echo "  ID:" . $d->id . " | " . $d->created_at->format('Y-m-d') 
        . " | tot_awal=" . $d->totalisator_awal 
        . " | tot_akhir=" . $d->totalisator_akhir . "\n";
}
