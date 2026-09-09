<?php $__env->startSection('title', 'Sistem Penggajian'); ?>

<?php $__env->startPush('style'); ?>
<style>
  /* ─── Tab Navigation ─────────────────────────────────── */
  .payroll-tabs {
    display: flex;
    gap: 6px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 24px;
    flex-wrap: wrap;
  }
  .payroll-tab-btn {
    padding: 10px 22px;
    font-size: 13.5px;
    font-weight: 700;
    border: none;
    background: transparent;
    color: #64748b;
    border-radius: 10px 10px 0 0;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .payroll-tab-btn:hover {
    color: #0f172a;
    background: #f1f5f9;
  }
  .payroll-tab-btn.active {
    color: #059669;
    border-bottom-color: #059669;
    background: #ffffff;
    box-shadow: 0 -2px 6px rgba(0,0,0,0.02);
  }
  .payroll-tab-pane { display: none; }
  .payroll-tab-pane.active { display: block; }

  /* ─── Status Pills ───────────────────────────────────── */
  .status-ok   { color: #15803d; font-weight: 600; }
  .status-warn { color: #b45309; font-weight: 600; }
  .status-bad  { color: #b91c1c; font-weight: 600; }

  /* ─── Modern Cards ───────────────────────────────────── */
  .payroll-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    overflow: hidden;
  }

  .payroll-card-header {
    padding: 18px 22px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
  }

  /* ─── Lock Info ──────────────────────────────────────── */
  .lock-info-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px 22px;
    margin-bottom: 16px;
    font-size: 13.5px;
  }
  .lock-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #edf2f7;
  }
  .lock-info-row:last-child { border-bottom: none; }
  .lock-info-label { color: #64748b; font-size: 13px; font-weight: 600; }

  /* Buttons */
  .btn-emerald-primary {
    background: #059669;
    color: #ffffff;
    font-weight: 700;
    border: none;
    border-radius: 10px;
    padding: 10px 18px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
  }
  .btn-emerald-primary:hover {
    background: #047857;
    color: #ffffff;
    transform: translateY(-1px);
  }

  .btn-slate-outline {
    background: #ffffff;
    color: #334155;
    font-weight: 700;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    padding: 9px 18px;
    transition: all 0.2s ease;
  }
  .btn-slate-outline:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #94a3b8;
  }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap" style="gap: 16px;">
  <div>
    <h1 class="page-title mb-1" style="font-size: 24px; font-weight: 900; color: #0f172a;">Sistem Penggajian</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Pengaturan rumus gaji, proses kalkulasi bulanan, dan persetujuan penggajian operator Pertashop.</p>
  </div>
</div>

<?php if(session('success')): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 12px; border: none; background: #dcfce7; color: #15803d; font-weight: 600;">
    <i class="fas fa-check-circle mr-1"></i> <?php echo e(session('success')); ?>

    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
<?php endif; ?>
<?php if(isset($errors) && $errors->any()): ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius: 12px; border: none; background: #fee2e2; color: #b91c1c; font-weight: 600;">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($e); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
<?php endif; ?>


<div class="payroll-tabs">
  <button class="payroll-tab-btn active" data-tab="pengaturan">
    <i class="fas fa-sliders-h"></i> Pengaturan Gaji
  </button>
  <button class="payroll-tab-btn" data-tab="proses">
    <i class="fas fa-calculator"></i> Proses Gaji Bulanan
  </button>
  <button class="payroll-tab-btn" data-tab="kunci">
    <i class="fas fa-lock"></i> Kunci &amp; Setujui
  </button>
  <?php if($canViewBenchmark): ?>
    <button class="payroll-tab-btn" data-tab="perbandingan">
      <i class="fas fa-chart-bar"></i> Perbandingan Outlet
    </button>
  <?php endif; ?>
</div>




<div class="payroll-tab-pane active" id="tab-pengaturan">

  
  <div class="payroll-card mb-4" style="padding: 14px 20px;">
    <form method="GET" action="<?php echo e(route('payroll-systems.index')); ?>" class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 12px;">
      <input type="hidden" name="tab" value="pengaturan">
      <div class="d-flex align-items-center" style="gap: 10px;">
        <label class="text-muted mb-0" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Filter Pertashop:</label>
        <select name="shop_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; width: auto; min-width: 180px;" onchange="this.form.submit()">
          <?php if(in_array(Auth::user()->role, ['super-admin', 'super_admin'])): ?>
            <option value="">Semua Pertashop</option>
          <?php endif; ?>
          <?php $__currentLoopData = $shops; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($shop->id); ?>" <?php echo e(request('shop_id') == $shop->id ? 'selected' : ''); ?>><?php echo e($shop->nama); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
      <?php if(collect(['super-admin', 'super_admin', 'admin'])->contains(Auth::user()->role)): ?>
        <a href="<?php echo e(route('payroll-systems.create')); ?>" class="btn-emerald-primary btn-sm text-decoration-none" style="font-size: 13px;">
          <i class="fas fa-plus mr-1"></i> Tambah Pengaturan Baru
        </a>
      <?php endif; ?>
    </form>
  </div>

  
  <div class="payroll-card">
    <div class="payroll-card-header">
      <div class="font-weight-bold" style="font-size: 16px; color: #0f172a;">
        <i class="fas fa-list-ul mr-2 text-success"></i>Daftar Pengaturan Gaji per Pertashop
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: #f8fafc;">
            <th style="font-size: 11px; text-transform: uppercase; color: #475569; padding: 12px 16px;">Pertashop</th>
            <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Nama Pengaturan</th>
            <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Skema Gaji</th>
            <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Rate/Liter</th>
            <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Gaji Pokok</th>
            <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Transport/Hari</th>
            <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Potongan Absen</th>
            <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Metode Potongan</th>
            <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Losses/Gain</th>
            <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #475569;">Status</th>
            <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #475569;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $payrollSystems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ps): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td style="font-size: 13px; font-weight: 700; color: #0f172a; padding: 12px 16px;">
              <span class="badge" style="background: #e2e8f0; color: #334155; padding: 5px 10px; border-radius: 6px;"><?php echo e($ps->shop->nama); ?></span>
            </td>
            <td style="font-size: 13px; font-weight: 600;"><?php echo e($ps->nama_sistem); ?></td>
            <td style="font-size: 13px;">
              <?php
                $skema = $ps->tipe_skema ?? ($ps->ada_gaji_pokok && $ps->ada_rate_per_liter ? 'hibrid' : ($ps->ada_gaji_pokok ? 'gaji_pokok_murni' : 'komisi_murni'));
              ?>
              <?php if($skema === 'komisi_murni'): ?>
                <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 5px 10px; border-radius: 6px; font-weight: 700;">
                  <i class="fas fa-gas-pump mr-1"></i> Komisi Murni
                </span>
              <?php elseif($skema === 'gaji_pokok_murni'): ?>
                <span class="badge" style="background: #f3e8ff; color: #6b21a8; padding: 5px 10px; border-radius: 6px; font-weight: 700;">
                  <i class="fas fa-money-check-alt mr-1"></i> Gaji Pokok Murni
                </span>
              <?php else: ?>
                <span class="badge" style="background: #dcfce7; color: #166534; padding: 5px 10px; border-radius: 6px; font-weight: 700;">
                  <i class="fas fa-layer-group mr-1"></i> Hibrid
                </span>
              <?php endif; ?>
            </td>
            <td style="font-size: 13px;">
              <?php if($ps->ada_rate_per_liter || in_array($ps->tipe_skema, ['komisi_murni', 'hibrid'])): ?>
                Rp <?php echo e(number_format($ps->rate_per_liter, 0, ',', '.')); ?>/L
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size: 13px;">
              <?php if($ps->ada_gaji_pokok || in_array($ps->tipe_skema, ['gaji_pokok_murni', 'hibrid'])): ?>
                Rp <?php echo e(number_format($ps->nominal_gaji_pokok, 0, ',', '.')); ?>

              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size: 13px;">
              <?php if(floatval($ps->rate_transport_per_hari ?? 0) > 0): ?>
                Rp <?php echo e(number_format($ps->rate_transport_per_hari, 0, ',', '.')); ?>

              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size: 13px; color: #dc2626; font-weight: 600;">
              Rp <?php echo e(number_format($ps->potongan_per_hari_alpha, 0, ',', '.')); ?>

            </td>
            <td style="font-size: 13px;">
              <?php if(($ps->metode_potongan_alpha ?? 'nominal_tetap') === 'prorata_gaji_pokok'): ?>
                Prorata Gaji
              <?php else: ?>
                Nominal Tetap
              <?php endif; ?>
            </td>
            <td style="font-size: 13px;">
              <?php echo e($ps->perlakuan_losses_gain_label); ?>

            </td>
            <td class="text-center">
              <?php if($ps->aktif): ?>
                <span class="badge badge-success px-2 py-1" style="border-radius: 6px;">Aktif</span>
              <?php else: ?>
                <span class="badge badge-danger px-2 py-1" style="border-radius: 6px;">Nonaktif</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if(collect(['super-admin', 'super_admin', 'admin'])->contains(Auth::user()->role)): ?>
                <a href="<?php echo e(route('payroll-systems.edit', $ps->id)); ?>" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px; font-size: 11px; padding: 4px 10px;">
                  <i class="fas fa-edit mr-1"></i> Edit
                </a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr>
            <td colspan="11" class="text-center text-muted py-5" style="font-size: 13px;">

              <div style="width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; color: #94a3b8; font-size: 20px;">
                <i class="fas fa-receipt"></i>
              </div>
              Belum ada pengaturan sistem penggajian.<br>
              <?php if(collect(['super-admin', 'super_admin', 'admin'])->contains(Auth::user()->role)): ?>
                <a href="<?php echo e(route('payroll-systems.create')); ?>" class="font-weight-bold" style="color: #059669;">Tambah pengaturan sekarang</a>.
              <?php endif; ?>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>





<div class="payroll-tab-pane" id="tab-proses">

  <div class="row">

    
    <div class="col-lg-5 mb-4">
      
      
      <div class="payroll-card mb-4">
        <div class="payroll-card-header">
          <div class="font-weight-bold" style="font-size: 15px; color: #0f172a;">
            <i class="fas fa-calculator text-success mr-2"></i>Hitung Gaji Bulanan
          </div>
        </div>

        <div style="padding: 22px;">
          <form action="<?php echo e(route('payroll.generate')); ?>" method="POST" id="form-generate">
            <?php echo csrf_field(); ?>
            
            <div class="form-group mb-3">
              <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Pertashop / Outlet</label>
              <select name="shop_id" id="gen-shop" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required onchange="window.location.href='?tab=proses&shop_id=' + this.value + '&tahun=<?php echo e($selectedTahun); ?>'">
                <?php $__currentLoopData = $shops; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($shop->id); ?>" <?php echo e($selectedShopId == $shop->id ? 'selected' : ''); ?>><?php echo e($shop->nama); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group col-6 mb-3">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Bulan Periode</label>
                <select name="bulan" id="gen-bulan" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required onchange="checkFinalStatus()">
                  <?php $__currentLoopData = range(1,12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                      $namaB = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$b];
                      $statusB = $periodStatusMap->get($b);
                    ?>
                    <option value="<?php echo e($b); ?>"
                      data-status="<?php echo e($statusB ?? ''); ?>"
                      <?php echo e(now()->month == $b ? 'selected' : ''); ?>>
                      <?php echo e($namaB); ?><?php echo e($statusB ? ' (' . ucfirst($statusB) . ')' : ''); ?>

                    </option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </div>
              
              <div class="form-group col-6 mb-3">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Tahun</label>
                <select name="tahun" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required>
                  <?php $__currentLoopData = $availableYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($y); ?>" <?php echo e($selectedTahun == $y ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </div>
            </div>

            
            <div id="final-alert" class="alert alert-danger py-2 small d-none" style="border-radius: 10px; font-weight: 600;">
              <i class="fas fa-lock mr-1"></i> Periode sudah Final dan terkunci, tidak bisa dihitung ulang.
            </div>
            <div id="draft-alert" class="alert alert-warning py-2 small d-none" style="border-radius: 10px; font-weight: 600;">
              <i class="fas fa-info-circle mr-1"></i> Sudah ada draft untuk bulan ini. Menghitung ulang akan memperbarui data draft.
            </div>

            
            <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px;">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span style="font-size: 12px; font-weight: 700; color: #1e293b;">
                  <i class="fas fa-plus-circle text-primary mr-1"></i> Komponen Tambahan (Opsional)
                </span>
                <button type="button" class="btn btn-xs btn-slate-outline btn-add-custom-row" style="padding: 3px 8px; font-size: 11px; border-radius: 6px;">
                  + Tambah Baris
                </button>
              </div>
              <div id="custom-items-container-sys">
                
              </div>
              <small class="text-muted d-block mt-1" style="font-size: 11px;">
                Tambahkan bonus atau potongan insidental khusus bulan ini.
              </small>
            </div>

            <p class="text-muted small mb-3">
              <i class="fas fa-info-circle mr-1 text-primary"></i> Sistem otomatis mengagregasi volume penjualan liter dan rekap kehadiran operator.
            </p>

            <button type="button" onclick="confirmSingleGenerate(event)" id="btn-submit-gen" class="btn btn-block btn-emerald-primary font-weight-bold" style="border-radius: 10px; padding: 12px;">
              <i class="fas fa-bolt mr-1"></i> Hitung Gaji Bulan Ini
            </button>
          </form>

          <hr class="my-3" style="border-color: #f1f5f9;">

          <form id="form-bulk-generate" action="<?php echo e(route('payroll.bulk-generate')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="bulan" value="<?php echo e(now()->month); ?>">
            <input type="hidden" name="tahun" value="<?php echo e($selectedTahun); ?>">
            <button type="button" onclick="confirmBulkGenerate(event)" class="btn btn-block btn-slate-outline font-weight-bold" style="border-radius: 10px; padding: 10px; font-size: 13px;">
              <i class="fas fa-layer-group mr-1"></i> Hitung Seluruh Outlet Sekaligus (Draft)
            </button>
          </form>
        </div>
      </div>



    </div>

    
    <div class="col-lg-7">
      
      <div class="payroll-card">
        
        
        <div class="payroll-card-header">
          <div class="font-weight-bold" style="font-size: 16px; color: #0f172a;">
            <i class="fas fa-history mr-2 text-primary"></i>Riwayat Penggajian — <?php echo e($selectedTahun); ?>

          </div>

          
          <form method="GET" action="<?php echo e(route('payroll-systems.index')); ?>" class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <input type="hidden" name="tab" value="proses">
            
            <select name="tahun" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; width: 90px;" onchange="this.form.submit()">
              <?php $__currentLoopData = $availableYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($y); ?>" <?php echo e($selectedTahun == $y ? 'selected' : ''); ?>><?php echo e($y); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <select name="shop_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; min-width: 140px;" onchange="this.form.submit()">
              <?php $__currentLoopData = $shops; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($shop->id); ?>" <?php echo e($selectedShopId == $shop->id ? 'selected' : ''); ?>><?php echo e($shop->nama); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </form>
        </div>

        <div style="padding: 0;">
          <?php if($periods->isEmpty()): ?>
            <div class="text-center py-5 px-3">
              <div style="width: 56px; height: 56px; border-radius: 16px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; color: #94a3b8; font-size: 24px;">
                <i class="fas fa-receipt"></i>
              </div>
              <div style="font-weight: 700; color: #334155; font-size: 15px;">Belum Ada Riwayat Penggajian</div>
              <div class="text-muted small mt-1">
                Belum ada data periode penggajian untuk tahun <?php echo e($selectedTahun); ?>.<br>
                Gunakan formulir di sebelah kiri untuk menghitung gaji operator.
              </div>
            </div>
          <?php else: ?>
            <?php $__currentLoopData = $periods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap" style="background: #fafafa; gap: 10px;">
              <div>
                <strong style="font-size: 14.5px; color: #0f172a;"><?php echo e($period->periode_label); ?></strong>
                <span class="ml-2">
                  <?php if($period->isFinal()): ?>
                    <span class="badge badge-success px-2 py-1" style="border-radius: 6px;"><i class="fas fa-lock mr-1"></i>Final</span>
                  <?php else: ?>
                    <span class="badge badge-warning px-2 py-1" style="border-radius: 6px;"><i class="fas fa-edit mr-1"></i>Draft</span>
                  <?php endif; ?>
                </span>
                <span class="text-muted ml-2" style="font-size: 12.5px;">— <?php echo e($period->shop->nama); ?></span>
              </div>
              
              <div class="d-flex" style="gap: 6px;">
                <a href="<?php echo e(route('payroll.show', $period->id)); ?>" class="btn btn-sm btn-outline-secondary" style="font-size: 12px; border-radius: 6px;">
                  <i class="fas fa-eye mr-1"></i> Detail
                </a>
                <a href="<?php echo e(route('payroll.export-pdf', $period->id)); ?>" class="btn btn-sm btn-outline-danger" style="font-size: 12px; border-radius: 6px;">
                  <i class="fas fa-file-pdf mr-1"></i> Slip Gaji
                </a>
                <?php if($period->isDraft()): ?>
                <button class="btn btn-sm btn-outline-danger btn-delete-period" data-id="<?php echo e($period->id); ?>" style="font-size: 12px; border-radius: 6px;">
                  <i class="fas fa-trash-alt"></i>
                </button>
                <?php endif; ?>
              </div>
            </div>

            <div class="table-responsive mb-0">
              <table class="table table-sm table-hover mb-0">
                <thead style="background: #f8fafc;">
                  <tr>
                    <th style="font-size: 11px; text-transform: uppercase; color: #64748b; padding: 10px 16px;">Operator</th>
                    <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #64748b;">Gaji Pokok</th>
                    <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #64748b;">Komisi Liter</th>
                    <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #64748b;">Transport</th>
                    <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #64748b;">Potongan</th>
                    <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #059669; font-weight: 700; padding-right: 16px;">Total THP</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $__currentLoopData = $period->details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <tr>
                    <td style="font-size: 13px; font-weight: 600; padding: 10px 16px;"><?php echo e($detail->operator->user?->name ?? '-'); ?></td>
                    <td class="text-right" style="font-size: 13px;">
                      <?php if(floatval($detail->gaji_pokok) > 0): ?>
                        Rp <?php echo e(number_format($detail->gaji_pokok, 0, ',', '.')); ?>

                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right" style="font-size: 13px;">
                      <?php if(floatval($detail->gaji_variable) > 0): ?>
                        Rp <?php echo e(number_format($detail->gaji_variable, 0, ',', '.')); ?>

                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right" style="font-size: 13px;">
                      <?php if(floatval($detail->uang_transport) > 0): ?>
                        Rp <?php echo e(number_format($detail->uang_transport, 0, ',', '.')); ?>

                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right" style="font-size: 13px; color: #dc2626;">
                      <?php $totalPotongan = floatval($detail->potongan_tidak_masuk) + floatval($detail->kurang_setoran) + floatval($detail->tabungan_gaji) + floatval($detail->tabungan_setoran) + floatval($detail->potongan_hutang); ?>
                      <?php if($totalPotongan > 0): ?>
                        - Rp <?php echo e(number_format($totalPotongan, 0, ',', '.')); ?>

                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right font-weight-bold" style="font-size: 13.5px; color: #059669; padding-right: 16px;">
                      Rp <?php echo e(number_format($detail->hitungTHP(), 0, ',', '.')); ?>

                    </td>
                  </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  <?php if($period->details->isEmpty()): ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-3" style="font-size: 13px;">
                      Belum ada rincian operator. Klik "Detail" untuk cek.
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
                <tfoot style="background: #f8fafc;">
                  <tr>
                    <td colspan="5" class="text-right font-weight-bold" style="font-size: 12.5px; padding: 10px 16px;">Total Penggajian Outlet:</td>
                    <td class="text-right font-weight-bold" style="font-size: 14px; color: #059669; padding-right: 16px;">
                      Rp <?php echo e(number_format($period->details->sum(fn($d) => $d->hitungTHP()), 0, ',', '.')); ?>

                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          <?php endif; ?>
        </div>
      </div>

    </div>

  </div>
</div>





<div class="payroll-tab-pane" id="tab-kunci">

  
  <div class="payroll-card mb-4" style="padding: 14px 20px;">
    <form method="GET" action="<?php echo e(route('payroll-systems.index')); ?>" class="d-flex align-items-center" style="gap: 16px; flex-wrap: wrap;">
      <input type="hidden" name="tab" value="kunci">
      <div class="d-flex align-items-center" style="gap: 8px;">
        <label class="text-muted mb-0" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Pertashop:</label>
        <select name="shop_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; min-width: 160px;" onchange="this.form.submit()">
          <?php $__currentLoopData = $shops; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($shop->id); ?>" <?php echo e($selectedShopId == $shop->id ? 'selected' : ''); ?>><?php echo e($shop->nama); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
      <div class="d-flex align-items-center" style="gap: 8px;">
        <label class="text-muted mb-0" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Bulan:</label>
        <input type="month" name="year_month" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px;"
          value="<?php echo e($selectedMonth); ?>" onchange="this.form.submit()">
      </div>
    </form>
  </div>

  <div class="row">
    <div class="col-md-6 mb-4">
      
      <div class="payroll-card mb-4">
        <div class="payroll-card-header">
          <div class="font-weight-bold" style="font-size: 15px; color: #0f172a;">
            <i class="fas fa-shield-alt text-primary mr-1"></i>Status Laporan Bulan Ini
          </div>
        </div>
        <div style="padding: 20px;">
          <div class="lock-info-card">
            <div class="lock-info-row">
              <span class="lock-info-label">Jumlah laporan harian masuk</span>
              <span>
                <strong><?php echo e($jumlahLaporanMasuk); ?></strong> dari <?php echo e($jumlahHariBulan); ?> hari
                <?php if($jumlahLaporanMasuk >= $jumlahHariBulan): ?>
                  <span class="status-ok ml-1">(Lengkap)</span>
                <?php else: ?>
                  <span class="status-warn ml-1">(Belum lengkap)</span>
                <?php endif; ?>
              </span>
            </div>
            <div class="lock-info-row">
              <span class="lock-info-label">Semua laporan terverifikasi</span>
              <span>
                <?php if($semuaLaporanTerverifikasi): ?>
                  <span class="status-ok">Ya</span>
                <?php else: ?>
                  <span class="status-bad">Belum</span>
                <?php endif; ?>
              </span>
            </div>
            <div class="lock-info-row">
              <span class="lock-info-label">Status kunci periode</span>
              <span>
                <?php if($isLocked): ?>
                  <span class="status-ok"><i class="fas fa-lock mr-1"></i>Terkunci</span>
                <?php else: ?>
                  <span class="text-muted"><i class="fas fa-lock-open mr-1"></i>Terbuka</span>
                <?php endif; ?>
              </span>
            </div>
            <?php if($isLocked && $periodLockObj): ?>
              <div class="lock-info-row">
                <span class="lock-info-label">Dikunci oleh</span>
                <span class="text-muted"><?php echo e($periodLockObj->locker?->name ?? '-'); ?></span>
              </div>
              <div class="lock-info-row">
                <span class="lock-info-label">Waktu dikunci</span>
                <span class="text-muted"><?php echo e($periodLockObj->locked_at ? \Carbon\Carbon::parse($periodLockObj->locked_at)->format('d M Y, H:i') : '-'); ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div>
            <?php if(!$isLocked): ?>
              <p class="text-muted small mb-3">Setelah dikunci, seluruh data laporan dan penggajian bulan ini tidak bisa diubah lagi.</p>
              <form action="<?php echo e(route('reports.approval.lock-period')); ?>" method="POST" id="form-lock">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="shop_id" value="<?php echo e($selectedShopId); ?>">
                <input type="hidden" name="year_month" value="<?php echo e($selectedMonth); ?>">
                <button type="button" class="btn btn-dark btn-block font-weight-bold" style="border-radius: 10px; padding: 11px;" onclick="confirmLock()">
                  <i class="fas fa-lock mr-1"></i> Kunci Periode Ini
                </button>
              </form>
            <?php else: ?>
              <p class="text-muted small mb-3">Periode ini sudah terkunci. Untuk membuka kembali, diperlukan alasan tertulis.</p>
              <button type="button" class="btn btn-warning btn-block font-weight-bold text-dark" style="border-radius: 10px; padding: 11px;" data-toggle="modal" data-target="#reopenModal">
                <i class="fas fa-unlock-alt mr-1"></i> Buka Kunci Periode
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    
    <div class="col-md-6 mb-4">
      <div class="payroll-card h-100">
        <div class="payroll-card-header">
          <div class="font-weight-bold" style="font-size: 15px; color: #0f172a;">
            <i class="fas fa-history text-muted mr-1"></i>Riwayat Persetujuan
          </div>
        </div>
        <?php
          $approvalHistories = \App\Models\ReportApprovalHistory::with('actor')
            ->where('shop_id', $selectedShopId)
            ->where('year_month', $selectedMonth)
            ->orderBy('created_at', 'desc')
            ->get();
        ?>
        <div style="padding: 20px; max-height: 380px; overflow-y: auto;">
          <?php $__empty_1 = true; $__currentLoopData = $approvalHistories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
              <div style="font-size: 13px; font-weight: 700; color: #0f172a;">
                <?php echo e(strtoupper($h->from_status ?? '')); ?> → <?php echo e(strtoupper($h->to_status ?? '')); ?>

              </div>
              <div class="text-muted" style="font-size: 12px; margin-top: 2px;">
                Oleh: <?php echo e($h->actor->name ?? '-'); ?> — <?php echo e(\Carbon\Carbon::parse($h->created_at)->format('d M Y H:i')); ?>

              </div>
              <?php if($h->reason): ?>
                <div style="font-size: 12px; color: #475569; margin-top: 4px; font-style: italic; background: #f8fafc; padding: 6px 10px; border-radius: 6px;">
                  "<?php echo e($h->reason); ?>"
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-center text-muted py-5" style="font-size: 13px;">
              <div style="width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; color: #94a3b8; font-size: 20px;">
                <i class="fas fa-clipboard-check"></i>
              </div>
              Belum ada riwayat persetujuan untuk periode ini.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  
  <div class="modal fade" id="reopenModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content" style="border-radius: 18px; overflow: hidden; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
        <div class="modal-header px-4 py-3" style="background: #d97706; color: #ffffff;">
          <h5 class="modal-title font-weight-bold" style="font-size: 15px;">
            <i class="fas fa-unlock mr-1"></i> Buka Kunci Periode
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <form action="<?php echo e(route('reports.approval.reopen-period')); ?>" method="POST">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="shop_id" value="<?php echo e($selectedShopId); ?>">
          <input type="hidden" name="year_month" value="<?php echo e($selectedMonth); ?>">
          <div class="modal-body p-4" style="font-size: 13px;">
            <p class="mb-2 text-muted">Membuka kembali kunci periode memerlukan alasan tertulis:</p>
            <textarea name="reason" class="form-control" rows="3" required
              placeholder="Contoh: Koreksi pengeluaran tanggal 14..." style="border-radius: 10px; font-size: 13px;"></textarea>
          </div>
          <div class="modal-footer px-4 py-3" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
            <button type="button" class="btn btn-light btn-sm" style="border-radius: 8px;" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-warning text-dark font-weight-bold btn-sm" style="border-radius: 8px; padding: 6px 18px;">
              Buka Kunci
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>





<?php if($canViewBenchmark): ?>
<div class="payroll-tab-pane" id="tab-perbandingan">

  
  <div class="payroll-card mb-4" style="padding: 14px 20px;">
    <form method="GET" action="<?php echo e(route('payroll-systems.index')); ?>" class="d-flex align-items-center" style="gap: 12px;">
      <input type="hidden" name="tab" value="perbandingan">
      <label class="text-muted mb-0" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Bulan Periode:</label>
      <input type="month" name="benchmark_month" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; width: auto;"
        value="<?php echo e($benchmarkMonth); ?>" onchange="this.form.submit()">
    </form>
  </div>

  <div class="payroll-card">
    <div class="payroll-card-header">
      <div class="font-weight-bold" style="font-size: 16px; color: #0f172a;">
        <i class="fas fa-chart-line mr-2 text-primary"></i>Perbandingan Kinerja Antar Pertashop / Outlet
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: #f8fafc;">
            <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #475569; padding: 12px 16px;">Peringkat</th>
            <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Pertashop</th>
            <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569;">Penjualan (L)</th>
            <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569;">Rupiah Penjualan</th>
            <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569;">Biaya Operasional</th>
            <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569;">Laba Bersih</th>
            <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569; padding-right: 16px;">Losses / Gain (L)</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $benchmarks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td class="text-center font-weight-bold" style="font-size: 13px; padding: 12px 16px;">#<?php echo e($idx + 1); ?></td>
            <td style="font-size: 13px; font-weight: 700; color: #0f172a;"><?php echo e($b['nama']); ?></td>
            <td class="text-right font-weight-bold" style="font-size: 13px;">
              <?php echo e(number_format($b['total_volume'], 1, ',', '.')); ?> L
            </td>
            <td class="text-right" style="font-size: 13px;">
              Rp <?php echo e(number_format($b['total_rupiah'], 0, ',', '.')); ?>

            </td>
            <td class="text-right" style="font-size: 13px; color: #dc2626;">
              Rp <?php echo e(number_format($b['total_cost'], 0, ',', '.')); ?>

            </td>
            <td class="text-right font-weight-bold" style="font-size: 13.5px; color: #059669;">
              Rp <?php echo e(number_format($b['total_profit'], 0, ',', '.')); ?>

            </td>
            <td class="text-right font-weight-bold" style="font-size: 13px; color: <?php echo e($b['total_losses'] < 0 ? '#dc2626' : '#059669'); ?>; padding-right: 16px;">
              <?php echo e(number_format($b['total_losses'], 2, ',', '.')); ?> L
            </td>
          </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted" style="font-size: 13px;">
              <div style="width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; color: #94a3b8; font-size: 20px;">
                <i class="fas fa-chart-pie"></i>
              </div>
              Belum ada data laporan untuk bulan ini.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// ─── Tab Navigation ──────────────────────────────────────────────────────────
(function() {
  const btns  = document.querySelectorAll('.payroll-tab-btn');
  const panes = document.querySelectorAll('.payroll-tab-pane');

  // Restore tab dari URL atau localStorage
  const urlTab   = new URLSearchParams(window.location.search).get('tab');
  const savedTab = urlTab || localStorage.getItem('payrollActiveTab') || 'pengaturan';

  function activateTab(tabId) {
    btns.forEach(b  => b.classList.toggle('active',  b.dataset.tab === tabId));
    panes.forEach(p => p.classList.toggle('active', p.id === 'tab-' + tabId));
    localStorage.setItem('payrollActiveTab', tabId);
  }

  activateTab(savedTab);

  btns.forEach(btn => {
    btn.addEventListener('click', () => activateTab(btn.dataset.tab));
  });
})();

// ─── Cek status periode (Tab 2) ──────────────────────────────────────────────
function checkFinalStatus() {
  const sel    = document.getElementById('gen-bulan');
  if (!sel) return;
  const opt    = sel.options[sel.selectedIndex];
  const status = opt ? opt.dataset.status : '';
  const btnGen = document.getElementById('btn-submit-gen');
  const aFin   = document.getElementById('final-alert');
  const aDraf  = document.getElementById('draft-alert');

  if (aFin) aFin.classList.add('d-none');
  if (aDraf) aDraf.classList.add('d-none');
  
  if (btnGen) {
    btnGen.disabled = false;
    btnGen.style.opacity = '1';
  }

  if (status === 'final') {
    if (aFin) aFin.classList.remove('d-none');
    if (btnGen) {
      btnGen.disabled = true;
      btnGen.style.opacity = '0.6';
    }
  } else if (status === 'draft') {
    if (aDraf) aDraf.classList.remove('d-none');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  checkFinalStatus();

  // Hapus Draft Period (Event Delegation)
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete-period');
    if (!btn) return;
    e.preventDefault();

    const id = btn.dataset.id;
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '<?php echo e(csrf_token()); ?>';

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Hapus Draft Penggajian?',
        text: 'Data draft yang belum difinalisasi akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        cancelButtonText: 'Batal',
        confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus Draft'
      }).then(result => {
        if (result.isConfirmed) {
          executeDeletePayroll(id, token);
        }
      });
    } else {
      if (confirm('Hapus draft penggajian ini?')) {
        executeDeletePayroll(id, token);
      }
    }
  });
});

function executeDeletePayroll(id, token) {
  fetch(`/payroll/${id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  })
  .then(async r => {
    const data = await r.json().catch(() => ({}));
    if (!r.ok || data.error) {
      alert(data.error || data.message || 'Gagal menghapus draft.');
    } else {
      location.reload();
    }
  })
  .catch(err => {
    alert('Terjadi kesalahan sistem: ' + err.message);
  });
}

// ─── Dynamic Custom Items Row Handler ──────────────────────────────────────
let customRowIndex = 0;

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-add-custom-row');
  if (!btn) return;
  e.preventDefault();

  const container = document.getElementById('custom-items-container-sys') || document.getElementById('custom-items-container-index');
  if (!container) return;

  const idx = customRowIndex++;
  const rowHtml = `
    <div class="custom-item-row p-2 mb-2 border rounded" style="background:#ffffff; font-size:12px; border-color:#e2e8f0!important;" id="custom-row-${idx}">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <select name="custom_items[${idx}][tipe]" class="form-control form-control-sm py-0 px-2 font-weight-bold" style="width: auto; height: 26px; font-size: 11px;">
          <option value="tambahan">+ Pendapatan Tambahan</option>
          <option value="potongan">− Potongan Lain</option>
        </select>
        <button type="button" class="btn btn-xs text-danger btn-remove-custom-row p-0 ml-2" style="font-weight:bold; font-size:16px; line-height:1;" title="Hapus baris">&times;</button>
      </div>
      <div class="form-row">
        <div class="col-7 pr-1">
          <input type="text" name="custom_items[${idx}][nama_item]" class="form-control form-control-sm" placeholder="Keterangan (ex: Bonus Target)" style="font-size:11.5px; border-radius:6px;" required>
        </div>
        <div class="col-5 pl-1">
          <input type="number" name="custom_items[${idx}][jumlah]" class="form-control form-control-sm" placeholder="Nominal (Rp)" style="font-size:11.5px; border-radius:6px;" min="0" step="1000" required>
        </div>
      </div>
    </div>
  `;

  container.insertAdjacentHTML('beforeend', rowHtml);
});

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-remove-custom-row');
  if (!btn) return;
  e.preventDefault();
  const row = btn.closest('.custom-item-row');
  if (row) row.remove();
});

function confirmSingleGenerate(e) {
  e.preventDefault();
  const form = document.getElementById('form-generate');
  const sel = document.getElementById('gen-bulan');
  const opt = sel ? sel.options[sel.selectedIndex] : null;
  const status = opt ? opt.dataset.status : '';

  if (status === 'final') {
    if (typeof Swal !== 'undefined') {
      Swal.fire('Terkunci', 'Periode sudah final dan tidak dapat dihitung ulang.', 'error');
    } else {
      alert('Periode sudah final dan tidak dapat dihitung ulang.');
    }
    return;
  }

  if (status === 'draft') {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Hitung Ulang Draft?',
        text: 'Sudah ada data draft untuk bulan ini. Data draft lama akan diperbarui. Lanjutkan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hitung Ulang',
        cancelButtonText: 'Batal'
      }).then(result => {
        if (result.isConfirmed) form.submit();
      });
    } else {
      if (confirm('Sudah ada draft. Hitung ulang dan perbarui?')) form.submit();
    }
  } else {
    form.submit();
  }
}

function confirmBulkGenerate(e) {
  e.preventDefault();
  const form = document.getElementById('form-bulk-generate');
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Hitung Seluruh Outlet Sekaligus?',
      text: 'Proses kalkulasi gaji akan dijalankan untuk SEMUA Pertashop bulan ini. Outlet yang sudah Final akan otomatis dilewati.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#059669',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Ya, Hitung Semua',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm('Hitung gaji untuk SEMUA outlet bulan ini? Outlet Final akan dilewati.')) {
      form.submit();
    }
  }
}

function confirmLock() {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Kunci Periode Ini?',
      text: 'Setelah dikunci, data laporan dan penggajian bulan ini tidak bisa diubah kembali tanpa izin tertulis.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#0f172a',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-lock mr-1"></i> Ya, Kunci Periode',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById('form-lock').submit();
      }
    });
  } else {
    if (confirm('Kunci periode ini? Data tidak bisa diubah kembali.')) {
      document.getElementById('form-lock').submit();
    }
  }
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts._new_admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/payroll_systems/index.blade.php ENDPATH**/ ?>