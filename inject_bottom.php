<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');
$bottom = file_get_contents('page1_bottom.blade.php');

// We append $bottom right before the closing </div> of Page 1.
$pattern = '/\s*<\/div>\s*<!-- PAGE 2: LABA BERSIH -->/s';
$replacement = "\n" . $bottom . "\n</div>\n\n<!-- PAGE 2: LABA BERSIH -->";

$content = preg_replace($pattern, $replacement, $content);
file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Injected!";
