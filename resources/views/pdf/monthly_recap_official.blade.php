<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Bulanan Resmi — {{ $shop->nama ?? 'Pertashop' }} — {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 8mm 8mm 8mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 7.5px;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.25;
        }
        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 5px;
            margin-bottom: 6px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-title h2 {
            margin: 0 0 2px 0;
            color: #1e3a8a;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 800;
        }
        .header-title p {
            margin: 0;
            font-size: 8.5px;
            color: #475569;
        }
        .badge-locked {
            background-color: #15803d;
            color: #ffffff;
            font-weight: bold;
            padding: 3px 7px;
            border-radius: 4px;
            font-size: 8.5px;
            display: inline-block;
        }
        .summary-boxes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .summary-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            text-align: center;
        }
        .summary-box .label {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }
        .summary-box .value {
            font-size: 9.5px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 1px;
        }
        table.recap-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
        }
        table.recap-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            border: 1px solid #0f172a;
            padding: 3px 2px;
            text-align: center;
            font-weight: bold;
        }
        table.recap-table td {
            border: 1px solid #cbd5e1;
            padding: 2px 2px;
            vertical-align: middle;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .row-sunday { background-color: #fffbeb; }
        .row-total {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
        }
        .row-extra {
            background-color: #f1f5f9;
            font-weight: bold;
        }
        .text-danger { color: #dc2626; font-weight: bold; }
        .text-success { color: #16a34a; font-weight: bold; }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            font-size: 7.5px;
        }
        .sig-box {
            height: 35px;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <table>
            <tr>
                <td style="width: 65%;" class="header-title">
                    <h2>LAPORAN REKAPITULASI BULANAN OPERASIONAL PERTASHOP</h2>
                    <p>
                        <strong>{{ $shop->nama }}</strong> (Kode: {{ $shop->kode ?: '-' }}) • 
                        Periode: <strong>{{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}</strong> •
                        Badan Usaha: {{ $shop->corporation?->nama ?? 'PT Pertashop' }}
                    </p>
                </td>
                <td style="width: 35%; text-align: right; vertical-align: top;">
                    <div class="badge-locked">
                        ✓ TERVALIDASI &amp; TERKUNCI RESMI
                    </div>
                    <div style="font-size: 7px; color: #475569; margin-top: 2px;">
                        Divalidasi oleh: <strong>{{ $periodLock?->locker?->name ?? 'Super Admin' }}</strong><br>
                        Tanggal: {{ $periodLock?->locked_at ? \Carbon\Carbon::parse($periodLock->locked_at)->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- KPI SUMMARY BOXES --}}
    @php
        $tot = $recapData['summary']['row_total_35'];
        $avg = $recapData['summary']['row_averages_closing_36_39'];
    @endphp
    <table class="summary-boxes">
        <tr>
            <td class="summary-box">
                <div class="label">Total Penjualan Aktual</div>
                <div class="value">{{ number_format($tot['L_penjualan_aktual_vol'], 2, ',', '.') }} L</div>
                <div style="font-size: 7px; color: #15803d; font-weight: bold;">Rp {{ number_format($tot['M_penjualan_aktual_rp'], 0, ',', '.') }}</div>
            </td>
            <td class="summary-box">
                <div class="label">Total Terima BBM</div>
                <div class="value">{{ number_format($tot['P_terima_bbm'], 0, ',', '.') }} L</div>
                <div style="font-size: 7px; color: #475569;">Beli: Rp {{ number_format($recapData['parameters']['harga_beli_pertamina'], 2, ',', '.') }}/L</div>
            </td>
            <td class="summary-box">
                <div class="label">Losses / Gain Akumulasi</div>
                <div class="value {{ $tot['T_losses_gain_liter'] < 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($tot['T_losses_gain_liter'], 3, ',', '.') }} L
                </div>
                <div style="font-size: 7px;" class="{{ $tot['U_losses_gain_rp'] < 0 ? 'text-danger' : 'text-success' }}">
                    Rp {{ number_format($tot['U_losses_gain_rp'], 0, ',', '.') }}
                </div>
            </td>
            <td class="summary-box">
                <div class="label">Total Pengeluaran</div>
                <div class="value" style="color: #b91c1c;">
                    Rp {{ number_format($tot['AG_total_pengeluaran'], 0, ',', '.') }}
                </div>
                <div style="font-size: 7px; color: #64748b;">(Bongkar, TF, ATK, Listrik, dll)</div>
            </td>
            <td class="summary-box">
                <div class="label">Total Disetorkan</div>
                <div class="value" style="color: #1e40af;">
                    Rp {{ number_format($avg['AL36_total_disetor'] ?? $tot['AL_setoran_bri'], 0, ',', '.') }}
                </div>
                <div style="font-size: 7px; color: #64748b;">Uang Belum Disetor (AP35): Rp {{ number_format($tot['AP_total_uang_belum_disetor'], 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    {{-- TABEL REKAP BULANAN --}}
    <table class="recap-table">
        <thead>
            <tr>
                <th style="width: 15px;">Tgl</th>
                <th>Tanggal</th>
                <th>Tot Awal</th>
                <th>Tot Akhir</th>
                <th>Test (L)</th>
                <th>Kirim (L)</th>
                <th>Jual (L)</th>
                <th>Jual (Rp)</th>
                <th>Stok Awal</th>
                <th>Terima</th>
                <th>Stok Teo</th>
                <th>cm</th>
                <th>Stok Aktual</th>
                <th>Losses(L)</th>
                <th>Losses(Rp)</th>
                <th>Pengeluaran</th>
                <th>Pendapatan-Biaya</th>
                <th>Setor (Rp)</th>
                <th>Selisih Setor</th>
                <th>Uang Belum Disetor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recapData['rows'] as $r)
            @php
                $isSunday = str_contains($r['C_tanggal'] ?? '', 'Minggu');
            @endphp
            <tr class="{{ $isSunday ? 'row-sunday' : '' }}">
                <td class="text-center font-weight-bold">{{ $r['A_nomor_hari'] }}</td>
                <td class="text-left">{{ $r['C_tanggal'] }}</td>
                <td class="text-right">{{ number_format($r['D_totalisator_awal'], 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($r['E_totalisator_akhir'], 2, ',', '.') }}</td>
                <td class="text-right">{{ $r['H_test_pump_vol'] > 0 ? number_format($r['H_test_pump_vol'], 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $r['J_bbm_keluar_lain_vol'] > 0 ? number_format($r['J_bbm_keluar_lain_vol'], 0, ',', '.') : '-' }}</td>
                <td class="text-right font-weight-bold">{{ number_format($r['L_penjualan_aktual_vol'], 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($r['M_penjualan_aktual_rp'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($r['N_stok_awal'], 2, ',', '.') }}</td>
                <td class="text-right">{{ $r['P_terima_bbm'] > 0 ? number_format($r['P_terima_bbm'], 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ number_format($r['Q_stok_teoritis'], 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($r['R_stok_aktual_cm'], 1, ',', '.') }}</td>
                <td class="text-right">{{ number_format($r['S_stok_aktual_vol'], 2, ',', '.') }}</td>
                <td class="text-right {{ $r['T_losses_gain_liter'] < 0 ? 'text-danger' : '' }}">{{ number_format($r['T_losses_gain_liter'], 2, ',', '.') }}</td>
                <td class="text-right {{ $r['U_losses_gain_rp'] < 0 ? 'text-danger' : '' }}">{{ number_format($r['U_losses_gain_rp'], 0, ',', '.') }}</td>
                <td class="text-right">{{ $r['AG_total_pengeluaran'] > 0 ? number_format($r['AG_total_pengeluaran'], 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ number_format($r['AJ_pendapatan_minus_pengeluaran'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($r['AL_setoran_bri'], 0, ',', '.') }}</td>
                <td class="text-right {{ $r['AO_selisih_setoran'] < 0 ? 'text-danger' : '' }}">{{ number_format($r['AO_selisih_setoran'], 0, ',', '.') }}</td>
                <td class="text-right font-weight-bold {{ $r['AP_total_uang_belum_disetor'] < 0 ? 'text-danger' : '' }}">{{ number_format($r['AP_total_uang_belum_disetor'], 0, ',', '.') }}</td>
            </tr>
            @endforeach

            {{-- BARIS 35: TOTAL --}}
            <tr class="row-total">
                <td colspan="6" class="text-center">TOTAL BULAN INI (BARIS 35)</td>
                <td class="text-right">{{ number_format($tot['L_penjualan_aktual_vol'], 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($tot['M_penjualan_aktual_rp'], 0, ',', '.') }}</td>
                <td></td>
                <td class="text-right">{{ number_format($tot['P_terima_bbm'], 0, ',', '.') }}</td>
                <td colspan="3"></td>
                <td class="text-right {{ $tot['T_losses_gain_liter'] < 0 ? 'text-danger' : '' }}">{{ number_format($tot['T_losses_gain_liter'], 2, ',', '.') }}</td>
                <td class="text-right {{ $tot['U_losses_gain_rp'] < 0 ? 'text-danger' : '' }}">{{ number_format($tot['U_losses_gain_rp'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($tot['AG_total_pengeluaran'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($tot['AJ_pendapatan_minus_pengeluaran'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($tot['AL_setoran_bri'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($tot['AO_selisih_setoran'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($tot['AP_total_uang_belum_disetor'], 0, ',', '.') }}</td>
            </tr>

            {{-- BARIS 36: RATA-RATA HARIAN --}}
            <tr class="row-extra">
                <td colspan="6" class="text-center">RATA-RATA HARIAN (BARIS 36)</td>
                <td class="text-right">{{ number_format($avg['F36_avg_liter'] ?? 0, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($avg['G36_avg_rupiah'] ?? 0, 0, ',', '.') }}</td>
                <td colspan="10"></td>
                <td class="text-right" colspan="2">Total Setor (AL36):</td>
                <td class="text-right font-weight-bold">Rp {{ number_format($avg['AL36_total_disetor'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TANDA TANGAN / OTORISASI RESMI --}}
    <table class="signature-table">
        <tr>
            <td>
                <strong>Dibuat Oleh / Operator Shift</strong><br>
                Pertashop {{ $shop->nama }}
                <div class="sig-box"></div>
                ___________________________<br>
                Petugas Operator
            </td>
            <td>
                <strong>Dicek &amp; Diverifikasi</strong><br>
                Admin Operasional
                <div class="sig-box"></div>
                ___________________________<br>
                Admin Wilayah
            </td>
            <td>
                <strong>Disetujui &amp; Dikunci Resmi</strong><br>
                Super Admin / Manajemen
                <div class="sig-box" style="display: flex; align-items: center; justify-content: center;">
                    <div style="border: 1px dashed #15803d; color: #15803d; font-size: 7.5px; padding: 4px 6px; border-radius: 4px;">
                        ✓ SECURE DIGITAL SIGNATURE<br>
                        {{ $periodLock?->locked_at ? \Carbon\Carbon::parse($periodLock->locked_at)->format('d M Y H:i') : now()->format('d M Y') }}
                    </div>
                </div>
                <strong>{{ $periodLock?->locker?->name ?? 'Super Admin' }}</strong><br>
                Direktur / Owner
            </td>
        </tr>
    </table>

</body>
</html>
