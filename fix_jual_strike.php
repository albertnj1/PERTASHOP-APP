<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// Fix the typo where I used harga_beli instead of harga_jual for isNaik/isTurun
$content = str_replace(
    "\$isNaik = (\$p['harga_beli'] ?? 0) > (\$prevP['harga_beli'] ?? 0);", 
    "\$isNaik = (\$p['harga_jual'] ?? 0) > (\$prevP['harga_jual'] ?? 0);", 
    $content
);
$content = str_replace(
    "\$isTurun = (\$p['harga_beli'] ?? 0) < (\$prevP['harga_beli'] ?? 0);", 
    "\$isTurun = (\$p['harga_jual'] ?? 0) < (\$prevP['harga_jual'] ?? 0);", 
    $content
);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Fixed!";
