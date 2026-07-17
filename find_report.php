<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reports = App\Models\MonthlyReport::all();
foreach ($reports as $mr) {
    if (str_contains($mr->data, '2509020') || str_contains($mr->data, '2.509.020') || str_contains($mr->data, '2509020')) {
        echo 'Found in Monthly Report ID: ' . $mr->id . ' Shop: ' . $mr->shop_id . "\n";
    }
}
