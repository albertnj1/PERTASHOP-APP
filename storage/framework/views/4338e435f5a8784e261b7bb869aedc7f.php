<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Riwayat Laporan Bulanan</h1>
        <a href="<?php echo e(route('monthly-reports.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Upload Laporan Bulanan
        </a>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success">
            <?php echo e(session('success')); ?>

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
                            <th>Waktu Upload</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($index + 1); ?></td>
                                <td><?php echo e($report->shop->nama); ?></td>
                                <td><?php echo e(date('F Y', strtotime($report->bulan_tahun))); ?></td>
                                <td class="font-weight-bold text-success">
                                    Rp <?php echo e(number_format($report->grand_totals['disetorkan'] ?? 0, 0, ',', '.')); ?>

                                </td>
                                <td><?php echo e($report->created_at->format('d M Y H:i')); ?></td>
                                <td>
                                    <a href="<?php echo e(route('monthly-reports.show', $report->id)); ?>" class="btn btn-sm btn-info text-white">
                                        <i class="fas fa-eye"></i> Lihat Data
                                    </a>
                                    <form action="<?php echo e(route('monthly-reports.destroy', $report->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini secara permanen?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data laporan bulanan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/monthly_reports/index.blade.php ENDPATH**/ ?>