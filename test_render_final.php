<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $report = \App\Models\MonthlyReport::with('shop.investors')->first();
    if (!$report) {
        echo "No report found to test.";
        exit;
    }
    
    // Fake the required variables
    $history = \App\Models\MonthlyReport::where('shop_id', $report->shop_id)
        ->where('created_at', '<=', $report->created_at)
        ->orderBy('bulan_tahun', 'asc')
        ->get();
    
    $view = view('monthly_reports.show', compact('report', 'history'))->render();
    echo "View compiled successfully! Length: " . strlen($view) . " bytes.\n";
    
    // Check if the charts are present in the final HTML
    if (strpos($view, 'id="chartPenjualan"') !== false) {
        echo "chartPenjualan found.\n";
    }
    if (strpos($view, 'id="chartModal"') !== false) {
        echo "chartModal found.\n";
    }
    if (strpos($view, 'table-premium') !== false) {
        echo "table-premium found.\n";
    }
} catch (\Exception $e) {
    echo "Error compiling view:\n";
    echo $e->getMessage() . "\n" . $e->getFile() . " on line " . $e->getLine();
}
