<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

$search1 = <<<'EOD'
                foreach($p['rincian_pengeluaran'] as $rinc) {
                    $rincianAll[] = $rinc;
                    $totalBiaya += $rinc['nom'];
                }
EOD;

$replace1 = <<<'EOD'
                foreach($p['rincian_pengeluaran'] as $rinc) {
                    $rincianAll[] = $rinc;
                    $totalBiaya += $rinc['nominal'] ?? $rinc['nom'] ?? 0;
                }
EOD;

$content = str_replace($search1, $replace1, $content);

$search2 = <<<'EOD'
            @foreach($rincianAll as $i => $rinc)
            <tr>
                <td>{{ $i+1 }}.</td>
                <td>{{ strtoupper($rinc['ket']) }} ................................................................</td>
                <td>=</td>
                <td>Rp</td>
                <td class="text-right">{{ number_format($rinc['nom'], 0) }}</td>
            </tr>
            @endforeach
EOD;

$replace2 = <<<'EOD'
            @foreach($rincianAll as $i => $rinc)
            <tr>
                <td>{{ $i+1 }}.</td>
                <td>{{ strtoupper($rinc['keterangan'] ?? $rinc['ket'] ?? '') }} ................................................................</td>
                <td>=</td>
                <td>Rp</td>
                <td class="text-right">{{ number_format($rinc['nominal'] ?? $rinc['nom'] ?? 0, 0) }}</td>
            </tr>
            @endforeach
EOD;

$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Fixed rincian keys\n";
