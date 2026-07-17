    <!-- BOTTOM SECTION OF PAGE 1 -->
    <div style="display: flex; justify-content: space-between; margin-top: 20px; font-family: 'Times New Roman', Times, serif; font-size: 11px;">
        <!-- LEFT SIDE: DO DI MAOS -->
        <div style="width: 48%;">
            <div class="fw-bold font-italic mb-1">IV. Sisa Stock DO Di Maos</div>
            <table style="width: 100%; border-collapse: collapse; border: 2px solid #000;">
                <tr>
                    <td style="border: 1px solid #000; width: 60%;"></td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold;">PERTAMAX</td>
                </tr>
                @php
                    // Compute DO Logic
                    // 1. Datang (Total volume of BBM Datang)
                    $totalDatangL = 0;
                    foreach($page1Periods as $p) {
                        $totalDatangL += floatval($p['bbm_datang']);
                    }
                    $datangKL = $totalDatangL / 1000;
                    
                    // 2. Sisa (from report)
                    $sisaKL = floatval($report->do_di_pertamina ?? 0);
                    
                    // 3. Stok Awal (prev month Sisa)
                    $prevReport = \App\Models\MonthlyReport::where('shop_id', $report->shop_id)
                        ->where('bulan_tahun', '<', $report->bulan_tahun)
                        ->orderBy('bulan_tahun', 'desc')->first();
                    $stokAwalKL = $prevReport ? floatval($prevReport->do_di_pertamina ?? 0) : 0;
                    // If no prev report, let's try to infer from a math check or just 0
                    
                    // 4. Setor (Calculated)
                    // Stok Awal + Setor = Jumlah
                    // Jumlah - Datang = Sisa => Jumlah = Sisa + Datang
                    $jumlahKL = $sisaKL + $datangKL;
                    $setorKL = $jumlahKL - $stokAwalKL;
                @endphp
                <tr>
                    <td style="border: 1px solid #000;">Stok Awal</td>
                    <td style="border: 1px solid #000; text-align: right;">{{ number_format($stokAwalKL, 2) }} KL</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000;">Setor</td>
                    <td style="border: 1px solid #000; text-align: right;">{{ number_format($setorKL, 2) }} KL</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000;">Setoran Tunai</td>
                    <td style="border: 1px solid #000; text-align: right;">- KL</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000;">Jumlah</td>
                    <td style="border: 1px solid #000; text-align: right;">{{ number_format($jumlahKL, 2) }} KL</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000;">Datang</td>
                    <td style="border: 1px solid #000; text-align: right;">{{ number_format($datangKL, 2) }} KL</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000;">Sisa</td>
                    <td style="border: 1px solid #000; text-align: right;">{{ $sisaKL > 0 ? number_format($sisaKL, 2) : '-' }} KL <span style="color:red">* )</span></td>
                </tr>
            </table>

            @if(count($page1Periods) > 1 && $stokAwalKL > 0)
                @php
                    $keys = array_keys($page1Periods);
                    $firstPeriod = $page1Periods[$keys[0]];
                    $secondPeriod = $page1Periods[$keys[1]];
                    $hargaLama = $firstPeriod['harga_beli'] ?? 0;
                    $hargaBaru = $secondPeriod['harga_beli'] ?? 0;
                    $isNaik = $hargaBaru > $hargaLama;
                    $labelNaikTurun = $isNaik ? 'Naik' : 'Turun';
                    $colorNaikTurun = $isNaik ? 'red' : 'blue';
                    
                    $valEksisting = $stokAwalKL * 1000 * $hargaLama;
                    $valBaru = $stokAwalKL * 1000 * $hargaBaru;
                    $selisih = $valEksisting - $valBaru;
                @endphp
                <div class="mt-3">
                    <div><span style="color:red">* )</span> Selisih Naik/Turun Harga (Kurang/Lebih Bayar)</div>
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td class="fw-bold">Harga Beli Eksisting</td>
                            <td style="width: 20px;">= Rp</td>
                            <td style="text-align: right; width: 80px;" class="fw-bold">{{ number_format($valEksisting, 0) }}</td>
                            <td style="text-align: right;">(per {{ number_format($stokAwalKL, 0) }}KL)</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Harga Beli <span style="color:{{$colorNaikTurun}}">{{ $labelNaikTurun }}</span></td>
                            <td>= Rp</td>
                            <td style="text-align: right; border-bottom: 1px solid #000;" class="fw-bold">{{ number_format($valBaru, 0) }}</td>
                            <td style="text-align: right;">(per {{ number_format($stokAwalKL, 0) }}KL) -</td>
                        </tr>
                        <tr>
                            <td>Selisih Harga / K.B</td>
                            <td>= <span style="color:red">Rp</span></td>
                            <td style="text-align: right; color:red;">({{ number_format(abs($selisih), 0) }})</td>
                            <td></td>
                        </tr>
                    </table>
                </div>
            @endif
        </div>

        <!-- RIGHT SIDE: LABA KOTOR & MARGIN -->
        <div style="width: 48%;">
            @foreach($page1Periods as $idx => $p)
            @php
                $isNaik = false;
                $labelNT = 'Naik/Turun';
                if ($idx > 1) {
                    $prevP = $page1Periods[$idx - 1] ?? null;
                    if ($prevP) {
                        $isNaik = ($p['harga_jual'] ?? 0) > ($prevP['harga_jual'] ?? 0);
                        $labelNT = $isNaik ? 'Naik' : 'Turun';
                    }
                }
            @endphp
            <table style="width: 100%; border: 3px solid #000; margin-bottom: 10px; border-collapse: collapse;">
                <tr>
                    <td rowspan="3" style="width: 35%; text-align: center; border-right: 2px solid #000;">
                        <div style="font-size: 14px;">Total Laba Kotor</div>
                        <div style="font-size: 12px;">Setelah <span style="color:#0d6efd">Naik</span>/<span style="color:red">Turun</span></div>
                    </td>
                    <td style="text-align: right; width: 40%;">Total Penjualan {{ $idx }}</td>
                    <td style="width: 5%;"> = Rp</td>
                    <td style="text-align: right; width: 20%;">{{ number_format($p['penjualan_bersih_rp'], 0) }}</td>
                </tr>
                <tr>
                    <td style="text-align: right;">Total Pembelian {{ $idx }}</td>
                    <td> = Rp</td>
                    <td style="text-align: right; border-bottom: 1px solid #000;">{{ number_format($p['jml_beli_rp'], 0) }}</td>
                    <td> -</td>
                </tr>
                <tr>
                    <td style="text-align: right; font-style: italic; font-weight: bold;">Total Laba Kotor {{ $idx }}</td>
                    <td style="font-weight: bold; font-style: italic;"> = Rp</td>
                    <td style="text-align: right; font-weight: bold; font-style: italic;">{{ number_format($p['laba_kotor'], 0) }}</td>
                </tr>
            </table>
            @endforeach

            <table style="width: 100%; border: 3px solid #000; margin-bottom: 10px; border-collapse: collapse;">
                <tr>
                    <td style="width: 35%; text-align: center; border-right: 2px solid #000; font-size: 16px; font-weight: bold;">
                        Grand Total Laba<br>Kotor
                    </td>
                    <td style="padding: 0; vertical-align: top;">
                        <table style="width: 100%; border: none;">
                            @foreach($page1Periods as $idx => $p)
                            <tr>
                                <td style="text-align: right; width: 60%;">Total Laba Kotor {{ $idx }}</td>
                                <td style="width: 10%;"> = Rp</td>
                                <td style="text-align: right; width: 30%; {{ $loop->last ? 'border-bottom: 1px solid #000;' : '' }}">{{ number_format($p['laba_kotor'], 0) }}</td>
                                <td>{{ $loop->last ? ' +' : '' }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td style="text-align: right; font-weight: bold; font-style: italic;">Grand Total Laba Kotor</td>
                                <td style="font-weight: bold; font-style: italic;"> = Rp</td>
                                <td style="text-align: right; font-weight: bold; font-style: italic;">{{ number_format($totalLabaKotor, 0) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- MARGIN TABLE -->
            <div class="fw-bold font-italic mb-1">Ilustrasi Turun / Naik Margin Pertamax92 Pertashop</div>
            <table style="width: 100%; border: 2px solid #000; border-collapse: collapse;">
                <tr>
                    <th style="border: 1px solid #000; background: yellow; text-align: center;">Tanggal</th>
                    <th style="border: 1px solid #000; background: yellow; text-align: center;">Harga Beli</th>
                    <th style="border: 1px solid #000; background: yellow; text-align: center;">Harga Jual</th>
                    <th style="border: 1px solid #000; background: yellow; text-align: center;">Margin</th>
                </tr>
                @foreach($page1Periods as $idx => $p)
                <tr>
                    <td style="border: 1px solid #000;">{{ $p['start_date'] }} 2026</td>
                    <td style="border: 1px solid #000; text-align: right;">Rp{{ number_format(($p['harga_beli'] ?? 0), 2) }}</td>
                    <td style="border: 1px solid #000; text-align: right;">Rp{{ number_format(($p['harga_jual'] ?? 0), 2) }}</td>
                    <td style="border: 1px solid #000; text-align: right;">{{ number_format(($p['harga_jual'] ?? 0) - ($p['harga_beli'] ?? 0), 2) }}</td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
    <!-- END BOTTOM SECTION -->
