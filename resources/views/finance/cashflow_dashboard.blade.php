@extends('layouts._new_admin')
@section('title', 'Dashboard Cash Flow & Analisis Keuangan')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Dashboard Cash Flow &amp; Analisis Keuangan</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Proyeksi arus kas, posisi modal, dan estimasi kebutuhan stok BBM</p>
  </div>
  <div>
    <form method="GET" action="{{ route('finance.cashflow') }}" class="d-flex align-items-center gap-2">
      <select name="shop_id" class="form-control form-control-sm mr-2" style="border-radius: 8px;" onchange="this.form.submit()">
        @foreach($shops as $shop)
          <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
        @endforeach
      </select>
      <input type="month" name="bulan" class="form-control form-control-sm" style="border-radius: 8px;" value="{{ $selectedMonth }}" onchange="this.form.submit()">
    </form>
  </div>
</div>

{{-- WARNING ANOMALI BILA ADA --}}
@if(!empty($anomalies))
<div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius: 10px; border: none; background: #fee2e2; color: #b91c1c;">
  <div style="font-weight: 700; font-size: 13px; margin-bottom: 4px;">Terdeteksi {{ count($anomalies) }} Anomali pada Laporan Bulan Ini:</div>
  <ul class="mb-0 pl-3" style="font-size: 12.5px;">
    @foreach($anomalies as $anom)
      <li><strong>Tanggal {{ $anom['tanggal'] }}:</strong> {{ implode(', ', $anom['reasons']) }}</li>
    @endforeach
  </ul>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif

{{-- PREDIKSI KEBUTUHAN STOK BBM --}}
<div class="panel mb-4">
  <div class="panel-head">
    <div class="panel-title">Prediksi Kebutuhan Stok BBM &amp; Rekomendasi Pemesanan SO</div>
    <div>
      @if($stockPrediction['is_critical'])
        <span class="status-pill" style="background:#fee2e2; color:#b91c1c;">KRITIS — Perlu Pemesanan Ulang</span>
      @else
        <span class="status-pill" style="background:#dcfce7; color:#15803d;">Stok Aman</span>
      @endif
    </div>
  </div>
  <div class="row cols-1-1-1" style="grid-template-columns: repeat(4, 1fr);">
    <div class="text-center py-2 border-right">
      <div class="text-muted" style="font-size: 11px; text-transform: uppercase;">Penjualan Harian Rata-rata</div>
      <div style="font-size: 18px; font-weight: 800; color: var(--ink);" class="mt-1">{{ number_format($stockPrediction['avg_daily_sales'], 1, ',', '.') }} L/hari</div>
    </div>
    <div class="text-center py-2 border-right">
      <div class="text-muted" style="font-size: 11px; text-transform: uppercase;">Stok Tangki Aktual</div>
      <div style="font-size: 18px; font-weight: 800; color: var(--blue);" class="mt-1">{{ number_format($stockPrediction['current_stok'], 1, ',', '.') }} L</div>
    </div>
    <div class="text-center py-2 border-right">
      <div class="text-muted" style="font-size: 11px; text-transform: uppercase;">Estimasi Sisa Hari Stok</div>
      <div style="font-size: 18px; font-weight: 800; color: {{ $stockPrediction['is_critical'] ? 'var(--red)' : 'var(--green)' }};" class="mt-1">
        {{ $stockPrediction['days_remaining'] }} Hari
      </div>
    </div>
    <div class="text-center py-2">
      <div class="text-muted" style="font-size: 11px; text-transform: uppercase;">Saran Tgl Order &amp; Volume SO</div>
      <div style="font-size: 18px; font-weight: 800; color: var(--amber);" class="mt-1">
        {{ $stockPrediction['recommended_order_date'] ?? '-' }} (±{{ number_format($stockPrediction['suggested_volume'], 0, ',', '.') }} L)
      </div>
    </div>
  </div>
</div>

{{-- STATISTIK CASHFLOW BULANAN --}}
<div class="summary-grid mb-4">
  <div class="metric-card pos">
    <div class="metric-label">Total Pendapatan Bersih</div>
    <div class="metric-value pos">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</div>
    <div class="metric-sub">Bulan berjalan</div>
  </div>
  <div class="metric-card neutral">
    <div class="metric-label">Total Cash Disetorkan</div>
    <div class="metric-value" style="color: var(--blue);">Rp {{ number_format($totalDisetorkan, 0, ',', '.') }}</div>
    <div class="metric-sub">Laporan harian</div>
  </div>
  <div class="metric-card neg">
    <div class="metric-label">Total Biaya Operasional</div>
    <div class="metric-value neg">Rp {{ number_format($totalSpendings, 0, ',', '.') }}</div>
    <div class="metric-sub">Pengeluaran terdistribusi</div>
  </div>
  <div class="metric-card neutral">
    <div class="metric-label">Total Pembelian BBM (SO)</div>
    <div class="metric-value" style="color: var(--amber);">Rp {{ number_format($totalPurchasesNominal, 0, ',', '.') }}</div>
    <div class="metric-sub">Pembelian Pertamina</div>
  </div>
</div>

{{-- REKAP POSITION & PURCHASES --}}
<div class="row cols-1-1">
  <div class="panel mb-0">
    <div class="panel-head">
      <div class="panel-title">Posisi Modal Toko</div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <tbody>
          <tr>
            <td style="font-size: 13px; color: var(--muted);">Saldo Awal Modal:</td>
            <td class="text-right font-weight-bold" style="font-size: 13px;">Rp {{ number_format($capitalRecap->saldo_awal ?? 0, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td style="font-size: 13px; color: var(--muted);">Saldo Akhir Modal:</td>
            <td class="text-right font-weight-bold" style="font-size: 13px; color: var(--green);">Rp {{ number_format($capitalRecap->saldo_akhir ?? 0, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td style="font-size: 13px; color: var(--muted);">Total Accum. Profit:</td>
            <td class="text-right font-weight-bold" style="font-size: 13px; color: var(--blue);">Rp {{ number_format($capitalRecap->total_laba ?? 0, 0, ',', '.') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel mb-0">
    <div class="panel-head">
      <div class="panel-title">Riwayat Pembelian BBM (SO Pertamina)</div>
    </div>
    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: var(--page-bg);">
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Tanggal</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Volume</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Total Harga</th>
          </tr>
        </thead>
        <tbody>
          @forelse($purchases as $p)
          <tr>
            <td style="font-size: 13px;">{{ Carbon\Carbon::parse($p->purchase_date)->format('d M Y') }}</td>
            <td style="font-size: 13px;">{{ number_format($p->volume, 0, ',', '.') }} L</td>
            <td style="font-size: 13px; font-weight: 600;">Rp {{ number_format($p->total_harga, 0, ',', '.') }}</td>
          </tr>
          @empty
          <tr><td colspan="3" class="text-center text-muted py-4" style="font-size: 13px;">Belum ada pembelian BBM bulan ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
