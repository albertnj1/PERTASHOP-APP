<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

// Find any $p['some_key'] that doesn't have ?? inside the bracket
// Actually, it's easier to just do it via regex replacing any $p['something'] that is not immediately followed by ??
// Or just let's look at lines 38-50 in the file to see if I missed any.
$lines = explode("\n", $content);
for ($i = 30; $i < 60; $i++) {
    echo "Line $i: " . $lines[$i] . "\n";
}
