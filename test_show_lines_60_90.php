<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);
for ($i = 60; $i < 90; $i++) {
    echo "Line $i: " . $lines[$i] . "\n";
}
