<?php
use App\Models\Shop;
use App\Models\Corporation;

$c = Corporation::first();
if (!$c) {
    echo "No corporation found.\n";
} else {
    $shop = Shop::firstOrCreate(
        ['kode' => '4P.532.40'],
        [
            'nama' => 'Sumingkir',
            'alamat' => 'Sumingkir',
            'corporation_id' => $c->id,
            'short_name' => 'Sumingkir',
        ]
    );
    echo "Shop Added/Exists: " . $shop->nama . "\n";
}
