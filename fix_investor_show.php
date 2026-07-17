<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$oldInvestorsHtml = "
                  <div class=\"bold mt-5 mb-3\">Pembagian Laba Bersih:</div>
                  @foreach(\$report->shop->investors as \$idx => \$inv)
                  <div class=\"flex-row-dots mb-1\">
                      <div>{{ \$idx + 1 }}. {{ \$inv->nama_investor }}</div>
                      <div class=\"dotted-leader\"></div>
                      <div>{{ \$inv->persentase_kepemilikan }}%</div>
                      <div>= Rp</div>
                      <div style=\"width: 120px; text-align: right;\">{{ number_format(\$labaDibagi * 
(\$inv->persentase_kepemilikan / 100), 0) }}</div>
                  </div>
                  @endforeach
";

$newInvestorsHtml = "
                  <div class=\"bold mt-5 mb-3\">Pembagian Laba Bersih:</div>
                  @php
                      \$profitSharing = \$report->grand_totals['profit_sharing'] ?? [];
                  @endphp
                  @foreach(\$profitSharing as \$idx => \$inv)
                  <div class=\"flex-row-dots mb-1\">
                      <div>{{ \$idx + 1 }}. {{ strtoupper(\$inv['nama']) }}</div>
                      <div class=\"dotted-leader\"></div>
                      <div>{{ \$inv['persentase'] }}%</div>
                      <div>= Rp</div>
                      <div style=\"width: 120px; text-align: right;\">{{ number_format(\$labaDibagi * (\$inv['persentase'] / 100), 0) }}</div>
                  </div>
                  @endforeach
";

$content = str_replace(trim($oldInvestorsHtml), trim($newInvestorsHtml), $content);

file_put_contents($file, $content);
echo "Done modifying show.blade.php\n";
