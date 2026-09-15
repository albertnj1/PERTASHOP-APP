<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Shop;
use App\Models\MonthlyReport;
use App\Models\Spending;
use App\Models\SpendingCategory;
use Illuminate\Support\Facades\DB;

$shop = Shop::where('nama', 'like', '%Kalitapen%')->first();
if (!$shop) {
    echo "Shop Kalitapen tidak ditemukan!\n";
    exit;
}

echo "=== SHOP ===\n";
echo "ID: {$shop->id} | Nama: {$shop->nama} | Kode: {$shop->kode}\n\n";

echo "=== INVESTORS DI SHOP INI ===\n";
foreach ($shop->investors as $inv) {
    $user = $inv->user;
    echo "  Investor ID: {$inv->id} | User: " . ($user ? $user->name : 'N/A') . " | Persentase: {$inv->pivot->persentase}%\n";
}

echo "\n=== SPENDING CATEGORIES ===\n";
$cats = SpendingCategory::all();
foreach ($cats as $cat) {
    echo "  ID: {$cat->id} | Nama: {$cat->nama}\n";
}

echo "\n=== SPENDINGS JULI 2026 UNTUK KALITAPEN ===\n";
$spendings = Spending::where('shop_id', $shop->id)
    ->whereMonth('created_at', 7)
    ->whereYear('created_at', 2026)
    ->get();
echo "Total spendings: " . $spendings->count() . "\n";
$grouped = $spendings->groupBy('spending_category_id');
foreach ($grouped as $catId => $items) {
    $cat = SpendingCategory::find($catId);
    echo "  Category {$catId} (" . ($cat ? $cat->nama : 'N/A') . "): Rp " . number_format($items->sum('jumlah'), 0, ',', '.') . " ({$items->count()} items)\n";
}

echo "\n=== MONTHLY REPORTS UNTUK KALITAPEN ===\n";
$reports = MonthlyReport::where('shop_id', $shop->id)->orderBy('bulan_tahun')->get();
foreach ($reports as $rep) {
    $dp = is_array($rep->data_parsed) ? $rep->data_parsed : [];
    echo "  ID: {$rep->id} | Bulan: {$rep->bulan_tahun}\n";
    echo "    Grand Laba Kotor: " . ($dp['grand_laba_kotor'] ?? 'N/A') . "\n";
    echo "    Total Biaya: " . ($dp['total_biaya'] ?? 'N/A') . "\n";
    echo "    Laba Bersih: " . ($dp['laba_bersih'] ?? 'N/A') . "\n";
    echo "    Investors: " . json_encode($dp['investors'] ?? []) . "\n";
    echo "    Pengeluaran Extra: " . json_encode($dp['pengeluaran_extra'] ?? []) . "\n";
    echo "    Operator Salaries: " . json_encode($dp['operator_salaries'] ?? []) . "\n";
}

echo "\n=== LABA BERSIH (OLD MODULE) UNTUK KALITAPEN JULI 2026 ===\n";
$labaKotorSummary = \App\Http\Controllers\LabaKotorController::getSummary($shop->id, '2026-07');
echo "Laba Kotor (dari LabaKotorController): " . $labaKotorSummary['laba_kotor'] . "\n";
echo "Beban Losses: " . $labaKotorSummary['beban_losses_rp'] . "\n";
echo "Beban Test Pump: " . $labaKotorSummary['beban_test_pump_rp'] . "\n";
echo "Beban Keluar Lain: " . $labaKotorSummary['beban_keluar_lain_rp'] . "\n";
