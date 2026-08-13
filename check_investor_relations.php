<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Investor;
use App\Models\Shop;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== TABLES RELATED TO INVESTORS & SHOPS ===" . PHP_EOL;

if (Schema::hasTable('investors')) {
    echo "Columns in 'investors': " . implode(', ', Schema::getColumnListing('investors')) . PHP_EOL;
}

if (Schema::hasTable('investor_shops')) {
    echo "Columns in 'investor_shops': " . implode(', ', Schema::getColumnListing('investor_shops')) . PHP_EOL;
}

if (Schema::hasTable('investor_outlet_assignments')) {
    echo "Columns in 'investor_outlet_assignments': " . implode(', ', Schema::getColumnListing('investor_outlet_assignments')) . PHP_EOL;
}

echo PHP_EOL . "=== SAMPLE INVESTORS & THEIR ASSIGNED SHOPS ===" . PHP_EOL;
$investorUsers = User::where('role', 'investor')->get();

foreach ($investorUsers as $u) {
    echo "User ID: {$u->id} | Name: {$u->name} | Email: {$u->email}" . PHP_EOL;
    
    // Check Investor model
    $invModel = Investor::where('user_id', $u->id)->first();
    if ($invModel) {
        echo "   -> Investor Record ID: {$invModel->id}" . PHP_EOL;
        // Check relation to shops if exists
        if (method_exists($invModel, 'shops')) {
            $shops = $invModel->shops;
            echo "   -> Assigned Shops via Investor->shops: " . $shops->pluck('nama')->implode(', ') . " (Count: " . $shops->count() . ")" . PHP_EOL;
        }
    }

    // Check investor_outlet_assignments
    $assignedShopsD0 = DB::table('investor_outlet_assignments')
        ->join('shops', 'investor_outlet_assignments.shop_id', '=', 'shops.id')
        ->where('investor_id', $u->id)
        ->pluck('shops.nama');
    if ($assignedShopsD0->count() > 0) {
        echo "   -> Assigned Shops via investor_outlet_assignments: " . $assignedShopsD0->implode(', ') . PHP_EOL;
    }
    echo "--------------------------------------------------------" . PHP_EOL;
}
