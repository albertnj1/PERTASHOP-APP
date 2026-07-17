<div class="report-page">
    <div class="report-header" style="text-align: center; font-weight: bold; font-family: 'Times New Roman', Times, serif; font-size: 14px; border-bottom: 3px double #000; margin-bottom: 5px; padding-bottom: 5px;">
        PERHITUNGAN LABA BERSIH {{ strtoupper(\Carbon\Carbon::parse($report->bulan_tahun)->isoFormat('01-t MMMM YYYY')) }}<br>
        PERTASHOP {{ $report->shop->kode }} {{ strtoupper($report->shop->alamat) }}<br>
        {{ strtoupper($companyName) }}
    </div>
    
    <table class="no-border" style="width: 100%; font-family: 'Times New Roman', Times, serif; font-size: 12px; border-collapse: collapse;">
        <tr>
            <td class="fw-bold" colspan="2">PENDAPATAN</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td width="3%">1.</td>
            <td width="55%">LABA KOTOR <span style="float:right;">.......................................................................</span></td>
            <td width="2%">=</td>
            <td width="5%">Rp</td>
            <td width="35%" class="text-right">{{ number_format($totalLabaKotor, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right fw-bold font-italic">A. Total Laba Kotor =</td>
            <td></td>
            <td class="fw-bold" style="border-top:1px solid #000 !important">Rp</td>
            <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($totalLabaKotor, 0, ',', '.') }}</td>
        </tr>
        
        <tr>
            <td class="fw-bold pt-3" colspan="2">PENGELUARAN</td>
            <td colspan="3"></td>
        </tr>
        @foreach($rincianAll as $i => $rinc)
        <tr>
            <td>{{ $i+1 }}.</td>
            <td>{{ strtoupper($rinc['keterangan'] ?? $rinc['ket'] ?? '') }} <span style="float:right;">.......................................................................</span></td>
            <td>=</td>
            <td>Rp</td>
            <td class="text-right">{{ number_format($rinc['nominal'] ?? $rinc['nom'] ?? 0, 0, ',', '.') }}{{ $loop->last ? ' +' : '' }}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="2" class="text-right fw-bold font-italic">B. Total Biaya =</td>
            <td></td>
            <td class="fw-bold" style="border-top:1px solid #000 !important">Rp</td>
            <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($totalBiaya, 0, ',', '.') }}</td>
        </tr>
        
        <tr><td colspan="5" style="height:20px;"></td></tr>
        
        <tr>
            <td colspan="2" class="text-right fw-bold font-italic">A. Total Laba Kotor =</td>
            <td></td>
            <td>Rp</td>
            <td class="text-right">{{ number_format($totalLabaKotor, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right fw-bold font-italic">B. Total Biaya =</td>
            <td></td>
            <td style="border-bottom:1px solid #000 !important">Rp</td>
            <td class="text-right" style="border-bottom:1px solid #000 !important">{{ number_format($totalBiaya, 0, ',', '.') }} -</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right fw-bold font-italic">(A-B) LABA BERSIH =</td>
            <td></td>
            <td class="fw-bold">Rp</td>
            <td class="text-right fw-bold">{{ number_format($labaBersih, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right fw-bold font-italic" style="color:red">*) Alokasi Penambahan Modal dari 10% Profit =</td>
            <td></td>
            <td class="fw-bold" style="color:red">Rp</td>
            <td class="text-right fw-bold" style="color:red; border-bottom:1px solid #000 !important">{{ number_format($alokasiModal, 0, ',', '.') }} -</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right font-italic">Saldo Laba Bersih (90%) yg Dibagi =</td>
            <td></td>
            <td class="fw-bold font-italic">Rp</td>
            <td class="text-right fw-bold font-italic">{{ number_format($labaDibagi, 0, ',', '.') }}</td>
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
            <td class="text-right fw-bold font-italic" style="border-bottom:1px solid #000 !important; border-bottom-style: double !important;">{{ number_format($labaDibagi, 0, ',', '.') }}</td>
        </tr>
    </table>
    
    <table class="no-border mt-3" style="width: 100%; font-family: 'Times New Roman', Times, serif; font-size: 12px; border-collapse: collapse;">
        <tr>
            <td class="fw-bold" colspan="6">Pembagian Laba Bersih :</td>
        </tr>
        @foreach($investors as $i => $inv)
        <tr>
            <td width="3%">{{ $i+1 }}.</td>
            <td width="35%">{{ $inv['nama'] }} <span style="float:right;">.......................................</span></td>
            <td width="7%">{{ $inv['persen'] }}%</td>
            <td width="2%">=</td>
            <td width="5%">Rp</td>
            <td width="48%" class="text-right">{{ number_format($labaDibagi * ($inv['persen']/100), 0, ',', '.') }}{{ $loop->last ? ' +' : '' }}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="4"></td>
            <td class="fw-bold font-italic" style="border-top:1px solid #000 !important; border-bottom:1px solid #000 !important; border-bottom-style: double !important;">Rp</td>
            <td class="text-right fw-bold font-italic" style="border-top:1px solid #000 !important; border-bottom:1px solid #000 !important; border-bottom-style: double !important;">{{ number_format($labaDibagi, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="no-border mt-3" style="width: 100%; font-family: 'Times New Roman', Times, serif; font-size: 12px; border-collapse: collapse;">
        <tr>
            <td class="fw-bold" colspan="5">Catatan :</td>
            <td class="text-right" style="font-size: 11px;">Checklist Transfer &#9745;</td>
        </tr>
        <tr>
            <td colspan="6">Bila Sudah Disetujui maka Laba akan segera ditransfer ke Rekening</td>
        </tr>
        @foreach($investors as $i => $inv)
        <tr>
            <td width="3%">{{ $i+1 }}.</td>
            <td width="65%">{{ ucwords(strtolower($inv['nama_bank'] ?? '')) }} {{ $inv['no_rekening'] ?? '' }} a/n {{ strtoupper($inv['atas_nama_rekening'] ?? '') }} <span style="float:right;">..........................................</span></td>
            <td width="2%">=</td>
            <td width="5%">Rp</td>
            <td width="15%" class="text-right">{{ number_format($labaDibagi * ($inv['persen']/100), 0, ',', '.') }}</td>
            <td width="10%" class="text-right"><div style="display:inline-block; width:15px; height:15px; border:1px solid #000;"></div></td>
        </tr>
        @endforeach
        <tr>
            <td colspan="6" class="fw-bold font-italic mt-1" style="color:red; padding-top: 5px;">*) Jika Laba Positif, Alokasi Modal 10% Untuk Penambahan Modal Dasar</td>
        </tr>
    </table>
    
    @include('monthly_reports.signature')
</div>
