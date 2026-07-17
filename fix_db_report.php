<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$report = App\Models\MonthlyReport::latest()->first();
if ($report) {
    echo "Old values:\n";
    echo "Saldo Awal: {$report->saldo_awal_modal}\n";
    echo "DO: {$report->do_di_pertamina}\n";
    echo "Kas Kecil: {$report->kas_kecil}\n";
    
    // Fix them manually for demonstration
    if ($report->saldo_awal_modal == 67) {
        $report->saldo_awal_modal = 67000000;
    }
    if ($report->do_di_pertamina < 100 && $report->do_di_pertamina > 0) {
        $report->do_di_pertamina = $report->do_di_pertamina * 1000;
    }
    // Check if kas kecil is something like 780
    if ($report->kas_kecil > 0 && $report->kas_kecil < 10000) {
        $report->kas_kecil = $report->kas_kecil * 1000; 
    }

    // Check grand_totals for stok_awal_fisik
    $grandTotals = $report->grand_totals;
    if (!isset($grandTotals['stok_awal_fisik'])) {
        $grandTotals['stok_awal_fisik'] = 1731; // Based on period 1 stok awal in previous conversations
        $report->grand_totals = $grandTotals;
    }

    $report->save();
    echo "\nNew values:\n";
    echo "Saldo Awal: {$report->saldo_awal_modal}\n";
    echo "DO: {$report->do_di_pertamina}\n";
    echo "Kas Kecil: {$report->kas_kecil}\n";
} else {
    echo "No report found\n";
}
