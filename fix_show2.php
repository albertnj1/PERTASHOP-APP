<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// We want the text column (col 2) to be the same width for both Pembelian and Penjualan so the '=' signs align.
// Also we want to ensure the last column absorbs the remaining space.

// 1. Ensure all 'Stok Awal' and 'a. Totalisator' text columns are fixed width 250px
$content = str_replace('<td style="width: 100px;">Stok Awal</td>', '<td style="width: 280px;">Stok Awal</td>', $content);
$content = str_replace('<td style="width: 200px;">a. Totalisator Akhir', '<td style="width: 280px;">a. Totalisator Akhir', $content);

// 2. Remove 'width: 100%' from the tables so they don't stretch weirdly, OR let them stretch but ensure the last column has no width.
// In the current file, the tables have: <table style="width: 100%; border-collapse: collapse;">
// Let's change the last column of every row. The last column is usually <td style="width: 20px;"></td> or <td></td> or <td colspan="7"></td>
// Let's just remove width: 100% from the tables so they are tightly packed!
$content = str_replace('<table style="width: 100%; border-collapse: collapse;">', '<table style="border-collapse: collapse;">', $content);

// 3. Fix the "Sisa Stok Akhir" block at the bottom
// <td style="width: 160px; font-style: italic;">I{{ str_repeat('I', $idx + 1) }}. Sisa Stok Akhir
// Change to 280px so it aligns too! Wait, it has "III. Sisa Stok Akhir" + "102.80 cm".
$content = str_replace('<td style="width: 160px; font-style: italic;">III. Sisa Stok Akhir {{ $idx }}</td>', '<td style="width: 180px; font-style: italic;">III. Sisa Stok Akhir {{ $idx }}</td>', $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Fixed!";
