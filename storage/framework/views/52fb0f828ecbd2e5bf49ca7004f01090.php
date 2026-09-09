<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Harian — <?php echo e($shop->nama ?? 'Pertashop'); ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #2b2b2b;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        .header {
            border-bottom: 2px solid #00529C;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-title h2 {
            margin: 0 0 3px 0;
            color: #00529C;
            font-size: 16px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .header-title p {
            margin: 0;
            font-size: 10px;
            color: #555;
        }
        .header-meta {
            text-align: right;
            font-size: 9px;
            color: #666;
        }
        .summary-boxes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .summary-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            text-align: center;
        }
        .summary-box .label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .summary-box .value {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            font-size: 8.5px;
        }
        .data-table th {
            background-color: #00529C;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }
        .data-table td.center { text-align: center; }
        .data-table td.left { text-align: left; }
        .data-table td.right { text-align: right; }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            color: #ffffff;
        }
        .badge-manual { background-color: #3b82f6; }
        .badge-backdate { background-color: #0284c7; }
        .badge-verified { background-color: #10b981; }
        .badge-draft { background-color: #6b7280; }
        .text-danger { color: #dc2626; font-weight: bold; }
        .text-success { color: #16a34a; font-weight: bold; }
        .total-row {
            background-color: #e2e8f0 !important;
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #64748b;
            font-weight: bold;
            background-color: #e2e8f0 !important;
        }
        .footer {
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            font-size: 8px;
            color: #64748b;
        }
        .footer table {
            width: 100%;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td class="header-title">
                    <h2>Laporan Operasional Harian</h2>
                    <p><strong><?php echo e($shop->nama ?? 'Pertashop'); ?></strong> (Kode: <?php echo e($shop->kode ?? '-'); ?>) — <?php echo e($shop->alamat ?? ''); ?></p>
                </td>
                <td class="header-meta">
                    <strong>Periode Laporan:</strong><br>
                    <?php echo e(\Carbon\Carbon::parse($startDate)->translatedFormat('d M Y')); ?> s/d <?php echo e(\Carbon\Carbon::parse($endDate)->translatedFormat('d M Y')); ?><br>
                    <span style="color:#94a3b8;">Total: <?php echo e(count($reports)); ?> Data Laporan</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary-boxes">
        <tr>
            <td class="summary-box" style="width: 20%;">
                <div class="label">Total Volume Terjual</div>
                <div class="value"><?php echo e(number_format($totals['volume_penjualan'], 3, ',', '.')); ?> &ell;</div>
            </td>
            <td class="summary-box" style="width: 20%;">
                <div class="label">Total Omset Penjualan</div>
                <div class="value">Rp <?php echo e(number_format($totals['rupiah_penjualan'], 0, ',', '.')); ?></div>
            </td>
            <td class="summary-box" style="width: 20%;">
                <div class="label">Total Pengeluaran</div>
                <div class="value">Rp <?php echo e(number_format($totals['pengeluaran'], 0, ',', '.')); ?></div>
            </td>
            <td class="summary-box" style="width: 20%;">
                <div class="label">Pendapatan Bersih</div>
                <div class="value">Rp <?php echo e(number_format($totals['pendapatan'], 0, ',', '.')); ?></div>
            </td>
            <td class="summary-box" style="width: 20%;">
                <div class="label">Total Disetorkan</div>
                <div class="value">Rp <?php echo e(number_format($totals['disetorkan'], 0, ',', '.')); ?></div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 24px;">No</th>
                <th style="width: 65px;">Tanggal</th>
                <th style="width: 55px;">Sumber</th>
                <th style="width: 75px;">Operator</th>
                <th style="width: 50px;">Penerimaan (&ell;)</th>
                <th style="width: 55px;">Vol Jual (&ell;)</th>
                <th style="width: 70px;">Rupiah Jual</th>
                <th style="width: 50px;">Stok Akhir (&ell;)</th>
                <th style="width: 50px;">Loss/Gain (&ell;)</th>
                <th style="width: 65px;">Pengeluaran</th>
                <th style="width: 65px;">Pendapatan</th>
                <th style="width: 65px;">Disetorkan</th>
                <th style="width: 65px;">Belum Setor</th>
                <th style="width: 45px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $isBackdate = ($r->sumber_data === 'import_excel_arsip');
                    $lossGain = (float) $r->losses_gain;
                    $balance = (float) $r->running_balance_setoran;
                ?>
                <tr>
                    <td class="center"><?php echo e($idx + 1); ?></td>
                    <td class="center"><?php echo e(\Carbon\Carbon::parse($r->created_at)->format('d/m/Y')); ?></td>
                    <td class="center">
                        <?php if($isBackdate): ?>
                            <span class="badge badge-backdate">Backdate</span>
                        <?php else: ?>
                            <span class="badge badge-manual">Manual</span>
                        <?php endif; ?>
                    </td>
                    <td class="left"><?php echo e($r->operator?->user?->name ?? ($isBackdate ? 'Arsip / Sistem' : '-')); ?></td>
                    <td class="right"><?php echo e(number_format($r->penerimaan, 2, ',', '.')); ?></td>
                    <td class="right"><strong><?php echo e(number_format($r->volume_penjualan, 3, ',', '.')); ?></strong></td>
                    <td class="right">Rp <?php echo e(number_format($r->rupiah_penjualan, 0, ',', '.')); ?></td>
                    <td class="right"><?php echo e(number_format($r->stok_akhir_aktual, 2, ',', '.')); ?></td>
                    <td class="right <?php echo e($lossGain < 0 ? 'text-danger' : ($lossGain > 0 ? 'text-success' : '')); ?>">
                        <?php echo e(($lossGain > 0 ? '+' : '') . number_format($lossGain, 3, ',', '.')); ?>

                    </td>
                    <td class="right">Rp <?php echo e(number_format($r->pengeluaran, 0, ',', '.')); ?></td>
                    <td class="right">Rp <?php echo e(number_format($r->pendapatan, 0, ',', '.')); ?></td>
                    <td class="right">Rp <?php echo e(number_format($r->disetorkan, 0, ',', '.')); ?></td>
                    <td class="right <?php echo e($balance > 100 ? 'text-danger' : ($balance < -100 ? 'text-success' : '')); ?>">
                        Rp <?php echo e(number_format(abs($balance), 0, ',', '.')); ?>

                    </td>
                    <td class="center">
                        <span class="badge <?php echo e($r->status_lifecycle === 'approved' || $r->status_lifecycle === 'locked' ? 'badge-verified' : 'badge-draft'); ?>">
                            <?php echo e(ucfirst($r->status_lifecycle ?? 'draft')); ?>

                        </span>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="14" class="center" style="padding: 15px; color: #64748b;">
                        Tidak ada data laporan harian pada rentang tanggal terpilih.
                    </td>
                </tr>
            <?php endif; ?>

            <?php if(count($reports) > 0): ?>
                <tr class="total-row">
                    <td colspan="4" class="center">TOTAL KESELURUHAN</td>
                    <td class="right"><?php echo e(number_format($totals['penerimaan'], 2, ',', '.')); ?></td>
                    <td class="right"><?php echo e(number_format($totals['volume_penjualan'], 3, ',', '.')); ?></td>
                    <td class="right">Rp <?php echo e(number_format($totals['rupiah_penjualan'], 0, ',', '.')); ?></td>
                    <td class="right">-</td>
                    <td class="right <?php echo e($totals['losses_gain'] < 0 ? 'text-danger' : ($totals['losses_gain'] > 0 ? 'text-success' : '')); ?>">
                        <?php echo e(($totals['losses_gain'] > 0 ? '+' : '') . number_format($totals['losses_gain'], 3, ',', '.')); ?>

                    </td>
                    <td class="right">Rp <?php echo e(number_format($totals['pengeluaran'], 0, ',', '.')); ?></td>
                    <td class="right">Rp <?php echo e(number_format($totals['pendapatan'], 0, ',', '.')); ?></td>
                    <td class="right">Rp <?php echo e(number_format($totals['disetorkan'], 0, ',', '.')); ?></td>
                    <td class="right">Rp <?php echo e(number_format(abs($totals['running_balance']), 0, ',', '.')); ?></td>
                    <td class="center">-</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td style="text-align: left;">
                    Dokumen ini digenerate otomatis oleh Sistem Pertashop App pada <?php echo e(\Carbon\Carbon::now()->translatedFormat('d F Y H:i')); ?>.
                </td>
                <td style="text-align: right;">
                    Halaman 1 dari 1
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
<?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/pdf/daily_reports_export.blade.php ENDPATH**/ ?>