<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Shop;

Shop::where('id', 1)->update(['tanggal_mulai_operasional' => '2021-07-01']);
Shop::where('id', '>', 1)->update(['tanggal_mulai_operasional' => '2023-07-01']);

echo "Updated shops start dates!\n";
