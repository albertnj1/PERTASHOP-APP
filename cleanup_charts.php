<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// Remove all instances of CHART PENJUALAN HARIAN and its block
$content = preg_replace('/<!-- CHART PENJUALAN HARIAN -->.*?<\/div>\s*<\/div>/s', '', $content);
$content = preg_replace('/<!-- CHART PENJUALAN HARIAN -->.*?<\/div>/s', '', $content);

// Now re-insert exactly one CHART PENJUALAN HARIAN before the END BOTTOM SECTION
$canvas1 = <<<EOT
    <!-- CHART PENJUALAN HARIAN -->
    <div style="margin-top: 30px; height: 350px; width: 100%; border: 1px solid #e2e8f0; padding: 15px; border-radius: 10px; background-color: #f8fafc;" class="shadow-sm d-print-table-row">
        <canvas id="chartPenjualan"></canvas>
    </div>
EOT;

$content = str_replace('<!-- END BOTTOM SECTION -->', $canvas1 . "\n    <!-- END BOTTOM SECTION -->", $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Cleaned up charts.";
