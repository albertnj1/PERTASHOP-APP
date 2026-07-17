<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/monthly-reports/11', 'GET');
$response = app()->handle($request);
file_put_contents('test_output_11.html', $response->getContent());

$request = Illuminate\Http\Request::create('/monthly-reports/10', 'GET');
$response = app()->handle($request);
file_put_contents('test_output_10.html', $response->getContent());
echo "Done";
