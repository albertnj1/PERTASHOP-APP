<?php
$c = file_get_contents('resources/views/monthly_reports/show.blade.php');
$p = file_get_contents('page1_test.blade.php');
$c = preg_replace('/<!-- PAGE 1: LABA KOTOR -->.*?(?=<!-- PAGE 2: LABA BERSIH -->)/s', "<!-- PAGE 1: LABA KOTOR -->\n" . $p . "\n\n", $c);
file_put_contents('resources/views/monthly_reports/show.blade.php', $c);
echo 'Replaced!';
