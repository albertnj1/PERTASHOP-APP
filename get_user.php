<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rep = App\Models\MonthlyReport::first();
if($rep && $rep->user_id) {
    echo App\Models\User::find($rep->user_id)->name;
} else {
    echo 'No user';
}
