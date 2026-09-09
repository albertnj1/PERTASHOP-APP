<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Bulanan</h1>
        <?php if(Auth::user()->role !== 'investor'): ?>
        <div>
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#generateModal">
                <i class="fas fa-bolt mr-1"></i> GENERATE LAPORAN BULANAN
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e(session('success')); ?>

            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo e(session('error')); ?>

            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Pertashop</th>
                            <th>Periode</th>
                            <th>Total Setoran</th>
                            <th>Waktu Pembuatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($index + 1); ?></td>
                                <td><strong><?php echo e($report->shop->nama ?? '-'); ?></strong></td>
                                <td><?php echo e($report->bulan_tahun); ?></td>
                                <td class="font-weight-bold text-success">
                                    Rp <?php echo e(number_format($report->grand_totals['total_disetorkan'] ?? $report->grand_totals['disetorkan'] ?? 0, 0, ',', '.')); ?>

                                </td>
                                <td><?php echo e($report->created_at->format('d M Y H:i')); ?></td>
                                <td>
                                    <a href="<?php echo e(route('monthly-reports.show', $report->id)); ?>" class="btn btn-sm btn-info text-white" title="Lihat Detail">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                    <a href="<?php echo e(route('monthly-reports.export-pdf', $report->id)); ?>" class="btn btn-sm btn-danger text-white ml-1" title="Unduh PDF Siap Kirim">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                    <?php if(Auth::user()->role !== 'investor'): ?>
                                    <form action="<?php echo e(route('monthly-reports.destroy', $report->id)); ?>" method="POST" class="d-inline ml-1" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada data laporan bulanan. Gunakan tombol <strong>Generate Otomatis</strong> untuk membuat laporan dari Laporan Harian.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<?php if(Auth::user()->role !== 'investor'): ?>
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--page-bg); border-bottom: 1px solid var(--border);">
                <h5 class="modal-title font-weight-bold" style="font-size: 15px;">Generate Laporan Bulanan Otomatis</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="<?php echo e(route('monthly-reports.generate')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <p class="text-muted style-sm">Sistem akan mengumpulkan dan menghitung agregat dari seluruh <strong>Laporan Harian</strong> toko pada bulan yang dipilih.</p>
                    <div class="form-group">
                        <label class="font-weight-bold">Pertashop / Toko</label>
                        <select name="shop_id" class="form-control" required>
                            <?php
                                $shopsList = \App\Models\Shop::all();
                                if (Auth::user()->role === 'admin') {
                                    $shopsList = \App\Models\Shop::where('id', Auth::user()->admin->shop_id)->get();
                                }
                            ?>
                            <?php $__currentLoopData = $shopsList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($s->id); ?>"><?php echo e($s->nama); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Bulan & Tahun</label>
                        <input type="month" name="year_month" class="form-control" required value="<?php echo e(date('Y-m')); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success font-weight-bold">Generate Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/monthly_reports/index.blade.php ENDPATH**/ ?>