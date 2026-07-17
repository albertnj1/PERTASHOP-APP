<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$oldStokAwal = "
                  @php
                      \$stokAwalPeriode = 0;
                  @endphp
                  @foreach(\$periods as \$idx => \$p)
";
$newStokAwal = "
                  @php
                      \$stokAwalPeriode = \$report->grand_totals['stok_awal_fisik'] ?? 0;
                  @endphp
                  @foreach(\$periods as \$idx => \$p)
";
$content = str_replace(trim($oldStokAwal), trim($newStokAwal), $content);

file_put_contents($file, $content);
echo "Done updating show.blade.php Page 1\n";
