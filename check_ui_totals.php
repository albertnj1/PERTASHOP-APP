<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mr = App\Models\MonthlyReport::find(29);
$data = is_array($mr->data_parsed) ? $mr->data_parsed : json_decode($mr->data_parsed, true);
$dailyData = $data['daily_data'];

$t_vol_jual_teoritis = 0; $t_rupiah_jual_teoritis = 0;
$t_tp_vol = 0; $t_tp_rupiah = 0;
$t_terima_bbm = 0; $t_losses_vol = 0; $t_losses_rupiah = 0;
$t_penjualan_aktual = 0;

foreach($dailyData as $row) {
    $t_vol_jual_teoritis += floatval($row['volume_jual_teoritis'] ?? 0);
    $t_rupiah_jual_teoritis += floatval($row['rupiah_jual_teoritis'] ?? 0);
    $t_tp_vol += floatval($row['tp_volume'] ?? 0);
    $t_tp_rupiah += floatval($row['tp_rupiah'] ?? 0);
    $t_terima_bbm += floatval($row['terima_bbm'] ?? 0);
    $t_losses_vol += floatval($row['losses_volume'] ?? 0);
    $t_losses_rupiah += floatval($row['losses_rupiah'] ?? 0);
    $t_penjualan_aktual += floatval($row['volume_jual_aktual'] ?? 0);
}

echo "t_vol_jual_teoritis: $t_vol_jual_teoritis\n";
echo "t_rupiah_jual_teoritis: $t_rupiah_jual_teoritis\n";
echo "t_tp_vol: $t_tp_vol\n";
echo "t_tp_rupiah: $t_tp_rupiah\n";
echo "t_terima_bbm: $t_terima_bbm\n";
echo "t_losses_vol: $t_losses_vol\n";
echo "t_losses_rupiah: $t_losses_rupiah\n";
echo "t_penjualan_aktual: $t_penjualan_aktual\n";
