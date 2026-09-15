<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startPush('style'); ?>
<style>
  :root {
    --emerald-light: #ecfdf5;
    --emerald-main: #059669;
    --emerald-dark: #064e3b;
    --rose-light: #fff1f2;
    --rose-main: #e11d48;
    --blue-light: #eff6ff;
    --blue-main: #2563eb;
    --amber-light: #fef3c7;
    --amber-main: #d97706;
    --teal-light: #f0fdfa;
    --teal-main: #0d9488;
  }

  body {
    background-color: #f8fafc;
  }

  /* ─── 1. KPI Metric Cards 6-Column Grid ──────────────────────────────── */
  .kpi-grid-6 {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
    margin-bottom: 24px;
  }

  @media (max-width: 1200px) {
    .kpi-grid-6 {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (max-width: 640px) {
    .kpi-grid-6 {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  .kpi-card {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    padding: 16px 18px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 110px;
    position: relative;
    overflow: hidden;
  }

  .kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
  }

  .kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
  }

  .kpi-card.kpi-emerald::before { background: var(--emerald-main); }
  .kpi-card.kpi-amber::before { background: var(--amber-main); }
  .kpi-card.kpi-blue::before { background: var(--blue-main); }
  .kpi-card.kpi-teal::before { background: var(--teal-main); }
  .kpi-card.kpi-rose::before { background: var(--rose-main); }
  .kpi-card.kpi-dark-emerald::before { background: var(--emerald-dark); }

  .kpi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
  }

  .kpi-label {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
  }

  .kpi-icon-badge {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
  }

  .kpi-val {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.2;
  }

  .kpi-sub {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    margin-top: 4px;
  }

  /* ─── 2. 6-Outlet Grid ───────────────────────────────────────────────── */
  .outlet-grid-6 {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
    margin-bottom: 24px;
  }

  @media (max-width: 1280px) {
    .outlet-grid-6 {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (max-width: 640px) {
    .outlet-grid-6 {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  .outlet-pill-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px 16px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease;
  }

  .outlet-pill-card:hover {
    border-color: #10b981;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.08);
  }

  .live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #10b981;
    display: inline-block;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    animation: pulse-dot 2s infinite;
  }

  @keyframes pulse-dot {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    70% { box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
  }

  /* ─── 3. Dashboard Panels ────────────────────────────────────────────── */
  .dash-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    overflow: hidden;
    margin-bottom: 24px;
  }

  .dash-panel-header {
    padding: 16px 20px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .dash-panel-title {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  /* ─── Stock list items ───────────────────────────────────────────────── */
  .stock-item-row {
    padding: 12px 18px;
    border-bottom: 1px solid #f8fafc;
    transition: background 0.15s ease;
  }

  .stock-item-row:last-child {
    border-bottom: none;
  }

  .stock-item-row:hover {
    background: #f8fafc;
  }

  .stock-progress-track {
    width: 100%;
    height: 7px;
    background: #f1f5f9;
    border-radius: 9999px;
    overflow: hidden;
    margin-top: 6px;
  }

  .stock-progress-fill {
    height: 100%;
    border-radius: 9999px;
    transition: width 0.6s ease;
  }

  /* ─── Modern Tables ──────────────────────────────────────────────────── */
  .dash-table th {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    background: #f8fafc;
    border-top: none !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 12px 16px;
  }

  .dash-table td {
    padding: 13px 16px;
    vertical-align: middle !important;
    font-size: 13px;
    border-bottom: 1px solid #f1f5f9;
  }

  .dash-table tbody tr:hover {
    background-color: #f8fafc;
  }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
  $palette = [
      '#059669', '#2563eb', '#d97706', '#8b5cf6', '#0d9488', '#e11d48',
      '#4b5c6b', '#c96b8e', '#8fb339', '#3b82f6'
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

  $total_beban = max(0, ($totals['laba_kotor'] ?? 0) - ($totals['laba_bersih'] ?? 0));
  
  if (!function_exists('formatRp')) {
      function formatRp($value) {
          if ($value < 0) {
              return '-Rp ' . number_format(abs($value)/1000000, 1, ',', '.') . ' Jt';
          }
          return 'Rp ' . number_format($value/1000000, 1, ',', '.') . ' Jt';
      }
  }
  if (!function_exists('formatRpFull')) {
      function formatRpFull($value) {
          $sign = $value < 0 ? '-' : '';
          return $sign . 'Rp ' . number_format(abs($value), 0, ',', '.');
      }
  }
  if (!function_exists('formatVol')) {
      function formatVol($value) {
          return number_format($value, 1, ',', '.') . ' L';
      }
  }
?>


<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap" style="gap: 16px;">
  <div>
    <h1 class="page-title mb-1" style="font-size: 24px; font-weight: 900; color: #0f172a;">Dashboard Utama</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Executive summary kinerja operasional, penjualan BBM, margin, dan arus kas seluruh cabang.</p>
  </div>

  <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
    <form id="shopFilterForm" method="GET" action="<?php echo e(route('dashboard')); ?>" class="d-flex align-items-center" style="gap: 8px; margin: 0;">
      <?php if(request('filter')): ?>
        <input type="hidden" name="filter" value="<?php echo e(request('filter')); ?>">
      <?php endif; ?>
      <select name="shop_id" class="form-control form-control-sm font-weight-bold" onchange="document.getElementById('shopFilterForm').submit();" style="border-radius: 10px; border: 1.5px solid #cbd5e1; padding: 6px 14px; font-size: 13px; min-width: 170px; background: white; color: #0f172a; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
        <option value="">Semua Pertashop</option>
        <?php $__currentLoopData = $shops; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($s->id); ?>" <?php echo e(request('shop_id') == $s->id ? 'selected' : ''); ?>>
            <?php echo e($s->nama); ?>

          </option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </form>
  </div>
</div>


<div class="kpi-grid-6">
  
  
  <div class="kpi-card kpi-emerald">
    <div class="kpi-header">
      <span class="kpi-label">Penjualan Bersih</span>
      <div class="kpi-icon-badge" style="background: var(--emerald-light); color: var(--emerald-main);">
        <i class="fas fa-chart-line"></i>
      </div>
    </div>
    <div class="kpi-val" style="color: var(--emerald-main);"><?php echo e(formatRp($totals['penjualan_bersih'])); ?></div>
    <div class="kpi-sub">Bulan berjalan</div>
  </div>

  
  <div class="kpi-card kpi-amber">
    <div class="kpi-header">
      <span class="kpi-label">Pembelian BBM</span>
      <div class="kpi-icon-badge" style="background: var(--amber-light); color: var(--amber-main);">
        <i class="fas fa-shopping-cart"></i>
      </div>
    </div>
    <div class="kpi-val" style="color: #b45309;"><?php echo e(formatRp($totals['pembelian'])); ?></div>
    <div class="kpi-sub">Total pengadaan</div>
  </div>

  
  <div class="kpi-card kpi-blue">
    <div class="kpi-header">
      <span class="kpi-label">Volume Terjual</span>
      <div class="kpi-icon-badge" style="background: var(--blue-light); color: var(--blue-main);">
        <i class="fas fa-gas-pump"></i>
      </div>
    </div>
    <div class="kpi-val" style="color: var(--blue-main);"><?php echo e(formatVol($totals['volume'])); ?></div>
    <div class="kpi-sub">Total liter keluar</div>
  </div>

  
  <div class="kpi-card kpi-teal">
    <div class="kpi-header">
      <span class="kpi-label">Laba Kotor</span>
      <div class="kpi-icon-badge" style="background: var(--teal-light); color: var(--teal-main);">
        <i class="fas fa-coins"></i>
      </div>
    </div>
    <div class="kpi-val" style="color: var(--teal-main);"><?php echo e(formatRp($totals['laba_kotor'])); ?></div>
    <div class="kpi-sub">Margin bruto</div>
  </div>

  
  <div class="kpi-card kpi-rose">
    <div class="kpi-header">
      <span class="kpi-label">Beban Biaya</span>
      <div class="kpi-icon-badge" style="background: var(--rose-light); color: var(--rose-main);">
        <i class="fas fa-receipt"></i>
      </div>
    </div>
    <div class="kpi-val" style="color: var(--rose-main);"><?php echo e(formatRp($total_beban)); ?></div>
    <div class="kpi-sub">Operasional &amp; gaji</div>
  </div>

  
  <div class="kpi-card kpi-dark-emerald">
    <div class="kpi-header">
      <span class="kpi-label">Laba Bersih</span>
      <div class="kpi-icon-badge" style="background: #d1fae5; color: var(--emerald-dark);">
        <i class="fas fa-wallet"></i>
      </div>
    </div>
    <div class="kpi-val" style="color: var(--emerald-dark);"><?php echo e(formatRp($totals['laba_bersih'])); ?></div>
    <div class="kpi-sub">Net corporate &amp; investor</div>
  </div>

</div>


<div class="outlet-grid-6">
  <?php $__currentLoopData = $summaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
      $s_name = $s['shop']->nama ?? '-';
      $s_id = $s['shop']->id ?? 0;
      $s_color = $shopColors[$s_id] ?? '#059669';
      $hj = $s['harga_jual_aktif'] ?? 0;
      $hb = $s['harga_beli_aktif'] ?? 0;
      $margin = $hj - $hb;
      $berlaku = $s['effective_at'] ? \Carbon\Carbon::parse($s['effective_at'])->format('d M Y') : '-';
    ?>
    <div class="outlet-pill-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="d-flex align-items-center" style="gap: 6px; overflow: hidden;">
          <span class="live-dot"></span>
          <span class="font-weight-bold text-truncate" style="font-size: 13px; color: #0f172a;" title="<?php echo e($s_name); ?>">
            <?php echo e($s_name); ?>

          </span>
        </div>
        <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 10px; border-radius: 6px;">
          #<?php echo e($s_id); ?>

        </span>
      </div>

      <div class="d-flex justify-content-between mb-1" style="font-size: 11.5px; color: #64748b;">
        <span>Harga Jual:</span>
        <span style="font-weight: 700; color: #059669;">Rp <?php echo e(number_format($hj, 0, ',', '.')); ?></span>
      </div>
      <div class="d-flex justify-content-between mb-1" style="font-size: 11.5px; color: #64748b;">
        <span>Harga Beli:</span>
        <span style="font-weight: 600; color: #334155;">Rp <?php echo e(number_format($hb, 0, ',', '.')); ?></span>
      </div>
      <div class="d-flex justify-content-between pt-1 border-top" style="font-size: 11.5px; border-color: #f1f5f9 !important;">
        <span class="font-weight-bold" style="color: #475569;">Margin:</span>
        <span style="font-weight: 800; color: <?php echo e($margin >= 0 ? '#059669' : '#e11d48'); ?>;">
          <?php echo e($margin < 0 ? '-' : ''); ?>Rp <?php echo e(number_format(abs($margin), 0, ',', '.')); ?>

        </span>
      </div>
      <div class="text-muted mt-1 text-truncate" style="font-size: 10px;">
        Efektif: <?php echo e($berlaku); ?>

      </div>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="row">
  
  
  <div class="col-lg-8 mb-4">
    <div class="dash-panel h-100">
      <div class="dash-panel-header">
        <div class="dash-panel-title">
          <i class="fas fa-chart-area text-success"></i> Grafik Tren Penjualan &amp; Volume
        </div>
        
        <div class="btn-group btn-group-sm" role="group">
          <a href="?filter=day<?php echo e(request('shop_id') ? '&shop_id='.request('shop_id') : ''); ?>" 
             class="btn <?php echo e(request('filter', 'month') == 'day' ? 'btn-success' : 'btn-outline-secondary'); ?>" 
             style="font-size: 11.5px; font-weight: 700; border-radius: 6px 0 0 6px;">Harian</a>
          <a href="?filter=week<?php echo e(request('shop_id') ? '&shop_id='.request('shop_id') : ''); ?>" 
             class="btn <?php echo e(request('filter') == 'week' ? 'btn-success' : 'btn-outline-secondary'); ?>" 
             style="font-size: 11.5px; font-weight: 700;">Mingguan</a>
          <a href="?filter=month<?php echo e(request('shop_id') ? '&shop_id='.request('shop_id') : ''); ?>" 
             class="btn <?php echo e(request('filter', 'month') == 'month' ? 'btn-success' : 'btn-outline-secondary'); ?>" 
             style="font-size: 11.5px; font-weight: 700; border-radius: 0 6px 6px 0;">Bulanan</a>
        </div>
      </div>

      <div style="padding: 20px; position: relative; min-height: 340px; height: 340px;">
        <canvas id="salesChart"></canvas>
      </div>
    </div>
  </div>

  
  <div class="col-lg-4 mb-4">
    <div class="dash-panel h-100">
      <div class="dash-panel-header">
        <div class="dash-panel-title">
          <i class="fas fa-oil-can text-primary"></i> Sisa Stok Tangki (Liter)
        </div>
        <span class="badge badge-success px-2 py-1" style="border-radius: 6px; font-size: 11px;">Aktual</span>
      </div>

      <div style="padding: 8px 0; max-height: 340px; overflow-y: auto;">
        <?php $__currentLoopData = $initial_dashboard['stocks']['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $stockVal = $initial_dashboard['stocks']['datasets'][0]['data'][$index] ?? 0;
            $maxVal = $initial_dashboard['stocks']['datasets'][1]['data'][$index] ?? 1500;
            if ($maxVal <= 0) $maxVal = 1500;
            $color = $shopColorsByName[$label] ?? '#059669';
            $pct = min(100, max(0, ($stockVal / $maxVal) * 100));
          ?>
          <div class="stock-item-row">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <div class="d-flex align-items-center" style="gap: 8px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo e($color); ?>; flex-shrink: 0;"></span>
                <span style="font-size: 13px; font-weight: 700; color: #0f172a;"><?php echo e($label); ?></span>
              </div>
              <span style="font-size: 13px; font-weight: 800; color: #059669;">
                <?php echo e(number_format($stockVal, 1, ',', '.')); ?> L
              </span>
            </div>
            <div class="stock-progress-track">
              <div class="stock-progress-fill" style="width: <?php echo e($pct); ?>%; background: <?php echo e($color); ?>;"></div>
            </div>
            <div class="d-flex justify-content-between mt-1 text-muted" style="font-size: 10.5px;">
              <span>Kapasitas: <?php echo e(number_format($maxVal, 0, ',', '.')); ?> L</span>
              <span><?php echo e(round($pct, 1)); ?>% Terisi</span>
            </div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
  </div>

</div>


<div class="row">
  
  
  <div class="col-lg-7 mb-4">
    <div class="dash-panel mb-4">
      <div class="dash-panel-header">
        <div class="dash-panel-title">
          <i class="fas fa-pie-chart text-success"></i> Proporsi Omzet &amp; Laba Kotor per Cabang
        </div>
      </div>
      <div style="padding: 20px;">
        <div class="row align-items-center">
          <div class="col-md-6 mb-3 mb-md-0 text-center">
            <div style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px;">Omzet Penjualan</div>
            <div style="height: 180px; position: relative;">
              <canvas id="omzetChart"></canvas>
            </div>
            <div class="mt-2 text-muted" style="font-size: 11px;" id="omzetLegend"></div>
          </div>
          <div class="col-md-6 text-center">
            <div style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px;">Laba Kotor Bruto</div>
            <div style="height: 180px; position: relative;">
              <canvas id="labaChart"></canvas>
            </div>
            <div class="mt-2 text-muted" style="font-size: 11px;" id="labaLegend"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  
  <div class="col-lg-5 mb-4">
    <div class="dash-panel mb-4">
      <div class="dash-panel-header">
        <div class="dash-panel-title">
          <i class="fas fa-balance-scale text-primary"></i> Bagi Hasil &amp; Losses BBM
        </div>
      </div>
      <div style="padding: 18px 20px;">
        
        
        <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px;">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="font-size: 12px; font-weight: 700; color: #475569;">Laba Bersih Total:</span>
            <span style="font-size: 15px; font-weight: 800; color: #059669;"><?php echo e(formatRpFull($totals['laba_bersih'])); ?></span>
          </div>
          <div class="d-flex justify-content-between mb-1" style="font-size: 12px;">
            <span class="text-muted"><i class="fas fa-building text-primary mr-1"></i> Corporate Share:</span>
            <span class="font-weight-bold" style="color: #2563eb;"><?php echo e(formatRpFull($totals['corporate_share'])); ?></span>
          </div>
          <div class="d-flex justify-content-between" style="font-size: 12px;">
            <span class="text-muted"><i class="fas fa-users text-warning mr-1"></i> Investor Share:</span>
            <span class="font-weight-bold" style="color: #d97706;"><?php echo e(formatRpFull($totals['investor_share'])); ?></span>
          </div>
        </div>

        
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span style="font-size: 12px; font-weight: 700; color: #475569;">Total Losses / Gain:</span>
          <span style="font-size: 13px; font-weight: 800; color: <?php echo e($totals['losses_gain_vol'] >= 0 ? '#059669' : '#dc2626'); ?>;">
            <?php echo e(formatVol($totals['losses_gain_vol'])); ?> (<?php echo e(formatRpFull($totals['losses_gain_rp'])); ?>)
          </span>
        </div>

      </div>
    </div>
  </div>

</div>


<div class="dash-panel">
  <div class="dash-panel-header">
    <div class="dash-panel-title">
      <i class="fas fa-money-bill-wave text-success"></i> Rincian Arus Kas, Setoran &amp; Kolektan per Cabang
    </div>
  </div>

  <div class="table-responsive">
    <table class="table dash-table mb-0">
      <thead>
        <tr>
          <th>Pertashop / Outlet</th>
          <th class="text-right">Sudah Disetor</th>
          <th class="text-right">Belum Disetor</th>
          <th class="text-right">Diambil Kolektan</th>
          <th class="text-right">Laba Kotor</th>
          <th class="text-center">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php $__currentLoopData = $summaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php 
            $s_name = $s['shop']->nama ?? '-';
            $s_id = $s['shop']->id ?? '';
            $s_color = $shopColors[$s_id] ?? '#059669';
            $link = $s_id ? route('daily-reports.index', ['shop_id' => $s_id]) : '#';
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center" style="gap: 8px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo e($s_color); ?>;"></span>
                <a href="<?php echo e($link); ?>" style="color: #0f172a; font-weight: 700; text-decoration: none;">
                  <?php echo e($s_name); ?>

                </a>
              </div>
            </td>
            <td class="text-right font-weight-bold" style="color: #059669;">
              <?php echo e(formatRpFull($s['sudah_disetorkan'] ?? 0)); ?>

            </td>
            <td class="text-right font-weight-bold" style="color: <?php echo e(($s['belum_disetorkan'] ?? 0) > 0 ? '#d97706' : '#64748b'); ?>;">
              <?php echo e(formatRpFull($s['belum_disetorkan'] ?? 0)); ?>

            </td>
            <td class="text-right font-weight-bold" style="color: #2563eb;">
              <?php echo e(formatRpFull($s['total_setor_kolektan'] ?? 0)); ?>

            </td>
            <td class="text-right font-weight-bold" style="color: #059669;">
              <?php echo e(formatRpFull($s['laba_kotor'] ?? 0)); ?>

            </td>
            <td class="text-center">
              <span class="badge badge-success px-2 py-1" style="border-radius: 6px; font-size: 11px;">Aktif</span>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
      <tfoot style="background: #f8fafc;">
        <tr style="font-weight: 800; font-size: 13.5px;">
          <td style="padding: 14px 16px; color: #0f172a;">TOTAL KESELURUHAN</td>
          <td class="text-right" style="color: #059669;"><?php echo e(formatRpFull($totals['sudah_disetorkan'])); ?></td>
          <td class="text-right" style="color: <?php echo e($totals['belum_disetorkan'] >= 0 ? '#d97706' : '#dc2626'); ?>;"><?php echo e(formatRpFull($totals['belum_disetorkan'])); ?></td>
          <td class="text-right" style="color: #2563eb;"><?php echo e(formatRpFull($totals['setor_kolektan'])); ?></td>
          <td class="text-right" style="color: #059669;"><?php echo e(formatRpFull($totals['laba_kotor'])); ?></td>
          <td class="text-center"><span class="badge badge-primary px-2 py-1" style="border-radius: 6px;">6 Outlet</span></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
  const dashboardData = <?php echo json_encode($initial_dashboard, 15, 512) ?>;
  const shopColorsByName = <?php echo json_encode($shopColorsByName, 15, 512) ?>;
  const summaries = dashboardData.summaries || [];

  Chart.defaults.font.family = 'Inter, sans-serif';
  Chart.defaults.color = '#64748b';

  // ─── 1. Grafik Penjualan Line Chart ──────────────────────────────────────
  const salesLabels = dashboardData.sales.labels;
  const salesDatasets = dashboardData.sales.datasets.map(ds => {
      const color = shopColorsByName[ds.label] || ds.backgroundColor;
      return {
          label: ds.label,
          borderColor: color,
          backgroundColor: color + '15',
          data: ds.data,
          tension: 0.35,
          pointRadius: 2,
          pointHoverRadius: 5,
          borderWidth: 2.5,
          fill: true,
      };
  });

  const salesCtx = document.getElementById('salesChart');
  if (salesCtx) {
    new Chart(salesCtx, {
      type: 'line',
      data: {
        labels: salesLabels,
        datasets: salesDatasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
          legend: { 
            position: 'top', 
            align: 'start', 
            labels: { 
              boxWidth: 8, 
              boxHeight: 8, 
              usePointStyle: true, 
              font: { size: 11.5, weight: '600' } 
            } 
          } 
        },
        scales: {
          y: { 
            grid: { color: '#f1f5f9' }, 
            ticks: { font: { size: 10.5 } } 
          },
          x: { 
            grid: { display: false }, 
            ticks: { font: { size: 10.5 } } 
          }
        }
      }
    });
  }

  // ─── 2. Donut charts ─────────────────────────────────────────────────────
  function makeDonut(canvasId, legendId, labels, values, bgColors, formatFn){
    const canvas = document.getElementById(canvasId);
    const legend = document.getElementById(legendId);
    if (!canvas) return;

    if (values.every(v => v === 0)) {
      canvas.style.display = 'none';
      if (legend) legend.innerHTML = '<div class="text-muted small py-3">Data belum tersedia</div>';
      return;
    }
    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{ data: values, backgroundColor: bgColors, borderWidth: 2, borderColor: '#fff' }]
      },
      options: {
        cutout: '70%',
        plugins: { legend: { display: false }, tooltip: { enabled: true } },
        responsive: true,
        maintainAspectRatio: false
      }
    });
    
    if (legend) {
      legend.innerHTML = labels.map((name, i) => `
        <span class="d-inline-flex align-items-center mr-2 mb-1" style="font-size: 11px;">
          <span style="width: 7px; height: 7px; border-radius: 50%; background: ${bgColors[i]}; margin-right: 4px;"></span>
          ${name} (${formatFn(values[i])})
        </span>
      `).join('');
    }
  }

  const omzetLabels = [];
  const omzetValues = [];
  const labaValues = [];
  const chartColors = [];

  Object.values(summaries).forEach(s => {
      const name = s.shop ? s.shop.nama : '-';
      omzetLabels.push(name);
      omzetValues.push(s.jumlah_penjualan_bersih_rp || 0);
      labaValues.push(s.laba_kotor || 0);
      chartColors.push(shopColorsByName[name] || '#059669');
  });

  const rpFormatter = (v) => {
      if (v < 0) return '-Rp ' + (Math.abs(v)/1000000).toFixed(1) + ' Jt';
      return 'Rp ' + (v/1000000).toFixed(1) + ' Jt';
  };

  makeDonut('omzetChart', 'omzetLegend', omzetLabels, omzetValues, chartColors, rpFormatter);
  makeDonut('labaChart', 'labaLegend', omzetLabels, labaValues, chartColors, rpFormatter);
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts._new_admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/dashboard/index.blade.php ENDPATH**/ ?>