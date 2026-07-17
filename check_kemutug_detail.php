<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check Kemutug Lor shop
$shop = \App\Models\Shop::where('nama', 'like', '%Kemutug%')->first();
if (!$shop) {
    echo "Shop Kemutug Lor not found\n";
    exit;
}

echo "Shop: " . $shop->nama . " (ID: " . $shop->id . ")\n";
echo "skala: " . $shop->skala . "\n";
echo "totalisator_awal: " . $shop->totalisator_awal . "\n";
echo "stik_awal: " . $shop->stik_awal . "\n";

// Check monthly report
$report = \App\Models\MonthlyReport::where('shop_id', $shop->id)->orderByDesc('id')->first();
if ($report) {
    $dataParsed = $report->data_parsed;
    $segments = $dataParsed['segments'] ?? [];
    echo "\nMonthly report ID: " . $report->id . "\n";
    echo "Segments:\n";
    foreach ($segments as $seg) {
        echo "  Price segment: harga_beli=" . ($seg['harga_beli'] ?? '-') . " harga_jual=" . ($seg['harga_jual'] ?? '-') . "\n";
        echo "  tot_awal=" . ($seg['totalisator_awal'] ?? '-') . " tot_akhir=" . ($seg['totalisator_akhir'] ?? '-') . " jumlah_penjualan=" . ($seg['jumlah_penjualan'] ?? '-') . "\n";
    }
}

// Check daily reports to understand the pattern
$dailies = \App\Models\DailyReport::where('shop_id', $shop->id)
    ->whereMonth('created_at', 6)
    ->whereYear('created_at', 2026)
    ->orderBy('created_at')
    ->get();

echo "\nDaily reports with non-zero values:\n";
foreach ($dailies as $d) {
    if ($d->totalisator_akhir != $d->totalisator_awal) {
        echo "  " . $d->created_at->format('Y-m-d') . " | tot_awal=" . $d->totalisator_awal 
            . " | tot_akhir=" . $d->totalisator_akhir 
            . " | stok_awal=" . $d->stok_awal
            . " | stik_akhir=" . $d->stik_akhir
            . " | penerimaan_volume=" . $d->penerimaan_volume . "\n";
    }
}
