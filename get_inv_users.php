<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$invs = App\Models\Investor::with('user')->get();
foreach($invs as $i) {
    echo "ID: {$i->id}, User: " . ($i->user ? $i->user->name : 'none') . ", Rek: {$i->atas_nama_rekening}\n";
}
