<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$r = App\Models\MonthlyReport::with('shop')->orderBy('id', 'desc')->first();
echo 'Shop ID: ' . $r->shop_id . "\n";
echo 'Shop Name: ' . $r->shop->name . "\n";
echo 'Investors Count: ' . $r->shop->investors()->count() . "\n";
