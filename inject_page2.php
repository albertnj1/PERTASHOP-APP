<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');
$page2 = file_get_contents('page2_new.blade.php');

$pattern = '/<!-- PAGE 2: LABA BERSIH -->.*?<\/div>\s*<!-- PAGE 3: POSISI MODAL KERJA -->/s';
$replacement = "<!-- PAGE 2: LABA BERSIH -->\n" . $page2 . "\n\n    <!-- PAGE 3: POSISI MODAL KERJA -->";

$content = preg_replace($pattern, $replacement, $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Injected page 2!";
