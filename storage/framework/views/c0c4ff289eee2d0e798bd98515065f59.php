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

/* Executive Document Styles for 4 Official Pages */
.report-paper-preview {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 28px 32px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
    margin-bottom: 20px;
    color: #0f172a;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.report-header-title-pv {
    font-size: 15px;
    font-weight: 800;
    text-transform: uppercase;
    text-align: center;
    margin-bottom: 2px;
    color: #0f172a;
}

.report-header-sub-pv {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    text-align: center;
    color: #1e293b;
    margin-bottom: 2px;
}

.report-header-pt-pv {
    font-size: 12.5px;
    font-weight: 800;
    text-transform: uppercase;
    text-align: center;
    color: #0f172a;
    border-bottom: 3px double #0f172a;
    padding-bottom: 6px;
    margin-bottom: 14px;
}

.table-formal-pv {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    line-height: 1.45;
}

.table-formal-pv th, .table-formal-pv td {
    padding: 4px 8px;
    vertical-align: middle;
}

.table-formal-bordered-pv {
    border: 1px solid #94a3b8;
}

.table-formal-bordered-pv th, .table-formal-bordered-pv td {
    border: 1px solid #cbd5e1;
}

.table-formal-bordered-pv thead th {
    background-color: #f1f5f9;
    color: #0f172a;
    font-weight: 700;
    text-align: center;
    border-bottom: 2px solid #64748b;
}

.box-segment-pv {
    border: 1.5px solid #64748b;
    border-radius: 8px;
    padding: 14px;
    margin-bottom: 16px;
    position: relative;
    background: #ffffff;
}

.box-segment-number-pv {
    position: absolute;
    top: 50%;
    right: 20px;
    transform: translateY(-50%);
    width: 55px;
    height: 65px;
    border: 2px solid #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    font-weight: 800;
    color: #0f172a;
    background: #f8fafc;
}

.sub-report-nav .nav-link {
    border: 1px solid #e2e8f0;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    color: #475569;
    font-weight: 600;
    font-size: 12.5px;
    padding: 8px 16px;
    background: #f8fafc;
}

.sub-report-nav .nav-link.active {
    background: #ffffff;
    color: #2563eb;
    border-color: #cbd5e1;
    border-top: 3px solid #2563eb;
    font-weight: 800;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">

  <?php if(session('success')): ?>
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-left: 4px solid #10b981 !important;">
          <h6 class="font-weight-bold text-success mb-1"><i class="fas fa-check-circle mr-1"></i> Berhasil Tersinkronisasi!</h6>
          <p class="mb-0 text-dark" style="font-size: 13px;"><?php echo e(session('success')); ?></p>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
  <?php endif; ?>

  <?php if(session('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-left: 4px solid #ef4444 !important;">
          <h6 class="font-weight-bold text-danger mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal Sinkronisasi!</h6>
          <p class="mb-0 text-dark" style="font-size: 13px;"><?php echo e(session('error')); ?></p>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
  <?php endif; ?>
  
  
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
        <?php if(isset($monthlyReport) && $monthlyReport): ?>
          <span class="badge badge-primary px-2.5 py-1" style="font-size: 12px; border-radius: 6px;">
            <i class="fas fa-link mr-1"></i> Terhubung Laporan Bulanan (#<?php echo e($monthlyReport->id); ?>)
          </span>
        <?php else: ?>
          <span class="badge badge-warning px-2.5 py-1 text-dark font-weight-bold" style="font-size: 12px; border-radius: 6px;">
            <i class="fas fa-exclamation-circle mr-1"></i> Perlu Disinkronkan
          </span>
        <?php endif; ?>
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

      
      <form id="sync-backdate-form" action="<?php echo e(route('backdate-excel-files.sync', $backdateExcelFile->id)); ?>" method="POST" class="d-inline">
        <?php echo csrf_field(); ?>
        <button type="button" onclick="confirmSyncBackdate()" class="btn btn-info btn-sm shadow-sm" style="font-weight: 700; border-radius: 8px; padding: 9px 16px;">
          <i class="fas fa-sync-alt mr-1.5"></i> Sinkronkan ke Laporan Bulanan &amp; Rekap Modal
        </button>
      </form>

      <?php if(isset($monthlyReport) && $monthlyReport): ?>
        <a href="<?php echo e(route('monthly-reports.show', $monthlyReport->id)); ?>" class="btn btn-dark btn-sm shadow-sm" style="font-weight: 700; border-radius: 8px; padding: 9px 16px;">
          <i class="fas fa-external-link-alt mr-1.5"></i> Buka Laporan Bulanan Resmi
        </a>
      <?php endif; ?>

      <a href="<?php echo e(route('backdate-excel-files.download', $backdateExcelFile->id)); ?>" class="btn btn-outline-primary btn-sm shadow-sm" style="font-weight: 700; border-radius: 8px; padding: 9px 16px;">
        <i class="fas fa-download mr-1.5"></i> Unduh File Asli
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
            <i class="fas fa-chart-pie text-primary mr-2"></i> Kesimpulan Ringkasan Laporan Backdate (Terintegrasi)
            <?php if(!empty($summary['matched_sheet_name'])): ?>
              <span class="badge badge-info font-weight-normal ml-2 px-2 py-1" style="font-size: 12px; border-radius: 6px;">
                <i class="fas fa-table mr-1"></i> Sheet Toko: <?php echo e($summary['matched_sheet_name']); ?>

              </span>
            <?php endif; ?>
          </h4>
          <span class="text-muted" style="font-size: 13px;">
            Rangkuman 4 Halaman Resmi Operasional Pertashop <strong><?php echo e($backdateExcelFile->shop->nama); ?></strong> periode <strong><?php echo e($backdateExcelFile->formatted_period); ?></strong> yang terhubung dengan modul Laporan Bulanan dan Rekapitulasi Modal.
          </span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php if(isset($monthlyReport) && $monthlyReport): ?>
            <a href="<?php echo e(route('monthly-reports.show', $monthlyReport->id)); ?>" class="btn btn-outline-success btn-sm font-weight-bold" style="border-radius: 6px;">
              <i class="fas fa-check-circle mr-1"></i> Tersinkron (Laporan #<?php echo e($monthlyReport->id); ?>)
            </a>
          <?php endif; ?>
          <a href="<?php echo e(route('backdate-excel-files.download', $backdateExcelFile->id)); ?>" class="btn btn-outline-primary btn-sm style-btn" style="border-radius: 6px; font-weight: 600;">
            <i class="fas fa-file-download mr-1"></i> Unduh Berkas (.xlsx)
          </a>
        </div>
      </div>      <?php
        $h1 = $summary['hal1'] ?? [];
        $h2 = $summary['hal2'] ?? [];
        $h3 = $summary['hal3'] ?? [];
        $h4 = $summary['hal4'] ?? [];
      ?>

      
      <ul class="nav nav-tabs sub-report-nav mb-4" id="previewSubTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" id="pv-hal1-tab" data-toggle="tab" href="#pv-hal1" role="tab">
            <i class="fas fa-gas-pump mr-1.5 text-primary"></i> Hal 1: Stok, Penjualan &amp; Laba Kotor
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="pv-hal2-tab" data-toggle="tab" href="#pv-hal2" role="tab">
            <i class="fas fa-hand-holding-usd mr-1.5 text-success"></i> Hal 2: Laba Bersih &amp; Profit Sharing
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="pv-hal3-tab" data-toggle="tab" href="#pv-hal3" role="tab">
            <i class="fas fa-balance-scale mr-1.5 text-info"></i> Hal 3: Posisi Modal Kerja (Neraca)
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="pv-hal4-tab" data-toggle="tab" href="#pv-hal4" role="tab">
            <i class="fas fa-history mr-1.5 text-warning"></i> Hal 4: Rekapitulasi Nilai Modal Historis
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="pv-bkh-tab" data-toggle="tab" href="#pv-bkh" role="tab">
            <i class="fas fa-table mr-1.5 text-secondary"></i> Rincian 8 Poin BKH
          </a>
        </li>
      </ul>

      <div class="tab-content" id="previewSubTabsContent">

        
        
        
        <div class="tab-pane fade show active" id="pv-hal1" role="tabpanel">
          <div class="report-paper-preview">
            <div class="report-header-title-pv">LAPORAN STOCK, PENJUALAN &amp; LABA KOTOR <?php echo e($backdateExcelFile->formatted_period); ?></div>
            <div class="report-header-sub-pv">PERTASHOP <?php echo e($backdateExcelFile->shop->kode); ?> <?php echo e($backdateExcelFile->shop->alamat); ?></div>
            <div class="report-header-pt-pv">PT SERAYU AGUNG MANDIRI</div>

            
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 text-dark" style="font-size: 11.5px; font-weight: 700; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px;">
              <div>
                <span class="text-uppercase">PERTAMAX :</span>
                <?php $__currentLoopData = $h1['segments'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sIdx => $seg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <span class="ml-2">Harga Beli <?php echo e($sIdx + 1); ?>: Rp <?php echo e(number_format($seg['harga_beli'], 2, ',', '.')); ?>,- &nbsp; Harga Jual <?php echo e($sIdx + 1); ?>: Rp <?php echo e(number_format($seg['harga_jual'], 2, ',', '.')); ?>,-</span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
              <div>
                Rata-rata omset Harian (ℓ) = <span class="text-primary"><?php echo e(number_format($h1['rata_rata_omset_harian'] ?? 0, 2, ',', '.')); ?></span>
              </div>
            </div>

            
            <?php $__empty_1 = true; $__currentLoopData = $h1['segments'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sIdx => $seg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <div class="box-segment-pv">
                <div class="box-segment-number-pv"><?php echo e($sIdx + 1); ?></div>

                
                <div class="font-weight-bold mb-1" style="font-size: 12.5px;">I. PEMBELIAN <?php echo e($sIdx + 1); ?></div>
                <table class="table-formal-pv mb-2" style="max-width: 85%;">
                  <tr>
                    <td style="width: 140px;">Stok Awal</td>
                    <td style="width: 20px;">=</td>
                    <td style="width: 90px;" class="text-right"><?php echo e(number_format($seg['stok_awal'], 2, ',', '.')); ?></td>
                    <td style="width: 30px;">ℓ</td>
                    <td style="width: 20px;">x</td>
                    <td style="width: 100px;">Rp <?php echo e(number_format($seg['harga_beli'], 2, ',', '.')); ?></td>
                    <td style="width: 30px;" class="text-center">&rarr;</td>
                    <td style="width: 130px;" class="text-right">Rp <?php echo e(number_format($seg['stok_awal_rp'], 0, ',', '.')); ?></td>
                  </tr>
                  <tr>
                    <td>BBM Datang</td>
                    <td>=</td>
                    <td class="text-right"><?php echo e(number_format($seg['bbm_datang'], 2, ',', '.')); ?></td>
                    <td>ℓ</td>
                    <td>x</td>
                    <td>Rp <?php echo e(number_format($seg['harga_beli'], 2, ',', '.')); ?></td>
                    <td style="width: 30px;" class="text-center">&rarr;</td>
                    <td style="width: 130px;" class="text-right">Rp <?php echo e(number_format($seg['bbm_datang_rp'], 0, ',', '.')); ?></td>
                  </tr>
                  <tr style="font-weight: 700; border-top: 1px solid #94a3b8;">
                    <td>A. Jumlah Pembelian <?php echo e($sIdx + 1); ?></td>
                    <td>=</td>
                    <td class="text-right"><?php echo e(number_format($seg['jumlah_pembelian'], 2, ',', '.')); ?></td>
                    <td>ℓ</td>
                    <td colspan="2"></td>
                    <td class="text-center">&rarr;</td>
                    <td class="text-right">Rp <?php echo e(number_format($seg['jumlah_pembelian_rp'], 0, ',', '.')); ?></td>
                  </tr>
                </table>

                
                <div class="font-weight-bold mt-2 mb-1" style="font-size: 12.5px;">II. PENJUALAN <?php echo e($sIdx + 1); ?></div>
                <table class="table-formal-pv mb-2" style="max-width: 85%;">
                  <tr>
                    <td style="width: 230px;">a. Totalisator Akhir (<?php echo e($seg['end_datetime_label'] ?? '-'); ?>)</td>
                    <td style="width: 20px;">=</td>
                    <td style="width: 90px;" class="text-right"><?php echo e(number_format($seg['totalisator_akhir'], 2, ',', '.')); ?></td>
                    <td style="width: 30px;">ℓ</td>
                    <td colspan="4"></td>
                  </tr>
                  <tr>
                    <td>b. Totalisator Awal (<?php echo e($seg['start_datetime_label'] ?? '-'); ?>)</td>
                    <td>=</td>
                    <td class="text-right"><?php echo e(number_format($seg['totalisator_awal'], 2, ',', '.')); ?></td>
                    <td>ℓ</td>
                    <td style="width: 20px;">-</td>
                    <td colspan="3"></td>
                  </tr>
                  <tr style="border-top: 1px solid #cbd5e1;">
                    <td>c. Total Penjualan <?php echo e($sIdx + 1); ?> (a-b)</td>
                    <td>=</td>
                    <td class="text-right"><?php echo e(number_format($seg['total_penjualan'], 2, ',', '.')); ?></td>
                    <td>ℓ</td>
                    <td colspan="4"></td>
                  </tr>
                  <tr>
                    <td>d. Percobaan (Test Pump)</td>
                    <td>=</td>
                    <td class="text-right"><?php echo e($seg['test_pump'] > 0 ? number_format($seg['test_pump'], 2, ',', '.') : '-'); ?></td>
                    <td>ℓ</td>
                    <td>-</td>
                    <td colspan="3"></td>
                  </tr>
                  <tr style="font-weight: 700; border-top: 1px solid #cbd5e1;">
                    <td>B. Jumlah Penjualan <?php echo e($sIdx + 1); ?> (c-d)</td>
                    <td>=</td>
                    <td class="text-right"><?php echo e(number_format($seg['jumlah_penjualan'], 2, ',', '.')); ?></td>
                    <td>ℓ</td>
                    <td>x</td>
                    <td>Rp <?php echo e(number_format($seg['harga_jual'], 2, ',', '.')); ?></td>
                    <td class="text-center">&rarr;</td>
                    <td class="text-right">Rp <?php echo e(number_format($seg['jumlah_penjualan_rp'], 0, ',', '.')); ?></td>
                  </tr>
                  <tr>
                    <td>Sisa Stock (A-B)</td>
                    <td>=</td>
                    <td class="text-right"><?php echo e(number_format($seg['sisa_stok_teoretis'], 2, ',', '.')); ?></td>
                    <td>ℓ</td>
                    <td>x</td>
                    <td>Rp <?php echo e(number_format($seg['harga_beli'], 2, ',', '.')); ?></td>
                    <td class="text-center">&rarr;</td>
                    <td class="text-right">Rp <?php echo e(number_format($seg['sisa_stok_teoretis_rp'], 0, ',', '.')); ?> -</td>
                  </tr>
                  <tr style="border-top: 1px solid #cbd5e1; font-weight: 600;">
                    <td colspan="6">Jumlah <?php echo e($sIdx + 1); ?></td>
                    <td class="text-center">&rarr;</td>
                    <td class="text-right">Rp <?php echo e(number_format($seg['jumlah_penjualan_rp'] + $seg['sisa_stok_teoretis_rp'], 0, ',', '.')); ?></td>
                  </tr>
                  <tr>
                    <td>Losses / Gain &nbsp;&rarr;&nbsp; <span class="<?php echo e($seg['losses_gain'] < 0 ? 'text-danger' : 'text-success'); ?>"><?php echo e($seg['losses_gain'] < 0 ? 'Losses' : 'Gain'); ?> (<?php echo e(number_format($seg['losses_gain_persen'], 3)); ?>%)</span></td>
                    <td>=</td>
                    <td class="text-right <?php echo e($seg['losses_gain'] < 0 ? 'text-danger' : 'text-success'); ?>">(<?php echo e(number_format(abs($seg['losses_gain']), 3, ',', '.')); ?>)</td>
                    <td>ℓ</td>
                    <td>x</td>
                    <td>Rp <?php echo e(number_format($seg['harga_beli'], 2, ',', '.')); ?></td>
                    <td class="text-center">&rarr;</td>
                    <td class="text-right <?php echo e($seg['losses_gain'] < 0 ? 'text-danger' : 'text-success'); ?>">Rp (<?php echo e(number_format(abs($seg['losses_gain_rp']), 0, ',', '.')); ?>) +</td>
                  </tr>
                  <tr style="font-weight: 700; border-top: 1.5px solid #0f172a;">
                    <td colspan="6">C. Jumlah Penjualan Bersih <?php echo e($sIdx + 1); ?></td>
                    <td class="text-center">&rarr;</td>
                    <td class="text-right text-primary">Rp <?php echo e(number_format($seg['jumlah_penjualan_bersih'], 0, ',', '.')); ?></td>
                  </tr>
                </table>

                
                <div class="font-weight-bold mt-2" style="font-size: 12.5px;">
                  III. Sisa Stok Akhir <?php echo e($sIdx + 1); ?> : &nbsp;&nbsp; <?php echo e(number_format($seg['stok_akhir_cm'] ?? 0, 2)); ?> cm &nbsp;&nbsp; = &nbsp;&nbsp; <?php echo e(number_format($seg['stok_akhir_fisik'], 2, ',', '.')); ?> ℓ &nbsp; x &nbsp; Rp <?php echo e(number_format($seg['harga_beli'], 2, ',', '.')); ?> &nbsp;&rarr;&nbsp; <strong>Rp <?php echo e(number_format($seg['stok_akhir_fisik_rp'], 0, ',', '.')); ?></strong>
                </div>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <div class="alert alert-light border text-center py-3">
                <i class="fas fa-info-circle text-info mr-1"></i> Data segmen penjualan sedang diformat dari berkas Excel.
              </div>
            <?php endif; ?>

            
            <div class="row mt-3">
              <div class="col-md-5 mb-3">
                <div class="border p-2.5 rounded bg-light" style="font-size: 11.5px;">
                  <div class="font-weight-bold mb-2">IV. Sisa Stock DO Di Mees :</div>
                  <table class="table table-sm table-bordered bg-white mb-0 text-center">
                    <thead class="bg-light"><tr><th>PERTAMAX</th><th>KL</th></tr></thead>
                    <tbody>
                      <tr><td class="text-left">Stok Awal</td><td><?php echo e(number_format($h1['sisa_do_mees']['stok_awal_kl'] ?? 0, 2)); ?> KL</td></tr>
                      <tr><td class="text-left">Setor</td><td><?php echo e(number_format($h1['sisa_do_mees']['setor_kl'] ?? 0, 2)); ?> KL</td></tr>
                      <tr><td class="text-left">Setoran Tunai</td><td><?php echo e(number_format($h1['sisa_do_mees']['setoran_tunai'] ?? 0, 2)); ?> KL</td></tr>
                      <tr class="font-weight-bold"><td class="text-left">Jumlah</td><td><?php echo e(number_format($h1['sisa_do_mees']['setor_kl'] ?? 0, 2)); ?> KL</td></tr>
                      <tr><td class="text-left">Datang</td><td><?php echo e(number_format($h1['sisa_do_mees']['setor_kl'] ?? 0, 2)); ?> KL</td></tr>
                      <tr class="font-weight-bold bg-light"><td class="text-left">Sisa</td><td>- KL *)</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="col-md-7 mb-3">
                <div class="border p-2.5 rounded bg-light" style="font-size: 12px;">
                  <?php $__currentLoopData = $h1['segments'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sIdx => $seg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span>Total Laba Kotor <?php echo e($sIdx + 1); ?> = Penjualan (Rp <?php echo e(number_format($seg['jumlah_penjualan_bersih'], 0, ',', '.')); ?>) - Pembelian (Rp <?php echo e(number_format($seg['jumlah_pembelian_rp'], 0, ',', '.')); ?>)</span>
                      <strong class="text-dark">Rp <?php echo e(number_format($seg['laba_kotor'], 0, ',', '.')); ?></strong>
                    </div>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  <hr class="my-2">
                  <div class="d-flex justify-content-between align-items-center font-weight-bold" style="font-size: 13.5px;">
                    <span>Grand Total Laba Kotor Bulan Berjalan :</span>
                    <span class="text-success" style="font-size: 15px;">Rp <?php echo e(number_format($h1['grand_total_laba_kotor'] ?? 0, 0, ',', '.')); ?></span>
                  </div>
                </div>
              </div>
            </div>

            
            <?php if(!empty($h1['margin_history'])): ?>
            <div class="mt-3 border-top pt-2" style="font-size: 11px;">
              <div class="font-weight-bold mb-1">Ilustrasi Turun / Naik Margin Pertamax92 Pertashop :</div>
              <div class="table-responsive">
                <table class="table table-sm table-bordered text-center mb-0 bg-white" style="font-size: 11px;">
                  <thead class="thead-light">
                    <tr><th>Tanggal Efektif</th><th>Harga Beli</th><th>Harga Jual</th><th>Margin</th><th>Naik / Turun</th></tr>
                  </thead>
                  <tbody>
                    <?php $__currentLoopData = $h1['margin_history']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <tr>
                        <td><?php echo e($mh['tanggal']); ?></td>
                        <td>Rp <?php echo e(number_format($mh['harga_beli'], 2, ',', '.')); ?></td>
                        <td>Rp <?php echo e(number_format($mh['harga_jual'], 2, ',', '.')); ?></td>
                        <td class="font-weight-bold">Rp <?php echo e(number_format($mh['margin'], 2, ',', '.')); ?></td>
                        <td class="<?php echo e($mh['arah'] == 'Naik' ? 'text-success' : ($mh['arah'] == 'Turun' ? 'text-danger' : '')); ?>">
                          <?php echo e($mh['arah']); ?> <?php echo e($mh['diff'] > 0 ? '(Rp ' . number_format($mh['diff'], 2, ',', '.') . ')' : ''); ?>

                        </td>
                      </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </tbody>
                </table>
              </div>
            </div>
            <?php endif; ?>

          </div>
        </div>

        
        
        
        <div class="tab-pane fade" id="pv-hal2" role="tabpanel">
          <div class="report-paper-preview">
            <div class="report-header-title-pv">PERHITUNGAN LABA BERSIH <?php echo e($backdateExcelFile->formatted_period); ?></div>
            <div class="report-header-sub-pv">PERTASHOP <?php echo e($backdateExcelFile->shop->kode); ?> <?php echo e($backdateExcelFile->shop->alamat); ?></div>
            <div class="report-header-pt-pv">PT SERAYU AGUNG MANDIRI</div>

            
            <div class="font-weight-bold text-uppercase mb-1" style="font-size: 12.5px; text-decoration: underline;">PENDAPATAN</div>
            <table class="table-formal-pv mb-3">
              <tr>
                <td style="width: 280px;">1. LABA KOTOR ........................................................................</td>
                <td style="width: 20px;">=</td>
                <td style="width: 140px;" class="text-right">Rp <?php echo e(number_format($h1['grand_total_laba_kotor'] ?? 0, 0, ',', '.')); ?></td>
                <td style="width: 40px;"></td>
                <td class="text-right font-weight-bold" style="width: 200px;">A. Total Laba Kotor = Rp <?php echo e(number_format($h1['grand_total_laba_kotor'] ?? 0, 0, ',', '.')); ?></td>
              </tr>
            </table>

            
            <?php $pd = $h2['pengeluaran_details'] ?? []; ?>
            <div class="font-weight-bold text-uppercase mb-1" style="font-size: 12.5px; text-decoration: underline;">PENGELUARAN</div>
            <table class="table-formal-pv mb-2">
              <tr><td>1. GAJI OPERATOR ...................................................................</td><td>=</td><td class="text-right">Rp <?php echo e(number_format($pd['gaji_operator'] ?? 0, 0, ',', '.')); ?></td><td></td></tr>
              <tr><td>2. GAJI ADMIN ..........................................................................</td><td>=</td><td class="text-right">Rp <?php echo e(number_format($pd['gaji_admin'] ?? 500000, 0, ',', '.')); ?></td><td></td></tr>
              <tr><td>3. BIAYA CURAH / BONGKAR .................................................</td><td>=</td><td class="text-right">Rp <?php echo e(number_format($pd['biaya_curah'] ?? 50000, 0, ',', '.')); ?></td><td></td></tr>
              <tr><td>4. BIAYA TRANSFER BANK ....................................................</td><td>=</td><td class="text-right"><?php echo e(($pd['biaya_tf'] ?? 0) > 0 ? 'Rp ' . number_format($pd['biaya_tf'], 0, ',', '.') : 'Rp -'); ?></td><td></td></tr>
              <tr><td>5. LISTRIK .................................................................................</td><td>=</td><td class="text-right">Rp <?php echo e(number_format($pd['listrik'] ?? 0, 0, ',', '.')); ?></td><td></td></tr>
              <tr><td>6. AIR BERSIH ..........................................................................</td><td>=</td><td class="text-right">Rp <?php echo e(number_format($pd['air'] ?? 0, 0, ',', '.')); ?></td><td></td></tr>
              <tr><td>7. CASHBACK PENGECER .....................................................</td><td>=</td><td class="text-right">Rp <?php echo e(number_format($pd['cashback'] ?? 0, 0, ',', '.')); ?></td><td></td></tr>
              <tr><td>8. INTERNET .............................................................................</td><td>=</td><td class="text-right">Rp <?php echo e(number_format($pd['internet'] ?? 0, 0, ',', '.')); ?></td><td></td></tr>
              <tr><td>9. FOTOCOPY &amp; ATK ............................................................</td><td>=</td><td class="text-right"><?php echo e(($pd['atk'] ?? 0) > 0 ? 'Rp ' . number_format($pd['atk'], 0, ',', '.') : 'Rp -'); ?></td><td></td></tr>
              <tr><td>10. LAIN2 (<?php echo e($pd['lain_lain_notes'] ?? 'OPERASIONAL'); ?>) .................................</td><td>=</td><td class="text-right">Rp <?php echo e(number_format($pd['lain_lain'] ?? 0, 0, ',', '.')); ?></td><td></td></tr>
              <tr style="border-top: 1px solid #94a3b8; font-weight: 700;">
                <td colspan="2">B. Total Biaya</td>
                <td class="text-right text-danger">Rp <?php echo e(number_format($h2['total_biaya'] ?? 0, 0, ',', '.')); ?></td>
                <td></td>
              </tr>
            </table>

            
            <div class="row justify-content-end mb-3">
              <div class="col-md-6">
                <table class="table-formal-pv" style="font-size: 12.5px;">
                  <tr><td>A. Total Laba Kotor</td><td class="text-right font-weight-bold">Rp <?php echo e(number_format($h1['grand_total_laba_kotor'] ?? 0, 0, ',', '.')); ?></td></tr>
                  <tr><td>B. Total Biaya</td><td class="text-right font-weight-bold text-danger">Rp <?php echo e(number_format($h2['total_biaya'] ?? 0, 0, ',', '.')); ?> -</td></tr>
                  <tr style="border-top: 1.5px solid #0f172a; font-weight: 800;">
                    <td>(A-B) LABA BERSIH</td>
                    <td class="text-right text-success" style="font-size: 13.5px;">Rp <?php echo e(number_format($h2['laba_bersih'] ?? 0, 0, ',', '.')); ?></td>
                  </tr>
                  <tr class="text-muted">
                    <td>*) Alokasi Penambahan Modal dari 10% Profit</td>
                    <td class="text-right text-warning font-weight-bold">Rp <?php echo e(number_format($h2['alokasi_penambahan_modal'] ?? 0, 0, ',', '.')); ?> -</td>
                  </tr>
                  <tr style="font-weight: 700;">
                    <td>Saldo Laba Bersih (90%) yg Dibagi</td>
                    <td class="text-right">Rp <?php echo e(number_format($h2['saldo_laba_bersih_90'] ?? 0, 0, ',', '.')); ?></td>
                  </tr>
                  <tr>
                    <td>Saldo Laba Bersih Bulan Sebelumnya yg blm Dibagi</td>
                    <td class="text-right">Rp - +</td>
                  </tr>
                  <tr style="border-top: 2px solid #0f172a; font-weight: 800; background: #f8fafc;">
                    <td>Total Saldo Laba Bersih yg Dibagi [HOLD / PAYOUT]</td>
                    <td class="text-right text-primary" style="font-size: 14px;">Rp <?php echo e(number_format($h2['total_saldo_laba_dibagi'] ?? 0, 0, ',', '.')); ?></td>
                  </tr>
                </table>
              </div>
            </div>

            
            <div class="font-weight-bold mt-3 mb-1" style="font-size: 12.5px;">Pembagian Laba Bersih :</div>
            <table class="table-formal-pv mb-3" style="max-width: 60%;">
              <?php $__currentLoopData = $h2['investor_distributions'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                  <td style="width: 30px;"><?php echo e($idx + 1); ?>.</td>
                  <td style="width: 220px;"><?php echo e($inv['nama']); ?></td>
                  <td style="width: 70px;" class="text-right"><?php echo e(number_format($inv['persen'], 0)); ?>%</td>
                  <td style="width: 20px;" class="text-center">=</td>
                  <td class="text-right font-weight-bold">Rp <?php echo e(number_format($inv['nominal'], 0, ',', '.')); ?></td>
                </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              <tr style="border-top: 1.5px solid #0f172a; font-weight: 800;">
                <td colspan="4">Total</td>
                <td class="text-right text-success">Rp <?php echo e(number_format($h2['total_saldo_laba_dibagi'] ?? 0, 0, ',', '.')); ?></td>
              </tr>
            </table>

            
            <div class="font-weight-bold mt-3 mb-1" style="font-size: 12.5px;">Catatan Transfer :</div>
            <table class="table-formal-pv mb-3">
              <thead>
                <tr class="font-weight-bold" style="font-size: 11px; border-bottom: 1px solid #cbd5e1;">
                  <th>No</th>
                  <th>Bank &amp; No. Rekening</th>
                  <th>Atas Nama Rekening</th>
                  <th class="text-right">Nominal Transfer</th>
                  <th class="text-center">Status Transfer</th>
                </tr>
              </thead>
              <tbody>
                <?php $__currentLoopData = $h2['investor_distributions'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <tr>
                    <td><?php echo e($idx + 1); ?>.</td>
                    <td><strong><?php echo e($inv['nama_bank']); ?></strong> <?php echo e($inv['no_rekening']); ?></td>
                    <td>a/n <?php echo e($inv['atas_nama_rekening']); ?></td>
                    <td class="text-right font-weight-bold">Rp <?php echo e(number_format($inv['nominal'], 0, ',', '.')); ?></td>
                    <td class="text-center">
                      <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Siap Ditransfer</span>
                    </td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </tbody>
            </table>
            <div class="text-muted italic" style="font-size: 11px;">*) Jika Laba Positif, Alokasi Modal 10% Untuk Penambahan Modal Dasar</div>

          </div>
        </div>

        
        
        
        <div class="tab-pane fade" id="pv-hal3" role="tabpanel">
          <div class="report-paper-preview">
            <div class="report-header-title-pv">POSISI MODAL KERJA PERIODE <?php echo e($backdateExcelFile->formatted_period); ?></div>
            <div class="report-header-sub-pv">PERTASHOP <?php echo e($backdateExcelFile->shop->kode); ?> <?php echo e($backdateExcelFile->shop->alamat); ?></div>
            <div class="report-header-pt-pv">PT SERAYU AGUNG MANDIRI</div>

            <div class="d-flex justify-content-between align-items-center mb-2 font-weight-bold" style="font-size: 12.5px; border-bottom: 2px solid #0f172a; padding-bottom: 4px;">
              <span>POSISI MODAL KERJA</span>
              <span>Saldo Awal Modal Periode Bulan Sebelumnya : <strong class="text-primary">Rp <?php echo e(number_format($h3['saldo_awal_modal'] ?? 68019683, 0, ',', '.')); ?></strong></span>
            </div>

            <table class="table-formal-pv mb-3" style="font-size: 12.5px;">
              <tbody>
                <tr>
                  <td style="width: 30px;">1.</td>
                  <td style="width: 260px;">DO yang Masih Ada di Pertamina</td>
                  <td style="width: 140px;" class="text-center"><?php echo e(($h3['do_di_pertamina'] ?? 0) > 0 ? '5.00 ℓ x Rp ' . number_format($h1['final_harga_beli'] ?? 15334.81, 2, ',', '.') : '- ℓ x Rp ' . number_format($h1['final_harga_beli'] ?? 15334.81, 2, ',', '.')); ?></td>
                  <td style="width: 20px;">:</td>
                  <td class="text-right" style="width: 140px;"><?php echo e(($h3['do_di_pertamina'] ?? 0) > 0 ? 'Rp ' . number_format($h3['do_di_pertamina'], 0, ',', '.') : 'Rp -'); ?></td>
                </tr>
                <tr>
                  <td>2.</td>
                  <td>Uang Di Bank Periode Bulan ini</td>
                  <td class="text-center"></td>
                  <td>:</td>
                  <td class="text-right">Rp <?php echo e(number_format($h3['uang_di_bank'] ?? 0, 0, ',', '.')); ?></td>
                </tr>
                <tr>
                  <td>3.</td>
                  <td>Kas Kecil di Pertashop (TUNAI)</td>
                  <td class="text-center"></td>
                  <td>:</td>
                  <td class="text-right text-muted">Rp (<?php echo e(number_format(abs($h3['kas_kecil'] ?? 0), 0, ',', '.')); ?>)</td>
                </tr>
                <tr>
                  <td>4.</td>
                  <td>Sisa Stok yang Masih ada Di Pertashop</td>
                  <td class="text-center"><?php echo e(number_format($h1['final_stok_liter'] ?? 0, 2, ',', '.')); ?> ℓ x Rp <?php echo e(number_format($h1['final_harga_beli'] ?? 15334.81, 2, ',', '.')); ?></td>
                  <td>:</td>
                  <td class="text-right text-muted">Rp (<?php echo e(number_format(abs($h3['sisa_stok_pertashop_rp'] ?? 0), 0, ',', '.')); ?>)</td>
                </tr>
                <tr>
                  <td>5.</td>
                  <td>Hasil Penjualan yang Belum Disetor di Akhir Periode (TUNAI)</td>
                  <td class="text-center"></td>
                  <td>:</td>
                  <td class="text-right text-muted">Rp (<?php echo e(number_format(abs($h3['hasil_belum_disetor'] ?? 0), 0, ',', '.')); ?>)</td>
                </tr>
                <tr>
                  <td>6.</td>
                  <td>Piutang</td>
                  <td class="text-center"></td>
                  <td>:</td>
                  <td class="text-right text-muted">Rp (<?php echo e(number_format(abs($h3['piutang'] ?? 0), 0, ',', '.')); ?>) +</td>
                </tr>
                <tr style="border-top: 1.5px solid #0f172a; font-weight: 700; background: #f8fafc;">
                  <td colspan="3" class="text-right">A. Sub Total Saldo Akhir Modal :</td>
                  <td>:</td>
                  <td class="text-right">Rp <?php echo e(number_format($h3['subtotal_a'] ?? 0, 0, ',', '.')); ?></td>
                </tr>

                
                <tr>
                  <td>7.</td>
                  <td>Bunga Bank Periode Bulan ini</td>
                  <td class="text-center"></td>
                  <td>:</td>
                  <td class="text-right text-success">Rp <?php echo e(number_format($h3['bunga_bank'] ?? 0, 0, ',', '.')); ?></td>
                </tr>
                <tr>
                  <td>8.</td>
                  <td>Pajak Bank Periode Bulan ini</td>
                  <td class="text-center"></td>
                  <td>:</td>
                  <td class="text-right text-danger">Rp (<?php echo e(number_format($h3['pajak_bank'] ?? 0, 0, ',', '.')); ?>)</td>
                </tr>
                <tr>
                  <td>9.</td>
                  <td>Profit Sharing yang dibagi ke Investor</td>
                  <td class="text-center"></td>
                  <td>:</td>
                  <td class="text-right font-weight-bold text-dark">Rp <?php echo e(number_format($h3['profit_sharing_dibagi'] ?? 0, 0, ',', '.')); ?></td>
                </tr>
                <tr>
                  <td>10.</td>
                  <td><span class="<?php echo e(($h3['penambahan_keuntungan'] ?? 0) >= 0 ? 'text-primary' : 'text-danger'); ?>">Penambahan / Pengurangan Modal dari Keuntungan bulan ini</span></td>
                  <td class="text-center"></td>
                  <td>:</td>
                  <td class="text-right font-weight-bold <?php echo e(($h3['penambahan_keuntungan'] ?? 0) >= 0 ? 'text-primary' : 'text-danger'); ?>">
                    Rp <?php echo e(number_format($h3['penambahan_keuntungan'] ?? 0, 0, ',', '.')); ?> +
                  </td>
                </tr>
                <tr style="border-top: 1.5px solid #0f172a; font-weight: 700; background: #f8fafc;">
                  <td colspan="3" class="text-right">B. Sub Total Penambahan Modal :</td>
                  <td>:</td>
                  <td class="text-right">Rp <?php echo e(number_format($h3['subtotal_b'] ?? 0, 0, ',', '.')); ?></td>
                </tr>
                <tr style="border-top: 1px solid #cbd5e1; font-weight: 700;">
                  <td colspan="3" class="text-right">C. Sub Total Saldo Akhir Modal (A+B) :</td>
                  <td>:</td>
                  <td class="text-right">Rp <?php echo e(number_format($h3['subtotal_c'] ?? 0, 0, ',', '.')); ?></td>
                </tr>
                <tr style="border-top: 2px solid #0f172a; font-weight: 800; background: #eff6ff; font-size: 13.5px;">
                  <td colspan="3" class="text-right text-primary">D. Total Saldo Akhir Modal (C-9) :</td>
                  <td>:</td>
                  <td class="text-right text-primary" style="font-size: 15px;">Rp <?php echo e(number_format($h3['total_saldo_akhir_modal'] ?? 0, 0, ',', '.')); ?></td>
                </tr>
              </tbody>
            </table>

          </div>
        </div>

        
        
        
        <div class="tab-pane fade" id="pv-hal4" role="tabpanel">
          <div class="report-paper-preview">
            <div class="report-header-title-pv">REKAPITULASI NILAI MODAL <?php echo e($backdateExcelFile->shop->nama); ?></div>
            <div class="report-header-sub-pv"><?php echo e($backdateExcelFile->shop->kode); ?> <?php echo e($backdateExcelFile->shop->alamat); ?></div>
            <div class="report-header-pt-pv">PT SERAYU AGUNG MANDIRI</div>

            <div class="table-responsive mb-3" style="max-height: 480px; overflow-y: auto;">
              <table class="table-formal-pv table-formal-bordered-pv table-sm text-center" style="font-size: 11px; white-space: nowrap;">
                <thead>
                  <tr>
                    <th style="width: 50px;">Tahun Ke</th>
                    <th>Bulan</th>
                    <th>Nilai Modal Awal</th>
                    <th>Penyusutan Rugi</th>
                    <th>Pajak &amp; Biaya Bank</th>
                    <th>Alokasi Keuntungan</th>
                    <th>Bunga Bank</th>
                    <th>Nilai Penambahan/Penyusutan</th>
                    <th>Akumulasi Modal</th>
                    <th>Posisi Akhir Modal</th>
                    <th>Harga Beli Pertamax</th>
                    <th>Konversi Liter</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $bulanIndo = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                  ?>
                  <?php $__empty_1 = true; $__currentLoopData = $h4['capital_recaps'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recap): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                      <td><?php echo e($recap['tahun_ke'] ?? 1); ?></td>
                      <td class="text-left font-weight-bold"><?php echo e($bulanIndo[$recap['bulan']] ?? $recap['bulan']); ?> <?php echo e($recap['tahun']); ?></td>
                      <td class="text-right">Rp <?php echo e(number_format($recap['nilai_modal_awal'], 0, ',', '.')); ?></td>
                      <td class="text-right <?php echo e(($recap['penyusutan_rugi'] ?? 0) < 0 ? 'text-danger font-weight-bold' : ''); ?>">
                        <?php echo e(($recap['penyusutan_rugi'] ?? 0) < 0 ? 'Rp (' . number_format(abs($recap['penyusutan_rugi']), 0, ',', '.') . ')' : '-'); ?>

                      </td>
                      <td class="text-right <?php echo e(($recap['penyusutan_pajak_bank'] ?? 0) < 0 ? 'text-danger' : ''); ?>">
                        <?php echo e(($recap['penyusutan_pajak_bank'] ?? 0) < 0 ? 'Rp (' . number_format(abs($recap['penyusutan_pajak_bank']), 0, ',', '.') . ')' : '-'); ?>

                      </td>
                      <td class="text-right <?php echo e(($recap['penambahan_keuntungan'] ?? 0) > 0 ? 'text-success font-weight-bold' : ''); ?>">
                        <?php echo e(($recap['penambahan_keuntungan'] ?? 0) > 0 ? 'Rp ' . number_format($recap['penambahan_keuntungan'], 0, ',', '.') : '-'); ?>

                      </td>
                      <td class="text-right <?php echo e(($recap['penambahan_bunga_bank'] ?? 0) > 0 ? 'text-success' : ''); ?>">
                        <?php echo e(($recap['penambahan_bunga_bank'] ?? 0) > 0 ? 'Rp ' . number_format($recap['penambahan_bunga_bank'], 0, ',', '.') : '-'); ?>

                      </td>
                      <td class="text-right font-weight-bold <?php echo e(($recap['nilai_penambahan_penyusutan'] ?? 0) < 0 ? 'text-danger' : 'text-dark'); ?>">
                        Rp <?php echo e(number_format($recap['nilai_penambahan_penyusutan'] ?? 0, 0, ',', '.')); ?>

                      </td>
                      <td class="text-right font-weight-bold text-primary">
                        Rp <?php echo e(number_format($recap['akumulasi_penambahan_penyusutan'] ?? 0, 0, ',', '.')); ?>

                      </td>
                      <td class="text-right font-weight-bold" style="font-size: 11.5px; color: #0f172a;">
                        Rp <?php echo e(number_format($recap['posisi_akhir_modal'] ?? 0, 0, ',', '.')); ?>

                      </td>
                      <td class="text-right">Rp <?php echo e(number_format($recap['harga_beli_pertamax'] ?? 0, 2, ',', '.')); ?></td>
                      <td class="text-right font-weight-bold"><?php echo e(number_format($recap['konversi_liter'] ?? 0, 2, ',', '.')); ?> ℓ</td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                      <td colspan="12" class="text-center py-3 text-muted">Belum ada data Rekapitulasi Modal.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            
            <div class="row justify-content-between align-items-center p-3 rounded bg-light border">
              <div class="col-md-6" style="font-size: 12px;">
                <table class="table-formal-pv">
                  <tr>
                    <td style="width: 140px;">Nilai Modal Dasar</td>
                    <td style="width: 20px;">=</td>
                    <td style="width: 130px;" class="text-right font-weight-bold">Rp <?php echo e(number_format($h4['modal_awal_dasar'] ?? 60000000, 0, ',', '.')); ?></td>
                    <td class="text-right" style="width: 90px;">100.00%</td>
                  </tr>
                  <tr>
                    <td>Penambahan Modal</td>
                    <td>=</td>
                    <td class="text-right font-weight-bold text-success">+ Rp <?php echo e(number_format($h4['total_akumulasi_modal'] ?? 0, 0, ',', '.')); ?></td>
                    <td class="text-right text-success">+ <?php echo e(number_format($h4['persen_penambahan_modal'] ?? 0, 2)); ?>%</td>
                  </tr>
                  <tr style="border-top: 1.5px solid #0f172a; font-weight: 800;">
                    <td>Total Modal</td>
                    <td>=</td>
                    <td class="text-right text-primary" style="font-size: 13.5px;">Rp <?php echo e(number_format($h4['grand_total_modal'] ?? 60000000, 0, ',', '.')); ?></td>
                    <td class="text-right text-primary" style="font-size: 13.5px;"><?php echo e(number_format($h4['persen_grand_total'] ?? 100, 2)); ?>%</td>
                  </tr>
                </table>
              </div>
              <div class="col-md-5 text-right">
                <div class="p-2.5 bg-white border rounded shadow-xs">
                  <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 10.5px;">Saldo Akhir Modal Terverifikasi</small>
                  <span class="h5 font-weight-bold text-primary mb-0">Rp <?php echo e(number_format($h3['total_saldo_akhir_modal'] ?? 0, 0, ',', '.')); ?></span>
                </div>
              </div>
            </div>

          </div>
        </div>

        
        
        
        <div class="tab-pane fade" id="pv-bkh" role="tabpanel">
          
          <div class="row mb-4">
            <div class="col-md-4 col-lg-2 mb-3">
              <div class="kpi-card-v2 kpi-blue">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px;">1. Tot Awal</small>
                  <div class="kpi-icon-wrapper"><i class="fas fa-play"></i></div>
                </div>
                <h5 class="font-weight-bold text-dark mb-0" style="font-size: 15px;">
                  <?php echo e(number_format($summary['totalisator_awal'] ?? 0, 2, ',', '.')); ?>

                </h5>
                <small class="text-muted" style="font-size: 11px;">Awal Bulan</small>
              </div>
            </div>
            <div class="col-md-4 col-lg-2 mb-3">
              <div class="kpi-card-v2 kpi-emerald">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px;">2. Tot Akhir</small>
                  <div class="kpi-icon-wrapper"><i class="fas fa-flag-checkered"></i></div>
                </div>
                <h5 class="font-weight-bold text-dark mb-0" style="font-size: 15px;">
                  <?php echo e(number_format($summary['totalisator_akhir'] ?? 0, 2, ',', '.')); ?>

                </h5>
                <small class="text-muted" style="font-size: 11px;">Akhir Bulan</small>
              </div>
            </div>
            <div class="col-md-4 col-lg-2 mb-3">
              <div class="kpi-card-v2 kpi-indigo">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px;">3. Terjual (L)</small>
                  <div class="kpi-icon-wrapper"><i class="fas fa-gas-pump"></i></div>
                </div>
                <h5 class="font-weight-bold text-dark mb-0" style="font-size: 15px;">
                  <?php echo e(number_format($summary['jumlah_liter_terjual'] ?? 0, 2, ',', '.')); ?> L
                </h5>
                <small class="text-muted" style="font-size: 11px;">Total 1 Bulan</small>
              </div>
            </div>
            <div class="col-md-4 col-lg-2 mb-3">
              <div class="kpi-card-v2 kpi-cyan">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px;">6. Stok Akhir</small>
                  <div class="kpi-icon-wrapper"><i class="fas fa-boxes"></i></div>
                </div>
                <h5 class="font-weight-bold text-dark mb-0" style="font-size: 15px;">
                  <?php echo e(number_format($summary['stok_akhir'] ?? 0, 2, ',', '.')); ?> L
                </h5>
                <small class="text-muted" style="font-size: 11px;">Akhir Bulan</small>
              </div>
            </div>
            <div class="col-md-4 col-lg-2 mb-3">
              <div class="kpi-card-v2 kpi-amber">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px;">7. Pengeluaran</small>
                  <div class="kpi-icon-wrapper"><i class="fas fa-receipt"></i></div>
                </div>
                <h5 class="font-weight-bold text-dark mb-0" style="font-size: 13.5px;">
                  Rp <?php echo e(number_format($summary['total_pengeluaran']['total_rp'] ?? 0, 0, ',', '.')); ?>

                </h5>
                <small class="text-muted" style="font-size: 11px;">Operasional</small>
              </div>
            </div>
            <div class="col-md-4 col-lg-2 mb-3">
              <div class="kpi-card-v2 kpi-rose">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <small class="text-muted font-weight-bold text-uppercase" style="font-size: 11px;">8. Belum Disetor</small>
                  <div class="kpi-icon-wrapper"><i class="fas fa-exclamation-circle"></i></div>
                </div>
                <h5 class="font-weight-bold text-dark mb-0" style="font-size: 13.5px;">
                  Rp <?php echo e(number_format($summary['total_belum_disetorkan']['total_rp'] ?? 0, 0, ',', '.')); ?>

                </h5>
                <small class="text-muted" style="font-size: 11px;">Selisih Setoran</small>
              </div>
            </div>
          </div>

          
          <div class="row">
            
            <div class="col-md-6 mb-4">
              <div class="card shadow-sm border" style="border-radius: 10px; overflow: hidden;">
                <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 border-bottom">
                  <h6 class="mb-0 font-weight-bold text-dark" style="font-size: 13.5px;"><i class="fas fa-vial text-info mr-1"></i> 4. Test Pump</h6>
                  <span class="badge badge-info px-2 py-1" style="font-size: 11px;">Total: <?php echo e(number_format($summary['test_pump']['total_volume'] ?? 0, 2, ',', '.')); ?> L</span>
                </div>
                <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                  <table class="table table-sm table-striped table-hover mb-0" style="font-size: 12px;">
                    <thead class="thead-light"><tr><th>No</th><th>Tanggal</th><th class="text-right">Volume</th><th class="text-right">Nominal</th></tr></thead>
                    <tbody>
                      <?php $__empty_1 = true; $__currentLoopData = $summary['test_pump']['details'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $tp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr><td><?php echo e($idx + 1); ?></td><td><?php echo e($tp['tgl']); ?></td><td class="text-right font-weight-bold"><?php echo e(number_format($tp['volume'], 2, ',', '.')); ?> L</td><td class="text-right">Rp <?php echo e(number_format($tp['nominal'], 0, ',', '.')); ?></td></tr>
                      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada data test pump.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            
            <div class="col-md-6 mb-4">
              <div class="card shadow-sm border" style="border-radius: 10px; overflow: hidden;">
                <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 border-bottom">
                  <h6 class="mb-0 font-weight-bold text-dark" style="font-size: 13.5px;"><i class="fas fa-truck-loading text-success mr-1"></i> 5. Pembelian / Penerimaan BBM</h6>
                  <span class="badge badge-success px-2 py-1" style="font-size: 11px;">Total: <?php echo e(number_format($summary['pembelian_bbm']['total_volume_kl'] ?? 0, 2, ',', '.')); ?> KL</span>
                </div>
                <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                  <table class="table table-sm table-striped table-hover mb-0" style="font-size: 12px;">
                    <thead class="thead-light"><tr><th>No</th><th>Tanggal</th><th>Tipe</th><th class="text-right">Volume (L)</th><th class="text-right">Volume (KL)</th></tr></thead>
                    <tbody>
                      <?php $__empty_1 = true; $__currentLoopData = $summary['pembelian_bbm']['details'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $bbm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr><td><?php echo e($idx + 1); ?></td><td class="font-weight-bold"><?php echo e($bbm['tgl']); ?></td><td><span class="badge badge-success"><?php echo e($bbm['tipe'] ?? 'Penerimaan'); ?></span></td><td class="text-right font-weight-bold"><?php echo e(number_format($bbm['jumlah_liter'] ?? ($bbm['jumlah_kl'] * 1000), 0, ',', '.')); ?> L</td><td class="text-right text-success"><?php echo e(number_format($bbm['jumlah_kl'], 3, ',', '.')); ?> KL</td></tr>
                      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data penerimaan BBM.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

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

    const currentShopAliases = <?php echo json_encode($currentShopAliasesData ?? [], 15, 512) ?>;
    const allOtherShops = <?php echo json_encode($allOtherShopsData ?? [], 15, 512) ?>;
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

function confirmSyncBackdate() {
  const form = document.getElementById('sync-backdate-form');
  const shopName = "<?php echo e(addslashes($backdateExcelFile->shop->nama)); ?>";
  const period = "<?php echo e(addslashes($backdateExcelFile->formatted_period)); ?>";
  
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Sinkronkan Data Laporan?',
      html: `Apakah Anda yakin ingin menyinkronkan seluruh isi berkas Excel ini ke <strong>Laporan Bulanan</strong> &amp; <strong>Rekapitulasi Nilai Modal</strong> untuk toko <strong>${shopName}</strong> (Periode: <strong>${period}</strong>)?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#0284c7',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-sync-alt mr-1"></i> Ya, Sinkronkan Sekarang',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Memproses Sinkronisasi...',
          text: 'Mohon tunggu, data sedang dihitung dan disinkronkan ke database.',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        form.submit();
      }
    });
  } else {
    if (confirm(`Sinkronkan seluruh isi berkas Excel ini ke Laporan Bulanan & Rekapitulasi Nilai Modal untuk toko ${shopName} periode ${period}?`)) {
      form.submit();
    }
  }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts._new_admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/backdate_excel/show.blade.php ENDPATH**/ ?>