<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$r = App\Models\MonthlyReport::first();
if ($r) {
    print_r($r->data_parsed['periods'][1] ?? $r->data_parsed[1] ?? 'No data');
}
