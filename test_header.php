<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reps = \App\Models\MonthlyReport::all();
foreach($reps as $r) {
    echo $r->id . ' : ' . $r->file_path . ' : exists=' . (\Illuminate\Support\Facades\Storage::exists($r->file_path)?'1':'0') . "\n";
}
