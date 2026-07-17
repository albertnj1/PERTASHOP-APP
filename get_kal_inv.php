<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rep = App\Models\MonthlyReport::whereHas('shop', function($q){
    $q->where('nama', 'like', '%Kalitapen%');
})->first();

if($rep) {
    foreach($rep->shop->investors as $i) {
        echo $i->atas_nama_rekening . "\n";
    }
}
