<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check Kemutug Lor daily reports
$reports = \App\Models\DailyReport::where('shop_id', function($q) {
        $q->select('id')->from('shops')->where('nama', 'like', '%Kemutug%')->limit(1);
    })
    ->orderBy('created_at')
    ->with(['price', 'shop'])
    ->get();

echo "Total daily reports Kemutug Lor: " . $reports->count() . "\n\n";

if ($reports->count() > 0) {
    $first = $reports->first();
    $last = $reports->last();
    echo "First report (" . $first->created_at . "):\n";
    echo "  totalisator_awal  : " . $first->totalisator_awal . "\n";
    echo "  totalisator_akhir : " . $first->totalisator_akhir . "\n";
    echo "  volume_penjualan_teoritis: " . $first->volume_penjualan_teoritis . "\n";
    echo "  stik_akhir: " . $first->stik_akhir . "\n";
    echo "  stok_awal : " . $first->stok_awal . "\n";
    echo "  price->harga_jual: " . ($first->price ? $first->price->harga_jual : 'null') . "\n";
    echo "\nLast report (" . $last->created_at . "):\n";
    echo "  totalisator_awal  : " . $last->totalisator_awal . "\n";
    echo "  totalisator_akhir : " . $last->totalisator_akhir . "\n";
    echo "\nAll records (date | tot_awal | tot_akhir | diff):\n";
    foreach ($reports as $r) {
        $diff = $r->totalisator_akhir - $r->totalisator_awal;
        echo "  " . $r->created_at->format('Y-m-d') . " | " . number_format($r->totalisator_awal) . " | " . number_format($r->totalisator_akhir) . " | " . number_format($diff) . "\n";
    }
}
