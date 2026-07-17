<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = App\Models\MonthlyReport::latest()->first();
if ($report) {
    file_put_contents('test_render.html', view('monthly_reports.show', compact('report'))->render());
    echo "Rendered test_render.html\n";
} else {
    echo "No report\n";
}
