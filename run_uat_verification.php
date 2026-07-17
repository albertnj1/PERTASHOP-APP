<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MonthlyReport;
use App\Models\MonthlyReportValidation;
use App\Http\Controllers\MonthlyReportController;

$reports = MonthlyReport::all();
$controller = new MonthlyReportController();

echo "========================================================\n";
echo "       UAT VERIFICATION & HARDENING AUDIT REPORT         \n";
echo "========================================================\n";
echo "Total Laporan Bulanan di Database: " . $reports->count() . "\n\n";

$allValid = true;
$totalChecked = 0;

foreach ($reports as $report) {
    $shopName = $report->shop ? $report->shop->nama : 'Unknown Shop';
    echo "Laporan ID: {$report->id} | {$shopName} ({$report->bulan_tahun})\n";
    
    try {
        // Run audit verification (isNew = false to prevent throwing ValidationException)
        $results = $controller->verifyReportCalculations($report, false);
        
        $invalidComp = [];
        foreach ($results as $comp => $res) {
            $status = $res['status'];
            if ($status === 'valid') {
                $statusStr = "VALID";
            } elseif ($status === 'info') {
                $statusStr = "INFO ";
            } else {
                $statusStr = "INVALID";
            }
            echo "  - [" . str_pad($comp, 18) . "] Sys: " . str_pad(number_format($res['system'], 2), 15) . " | Calc: " . str_pad(number_format($res['recalculated'], 2), 15) . " | Diff: " . str_pad(number_format($res['diff'], 2), 15) . " | {$statusStr}";
            if (!empty($res['note'])) {
                echo " (" . $res['note'] . ")";
            }
            echo "\n";
            if ($status === 'invalid') {
                $invalidComp[] = $comp;
                $allValid = false;
            }
        }
        
        if (count($invalidComp) > 0) {
            echo "  STATUS: INVALID (Selisih pada: " . implode(', ', $invalidComp) . ")\n";
        } else {
            $hasInfo = collect($results)->contains(fn($r) => $r['status'] === 'info');
            echo "  STATUS: " . ($hasInfo ? "OK (dengan catatan INFO)" : "OK (100% Cocok)") . "\n";
        }
    } catch (\Exception $e) {
        echo "  STATUS: ERROR (" . $e->getMessage() . ")\n";
        $allValid = false;
    }
    echo "--------------------------------------------------------\n";
    $totalChecked++;
}

echo "\n========================================================\n";
echo "SUMMARY AUDIT:\n";
echo "Total Laporan Diperiksa: {$totalChecked}\n";
echo "Status Akhir: " . ($allValid ? "SEMUA VALID (Tidak ada selisih kritis)" : "TERDAPAT SELISIH YANG HARUS DIPERIKSA") . "\n";
echo "========================================================\n";
