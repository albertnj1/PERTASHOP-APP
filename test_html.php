<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// login as user 1
Auth::loginUsingId(1);

$report = App\Models\MonthlyReport::latest()->first();
$id = $report->id;
$request = Illuminate\Http\Request::create('/monthly-reports/' . $id, 'GET');
$response = app()->handle($request);
file_put_contents('test_output_auth.html', $response->getContent());
