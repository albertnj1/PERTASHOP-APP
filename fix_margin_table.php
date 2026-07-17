<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

$search = '/<!-- MARGIN TABLE -->.*?<\/div>\s*<\/div>\s*<!-- END BOTTOM SECTION -->/s';

$replace = <<<'HTML'
            <!-- MARGIN TABLE -->
            <div class="fw-bold font-italic mb-1">Ilustrasi Turun / Naik Margin Pertamax92 Pertashop</div>
            
            <!-- TOP TABLE (Harga Jual Changes) -->
            <table style="width: 100%; border: 2px solid #000; border-collapse: collapse; margin-bottom: 0; border-bottom: none;">
                @php
                    $keys = array_keys($page1Periods);
                @endphp
                @foreach($page1Periods as $idx => $p)
                    @php
                        $cIdx = array_search($idx, $keys);
                        if ($cIdx == 0) continue; // Skip first for the change table, or show it?
                        $prevP = $page1Periods[$keys[$cIdx - 1]];
                        $hargaLama = $prevP['harga_jual'] ?? 0;
                        $hargaBaru = $p['harga_jual'] ?? 0;
                        $selisih = $hargaBaru - $hargaLama;
                        $isNaik = $selisih > 0;
                        $arrow = $isNaik ? '&#8593;' : '&#8595;';
                        $color = $isNaik ? 'red' : 'blue'; // Usually Up is red in their image? No, wait, in image Up is red, Down is green? Let's use red for Up, green for Down. Wait, the image shows "Up Rp 400" in red, "Down Rp 100" in green. 
                        $color = $isNaik ? 'red' : 'green';
                    @endphp
                    <tr>
                        <td style="border: 1px solid #000; border-bottom: none; border-top: none; width: 30%;">{{ $p['start_date'] }} 2026</td>
                        <td style="border: 1px solid #000; border-bottom: none; border-top: none; font-weight: bold; color: red;">Rp {{ number_format($hargaLama, 0, ',', '.') }}</td>
                        <td style="border: 1px solid #000; border-bottom: none; border-top: none; width: 20px; text-align: center;">&#10132;</td>
                        <td style="border: 1px solid #000; border-bottom: none; border-top: none; font-weight: bold; color: #0d6efd;">Rp {{ number_format($hargaBaru, 0, ',', '.') }}</td>
                        <td style="border: 1px solid #000; border-bottom: none; border-top: none; font-weight: bold;">( <span style="color:{{$color}}">{{ $arrow }} Rp {{ number_format(abs($selisih), 0, ',', '.') }}</span> )</td>
                    </tr>
                @endforeach
                @if(count($page1Periods) == 1)
                    <tr>
                        <td colspan="5" style="border: 1px solid #000; border-bottom: none; text-align: center; font-style: italic;">Tidak ada perubahan harga di bulan ini</td>
                    </tr>
                @endif
            </table>

            <!-- BOTTOM TABLE (Margin) -->
            <table style="width: 100%; border: 2px solid #000; border-collapse: collapse;">
                <tr>
                    <th style="border: 1px solid #000; background: yellow; text-align: center;" rowspan="2"></th>
                    <th style="border: 1px solid #000; background: yellow; text-align: center;" rowspan="2">Harga Beli</th>
                    <th style="border: 1px solid #000; background: yellow; text-align: center;" rowspan="2">Harga Jual</th>
                    <th style="border: 1px solid #000; background: yellow; text-align: center;" rowspan="2">Margin</th>
                    <th style="border: 1px solid #000; background: yellow; text-align: center;">Margin</th>
                </tr>
                <tr>
                    <th style="border: 1px solid #000; background: yellow; text-align: center; font-weight: normal;">
                        <span style="color:green">Naik &#8593;</span> / <span style="color:red">Turun &#8595;</span>
                    </th>
                </tr>
                @foreach($page1Periods as $idx => $p)
                    @php
                        $margin = ($p['harga_jual'] ?? 0) - ($p['harga_beli'] ?? 0);
                        $cIdx = array_search($idx, $keys);
                        
                        $marginDiffStr = '-';
                        $marginColor = 'black';
                        $marginArrow = '';
                        
                        if ($cIdx > 0) {
                            $prevP = $page1Periods[$keys[$cIdx - 1]];
                            $prevMargin = ($prevP['harga_jual'] ?? 0) - ($prevP['harga_beli'] ?? 0);
                            $marginDiff = $margin - $prevMargin;
                            
                            if ($marginDiff > 0) {
                                $marginColor = 'green';
                                $marginArrow = '&#8593;';
                                $marginDiffStr = number_format($marginDiff, 2);
                            } else if ($marginDiff < 0) {
                                $marginColor = 'red';
                                $marginArrow = '&#8595;';
                                $marginDiffStr = '(' . number_format(abs($marginDiff), 2) . ')';
                            } else {
                                $marginDiffStr = '0.00';
                            }
                        }
                    @endphp
                    <tr>
                        <td style="border: 1px solid #000;">{{ $p['start_date'] }} 2026</td>
                        <td style="border: 1px solid #000; text-align: right;">Rp{{ number_format(($p['harga_beli'] ?? 0), 2) }}</td>
                        <td style="border: 1px solid #000; text-align: right;">Rp{{ number_format(($p['harga_jual'] ?? 0), 2) }}</td>
                        <td style="border: 1px solid #000; text-align: right;">{{ number_format($margin, 2) }}</td>
                        <td style="border: 1px solid #000; text-align: center; color: {{ $marginColor }};">
                            @if($marginArrow) {!! $marginArrow !!} @endif 
                            {{ $marginDiffStr }}
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
    <!-- END BOTTOM SECTION -->
HTML;

$content = preg_replace($search, $replace, $content);
file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Table injected!";
