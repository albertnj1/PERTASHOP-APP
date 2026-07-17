<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Shop;
use App\Models\Investor;
use App\Models\User;

$shops = Shop::with('investors')->get();
foreach ($shops as $shop) {
    echo "Shop: {$shop->nama} ({$shop->id})\n";
    foreach ($shop->investors as $inv) {
        $name = $inv->atas_nama_rekening;
        echo " - Investor: {$name} (Persen: {$inv->persentase_saham}%)\n";
    }
    echo "\n";
}
