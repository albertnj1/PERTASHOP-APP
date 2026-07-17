<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$shops = App\Models\Shop::with('investors')->get();
foreach ($shops as $s) {
    echo "Shop ID: " . $s->id . ", Name: " . $s->nama . ", Investors: " . $s->investors->count() . "\n";
}
