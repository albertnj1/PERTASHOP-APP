<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// We replace the current Ilustrasi table block with a placeholder so we can inject the complex one.
$pattern = '/<!-- MARGIN TABLE -->.*?<\/table>\s*<\/div>/s';
$content = preg_replace($pattern, '<!-- MARGIN TABLE REPLACE --></div>', $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Cleared old margin table!";
