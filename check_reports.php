<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = App\Models\MonthlyReport::with('shop.investors')->orderBy('id', 'desc')->get();
foreach ($reports as $r) {
    echo "Report ID: " . $r->id . ", Shop Name: " . $r->shop->nama . " (ID: " . $r->shop_id . "), Investors in DB: " . $r->shop->investors->count() . "\n";
}
