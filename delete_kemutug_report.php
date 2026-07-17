<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\MonthlyReport;
use App\Models\DailyReport;
use App\Models\CapitalRecap;

$report = MonthlyReport::find(49);
if (!$report) {
    echo "Report 49 not found\n";
    exit;
}

echo "Menghapus laporan ID 49 (Kemutug Lor, Juni 2026)...\n";

DB::beginTransaction();
try {
    $shopId = $report->shop_id;
    $month = \Carbon\Carbon::parse($report->bulan_tahun)->month;
    $year = \Carbon\Carbon::parse($report->bulan_tahun)->year;
    
    // Delete daily reports
    $deleted = DailyReport::where('shop_id', $shopId)
        ->whereMonth('created_at', $month)
        ->whereYear('created_at', $year)
        ->delete();
    echo "  Daily reports dihapus: $deleted\n";
    
    // Also delete extra ones (like the 2026-07-01 ones)
    $extraDeleted = DailyReport::where('shop_id', $shopId)
        ->whereMonth('created_at', 7)
        ->whereYear('created_at', 2026)
        ->delete();
    echo "  Extra daily reports (July): $extraDeleted\n";
    
    // Delete capital recaps
    CapitalRecap::where('shop_id', $shopId)
        ->where('bulan', $month)
        ->where('tahun', $year)
        ->get()
        ->each(fn($r) => $r->delete());
    echo "  Capital recaps dihapus\n";
    
    // Delete monthly report validations
    \App\Models\MonthlyReportValidation::where('monthly_report_id', 49)->delete();
    echo "  Monthly report validations dihapus\n";
    
    // Delete file if exists
    if ($report->file_path && \Illuminate\Support\Facades\Storage::exists($report->file_path)) {
        \Illuminate\Support\Facades\Storage::delete($report->file_path);
        echo "  File Excel dihapus\n";
    }
    
    $report->delete();
    echo "  Monthly report dihapus\n";
    
    DB::commit();
    echo "\nSELESAI: Laporan Kemutug Lor Juni 2026 berhasil dihapus.\n";
    echo "Silakan upload ulang file Excel Kemutug Lor.\n";
} catch (Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
