<?php $__env->startSection('title', 'Pratinjau File Excel — ' . $backdateExcelFile->original_filename); ?>

<?php $__env->startPush('style'); ?>
<style>
/* Custom Styles for Modern Executive Excel Viewer */
.excel-viewer-card {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.01);
}

.excel-tab-bar {
  background: #0f172a;
  padding: 10px 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  overflow-x: auto;
  white-space: nowrap;
  border-bottom: 1px solid #1e293b;
}

.excel-tab-bar::-webkit-scrollbar {
  height: 5px;
}
.excel-tab-bar::-webkit-scrollbar-track {
  background: #0f172a;
}
.excel-tab-bar::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 4px;
}

.excel-tab-btn {
  background: rgba(255, 255, 255, 0.08);
  color: #94a3b8;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  padding: 7px 16px;
  font-size: 12.5px;
  font-weight: 600;
  transition: all 0.2s ease;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  outline: none !important;
}

.excel-tab-btn:hover {
  background: rgba(255, 255, 255, 0.16);
  color: #f8fafc;
  border-color: rgba(255, 255, 255, 0.25);
  transform: translateY(-1px);
}

.excel-tab-btn.active {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: #ffffff;
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
  font-weight: 700;
}

.excel-tab-btn.other-shop-tab {
  background: rgba(148, 163, 184, 0.1);
  color: #94a3b8;
  border-style: dashed;
}

.excel-tab-btn.other-shop-tab.active {
  background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
  color: #ffffff;
  border-style: solid;
}

.toggle-all-sheets-btn {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  margin-left: auto;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.toggle-all-sheets-btn:hover {
  background: rgba(56, 189, 248, 0.25);
  color: #ffffff;
}

.excel-sheet-viewport {
  max-height: calc(85vh - 200px);
  min-height: 480px;
  overflow: auto;
  background: #ffffff;
  position: relative;
}

.excel-sheet-viewport::-webkit-scrollbar {
  width: 9px;
  height: 9px;
}
.excel-sheet-viewport::-webkit-scrollbar-track {
  background: #f1f5f9;
}
.excel-sheet-viewport::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.excel-sheet-viewport::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Parsed Sheet Table Styling */
.excel-render-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 12px;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  color: #1e293b;
  background: #ffffff;
}

.excel-render-table td, 
.excel-render-table th {
  border-right: 1px solid #cbd5e1;
  border-bottom: 1px solid #cbd5e1;
  padding: 7px 12px;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
  line-height: 1.45;
}

.excel-render-table th,
.excel-render-table tr.excel-header-row td {
  background: #f1f5f9;
  color: #0f172a;
  font-weight: 700;
  font-size: 11.5px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  border-bottom: 2px solid #94a3b8;
  position: sticky;
  top: 0;
  z-index: 5;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.excel-render-table tr:hover td {
  background-color: #eff6ff !important;
}

.excel-render-table td.num-cell {
  text-align: right;
  font-family: 'JetBrains Mono', 'Fira Code', 'Segoe UI Mono', Consolas, monospace;
}

.excel-render-table td.empty-cell {
  background-color: #fafbfc;
  color: #cbd5e1;
}

.excel-search-bar {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 10px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

/* KPI Cards */
.kpi-card-v2 {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 16px;
  position: relative;
  overflow: hidden;
  transition: all 0.25s ease;
  box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}

.kpi-card-v2:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px -4px rgba(15, 23, 42, 0.08);
}

.kpi-card-v2::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.kpi-blue::before { background: linear-gradient(90deg, #3b82f6, #2563eb); }
.kpi-emerald::before { background: linear-gradient(90deg, #10b981, #059669); }
.kpi-indigo::before { background: linear-gradient(90deg, #6366f1, #4f46e5); }
.kpi-cyan::before { background: linear-gradient(90deg, #06b6d4, #0891b2); }
.kpi-amber::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
.kpi-rose::before { background: linear-gradient(90deg, #f43f5e, #e11d48); }

.kpi-icon-wrapper {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.kpi-blue .kpi-icon-wrapper { background: #eff6ff; color: #2563eb; }
.kpi-emerald .kpi-icon-wrapper { background: #ecfdf5; color: #059669; }
.kpi-indigo .kpi-icon-wrapper { background: #eef2ff; color: #4f46e5; }
.kpi-cyan .kpi-icon-wrapper { background: #ecfeff; color: #0891b2; }
.kpi-amber .kpi-icon-wrapper { background: #fffbeb; color: #d97706; }
.kpi-rose .kpi-icon-wrapper { background: #fff1f2; color: #e11d48; }

.search-highlight {
  background-color: #fef08a !important;
  font-weight: bold;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
  
  
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
      <a href="<?php echo e(route('backdate-excel-files.index')); ?>" class="btn btn-outline-secondary btn-sm mb-2" style="border-radius: 6px; font-weight: 600;">
        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Arsip
      </a>
      
      <div class="d-flex align-items-center flex-wrap gap-2">
        <span class="badge badge-success px-2 py-1" style="font-size: 12px; border-radius: 6px; background: #10b981;">
          <i class="fas fa-file-excel mr-1"></i> EXCEL (.xlsx)
        </span>
        <h1 class="page-title mb-0" style="font-size: 22px; font-weight: 800; color: #0f172a;">
          Pratinjau File: <?php echo e($backdateExcelFile->original_filename); ?>

        </h1>
      </div>

      <div class="d-flex align-items-center flex-wrap mt-2 gap-2 text-muted" style="font-size: 13px;">
        <span class="badge badge-light border px-2 py-1">
          <i class="fas fa-store mr-1 text-primary"></i> <strong><?php echo e($backdateExcelFile->shop->nama); ?></strong> (<?php echo e($backdateExcelFile->shop->kode); ?>)
        </span>
        <span class="badge badge-light border px-2 py-1">
          <i class="fas fa-calendar-alt mr-1 text-info"></i> Periode: <strong><?php echo e($backdateExcelFile->formatted_period); ?></strong>
        </span>
        <span class="badge badge-light border px-2 py-1">
          <i class="fas fa-hdd mr-1 text-secondary"></i> Ukuran: <strong><?php echo e($backdateExcelFile->formatted_file_size); ?></strong>
        </span>
        <?php if($backdateExcelFile->user): ?>
          <span class="badge badge-light border px-2 py-1">
            <i class="fas fa-user-edit mr-1 text-muted"></i> Oleh: <strong><?php echo e($backdateExcelFile->user->name); ?></strong>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <div class="mt-3 mt-md-0">
      <a href="<?php echo e(route('backdate-excel-files.download', $backdateExcelFile->id)); ?>" class="btn btn-primary btn-sm shadow-sm" style="font-weight: 700; border-radius: 8px; padding: 10px 18px; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
        <i class="fas fa-download mr-2"></i> Unduh File Excel Asli
      </a>
    </div>
  </div>

  
  <div class="excel-viewer-card">
    
    
    <div id="excel-loading" class="text-center py-5">
      <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
        <span class="sr-only">Membaca file Excel...</span>
      </div>
      <h6 class="font-weight-bold" style="color: #0f172a; font-size: 16px;">Membaca dan memproses isi berkas Excel...</h6>
      <p class="text-muted" style="font-size: 13px;">Mohon tunggu sebentar, sistem sedang memformat sheet dan kesimpulan laporan.</p>
    </div>

    
    <div id="excel-sheets-nav" class="excel-tab-bar" style="display: none;">
      
    </div>

    
    <div id="excel-summary-container" class="p-4" style="display: none; background: #ffffff;">
      
      
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 border-bottom pb-3 gap-2">
        <div>
          <h4 class="font-weight-bold mb-1" style="color: #0f172a; font-size: 18px;">
            <i class="fas fa-chart-pie text-primary mr-2"></i> Kesimpulan Ringkasan Laporan Backdate
            <?php if(!empty($summary['matched_sheet_name'])): ?>
              <span class="badge badge-info font-weight-normal ml-2 px-2 py-1" style="font-size: 12px; border-radius: 6px;">
                <i class="fas fa-table mr-1"></i> Sheet Toko: <?php echo e($summary['matched_sheet_name']); ?>

              </span>
            <?php endif; ?>
          </h4>
          <span class="text-muted" style="font-size: 13px;">
            Rangkuman poin-poin utama operasional Pertashop <strong><?php echo e($backdateExcelFile->shop->nama); ?></strong> periode <strong><?php echo e($backdateExcelFile->formatted_period); ?></strong>.
          </span>
        </div>
        <div>
          <a href="<?php echo e(route('backdate-excel-files.download', $backdateExcelFile->id)); ?>" class="btn btn-outline-primary btn-sm style-btn" style="border-radius: 6px; font-weight: 600;">
            <i class="fas fa-file-download mr-1"></i> Unduh Berkas (.xlsx)
          </a>
        </div>
      </div>

      <?php
        $sum = $summary ?? [
          'totalisator_awal' => 0,
          'totalisator_akhir' => 0,
          'jumlah_liter_terjual' => 0,
          'test_pump' => ['total_volume' => 0, 'total_rp' => 0, 'details' => []],
          'pembelian_bbm' => ['total_volume_kl' => 0, 'total_volume_liter' => 0, 'total_nominal' => 0, 'details' => []],
          'stok_akhir' => 0,
          'total_pengeluaran' => ['total_rp' => 0, 'category_totals' => [], 'details' => []],
          'total_belum_disetorkan' => ['total_rp' => 0, 'details' => []],
        ];

        $currentShopAliasesData = \App\Services\BackdateExcelSummaryService::getShopAliases($backdateExcelFile->shop);

        $allOtherShopsData = \App\Models\Shop::where('id', '!=', $backdateExcelFile->shop_id)->get()->map(function($s) {
            return [
                'id'      => $s->id,
                'nama'    => $s->nama,
                'aliases' => \App\Services\BackdateExcelSummaryService::getShopAliases($s),
            ];
        });
      ?>

      
      <div class="row mb-4">
        
        
        <div class="col-md-4 col-lg-2 mb-3">
          <div class="kpi-card-v2 kpi-blue">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">1. Tot Awal</small>
              <div class="kpi-icon-wrapper"><i class="fas fa-play"></i></div>
            </div>
            <h5 class="font-weight-bold text-dark mb-0" id="metric-tot-awal" style="font-size: 16px;">
              <?php echo e(number_format($sum['totalisator_awal'], 2, ',', '.')); ?>

            </h5>
            <small class="text-muted" style="font-size: 11px;">Awal Bulan</small>
          </div>
        </div>

        
        <div class="col-md-4 col-lg-2 mb-3">
          <div class="kpi-card-v2 kpi-emerald">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">2. Tot Akhir</small>
              <div class="kpi-icon-wrapper"><i class="fas fa-flag-checkered"></i></div>
            </div>
            <h5 class="font-weight-bold text-dark mb-0" id="metric-tot-akhir" style="font-size: 16px;">
              <?php echo e(number_format($sum['totalisator_akhir'], 2, ',', '.')); ?>

            </h5>
            <small class="text-muted" style="font-size: 11px;">Akhir Bulan</small>
          </div>
        </div>

        
        <div class="col-md-4 col-lg-2 mb-3">
          <div class="kpi-card-v2 kpi-indigo">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">3. Terjual (L)</small>
              <div class="kpi-icon-wrapper"><i class="fas fa-gas-pump"></i></div>
            </div>
            <h5 class="font-weight-bold text-dark mb-0" id="metric-liter-terjual" style="font-size: 16px;">
              <?php echo e(number_format($sum['jumlah_liter_terjual'], 2, ',', '.')); ?> <span style="font-size: 12px; font-weight: normal;">L</span>
            </h5>
            <small class="text-muted" style="font-size: 11px;">Total 1 Bulan</small>
          </div>
        </div>

        
        <div class="col-md-4 col-lg-2 mb-3">
          <div class="kpi-card-v2 kpi-cyan">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">6. Stok Akhir</small>
              <div class="kpi-icon-wrapper"><i class="fas fa-boxes"></i></div>
            </div>
            <h5 class="font-weight-bold text-dark mb-0" id="metric-stok-akhir" style="font-size: 16px;">
              <?php echo e(number_format($sum['stok_akhir'], 2, ',', '.')); ?> <span style="font-size: 12px; font-weight: normal;">L</span>
            </h5>
            <small class="text-muted" style="font-size: 11px;">Akhir Bulan</small>
          </div>
        </div>

        
        <div class="col-md-4 col-lg-2 mb-3">
          <div class="kpi-card-v2 kpi-amber">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">7. Pengeluaran</small>
              <div class="kpi-icon-wrapper"><i class="fas fa-receipt"></i></div>
            </div>
            <h5 class="font-weight-bold text-dark mb-0" id="metric-total-pengeluaran" style="font-size: 14px;">
              Rp <?php echo e(number_format($sum['total_pengeluaran']['total_rp'], 0, ',', '.')); ?>

            </h5>
            <small class="text-muted" style="font-size: 11px;">Operasional</small>
          </div>
        </div>

        
        <div class="col-md-4 col-lg-2 mb-3">
          <div class="kpi-card-v2 kpi-rose">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">8. Belum Disetor</small>
              <div class="kpi-icon-wrapper"><i class="fas fa-exclamation-circle"></i></div>
            </div>
            <h5 class="font-weight-bold text-dark mb-0" id="metric-belum-disetor" style="font-size: 14px;">
              Rp <?php echo e(number_format($sum['total_belum_disetorkan']['total_rp'], 0, ',', '.')); ?>

            </h5>
            <small class="text-muted" style="font-size: 11px;">Selisih Setoran</small>
          </div>
        </div>

      </div>

      
      <div class="row">
        
        
        <div class="col-md-6 mb-4">
          <div class="card shadow-sm border" style="border-radius: 10px; overflow: hidden;">
            <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 border-bottom">
              <h6 class="mb-0 font-weight-bold text-dark" style="font-size: 14px;">
                <i class="fas fa-vial text-info mr-1"></i> 4. Jumlah Test Pump &amp; Rincian
              </h6>
              <span class="badge badge-info px-2 py-1" id="badge-tp-vol" style="font-size: 11px;">
                Total: <?php echo e(number_format($sum['test_pump']['total_volume'], 2, ',', '.')); ?> L
              </span>
            </div>
            <div class="card-body p-0" style="max-height: 280px; overflow-y: auto;">
              <table class="table table-sm table-striped table-hover mb-0" style="font-size: 12px;">
                <thead class="thead-light">
                  <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>Tanggal</th>
                    <th class="text-right">Volume (ℓ)</th>
                    <th class="text-right">Nominal (Rp)</th>
                  </tr>
                </thead>
                <tbody id="table-tp-body">
                  <?php $__empty_1 = true; $__currentLoopData = $sum['test_pump']['details']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $tp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                      <td class="text-center"><?php echo e($idx + 1); ?></td>
                      <td><?php echo e($tp['tgl']); ?></td>
                      <td class="text-right font-weight-bold"><?php echo e(number_format($tp['volume'], 2, ',', '.')); ?> L</td>
                      <td class="text-right">Rp <?php echo e(number_format($tp['nominal'], 0, ',', '.')); ?></td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted py-3">Tidak ada data test pump pada periode ini.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        
        <div class="col-md-6 mb-4">
          <div class="card shadow-sm border" style="border-radius: 10px; overflow: hidden;">
            <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 border-bottom">
              <h6 class="mb-0 font-weight-bold text-dark" style="font-size: 14px;">
                <i class="fas fa-truck-loading text-success mr-1"></i> 5. Pembelian &amp; Penerimaan BBM (Rincian Tanggal)
              </h6>
              <span class="badge badge-success px-2 py-1" id="badge-bbm-vol" style="font-size: 11px;">
                Total: <?php echo e(number_format($sum['pembelian_bbm']['total_volume_kl'], 2, ',', '.')); ?> KL (<?php echo e(number_format($sum['pembelian_bbm']['total_volume_liter'], 0, ',', '.')); ?> L)
              </span>
            </div>
            <div class="card-body p-0" style="max-height: 280px; overflow-y: auto;">
              <table class="table table-sm table-striped table-hover mb-0" style="font-size: 12px;">
                <thead class="thead-light">
                  <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>Tanggal</th>
                    <th>Tipe Operasi</th>
                    <th class="text-right">Volume (L)</th>
                    <th class="text-right">Volume (KL)</th>
                    <th class="text-right">Total Kotor (Rp)</th>
                  </tr>
                </thead>
                <tbody id="table-bbm-body">
                  <?php $__empty_1 = true; $__currentLoopData = $sum['pembelian_bbm']['details']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $bbm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                      <td class="text-center"><?php echo e($idx + 1); ?></td>
                      <td class="font-weight-bold"><?php echo e($bbm['tgl']); ?></td>
                      <td>
                        <span class="badge <?php echo e(($bbm['tipe'] ?? '') == 'Terima BBM' ? 'badge-info' : 'badge-success'); ?>" style="border-radius: 4px; font-size: 10.5px;">
                          <i class="fas <?php echo e(($bbm['tipe'] ?? '') == 'Terima BBM' ? 'fa-download' : 'fa-shopping-cart'); ?> mr-1"></i>
                          <?php echo e($bbm['tipe'] ?? 'Penerimaan / Pembelian'); ?>

                        </span>
                      </td>
                      <td class="text-right font-weight-bold text-dark">
                        <?php echo e(number_format($bbm['jumlah_liter'] ?? ($bbm['jumlah_kl'] * 1000), 0, ',', '.')); ?> L
                      </td>
                      <td class="text-right font-weight-bold text-success">
                        <?php echo e(number_format($bbm['jumlah_kl'], 3, ',', '.')); ?> KL
                      </td>
                      <td class="text-right text-muted">
                        <?php echo e(($bbm['total_nominal'] ?? 0) > 0 ? 'Rp ' . number_format($bbm['total_nominal'], 0, ',', '.') : '-'); ?>

                      </td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted py-3">Tidak ada data penerimaan/pembelian BBM.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        
        <div class="col-md-12 mb-4">
          <div class="card shadow-sm border" style="border-radius: 10px; overflow: hidden;">
            <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 border-bottom">
              <h6 class="mb-0 font-weight-bold text-dark" style="font-size: 14px;">
                <i class="fas fa-shopping-cart text-warning mr-1"></i> 7. Total Pengeluaran &amp; Rincian Per Kategori / Tanggal
              </h6>
              <span class="badge badge-warning text-dark font-weight-bold px-2 py-1" id="badge-pengeluaran-total" style="font-size: 11px;">
                Total: Rp <?php echo e(number_format($sum['total_pengeluaran']['total_rp'], 0, ',', '.')); ?>

              </span>
            </div>
            
            <div class="card-body p-3">
              
              <div class="mb-3 d-flex flex-wrap" id="pengeluaran-category-pills">
                <?php $__currentLoopData = $sum['total_pengeluaran']['category_totals']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catName => $catVal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <span class="badge badge-light border mr-2 mb-2 p-2" style="font-size: 12px; border-radius: 6px;">
                    <strong><?php echo e($catName); ?>:</strong> Rp <?php echo e(number_format($catVal, 0, ',', '.')); ?>

                  </span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>

              
              <div style="max-height: 320px; overflow-y: auto;" class="border rounded">
                <table class="table table-sm table-striped table-hover mb-0" style="font-size: 12px;">
                  <thead class="thead-light">
                    <tr>
                      <th style="width: 40px;" class="text-center">No</th>
                      <th style="width: 120px;">Tanggal</th>
                      <th style="width: 160px;">Kategori</th>
                      <th class="text-right" style="width: 160px;">Nominal (Rp)</th>
                      <th>Keterangan Tambahan</th>
                    </tr>
                  </thead>
                  <tbody id="table-pengeluaran-body">
                    <?php $__empty_1 = true; $__currentLoopData = $sum['total_pengeluaran']['details']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                      <tr>
                        <td class="text-center"><?php echo e($idx + 1); ?></td>
                        <td><?php echo e($sp['tgl']); ?></td>
                        <td><span class="badge badge-secondary" style="border-radius: 4px;"><?php echo e($sp['kategori']); ?></span></td>
                        <td class="text-right font-weight-bold text-danger">Rp <?php echo e(number_format($sp['nominal'], 0, ',', '.')); ?></td>
                        <td class="text-muted"><?php echo e($sp['keterangan'] ?: '-'); ?></td>
                      </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                      <tr>
                        <td colspan="5" class="text-center text-muted py-3">Tidak ada pengeluaran operasional tercatat.</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

            </div>
          </div>
        </div>

        
        <div class="col-md-12 mb-4">
          <div class="card shadow-sm border" style="border-radius: 10px; overflow: hidden;">
            <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 border-bottom">
              <h6 class="mb-0 font-weight-bold text-dark" style="font-size: 14px;">
                <i class="fas fa-hand-holding-usd text-danger mr-1"></i> 8. Total Uang Yang Belum Disetorkan &amp; Rincian Per Tanggal
              </h6>
              <span class="badge badge-danger px-2 py-1" id="badge-belum-disetor-total" style="font-size: 11px;">
                Total Akumulasi: Rp <?php echo e(number_format($sum['total_belum_disetorkan']['total_rp'], 0, ',', '.')); ?>

              </span>
            </div>
            
            <div class="card-body p-0" style="max-height: 320px; overflow-y: auto;">
              <table class="table table-sm table-striped table-hover mb-0" style="font-size: 12px;">
                <thead class="thead-light">
                  <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th style="width: 130px;">Tanggal</th>
                    <th class="text-right" style="width: 180px;">Nominal / Selisih (Rp)</th>
                    <th>Keterangan / Catatan</th>
                  </tr>
                </thead>
                <tbody id="table-belum-disetor-body">
                  <?php $__empty_1 = true; $__currentLoopData = $sum['total_belum_disetorkan']['details']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $bd): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                      <td class="text-center"><?php echo e($idx + 1); ?></td>
                      <td><?php echo e($bd['tgl']); ?></td>
                      <td class="text-right font-weight-bold <?php echo e($bd['nominal'] < 0 ? 'text-danger' : 'text-success'); ?>">
                        Rp <?php echo e(number_format($bd['nominal'], 0, ',', '.')); ?>

                      </td>
                      <td class="text-muted"><?php echo e($bd['keterangan'] ?: '-'); ?></td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted py-3">Tidak ada data uang yang belum disetorkan.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

          </div>
        </div>

        
        <?php if(isset($sum['profit_sharing'])): ?>
        <?php $ps = $sum['profit_sharing']; ?>
        <div class="col-md-12 mb-4">
          <div class="card shadow-sm border" style="border-radius: 12px; overflow: hidden; border-color: #cbd5e1 !important;">
            <div class="card-header d-flex align-items-center justify-content-between py-2.5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff;">
              <h6 class="mb-0 font-weight-bold" style="font-size: 14px; letter-spacing: 0.3px;">
                <i class="fas fa-hand-holding-usd text-warning mr-2"></i> 9. Simulasi &amp; Rincian Pembagian Profit Sharing Investor (<?php echo e($backdateExcelFile->shop->nama); ?>)
              </h6>
              <span class="badge badge-warning text-dark font-weight-bold px-2.5 py-1" style="font-size: 11.5px; border-radius: 6px;">
                Total Bagi Hasil: Rp <?php echo e(number_format($ps['laba_bersih_dibagi'], 0, ',', '.')); ?>

              </span>
            </div>

            <div class="card-body p-3 bg-light">
              
              
              <div class="row mb-3">
                <div class="col-md-3 mb-2">
                  <div class="p-2.5 bg-white border rounded shadow-xs">
                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 10px;">Estimasi Laba Kotor (Margin Rp <?php echo e(number_format($ps['margin_per_liter'], 2, ',', '.')); ?>/L)</small>
                    <span class="h6 font-weight-bold text-success mb-0">Rp <?php echo e(number_format($ps['est_laba_kotor'], 0, ',', '.')); ?></span>
                  </div>
                </div>
                <div class="col-md-3 mb-2">
                  <div class="p-2.5 bg-white border rounded shadow-xs">
                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 10px;">Total Pengeluaran &amp; Test Pump</small>
                    <span class="h6 font-weight-bold text-danger mb-0">Rp <?php echo e(number_format($ps['total_pengeluaran'], 0, ',', '.')); ?></span>
                  </div>
                </div>
                <div class="col-md-3 mb-2">
                  <div class="p-2.5 bg-white border rounded shadow-xs">
                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 10px;">Cadangan Modal (<?php echo e($ps['persen_alokasi_modal']); ?>%)</small>
                    <span class="h6 font-weight-bold text-info mb-0">Rp <?php echo e(number_format($ps['alokasi_modal'], 0, ',', '.')); ?></span>
                  </div>
                </div>
                <div class="col-md-3 mb-2">
                  <div class="p-2.5 bg-white border rounded shadow-xs" style="border-left: 4px solid #10b981 !important;">
                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 10px;">Laba Bersih Siap Dibagi</small>
                    <span class="h6 font-weight-bold text-dark mb-0">Rp <?php echo e(number_format($ps['laba_bersih_dibagi'], 0, ',', '.')); ?></span>
                  </div>
                </div>
              </div>

              
              <div class="border rounded bg-white overflow-hidden" style="max-height: 350px; overflow-y: auto;">
                <table class="table table-sm table-striped table-hover mb-0" style="font-size: 12.5px;">
                  <thead class="thead-dark" style="background-color: #1e293b; color: #f8fafc;">
                    <tr>
                      <th style="width: 45px;" class="text-center">No</th>
                      <th>Nama Investor Pertashop</th>
                      <th class="text-center" style="width: 130px;">Porsi Share (%)</th>
                      <th class="text-right" style="width: 200px;">Modal Investasi Awal</th>
                      <th class="text-right text-warning" style="width: 220px;">Hak Profit Sharing Bulan Ini</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $ps['investor_details']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                      <tr>
                        <td class="text-center font-weight-bold"><?php echo e($idx + 1); ?></td>
                        <td class="font-weight-bold text-dark">
                          <i class="fas fa-user-circle text-primary mr-1"></i> <?php echo e($inv['nama']); ?>

                        </td>
                        <td class="text-center">
                          <span class="badge badge-info px-2 py-1" style="font-size: 11.5px; border-radius: 4px;">
                            <?php echo e(number_format($inv['persentase'], 2, ',', '.')); ?>%
                          </span>
                        </td>
                        <td class="text-right font-weight-bold text-muted">
                          Rp <?php echo e(number_format($inv['nominal_modal'], 0, ',', '.')); ?>

                        </td>
                        <td class="text-right font-weight-bold text-success" style="font-size: 13px;">
                          Rp <?php echo e(number_format($inv['nominal_share'], 0, ',', '.')); ?>

                        </td>
                      </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                      <tr>
                        <td colspan="5" class="text-center text-muted py-3">Belum ada investor terdaftar untuk toko <?php echo e($backdateExcelFile->shop->nama); ?>.</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

            </div>
          </div>
        </div>
        <?php endif; ?>

      </div>

    </div>

    
    <div id="excel-table-wrapper" style="display: none;">
      
      
      <div class="excel-search-bar">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-table text-primary"></i>
          <strong id="active-sheet-title" style="font-size: 13px; color: #0f172a;">-</strong>
          <span id="active-sheet-info" class="badge badge-light border ml-1" style="font-size: 11px; color: #64748b;">-</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div class="input-group input-group-sm" style="width: 280px;">
            <div class="input-group-prepend">
              <span class="input-group-text bg-white border-right-0" style="border-radius: 6px 0 0 6px;">
                <i class="fas fa-search text-muted" style="font-size: 11px;"></i>
              </span>
            </div>
            <input type="text" id="sheet-search-input" class="form-control border-left-0" placeholder="Cari angka / kata di sheet..." style="border-radius: 0 6px 6px 0;">
          </div>
          <button type="button" id="clear-sheet-search" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px; font-size: 11px; display: none;">
            Reset
          </button>
        </div>
      </div>

      
      <div id="excel-table-container" class="excel-sheet-viewport">
        
      </div>
    </div>

    
    <div id="excel-error" class="alert alert-danger m-3" style="display: none; border-radius: 8px;">
      Gagal membaca isi berkas Excel. Berkas mungkin korup atau format tidak didukung. Silakan gunakan tombol <strong>Unduh File Excel Asli</strong> di atas untuk membuka secara lokal.
    </div>

  </div>

</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileBase64 = <?php echo json_encode($fileBase64, 15, 512) ?>;
    const loadingEl = document.getElementById('excel-loading');
    const sheetsNavEl = document.getElementById('excel-sheets-nav');
    const summaryContainerEl = document.getElementById('excel-summary-container');
    const tableWrapperEl = document.getElementById('excel-table-wrapper');
    const tableContainerEl = document.getElementById('excel-table-container');
    const activeSheetTitleEl = document.getElementById('active-sheet-title');
    const activeSheetInfoEl = document.getElementById('active-sheet-info');
    const searchInputEl = document.getElementById('sheet-search-input');
    const clearSearchBtn = document.getElementById('clear-sheet-search');
    const errorEl = document.getElementById('excel-error');

    if (!fileBase64) {
        loadingEl.style.display = 'none';
        errorEl.style.display = 'block';
        errorEl.innerHTML = '<strong>File tidak ditemukan:</strong> Berkas fisik Excel tidak ada di server storage.';
        return;
    }

    const currentShopAliases = <?php echo json_encode($currentShopAliasesData, 15, 512) ?>;
    const allOtherShops = <?php echo json_encode($allOtherShopsData, 15, 512) ?>;
    const currentPeriod = <?php echo json_encode($backdateExcelFile->bulan_tahun, 15, 512) ?>;

    function parsePeriodFromSheet(sName) {
        const s = sName.toLowerCase().trim();
        const monthMap = {
            'jan': '01', 'januari': '01', 'january': '01',
            'feb': '02', 'februari': '02', 'february': '02',
            'mar': '03', 'maret': '03', 'march': '03',
            'apr': '04', 'april': '04',
            'mei': '05', 'may': '05',
            'jun': '06', 'juni': '06', 'june': '06',
            'jul': '07', 'juli': '07', 'july': '07',
            'ags': '08', 'agust': '08', 'agustus': '08', 'aug': '08',
            'sep': '09', 'sept': '09', 'september': '09',
            'okt': '10', 'oktober': '10', 'oct': '10',
            'nov': '11', 'november': '11',
            'des': '12', 'desember': '12', 'dec': '12'
        };

        let foundMonth = null;
        for (const [key, num] of Object.entries(monthMap)) {
            if (s.includes(key)) {
                foundMonth = num;
                break;
            }
        }

        let foundYear = null;
        let mYear = s.match(/20(2[0-9])\b/);
        if (mYear) {
            foundYear = '20' + mYear[1];
        } else {
            let mShortYear = s.match(/(?<=\D|^)(2[0-9])\b/);
            if (mShortYear) {
                foundYear = '20' + mShortYear[1];
            }
        }

        if (foundMonth && foundYear) {
            return foundYear + '-' + foundMonth;
        }
        return null;
    }

    function isSheetForCurrentShop(sName) {
        const sLower = sName.toLowerCase();
        const sNoDot = sLower.replace(/[\.\s\-]/g, '');

        for (const alias of currentShopAliases) {
            if (alias && (sLower.includes(alias) || sNoDot.includes(alias))) {
                return true;
            }
        }
        return false;
    }

    function isSheetForOtherShop(sName) {
        if (isSheetForCurrentShop(sName)) {
            return false;
        }

        const sLower = sName.toLowerCase();
        const sNoDot = sLower.replace(/[\.\s\-]/g, '');

        for (const other of allOtherShops) {
            for (const alias of other.aliases) {
                if (alias && (sLower.includes(alias) || sNoDot.includes(alias))) {
                    return true;
                }
            }
        }
        return false;
    }

    try {
        let workbook;
        try {
            workbook = XLSX.read(fileBase64, { type: 'base64', cellDates: true });
        } catch (e1) {
            console.warn('Strict XLSX parse failed, trying fallback parse:', e1);
            workbook = XLSX.read(fileBase64, { type: 'base64' });
        }

        if (!workbook || !workbook.SheetNames || workbook.SheetNames.length === 0) {
            throw new Error('File Excel tidak memiliki sheet atau sheet kosong.');
        }

        loadingEl.style.display = 'none';
        sheetsNavEl.style.display = 'flex';
        
        // Render Summary Container by default
        summaryContainerEl.style.display = 'block';

        // Clean navigation bar
        sheetsNavEl.innerHTML = '';
        
        // 1. TAB UTAMA: "📌 Kesimpulan Laporan"
        const summaryBtn = document.createElement('button');
        summaryBtn.className = 'excel-tab-btn active';
        summaryBtn.innerHTML = '<i class="fas fa-chart-pie"></i> 📌 Kesimpulan Laporan';

        summaryBtn.addEventListener('click', function() {
            Array.from(sheetsNavEl.querySelectorAll('.excel-tab-btn')).forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            tableWrapperEl.style.display = 'none';
            summaryContainerEl.style.display = 'block';
        });

        sheetsNavEl.appendChild(summaryBtn);

        // 2. TAB-TAB SHEET BAWAAN EXCEL
        let hiddenTabsCount = 0;

        workbook.SheetNames.forEach((sheetName) => {
            const isOtherShop = isSheetForOtherShop(sheetName);
            const sheetPeriod = parsePeriodFromSheet(sheetName);
            
            // If this record is for a specific month (e.g. 2025-07) and sheet belongs to another period (e.g. 2025-08 or 2026-01)
            const isDifferentPeriod = (currentPeriod && currentPeriod.length === 7 && sheetPeriod && sheetPeriod !== currentPeriod);

            const isHiddenByDefault = isOtherShop || isDifferentPeriod;
            if (isHiddenByDefault) hiddenTabsCount++;

            const btn = document.createElement('button');
            btn.className = isHiddenByDefault ? 'excel-tab-btn other-shop-tab hidden-sheet-tab' : 'excel-tab-btn';
            if (isHiddenByDefault) {
                btn.style.display = 'none'; // Sembunyikan sheet periode/toko lain secara default
            }
            btn.innerHTML = `<i class="fas fa-table"></i> ${sheetName}`;
            
            btn.addEventListener('click', function() {
                Array.from(sheetsNavEl.querySelectorAll('.excel-tab-btn')).forEach(b => {
                    b.classList.remove('active');
                });
                this.classList.add('active');

                summaryContainerEl.style.display = 'none';
                tableWrapperEl.style.display = 'block';

                activeSheetTitleEl.textContent = sheetName;
                renderSheet(workbook, sheetName);
            });

            sheetsNavEl.appendChild(btn);
        });

        // 3. TOMBOL TOGGLE UNTUK MENAMPILKAN / MENYEMBUNYIKAN SHEET PERIODE / TOKO LAIN
        if (hiddenTabsCount > 0) {
            let showingAll = false;
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'toggle-all-sheets-btn';
            toggleBtn.innerHTML = `<i class="fas fa-eye"></i> Tampilkan ${hiddenTabsCount} Sheet Periode / Toko Lain`;

            toggleBtn.addEventListener('click', function() {
                showingAll = !showingAll;
                const hiddenTabs = sheetsNavEl.querySelectorAll('.hidden-sheet-tab');
                hiddenTabs.forEach(tab => {
                    tab.style.display = showingAll ? 'inline-flex' : 'none';
                });
                this.className = showingAll ? 'toggle-all-sheets-btn active' : 'toggle-all-sheets-btn';
                this.innerHTML = showingAll 
                    ? '<i class="fas fa-eye-slash"></i> Sembunyikan Sheet Periode Lain' 
                    : `<i class="fas fa-eye"></i> Tampilkan ${hiddenTabsCount} Sheet Periode / Toko Lain`;
            });

            sheetsNavEl.appendChild(toggleBtn);
        }

    } catch (err) {
        console.error('Error parsing Excel file:', err);
        loadingEl.style.display = 'none';
        errorEl.style.display = 'block';
        errorEl.innerHTML = '<strong>Gagal membaca berkas Excel:</strong> ' + (err.message || 'File tidak dapat dibuka.') + '. Silakan gunakan tombol <strong>Unduh File Excel Asli</strong> di atas untuk membuka secara lokal.';
    }

    // Function to render and beautify sheet
    function renderSheet(workbook, sheetName) {
        const worksheet = workbook.Sheets[sheetName];
        if (!worksheet) {
            tableContainerEl.innerHTML = '<div class="text-muted p-4 text-center">Sheet kosong atau tidak ditemukan.</div>';
            return;
        }

        // Convert worksheet to HTML string
        const htmlString = XLSX.utils.sheet_to_html(worksheet, { header: '', footer: '' });
        tableContainerEl.innerHTML = htmlString;

        const table = tableContainerEl.querySelector('table');
        if (!table) {
            tableContainerEl.innerHTML = '<div class="text-muted p-4 text-center">Tabel data tidak dapat digenerate.</div>';
            return;
        }

        // Upgrade Table Styling
        table.className = 'excel-render-table';
        
        const rows = Array.from(table.querySelectorAll('tr'));
        activeSheetInfoEl.textContent = `${rows.length} Baris Data`;

        // Reset Search Input on sheet switch
        if (searchInputEl) {
            searchInputEl.value = '';
            clearSearchBtn.style.display = 'none';
        }

        // Process Header & Cell Alignment
        rows.forEach((row, rowIndex) => {
            const cells = Array.from(row.querySelectorAll('td, th'));
            let rowText = row.textContent.trim().toLowerCase();

            // Detect top header row
            if (rowIndex < 4 && (rowText.includes('tgl') || rowText.includes('tanggal') || rowText.includes('no') || rowText.includes('totalisator') || rowText.includes('volume') || rowText.includes('nama'))) {
                row.classList.add('excel-header-row');
            }

            // Detect total / summary row
            if (rowText.includes('total') || rowText.includes('jumlah') || rowText.includes('subtotal') || rowText.includes('take home pay')) {
                row.classList.add('total-row');
            }

            cells.forEach(cell => {
                let cellText = cell.textContent.trim();

                // Clean up character encoding artifacts like lone question marks in parentheses
                if (cellText.includes('?')) {
                    cellText = cellText.replace(/\(\s*\?\s*\)/g, '').replace(/\?/g, '').trim();
                    cell.textContent = cellText;
                }

                // Style empty cells
                if (!cellText || cellText === '&nbsp;') {
                    cell.classList.add('empty-cell');
                    return;
                }

                // Check if number or currency format
                const cleanText = cellText.replace(/^Rp\s?/, '').replace(/\./g, '').replace(',', '.');
                const isNumeric = !isNaN(cleanText) && cleanText !== '' && !/^[a-zA-Z]/.test(cellText);

                if (isNumeric || cellText.startsWith('Rp') || /^-?\d+([\.,]\d+)?\s?[L|KL|%]?$/.test(cellText)) {
                    cell.classList.add('num-cell');
                }
            });
        });
    }

    // Mini Live Search Filter across sheet cells
    if (searchInputEl) {
        searchInputEl.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            clearSearchBtn.style.display = query ? 'inline-block' : 'none';

            const table = tableContainerEl.querySelector('table');
            if (!table) return;

            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                // Keep header rows visible
                if (row.classList.contains('excel-header-row')) {
                    row.style.display = '';
                    return;
                }

                const cells = row.querySelectorAll('td, th');
                let match = false;

                cells.forEach(cell => {
                    cell.classList.remove('search-highlight');
                    if (query && cell.textContent.toLowerCase().includes(query)) {
                        match = true;
                        cell.classList.add('search-highlight');
                    }
                });

                if (query) {
                    row.style.display = match ? '' : 'none';
                } else {
                    row.style.display = '';
                }
            });
        });

        clearSearchBtn.addEventListener('click', function() {
            searchInputEl.value = '';
            searchInputEl.dispatchEvent(new Event('input'));
        });
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts._new_admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/backdate_excel/show.blade.php ENDPATH**/ ?>