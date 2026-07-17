<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tablesToTruncate = [
    'daily_reports',
    'daily_report_uploads',
    'monthly_reports',
    'purchases',
    'incomings',
    'spendings',
    'test_pumps',
    'laba_kotors',
    'laba_bersihs',
    'rekap_modals',
    'profit_sharings',
    'investor_profits',
    'excel_uploads',
    'excel_operasionals',
    'excel_setorans',
    'excel_rekaps',
    'sales'
];

try {
    // Disable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    foreach ($tablesToTruncate as $table) {
        DB::table($table)->truncate();
        echo "Truncated table: $table\n";
    }

    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    
    echo "\nOperational dummy data cleaned successfully!\n";
} catch (\Throwable $e) {
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "Error: " . $e->getMessage() . "\n";
}
