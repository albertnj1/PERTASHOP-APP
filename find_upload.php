<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find the newly uploaded file for report 50
$report = \App\Models\MonthlyReport::find(50);
echo "Report 50 file_path: " . ($report->file_path ?: '(empty)') . "\n";

// Check daily_report_uploads table for recent uploads
$uploads = \DB::table('daily_report_uploads')
    ->where('shop_id', 5)
    ->orderByDesc('id')
    ->limit(3)
    ->get();
echo "\nRecent uploads for shop 5:\n";
foreach ($uploads as $u) {
    echo "  ID:" . $u->id . " | file=" . $u->file_name . " | " . $u->created_at . "\n";
}

// Also check excel_uploads
$excelUploads = \DB::table('excel_uploads')
    ->orderByDesc('id')
    ->limit(5)
    ->get();
echo "\nRecent excel_uploads:\n";
foreach ($excelUploads as $eu) {
    echo "  ID:" . $eu->id . " | file=" . $eu->original_name . " | " . $eu->created_at . "\n";
}
