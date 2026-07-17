<div class="report-page">
    <div class="page1-header" style="text-align: center; font-weight: bold; font-family: 'Times New Roman', Times, serif; font-size: 12px; border-bottom: 3px double #000; margin-bottom: 5px; padding-bottom: 5px;">
        LAPORAN STOCK , PENJUALAN &amp; LABA KOTOR {{ \Carbon\Carbon::parse($report->bulan_tahun)->format('01-t F Y') }}<br>
        PERTASHOP {{ $report->shop->kode }} {{ strtoupper($report->shop->alamat) }}<br>
        {{ $companyName }}
    </div>
    
    <table class="report-table-page1" style="width: 100%; font-size: 11px; font-family: 'Times New Roman', Times, serif; border-bottom: 1px solid #000; margin-bottom: 10px;">
        <tr>
            <td class="font-italic text-decoration-underline" colspan="3">PERTAMAX :</td>
        </tr>
        @php
            $avgOmset = number_format($grandTotals['penjualan_liter'] / \Carbon\Carbon::parse($report->bulan_tahun)->daysInMonth, 2);
        @endphp
        @foreach($page1Periods as $idx => $p)
        <tr>
            <td style="width: 33%; font-style: italic;">Harga Beli {{ $idx }} : Rp {{ number_format(($p['harga_beli'] ?? 0), 2) }},-</td>
            <td style="width: 33%; font-style: italic;">Harga Jual {{ $idx }} : Rp {{ number_format(($p['harga_jual'] ?? 0), 0) }},-</td>
            <td style="width: 33%; text-align: right;">
                @if($idx == 2)
                Rata-rata omset Harian (&ell;) = {{ $avgOmset }}
                @endif
            </td>
        </tr>
        @endforeach
    </table>

    @foreach($page1Periods as $idx => $p)
    @php
        $skala = $report->shop->skala > 0 ? $report->shop->skala : 21;
        
        $totBbmDatang = floatval($p['bbm_datang']);
        $cnt2000 = floor($totBbmDatang / 2000);
        $cnt1000 = ($totBbmDatang - ($cnt2000 * 2000)) / 1000;
        
        $isLoss = $p['losses'] < 0;
        $color = $isLoss ? 'red' : 'black';
        $lossLabel = $isLoss ? 'Losses' : 'Gain';
        $sign = $isLoss ? '-' : '+';
        $valFormat = $isLoss ? '(' . number_format(abs($p['losses']), 3) . ')' : number_format(abs($p['losses']), 3);
        $rpFormat = $isLoss ? '(' . number_format(abs($p['losses_rp']), 0) . ')' : number_format(abs($p['losses_rp']), 0);
        $persenFormat = $isLoss ? '(' . number_format(abs($p['losses_persen']), 3) . ')' : number_format(abs($p['losses_persen']), 3);
    @endphp
    <div style="font-size: 11px; font-family: 'Times New Roman', Times, serif; margin-bottom: 10px;">
        <div class="fw-bold">I{{ str_repeat('I', $idx - 1) }}. PEMBELIAN {{ $idx }}</div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 30px;"></td>
                <td style="width: 180px;">Stok Awal</td>
                <td style="width: 20px;">=</td>
                <td style="width: 60px; text-align: right;">{{ number_format($p['stok_awal'], 2) }}</td>
                <td style="width: 20px; text-align: center;">&ell;</td>
                <td style="width: 20px; text-align: center;">x</td>
                <td style="width: 20px;">Rp</td>
                <td style="width: 70px; text-align: right;">{{ number_format(($p['harga_beli'] ?? 0), 2) }}</td>
                <td style="width: 30px; text-align: center; font-weight: bold;">&#10132;</td>
                <td style="width: 25px;">Rp</td>
                <td style="width: 80px; text-align: right;">{{ number_format($p['stok_awal'] * ($p['harga_beli'] ?? 0), 0) }}</td>
                <td style="width: 20px;"></td>
            </tr>
            <tr>
                <td></td>
                <td>BBM Datang <span style="display:inline-block; width:20px;"></span> 0 <span style="display:inline-block; width:15px; text-align:center;">x</span> 1,000 &ell;</td>
                <td>=</td>
                <td style="text-align: right;">{{ $cnt1000 > 0 ? number_format($cnt1000 * 1000, 2) : '-' }}</td>
                <td style="text-align: center;">&ell;</td>
                <td style="text-align: center;">x</td>
                <td>Rp</td>
                <td style="text-align: right;">{{ number_format(($p['harga_beli'] ?? 0), 2) }}</td>
                <td style="text-align: center; font-weight: bold;">&#10132;</td>
                <td>Rp</td>
                <td style="text-align: right;">{{ $cnt1000 > 0 ? number_format($cnt1000 * 1000 * ($p['harga_beli'] ?? 0), 0) : '-' }}</td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td>BBM Datang <span style="display:inline-block; width:20px;"></span> {{ $cnt2000 }} <span style="display:inline-block; width:15px; text-align:center;">x</span> 2,000 &ell;</td>
                <td>=</td>
                <td style="text-align: right; border-bottom: 1px solid #000;">{{ $cnt2000 > 0 ? number_format($cnt2000 * 2000, 2) : '-' }}</td>
                <td style="text-align: center; border-bottom: 1px solid #000;">&ell;</td>
                <td style="text-align: center;">x</td>
                <td>Rp</td>
                <td style="text-align: right;">{{ number_format(($p['harga_beli'] ?? 0), 2) }}</td>
                <td style="text-align: center; font-weight: bold;">&#10132;</td>
                <td style="border-bottom: 1px solid #000;">Rp</td>
                <td style="text-align: right; border-bottom: 1px solid #000;">{{ $cnt2000 > 0 ? number_format($cnt2000 * 2000 * ($p['harga_beli'] ?? 0), 0) : '-' }}</td>
                <td style="text-align: center;">+</td>
            </tr>
            <tr>
                <td></td>
                <td class="font-italic fw-bold">A. Jumlah Pembelian {{ $idx }}</td>
                <td></td>
                <td style="text-align: right;">{{ number_format($p['jml_beli_l'], 2) }}</td>
                <td style="text-align: center;">&ell;</td>
                <td colspan="4"></td>
                <td class="fw-bold">Rp</td>
                <td class="fw-bold" style="text-align: right;">{{ number_format($p['jml_beli_rp'], 0) }}</td>
                <td></td>
            </tr>
        </table>
        
        <div class="fw-bold mt-2">I{{ str_repeat('I', $idx) }}. PENJUALAN {{ $idx }}</div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 30px;"></td>
                <td style="width: 280px;">a. Totalisator Akhir ({{ $p['end_date'] ?? '-' }})</td>
                <td style="width: 20px;">=</td>
                <td style="width: 80px; text-align: right;">{{ number_format($p['tot_akhir'] ?? 0, 2) }}</td>
                <td style="width: 20px; text-align: center;">&ell;</td>
                <td colspan="7"></td>
            </tr>
            <tr>
                <td></td>
                <td>b. Totalisator Awal ({{ $p['start_date'] ?? '-' }})</td>
                <td>=</td>
                <td style="text-align: right; border-bottom: 1px solid #000;">{{ number_format($p['tot_awal'] ?? 0, 2) }}</td>
                <td style="text-align: center; border-bottom: 1px solid #000;">&ell;</td>
                <td style="text-align: center;">-</td>
                <td colspan="6"></td>
            </tr>
            <tr>
                <td></td>
                <td class="fw-bold">c. Total Penjualan {{ $idx }} (a-b)</td>
                <td>=</td>
                <td class="fw-bold" style="text-align: right;">{{ number_format($p['tot_jual'], 2) }}</td>
                <td class="fw-bold" style="text-align: center;">&ell;</td>
                <td colspan="7"></td>
            </tr>
            <tr>
                <td></td>
                <td>d. Percobaan (Test Pump)</td>
                <td>=</td>
                <td style="text-align: right; border-bottom: 1px solid #000;">{{ ($p['test_pump'] ?? 0) > 0 ? number_format($p['test_pump'], 2) : '-' }}</td>
                <td style="text-align: center; border-bottom: 1px solid #000;">&ell;</td>
                <td style="text-align: center;">-</td>
                <td colspan="6"></td>
            </tr>
            <tr>
                <td></td>
                <td class="font-italic fw-bold">B. Jumlah Penjualan {{ $idx }} (c-d)</td>
                <td>=</td>
                <td style="text-align: right;">{{ number_format($p['jml_jual'], 2) }}</td>
                <td style="text-align: center;">&ell;</td>
                <td style="width: 20px; text-align: center;">x</td>
                <td style="width: 20px;">Rp</td>
                <td style="width: 70px; text-align: right;">{{ number_format(($p['harga_jual'] ?? 0), 2) }}</td>
                <td style="width: 30px; text-align: center; font-weight: bold;">&#10132;</td>
                <td style="width: 25px;">Rp</td>
                <td style="width: 80px; text-align: right;">{{ number_format($p['jml_jual_rp'], 0) }}</td>
                <td style="width: 20px;"></td>
            </tr>
            <tr>
                <td></td>
                <td class="font-italic">Sisa Stock {{ $idx }} (A-B)</td>
                <td>=</td>
                <td style="text-align: right;">{{ number_format($p['sisa_stok'], 2) }}</td>
                <td style="text-align: center;">&ell;</td>
                <td style="text-align: center;">x</td>
                <td>Rp</td>
                <td style="text-align: right;">{{ number_format(($p['harga_beli'] ?? 0), 2) }}</td>
                <td style="text-align: center; font-weight: bold;">&#10132;</td>
                <td style="border-bottom: 1px solid #000;">Rp</td>
                <td style="text-align: right; border-bottom: 1px solid #000;">{{ number_format($p['sisa_stok_rp'], 0) }}</td>
                <td style="text-align: center;">+</td>
            </tr>
            <tr>
                <td></td>
                <td>Jumlah {{ $idx }}</td>
                <td>=</td>
                <td colspan="6"></td>
                <td>Rp</td>
                <td style="text-align: right;">{{ number_format($p['jml_jual_rp'] + $p['sisa_stok_rp'], 0) }}</td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td>Losses / Gain <span style="display:inline-block; width:20px; text-align:center; font-weight:bold;">&#10132;</span> <span style="color:{{$color}}">{{ $lossLabel }}</span> &nbsp;&nbsp;&nbsp; <span style="color:{{$color}}">{{ $persenFormat }} %</span></td>
                <td>=</td>
                <td style="text-align: right;"><span style="color:{{$color}}">{{ $valFormat }}</span></td>
                <td style="text-align: center;">&ell;</td>
                <td style="text-align: center;">x</td>
                <td>Rp</td>
                <td style="text-align: right;">{{ number_format(($p['harga_beli'] ?? 0), 2) }}</td>
                <td style="text-align: center; font-weight: bold;">&#10132;</td>
                <td style="border-bottom: 1px solid #000;"><span style="color:{{$color}}">Rp</span></td>
                <td style="text-align: right; border-bottom: 1px solid #000;"><span style="color:{{$color}}">{{ $rpFormat }}</span></td>
                <td style="text-align: center;">+</td>
            </tr>
            <tr>
                <td></td>
                <td class="font-italic fw-bold">C. Jumlah Penjualan Bersih {{ $idx }}</td>
                <td colspan="7"></td>
                <td class="fw-bold">Rp</td>
                <td class="fw-bold" style="text-align: right;">{{ number_format($p['penjualan_bersih_rp'], 0) }}</td>
                <td></td>
            </tr>
        </table>
        
        <div class="fw-bold mt-2" style="border-bottom: 1px solid #000; padding-bottom: 5px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 160px; font-style: italic;">I{{ str_repeat('I', $idx + 1) }}. Sisa Stok Akhir {{ $idx }}</td>
                    <td style="width: 20px;">:</td>
                    <td style="width: 100px;">{{ number_format(($p['stok_aktual'] ?? 0) / $skala, 2) }} cm</td>
                    <td style="width: 30px;">=</td>
                    <td style="width: 60px; text-align: right; font-weight: normal;">{{ number_format(($p['stok_aktual'] ?? 0), 2) }}</td>
                    <td style="width: 20px; text-align: center; font-weight: normal;">&ell;</td>
                    <td style="width: 20px; text-align: center; font-weight: normal;">x</td>
                    <td style="width: 20px; font-weight: normal;">Rp</td>
                    <td style="width: 70px; text-align: right; font-weight: normal;">{{ number_format(($p['harga_beli'] ?? 0), 2) }}</td>
                    <td style="width: 30px; text-align: center; font-weight: bold;">&#10132;</td>
                    <td style="width: 25px; font-weight: normal;">Rp</td>
                    <td style="width: 80px; text-align: right; font-weight: normal;">{{ number_format(($p['stok_aktual'] ?? 0) * ($p['harga_beli'] ?? 0), 0) }}</td>
                    <td style="width: 20px;"></td>
                </tr>
            </table>
        </div>
    </div>
    @endforeach
</div>
