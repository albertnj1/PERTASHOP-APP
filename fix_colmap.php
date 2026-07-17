<?php
$file = 'app/Http/Controllers/MonthlyReportController.php';
$content = file_get_contents($file);

$oldCode = "
                        if (str_contains(\$val, 'qris')) \$colMap['qris'] = \$letter;
                        if (str_contains(\$val, 'totalisator akhir') || str_contains(\$val, 'tot akhir')) \$colMap['totalisator_akhir'] = \$letter;
                        if (str_contains(\$val, 'test pump') || str_contains(\$val, 'tp')) \$colMap['test_pump'] = \$letter;
                        if (str_contains(\$val, 'curah')) \$colMap['curah'] = \$letter;
                        if (str_contains(\$val, 'stik malam')) \$colMap['stik_malam'] = \$letter;
                        if (str_contains(\$val, 'pengeluaran') && !str_contains(\$val, 'ket')) \$colMap['pengeluaran'] = \$letter;
                        if (str_contains(\$val, 'keterangan pengeluaran') || str_contains(\$val, 'ket pengeluaran')) \$colMap['ket_pengeluaran'] = \$letter;
                        if (str_contains(\$val, 'rp. penjualan') || str_contains(\$val, 'harga')) \$colMap['harga'] = \$letter;
                        if (str_contains(\$val, 'penjualan liter') || str_contains(\$val, 'volume')) \$colMap['penjualan_liter'] = \$letter;
                        if (str_contains(\$val, 'stok aktual')) \$colMap['stok_aktual'] = \$letter;
                        if (str_contains(\$val, 'gain/loses (liter)') || str_contains(\$val, 'gain/lose s (liter)') || str_contains(\$val, 'loses (liter)')) \$colMap['gain_loses'] = \$letter;
                        if (str_contains(\$val, 'gain/loses (%)') || str_contains(\$val, 'gain/lose ss') || str_contains(\$val, 'loses (%)')) \$colMap['gain_loses_persen'] = \$letter;
";

$newCode = "
                        if (empty(\$colMap['qris']) && str_contains(\$val, 'qris')) \$colMap['qris'] = \$letter;
                        if (empty(\$colMap['totalisator_akhir']) && (str_contains(\$val, 'totalisator akhir') || str_contains(\$val, 'tot akhir'))) \$colMap['totalisator_akhir'] = \$letter;
                        if (empty(\$colMap['test_pump']) && (str_contains(\$val, 'test pump') || str_contains(\$val, 'tp'))) \$colMap['test_pump'] = \$letter;
                        if (empty(\$colMap['curah']) && str_contains(\$val, 'curah')) \$colMap['curah'] = \$letter;
                        if (empty(\$colMap['stik_malam']) && str_contains(\$val, 'stik malam')) \$colMap['stik_malam'] = \$letter;
                        if (empty(\$colMap['pengeluaran']) && str_contains(\$val, 'pengeluaran') && !str_contains(\$val, 'ket') && !str_contains(\$val, 'pendapatan')) \$colMap['pengeluaran'] = \$letter;
                        if (empty(\$colMap['ket_pengeluaran']) && (str_contains(\$val, 'keterangan pengeluaran') || str_contains(\$val, 'ket pengeluaran'))) \$colMap['ket_pengeluaran'] = \$letter;
                        if (empty(\$colMap['harga']) && (str_contains(\$val, 'rp. penjualan') || str_contains(\$val, 'harga'))) \$colMap['harga'] = \$letter;
                        if (empty(\$colMap['penjualan_liter']) && (str_contains(\$val, 'penjualan liter') || str_contains(\$val, 'volume'))) \$colMap['penjualan_liter'] = \$letter;
                        if (empty(\$colMap['stok_aktual']) && str_contains(\$val, 'stok aktual')) \$colMap['stok_aktual'] = \$letter;
                        if (empty(\$colMap['gain_loses']) && (str_contains(\$val, 'gain/loses (liter)') || str_contains(\$val, 'gain/lose s (liter)') || str_contains(\$val, 'loses (liter)'))) \$colMap['gain_loses'] = \$letter;
                        if (empty(\$colMap['gain_loses_persen']) && (str_contains(\$val, 'gain/loses (%)') || str_contains(\$val, 'gain/lose ss') || str_contains(\$val, 'loses (%)'))) \$colMap['gain_loses_persen'] = \$letter;
";

$content = str_replace(trim($oldCode), trim($newCode), $content);

file_put_contents($file, $content);
echo \"Done fixing colMap logic.\\n\";
