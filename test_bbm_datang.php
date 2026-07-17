<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = App\Models\MonthlyReport::latest()->first();
print_r($report->grand_totals['bbm_datang'] ?? []);
