<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/monthly-reports/10', 'GET');
$response = app()->handle($request);
$content = $response->getContent();

preg_match('/Pembagian Laba Bersih :.*?Catatan :/s', $content, $m);
print_r($m[0] ?? 'Not found');
