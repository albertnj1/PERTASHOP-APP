<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = App\Models\MonthlyReport::with('shop.investors')->orderBy('created_at', 'desc')->first();
$grandTotals = is_string($report->data_parsed) ? json_decode($report->data_parsed, true) : $report->data_parsed;
$investors = $grandTotals['investors'] ?? [];
$shopInvs = $report->shop->investors ?? collect();
echo "Report ID: " . $report->id . "\n";
echo "Investors in JSON: " . count($investors) . "\n";
echo "Investors in Shop: " . count($shopInvs) . "\n";
