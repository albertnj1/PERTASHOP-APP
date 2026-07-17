<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// Fix colspan in PENDAPATAN
$content = str_replace(
    '<td colspan="2" class="text-right fw-bold font-italic">A. Total Laba Kotor =</td>
            <td></td>
            <td class="fw-bold"', 
    '<td colspan="3" class="text-right fw-bold font-italic">A. Total Laba Kotor =</td>
            <td class="fw-bold"', 
    $content
);

// Fix colspan in PENGELUARAN
$content = str_replace(
    '<td colspan="2" class="text-right fw-bold font-italic">B. Total Biaya =</td>
            <td></td>
            <td class="fw-bold"', 
    '<td colspan="3" class="text-right fw-bold font-italic">B. Total Biaya =</td>
            <td class="fw-bold"', 
    $content
);

// Fix the calculation block
$searchBlock = '        <tr><td colspan="5" style="height:20px;"></td></tr>
        
        <tr>
            <td colspan="2" class="text-right fw-bold font-italic">A. Total Laba Kotor =</td>
            <td></td>
            <td>Rp</td>
            <td class="text-right">{{ number_format($totalLabaKotor, 0, \',\', \'.\') }}</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right fw-bold font-italic">B. Total Biaya =</td>
            <td></td>
            <td style="border-bottom:1px solid #000 !important">Rp</td>
            <td class="text-right" style="border-bottom:1px solid #000 !important">{{ number_format($totalBiaya, 0, \',\', \'.\') }} -</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right fw-bold font-italic">(A-B) LABA BERSIH =</td>
            <td></td>
            <td class="fw-bold">Rp</td>
            <td class="text-right fw-bold">{{ number_format($labaBersih, 0, \',\', \'.\') }}</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right fw-bold font-italic" style="color:red">*) Alokasi Penambahan Modal dari 10% Profit =</td>
            <td></td>
            <td class="fw-bold" style="color:red">Rp</td>
            <td class="text-right fw-bold" style="color:red; border-bottom:1px solid #000 !important">{{ number_format($alokasiModal, 0, \',\', \'.\') }} -</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right font-italic">Saldo Laba Bersih (90%) yg Dibagi =</td>
            <td></td>
            <td class="fw-bold font-italic">Rp</td>
            <td class="text-right fw-bold font-italic">{{ number_format($labaDibagi, 0, \',\', \'.\') }}</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right font-italic">Saldo Laba Bersih Bulan (SEBELUMNYA) yg blm Dibagi =</td>
            <td></td>
            <td class="fw-bold font-italic" style="border-bottom:1px solid #000 !important">Rp</td>
            <td class="text-right fw-bold font-italic" style="border-bottom:1px solid #000 !important">- +</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right font-italic fw-bold">Total Saldo Laba Bersih yg Dibagi =</td>
            <td></td>
            <td class="fw-bold font-italic" style="border-bottom:1px solid #000 !important; border-bottom-style: double !important;">Rp</td>
            <td class="text-right fw-bold font-italic" style="border-bottom:1px solid #000 !important; border-bottom-style: double !important;">{{ number_format($labaDibagi, 0, \',\', \'.\') }}</td>
        </tr>';

$replaceBlock = '        <tr><td colspan="5" style="height:20px;"></td></tr>
        
        <tr>
            <td colspan="3" class="text-right fw-bold font-italic">A. Total Laba Kotor =</td>
            <td>Rp</td>
            <td class="text-right">{{ number_format($totalLabaKotor, 0, \',\', \'.\') }}</td>
        </tr>
        <tr>
            <td colspan="3" class="text-right fw-bold font-italic">B. Total Biaya =</td>
            <td style="border-bottom:1px solid #000 !important">Rp</td>
            <td class="text-right" style="border-bottom:1px solid #000 !important">{{ number_format($totalBiaya, 0, \',\', \'.\') }} -</td>
        </tr>
        <tr>
            <td colspan="3" class="text-right fw-bold font-italic">(A-B) LABA BERSIH =</td>
            <td class="fw-bold">Rp</td>
            <td class="text-right fw-bold">{{ number_format($labaBersih, 0, \',\', \'.\') }}</td>
        </tr>
        <tr>
            <td colspan="3" class="text-right fw-bold font-italic" style="color:red">*) Alokasi Penambahan Modal dari 10% Profit =</td>
            <td class="fw-bold" style="color:red">Rp</td>
            <td class="text-right fw-bold" style="color:red; border-bottom:1px solid #000 !important">{{ number_format($alokasiModal, 0, \',\', \'.\') }} -</td>
        </tr>
        <tr>
            <td colspan="3" class="text-right font-italic">Saldo Laba Bersih (90%) yg Dibagi =</td>
            <td class="fw-bold font-italic">Rp</td>
            <td class="text-right fw-bold font-italic">{{ number_format($labaDibagi, 0, \',\', \'.\') }}</td>
        </tr>
        <tr>
            <td colspan="3" class="text-right font-italic">Saldo Laba Bersih Bulan (SEBELUMNYA) yg blm Dibagi =</td>
            <td class="fw-bold font-italic" style="border-bottom:1px solid #000 !important">Rp</td>
            <td class="text-right fw-bold font-italic" style="border-bottom:1px solid #000 !important">- +</td>
        </tr>
        <tr>
            <td colspan="3" class="text-right font-italic fw-bold">Total Saldo Laba Bersih yg Dibagi =</td>
            <td class="fw-bold font-italic" style="border-bottom:1px solid #000 !important; border-bottom-style: double !important;">Rp</td>
            <td class="text-right fw-bold font-italic" style="border-bottom:1px solid #000 !important; border-bottom-style: double !important;">{{ number_format($labaDibagi, 0, \',\', \'.\') }}</td>
        </tr>';

$content = str_replace($searchBlock, $replaceBlock, $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Fixed colspans!";
