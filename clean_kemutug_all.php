<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::beginTransaction();
try {
    $shopId = 5;

    // Delete ALL monthly reports for shop 5
    $monthlyReports = \App\Models\MonthlyReport::where('shop_id', $shopId)->get();
    echo "Monthly reports to delete: " . $monthlyReports->count() . "\n";
    foreach ($monthlyReports as $mr) {
        // Delete validations
        \App\Models\MonthlyReportValidation::where('monthly_report_id', $mr->id)->delete();
        if ($mr->file_path && \Illuminate\Support\Facades\Storage::exists($mr->file_path)) {
            \Illuminate\Support\Facades\Storage::delete($mr->file_path);
        }
        $mr->delete();
        echo "  Deleted monthly report ID: " . $mr->id . "\n";
    }

    // Delete ALL daily reports for shop 5
    $deletedDailies = \App\Models\DailyReport::where('shop_id', $shopId)->count();
    \App\Models\DailyReport::where('shop_id', $shopId)->delete();
    echo "Deleted $deletedDailies daily reports for shop 5\n";

    // Delete all capital recaps for shop 5
    \App\Models\CapitalRecap::where('shop_id', $shopId)->each(fn($r) => $r->delete());
    echo "Deleted capital recaps for shop 5\n";

    DB::commit();
    echo "\nSELESAI: Semua data Kemutug Lor berhasil dibersihkan.\n";
    echo "Silakan upload Excel Kemutug Lor melalui menu Generate Laporan dengan file Excel.\n";
} catch (Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
