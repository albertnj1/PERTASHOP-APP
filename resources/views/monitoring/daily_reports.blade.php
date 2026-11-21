@extends('layouts.master')

@push('style')
    <style>
        .monitoring-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }

        .table-rekap-container {
            max-height: 720px;
            overflow-x: auto;
            overflow-y: auto;
            position: relative;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }

        .table-rekap {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 11.5px;
            white-space: nowrap;
        }

        .table-rekap thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #1e3a8a;
            color: #ffffff;
            border: 1px solid #0f172a;
            padding: 5px 8px;
            text-align: center;
            vertical-align: middle;
            font-weight: 600;
        }

        .table-rekap thead tr:nth-child(2) th {
            top: 32px;
            z-index: 20;
            background-color: #2563eb;
        }

        /* Sticky Frozen Kolom A, B, C */
        .table-rekap th.sticky-col-1,
        .table-rekap td.sticky-col-1 {
            position: sticky;
            left: 0;
            z-index: 10;
            background-color: #f8fafc;
            min-width: 42px;
            max-width: 42px;
        }

        .table-rekap th.sticky-col-2,
        .table-rekap td.sticky-col-2 {
            position: sticky;
            left: 42px;
            z-index: 10;
            background-color: #f8fafc;
            min-width: 42px;
            max-width: 42px;
        }

        .table-rekap th.sticky-col-3,
        .table-rekap td.sticky-col-3 {
            position: sticky;
            left: 84px;
            z-index: 10;
            background-color: #f8fafc;
            min-width: 175px;
            border-right: 2px solid #94a3b8 !important;
        }

        .table-rekap thead th.sticky-col-1,
        .table-rekap thead th.sticky-col-2,
        .table-rekap thead th.sticky-col-3 {
            z-index: 30;
            background-color: #1e3a8a;
        }

        .table-rekap tbody td {
            padding: 4px 7px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .table-rekap tbody tr:hover td {
            background-color: #e0f2fe !important;
        }

        .table-rekap tbody tr.row-today td {
            background-color: #fef08a !important;
            font-weight: 600;
        }

        .table-rekap tbody tr.row-edited td {
            background-color: #f0f9ff;
        }

        .table-rekap tbody tr.row-sunday td {
            background-color: #fffbeb;
        }

        .table-rekap tbody tr.row-unreported td {
            background-color: #f8fafc;
            color: #94a3b8;
        }

        .text-val-negative {
            color: #dc2626 !important;
            font-weight: 600;
        }

        .text-val-positive {
            color: #16a34a;
        }

        .row-total-35 td {
            background-color: #e2e8f0 !important;
            font-weight: 700 !important;
            border-top: 2px solid #0f172a !important;
            border-bottom: 2px solid #0f172a !important;
        }

        .row-extra td {
            background-color: #f1f5f9 !important;
            font-weight: 600;
            border-bottom: 1px solid #cbd5e1;
        }

        .live-dot {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background-color: #22c55e;
            margin-right: 5px;
            animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 6px rgba(34, 197, 94, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
            }
        }
    </style>
@endpush

@section('content')
    <div class="content-header py-3">
        <div class="container-fluid">
            <div class="row align-items-center mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark font-weight-bold" style="font-size: 24px;">
                        <i class="fas fa-desktop text-primary mr-2"></i>Pantauan Laporan Harian Super Admin
                    </h1>
                    <p class="text-muted small mb-0 mt-1">
                        Pratinjau Dinamis Laporan Harian &amp; Format Rekap Bulanan Resmi Persis Excel — Terintegrasi
                        Pertashop Engine
                    </p>
                </div>
                <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
                    @if(isset($isLocked) && $isLocked)
                        <span class="badge badge-success px-3 py-2 mr-2 shadow-sm font-weight-bold" style="font-size: 11.5px;">
                            <i class="fas fa-lock mr-1"></i> VALID &amp; TERKUNCI
                            ({{ $periodLock?->locked_at ? \Carbon\Carbon::parse($periodLock->locked_at)->format('d/m/Y') : '' }})
                        </span>
                        <a href="{{ route('monitoring.daily-reports.export-pdf', ['shop_id' => $selectedShopId, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
                            class="btn btn-danger btn-sm shadow-sm font-weight-bold mr-1">
                            <i class="fas fa-file-pdf mr-1"></i> Unduh PDF Resmi
                        </a>
                        <form action="{{ route('monitoring.daily-reports.validate-period') }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Buka kunci periode {{ $yearMonth }} agar data dapat diedit kembali?')">
                            @csrf
                            <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <input type="hidden" name="year" value="{{ $selectedYear }}">
                            <input type="hidden" name="action" value="unlock">
                            <button type="submit" class="btn btn-outline-warning btn-sm shadow-sm font-weight-bold mr-1"
                                title="Buka Kunci untuk revisi">
                                <i class="fas fa-unlock mr-1"></i> Buka Kunci
                            </button>
                        </form>
                    @else
                        <span class="badge badge-warning text-dark px-3 py-2 mr-2 shadow-sm font-weight-bold"
                            style="font-size: 11.5px;">
                            <i class="fas fa-clock mr-1"></i> BELUM DIVALIDASI (DRAFT)
                        </span>
                        <form action="{{ route('monitoring.daily-reports.validate-period') }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Validasi dan kunci seluruh data laporan harian bulan {{ $yearMonth }}?')">
                            @csrf
                            <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <input type="hidden" name="year" value="{{ $selectedYear }}">
                            <input type="hidden" name="action" value="lock">
                            <button type="submit" class="btn btn-success btn-sm shadow-sm font-weight-bold mr-1">
                                <i class="fas fa-check-double mr-1"></i> Validasi &amp; Kunci Bulan Ini
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm font-weight-bold mr-1" disabled
                            title="PDF resmi dapat diunduh setelah rekap divalidasi">
                            <i class="fas fa-file-pdf mr-1"></i> PDF (Terkunci)
                        </button>
                    @endif

                    @if($recapData)
                        <a href="{{ route('monitoring.daily-reports.export-excel', ['shop_id' => $selectedShopId, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
                            class="btn btn-outline-success btn-sm shadow-sm font-weight-bold mr-1">
                            <i class="fas fa-file-excel mr-1"></i> Excel
                        </a>
                    @endif

                    <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm" id="btnManualRefresh"
                        onclick="triggerRefresh()" title="Segarkan Data">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            {{-- 1. Filter Bar & Quick Stats --}}
            <div class="card monitoring-card mb-3">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('monitoring.daily-reports') }}" id="filterForm">
                        <div class="form-row align-items-end">
                            <div class="col-12 col-sm-6 col-lg-3 mb-2">
                                <label class="small font-weight-bold text-muted mb-1">Pilih Cabang Pertashop</label>
                                <select name="shop_id" id="shop_id" class="form-control form-control-sm font-weight-bold"
                                    onchange="this.form.submit()">
                                    @foreach($shops as $s)
                                        <option value="{{ $s->id }}" {{ $selectedShopId == $s->id ? 'selected' : '' }}>
                                            {{ $s->kode ?: 'KOD' }} — {{ $s->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-3 mb-2">
                                <label class="small font-weight-bold text-muted mb-1">Bulan</label>
                                <select name="month" id="month" class="form-control form-control-sm font-weight-bold"
                                    onchange="this.form.submit()">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-3 mb-2">
                                <label class="small font-weight-bold text-muted mb-1">Tahun</label>
                                <select name="year" id="year" class="form-control form-control-sm font-weight-bold"
                                    onchange="this.form.submit()">
                                    @for($y = Carbon\Carbon::now()->year - 2; $y <= Carbon\Carbon::now()->year + 1; $y++)
                                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-5 col-sm-12 mb-2 text-md-right">
                                <button type="button" class="btn btn-outline-info btn-sm mr-2 shadow-sm font-weight-bold"
                                    data-toggle="modal" data-target="#multiShopModal">
                                    <i class="fas fa-store-alt mr-1"></i> Ringkasan Semua Gerai Hari Ini
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm font-weight-bold">
                                    <i class="fas fa-filter mr-1"></i> Terapkan Filter
                                </button>
                            </div>
                        </div>
                    </form>

                    @if($recapData)
                        {{-- Progress Bar Laporan Bulan Ini --}}
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small font-weight-bold text-dark">
                                    <i class="far fa-calendar-check text-success mr-1"></i> Progres Pelaporan:
                                    <span class="text-primary font-weight-bold"
                                        id="statReportedDays">{{ $recapData['statistics']['reported_days'] }}</span>
                                    dari {{ $recapData['statistics']['total_days'] }} Hari
                                    ({{ $recapData['statistics']['progress_percent'] }}%)
                                </span>
                                <span class="small text-muted">
                                    Parameter: Jual <b>Rp
                                        {{ number_format($recapData['parameters']['harga_jual'], 0, ',', '.') }}</b> |
                                    Beli <b>Rp
                                        {{ number_format($recapData['parameters']['harga_beli_pertamina'], 2, ',', '.') }}</b> |
                                    Tangki <b>{{ number_format($recapData['parameters']['koefisien_tangki'], 2, ',', '.') }}
                                        L/cm</b> |
                                    Komisi <b>Rp
                                        {{ number_format($recapData['parameters']['komisi_operator_per_liter'], 0, ',', '.') }}/L</b>
                                </span>
                            </div>
                            <div class="progress" style="height: 7px; border-radius: 4px;">
                                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                    id="statProgressBar" role="progressbar"
                                    style="width: {{ $recapData['statistics']['progress_percent'] }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if($recapData)
                {{-- 2. Ringkasan Kartu Metrik Utama --}}
                @php
                    $tot = $recapData['summary']['row_total_35'];
                    $avg = $recapData['summary']['row_averages_closing_36_39'];
                @endphp
                <div class="row kpi-row">
                    {{-- Penjualan Aktual --}}
                    <div class="col-6 col-sm-6 col-lg-3 mb-2 px-1 px-sm-2">
                        <div class="card monitoring-card h-100 border-left border-primary"
                            style="border-left-width: 4px !important;">
                            <div class="card-body p-2 p-sm-3">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Penjualan Aktual</div>
                                <div class="h5 mb-0 font-weight-bold text-dark">
                                    Rp <span
                                        id="cardTotalRpJual">{{ number_format($tot['M_penjualan_aktual_rp'], 0, ',', '.') }}</span>
                                </div>
                                <div class="small text-muted mt-1">
                                    Vol: <b><span
                                            id="cardTotalVolJual">{{ number_format($tot['L_penjualan_aktual_vol'], 3, ',', '.') }}</span>
                                        ℓ</b>
                                    <span class="d-none d-sm-inline">(Avg: {{ number_format($avg['F36_avg_liter'], 2, ',', '.') }} ℓ/hari)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Losses / Gain --}}
                    <div class="col-6 col-sm-6 col-lg-3 mb-2 px-1 px-sm-2">
                        <div class="card monitoring-card h-100 border-left {{ $tot['T_losses_gain_liter'] < 0 ? 'border-danger' : 'border-success' }}"
                            style="border-left-width: 4px !important;">
                            <div class="card-body p-2 p-sm-3">
                                <div
                                    class="text-xs font-weight-bold {{ $tot['T_losses_gain_liter'] < 0 ? 'text-danger' : 'text-success' }} text-uppercase mb-1">
                                    Losses / Gain
                                    <span
                                        class="badge {{ $tot['V_status_losses_gain'] === 'Losses' ? 'badge-danger' : ($tot['V_status_losses_gain'] === 'Gain' ? 'badge-success' : 'badge-secondary') }} ml-1">
                                        {{ $tot['V_status_losses_gain'] }}
                                    </span>
                                </div>
                                <div
                                    class="h5 mb-0 font-weight-bold {{ $tot['T_losses_gain_liter'] < 0 ? 'text-danger' : 'text-success' }}">
                                    <span
                                        id="cardTotalLossesVol">{{ number_format($tot['T_losses_gain_liter'], 3, ',', '.') }}</span>
                                    ℓ
                                </div>
                                <div class="small text-muted mt-1">
                                    HPP: <b>Rp {{ number_format($tot['U_losses_gain_rp'], 0, ',', '.') }}</b>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pengeluaran vs Setoran --}}
                    <div class="col-6 col-sm-6 col-lg-3 mb-2 px-1 px-sm-2">
                        <div class="card monitoring-card h-100 border-left border-warning"
                            style="border-left-width: 4px !important;">
                            <div class="card-body p-2 p-sm-3">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Setoran &amp; Biaya</div>
                                <div class="h5 mb-0 font-weight-bold text-dark">
                                    Rp <span
                                        id="cardTotalSetoran">{{ number_format($avg['AL36_total_disetor'], 0, ',', '.') }}</span>
                                </div>
                                <div class="small text-muted mt-1">
                                    Ops: <b>Rp {{ number_format($tot['AG_total_pengeluaran'], 0, ',', '.') }}</b>
                                    <span class="d-none d-sm-inline">| Closing: <b>Rp {{ number_format($avg['AJ37_setoran_closing'], 0, ',', '.') }}</b></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Saldo Belum Disetor (AP35 / AJ36) --}}
                    <div class="col-6 col-sm-6 col-lg-3 mb-2 px-1 px-sm-2">
                        <div class="card monitoring-card h-100 border-left {{ $tot['AP_total_uang_belum_disetor'] < 0 ? 'border-danger' : 'border-info' }}"
                            style="border-left-width: 4px !important;">
                            <div class="card-body p-2 p-sm-3">
                                <div
                                    class="text-xs font-weight-bold {{ $tot['AP_total_uang_belum_disetor'] < 0 ? 'text-danger' : 'text-info' }} text-uppercase mb-1">
                                    Belum Setor (AP)
                                </div>
                                <div
                                    class="h5 mb-0 font-weight-bold {{ $tot['AP_total_uang_belum_disetor'] < 0 ? 'text-danger' : 'text-dark' }}">
                                    Rp <span
                                        id="cardBelumDisetor">{{ number_format($tot['AP_total_uang_belum_disetor'], 0, ',', '.') }}</span>
                                </div>
                                <div class="small text-muted mt-1">
                                    @if($recapData['summary']['cross_checks']['aj36_equals_ap35'])
                                        <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Sinkron</span>
                                    @else
                                        <span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Selisih</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DYNAMIC TABS NAVIGATION --}}
                <ul class="nav nav-tabs font-weight-bold mb-3 border-bottom-0" id="monitoringTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active py-2 px-4 shadow-sm" id="tab-recap-link" data-toggle="tab" href="#tab-recap"
                            role="tab" aria-controls="tab-recap" aria-selected="true"
                            style="font-size: 13.5px; border-radius: 8px 8px 0 0;">
                            <i class="fas fa-table text-primary mr-2"></i>Pratinjau Rekap Bulanan (49 Kolom Excel)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-2 px-4 shadow-sm" id="tab-daily-link" data-toggle="tab" href="#tab-daily"
                            role="tab" aria-controls="tab-daily" aria-selected="false"
                            style="font-size: 13.5px; border-radius: 8px 8px 0 0;">
                            <i class="fas fa-calendar-alt text-info mr-2"></i>Daftar Laporan Harian (Operasional Input)
                            <span class="badge badge-info ml-1 px-2">{{ $dailyReports->count() }} Laporan</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="monitoringTabsContent">
                    {{-- TAB 1: PRATINJAU REKAP BULANAN (49 KOLOM EXCEL) --}}
                    <div class="tab-pane fade show active" id="tab-recap" role="tabpanel" aria-labelledby="tab-recap-link">
                        {{-- 3. Tabel Rekap Harian 49 Kolom (Persis Sheet REKAP Excel) --}}
                        <div class="card monitoring-card mb-4">
                            <div
                                class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="card-title font-weight-bold text-dark m-0" style="font-size: 16px;">
                                    <i class="fas fa-table text-primary mr-2"></i>{{ $recapData['rekap_title'] }}
                                </h5>
                                <span class="small text-muted">
                                    <span class="badge badge-warning text-dark mr-1">■ Kuning = Hari Ini</span>
                                    <span class="badge badge-light border mr-1">■ Kuning Muda = Hari Minggu</span>
                                    <span class="badge badge-secondary mr-1">■ Abu-abu = Belum Lapor</span>
                                </span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-rekap-container">
                                    <div class="table-responsive">
<table class="table-rekap w-100" id="tableRekapHarian">
                                        <thead>
                                            {{-- Baris Judul 1 (Multi-level) --}}
                                            <tr>
                                                <th rowspan="2" class="sticky-col-1">Tgl</th>
                                                <th rowspan="2" class="sticky-col-2">No</th>
                                                <th rowspan="2" class="sticky-col-3">Tanggal</th>
                                                <th rowspan="2">Totalisator Awal</th>
                                                <th rowspan="2">Totalisator Akhir</th>
                                                <th colspan="2">Teoritis Penjualan</th>
                                                <th colspan="2">TEST PUMP</th>
                                                <th colspan="2">{{ $recapData['kirim_label'] }}</th>
                                                <th colspan="2">Aktual Penjualan (L)</th>
                                                <th rowspan="2">STOK<br>AWAL (ℓ)</th>
                                                <th rowspan="2">Pembelian<br>BBM (ℓ)</th>
                                                <th rowspan="2">Terima<br>BBM (ℓ)</th>
                                                <th rowspan="2">STOK<br>TEORITIS (ℓ)</th>
                                                <th colspan="2">STOK AKTUAL AKHIR</th>
                                                <th rowspan="2">NOMINAL<br>LOSSES/GAIN (ℓ)</th>
                                                <th rowspan="2">NOMINAL<br>RUPIAH</th>
                                                <th rowspan="2">LOSSES / GAIN</th>
                                                <th rowspan="2">STOK<br>AKHIR (ℓ)</th>
                                                <th rowspan="2">Jumlah Actual Penjualan</th>
                                                <th colspan="8">PENGELUARAN</th>
                                                <th rowspan="2">Total<br>Pengeluaran</th>
                                                <th rowspan="2">Keterangan Lain2</th>
                                                <th rowspan="2">Pendapatan (Dikurangi)<br>Pengeluaran</th>
                                                <th colspan="3">JUMLAH DISETORKAN</th>
                                                <th rowspan="2">Selisih Uang Yang<br>Disetorkan dg Setoran</th>
                                                <th rowspan="2">Total uang yang<br>belum disetorkan</th>
                                                <th rowspan="2">KETERANGAN</th>
                                                <th rowspan="2">Tanggal</th>
                                                <th rowspan="2">OMSET KOTOR</th>
                                                <th colspan="2">Losses / Gain (C)</th>
                                                <th rowspan="2">OMSET + LOSSES</th>
                                                <th colspan="2">Penjualan(D) x
                                                    Rp{{ round($recapData['parameters']['komisi_operator_per_liter']) }} (Gaji
                                                    Opr.)</th>
                                                <th rowspan="2">Penjualan + Losses - Pengeluaran</th>
                                                <th rowspan="2" style="background-color: #0f172a;">Aksi</th>
                                            </tr>
                                            {{-- Baris Judul 2 (Sub-headers) --}}
                                            <tr>
                                                <th>Volume (ℓ)</th>
                                                <th>Rupiah</th>
                                                <th>Volume (ℓ)</th>
                                                <th>Rupiah</th>
                                                <th>Volume (ℓ)</th>
                                                <th>Rupiah</th>
                                                <th>Volume (ℓ)</th>
                                                <th>Rupiah</th>
                                                <th>CM</th>
                                                <th>Volume (ℓ)</th>
                                                <th>Ongkos Bongkar</th>
                                                <th>Biaya Transfer</th>
                                                <th>Fotocopy & ATK</th>
                                                <th>Listrik</th>
                                                <th>Air Bersih</th>
                                                <th>Cashback</th>
                                                <th>Internet</th>
                                                <th>Lain-lain</th>
                                                <th>BRI</th>
                                                <th>QRIS BRI</th>
                                                <th>TRANSFER BY OPERATOR</th>
                                                <th>Status</th>
                                                <th>Liter</th>
                                                <th>Operator 1</th>
                                                <th>Operator 2</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyRekapHarian">
                                            @foreach($recapData['rows'] as $d => $r)
                                                @php
                                                    $rowClass = '';
                                                    if ($r['meta']['is_today']) {
                                                        $rowClass = 'row-today';
                                                    } elseif ($r['meta']['is_sunday']) {
                                                        $rowClass = 'row-sunday';
                                                    } elseif (!$r['meta']['has_report']) {
                                                        $rowClass = 'row-unreported';
                                                    }
                                                    if (!empty($r['meta']['is_edited'])) {
                                                        $rowClass .= ' row-edited';
                                                    }
                                                @endphp
                                                <tr class="{{ $rowClass }}" id="row-day-{{ $d }}">
                                                    <td class="text-center sticky-col-1 font-weight-bold">{{ $r['A_nomor_hari'] }}
                                                    </td>
                                                    <td class="text-center sticky-col-2">{{ $r['B_no_urut'] }}</td>
                                                    <td class="sticky-col-3">
                                                        {{ $r['C_tanggal'] }}
                                                        @if($r['meta']['is_today'])
                                                            <span class="badge badge-warning text-dark ml-1">Hari Ini</span>
                                                        @endif
                                                        @if(!empty($r['meta']['is_input_edited']))
                                                            <span class="badge badge-info text-white ml-1"
                                                                title="Input operator diubah pada {{ $r['meta']['updated_at_formatted'] ?? '' }}"><i
                                                                    class="fas fa-edit mr-1"></i>Input Diedit</span>
                                                        @elseif(!empty($r['meta']['is_cascade_affected']))
                                                            <span class="badge badge-warning text-dark ml-1"
                                                                title="Nilai terdampak rekalkulasi kaskade perubahan tanggal sebelumnya"><i
                                                                    class="fas fa-layer-group mr-1"></i>Nilai Berubah (Kaskade)</span>
                                                        @endif
                                                        @if(!$r['meta']['has_report'])
                                                            <span class="badge badge-light border text-muted ml-1">Belum Lapor</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-right">
                                                        {{ number_format($r['D_totalisator_awal'], 3, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">
                                                        {{ number_format($r['E_totalisator_akhir'], 3, ',', '.') }}
                                                    </td>
                                                    <td class="text-right font-weight-bold">
                                                        {{ number_format($r['F_penjualan_teoritis_vol'], 3, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($r['G_penjualan_teoritis_rp'], 0, ',', '.') }}
                                                    </td>
                                                    <td
                                                        class="text-right {{ $r['H_test_pump_vol'] > 0 ? 'text-primary font-weight-bold' : '' }}">
                                                        {{ number_format($r['H_test_pump_vol'], 3, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp {{ number_format($r['I_test_pump_rp'], 0, ',', '.') }}
                                                    </td>
                                                    <td
                                                        class="text-right {{ $r['J_bbm_keluar_lain_vol'] > 0 ? 'text-info font-weight-bold' : '' }}">
                                                        {{ number_format($r['J_bbm_keluar_lain_vol'], 3, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($r['K_bbm_keluar_lain_rp'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right font-weight-bold text-success">
                                                        {{ number_format($r['L_penjualan_aktual_vol'], 3, ',', '.') }}
                                                    </td>
                                                    <td class="text-right font-weight-bold">Rp
                                                        {{ number_format($r['M_penjualan_aktual_rp'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">{{ number_format($r['N_stok_awal'], 3, ',', '.') }}</td>
                                                    <td
                                                        class="text-right {{ $r['O_pembelian_bbm'] > 0 ? 'font-weight-bold text-primary' : '' }}">
                                                        {{ number_format($r['O_pembelian_bbm'], 0, ',', '.') }}
                                                    </td>
                                                    <td
                                                        class="text-right {{ $r['P_terima_bbm'] > 0 ? 'font-weight-bold text-success' : '' }}">
                                                        {{ number_format($r['P_terima_bbm'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">{{ number_format($r['Q_stok_teoritis'], 3, ',', '.') }}
                                                    </td>
                                                    <td class="text-right font-weight-bold">
                                                        {{ number_format($r['R_stok_aktual_cm'], 2, ',', '.') }}
                                                    </td>
                                                    <td class="text-right font-weight-bold">
                                                        {{ number_format($r['S_stok_aktual_vol'], 3, ',', '.') }}
                                                    </td>
                                                    <td
                                                        class="text-right font-weight-bold {{ $r['T_losses_gain_liter'] < 0 ? 'text-val-negative' : ($r['T_losses_gain_liter'] > 0 ? 'text-val-positive' : '') }}">
                                                        {{ number_format($r['T_losses_gain_liter'], 3, ',', '.') }}
                                                    </td>
                                                    <td
                                                        class="text-right {{ $r['U_losses_gain_rp'] < 0 ? 'text-val-negative' : '' }}">
                                                        Rp {{ number_format($r['U_losses_gain_rp'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-center font-weight-bold">
                                                        <span
                                                            class="badge {{ $r['V_status_losses_gain'] === 'Losses' ? 'badge-danger' : ($r['V_status_losses_gain'] === 'Gain' ? 'badge-success' : 'badge-secondary') }}">
                                                            {{ $r['V_status_losses_gain'] }}
                                                        </span>
                                                    </td>
                                                    <td class="text-right">{{ number_format($r['W_stok_akhir'], 3, ',', '.') }}</td>
                                                    <td class="text-right font-weight-bold">Rp
                                                        {{ number_format($r['X_jumlah_actual_penjualan'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($r['Y_ongkos_bongkar'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($r['Z_biaya_transfer'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($r['AA_fotocopy_atk'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp {{ number_format($r['AB_listrik'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp {{ number_format($r['AC_air_bersih'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp {{ number_format($r['AD_cashback'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp {{ number_format($r['AE_internet'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp {{ number_format($r['AF_lain_lain'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right font-weight-bold text-danger">Rp
                                                        {{ number_format($r['AG_total_pengeluaran'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="small text-muted">{{ $r['AH_keterangan_lain_lain'] ?: '-' }}</td>
                                                    <td class="text-right font-weight-bold">Rp
                                                        {{ number_format($r['AJ_pendapatan_minus_pengeluaran'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp {{ number_format($r['AL_setoran_bri'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($r['AM_setoran_qris_bri'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($r['AN_setoran_transfer_operator'], 0, ',', '.') }}
                                                    </td>
                                                    <td
                                                        class="text-right font-weight-bold {{ $r['AO_selisih_setoran'] < 0 ? 'text-val-negative' : 'text-val-positive' }}">
                                                        Rp {{ number_format($r['AO_selisih_setoran'], 0, ',', '.') }}
                                                    </td>
                                                    <td
                                                        class="text-right font-weight-bold {{ $r['AP_total_uang_belum_disetor'] < 0 ? 'text-val-negative' : '' }}">
                                                        Rp {{ number_format($r['AP_total_uang_belum_disetor'], 0, ',', '.') }}
                                                    </td>
                                                    <td class="small">{{ $r['AQ_keterangan_tanggal'] }}</td>
                                                    <td class="small">{{ $r['AR_tanggal_gaji'] }}</td>
                                                    <td class="text-right">{{ number_format($r['AS_omset_kotor'], 3, ',', '.') }}
                                                    </td>
                                                    <td class="text-center">{{ $r['AT_status_gaji'] }}</td>
                                                    <td class="text-right">
                                                        {{ number_format($r['AU_losses_gain_gaji'], 3, ',', '.') }}
                                                    </td>
                                                    <td class="text-right font-weight-bold">
                                                        {{ number_format($r['AV_omset_plus_losses'], 3, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp {{ number_format($r['AW_gaji_harian'], 2, ',', '.') }}
                                                    </td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($r['AX_gaji_harian_operator_2'], 2, ',', '.') }}
                                                    </td>
                                                    <td class="text-right font-weight-bold">Rp
                                                        {{ number_format($r['AY_total_gaji_harian'], 2, ',', '.') }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if($r['meta']['has_report'])
                                                            <a href="{{ route('daily-reports.edit', $r['meta']['report_id']) }}"
                                                                target="_blank" class="btn btn-xs btn-outline-primary"
                                                                title="Koreksi Laporan">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        @else
                                                            <a href="{{ route('daily-reports.create', ['shop_id' => $selectedShopId, 'date' => $r['meta']['raw_date']]) }}"
                                                                target="_blank" class="btn btn-xs btn-outline-success"
                                                                title="Input Manual">
                                                                <i class="fas fa-plus"></i>
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach

                                            {{-- 4. Baris Total (Baris Rekap 35 di Excel) --}}
                                            <tr class="row-total-35">
                                                <td colspan="5" class="text-center font-weight-bold sticky-col-3"
                                                    style="background-color: #cbd5e1 !important;">TOTAL</td>
                                                <td class="text-right">
                                                    {{ number_format($tot['F_penjualan_teoritis_vol'], 3, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['G_penjualan_teoritis_rp'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">{{ number_format($tot['H_test_pump_vol'], 3, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['I_test_pump_rp'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">
                                                    {{ number_format($tot['J_bbm_keluar_lain_vol'], 3, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['K_bbm_keluar_lain_rp'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right text-success">
                                                    {{ number_format($tot['L_penjualan_aktual_vol'], 3, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['M_penjualan_aktual_rp'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-center">-</td>
                                                <td class="text-right">{{ number_format($tot['O_pembelian_bbm'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">{{ number_format($tot['P_terima_bbm'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-center">-</td>
                                                <td class="text-center">-</td>
                                                <td class="text-center">-</td>
                                                <td
                                                    class="text-right {{ $tot['T_losses_gain_liter'] < 0 ? 'text-val-negative' : 'text-val-positive' }}">
                                                    {{ number_format($tot['T_losses_gain_liter'], 3, ',', '.') }}
                                                </td>
                                                <td
                                                    class="text-right {{ $tot['U_losses_gain_rp'] < 0 ? 'text-val-negative' : '' }}">
                                                    Rp {{ number_format($tot['U_losses_gain_rp'], 0, ',', '.') }}</td>
                                                <td class="text-center">{{ $tot['V_status_losses_gain'] }}</td>
                                                <td class="text-center">-</td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['X_jumlah_actual_penjualan'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['Y_ongkos_bongkar'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['Z_biaya_transfer'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['AA_fotocopy_atk'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp {{ number_format($tot['AB_listrik'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['AC_air_bersih'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp {{ number_format($tot['AD_cashback'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp {{ number_format($tot['AE_internet'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp {{ number_format($tot['AF_lain_lain'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right text-danger">Rp
                                                    {{ number_format($tot['AG_total_pengeluaran'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-center">-</td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['AJ_pendapatan_minus_pengeluaran'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['AL_setoran_bri'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['AM_setoran_qris_bri'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['AN_setoran_transfer_operator'], 0, ',', '.') }}
                                                </td>
                                                <td
                                                    class="text-right {{ $tot['AO_selisih_setoran'] < 0 ? 'text-val-negative' : '' }}">
                                                    Rp {{ number_format($tot['AO_selisih_setoran'], 0, ',', '.') }}</td>
                                                <td
                                                    class="text-right {{ $tot['AP_total_uang_belum_disetor'] < 0 ? 'text-val-negative' : '' }}">
                                                    Rp {{ number_format($tot['AP_total_uang_belum_disetor'], 0, ',', '.') }}
                                                </td>
                                                <td class="text-center">-</td>
                                                <td class="text-center">-</td>
                                                <td class="text-center">-</td>
                                                <td class="text-center">-</td>
                                                <td class="text-right">
                                                    {{ number_format($tot['AU_losses_gain_gaji'], 3, ',', '.') }}
                                                </td>
                                                <td class="text-right">
                                                    {{ number_format($tot['AV_omset_plus_losses'], 3, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['AW_gaji_harian'], 2, ',', '.') }}
                                                </td>
                                                <td class="text-center">-</td>
                                                <td class="text-right">Rp
                                                    {{ number_format($tot['AY_total_gaji_harian'], 2, ',', '.') }}
                                                </td>
                                                <td class="text-center">-</td>
                                            </tr>

                                            {{-- 5. Baris Rata-rata & Penutupan (Baris 36 - 39) --}}
                                            <tr class="row-extra">
                                                <td colspan="5" class="sticky-col-3">Rata-rata Penjualan Aktual (>0)</td>
                                                <td colspan="6" class="text-left font-weight-bold text-primary">
                                                    {{ number_format($avg['F36_avg_liter'], 3, ',', '.') }} ℓ &nbsp;|&nbsp; Rp
                                                    {{ number_format($avg['G36_avg_rupiah'], 0, ',', '.') }}
                                                </td>
                                                <td colspan="7" class="text-right font-weight-bold">Total Seluruh Setoran
                                                    (AL+AM+AN):</td>
                                                <td colspan="6" class="font-weight-bold text-success">Rp
                                                    {{ number_format($avg['AL36_total_disetor'], 0, ',', '.') }}
                                                </td>
                                                <td colspan="6" class="text-right font-weight-bold">Pecahan 1.000 L:</td>
                                                <td colspan="6">Beli: {{ number_format($avg['O38_pembelian_1kl'], 0) }} ℓ |
                                                    Terima: {{ number_format($avg['P38_terima_1kl'], 0) }} ℓ</td>
                                                <td colspan="12"></td>
                                            </tr>
                                            <tr class="row-extra">
                                                <td colspan="11" class="sticky-col-3 text-right font-weight-bold">Selisih
                                                    Setoran dg Kewajiban (AJ36 = AL - AJ35):</td>
                                                <td colspan="7"
                                                    class="font-weight-bold {{ $avg['AJ36_selisih_disetor'] < 0 ? 'text-danger' : 'text-success' }}">
                                                    Rp {{ number_format($avg['AJ36_selisih_disetor'], 0, ',', '.') }}
                                                    (Harus sama dengan AP35)
                                                </td>
                                                <td colspan="6" class="text-right font-weight-bold">Pecahan 2.000 L:</td>
                                                <td colspan="6">Beli: {{ number_format($avg['O39_pembelian_2kl'], 0) }} ℓ |
                                                    Terima: {{ number_format($avg['P39_terima_2kl'], 0) }} ℓ</td>
                                                <td colspan="18"></td>
                                            </tr>

                                            {{-- Pembagian Gaji Operator (Baris 37-39) --}}
                                            @foreach($recapData['summary']['operator_salaries'] as $opSal)
                                                <tr class="row-extra" style="background-color: #ecfdf5 !important;">
                                                    <td colspan="36" class="sticky-col-3 text-right font-weight-bold text-dark">
                                                        Gaji {{ $opSal['operator_name'] }} (Komisi Rp
                                                        {{ number_format($opSal['rate_komisi'], 0) }}/L):
                                                    </td>
                                                    <td colspan="4" class="text-right font-weight-bold text-primary">
                                                        {{ number_format($opSal['total_av'], 3, ',', '.') }} ℓ
                                                    </td>
                                                    <td colspan="8" class="text-right font-weight-bold text-success"
                                                        style="font-size: 13px;">
                                                        Rp {{ number_format($opSal['gaji'], 2, ',', '.') }}
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 2: DAFTAR LAPORAN HARIAN (OPERASIONAL INPUT) --}}
                    <div class="tab-pane fade" id="tab-daily" role="tabpanel" aria-labelledby="tab-daily-link">
                        <div class="card monitoring-card mb-4">
                            <div
                                class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title font-weight-bold text-dark m-0" style="font-size: 16px;">
                                        <i class="fas fa-list text-info mr-2"></i>Daftar Laporan Harian Operasional
                                    </h5>
                                    <p class="text-muted small mb-0 mt-1">
                                        Semua laporan harian masuk gerai <strong>{{ $recapData['shop']->nama }}</strong> pada
                                        periode
                                        <strong>{{ \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->translatedFormat('F Y') }}</strong>
                                    </p>
                                </div>
                                @if(!$isLocked)
                                    <a href="{{ route('daily-reports.create') }}"
                                        class="btn btn-primary btn-sm font-weight-bold shadow-sm">
                                        <i class="fas fa-plus mr-1"></i> Tambah Laporan Harian
                                    </a>
                                @else
                                    <span class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 11.5px;">
                                        <i class="fas fa-lock mr-1"></i> Periode Terkunci — Pengisian Ditutup
                                    </span>
                                @endif
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0 text-sm">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="text-center" style="width: 45px;">No</th>
                                                <th>Tanggal Laporan</th>
                                                <th>Operator Shift</th>
                                                <th class="text-right">Totalisator Awal</th>
                                                <th class="text-right">Totalisator Akhir</th>
                                                <th class="text-right">Penjualan Aktual (L)</th>
                                                <th class="text-right">Penerimaan BBM</th>
                                                <th class="text-right">Pengeluaran</th>
                                                <th class="text-right">Disetorkan</th>
                                                <th class="text-center">Status Laporan</th>
                                                <th class="text-center" style="width: 130px;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($dailyReports as $idx => $dr)
                                                @php
                                                    $tgl = $dr->report_date ? \Carbon\Carbon::parse($dr->report_date) : \Carbon\Carbon::parse($dr->created_at);
                                                    $isSunday = $tgl->isSunday();
                                                @endphp
                                                <tr class="{{ $isSunday ? 'row-sunday' : '' }}">
                                                    <td class="text-center font-weight-bold">{{ $idx + 1 }}</td>
                                                    <td>
                                                        <strong>{{ $tgl->format('d/m/Y') }}</strong>
                                                        <span
                                                            class="text-muted small d-block">{{ $tgl->translatedFormat('l') }}</span>
                                                    </td>
                                                    <td>
                                                        <i
                                                            class="fas fa-user-circle text-muted mr-1"></i>{{ $dr->operator?->user?->name ?? 'Operator' }}
                                                    </td>
                                                    <td class="text-right">{{ number_format($dr->totalisator_awal, 2, ',', '.') }}
                                                    </td>
                                                    <td class="text-right font-weight-bold">
                                                        {{ number_format($dr->totalisator_akhir, 2, ',', '.') }}
                                                    </td>
                                                    <td class="text-right font-weight-bold text-primary">
                                                        {{ number_format($dr->volume_penjualan_aktual ?? ($dr->totalisator_akhir - $dr->totalisator_awal), 2, ',', '.') }}
                                                        L
                                                    </td>
                                                    <td class="text-right">
                                                        @php $terimaSum = $dr->incomings->sum('volume'); @endphp
                                                        {{ $terimaSum > 0 ? number_format($terimaSum, 0, ',', '.') . ' L' : '-' }}
                                                    </td>
                                                    <td class="text-right text-danger">
                                                        @php $spendSum = $dr->spendings->sum('nominal'); @endphp
                                                        {{ $spendSum > 0 ? 'Rp ' . number_format($spendSum, 0, ',', '.') : '-' }}
                                                    </td>
                                                    <td class="text-right text-success font-weight-bold">
                                                        {{ $dr->disetorkan > 0 ? 'Rp ' . number_format($dr->disetorkan, 0, ',', '.') : '-' }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if($isLocked)
                                                            <span class="badge badge-success px-2 py-1"><i
                                                                    class="fas fa-lock mr-1"></i>Terkunci</span>
                                                        @elseif($dr->status_lifecycle === 'verified')
                                                            <span class="badge badge-info px-2 py-1">Terverifikasi</span>
                                                        @else
                                                            <span class="badge badge-secondary px-2 py-1">Draft</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="{{ route('daily-reports.show', $dr->id) }}"
                                                            class="btn btn-xs btn-outline-info mr-1" title="Lihat Rincian Laporan">
                                                            <i class="fas fa-eye"></i> Detail
                                                        </a>
                                                        @if(!$isLocked)
                                                            <a href="{{ route('daily-reports.edit', $dr->id) }}"
                                                                class="btn btn-xs btn-outline-primary" title="Edit Laporan">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="11" class="text-center text-muted py-5">
                                                        <i class="fas fa-calendar-times fa-2x mb-2 d-block text-secondary"></i>
                                                        Belum ada laporan harian yang diisi pada periode {{ $yearMonth }}.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Modal Ringkasan Semua Gerai Hari Ini --}}
    <div class="modal fade" id="multiShopModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title font-weight-bold" style="font-size: 16px;">
                        <i class="fas fa-store-alt mr-2"></i>Status Laporan Semua Gerai Hari Ini
                        ({{ \Carbon\Carbon::today()->translatedFormat('d F Y') }})
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 text-sm">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th>Cabang / Gerai</th>
                                    <th>Badan Usaha</th>
                                    <th class="text-center">Status Hari Ini</th>
                                    <th class="text-right">Penjualan Hari Ini</th>
                                    <th class="text-right">Stok Terakhir</th>
                                    <th class="text-right">Uang Belum Disetor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($multiShopOverview as $item)
                                    <tr>
                                        <td class="font-weight-bold">
                                            <a
                                                href="{{ route('monitoring.daily-reports', ['shop_id' => $item['shop_id'], 'month' => $selectedMonth, 'year' => $selectedYear]) }}">
                                                {{ $item['shop_nama'] }}
                                            </a>
                                            <span class="small text-muted d-block">Kode: {{ $item['shop_kode'] }}</span>
                                        </td>
                                        <td>{{ $item['corporation_nama'] }}</td>
                                        <td class="text-center">
                                            @if($item['has_today_report'])
                                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i>Sudah
                                                    Lapor</span>
                                            @else
                                                <span class="badge badge-warning text-dark px-2 py-1"><i
                                                        class="fas fa-clock mr-1"></i>Belum Lapor</span>
                                            @endif
                                        </td>
                                        <td class="text-right font-weight-bold">
                                            {{ number_format($item['today_liter_jual'], 2, ',', '.') }} ℓ
                                            <span class="small text-muted d-block">Rp
                                                {{ number_format($item['today_rupiah_jual'], 0, ',', '.') }}</span>
                                        </td>
                                        <td class="text-right">{{ number_format($item['latest_stok_liter'], 2, ',', '.') }} ℓ
                                        </td>
                                        <td
                                            class="text-right font-weight-bold {{ $item['uang_belum_disetor'] < 0 ? 'text-danger' : 'text-dark' }}">
                                            Rp {{ number_format($item['uang_belum_disetor'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Tidak ada data gerai.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let lastKnownTimestamp = null;
        let isPollingActive = true;
        let pollingIntervalMs = 15000; // 15 detik

        function triggerRefresh() {
            fetchLatestData(true);
        }

        function fetchLatestData(force = false) {
            const shopId = document.getElementById('shop_id')?.value;
            const month = document.getElementById('month')?.value;
            const year = document.getElementById('year')?.value;

            if (!shopId) return;

            const url = new URL("{{ route('monitoring.daily-reports.data') }}", window.location.origin);
            url.searchParams.set('shop_id', shopId);
            url.searchParams.set('month', month);
            url.searchParams.set('year', year);
            if (!force && lastKnownTimestamp) {
                url.searchParams.set('last_timestamp', lastKnownTimestamp);
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(res => {
                    if (res.has_changes) {
                        lastKnownTimestamp = res.timestamp;
                        updateViewWithData(res.recap);
                    }
                })
                .catch(err => console.warn('Polling check error:', err));
        }

        function updateViewWithData(recap) {
            if (!recap) return;

            // Update progress bar & stats
            const statReportedDays = document.getElementById('statReportedDays');
            const statProgressBar = document.getElementById('statProgressBar');
            if (statReportedDays && recap.statistics) {
                statReportedDays.innerText = recap.statistics.reported_days;
            }
            if (statProgressBar && recap.statistics) {
                statProgressBar.style.width = recap.statistics.progress_percent + '%';
            }

            // Update summary cards
            if (recap.summary && recap.summary.row_total_35) {
                const tot = recap.summary.row_total_35;
                const avg = recap.summary.row_averages_closing_36_39;

                const elRpJual = document.getElementById('cardTotalRpJual');
                if (elRpJual) elRpJual.innerText = new Intl.NumberFormat('id-ID').format(tot.M_penjualan_aktual_rp);

                const elVolJual = document.getElementById('cardTotalVolJual');
                if (elVolJual) elVolJual.innerText = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 3 }).format(tot.L_penjualan_aktual_vol);

                const elLosses = document.getElementById('cardTotalLossesVol');
                if (elLosses) elLosses.innerText = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 3 }).format(tot.T_losses_gain_liter);

                const elSetoran = document.getElementById('cardTotalSetoran');
                if (elSetoran) elSetoran.innerText = new Intl.NumberFormat('id-ID').format(avg.AL36_total_disetor);

                const elBelumDisetor = document.getElementById('cardBelumDisetor');
                if (elBelumDisetor) elBelumDisetor.innerText = new Intl.NumberFormat('id-ID').format(tot.AP_total_uang_belum_disetor);
            }
        }

        // Jalankan timer polling otomatis tiap 15 detik
        setInterval(function () {
            if (isPollingActive) {
                fetchLatestData(false);
            }
        }, pollingIntervalMs);
    </script>
@endpush