<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Hasil Investasi — {{ $investor->name ?? 'Investor' }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #1e40af; padding-bottom: 12px; margin-bottom: 20px; }
        .header h2 { margin: 0 0 4px 0; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px; }
        .header p { margin: 0; font-size: 12px; color: #64748b; }
        .card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 15px; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 4px 6px; font-size: 11px; }
        .label { font-weight: bold; color: #475569; width: 20%; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        .data-table th, .data-table td { border: 1px solid #cbd5e1; padding: 7px 10px; text-align: right; }
        .data-table th { background-color: #1e293b; color: #fff; text-align: center; font-size: 10px; text-transform: uppercase; }
        .data-table td.left { text-align: left; }
        .data-table td.center { text-align: center; }
        .highlight-row { background-color: #f1f5f9; font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 9px; color: #94a3b8; width: 100%; }
    </style>
</head>
<body>

    <div class="header">
        <h2>RINGKASAN HASIL INVESTASI & PROFIT SHARING</h2>
        <p>Sistem Informasi Pengelolaan Pertashop</p>
    </div>

    <div class="card">
        <table class="meta-table">
            <tr>
                <td class="label">Nama Investor:</td>
                <td><strong>{{ $investor->name }}</strong> ({{ $investor->nama_lengkap_gelar ?? $investor->name }})</td>
                <td class="label">Tanggal Cetak:</td>
                <td>{{ now()->translatedFormat('d F Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">No HP:</td>
                <td>{{ $investor->no_hp ?? '-' }}</td>
                <td class="label">Bank Rekening:</td>
                <td>{{ $investor->nama_bank ?? '-' }} — {{ $investor->no_rekening ?? '-' }} (a.n {{ $investor->atas_nama_rekening ?? '-' }})</td>
            </tr>
        </table>
    </div>

    <h3>1. Daftar Toko & Porsi Investasi</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pertashop</th>
                <th>Modal Pertashop (Rp)</th>
                <th>Nominal Investasi (Rp)</th>
                <th>Persentase Bagi Hasil (%)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalNominal = 0; @endphp
            @forelse($investor->shops as $idx => $shop)
            @php 
                $nom = $shop->pivot->nominal ?? 0;
                $pct = $shop->pivot->persentase ?? 0;
                $totalNominal += $nom;
            @endphp
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td class="left"><strong>{{ $shop->nama }}</strong></td>
                <td>Rp {{ number_format($shop->modal_awal ?? 0, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($nom, 0, ',', '.') }}</td>
                <td class="center"><strong>{{ number_format($pct, 2, ',', '.') }}%</strong></td>
            </tr>
            @empty
            <tr><td colspan="5" class="center">Belum ada data investasi toko.</td></tr>
            @endforelse
            <tr class="highlight-row">
                <td colspan="3" class="left">TOTAL INVESTASI</td>
                <td>Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                <td class="center">–</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: left;">Dokumen resmi SAL Pertashop — Harap simpan sebagai bukti cetak bagi hasil.</td>
                <td style="text-align: right;">Hal 1 / 1</td>
            </tr>
        </table>
    </div>

</body>
</html>
