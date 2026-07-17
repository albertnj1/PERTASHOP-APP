<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = \App\Models\MonthlyReport::find(42);
$dataParsed = $report->data_parsed;

echo json_encode([
    'total_biaya' => $dataParsed['total_biaya'] ?? null,
    'operator_salaries' => $dataParsed['operator_salaries'] ?? null,
    'pengeluaran_extra' => $dataParsed['pengeluaran_extra'] ?? null,
    'bpjs' => $dataParsed['bpjs'] ?? null,
    'other_biaya_keys' => array_keys($dataParsed)
], JSON_PRETTY_PRINT);
