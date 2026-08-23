@extends('layouts._new_admin')
@section('title', 'Laporan Bulanan ' . $report->shop->nama . ' — ' . $reportData['monthName'])

@push('style')
<style>
/* Executive Document Styles */
.report-paper {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 32px 36px;
    box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08);
    margin-bottom: 24px;
    color: #0f172a;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.report-header-title {
    font-size: 16px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: center;
    margin-bottom: 2px;
    color: #0f172a;
}

.report-header-sub {
    font-size: 13.5px;
    font-weight: 700;
    text-transform: uppercase;
    text-align: center;
    color: #1e293b;
    margin-bottom: 2px;
}

.report-header-pt {
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    text-align: center;
    color: #0f172a;
    letter-spacing: 0.5px;
    border-bottom: 3px double #0f172a;
    padding-bottom: 8px;
    margin-bottom: 16px;
}

.custom-report-tabs {
    border-bottom: 2px solid #e2e8f0;
    gap: 6px;
    display: flex;
    flex-wrap: wrap;
}

.custom-report-tabs .nav-link {
    border: 1px solid #e2e8f0;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    color: #475569;
    font-weight: 600;
    font-size: 13px;
    padding: 10px 18px;
    background: #f8fafc;
    transition: all 0.2s ease;
}

.custom-report-tabs .nav-link:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.custom-report-tabs .nav-link.active {
    background: #ffffff;
    color: #2563eb;
    border-color: #cbd5e1;
    border-top: 3px solid #2563eb;
    font-weight: 800;
    box-shadow: 0 -2px 8px rgba(37, 99, 235, 0.08);
}

/* Formal Line Tables */
.table-formal {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    line-height: 1.45;
}

.table-formal th, .table-formal td {
    padding: 4px 8px;
    vertical-align: middle;
}

.table-formal-bordered {
    border: 1px solid #94a3b8;
}

.table-formal-bordered th, .table-formal-bordered td {
    border: 1px solid #cbd5e1;
}

.table-formal-bordered thead th {
    background-color: #f1f5f9;
    color: #0f172a;
    font-weight: 700;
    text-align: center;
    border-bottom: 2px solid #64748b;
}

.box-segment {
    border: 1.5px solid #64748b;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    position: relative;
    background: #ffffff;
}

.box-segment-number {
    position: absolute;
    top: 50%;
    right: 24px;
    transform: translateY(-50%);
    width: 65px;
    height: 75px;
    border: 2px solid #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    font-weight: 800;
    color: #0f172a;
    background: #f8fafc;
}

.signature-container {
    margin-top: 30px;
    font-size: 12px;
}

.signature-box {
    text-align: center;
    display: inline-block;
    min-width: 110px;
}

.signature-stamp {
    position: relative;
    height: 65px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.badge-reconciled {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
}

/* Print Optimization */
@media print {
    body {
        background: #ffffff !important;
        font-size: 11pt;
    }
    .d-print-none, .main-sidebar, .sidebar, .navbar, .btn, .custom-report-tabs {
        display: none !important;
    }
    .report-paper {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        page-break-after: always;
    }
    .tab-content > .tab-pane {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    .print-page-break {
        page-break-before: always;
        break-before: page;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- Audit Validation Alert if any diffs exist --}}
    @if(isset($validations) && $validations->where('status', 'invalid')->count() > 0)
        <div class="alert alert-warning border-0 shadow-sm mb-4 d-print-none" role="alert" style="border-left: 4px solid #f59e0b !important;">
            <h6 class="font-weight-bold text-danger mb-1"><i class="fas fa-exclamation-triangle mr-2"></i> Peringatan Audit Perhitungan</h6>
            <p class="mb-2 text-dark" style="font-size: 13px;">Ditemukan ketidakcocokan nilai perhitungan pada laporan bulanan ini dibandingkan data sumber:</p>
            <ul class="mb-0 text-danger font-weight-bold" style="font-size: 12px;">
                @foreach($validations->where('status', 'invalid') as $val)
                    <li>
                        {{ ucwords(str_replace('_', ' ', $val->component)) }}: 
                        Nilai Sistem: <strong>Rp {{ number_format($val->system_value, 0, ',', '.') }}</strong> | 
                        Hasil Hitung Ulang: <strong>Rp {{ number_format($val->recalculated_value, 0, ',', '.') }}</strong> | 
                        Selisih: <u>Rp {{ number_format($val->diff, 0, ',', '.') }}</u>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Top Action & Control Bar --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3 d-print-none">
        <div>
            <a href="{{ route('monthly-reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2" style="border-radius: 6px; font-weight: 600;">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Laporan
            </a>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <h1 class="page-title mb-0" style="font-size: 22px; font-weight: 800; color: #0f172a;">
                    Laporan Bulanan {{ $report->shop->nama }}
                </h1>
                <span class="badge badge-primary px-2.5 py-1" style="font-size: 12px; border-radius: 6px;">
                    <i class="fas fa-calendar-alt mr-1"></i> {{ $reportData['monthName'] }}
                </span>
                <span class="badge-reconciled">
                    <i class="fas fa-check-circle mr-1"></i> Saldo Modal Terverifikasi: Rp {{ number_format($reportData['total_saldo_akhir_modal'], 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-2">
            <button onclick="window.print()" class="btn btn-dark btn-sm shadow-sm" style="font-weight: 600; border-radius: 6px;">
                <i class="fas fa-print mr-1"></i> Cetak / PDF
            </button>
            
            <form action="{{ route('monthly-reports.recalculate-cascade', $report->shop_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Jalankan rekalkulasi modal berantai (Cascading Update) dari awal toko beroperasi hingga bulan berjalan?')">
                @csrf
                <button type="submit" class="btn btn-info btn-sm shadow-sm" style="font-weight: 600; border-radius: 6px;">
                    <i class="fas fa-sync-alt mr-1"></i> Recalculate Berantai (Backdate)
                </button>
            </form>

            @php
                $matchingBackdate = \App\Models\BackdateExcelFile::where('shop_id', $report->shop_id)->where('bulan_tahun', $report->bulan_tahun)->first();
            @endphp
            @if($matchingBackdate)
                <a href="{{ route('backdate-excel-files.show', $matchingBackdate->id) }}" class="btn btn-outline-info btn-sm shadow-sm" style="font-weight: 600; border-radius: 6px;">
                    <i class="fas fa-file-invoice mr-1"></i> Pratinjau Backdate Online
                </a>
            @endif

            @if($report->file_path)
                <a href="{{ route('monthly-reports.download', $report->id) }}" class="btn btn-outline-primary btn-sm shadow-sm" style="font-weight: 600; border-radius: 6px;">
                    <i class="fas fa-file-excel mr-1"></i> Unduh Excel Asli
                </a>
            @endif
        </div>
    </div>

    {{-- Navigation Tabs: 4 Official Report Pages --}}
    <ul class="nav nav-tabs custom-report-tabs mb-4 d-print-none" id="reportTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="tab-hal1-link" data-toggle="tab" href="#tab-hal1" role="tab">
                <i class="fas fa-gas-pump mr-1.5 text-primary"></i> Hal 1: Stok, Penjualan &amp; Laba Kotor
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-hal2-link" data-toggle="tab" href="#tab-hal2" role="tab">
                <i class="fas fa-hand-holding-usd mr-1.5 text-success"></i> Hal 2: Laba Bersih &amp; Profit Sharing
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-hal3-link" data-toggle="tab" href="#tab-hal3" role="tab">
                <i class="fas fa-balance-scale mr-1.5 text-info"></i> Hal 3: Posisi Modal Kerja (Neraca)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-hal4-link" data-toggle="tab" href="#tab-hal4" role="tab">
                <i class="fas fa-history mr-1.5 text-warning"></i> Hal 4: Rekapitulasi Nilai Modal Historis
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-bkh-link" data-toggle="tab" href="#tab-bkh" role="tab">
                <i class="fas fa-table mr-1.5 text-secondary"></i> Rincian Harian (BKH)
            </a>
        </li>
    </ul>

    <div class="tab-content" id="reportTabsContent">

        {{-- ========================================================================= --}}
        {{-- HALAMAN 1: LAPORAN STOK, PENJUALAN & LABA KOTOR --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade show active" id="tab-hal1" role="tabpanel">
            <div class="report-paper">
                <div class="report-header-title">LAPORAN STOCK, PENJUALAN &amp; LABA KOTOR {{ $reportData['period']->isoFormat('DD-MMMM-YYYY') }}</div>
                <div class="report-header-sub">PERTASHOP {{ $report->shop->kode }} {{ $report->shop->alamat }}</div>
                <div class="report-header-pt">PT SERAYU AGUNG MANDIRI</div>

                {{-- Header Prices & Daily Average --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 text-dark" style="font-size: 12px; font-weight: 700; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px;">
                    <div>
                        <span class="text-uppercase">PERTAMAX :</span>
                        @foreach($reportData['segments'] as $sIdx => $seg)
                            <span class="ml-2">Harga Beli {{ $sIdx + 1 }}: Rp {{ number_format($seg['harga_beli'], 2, ',', '.') }},- &nbsp; Harga Jual {{ $sIdx + 1 }}: Rp {{ number_format($seg['harga_jual'], 2, ',', '.') }},-</span>
                        @endforeach
                    </div>
                    <div>
                        Rata-rata omset Harian (ℓ) = <span class="text-primary">{{ number_format($reportData['rata_rata_omset_harian'], 2, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Segments Loop (Kotak 1, Kotak 2, etc.) --}}
                @foreach($reportData['segments'] as $sIdx => $seg)
                    <div class="box-segment">
                        <div class="box-segment-number">{{ $sIdx + 1 }}</div>

                        {{-- I. PEMBELIAN --}}
                        <div class="font-weight-bold mb-1" style="font-size: 13px;">I. PEMBELIAN {{ $sIdx + 1 }}</div>
                        <table class="table-formal mb-2" style="max-width: 85%;">
                            <tr>
                                <td style="width: 140px;">Stok Awal</td>
                                <td style="width: 20px;">=</td>
                                <td style="width: 90px;" class="text-right">{{ number_format($seg['stok_awal'], 2, ',', '.') }}</td>
                                <td style="width: 30px;">ℓ</td>
                                <td style="width: 20px;">x</td>
                                <td style="width: 100px;">Rp {{ number_format($seg['harga_beli'], 2, ',', '.') }}</td>
                                <td style="width: 30px;" class="text-center">&rarr;</td>
                                <td style="width: 130px;" class="text-right">Rp {{ number_format($seg['stok_awal_rp'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>BBM Datang</td>
                                <td>=</td>
                                <td class="text-right">{{ number_format($seg['bbm_datang'], 2, ',', '.') }}</td>
                                <td>ℓ</td>
                                <td>x</td>
                                <td>Rp {{ number_format($seg['harga_beli'], 2, ',', '.') }}</td>
                                <td class="text-center">&rarr;</td>
                                <td class="text-right">Rp {{ number_format($seg['bbm_datang_rp'], 0, ',', '.') }}</td>
                            </tr>
                            <tr style="font-weight: 700; border-top: 1px solid #94a3b8;">
                                <td>A. Jumlah Pembelian {{ $sIdx + 1 }}</td>
                                <td>=</td>
                                <td class="text-right">{{ number_format($seg['jumlah_pembelian'], 2, ',', '.') }}</td>
                                <td>ℓ</td>
                                <td colspan="2"></td>
                                <td class="text-center">&rarr;</td>
                                <td class="text-right">Rp {{ number_format($seg['jumlah_pembelian_rp'], 0, ',', '.') }}</td>
                            </tr>
                        </table>

                        {{-- II. PENJUALAN --}}
                        <div class="font-weight-bold mt-2 mb-1" style="font-size: 13px;">II. PENJUALAN {{ $sIdx + 1 }}</div>
                        <table class="table-formal mb-2" style="max-width: 85%;">
                            <tr>
                                <td style="width: 230px;">a. Totalisator Akhir ({{ $seg['end_datetime_label'] ?? $seg['end_date'] }})</td>
                                <td style="width: 20px;">=</td>
                                <td style="width: 90px;" class="text-right">{{ number_format($seg['totalisator_akhir'], 2, ',', '.') }}</td>
                                <td style="width: 30px;">ℓ</td>
                                <td colspan="4"></td>
                            </tr>
                            <tr>
                                <td>b. Totalisator Awal ({{ $seg['start_datetime_label'] ?? $seg['start_date'] }})</td>
                                <td>=</td>
                                <td class="text-right">{{ number_format($seg['totalisator_awal'], 2, ',', '.') }}</td>
                                <td>ℓ</td>
                                <td style="width: 20px;">-</td>
                                <td colspan="3"></td>
                            </tr>
                            <tr style="border-top: 1px solid #cbd5e1;">
                                <td>c. Total Penjualan {{ $sIdx + 1 }} (a-b)</td>
                                <td>=</td>
                                <td class="text-right">{{ number_format($seg['total_penjualan'], 2, ',', '.') }}</td>
                                <td>ℓ</td>
                                <td colspan="4"></td>
                            </tr>
                            <tr>
                                <td>d. Percobaan (Test Pump)</td>
                                <td>=</td>
                                <td class="text-right">{{ $seg['test_pump'] > 0 ? number_format($seg['test_pump'], 2, ',', '.') : '-' }}</td>
                                <td>ℓ</td>
                                <td>-</td>
                                <td colspan="3"></td>
                            </tr>
                            <tr style="font-weight: 700; border-top: 1px solid #cbd5e1;">
                                <td>B. Jumlah Penjualan {{ $sIdx + 1 }} (c-d)</td>
                                <td>=</td>
                                <td class="text-right">{{ number_format($seg['jumlah_penjualan'], 2, ',', '.') }}</td>
                                <td>ℓ</td>
                                <td>x</td>
                                <td>Rp {{ number_format($seg['harga_jual'], 2, ',', '.') }}</td>
                                <td class="text-center">&rarr;</td>
                                <td class="text-right">Rp {{ number_format($seg['jumlah_penjualan_rp'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Sisa Stock (A-B)</td>
                                <td>=</td>
                                <td class="text-right">{{ number_format($seg['sisa_stok_teoretis'], 2, ',', '.') }}</td>
                                <td>ℓ</td>
                                <td>x</td>
                                <td>Rp {{ number_format($seg['harga_beli'], 2, ',', '.') }}</td>
                                <td class="text-center">&rarr;</td>
                                <td class="text-right">Rp {{ number_format($seg['sisa_stok_teoretis_rp'], 0, ',', '.') }} -</td>
                            </tr>
                            <tr style="border-top: 1px solid #cbd5e1; font-weight: 600;">
                                <td colspan="6">Jumlah {{ $sIdx + 1 }}</td>
                                <td class="text-center">&rarr;</td>
                                <td class="text-right">Rp {{ number_format($seg['jumlah_penjualan_rp'] + $seg['sisa_stok_teoretis_rp'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Losses / Gain &nbsp;&rarr;&nbsp; <span class="{{ $seg['losses_gain'] < 0 ? 'text-danger' : 'text-success' }}">{{ $seg['losses_gain'] < 0 ? 'Losses' : 'Gain' }} ({{ number_format($seg['losses_gain_persen'], 3) }}%)</span></td>
                                <td>=</td>
                                <td class="text-right {{ $seg['losses_gain'] < 0 ? 'text-danger' : 'text-success' }}">({{ number_format(abs($seg['losses_gain']), 3, ',', '.') }})</td>
                                <td>ℓ</td>
                                <td>x</td>
                                <td>Rp {{ number_format($seg['harga_beli'], 2, ',', '.') }}</td>
                                <td class="text-center">&rarr;</td>
                                <td class="text-right {{ $seg['losses_gain'] < 0 ? 'text-danger' : 'text-success' }}">Rp ({{ number_format(abs($seg['losses_gain_rp']), 0, ',', '.') }}) +</td>
                            </tr>
                            <tr style="font-weight: 700; border-top: 1.5px solid #0f172a;">
                                <td colspan="6">C. Jumlah Penjualan Bersih {{ $sIdx + 1 }}</td>
                                <td class="text-center">&rarr;</td>
                                <td class="text-right text-primary">Rp {{ number_format($seg['jumlah_penjualan_bersih'], 0, ',', '.') }}</td>
                            </tr>
                        </table>

                        {{-- III. SISA STOK AKHIR --}}
                        <div class="font-weight-bold mt-2" style="font-size: 13px;">
                            III. Sisa Stok Akhir {{ $sIdx + 1 }} : &nbsp;&nbsp; {{ number_format($seg['stok_akhir_cm'] ?? 0, 2) }} cm &nbsp;&nbsp; = &nbsp;&nbsp; {{ number_format($seg['stok_akhir_fisik'], 2, ',', '.') }} ℓ &nbsp; x &nbsp; Rp {{ number_format($seg['harga_beli'], 2, ',', '.') }} &nbsp;&rarr;&nbsp; <strong>Rp {{ number_format($seg['stok_akhir_fisik_rp'], 0, ',', '.') }}</strong>
                        </div>
                    </div>
                @endforeach

                {{-- Summary Laba Kotor & DO Box Grid --}}
                <div class="row mt-4">
                    {{-- Sisa Stock DO Mees --}}
                    <div class="col-md-5 mb-3">
                        <div class="border p-2.5 rounded bg-light" style="font-size: 12px;">
                            <div class="font-weight-bold mb-2">IV. Sisa Stock DO Di Mees :</div>
                            <table class="table table-sm table-bordered bg-white mb-0 text-center">
                                <thead class="bg-light">
                                    <tr><th>PERTAMAX</th><th>KL</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td class="text-left">Stok Awal</td><td>{{ number_format($reportData['sisa_do_mees']['stok_awal_kl'], 2) }} KL</td></tr>
                                    <tr><td class="text-left">Setor</td><td>{{ number_format($reportData['sisa_do_mees']['setor_kl'], 2) }} KL</td></tr>
                                    <tr><td class="text-left">Setoran Tunai</td><td>{{ number_format($reportData['sisa_do_mees']['setoran_tunai'], 2) }} KL</td></tr>
                                    <tr class="font-weight-bold"><td class="text-left">Jumlah</td><td>{{ number_format($reportData['sisa_do_mees']['setor_kl'], 2) }} KL</td></tr>
                                    <tr><td class="text-left">Datang</td><td>{{ number_format($reportData['sisa_do_mees']['setor_kl'], 2) }} KL</td></tr>
                                    <tr class="font-weight-bold bg-light"><td class="text-left">Sisa</td><td>- KL *)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Summary Laba Kotor Calculations --}}
                    <div class="col-md-7 mb-3">
                        <div class="border p-2.5 rounded bg-light" style="font-size: 12.5px;">
                            @foreach($reportData['segments'] as $sIdx => $seg)
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Total Laba Kotor {{ $sIdx + 1 }} = Penjualan {{ $sIdx + 1 }} (Rp {{ number_format($seg['jumlah_penjualan_bersih'], 0, ',', '.') }}) - Pembelian {{ $sIdx + 1 }} (Rp {{ number_format($seg['jumlah_pembelian_rp'], 0, ',', '.') }})</span>
                                    <strong class="text-dark">Rp {{ number_format($seg['laba_kotor'], 0, ',', '.') }}</strong>
                                </div>
                            @endforeach
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center font-weight-bold" style="font-size: 14px;">
                                <span>Grand Total Laba Kotor Bulan Berjalan :</span>
                                <span class="text-success" style="font-size: 16px;">Rp {{ number_format($reportData['grand_total_laba_kotor'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Margin History Footnote --}}
                <div class="mt-3 border-top pt-2" style="font-size: 11px;">
                    <div class="font-weight-bold mb-1">Ilustrasi Turun / Naik Margin Pertamax92 Pertashop :</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered text-center mb-0 bg-white" style="font-size: 11px;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Tanggal Efektif</th>
                                    <th>Harga Beli</th>
                                    <th>Harga Jual</th>
                                    <th>Margin</th>
                                    <th>Naik / Turun</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['margin_history'] as $mh)
                                    <tr>
                                        <td>{{ $mh['tanggal'] }}</td>
                                        <td>Rp {{ number_format($mh['harga_beli'], 2, ',', '.') }}</td>
                                        <td>Rp {{ number_format($mh['harga_jual'], 2, ',', '.') }}</td>
                                        <td class="font-weight-bold">Rp {{ number_format($mh['margin'], 2, ',', '.') }}</td>
                                        <td class="{{ $mh['arah'] == 'Naik' ? 'text-success' : ($mh['arah'] == 'Turun' ? 'text-danger' : '') }}">
                                            {{ $mh['arah'] }} {{ $mh['diff'] > 0 ? '(Rp ' . number_format($mh['diff'], 2, ',', '.') . ')' : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Signatures --}}
                <div class="d-flex justify-content-between align-items-end mt-5 signature-container">
                    <div>
                        <div class="text-muted mb-4 font-weight-bold">Disetujui Oleh,</div>
                        <div class="d-flex gap-3">
                            <div class="signature-box">PT. SAM</div>
                            <div class="signature-box">Victor E. A.</div>
                            <div class="signature-box">Koko Aribowo</div>
                            <div class="signature-box">Kaswari</div>
                            <div class="signature-box">Sugiyanto K.</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-muted mb-1">{{ $report->shop->kota ?? 'Banyumas' }}, {{ $reportData['period']->endOfMonth()->isoFormat('DD MMMM YYYY') }}</div>
                        <div class="font-weight-bold mb-2">Dibuat Oleh,</div>
                        <div class="font-weight-bold" style="color: #1e3a8a;">PT. SERAYU AGUNG MANDIRI</div>
                        <div class="signature-stamp">
                            <i class="fas fa-file-signature text-primary fa-2x opacity-50"></i>
                        </div>
                        <div class="font-weight-bold text-dark">Dwi Yuliarto</div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- HALAMAN 2: LAPORAN LABA BERSIH & PROFIT SHARING --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade" id="tab-hal2" role="tabpanel">
            <div class="report-paper">
                <div class="report-header-title">PERHITUNGAN LABA BERSIH {{ $reportData['period']->isoFormat('DD-MMMM-YYYY') }}</div>
                <div class="report-header-sub">PERTASHOP {{ $report->shop->kode }} {{ $report->shop->alamat }}</div>
                <div class="report-header-pt">PT SERAYU AGUNG MANDIRI</div>

                {{-- PENDAPATAN --}}
                <div class="font-weight-bold text-uppercase mb-1" style="font-size: 13px; text-decoration: underline;">PENDAPATAN</div>
                <table class="table-formal mb-3">
                    <tr>
                        <td style="width: 280px;">1. LABA KOTOR ........................................................................</td>
                        <td style="width: 20px;">=</td>
                        <td style="width: 140px;" class="text-right">Rp {{ number_format($reportData['grand_total_laba_kotor'], 0, ',', '.') }}</td>
                        <td style="width: 40px;"></td>
                        <td class="text-right font-weight-bold" style="width: 200px;">A. Total Laba Kotor = Rp {{ number_format($reportData['grand_total_laba_kotor'], 0, ',', '.') }}</td>
                    </tr>
                </table>

                {{-- PENGELUARAN --}}
                @php $p = $reportData['pengeluaran_details']; @endphp
                <div class="font-weight-bold text-uppercase mb-1" style="font-size: 13px; text-decoration: underline;">PENGELUARAN</div>
                <table class="table-formal mb-2">
                    <tr><td>1. GAJI OPERATOR ...................................................................</td><td>=</td><td class="text-right">Rp {{ number_format($p['gaji_operator'], 0, ',', '.') }}</td><td></td></tr>
                    <tr><td>2. GAJI ADMIN ..........................................................................</td><td>=</td><td class="text-right">Rp {{ number_format($p['gaji_admin'], 0, ',', '.') }}</td><td></td></tr>
                    <tr><td>3. BIAYA CURAH / BONGKAR .................................................</td><td>=</td><td class="text-right">Rp {{ number_format($p['biaya_curah'], 0, ',', '.') }}</td><td></td></tr>
                    <tr><td>4. BIAYA TRANSFER BANK ....................................................</td><td>=</td><td class="text-right">{{ $p['biaya_tf'] > 0 ? 'Rp ' . number_format($p['biaya_tf'], 0, ',', '.') : 'Rp -' }}</td><td></td></tr>
                    <tr><td>5. LISTRIK .................................................................................</td><td>=</td><td class="text-right">Rp {{ number_format($p['listrik'], 0, ',', '.') }}</td><td></td></tr>
                    <tr><td>6. AIR BERSIH ..........................................................................</td><td>=</td><td class="text-right">Rp {{ number_format($p['air'], 0, ',', '.') }}</td><td></td></tr>
                    <tr><td>7. CASHBACK PENGECER .....................................................</td><td>=</td><td class="text-right">Rp {{ number_format($p['cashback'], 0, ',', '.') }}</td><td></td></tr>
                    <tr><td>8. INTERNET .............................................................................</td><td>=</td><td class="text-right">Rp {{ number_format($p['internet'], 0, ',', '.') }}</td><td></td></tr>
                    <tr><td>9. FOTOCOPY &amp; ATK ............................................................</td><td>=</td><td class="text-right">{{ $p['atk'] > 0 ? 'Rp ' . number_format($p['atk'], 0, ',', '.') : 'Rp -' }}</td><td></td></tr>
                    <tr><td>10. LAIN2 ({{ $p['lain_lain_notes'] ?: 'OPERASIONAL' }}) .................................</td><td>=</td><td class="text-right">Rp {{ number_format($p['lain_lain'], 0, ',', '.') }}</td><td></td></tr>
                    <tr style="border-top: 1px solid #94a3b8; font-weight: 700;">
                        <td colspan="2">B. Total Biaya</td>
                        <td class="text-right text-danger">Rp {{ number_format($reportData['total_biaya'], 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </table>

                {{-- STATEMENT SUMMARY LABA BERSIH & ALOKASI --}}
                <div class="row justify-content-end mb-3">
                    <div class="col-md-6">
                        <table class="table-formal" style="font-size: 13px;">
                            <tr><td>A. Total Laba Kotor</td><td class="text-right font-weight-bold">Rp {{ number_format($reportData['grand_total_laba_kotor'], 0, ',', '.') }}</td></tr>
                            <tr><td>B. Total Biaya</td><td class="text-right font-weight-bold text-danger">Rp {{ number_format($reportData['total_biaya'], 0, ',', '.') }} -</td></tr>
                            <tr style="border-top: 1.5px solid #0f172a; font-weight: 800;">
                                <td>(A-B) LABA BERSIH</td>
                                <td class="text-right text-success" style="font-size: 14px;">Rp {{ number_format($reportData['laba_bersih'], 0, ',', '.') }}</td>
                            </tr>
                            <tr class="text-muted">
                                <td>*) Alokasi Penambahan Modal dari 10% Profit</td>
                                <td class="text-right text-warning font-weight-bold">Rp {{ number_format($reportData['alokasi_penambahan_modal'], 0, ',', '.') }} -</td>
                            </tr>
                            <tr style="font-weight: 700;">
                                <td>Saldo Laba Bersih (90%) yg Dibagi</td>
                                <td class="text-right">Rp {{ number_format($reportData['saldo_laba_bersih_90'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Saldo Laba Bersih Bulan (SEBELUMNYA) yg blm Dibagi</td>
                                <td class="text-right">{{ $reportData['saldo_laba_sebelumnya'] > 0 ? 'Rp ' . number_format($reportData['saldo_laba_sebelumnya'], 0, ',', '.') : 'Rp -' }} +</td>
                            </tr>
                            <tr style="border-top: 2px solid #0f172a; font-weight: 800; background: #f8fafc;">
                                <td>Total Saldo Laba Bersih yg Dibagi [HOLD / PAYOUT]</td>
                                <td class="text-right text-primary" style="font-size: 15px;">Rp {{ number_format($reportData['total_saldo_laba_dibagi'], 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- PEMBAGIAN LABA BERSIH INVESTOR --}}
                <div class="font-weight-bold mt-3 mb-1" style="font-size: 13px;">Pembagian Laba Bersih :</div>
                <table class="table-formal mb-3" style="max-width: 60%;">
                    @foreach($reportData['investor_distributions'] as $idx => $inv)
                        <tr>
                            <td style="width: 30px;">{{ $idx + 1 }}.</td>
                            <td style="width: 220px;">{{ $inv['nama'] }}</td>
                            <td style="width: 70px;" class="text-right">{{ number_format($inv['persen'], 0) }}%</td>
                            <td style="width: 20px;" class="text-center">=</td>
                            <td class="text-right font-weight-bold">Rp {{ number_format($inv['nominal'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr style="border-top: 1.5px solid #0f172a; font-weight: 800;">
                        <td colspan="4">Total</td>
                        <td class="text-right text-success">Rp {{ number_format($reportData['total_saldo_laba_dibagi'], 0, ',', '.') }}</td>
                    </tr>
                </table>

                {{-- CATATAN REKENING & CHECKLIST TRANSFER --}}
                <div class="font-weight-bold mt-3 mb-1" style="font-size: 13px;">Catatan :</div>
                <div class="text-muted mb-2" style="font-size: 12px;">Bila Sudah Disetujui maka Laba akan segera ditransfer ke Rekening :</div>
                <table class="table-formal mb-3">
                    <thead>
                        <tr class="font-weight-bold" style="font-size: 11.5px; border-bottom: 1px solid #cbd5e1;">
                            <th>No</th>
                            <th>Bank &amp; No. Rekening</th>
                            <th>Atas Nama Rekening</th>
                            <th class="text-right">Nominal Transfer</th>
                            <th class="text-center">Checklist Transfer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['investor_distributions'] as $idx => $inv)
                            <tr>
                                <td>{{ $idx + 1 }}.</td>
                                <td><strong>{{ $inv['nama_bank'] }}</strong> {{ $inv['no_rekening'] }}</td>
                                <td>a/n {{ $inv['atas_nama_rekening'] }}</td>
                                <td class="text-right font-weight-bold">Rp {{ number_format($inv['nominal'], 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <input type="checkbox" checked disabled style="transform: scale(1.2);">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="text-muted italic mb-4" style="font-size: 11.5px;">*) Jika Laba Positif, Alokasi Modal 10% Untuk Penambahan Modal Dasar</div>

                {{-- Signatures --}}
                <div class="d-flex justify-content-between align-items-end mt-4 signature-container">
                    <div>
                        <div class="text-muted mb-4 font-weight-bold">Disetujui Oleh,</div>
                        <div class="d-flex gap-3">
                            <div class="signature-box">PT. SAM</div>
                            <div class="signature-box">Victor E. A.</div>
                            <div class="signature-box">Koko Aribowo</div>
                            <div class="signature-box">Kaswari</div>
                            <div class="signature-box">Sugiyanto K.</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-muted mb-1">{{ $report->shop->kota ?? 'Banyumas' }}, {{ $reportData['period']->endOfMonth()->isoFormat('DD MMMM YYYY') }}</div>
                        <div class="font-weight-bold mb-2">Dibuat Oleh,</div>
                        <div class="font-weight-bold" style="color: #1e3a8a;">PT. SERAYU AGUNG MANDIRI</div>
                        <div class="signature-stamp">
                            <i class="fas fa-file-signature text-primary fa-2x opacity-50"></i>
                        </div>
                        <div class="font-weight-bold text-dark">Dwi Yuliarto</div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- HALAMAN 3: POSISI MODAL KERJA (NERACA LIKUIDITAS) --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade" id="tab-hal3" role="tabpanel">
            <div class="report-paper">
                <div class="report-header-title">POSISI MODAL KERJA PERIODE {{ $reportData['period']->isoFormat('DD-MMMM-YYYY') }}</div>
                <div class="report-header-sub">PERTASHOP {{ $report->shop->kode }} {{ $report->shop->alamat }}</div>
                <div class="report-header-pt">PT SERAYU AGUNG MANDIRI</div>

                {{-- Working Capital Table --}}
                <div class="d-flex justify-content-between align-items-center mb-2 font-weight-bold" style="font-size: 13.5px; border-bottom: 2px solid #0f172a; padding-bottom: 4px;">
                    <span>POSISI MODAL KERJA</span>
                    <span>Saldo Awal Modal Periode Bulan Sebelumnya : <strong class="text-primary">Rp {{ number_format($reportData['saldo_awal_modal'], 0, ',', '.') }}</strong></span>
                </div>

                <table class="table-formal mb-3" style="font-size: 13px;">
                    <tbody>
                        <tr>
                            <td style="width: 30px;">1.</td>
                            <td style="width: 260px;">DO yang Masih Ada di Pertamina</td>
                            <td style="width: 140px;" class="text-center">{{ $reportData['do_di_pertamina'] > 0 ? number_format($reportData['sisa_do_mees']['setor_kl'] ?? 0, 2) . ' ℓ x Rp ' . number_format($reportData['final_harga_beli'], 2, ',', '.') : '- ℓ x Rp ' . number_format($reportData['final_harga_beli'], 2, ',', '.') }}</td>
                            <td style="width: 20px;">:</td>
                            <td class="text-right" style="width: 140px;">{{ $reportData['do_di_pertamina'] > 0 ? 'Rp ' . number_format($reportData['do_di_pertamina'], 0, ',', '.') : 'Rp -' }}</td>
                        </tr>
                        <tr>
                            <td>2.</td>
                            <td>Uang Di Bank Periode Bulan ini</td>
                            <td class="text-center"></td>
                            <td>:</td>
                            <td class="text-right">Rp {{ number_format($reportData['uang_di_bank'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>3.</td>
                            <td>Kas Kecil di Pertashop (TUNAI)</td>
                            <td class="text-center"></td>
                            <td>:</td>
                            <td class="text-right text-muted">Rp ({{ number_format(abs($reportData['kas_kecil']), 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td>4.</td>
                            <td>Sisa Stok yang Masih ada Di Pertashop</td>
                            <td class="text-center">{{ number_format($reportData['final_stok_liter'], 2, ',', '.') }} ℓ x Rp {{ number_format($reportData['final_harga_beli'], 2, ',', '.') }}</td>
                            <td>:</td>
                            <td class="text-right text-muted">Rp ({{ number_format(abs($reportData['sisa_stok_pertashop_rp']), 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td>5.</td>
                            <td>Hasil Penjualan yang Belum Disetor di Akhir Periode (TUNAI)</td>
                            <td class="text-center"></td>
                            <td>:</td>
                            <td class="text-right text-muted">Rp ({{ number_format(abs($reportData['hasil_belum_disetor']), 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td>6.</td>
                            <td>Piutang</td>
                            <td class="text-center"></td>
                            <td>:</td>
                            <td class="text-right text-muted">Rp ({{ number_format(abs($reportData['piutang']), 0, ',', '.') }}) +</td>
                        </tr>
                        <tr style="border-top: 1.5px solid #0f172a; font-weight: 700; background: #f8fafc;">
                            <td colspan="3" class="text-right">A. Sub Total Saldo Akhir Modal :</td>
                            <td>:</td>
                            <td class="text-right">Rp {{ number_format($reportData['subtotal_a'], 0, ',', '.') }}</td>
                        </tr>

                        {{-- Section Adjustments --}}
                        <tr>
                            <td>7.</td>
                            <td>Bunga Bank Periode Bulan ini</td>
                            <td class="text-center"></td>
                            <td>:</td>
                            <td class="text-right text-success">Rp {{ number_format($reportData['bunga_bank'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>8.</td>
                            <td>Pajak Bank Periode Bulan ini</td>
                            <td class="text-center"></td>
                            <td>:</td>
                            <td class="text-right text-danger">Rp ({{ number_format($reportData['pajak_bank'], 0, ',', '.') }})</td>
                        </tr>
                        <tr>
                            <td>9.</td>
                            <td>Profit Sharing yang dibagi ke Investor</td>
                            <td class="text-center"></td>
                            <td>:</td>
                            <td class="text-right font-weight-bold text-dark">Rp {{ number_format($reportData['profit_sharing_dibagi'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>10.</td>
                            <td><span class="{{ $reportData['penambahan_keuntungan'] >= 0 ? 'text-primary' : 'text-danger' }}">Penambahan / Pengurangan Modal dari Keuntungan bulan ini</span></td>
                            <td class="text-center"></td>
                            <td>:</td>
                            <td class="text-right font-weight-bold {{ $reportData['penambahan_keuntungan'] >= 0 ? 'text-primary' : 'text-danger' }}">
                                Rp {{ number_format($reportData['penambahan_keuntungan'], 0, ',', '.') }} +
                            </td>
                        </tr>
                        <tr style="border-top: 1.5px solid #0f172a; font-weight: 700; background: #f8fafc;">
                            <td colspan="3" class="text-right">B. Sub Total Penambahan Modal :</td>
                            <td>:</td>
                            <td class="text-right">Rp {{ number_format($reportData['subtotal_b'], 0, ',', '.') }}</td>
                        </tr>
                        <tr style="border-top: 1px solid #cbd5e1; font-weight: 700;">
                            <td colspan="3" class="text-right">C. Sub Total Saldo Akhir Modal (A+B) :</td>
                            <td>:</td>
                            <td class="text-right">Rp {{ number_format($reportData['subtotal_c'], 0, ',', '.') }}</td>
                        </tr>
                        <tr style="border-top: 2px solid #0f172a; font-weight: 800; background: #eff6ff; font-size: 14px;">
                            <td colspan="3" class="text-right text-primary">D. Total Saldo Akhir Modal (C-9) :</td>
                            <td>:</td>
                            <td class="text-right text-primary" style="font-size: 16px;">Rp {{ number_format($reportData['total_saldo_akhir_modal'], 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- Signatures --}}
                <div class="d-flex justify-content-end mt-5 signature-container">
                    <div class="text-center" style="min-width: 250px;">
                        <div class="text-muted mb-1">{{ $report->shop->kota ?? 'Banyumas' }}, {{ $reportData['period']->endOfMonth()->isoFormat('DD MMMM YYYY') }}</div>
                        <div class="font-weight-bold mb-2">Dibuat Oleh,</div>
                        <div class="font-weight-bold" style="color: #1e3a8a;">PT. SERAYU AGUNG MANDIRI</div>
                        <div class="signature-stamp">
                            <i class="fas fa-file-signature text-primary fa-2x opacity-50"></i>
                        </div>
                        <div class="font-weight-bold text-dark">Dwi Yuliarto</div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- HALAMAN 4: REKAPITULASI NILAI MODAL HISTORIS --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade" id="tab-hal4" role="tabpanel">
            <div class="report-paper">
                <div class="report-header-title">REKAPITULASI NILAI MODAL {{ $report->shop->nama }}</div>
                <div class="report-header-sub">{{ $report->shop->kode }} {{ $report->shop->alamat }}</div>
                <div class="report-header-pt">PT SERAYU AGUNG MANDIRI</div>

                <div class="table-responsive mb-4" style="max-height: 520px; overflow-y: auto;">
                    <table class="table-formal table-formal-bordered table-sm text-center" style="font-size: 11.5px; white-space: nowrap;">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Tahun Ke</th>
                                <th>Bulan</th>
                                <th>Nilai Modal Awal</th>
                                <th>Penyusutan Karena Rugi</th>
                                <th>Penyusutan Pajak &amp; Biaya Bank</th>
                                <th>Penambahan Dari Alokasi Keuntungan</th>
                                <th>Penambahan Dari Bunga Bank</th>
                                <th>Nilai Penambahan / Penyusutan Modal</th>
                                <th>Akumulasi Penambahan / Penyusutan Modal</th>
                                <th>Posisi Akhir Modal</th>
                                <th>Harga Beli Pertamax</th>
                                <th>Konversi Jumlah Modal Ke Liter</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $bulanIndo = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                $curMonth = $reportData['period']->month;
                                $curYear = $reportData['period']->year;
                            @endphp
                            @forelse($reportData['capital_recaps'] as $recap)
                                @php
                                    $isCurrent = ($recap->tahun == $curYear && $recap->bulan == $curMonth);
                                @endphp
                                <tr class="{{ $isCurrent ? 'font-weight-bold' : '' }}" style="{{ $isCurrent ? 'background-color: #fef9c3 !important; border: 2px solid #eab308 !important;' : '' }}">
                                    <td>{{ $recap->tahun_ke }}</td>
                                    <td class="text-left font-weight-bold">{{ $bulanIndo[$recap->bulan] ?? $recap->bulan }} {{ $recap->tahun }}</td>
                                    <td class="text-right">Rp {{ number_format($recap->nilai_modal_awal, 0, ',', '.') }}</td>
                                    
                                    <td class="text-right {{ $recap->penyusutan_rugi < 0 ? 'text-danger font-weight-bold' : '' }}">
                                        {{ $recap->penyusutan_rugi < 0 ? 'Rp (' . number_format(abs($recap->penyusutan_rugi), 0, ',', '.') . ')' : '-' }}
                                    </td>
                                    <td class="text-right {{ $recap->penyusutan_pajak_bank < 0 ? 'text-danger' : '' }}">
                                        {{ $recap->penyusutan_pajak_bank < 0 ? 'Rp (' . number_format(abs($recap->penyusutan_pajak_bank), 0, ',', '.') . ')' : '-' }}
                                    </td>
                                    <td class="text-right {{ $recap->penambahan_keuntungan > 0 ? 'text-success font-weight-bold' : '' }}">
                                        {{ $recap->penambahan_keuntungan > 0 ? 'Rp ' . number_format($recap->penambahan_keuntungan, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-right {{ $recap->penambahan_bunga_bank > 0 ? 'text-success' : '' }}">
                                        {{ $recap->penambahan_bunga_bank > 0 ? 'Rp ' . number_format($recap->penambahan_bunga_bank, 0, ',', '.') : '-' }}
                                    </td>
                                    
                                    <td class="text-right font-weight-bold {{ $recap->nilai_penambahan_penyusutan < 0 ? 'text-danger' : 'text-dark' }}">
                                        Rp {{ number_format($recap->nilai_penambahan_penyusutan, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right font-weight-bold text-primary">
                                        Rp {{ number_format($recap->akumulasi_penambahan_penyusutan, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right font-weight-bold" style="font-size: 12px; color: #0f172a;">
                                        Rp {{ number_format($recap->posisi_akhir_modal, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right">Rp {{ number_format($recap->harga_beli_pertamax, 2, ',', '.') }}</td>
                                    <td class="text-right font-weight-bold">{{ number_format($recap->konversi_liter, 2, ',', '.') }} ℓ</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-4 text-muted">Belum ada data Rekapitulasi Modal. Silakan Import atau Recalculate.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Summary Footer --}}
                <div class="row justify-content-between align-items-center p-3 rounded bg-light border">
                    <div class="col-md-6" style="font-size: 12.5px;">
                        <table class="table-formal">
                            <tr>
                                <td style="width: 140px;">Nilai Modal Dasar</td>
                                <td style="width: 20px;">=</td>
                                <td style="width: 130px;" class="text-right font-weight-bold">Rp {{ number_format($reportData['modal_awal_dasar'], 0, ',', '.') }}</td>
                                <td class="text-right" style="width: 90px;">100.00%</td>
                            </tr>
                            <tr>
                                <td>Penambahan Modal</td>
                                <td>=</td>
                                <td class="text-right font-weight-bold text-success">+ Rp {{ number_format($reportData['total_akumulasi_modal'], 0, ',', '.') }}</td>
                                <td class="text-right text-success">+ {{ number_format($reportData['persen_penambahan_modal'], 2) }}%</td>
                            </tr>
                            <tr style="border-top: 1.5px solid #0f172a; font-weight: 800;">
                                <td>Total Modal</td>
                                <td>=</td>
                                <td class="text-right text-primary" style="font-size: 14px;">Rp {{ number_format($reportData['grand_total_modal'], 0, ',', '.') }}</td>
                                <td class="text-right text-primary" style="font-size: 14px;">{{ number_format($reportData['persen_grand_total'], 2) }}%</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-5 text-right">
                        <div class="p-3 bg-white border rounded shadow-xs">
                            <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 11px;">Saldo Akhir Modal Terverifikasi</small>
                            <span class="h4 font-weight-bold text-primary mb-0">Rp {{ number_format($reportData['total_saldo_akhir_modal'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Signatures --}}
                <div class="d-flex justify-content-end mt-4 signature-container">
                    <div class="text-center" style="min-width: 250px;">
                        <div class="text-muted mb-1">{{ $report->shop->kota ?? 'Banyumas' }}, {{ $reportData['period']->endOfMonth()->isoFormat('DD MMMM YYYY') }}</div>
                        <div class="font-weight-bold mb-2">Dibuat Oleh,</div>
                        <div class="font-weight-bold" style="color: #1e3a8a;">PT. SERAYU AGUNG MANDIRI</div>
                        <div class="signature-stamp">
                            <i class="fas fa-file-signature text-primary fa-2x opacity-50"></i>
                        </div>
                        <div class="font-weight-bold text-dark">Dwi Yuliarto</div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- TAB RINCIAN HARIAN (BKH DATA) --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade" id="tab-bkh" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-table text-secondary mr-2"></i> Rincian Buku Kendali Harian (BKH)</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered text-center" style="font-size: 11.5px; white-space: nowrap;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Hari / Tgl</th>
                                    <th>Tot Awal</th>
                                    <th>Tot Akhir</th>
                                    <th>Vol Jual (L)</th>
                                    <th>Rupiah Jual</th>
                                    <th>Test Pump</th>
                                    <th>Terima BBM</th>
                                    <th>Losses (L)</th>
                                    <th>Stok Akhir</th>
                                    <th>Total Biaya</th>
                                    <th>Belum Setor</th>
                                    <th>Operator</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report->data_parsed['daily_data'] ?? [] as $row)
                                    <tr>
                                        <td class="font-weight-bold text-left">{{ $row['hari_tgl'] ?? '-' }}</td>
                                        <td>{{ number_format($row['tot_awal'] ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ number_format($row['tot_akhir'] ?? 0, 2, ',', '.') }}</td>
                                        <td class="font-weight-bold text-primary">{{ number_format($row['volume_jual_teoritis'] ?? 0, 2, ',', '.') }}</td>
                                        <td>Rp {{ number_format($row['rupiah_jual_teoritis'] ?? 0, 0, ',', '.') }}</td>
                                        <td>{{ number_format($row['tp_volume'] ?? 0, 2, ',', '.') }}</td>
                                        <td class="text-success font-weight-bold">{{ number_format($row['terima_bbm'] ?? 0, 2, ',', '.') }}</td>
                                        <td class="{{ ($row['losses_volume'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['losses_volume'] ?? 0, 2, ',', '.') }}</td>
                                        <td>{{ number_format($row['stok_akhir'] ?? 0, 2, ',', '.') }}</td>
                                        <td class="text-danger font-weight-bold">Rp {{ number_format($row['biaya']['total'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-warning font-weight-bold">Rp {{ number_format($row['setoran']['belum_setor'] ?? 0, 0, ',', '.') }}</td>
                                        <td>{{ $row['operator_nama'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="12" class="text-center text-muted py-3">Tidak ada data harian.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
