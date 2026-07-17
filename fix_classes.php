<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

// Fix double classes like: class="something" class="something-else"
// Replace them with: class="something something-else"
$content = preg_replace('/class="([^"]*)"\s+class="([^"]*)"/', 'class="$1 $2"', $content);

file_put_contents($file, $content);
echo "Fixed double classes.";
