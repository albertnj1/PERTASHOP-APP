<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = DB::select("SHOW COLUMNS FROM daily_reports WHERE Field IN ('totalisator_awal', 'totalisator_akhir', 'stik_akhir', 'stok_awal')");
foreach($cols as $c) {
    echo $c->Field . ' => ' . $c->Type . "\n";
}
