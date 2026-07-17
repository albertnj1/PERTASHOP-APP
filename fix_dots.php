<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// Replace the float:right dots with inline dots and a wrapper for the td
$content = str_replace('<td width="55%">LABA KOTOR <span style="float:right;">.......................................................................</span></td>', '<td width="55%" style="white-space: nowrap; overflow: hidden; max-width: 250px;">LABA KOTOR ......................................................................................................................................</td>', $content);

$content = str_replace('<td>{{ strtoupper($rinc[\'keterangan\'] ?? $rinc[\'ket\'] ?? \'\') }} <span style="float:right;">.......................................................................</span></td>', '<td style="white-space: nowrap; overflow: hidden; max-width: 250px;">{{ strtoupper($rinc[\'keterangan\'] ?? $rinc[\'ket\'] ?? \'\') }} ......................................................................................................................................</td>', $content);

$content = str_replace('<td width="35%">{{ $inv[\'nama\'] }} <span style="float:right;">.......................................</span></td>', '<td width="35%" style="white-space: nowrap; overflow: hidden; max-width: 150px;">{{ $inv[\'nama\'] }} .....................................................................................................</td>', $content);

$content = str_replace('<td width="65%">{{ ucwords(strtolower($inv[\'nama_bank\'] ?? \'\')) }} {{ $inv[\'no_rekening\'] ?? \'\' }} a/n {{ strtoupper($inv[\'atas_nama_rekening\'] ?? \'\') }} <span style="float:right;">..........................................</span></td>', '<td width="65%" style="white-space: nowrap; overflow: hidden; max-width: 300px;">{{ ucwords(strtolower($inv[\'nama_bank\'] ?? \'\')) }} {{ $inv[\'no_rekening\'] ?? \'\' }} a/n {{ strtoupper($inv[\'atas_nama_rekening\'] ?? \'\') }} ......................................................................................................................................</td>', $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Fixed dots!";
