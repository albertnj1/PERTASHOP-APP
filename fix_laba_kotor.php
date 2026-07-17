<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$search1 = <<<'EOD'
            // lossesRp is negative if fuel shrinks. Subtracting negative adds it to HPP, reducing profit.
            // lossesRp is positive if fuel expands. Subtracting positive reduces HPP, increasing profit.
            $penjualanBersihRp = $jmlBeliRp - $sisaStokRp - $lossesRp;
EOD;

$replace1 = <<<'EOD'
            // lossesRp TIDAK dimasukkan ke Laba Kotor sesuai instruksi owner.
            // Laba Kotor murni = Liter Terjual * Margin (atau Penjualan - Harga Pokok murni)
            // Harga Pokok murni = Liter Terjual * Harga Beli
            $penjualanBersihRp = $jmlJualL * ($p['harga_beli'] ?? 0);
EOD;

$content = str_replace($search1, $replace1, $content);
file_put_contents($file, $content);
echo "Fixed Laba Kotor formula to match pure Margin logic.\n";
