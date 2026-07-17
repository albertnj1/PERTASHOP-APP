<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// Fix Roman numerals
$content = preg_replace('/I\{\{ str_repeat\(\'I\', \$idx - 1\) \}\}\./', 'I.', $content);
$content = preg_replace('/I\{\{ str_repeat\(\'I\', \$idx\) \}\}\./', 'II.', $content);
$content = preg_replace('/I\{\{ str_repeat\(\'I\', \$idx \+ 1\) \}\}\./', 'III.', $content);

// Tighten Harga Beli / Jual section
$content = str_replace('<td style="width: 33%; font-style: italic;">Harga Beli {{ $idx }}', '<td style="width: 250px; font-style: italic;">Harga Beli {{ $idx }}', $content);
$content = str_replace('<td style="width: 33%; font-style: italic;">Harga Jual {{ $idx }}', '<td style="width: 250px; font-style: italic;">Harga Jual {{ $idx }}', $content);

// Tighten table spaces (from 180px down to 100px or so for Stok Awal, etc)
// Wait, the text "Stok Awal" doesn't take 180px.
$content = str_replace('<td style="width: 180px;">Stok Awal</td>', '<td style="width: 100px;">Stok Awal</td>', $content);
$content = str_replace('<td style="width: 280px;">a. Totalisator Akhir', '<td style="width: 200px;">a. Totalisator Akhir', $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Fixed!";
