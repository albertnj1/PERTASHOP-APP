<?php $__env->startSection('title', 'Kasbon & Pinjaman Karyawan'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Kasbon &amp; Pinjaman Karyawan</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Pengajuan dan alur persetujuan hutang operator Pertashop</p>
  </div>
  <div>
    <button type="button" class="btn btn-primary" style="border-radius: 9px; font-weight: 600; font-size: 13px;" data-toggle="modal" data-target="#modalPengajuanKasbon">
      Ajukan Kasbon Baru
    </button>
  </div>
</div>

<?php if(session('success')): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 10px; border: none; background: #dcfce7; color: #15803d;">
    <?php echo e(session('success')); ?>

    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
<?php endif; ?>

<?php if($errors->any()): ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius: 10px; border: none; background: #fee2e2; color: #b91c1c;">
    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($e); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
<?php endif; ?>

<div class="panel">
  <div class="panel-head">
    <div class="panel-title">Daftar Pengajuan Kasbon</div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr style="background: var(--page-bg);">
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">#</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Operator</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Toko</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Tanggal</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Nominal</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Keterangan</th>
          <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Status</th>
          <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Disetujui Oleh</th>
          <?php if(collect(['super-admin','admin'])->contains(Auth::user()->role)): ?>
          <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Aksi</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $loans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td style="font-size: 13px;"><?php echo e($loop->iteration); ?></td>
          <td style="font-size: 13px; font-weight: 600;"><?php echo e($loan->operator?->user?->name ?? '-'); ?></td>
          <td style="font-size: 13px;"><span class="status-pill" style="background:#e2e8f0; color:#475569;"><?php echo e($loan->operator?->shop?->nama); ?></span></td>
          <td style="font-size: 13px;"><?php echo e($loan->tanggal?->format('d/m/Y')); ?></td>
          <td style="font-size: 13px; font-weight: 700; color: var(--red);">Rp <?php echo e(number_format($loan->jumlah, 0, ',', '.')); ?></td>
          <td style="font-size: 13px; color: var(--muted);"><?php echo e($loan->keterangan ?? '-'); ?></td>
          <td class="text-center">
            <?php if($loan->status === 'approved'): ?>
              <span class="status-pill" style="background:#dcfce7; color:#15803d;">Disetujui</span>
            <?php elseif($loan->status === 'rejected'): ?>
              <span class="status-pill" style="background:#fee2e2; color:#b91c1c;">Ditolak</span>
            <?php else: ?>
              <span class="status-pill" style="background:#fef3c7; color:#b45309;">Menunggu Approval</span>
            <?php endif; ?>
          </td>
          <td class="text-center text-muted" style="font-size: 12px;">
            <?php if($loan->approver): ?>
              <?php echo e($loan->approver->name); ?> <span style="font-size: 11px; opacity:0.8;">(<?php echo e($loan->approved_at?->format('d/m/Y H:i')); ?>)</span>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <?php if(collect(['super-admin','admin'])->contains(Auth::user()->role)): ?>
          <td class="text-center">
            <?php if($loan->status === 'pending'): ?>
              <form action="<?php echo e(route('employee-loans.approve', $loan->id)); ?>" method="POST" class="d-inline">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-sm btn-success" style="border-radius: 6px; font-size: 11px; padding: 3px 8px;" onclick="return confirm('Setujui pengajuan kasbon ini?')">Setujui</button>
              </form>
              <form action="<?php echo e(route('employee-loans.reject', $loan->id)); ?>" method="POST" class="d-inline ml-1">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 6px; font-size: 11px; padding: 3px 8px;" onclick="return confirm('Tolak pengajuan kasbon ini?')">Tolak</button>
              </form>
            <?php else: ?>
              <span class="text-muted" style="font-size: 11px;">Selesai</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="9" class="text-center text-muted py-4" style="font-size: 13px;">Belum ada riwayat pengajuan kasbon.</td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>


<div class="modal fade" id="modalPengajuanKasbon" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
      <div class="modal-header" style="background: var(--page-bg); border-bottom: 1px solid var(--border);">
        <h5 class="modal-title" style="font-weight: 700; font-size: 15px;">Form Pengajuan Kasbon</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form action="<?php echo e(route('employee-loans.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="modal-body" style="padding: 20px;">
          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Operator</label>
            <?php if(Auth::user()->role === 'operator'): ?>
              <input type="text" class="form-control" style="border-radius: 8px; background: var(--page-bg);" value="<?php echo e(Auth::user()->name); ?> (<?php echo e(Auth::user()->operator?->shop?->nama); ?>)" readonly>
              <input type="hidden" name="operator_id" value="<?php echo e(Auth::user()->operator?->id); ?>">
            <?php else: ?>
              <select name="operator_id" class="form-control" style="border-radius: 8px;" required>
                <?php $__currentLoopData = $operators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($op->id); ?>"><?php echo e($op->user?->name ?? 'Operator #'.$op->id); ?> (<?php echo e($op->shop?->nama); ?>)</option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            <?php endif; ?>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Tanggal Pengajuan</label>
            <input type="date" name="tanggal" class="form-control" style="border-radius: 8px;" value="<?php echo e(now()->format('Y-m-d')); ?>" required>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Nominal Kasbon (Rp)</label>
            <input type="number" name="jumlah" class="form-control" style="border-radius: 8px;" placeholder="Contoh: 500000" min="1000" step="50000" required>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Alasan / Keterangan</label>
            <textarea name="keterangan" class="form-control" style="border-radius: 8px;" rows="3" placeholder="Contoh: Pinjaman darurat pengobatan keluarga" required></textarea>
          </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid var(--border);">
          <button type="button" class="btn btn-light btn-sm" style="border-radius: 8px;" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 8px; font-weight: 600;">Kirim Pengajuan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts._new_admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/employee_loans/index.blade.php ENDPATH**/ ?>