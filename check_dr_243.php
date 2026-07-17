<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dr = App\Models\DailyReport::find(243);
echo 'Vol Teoritis: ' . $dr->volume_penjualan_teoritis . "\n";
echo 'Rp Teoritis DB: ' . $dr->rupiah_penjualan_teoritis . "\n";
echo 'Harga Jual from Price: ' . ($dr->price ? $dr->price->harga_jual : 'none') . "\n";
