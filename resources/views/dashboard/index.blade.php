@extends('layouts._new_admin')
@section('title', 'Dashboard')

@section('content')
@php
  // Consistent color palette
  $palette = [
      '#2f8f52', '#d1453d', '#e0a032', '#3b6ea5', '#8a5fc2', '#2aa9a0',
      '#d97736', '#4b5c6b', '#c96b8e', '#8fb339'
  ];
  $shopColors = [];
  $shopColorsByName = [];
  $idx = 0;
  foreach($shops as $shop) {
      $color = $palette[$idx % count($palette)];
      $shopColors[$shop->id] = $color;
      $shopColorsByName[$shop->nama] = $color;
      $idx++;
  }
  
  $totals = $initial_dashboard['totals'];
  $summaries = $initial_dashboard['summaries'];
  
  if (!function_exists('formatRp')) {
      function formatRp($value) {
          if ($value < 0) {
              return '-Rp' . number_format(abs($value)/1000000, 1, ',', '.') . 'jt';
          }
          return 'Rp' . number_format($value/1000000, 1, ',', '.') . 'jt';
      }
  }
  if (!function_exists('formatRpFull')) {
      function formatRpFull($value) {
          $sign = $value < 0 ? '-' : '';
          return $sign . 'Rp' . number_format(abs($value), 0, ',', '.');
      }
  }
  if (!function_exists('formatVol')) {
      function formatVol($value) {
          return number_format($value, 1, ',', '.') . ' ℓ';
      }
  }
@endphp

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <h1 class="page-title" style="margin: 0;">Dashboard</h1>
  <form id="shopFilterForm" method="GET" action="{{ route('dashboard') }}" style="margin: 0;">
      @if(request('filter'))
          <input type="hidden" name="filter" value="{{ request('filter') }}">
      @endif
      <select name="shop_id" class="form-control" onchange="document.getElementById('shopFilterForm').submit();" style="border-radius: 8px; border: 1.5px solid #dcdfdc; padding: 6px 36px 6px 14px; font-size: 13.5px; font-weight: 500; background: white; color: #1b2420; cursor: pointer; outline: none; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
          <option value="">Semua Pertashop</option>
          @foreach($shops as $s)
              <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>
                  {{ $s->nama }}
              </option>
          @endforeach
      </select>
  </form>
</div>
<!-- SUMMARY CARDS -->
<div class="summary-grid">
  <div class="metric-card {{ $totals['penjualan_bersih'] >= 0 ? 'pos' : 'neg' }}">
    <div class="metric-label">Penjualan Bersih</div>
    <div class="metric-value {{ $totals['penjualan_bersih'] >= 0 ? 'pos' : 'neg' }}">{{ formatRp($totals['penjualan_bersih']) }}</div>
    <div class="metric-sub">Bulan berjalan</div>
  </div>
  <div class="metric-card neutral">
    <div class="metric-label">Pembelian</div>
    <div class="metric-value">{{ formatRp($totals['pembelian']) }}</div>
    <div class="metric-sub">Bulan berjalan</div>
  </div>
  <div class="metric-card {{ $totals['laba_kotor'] >= 0 ? 'pos' : 'neg' }}">
    <div class="metric-label">Laba Kotor</div>
    <div class="metric-value {{ $totals['laba_kotor'] >= 0 ? 'pos' : 'neg' }}">{{ formatRp($totals['laba_kotor']) }}</div>
    <div class="metric-sub">Bulan berjalan</div>
  </div>
  <div class="metric-card neutral">
    <div class="metric-label">Volume Penjualan</div>
    <div class="metric-value">{{ formatVol($totals['volume']) }}</div>
    <div class="metric-sub">Total volume</div>
  </div>
  <div class="metric-card {{ $totals['losses_gain_vol'] >= 0 ? 'pos' : 'neg' }}">
    <div class="metric-label">Losses</div>
    <div class="metric-value {{ $totals['losses_gain_vol'] >= 0 ? 'pos' : 'neg' }}">{{ formatVol($totals['losses_gain_vol']) }}</div>
    <div class="metric-sub">{{ formatRpFull($totals['losses_gain_rp']) }}</div>
  </div>
  <div class="metric-card {{ $totals['laba_bersih'] >= 0 ? 'pos' : 'neg' }}">
    <div class="metric-label">Laba Bersih</div>
    <div class="metric-value {{ $totals['laba_bersih'] >= 0 ? 'pos' : 'neg' }}">{{ formatRp($totals['laba_bersih']) }}</div>
    <div class="metric-sub">Corporate &amp; investor</div>
  </div>
</div>

<!-- PRICE CARDS -->
<div class="outlet-price-grid mb-4">
  @foreach($summaries as $s)
    @php
      $s_name = $s['shop']->nama ?? '-';
      $s_color = $shopColors[$s['shop']->id ?? 0] ?? '#767f76';
      $hj = $s['harga_jual_aktif'] ?? 0;
      $hb = $s['harga_beli_aktif'] ?? 0;
      $margin = $hj - $hb;
      $berlaku = $s['effective_at'] ? \Carbon\Carbon::parse($s['effective_at'])->format('d M Y') : '-';
    @endphp
    <div class="outlet-price-card">
      <div class="d-flex align-items-center gap-2 mb-2">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $s_color }}; flex-shrink: 0;"></span>
        <span class="shop-name font-weight-bold text-truncate" style="font-size: 13px; color: #1b2420;">{{ $s_name }}</span>
      </div>
      <div class="d-flex justify-content-between price-row" style="font-size: 11px; color: #767f76; margin-bottom: 2px;">
        <span>Harga Jual</span>
        <span style="color: #2f8f52; font-weight: 600;">Rp {{ number_format($hj, 0, ',', '.') }}</span>
      </div>
      <div class="d-flex justify-content-between price-row" style="font-size: 11px; color: #767f76; margin-bottom: 2px;">
        <span>Harga Beli</span>
        <span style="color: #1b2420; font-weight: 600;">Rp {{ number_format($hb, 0, ',', '.') }}</span>
      </div>
      <div class="d-flex justify-content-between price-row" style="font-size: 11px; font-weight: 600; margin-bottom: 6px;">
        <span>Margin</span>
        <span style="color: {{ $margin >= 0 ? '#2f8f52' : '#d1453d' }};">{{ $margin < 0 ? '-' : '' }}Rp {{ number_format(abs($margin), 0, ',', '.') }}</span>
      </div>
      <div class="text-muted" style="font-size: 10px;">
        Berlaku: {{ $berlaku }}
      </div>
    </div>
  @endforeach
</div>

<!-- GRAFIK + STOK -->
<div class="row cols-2-1">
  <div class="panel" style="background: white;">
      <div class="panel-head">
        <div class="panel-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5M4 19h16M8 15l3-4 3 3 5-7"/></svg>
          Grafik Penjualan
        </div>
        <div class="custom-dropdown" tabindex="0">
          <div class="dropdown-selected">
            {{ request('filter', 'month') == 'week' ? 'Mingguan' : (request('filter', 'month') == 'day' ? 'Harian' : 'Bulanan') }}
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
          </div>
          <div class="dropdown-options">
            <a href="?filter=day" class="{{ request('filter', 'day') == 'day' ? 'active' : '' }}">Harian</a>
            <a href="?filter=week" class="{{ request('filter') == 'week' ? 'active' : '' }}">Mingguan</a>
            <a href="?filter=month" class="{{ request('filter') == 'month' ? 'active' : '' }}">Bulanan</a>
          </div>
        </div>
      </div>
      <div style="height: 250px; position: relative;">
        <canvas id="salesChart"></canvas>
      </div>
    </div>

  <div class="panel" style="background: white;">
    <div class="panel-head">
      <div class="panel-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-6 9 6v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9z"/></svg>
        Stok (ℓ)
      </div>
    </div>
    <div id="stockList">
      @foreach($initial_dashboard['stocks']['labels'] as $index => $label)
        @php
          $stockVal = $initial_dashboard['stocks']['datasets'][0]['data'][$index] ?? 0;
          $maxVal = $initial_dashboard['stocks']['datasets'][1]['data'][$index] ?? 1500;
          if ($maxVal <= 0) $maxVal = 1500; // prevent div by zero
          $color = $shopColorsByName[$label] ?? '#8854d0';
          $pct = min(100, ($stockVal / $maxVal) * 100);
        @endphp
        <div class="stock-item">
          <div class="stock-top">
            <span class="dot-label"><span class="dot" style="background:{{ $color }}"></span>{{ $label }}</span>
            <span>{{ number_format($stockVal, 1, ',', '.') }} ℓ</span>
          </div>
          <div class="stock-bar-bg"><div class="stock-bar-fill" style="width:{{ $pct }}%; background:{{ $color }}"></div></div>
        </div>
      @endforeach
    </div>
  </div>
</div>

<!-- DONUTS -->
<div class="row cols-1-1">
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">Omzet per Pertashop</div>
    </div>
    <div class="donut-row">
      <canvas id="omzetChart"></canvas>
      <div class="legend-list" id="omzetLegend"></div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">Laba Kotor per Pertashop</div>
    </div>
    <div class="donut-row">
      <canvas id="labaChart"></canvas>
      <div class="legend-list" id="labaLegend"></div>
    </div>
  </div>
</div>

<!-- LAPORAN TERBARU + PROFIT SHARING + GAIN/LOSS -->
<div class="row cols-1-1-1">
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">Laporan Terbaru</div>
      <a href="#" class="panel-link">Lihat Semua</a>
    </div>
    <div id="reportList">
      @forelse($recent_reports as $r)
        <div class="report-item">
          <div>
            <div class="outlet">{{ $r->shop->kode ?? '' }} {{ $r->shop->nama ?? '' }}</div>
            <div class="date">{{ $r->created_at->format('d M Y, H:i') }}</div>
          </div>
          <span class="status-pill">Selesai</span>
        </div>
      @empty
        <div class="empty-hint">Belum ada laporan harian.</div>
      @endforelse
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><div class="panel-title">Profit Sharing</div></div>
    <div class="kv-row total"><div class="k">Laba Bersih Total</div><div class="v {{ $totals['laba_bersih'] >= 0 ? 'pos' : 'neg' }}">{{ formatRpFull($totals['laba_bersih']) }}</div></div>
    <div class="kv-row"><div class="k"><span class="dot" style="background:var(--blue)"></span>Corporate Share</div><div class="v {{ $totals['corporate_share'] >= 0 ? 'pos' : 'neg' }}">{{ formatRpFull($totals['corporate_share']) }}</div></div>
    <div class="kv-row"><div class="k"><span class="dot" style="background:var(--amber)"></span>Investor Share</div><div class="v {{ $totals['investor_share'] >= 0 ? 'pos' : 'neg' }}">{{ formatRpFull($totals['investor_share']) }}</div></div>
  </div>

  <div class="panel">
    <div class="panel-head"><div class="panel-title">Gain / Losses</div></div>
    <div class="kv-row total"><div class="k">Total Losses</div><div class="v {{ $totals['losses_gain_vol'] >= 0 ? 'pos' : 'neg' }}">{{ formatVol($totals['losses_gain_vol']) }}</div></div>
    <div class="kv-row"><div class="k">Kerugian Rupiah</div><div class="v {{ $totals['losses_gain_rp'] >= 0 ? 'pos' : 'neg' }}">{{ formatRpFull($totals['losses_gain_rp']) }}</div></div>
    <div id="gainLossList">
      @foreach($summaries as $s)
        @php 
          $s_name = $s['shop']->nama ?? '-';
          $s_color = $shopColors[$s['shop']->id ?? 0] ?? '#767f76';
          $val = $s['total_losses_gain_vol'] ?? 0;
        @endphp
        <div class="kv-row">
          <div class="k"><span class="dot" style="background:{{ $s_color }}"></span>{{ $s_name }}</div>
          <div class="v {{ $val >= 0 ? 'pos' : 'neg' }}">{{ number_format($val, 1, ',', '.') }} ℓ</div>
        </div>
      @endforeach
    </div>
  </div>
</div>

<div class="row cols-1">
  <div class="panel">
    <div class="panel-head"><div class="panel-title">Rincian Setoran & Kas (Per Pertashop)</div></div>
    <div class="table-responsive" style="margin-top: 8px;">
      <table class="table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <thead>
          <tr style="border-bottom: 2px solid #eef0ec; color: #767f76;">
            <th style="padding: 12px 8px; text-align: left; font-weight: 500;">Pertashop</th>
            <th style="padding: 12px 8px; text-align: right; font-weight: 500;">Sudah Disetorkan</th>
            <th style="padding: 12px 8px; text-align: right; font-weight: 500;">Belum Disetorkan</th>
            <th style="padding: 12px 8px; text-align: right; font-weight: 500;">Diambil Kolektan</th>
          </tr>
        </thead>
        <tbody>
          @foreach($summaries as $s)
            @php 
              $s_name = $s['shop']->nama ?? '-';
              $s_id = $s['shop']->id ?? '';
              $s_color = $shopColors[$s_id] ?? '#767f76';
              $link = $s_id ? route('daily-reports.index', ['shop_id' => $s_id]) : '#';
            @endphp
            <tr style="border-bottom: 1px solid #eef0ec;">
              <td style="padding: 12px 8px;">
                <span class="dot" style="background:{{ $s_color }};"></span>
                <a href="{{ $link }}" style="color: #3b6ea5; text-decoration: none; font-weight: 600;">{{ $s_name }}</a>
              </td>
              <td style="padding: 12px 8px; text-align: right;" class="pos">{{ formatRpFull($s['sudah_disetorkan'] ?? 0) }}</td>
              <td style="padding: 12px 8px; text-align: right;" class="{{ ($s['belum_disetorkan'] ?? 0) >= 0 ? 'pos' : 'neg' }}">{{ formatRpFull($s['belum_disetorkan'] ?? 0) }}</td>
              <td style="padding: 12px 8px; text-align: right;" class="neutral">{{ formatRpFull($s['total_setor_kolektan'] ?? 0) }}</td>
            </tr>
          @endforeach
          <tr style="font-weight: 700; background: #fafcfa;">
             <td style="padding: 12px 8px; color: #1b2420;">TOTAL KESELURUHAN</td>
             <td style="padding: 12px 8px; text-align: right;" class="pos">{{ formatRpFull($totals['sudah_disetorkan']) }}</td>
             <td style="padding: 12px 8px; text-align: right;" class="{{ $totals['belum_disetorkan'] >= 0 ? 'pos' : 'neg' }}">{{ formatRpFull($totals['belum_disetorkan']) }}</td>
             <td style="padding: 12px 8px; text-align: right;" class="neutral">{{ formatRpFull($totals['setor_kolektan']) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
  const dashboardData = @json($initial_dashboard);
  const shopColorsByName = @json($shopColorsByName);
  const summaries = dashboardData.summaries || [];

  Chart.defaults.font.family = 'Inter, sans-serif';
  Chart.defaults.color = '#767f76';

  // ---- Grafik Penjualan (line) ----
  const salesLabels = dashboardData.sales.labels;
  const salesDatasets = dashboardData.sales.datasets.map(ds => {
      const color = shopColorsByName[ds.label] || ds.backgroundColor;
      return {
          label: ds.label,
          borderColor: color,
          backgroundColor: color + '22',
          data: ds.data,
          tension: 0.35,
          pointRadius: 0,
          borderWidth: 2,
          fill: false,
      };
  });

  new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
      labels: salesLabels,
      datasets: salesDatasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'top', align: 'start', labels: { boxWidth: 9, boxHeight: 9, usePointStyle: true, font: { size: 11 } } } },
      scales: {
        y: { grid: { color: '#eef0ec' }, ticks: { font: { size: 10 } } },
        x: { grid: { display: false }, ticks: { font: { size: 10 } } }
      }
    }
  });

  // ---- Donut charts (shared categorical palette) ----
  function makeDonut(canvasId, legendId, labels, values, bgColors, formatFn){
    if (values.every(v => v === 0)) {
        // Prevent empty chart rendering weirdly
        document.getElementById(canvasId).style.display = 'none';
        document.getElementById(legendId).innerHTML = '<div class="empty-hint" style="padding:10px">Data belum tersedia</div>';
        return;
    }
    new Chart(document.getElementById(canvasId), {
      type:'doughnut',
      data:{
        labels: labels,
        datasets:[{ data: values, backgroundColor: bgColors, borderWidth:2, borderColor:'#fff' }]
      },
      options:{
        cutout:'68%',
        plugins:{ legend:{ display:false }, tooltip:{ enabled:true } }
      }
    });
    document.getElementById(legendId).innerHTML = labels.map((name, i) => `
      <div class="legend-row">
        <span class="dot-label"><span class="dot" style="background:${bgColors[i]}"></span>${name}</span>
        <span class="val">${formatFn(values[i])}</span>
      </div>
    `).join('');
  }

  const omzetLabels = [];
  const omzetValues = [];
  const labaValues = [];
  const chartColors = [];

  // Map from summaries array
  Object.values(summaries).forEach(s => {
      const name = s.shop ? s.shop.nama : '-';
      omzetLabels.push(name);
      omzetValues.push(s.jumlah_penjualan_bersih_rp || 0);
      labaValues.push(s.laba_kotor || 0);
      chartColors.push(shopColorsByName[name] || '#767f76');
  });

  const rpFormatter = (v) => {
      if (v < 0) return '-Rp' + (Math.abs(v)/1000000).toFixed(1) + 'jt';
      return 'Rp' + (v/1000000).toFixed(1) + 'jt';
  };

  makeDonut('omzetChart', 'omzetLegend', omzetLabels, omzetValues, chartColors, rpFormatter);
  makeDonut('labaChart', 'labaLegend', omzetLabels, labaValues, chartColors, rpFormatter);

</script>
@endpush
