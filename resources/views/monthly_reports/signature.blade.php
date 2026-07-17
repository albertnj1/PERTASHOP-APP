<div class="signature-section mt-5" style="page-break-inside: avoid; font-family: 'Times New Roman', Times, serif; font-size: 11px; margin-top: 30px;">
    <table style="width: 100%; border: none;">
        <tr>
            <td style="text-align: center; vertical-align: top;">
                <div>Disetujui Oleh,</div>
                <br><br><br><br><br>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="text-align: center; width: 16%;">{{ $companyName }}</td>
                        @foreach($investors as $inv)
                            <td style="text-align: center; width: 16%;">{{ ucwords(strtolower($inv['nama'] ?? $inv['atas_nama_rekening'] ?? '')) }}</td>
                        @endforeach
                    </tr>
                </table>
            </td>
            <td style="text-align: center; vertical-align: top; width: 25%;">
                <div>Banyumas, {{ \Carbon\Carbon::parse($report->bulan_tahun)->endOfMonth()->isoFormat('D MMMM YYYY') }}</div>
                <div>Dibuat Oleh,</div>
                <br><br><br><br><br>
                <div>Admin Pertashop</div>
            </td>
        </tr>
    </table>
</div>
