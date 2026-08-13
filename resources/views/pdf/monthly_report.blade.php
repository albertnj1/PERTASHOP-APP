<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Bulanan — {{ $report->shop->nama ?? 'Pertashop' }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 15px; }
        .header { text-align: center; border-bottom: 2px solid #004085; padding-bottom: 10px; margin-bottom: 15px; }
        .header h2 { margin: 0 0 5px 0; color: #004085; text-transform: uppercase; }
        .header p { margin: 0; font-size: 12px; color: #555; }
        .meta-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .meta-table td { padding: 4px 8px; font-size: 11px; }
        .meta-label { font-weight: bold; width: 15%; color: #444; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10px; }
        .data-table th, .data-table td { border: 1px solid #ccc; padding: 5px 6px; text-align: right; }
        .data-table th { background-color: #f2f4f7; color: #111; font-weight: bold; text-align: center; }
        .data-table td.left { text-align: left; }
        .data-table td.center { text-align: center; }
        .total-row { background-color: #e9ecef; font-weight: bold; }
        .footer { margin-top: 20px; width: 100%; border-top: 1px solid #ddd; padding-top: 10px; font-size: 9px; color: #777; }
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 9px; color: #fff; background: #28a745; }
    </style>
</head>
<body>

    <div class="header">
        <h2>LAPORAN BULANAN PERTASHOP</h2>
        <p><strong>{{ $report->shop->nama ?? '-' }}</strong> — {{ $report->bulan_tahun }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Outlet:</td>
            <td>{{ $report->shop->nama ?? '-' }}</td>
            <td class="meta-label">Totalisator Awal:</td>
            <td>{{ number_format($report->totalisator_awal, 3, ',', '.') }} L</td>
        </tr>
        <tr>
            <td class="meta-label">Periode:</td>
            <td>{{ $report->bulan_tahun }}</td>
            <td class="meta-label">Totalisator Akhir:</td>
            <td>{{ number_format($report->totalisator_akhir, 3, ',', '.') }} L</td>
        </tr>
        <tr>
            <td class="meta-label">Alamat:</td>
            <td>{{ $report->shop->alamat ?? '-' }}</td>
            <td class="meta-label">Total Penjualan:</td>
            <td><strong>{{ number_format($report->grand_totals['total_volume'] ?? 0, 3, ',', '.') }} L</strong></td>
        </tr>
    </table>

    <h3>1. Ringkasan Penjualan & Keuangan</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Uraian</th>
                <th>Volume (Liter)</th>
                <th>Rupiah Penjualan (Rp)</th>
                <th>Total Pengeluaran (Rp)</th>
                <th>Pendapatan Bersih (Rp)</th>
                <th>Total Disetorkan (Rp)</th>
                <th>Losses / Gain (Liter)</th>
            </tr>
        </thead>
        <tbody>
            @php $gt = $report->grand_totals ?? []; @endphp
            <tr class="total-row">
                <td class="left">TOTAL BULAN INI</td>
                <td>{{ number_format($gt['total_volume'] ?? 0, 3, ',', '.') }}</td>
                <td>Rp {{ number_format($gt['total_rupiah_penjualan'] ?? 0, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($gt['total_pengeluaran'] ?? 0, 0, ',', '.') }}</td>
                <td style="color: #28a745;">Rp {{ number_format($gt['total_pendapatan'] ?? 0, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($gt['total_disetorkan'] ?? 0, 0, ',', '.') }}</td>
                <td style="color: {{ ($gt['total_losses_gain'] ?? 0) < 0 ? '#dc3545' : '#28a745' }};">
                    {{ number_format($gt['total_losses_gain'] ?? 0, 3, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <h3>2. Breakdown Rincian Setoran</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Setor Tunai</th>
                <th>Setor QRIS</th>
                <th>Setor Transfer</th>
                <th>Setor Kolektan</th>
                <th>Total Disetorkan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Rp {{ number_format($gt['total_setor_tunai'] ?? 0, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($gt['total_setor_qris'] ?? 0, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($gt['total_setor_transfer'] ?? 0, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($gt['total_setor_kolektan'] ?? 0, 0, ',', '.') }}</td>
                <td class="total-row">Rp {{ number_format($gt['total_disetorkan'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if(!empty($report->bbm_datang))
    <h3>3. Penerimaan BBM (BBM Datang)</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>No Polisi</th>
                <th>Sopir</th>
                <th>Volume Surator (L)</th>
                <th>Penerimaan Real (L)</th>
                <th>Losses / Gain (L)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report->bbm_datang as $idx => $bbm)
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td class="center">{{ $bbm['tanggal'] ?? '-' }}</td>
                <td class="center">{{ $bbm['no_polisi'] ?? '-' }}</td>
                <td class="left">{{ $bbm['sopir'] ?? '-' }}</td>
                <td>{{ number_format($bbm['volume'] ?? 0, 2, ',', '.') }}</td>
                <td>{{ number_format($bbm['penerimaan_real'] ?? 0, 2, ',', '.') }}</td>
                <td style="color: {{ ($bbm['losses_gain'] ?? 0) < 0 ? '#dc3545' : '#28a745' }};">
                    {{ number_format($bbm['losses_gain'] ?? 0, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: left;">Dokumen resmi sistem SAL Pertashop — Digenerate pada: {{ now()->translatedFormat('d F Y H:i') }}</td>
                <td style="text-align: right;">Halaman 1 dari 1</td>
            </tr>
        </table>
    </div>

</body>
</html>
