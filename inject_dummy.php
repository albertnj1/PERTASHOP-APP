<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$r = App\Models\MonthlyReport::find(11);
if ($r) {
    $dp = is_string($r->data_parsed) ? json_decode($r->data_parsed, true) : $r->data_parsed;
    $gt = $r->grand_totals;
    if (empty($gt['investors'])) {
        $gt['investors'] = [
            [
                'nama' => 'Test Investor 1',
                'persen' => 60,
                'nama_bank' => 'BCA',
                'no_rekening' => '123456',
                'atas_nama_rekening' => 'Test 1'
            ],
            [
                'nama' => 'Test Investor 2',
                'persen' => 40,
                'nama_bank' => 'Mandiri',
                'no_rekening' => '654321',
                'atas_nama_rekening' => 'Test 2'
            ]
        ];
        $r->grand_totals = $gt;
        $r->save();
        echo "Injected dummy investors into Report 11\n";
    }
}
