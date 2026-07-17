<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

$content = str_replace('<td width="3%">1.</td>', '<td width="4%">1.</td>', $content);
$content = str_replace('<td width="55%" style="white-space: nowrap; overflow: hidden; max-width: 250px;">LABA KOTOR', '<td width="75%" style="white-space: nowrap; overflow: hidden; max-width: 450px;">LABA KOTOR', $content);
$content = str_replace('<td width="2%">=</td>', '<td width="2%">=</td>', $content);
$content = str_replace('<td width="5%">Rp</td>', '<td width="4%">Rp</td>', $content);
$content = str_replace('<td width="35%" class="text-right">', '<td width="15%" class="text-right">', $content);

// The pengeluaran items use default <td>, we need to add widths to them?
// Wait, the widths are defined by the first row of the table! The browser calculates it based on the first row (LABA KOTOR).
// So changing the first row is enough.

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Fixed widths!";
