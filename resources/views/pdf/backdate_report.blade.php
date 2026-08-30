@php
    // Adapter mapping jika data dipassing sebagai $summary, $shop, $period dari Controller
    if (isset($summary)) {
        $hal1 = $summary['hal1'] ?? [];
        $hal2 = $summary['hal2'] ?? [];
        $hal3 = $summary['hal3'] ?? [];
        $hal4 = $summary['hal4'] ?? [];
        $segments = $hal1['segments'] ?? [];

        $outlet_name = $outlet_name ?? ($shop->nama ?? 'Pertashop');
        $outlet_code = $outlet_code ?? ($shop->kode ?? '-');
        
        $pObj = isset($period) ? \Carbon\Carbon::parse($period . '-01') : now();
        $period = $periodFormatted ?? $pObj->translatedFormat('F Y');

        $hpp = $hpp ?? (!empty($segments) ? ($segments[0]['harga_beli'] ?? 11376.29) : ($hal1['final_harga_beli'] ?? 11376.29));
        $harga_jual = $harga_jual ?? (!empty($segments) ? ($segments[0]['harga_jual'] ?? 12200) : 12200);
        $total_liter = $total_liter ?? ($hal1['total_liter_terjual'] ?? 0);
        $omset_harian = $omset_harian ?? ($hal1['rata_rata_omset_harian'] ?? 0);
        $laba_kotor = $laba_kotor ?? ($hal1['grand_total_laba_kotor'] ?? 0);

        if (!isset($batches) && !empty($segments)) {
            $batches = [];
            foreach ($segments as $s) {
                $batches[] = [
                    'hpp' => $s['harga_beli'] ?? $hpp,
                    'jual' => $s['harga_jual'] ?? $harga_jual,
                    'stok_awal' => $s['stok_awal'] ?? 0,
                    'do_masuk' => $s['bbm_datang'] ?? 0,
                    'liter_terjual' => $s['jumlah_penjualan'] ?? 0,
                    'stok_akhir' => $s['stok_akhir_fisik'] ?? 0,
                    'laba_kotor' => $s['laba_kotor'] ?? 0,
                ];
            }
        }

        $total_beban = $total_beban ?? ($hal2['total_biaya'] ?? 0);
        $laba_bersih = $laba_bersih ?? ($hal2['laba_bersih'] ?? 0);
        $alokasi_modal = $alokasi_modal ?? ($hal2['alokasi_penambahan_modal'] ?? 0);
        $saldo_dibagi = $saldo_dibagi ?? ($hal2['total_saldo_laba_dibagi'] ?? 0);

        $expDetails = $hal2['pengeluaran_details'] ?? [];
        $beban_gaji = $beban_gaji ?? ($expDetails['gaji_operator'] ?? 0);
        $beban_admin = $beban_admin ?? ($expDetails['gaji_admin'] ?? 0);
        $beban_curah = $beban_curah ?? ($expDetails['biaya_curah'] ?? 0);
        $beban_transfer = $beban_transfer ?? ($expDetails['biaya_tf'] ?? 0);
        $beban_listrik = $beban_listrik ?? ($expDetails['listrik'] ?? 0);
        $beban_air = $beban_air ?? ($expDetails['air'] ?? 0);
        $beban_cashback = $beban_cashback ?? ($expDetails['cashback'] ?? 0);
        $beban_internet = $beban_internet ?? ($expDetails['internet'] ?? 0);
        $beban_atk = $beban_atk ?? ($expDetails['atk'] ?? 0);
        $beban_lain = $beban_lain ?? ($expDetails['lain_lain'] ?? 0);

        if (!isset($investors) && !empty($hal2['investor_distributions'])) {
            $investors = [];
            foreach ($hal2['investor_distributions'] as $inv) {
                $investors[] = [
                    'name' => $inv['nama'] ?? 'Investor',
                    'percentage' => $inv['persen'] ?? 0,
                    'amount' => $inv['nominal'] ?? 0,
                    'bank' => $inv['nama_bank'] ?? 'Mandiri',
                    'account_number' => $inv['no_rekening'] ?? '-',
                ];
            }
        }

        $modal_awal = $modal_awal ?? ($hal3['saldo_awal_modal'] ?? ($shop->modal_awal ?? 60000000));
        $aset_do = $aset_do ?? ($hal3['do_di_pertamina'] ?? 0);
        $aset_bank = $aset_bank ?? ($hal3['uang_di_bank'] ?? 0);
        $aset_kas = $aset_kas ?? ($hal3['kas_kecil'] ?? 0);
        $aset_stok = $aset_stok ?? ($hal3['sisa_stok_pertashop_rp'] ?? 0);
        $aset_belum_setor = $aset_belum_setor ?? ($hal3['hasil_belum_disetor'] ?? 0);
        $aset_piutang = $aset_piutang ?? ($hal3['piutang'] ?? 0);
        $subtotal_aset = $subtotal_aset ?? ($hal3['subtotal_a'] ?? $modal_awal);

        $bunga_bank = $bunga_bank ?? ($hal3['bunga_bank'] ?? 0);
        $pajak_bank = $pajak_bank ?? ($hal3['pajak_bank'] ?? 0);
        $modal_akhir = $modal_akhir ?? ($hal3['total_saldo_akhir_modal'] ?? ($modal_awal + $alokasi_modal));

        if (!isset($modal_history) && !empty($hal4['capital_recaps'])) {
            $modal_history = [];
            $mNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
            foreach ($hal4['capital_recaps'] as $rec) {
                $yr = intval($rec['tahun'] ?? 0);
                $mo = intval($rec['bulan'] ?? 0);
                if ($yr >= 2020 && $yr <= 2100 && $mo >= 1 && $mo <= 12) {
                    $modal_history[] = [
                        'period' => ($mNames[$mo] ?? '') . ' ' . $yr,
                        'modal_awal' => $rec['nilai_modal_awal'] ?? 0,
                        'profit_10' => $rec['penambahan_keuntungan'] ?? 0,
                        'tax_fee' => $rec['penyusutan_pajak_bank'] ?? 0,
                        'modal_akhir' => $rec['posisi_akhir_modal'] ?? 0,
                        'liter_equivalent' => $rec['konversi_liter'] ?? 0,
                    ];
                }
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Resmi - {{ $outlet_name ?? ($data['outlet_name'] ?? 'Pertashop') }}</title>
    <style>
        /* Konfigurasi Ukuran Kertas & Margin */
        @page {
            margin: 14mm 16mm;
            size: A4 portrait; /* Ganti ke landscape jika data kolom sangat banyak */
        }

        * {
            box-sizing: border-box;
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        body {
            font-size: 8.5pt;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* Utility & Spacing */
        .page-break {
            page-break-after: always;
        }
        .avoid-break {
            page-break-inside: avoid;
        }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 14px; }
        .mb-4 { margin-bottom: 20px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-muted { color: #64748b; }
        .text-primary { color: #0f4c81; }

        /* Header / Kop Dokumen */
        .header-container {
            border-bottom: 2px solid #0f4c81;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .company-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f4c81;
            margin: 0;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 10pt;
            font-weight: bold;
            color: #334155;
            margin: 3px 0 0 0;
        }
        .outlet-info {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }

        /* Grid Cards Summary (2 Kolom) */
        .grid-2 {
            width: 100%;
            margin-bottom: 12px;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            background-color: #ffffff;
        }
        .card-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .card-highlight {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }

        /* Tabel Standar Korporat */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 10px;
        }
        th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            padding: 5px 7px;
            text-align: left;
            border: 1px solid #1e293b;
            font-size: 7.5pt;
            text-transform: uppercase;
        }
        td {
            padding: 4.5px 7px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .table-subtotal td {
            background-color: #f1f5f9;
            font-weight: bold;
        }
        .table-total td {
            background-color: #e2e8f0;
            font-weight: bold;
            font-size: 8.5pt;
        }

        /* Section Title */
        .section-header {
            font-size: 9pt;
            font-weight: bold;
            color: #0f4c81;
            border-left: 3px solid #0f4c81;
            padding-left: 6px;
            margin: 12px 0 6px 0;
            text-transform: uppercase;
        }

        /* Area Tanda Tangan */
        .signature-table {
            width: 100%;
            margin-top: 15px;
            border: none;
        }
        .signature-table td {
            border: none;
            background: transparent !important;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .sign-space {
            height: 55px; /* Ruang untuk tanda tangan & cap */
        }
        .sign-line {
            border-bottom: 1px solid #334155;
            font-weight: bold;
            padding-bottom: 2px;
            display: inline-block;
            min-width: 150px;
        }

        /* Footer Info */
        .doc-footer {
            font-size: 7pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            margin-top: 15px;
        }
    </style>
</head>
<body>


    {{-- HALAMAN 1: OPERASIONAL STOK, PENJUALAN & LABA KOTOR               --}}
 
    <div class="header-container">
        <table style="margin: 0; border: none;">
            <tr>
                <td style="border: none; padding: 0;" class="text-left">
                    <h1 class="company-title">PT. SERAYU AGUNG MANDIRI</h1>
                    <div class="report-subtitle">LAPORAN OPERASIONAL STOK & PENJUALAN</div>
                    <div class="outlet-info">{{ $outlet_name ?? 'Pertashop' }} (Kode: {{ $outlet_code ?? '-' }}) • Periode: {{ $period ?? 'Bulan Berjalan' }}</div>
                </td>
                <td style="border: none; padding: 0; width: 140px;" class="text-right">
                    <div style="display: inline-block; background: #0f4c81; color: #fff; padding: 4px 8px; border-radius: 3px; font-weight: bold; font-size: 8pt;">
                        HALAMAN 1
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Parameter & Performa Penjualan -->
    <table class="grid-2">
        <tr>
            <td style="border: none; padding: 0 5px 0 0; width: 50%; vertical-align: top;">
                <div class="card">
                    <div class="card-title">Parameter Harga BBM (Pertamax)</div>
                    <table style="margin: 0;">
                        <tr>
                            <td style="border: none; padding: 2px 0;">Harga Beli (HPP) Terakhir</td>
                            <td style="border: none; padding: 2px 0;" class="text-right font-bold">Rp {{ number_format($hpp ?? 0, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 0;">Harga Jual Eceran</td>
                            <td style="border: none; padding: 2px 0;" class="text-right font-bold">Rp {{ number_format($harga_jual ?? 0, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 0; color: #166534;">Margin per Liter</td>
                            <td style="border: none; padding: 2px 0; color: #166534;" class="text-right font-bold">Rp {{ number_format(($harga_jual ?? 0) - ($hpp ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="border: none; padding: 0 0 0 5px; width: 50%; vertical-align: top;">
                <div class="card card-highlight">
                    <div class="card-title" style="color: #166534;">Volume & Performa Penjualan</div>
                    <table style="margin: 0;">
                        <tr>
                            <td style="border: none; padding: 2px 0;">Total Volume Terjual</td>
                            <td style="border: none; padding: 2px 0;" class="text-right font-bold">{{ number_format($total_liter ?? 0, 2, ',', '.') }} ℓ</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 0;">Rata-rata Omset Harian</td>
                            <td style="border: none; padding: 2px 0;" class="text-right font-bold">{{ number_format($omset_harian ?? 0, 2, ',', '.') }} ℓ/hari</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 0;">Grand Total Laba Kotor</td>
                            <td style="border: none; padding: 2px 0; color: #166534;" class="text-right font-bold">Rp {{ number_format($laba_kotor ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Rincian Batch Pembelian & Penjualan -->
    <div class="section-header">Rincian Perhitungan Batch Penjualan</div>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Komponen Alur</th>
                <th style="width: 25%;">Keterangan / Meteran</th>
                <th style="width: 25%; text-align: right;">Volume (Liter)</th>
                <th style="width: 25%; text-align: right;">Valuasi / Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($batches) && count($batches) > 0)
                @foreach($batches as $index => $b)
                    <tr class="table-subtotal">
                        <td colspan="4">BATCH PEMBELIAN #{{ $index + 1 }} (HPP: Rp {{ number_format($b['hpp'] ?? 0, 2, ',', '.') }} | Jual: Rp {{ number_format($b['jual'] ?? 0, 2, ',', '.') }})</td>
                    </tr>
                    <tr>
                        <td>a. Stok Awal Tangki</td>
                        <td>Fisik Awal Periode</td>
                        <td class="text-right">{{ number_format($b['stok_awal'] ?? 0, 2, ',', '.') }} ℓ</td>
                        <td class="text-right">Rp {{ number_format(($b['stok_awal'] ?? 0) * ($b['hpp'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>b. BBM Datang (DO)</td>
                        <td>Penerimaan Tangki Pertamina</td>
                        <td class="text-right">{{ number_format($b['do_masuk'] ?? 0, 2, ',', '.') }} ℓ</td>
                        <td class="text-right">Rp {{ number_format(($b['do_masuk'] ?? 0) * ($b['hpp'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>c. Penjualan Bersih</td>
                        <td>Totalisator - Test Pump</td>
                        <td class="text-right font-bold">{{ number_format($b['liter_terjual'] ?? 0, 2, ',', '.') }} ℓ</td>
                        <td class="text-right font-bold">Rp {{ number_format(($b['liter_terjual'] ?? 0) * ($b['jual'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>d. Sisa Stok Akhir</td>
                        <td>Fisik Akhir / Sisa Tangki</td>
                        <td class="text-right">{{ number_format($b['stok_akhir'] ?? 0, 2, ',', '.') }} ℓ</td>
                        <td class="text-right">Rp {{ number_format(($b['stok_akhir'] ?? 0) * ($b['hpp'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                    <tr style="background: #f0fdf4;">
                        <td colspan="3" class="font-bold text-right" style="color: #166534;">Subtotal Laba Kotor Batch #{{ $index + 1 }}</td>
                        <td class="text-right font-bold" style="color: #166534;">Rp {{ number_format($b['laba_kotor'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>Stok Awal + DO Masuk</td>
                    <td>Penerimaan BBM</td>
                    <td class="text-right">{{ number_format($total_liter ?? 0, 2, ',', '.') }} ℓ</td>
                    <td class="text-right">Rp {{ number_format(($total_liter ?? 0) * ($hpp ?? 0), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Penjualan Bersih</td>
                    <td>Totalisator Dispenser</td>
                    <td class="text-right font-bold">{{ number_format($total_liter ?? 0, 2, ',', '.') }} ℓ</td>
                    <td class="text-right font-bold">Rp {{ number_format(($total_liter ?? 0) * ($harga_jual ?? 0), 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="table-total">
                <td colspan="3" class="text-right">GRAND TOTAL LABA KOTOR OPERASIONAL</td>
                <td class="text-right text-primary">Rp {{ number_format($laba_kotor ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="doc-footer">
        Dokumen ini dibuat otomatis oleh Sistem Informasi Pertashop • Dicetak pada {{ date('d/m/Y H:i') }} WIB
    </div>

    <div class="page-break"></div>
 
    {{-- HALAMAN 2: BEBAN OPERASIONAL, LABA BERSIH & PROFIT SHARING        --}}
 
    <div class="header-container">
        <table style="margin: 0; border: none;">
            <tr>
                <td style="border: none; padding: 0;" class="text-left">
                    <h1 class="company-title">PT. SERAYU AGUNG MANDIRI</h1>
                    <div class="report-subtitle">LAPORAN BEBAN OPERASIONAL & PROFIT SHARING</div>
                    <div class="outlet-info">{{ $outlet_name ?? 'Pertashop' }} • Periode: {{ $period ?? 'Bulan Berjalan' }}</div>
                </td>
                <td style="border: none; padding: 0; width: 140px;" class="text-right">
                    <div style="display: inline-block; background: #0f4c81; color: #fff; padding: 4px 8px; border-radius: 3px; font-weight: bold; font-size: 8pt;">
                        HALAMAN 2
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Ringkasan Laba Bersih Top Card -->
    <table class="grid-2">
        <tr>
            <td style="border: none; padding: 0 5px 0 0; width: 33.3%;">
                <div class="card">
                    <div class="card-title">A. Total Laba Kotor</div>
                    <div class="font-bold" style="font-size: 10pt; color: #0f4c81;">Rp {{ number_format($laba_kotor ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="border: none; padding: 0 5px; width: 33.3%;">
                <div class="card">
                    <div class="card-title">B. Total Beban Biaya</div>
                    <div class="font-bold" style="font-size: 10pt; color: #b91c1c;">Rp {{ number_format($total_beban ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="border: none; padding: 0 0 0 5px; width: 33.3%;">
                <div class="card card-highlight">
                    <div class="card-title" style="color: #166534;">C. Laba Bersih (A - B)</div>
                    <div class="font-bold" style="font-size: 10pt; color: #166534;">Rp {{ number_format($laba_bersih ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tabel 11 Pos Beban Operasional -->
    <div class="section-header">Rincian Pos Beban Operasional Bulanan</div>
    <table>
        <thead>
            <tr>
                <th style="width: 8%; text-align: center;">No</th>
                <th style="width: 62%;">Pos Pengeluaran Operasional</th>
                <th style="width: 30%; text-align: right;">Nominal Biaya (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $expenses_list = $expenses ?? [
                    ['name' => 'Gaji Operator', 'amount' => $beban_gaji ?? 0],
                    ['name' => 'Gaji Admin', 'amount' => $beban_admin ?? 0],
                    ['name' => 'Ongkos Bongkar / Biaya Curah', 'amount' => $beban_curah ?? 0],
                    ['name' => 'Biaya Transfer Bank & Adm', 'amount' => $beban_transfer ?? 0],
                    ['name' => 'Tagihan Listrik / PLN', 'amount' => $beban_listrik ?? 0],
                    ['name' => 'Air Bersih / PDAM', 'amount' => $beban_air ?? 0],
                    ['name' => 'Cashback Pengecer BBM', 'amount' => $beban_cashback ?? 0],
                    ['name' => 'Biaya Internet & CCTV', 'amount' => $beban_internet ?? 0],
                    ['name' => 'Fotocopy, ATK & Perlengkapan', 'amount' => $beban_atk ?? 0],
                    ['name' => 'Biaya Operasional Lain-lain', 'amount' => $beban_lain ?? 0],
                ];
            @endphp
            @foreach($expenses_list as $idx => $exp)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $exp['name'] }}</td>
                    <td class="text-right">Rp {{ number_format($exp['amount'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="table-total">
                <td colspan="2" class="text-right">TOTAL BEBAN OPERASIONAL (B)</td>
                <td class="text-right" style="color: #b91c1c;">Rp {{ number_format($total_beban ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Alokasi Modal & Tabel Bagi Hasil Investor -->
    <div class="section-header">Distribusi Profit Sharing & Alokasi Modal</div>
    <div class="card mb-3" style="background: #f8fafc;">
        <table style="margin: 0; font-size: 8pt;">
            <tr>
                <td style="border: none; padding: 3px 0;">• Cadangan Penambahan Modal Dasar (10%)</td>
                <td style="border: none; padding: 3px 0;" class="text-right font-bold">Rp {{ number_format($alokasi_modal ?? (($laba_bersih ?? 0) * 0.1), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="border: none; padding: 3px 0; color: #166534;">• Saldo Laba Bersih yang Siap Dibagi ke Investor (90%)</td>
                <td style="border: none; padding: 3px 0; color: #166534;" class="text-right font-bold">Rp {{ number_format($saldo_dibagi ?? (($laba_bersih ?? 0) * 0.9), 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th>
                <th style="width: 25%;">Nama Pemegang Saham</th>
                <th style="width: 10%; text-align: center;">Porsi</th>
                <th style="width: 20%; text-align: right;">Hak Bagi Hasil (Rp)</th>
                <th style="width: 25%;">Bank & No. Rekening</th>
                <th style="width: 15%; text-align: center;">Checklist</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($investors) && count($investors) > 0)
                @foreach($investors as $i => $inv)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="font-bold">{{ $inv['name'] }}</td>
                        <td class="text-center">{{ $inv['percentage'] }}%</td>
                        <td class="text-right font-bold">Rp {{ number_format($inv['amount'] ?? 0, 0, ',', '.') }}</td>
                        <td>{{ $inv['bank'] ?? 'Bank' }} - {{ $inv['account_number'] ?? '-' }}</td>
                        <td class="text-center">[ &nbsp; &nbsp; ] Transfer</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td class="text-center">1</td>
                    <td class="font-bold">PT. SAM (Pengelola)</td>
                    <td class="text-center">100%</td>
                    <td class="text-right font-bold">Rp {{ number_format($saldo_dibagi ?? 0, 0, ',', '.') }}</td>
                    <td>Rekening Operasional SAM</td>
                    <td class="text-center">[ &nbsp; &nbsp; ] Transfer</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Area Tanda Tangan -->
    <div class="avoid-break" style="margin-top: 20px;">
        <table class="signature-table">
            <tr>
                <td style="width: 33%;">
                    <div>Dibuat Oleh,</div>
                    <div class="text-muted" style="font-size: 7.5pt;">Admin Operasional</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">{{ $admin_name ?? 'Dwi Yuliarto' }}</div>
                </td>
                <td style="width: 33%;">
                    <div>Mengetahui,</div>
                    <div class="text-muted" style="font-size: 7.5pt;">Direktur PT. SAM</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">Adlai Budiarto T.</div>
                </td>
                <td style="width: 33%;">
                    <div>Menyetujui,</div>
                    <div class="text-muted" style="font-size: 7.5pt;">Perwakilan Investor</div>
                    <div class="sign-space"></div>
                    <div class="sign-line">( .................................... )</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-footer">
        Dokumen ini dibuat otomatis oleh Sistem Informasi Pertashop • Dicetak pada {{ date('d/m/Y H:i') }} WIB
    </div>

    <div class="page-break"></div>

   
    {{-- HALAMAN 3: POSISI MODAL KERJA & REKAP HISTORIS                   --}}
 
    <div class="header-container">
        <table style="margin: 0; border: none;">
            <tr>
                <td style="border: none; padding: 0;" class="text-left">
                    <h1 class="company-title">PT. SERAYU AGUNG MANDIRI</h1>
                    <div class="report-subtitle">POSISI MODAL KERJA & HISTORI PERTUMBUHAN</div>
                    <div class="outlet-info">{{ $outlet_name ?? 'Pertashop' }} • Posisi Periode: {{ $period ?? 'Bulan Berjalan' }}</div>
                </td>
                <td style="border: none; padding: 0; width: 140px;" class="text-right">
                    <div style="display: inline-block; background: #0f4c81; color: #fff; padding: 4px 8px; border-radius: 3px; font-weight: bold; font-size: 8pt;">
                        HALAMAN 3
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Layout 2 Kolom Modal Kerja -->
    <table class="grid-2">
        <tr>
            <!-- Kolom Kiri: Rincian Aset Lancar -->
            <td style="border: none; padding: 0 6px 0 0; width: 50%; vertical-align: top;">
                <div class="section-header" style="margin-top: 0;">A. Posisi Aset Lancar (Likuiditas)</div>
                <table>
                    <thead>
                        <tr>
                            <th>Komponen Aset</th>
                            <th style="text-align: right;">Nilai (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1. Sisa DO di Pertamina</td>
                            <td class="text-right">Rp {{ number_format($aset_do ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>2. Saldo Kas di Bank</td>
                            <td class="text-right">Rp {{ number_format($aset_bank ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>3. Kas Kecil di Outlet (Tunai)</td>
                            <td class="text-right">Rp {{ number_format($aset_kas ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>4. Valuasi Fisik Sisa BBM</td>
                            <td class="text-right">Rp {{ number_format($aset_stok ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>5. Hasil Jual Belum Setor</td>
                            <td class="text-right">Rp {{ number_format($aset_belum_setor ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>6. Piutang Usaha</td>
                            <td class="text-right">Rp {{ number_format($aset_piutang ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="table-total">
                            <td>Subtotal Aset Lancar</td>
                            <td class="text-right">Rp {{ number_format($subtotal_aset ?? $modal_awal ?? 60000000, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>

            <!-- Kolom Kanan: Penyesuaian Modal & Hasil Akhir -->
            <td style="border: none; padding: 0 0 0 6px; width: 50%; vertical-align: top;">
                <div class="section-header" style="margin-top: 0;">B. Rekonsiliasi Saldo Modal</div>
                <table>
                    <thead>
                        <tr>
                            <th>Penyesuaian Modal</th>
                            <th style="text-align: right;">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>• Saldo Awal Modal Bulan Lalu</td>
                            <td class="text-right font-bold">Rp {{ number_format($modal_awal ?? 60000000, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>• Bunga Bank Periode Ini (+)</td>
                            <td class="text-right">Rp {{ number_format($bunga_bank ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>• Pajak & Biaya Bank (-)</td>
                            <td class="text-right" style="color: #b91c1c;">Rp ({{ number_format($pajak_bank ?? 0, 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td>• Penambahan Alokasi Modal 10% (+)</td>
                            <td class="text-right" style="color: #166534;">Rp {{ number_format($alokasi_modal ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>• Profit Sharing Didistribusikan (-)</td>
                            <td class="text-right" style="color: #b91c1c;">Rp ({{ number_format($saldo_dibagi ?? 0, 0, ',', '.') }})</td>
                        </tr>
                        <tr class="table-total" style="background: #f0fdf4;">
                            <td style="color: #166534;">TOTAL MODAL AKHIR BULAN</td>
                            <td class="text-right font-bold" style="color: #166534;">Rp {{ number_format($modal_akhir ?? ($modal_awal ?? 60000000) + ($alokasi_modal ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Rekapitulasi Historis Modal Kumulatif -->
    <div class="section-header">Histori Akumulasi Pertumbuhan Modal Dasar</div>
    <table>
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">No</th>
                <th style="width: 14%;">Periode</th>
                <th style="width: 16%; text-align: right;">Modal Awal</th>
                <th style="width: 16%; text-align: right;">Penambahan 10%</th>
                <th style="width: 14%; text-align: right;">Beban Pajak/Bank</th>
                <th style="width: 18%; text-align: right;">Posisi Akhir Modal</th>
                <th style="width: 16%; text-align: right;">Konversi (Liter)</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($modal_history) && count($modal_history) > 0)
                @foreach($modal_history as $idx => $hist)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $hist['period'] }}</td>
                        <td class="text-right">Rp {{ number_format($hist['modal_awal'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right text-primary">+Rp {{ number_format($hist['profit_10'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right" style="color: #b91c1c;">-Rp {{ number_format($hist['tax_fee'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">Rp {{ number_format($hist['modal_akhir'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($hist['liter_equivalent'] ?? 0, 2, ',', '.') }} ℓ</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td class="text-center">1</td>
                    <td>{{ $period ?? 'Bulan Berjalan' }}</td>
                    <td class="text-right">Rp {{ number_format($modal_awal ?? 60000000, 0, ',', '.') }}</td>
                    <td class="text-right text-primary">+Rp {{ number_format($alokasi_modal ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right" style="color: #b91c1c;">-Rp {{ number_format($pajak_bank ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($modal_akhir ?? ($modal_awal ?? 60000000) + ($alokasi_modal ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((($modal_akhir ?? 60000000) / ($hpp ?: 1)), 2, ',', '.') }} ℓ</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="doc-footer">
        Dokumen ini dibuat otomatis oleh Sistem Informasi Pertashop • Dicetak pada {{ date('d/m/Y H:i') }} WIB
    </div>

</body>
</html>
