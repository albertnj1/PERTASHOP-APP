<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$search1 = <<<'EOD'
                <td colspan="2">Loses / Gain <span style="color:red">({{ number_format($p['losses_persen'], 3) }} %)</span></td>
                <td class="text-center">=</td>
                <td><span style="color:red">({{ number_format(abs($p['losses']), 2) }})</span> L</td>
                <td class="text-center">x</td>
                <td>Rp {{ number_format(($p['harga_beli'] ?? 0), 2) }}</td>
                <td class="text-right"><span style="color:red">= Rp ({{ number_format(abs($p['losses_rp']), 0) }})</span></td>
EOD;

$replace1 = <<<'EOD'
                @php
                    $isLoss = $p['losses'] < 0;
                    $color = $isLoss ? 'red' : 'green';
                    $sign = $isLoss ? '-' : '+';
                    $valFormat = $isLoss ? '(' . number_format(abs($p['losses']), 2) . ')' : number_format(abs($p['losses']), 2);
                    $rpFormat = $isLoss ? '(' . number_format(abs($p['losses_rp']), 0) . ')' : number_format(abs($p['losses_rp']), 0);
                    $persenFormat = $isLoss ? '(' . number_format(abs($p['losses_persen']), 3) . ')' : number_format(abs($p['losses_persen']), 3);
                @endphp
                <td colspan="2">Loses / Gain <span style="color:{{$color}}">{{ $persenFormat }} %</span></td>
                <td class="text-center">=</td>
                <td><span style="color:{{$color}}">{{ $sign }} {{ $valFormat }}</span> L</td>
                <td class="text-center">x</td>
                <td>Rp {{ number_format(($p['harga_beli'] ?? 0), 2) }}</td>
                <td class="text-right"><span style="color:{{$color}}">= {{ $sign }} Rp {{ $rpFormat }}</span></td>
EOD;

$content = str_replace($search1, $replace1, $content);
file_put_contents($file, $content);
echo "Fixed Loses/Gain formatting in show.blade.php\n";
