<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$shopId = \App\Models\Shop::where('name', 'like', '%Sumingkir%')->first()->id;
$recaps = \App\Models\CapitalRecap::where('shop_id', $shopId)->get();
echo "Recaps for Sumingkir (shop_id $shopId):\n";
foreach ($recaps as $r) {
    echo $r->bulan . '/' . $r->tahun . ' : ' . $r->posisi_akhir_modal . PHP_EOL;
}
if ($recaps->isEmpty()) {
    echo "No recaps found for Sumingkir.\n";
}
