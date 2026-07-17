<?php $__env->startSection('title', 'Detail Laporan Bulanan'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <?php if(isset($validations) && $validations->where('status', 'invalid')->count() > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4 d-print-none" role="alert" style="border-left: 4px solid #f6c23e !important;">
            <h5 class="alert-heading font-weight-bold text-danger"><i class="fas fa-exclamation-triangle mr-2"></i> Peringatan Audit Perhitungan</h5>
            <p class="mb-2 text-dark">Ditemukan ketidakcocokan nilai perhitungan pada laporan bulanan ini dibandingkan dengan rincian data harian/sumber:</p>
            <ul class="mb-0 text-danger font-weight-bold" style="font-size: 13px;">
                <?php $__currentLoopData = $validations->where('status', 'invalid'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li>
                        <?php echo e(ucwords(str_replace('_', ' ', $val->component))); ?>: 
                        Nilai Sistem: <strong>Rp <?php echo e(number_format($val->system_value, 0, ',', '.')); ?></strong> | 
                        Hasil Hitung Ulang: <strong>Rp <?php echo e(number_format($val->recalculated_value, 0, ',', '.')); ?></strong> | 
                        Selisih: <strong style="text-decoration: underline;">Rp <?php echo e(number_format($val->diff, 0, ',', '.')); ?></strong>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
        <h4 class="mb-0 text-gray-800"><i class="fas fa-file-invoice-dollar text-primary me-2"></i> Laporan Bulanan Pertashop</h4>
        <div>
            <a href="<?php echo e(route('monthly-reports.index')); ?>" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Cetak / Cetak PDF
            </button>
        </div>
    </div>

    <?php
        $dataParsed = $report->data_parsed;
        $dailyData = $dataParsed['daily_data'] ?? [];
        $segments = $dataParsed['segments'] ?? [];
        $operatorSalaries = $dataParsed['operator_salaries'] ?? [];
        $investors = $dataParsed['investors'] ?? [];
        $pengeluaranExtra = $dataParsed['pengeluaran_extra'] ?? [];
        $grandLabaKotor = $dataParsed['grand_laba_kotor'] ?? 0;
        $totalBiaya = $dataParsed['total_biaya'] ?? 0;
        $totalGajiOperator = collect($operatorSalaries)->sum('gaji');
        $labaBersih = $dataParsed['laba_bersih'] ?? 0;
        $penambahanModal10 = $dataParsed['penambahan_modal_10'] ?? 0;
        $labaDibagi90 = $dataParsed['laba_dibagi_90'] ?? 0;
        $totalLabaDibagi = $dataParsed['total_laba_dibagi'] ?? 0;
        $saldoLabaSebelumnya = $dataParsed['saldo_laba_sebelumnya'] ?? 0;
        $sisaDoVolume = $dataParsed['sisa_do_volume'] ?? 0;
        $sisaStokRp = $dataParsed['sisa_stok_rp'] ?? 0;
        $belumDisetorkanRp = $dataParsed['belum_disetorkan_rp'] ?? 0;
        $rataRataPenjualan = $dataParsed['rata_rata_penjualan'] ?? 0;
        
        $monthName = \Carbon\Carbon::parse($report->bulan_tahun)->isoFormat('MMMM YYYY');

        if (!function_exists('fAesthetic')) {
            function fAesthetic($val, $dec = 0) {
                $num = floatval($val);
                if (abs($num) < 0.0001) {
                    return '<span class="text-muted opacity-25">-</span>';
                }
                return number_format($num, $dec, ',', '.');
            }
        }

        // Pre-calculate BKH Totals for summary cards
        $t_vol_jual_teoritis = 0; $t_rupiah_jual_teoritis = 0;
        $t_tp_vol = 0; $t_tp_rupiah = 0;
        $t_terima_bbm = 0; $t_losses_vol = 0; $t_losses_rupiah = 0;
        $t_penjualan_aktual = 0;
        $t_bongkar = 0; $t_tf = 0; $t_atk = 0; $t_listrik = 0; $t_air = 0; $t_cashback = 0; $t_internet = 0; $t_lain = 0; $t_biaya = 0;
        $t_mandiri = 0; $t_qris = 0; $t_tf_oper = 0; $t_selisih = 0; $t_belum_setor = 0;

        foreach($dailyData as $row) {
            $t_vol_jual_teoritis += floatval($row['volume_jual_teoritis'] ?? 0);
            $t_rupiah_jual_teoritis += floatval($row['rupiah_jual_teoritis'] ?? 0);
            $t_tp_vol += floatval($row['tp_volume'] ?? 0);
            $t_tp_rupiah += floatval($row['tp_rupiah'] ?? 0);
            $t_terima_bbm += floatval($row['terima_bbm'] ?? 0);
            $t_losses_vol += floatval($row['losses_volume'] ?? 0);
            $t_losses_rupiah += floatval($row['losses_rupiah'] ?? 0);
            $t_penjualan_aktual += floatval($row['volume_jual_aktual'] ?? 0);
            
            $b = $row['biaya'] ?? [];
            $t_bongkar += floatval($b['bongkar'] ?? 0);
            $t_tf += floatval($b['tf'] ?? 0);
            $t_atk += floatval($b['atk'] ?? 0);
            $t_listrik += floatval($b['listrik'] ?? 0);
            $t_air += floatval($b['air'] ?? 0);
            $t_cashback += floatval($b['cashback'] ?? 0);
            $t_internet += floatval($b['internet'] ?? 0);
            $t_lain += floatval($b['lain_lain_rp'] ?? 0);
            $t_biaya += floatval($b['total'] ?? 0);
            
            $s = $row['setoran'] ?? [];
            $t_mandiri += floatval($s['mandiri'] ?? 0);
            $t_qris += floatval($s['piutang'] ?? 0);
            $t_tf_oper += floatval($s['tf_cust'] ?? 0);
            $t_selisih += floatval($s['selisih'] ?? 0);
        }
        if (count($dailyData) > 0) {
            $lastRow = $dailyData[count($dailyData) - 1];
            $t_belum_setor = floatval($lastRow['setoran']['belum_setor'] ?? 0);
        }
    ?>

    <!-- Nav Tabs (4 Pages structure matching Excel) -->
    <ul class="nav nav-tabs custom-tabs mb-0 d-print-none" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="page1-tab" data-toggle="tab" href="#page1" role="tab"><i class="fas fa-table me-2"></i> Page 1: Buku Kendali Harian (BKH)</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="page2-tab" data-toggle="tab" href="#page2" role="tab"><i class="fas fa-boxes me-2"></i> Page 2: Kendali Laba Kotor (KLB)</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="page3-tab" data-toggle="tab" href="#page3" role="tab"><i class="fas fa-hand-holding-usd me-2"></i> Page 3: Laba Bersih & Profit Sharing (KLT)</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="page4-tab" data-toggle="tab" href="#page4" role="tab"><i class="fas fa-chart-line me-2"></i> Page 4: Posisi Modal Kerja & Investor</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="page5-tab" data-toggle="tab" href="#page5" role="tab"><i class="fas fa-money-check-alt me-2"></i> Page 5: Rekap Modal</a>
        </li>
    </ul>

    <div class="tab-content custom-tab-content p-4" id="reportTabsContent">
        
        <!-- PAGE 1: BUKU KENDALI HARIAN (BKH) -->
        <div class="tab-pane fade show active" id="page1" role="tabpanel" tabindex="0">
            <div class="text-center mb-4">
                <h4 class="font-weight-extrabold text-gray-800">BUKU KENDALI HARIAN (HARIAN OPERATOR)</h4>
                <h5 class="text-uppercase text-primary font-weight-bold"><?php echo e($report->shop->nama); ?></h5>
                <h6 class="text-muted">Periode: <?php echo e($monthName); ?></h6>
            </div>

            <!-- Simplified Summary Cards for Investors -->
            <div class="row">
                <!-- Penjualan BBM -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #4e73df !important;">
                        <div class="card-body py-4">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Penjualan BBM Aktual
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo e(number_format($t_penjualan_aktual, 2, ',', '.')); ?> L</div>
                                    <div class="mt-2 text-xs text-muted">
                                        Total Revenue: <strong class="text-dark">Rp <?php echo e(number_format($t_rupiah_jual_teoritis - $t_tp_rupiah, 0, ',', '.')); ?></strong>
                                    </div>
                                    <div class="mt-1 text-xs text-muted">
                                        Test Pump: <?php echo e(number_format($t_tp_vol, 2, ',', '.')); ?> L (Rp <?php echo e(number_format($t_tp_rupiah, 0, ',', '.')); ?>)
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-gas-pump fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Penerimaan & Losses -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #1cc88a !important;">
                        <div class="card-body py-4">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Penerimaan & Susut (Losses)
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo e(number_format($t_terima_bbm, 2, ',', '.')); ?> L</div>
                                    <?php
                                        $lossColorClass = $t_losses_vol < 0 ? 'text-danger' : 'text-success';
                                    ?>
                                    <div class="mt-2 text-xs text-muted">
                                        Losses/Gain: <strong class="<?php echo e($lossColorClass); ?>"><?php echo e(number_format($t_losses_vol, 2, ',', '.')); ?> L</strong>
                                    </div>
                                    <div class="mt-1 text-xs text-muted">
                                        Nilai Susut: <strong class="<?php echo e($lossColorClass); ?>">Rp <?php echo e(number_format($t_losses_rupiah, 0, ',', '.')); ?></strong>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-boxes fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biaya Operasional Harian -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #e74a3b !important;">
                        <div class="card-body py-4">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Biaya Operasional Harian
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800">Rp <?php echo e(number_format($t_biaya, 0, ',', '.')); ?></div>
                                    <div class="mt-2 text-xs text-muted" style="line-height: 1.4;">
                                        Bongkar: Rp <?php echo e(number_format($t_bongkar, 0, ',', '.')); ?> | Listrik: Rp <?php echo e(number_format($t_listrik, 0, ',', '.')); ?><br>
                                        Air: Rp <?php echo e(number_format($t_air, 0, ',', '.')); ?> | Internet: Rp <?php echo e(number_format($t_internet, 0, ',', '.')); ?>

                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Setoran & Keuangan -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f6c23e !important;">
                        <div class="card-body py-4">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Setoran & Kas
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800">Rp <?php echo e(number_format($t_mandiri + $t_qris + $t_tf_oper, 0, ',', '.')); ?></div>
                                    <div class="mt-2 text-xs text-muted">
                                        Belum Disetor: <strong class="text-warning">Rp <?php echo e(number_format($t_belum_setor, 0, ',', '.')); ?></strong>
                                    </div>
                                    <div class="mt-1 text-xs text-muted">
                                        Mandiri: Rp <?php echo e(number_format($t_mandiri, 0, ',', '.')); ?> | QRIS: Rp <?php echo e(number_format($t_qris, 0, ',', '.')); ?>

                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-wallet fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Collapsible full daily table details -->
            <div class="text-center mt-3 mb-4 d-print-none">
                <button class="btn btn-outline-primary px-4 py-2 font-weight-bold shadow-sm" type="button" data-toggle="collapse" data-target="#collapseBkhTable" aria-expanded="false" aria-controls="collapseBkhTable">
                    <i class="fas fa-table mr-2"></i> Tampilkan Rincian Harian Lengkap (Buku Kendali Harian)
                </button>
            </div>

            <div class="collapse d-print-block" id="collapseBkhTable">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive custom-table-wrapper">
                            <table class="table custom-aesthetic-table table-sm text-center align-middle mb-0">
                                <thead>
                                    <tr class="header-main">
                                        <th rowspan="2" class="align-middle sticky-col col-tanggal">Tgl</th>
                                        <th rowspan="2" class="align-middle group-tot">Tot. Awal</th>
                                        <th rowspan="2" class="align-middle group-tot">Tot. Akhir</th>
                                        <th colspan="2" class="group-teoritis">Teoritis Penjualan</th>
                                        <th colspan="2" class="group-tp">Test Pump</th>
                                        <th rowspan="2" class="align-middle group-stok">Stok<br>Awal</th>
                                        <th rowspan="2" class="align-middle group-stok">Terima<br>BBM</th>
                                        <th colspan="3" class="group-losses">Losses / Gain</th>
                                        <th rowspan="2" class="align-middle group-stokakhir">Stok<br>Akhir</th>
                                        <th rowspan="2" class="align-middle group-stokakhir">Jual<br>Aktual</th>
                                        <th colspan="9" class="group-biaya">Biaya Pengeluaran (Operasional)</th>
                                        <th colspan="5" class="group-setoran">Setoran & Selisih</th>
                                        <th rowspan="2" class="align-middle col-tanggal">Operator</th>
                                    </tr>
                                    <tr class="header-sub">
                                        <th class="group-teoritis">Vol (L)</th>
                                        <th class="group-teoritis">Rupiah</th>
                                        <th class="group-tp">Vol (L)</th>
                                        <th class="group-tp">Rupiah</th>
                                        <th class="group-losses">Vol (L)</th>
                                        <th class="group-losses">Rupiah</th>
                                        <th class="group-losses">Ket</th>
                                        <th class="group-biaya">Bongkar</th>
                                        <th class="group-biaya">TF</th>
                                        <th class="group-biaya">ATK</th>
                                        <th class="group-biaya">Listrik</th>
                                        <th class="group-biaya">Air</th>
                                        <th class="group-biaya">Cashback</th>
                                        <th class="group-biaya">Internet</th>
                                        <th class="group-biaya">Lain-lain</th>
                                        <th class="group-biaya font-weight-bold">Total Biaya</th>
                                        <th class="group-setoran">Mandiri</th>
                                        <th class="group-setoran">QRIS</th>
                                        <th class="group-setoran">TF Oper</th>
                                        <th class="group-setoran">Selisih</th>
                                        <th class="group-setoran text-warning">Belum Setor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $dailyData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $b = $row['biaya'] ?? [];
                                        $s = $row['setoran'] ?? [];
                                    ?>
                                    <tr>
                                        <td class="sticky-col fw-bold bg-white text-dark shadow-sm-right"><?php echo e($row['hari_tgl']); ?></td>
                                        <td class="col-tot"><?php echo fAesthetic($row['tot_awal'], 2); ?></td>
                                        <td class="col-tot"><?php echo fAesthetic($row['tot_akhir'], 2); ?></td>
                                        <td class="col-teoritis fw-bold text-primary"><?php echo fAesthetic($row['volume_jual_teoritis'], 2); ?></td>
                                        <td class="col-teoritis">Rp <?php echo fAesthetic($row['rupiah_jual_teoritis'], 0); ?></td>
                                        <td class="col-tp"><?php echo fAesthetic($row['tp_volume'], 2); ?></td>
                                        <td class="col-tp">Rp <?php echo fAesthetic($row['tp_rupiah'], 0); ?></td>
                                        <td class="col-stok"><?php echo fAesthetic($row['stok_awal'], 2); ?></td>
                                        <td class="col-stok fw-bold text-success"><?php echo fAesthetic($row['terima_bbm'], 2); ?></td>
                                        
                                        <?php
                                            $lossColor = $row['losses_volume'] < 0 ? 'text-danger' : 'text-success';
                                            if($row['losses_volume'] == 0) $lossColor = '';
                                        ?>
                                        <td class="col-losses <?php echo e($lossColor); ?>"><?php echo fAesthetic($row['losses_volume'], 2); ?></td>
                                        <td class="col-losses <?php echo e($lossColor); ?>">Rp <?php echo fAesthetic($row['losses_rupiah'], 0); ?></td>
                                        <td class="col-losses <?php echo e($lossColor); ?>"><small><?php echo e($row['losses_ket']); ?></small></td>
                                        
                                        <td class="col-stokakhir"><?php echo fAesthetic($row['stok_akhir'], 2); ?></td>
                                        <td class="col-stokakhir fw-bold text-primary"><?php echo fAesthetic($row['volume_jual_aktual'], 2); ?></td>
                                        
                                        <td class="col-biaya">Rp <?php echo fAesthetic($b['bongkar'] ?? 0, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($b['tf'] ?? 0, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($b['atk'] ?? 0, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($b['listrik'] ?? 0, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($b['air'] ?? 0, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($b['cashback'] ?? 0, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($b['internet'] ?? 0, 0); ?></td>
                                        <td class="col-biaya" title="<?php echo e($b['lain_lain_ket'] ?? ''); ?>">
                                            Rp <?php echo fAesthetic($b['lain_lain_rp'] ?? 0, 0); ?>

                                        </td>
                                        <td class="col-biaya fw-bold text-danger">Rp <?php echo fAesthetic($b['total'] ?? 0, 0); ?></td>
                                        
                                        <td class="col-setoran">Rp <?php echo fAesthetic($s['mandiri'] ?? 0, 0); ?></td>
                                        <td class="col-setoran">Rp <?php echo fAesthetic($s['piutang'] ?? 0, 0); ?></td>
                                        <td class="col-setoran">Rp <?php echo fAesthetic($s['tf_cust'] ?? 0, 0); ?></td>
                                        <td class="col-setoran">Rp <?php echo fAesthetic($s['selisih'] ?? 0, 0); ?></td>
                                        <td class="col-setoran fw-bold text-warning">Rp <?php echo fAesthetic($s['belum_setor'] ?? 0, 0); ?></td>
                                        <td class="text-xs text-muted"><?php echo e($row['operator_nama']); ?></td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                                <tfoot class="table-tfoot-total">
                                    <tr>
                                        <td class="sticky-col shadow-sm-right">TOTAL</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td class="col-teoritis"><?php echo fAesthetic($t_vol_jual_teoritis, 2); ?> L</td>
                                        <td class="col-teoritis">Rp <?php echo fAesthetic($t_rupiah_jual_teoritis, 0); ?></td>
                                        <td class="col-tp"><?php echo fAesthetic($t_tp_vol, 2); ?> L</td>
                                        <td class="col-tp">Rp <?php echo fAesthetic($t_tp_rupiah, 0); ?></td>
                                        <td>-</td>
                                        <td class="col-stok text-warning"><?php echo fAesthetic($t_terima_bbm, 2); ?> L</td>
                                        <td class="col-losses"><?php echo fAesthetic($t_losses_vol, 2); ?> L</td>
                                        <td class="col-losses">Rp <?php echo fAesthetic($t_losses_rupiah, 0); ?></td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td class="col-stokakhir text-warning"><?php echo fAesthetic($t_penjualan_aktual, 2); ?> L</td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($t_bongkar, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($t_tf, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($t_atk, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($t_listrik, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($t_air, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($t_cashback, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($t_internet, 0); ?></td>
                                        <td class="col-biaya">Rp <?php echo fAesthetic($t_lain, 0); ?></td>
                                        <td class="col-biaya text-warning">Rp <?php echo fAesthetic($t_biaya, 0); ?></td>
                                        <td class="col-setoran">Rp <?php echo fAesthetic($t_mandiri, 0); ?></td>
                                        <td class="col-setoran">Rp <?php echo fAesthetic($t_qris, 0); ?></td>
                                        <td class="col-setoran">Rp <?php echo fAesthetic($t_tf_oper, 0); ?></td>
                                        <td class="col-setoran">Rp <?php echo fAesthetic($t_selisih, 0); ?></td>
                                        <td class="col-setoran text-warning">Rp <?php echo fAesthetic($t_belum_setor, 0); ?></td>
                                        <td>-</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PAGE 2: KENDALI LABA KOTOR (KLB) -->
        <div class="tab-pane fade" id="page2" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="card-title text-primary font-weight-bold mb-4 border-bottom pb-3">Kendali Laba Kotor per Segmen Harga</h4>
                    
                    <?php $__currentLoopData = $segments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $segment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="mb-4">
                        <h5 class="font-weight-bold text-gray-800"><i class="fas fa-tag text-info me-2"></i> Segmen <?php echo e($segment['segmen_index']); ?> (<?php echo e($segment['start_date']); ?> s.d <?php echo e($segment['end_date']); ?>)</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <tr class="bg-light">
                                        <th colspan="2" class="text-center">Pembelian (HPP)</th>
                                    </tr>
                                    <tr>
                                        <td>Stok Awal Segment</td>
                                        <td class="text-end fw-bold"><?php echo e(number_format($segment['stok_awal'], 2, ',', '.')); ?> L</td>
                                    </tr>
                                    <tr>
                                        <td>BBM Datang (Penerimaan)</td>
                                        <td class="text-end fw-bold"><?php echo e(number_format($segment['bbm_datang'], 2, ',', '.')); ?> L</td>
                                    </tr>
                                    <tr class="table-info">
                                        <td><strong>Jumlah Pembelian</strong></td>
                                        <td class="text-end fw-bold"><strong><?php echo e(number_format($segment['jumlah_pembelian'], 2, ',', '.')); ?> L</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Harga Pokok (HPP) per Liter</td>
                                        <td class="text-end text-danger fw-bold">Rp <?php echo e(number_format($segment['harga_beli'], 2, ',', '.')); ?></td>
                                    </tr>
                                    <tr class="bg-light font-weight-bold">
                                        <td><strong>Total Pembelian (Rp)</strong></td>
                                        <td class="text-end text-danger font-weight-bold"><strong>Rp <?php echo e(number_format($segment['jumlah_pembelian_rp'], 0, ',', '.')); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <tr class="bg-light">
                                        <th colspan="2" class="text-center">Penjualan & Laba Kotor</th>
                                    </tr>
                                    <tr>
                                        <td>Total Volume Penjualan (Teoritis)</td>
                                        <td class="text-end fw-bold"><?php echo e(number_format($segment['total_penjualan'], 2, ',', '.')); ?> L</td>
                                    </tr>
                                    <tr>
                                        <td>Test Pump Volume</td>
                                        <td class="text-end fw-bold"><?php echo e(number_format($segment['test_pump'], 2, ',', '.')); ?> L</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>Volume Terjual Aktual</strong></td>
                                        <td class="text-end fw-bold"><strong><?php echo e(number_format($segment['jumlah_penjualan'], 2, ',', '.')); ?> L</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Harga Jual per Liter</td>
                                        <td class="text-end text-success fw-bold">Rp <?php echo e(number_format($segment['harga_jual'], 2, ',', '.')); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Penjualan Aktual (Rupiah)</td>
                                        <td class="text-end fw-bold">Rp <?php echo e(number_format($segment['jumlah_penjualan_rp'], 0, ',', '.')); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Stok Akhir Fisik (Aktual)</td>
                                        <td class="text-end fw-bold"><?php echo e(number_format($segment['totalisator_akhir'] * 0 === 0 ? ($segment['jumlah_pembelian'] - $segment['jumlah_penjualan'] + $segment['losses_gain']) : 0, 2, ',', '.')); ?> L</td>
                                    </tr>
                                    <tr>
                                        <td>Losses / Gain Volume</td>
                                        <td class="text-end fw-bold <?php echo e($segment['losses_gain'] < 0 ? 'text-danger' : 'text-success'); ?>"><?php echo e(number_format($segment['losses_gain'], 2, ',', '.')); ?> L (<?php echo e(number_format($segment['losses_gain_persen'], 2)); ?>%)</td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td><strong>Laba Kotor Segmen</strong></td>
                                        <td class="text-end text-primary font-weight-bold"><strong>Rp <?php echo e(number_format($segment['laba_kotor'], 0, ',', '.')); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <div class="card bg-success text-white mt-4 border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 font-weight-bold">GRAND TOTAL LABA KOTOR BULANAN:</h5>
                            <h4 class="mb-0 font-weight-bold">Rp <?php echo e(number_format($grandLabaKotor, 0, ',', '.')); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PAGE 3: LABA BERSIH & PROFIT SHARING (KLT) -->
        <div class="tab-pane fade" id="page3" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="card-title text-primary font-weight-bold mb-4 border-bottom pb-3">Laporan Perhitungan Laba Bersih & Alokasi Payout</h4>
                    
                    <div class="row">
                        <!-- Left Side: Laba Bersih Statement -->
                        <div class="col-md-6">
                            <h5 class="font-weight-bold text-gray-800 mb-3"><i class="fas fa-file-invoice text-success me-2"></i> Perhitungan Laba Bersih</h5>
                            <table class="table table-bordered table-hover">
                                <tbody>
                                    <tr>
                                        <td>Grand Total Laba Kotor</td>
                                        <td class="text-end text-primary fw-bold">Rp <?php echo number_format($grandLabaKotor, 0, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-3 text-muted">Dikurangi Biaya-biaya:</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">- Total Pengeluaran Harian (BKH)</td>
                                        <td class="text-end text-danger">- Rp <?php echo number_format($t_biaya, 0, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">- Gaji Operator Shift (D * Rp 200)</td>
                                        <td class="text-end text-danger">- Rp <?php echo number_format($totalGajiOperator, 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php $__currentLoopData = $pengeluaranExtra; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $extra): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="ps-4">- <?php echo e($extra['keterangan']); ?></td>
                                        <td class="text-end text-danger">- Rp <?php echo number_format($extra['nominal'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="table-warning font-weight-bold">
                                        <td><strong>Laba Bersih Operasional</strong></td>
                                        <td class="text-end text-success font-weight-bold"><strong>Rp <?php echo number_format($labaBersih, 0, ',', '.'); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Alokasi Penambahan Modal (10%)</td>
                                        <td class="text-end text-warning">- Rp <?php echo number_format($penambahanModal10, 0, ',', '.'); ?></td>
                                    </tr>
                                    <tr class="table-success font-weight-bold">
                                        <td><strong>Saldo Laba Bersih yang Dibagi (90%)</strong></td>
                                        <td class="text-end text-success font-weight-bold"><strong>Rp <?php echo number_format($labaDibagi90, 0, ',', '.'); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Saldo Laba Bersih Sebelumnya (Belum Dibagi)</td>
                                        <td class="text-end text-primary">+ Rp <?php echo number_format($saldoLabaSebelumnya, 0, ',', '.'); ?></td>
                                    </tr>
                                    <tr class="bg-primary text-white font-weight-bold">
                                        <td><strong>TOTAL SALDO LABA YANG DIBAGI</strong></td>
                                        <td class="text-end font-weight-bold"><strong>Rp <?php echo number_format($totalLabaDibagi, 0, ',', '.'); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Right Side: Investor Payout Distribution -->
                        <div class="col-md-6">
                            <h5 class="font-weight-bold text-gray-800 mb-3"><i class="fas fa-users-cog text-success me-2"></i> Payout Pembagian Profit Investor</h5>
                            <table class="table table-bordered text-center align-middle">
                                <thead class="bg-dark text-white">
                                    <tr>
                                        <th>Nama Investor</th>
                                        <th>Porsi Saham (%)</th>
                                        <th>Nominal Pembagian (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $investors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo e($inv['nama']); ?></td>
                                        <td class="fw-bold text-primary"><?php echo e(number_format($inv['persen'], 2)); ?>%</td>
                                        <td class="fw-bold text-success">Rp <?php echo e(number_format($inv['nominal'] ?? ($totalLabaDibagi * ($inv['persen'] / 100)), 0, ',', '.')); ?></td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="3" class="text-muted text-center">Tidak ada data investor</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Gaji Operator Breakdown (Shift Details) -->
                    <div class="row mt-5">
                        <div class="col-md-12">
                            <h5 class="mb-3 font-weight-bold text-gray-800"><i class="fas fa-user-clock text-info me-2"></i> Rincian Hari Kerja & Gaji Operator</h5>
                            <table class="table table-bordered table-striped text-center align-middle table-sm">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Nama Operator</th>
                                        <th>Hari Jaga</th>
                                        <th>Volume Terjual (B)</th>
                                        <th>Losses / Gain (C)</th>
                                        <th>Total Liter Diberlakukan (D)</th>
                                        <th>Gaji Diterima (Shift)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $operatorSalaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?php echo e($op['operator_nama']); ?></td>
                                        <td><?php echo e($op['hari_jaga']); ?> Hari</td>
                                        <td><?php echo e(number_format($op['total_penjualan_b'], 2, ',', '.')); ?> L</td>
                                        <td class="<?php echo e($op['losses_c'] < 0 ? 'text-danger' : 'text-success'); ?>">
                                            <?php echo e(number_format($op['losses_c'], 2, ',', '.')); ?> L
                                        </td>
                                        <td class="fw-bold bg-light"><?php echo e(number_format($op['penjualan_losses_d'], 2, ',', '.')); ?> L</td>
                                        <td class="fw-bold text-success">Rp <?php echo e(number_format($op['gaji'], 0, ',', '.')); ?></td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="text-muted">Tidak ada data operator</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- PAGE 4: POSISI MODAL KERJA & INVESTOR -->
        <div class="tab-pane fade" id="page4" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="card-title text-primary font-weight-bold mb-4 border-bottom pb-3">Posisi Modal Kerja & Rata-rata Penjualan</h4>
                    
                    <div class="row">
                        <!-- Working Capital Position -->
                        <div class="col-md-7">
                            <h5 class="font-weight-bold text-gray-800 mb-3"><i class="fas fa-balance-scale text-success me-2"></i> Posisi Modal Kerja</h5>
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="2">A. SUB TOTAL SALDO AWAL MODAL</td>
                                        <td class="text-end"><strong>Rp <?php echo e(number_format($report->saldo_awal_modal, 0, ',', '.')); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">1. DO di Pertamina</td>
                                        <td class="text-end">Rp <?php echo e(number_format($report->do_di_pertamina, 0, ',', '.')); ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">2. Uang di Bank</td>
                                        <td class="text-end">Rp <?php echo e(number_format($report->uang_di_bank, 0, ',', '.')); ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">3. Kas Kecil</td>
                                        <td class="text-end">Rp <?php echo e(number_format($report->kas_kecil, 0, ',', '.')); ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">4. Piutang</td>
                                        <td class="text-end">Rp <?php echo e(number_format($report->piutang, 0, ',', '.')); ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">5. Sisa Stok di Pertashop</td>
                                        <td class="text-end">Rp <?php echo e(number_format($sisaStokRp, 0, ',', '.')); ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">6. Hasil Penjualan Belum Disetor</td>
                                        <td class="text-end">Rp <?php echo e(number_format($belumDisetorkanRp, 0, ',', '.')); ?></td>
                                        <td></td>
                                    </tr>

                                    
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="2">B. SUB TOTAL PENAMBAHAN MODAL</td>
                                        <td class="text-end"><strong>Rp <?php echo e(number_format($report->bunga_bank - $report->pajak_bank + $totalLabaDibagi + $penambahanModal10, 0, ',', '.')); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">1. Bunga Bank</td>
                                        <td class="text-end">Rp <?php echo e(number_format($report->bunga_bank, 0, ',', '.')); ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">2. Pajak Bank</td>
                                        <td class="text-end text-danger">- Rp <?php echo e(number_format($report->pajak_bank, 0, ',', '.')); ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">3. Profit Sharing (90% + Carryover)</td>
                                        <td class="text-end">Rp <?php echo e(number_format($totalLabaDibagi, 0, ',', '.')); ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4">4. Penambahan Modal (10%)</td>
                                        <td class="text-end">Rp <?php echo e(number_format($penambahanModal10, 0, ',', '.')); ?></td>
                                        <td></td>
                                    </tr>

                                    
                                    <tr class="table-warning fw-bold">
                                        <td colspan="2">C. TOTAL SALDO MODAL (A + B)</td>
                                        <td class="text-end"><strong>Rp <?php echo e(number_format($report->saldo_awal_modal + $report->bunga_bank - $report->pajak_bank + $totalLabaDibagi + $penambahanModal10, 0, ',', '.')); ?></strong></td>
                                    </tr>

                                    
                                    <tr class="table-info fw-bold">
                                        <td colspan="2">D. TOTAL SALDO AKHIR MODAL (C - Profit Sharing)</td>
                                        <td class="text-end text-primary" style="font-size: 1.15rem;"><strong>Rp <?php echo e(number_format($report->saldo_akhir_modal, 0, ',', '.')); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Daily Average and General Info -->
                        <div class="col-md-5">
                            <h5 class="font-weight-bold text-gray-800 mb-3"><i class="fas fa-tachometer-alt text-success me-2"></i> Rata-rata Penjualan Harian</h5>
                            <div class="card bg-light border-0 mb-4">
                                <div class="card-body text-center py-4">
                                    <h6 class="text-muted text-uppercase mb-2">Volume Rata-rata per Hari</h6>
                                    <h2 class="font-weight-extrabold text-primary mb-0"><?php echo e(number_format($rataRataPenjualan, 2, ',', '.')); ?> L</h2>
                                    <small class="text-muted">Total Penjualan Aktual / Jumlah Hari</small>
                                </div>
                            </div>

                            <h5 class="font-weight-bold text-gray-800 mb-3"><i class="fas fa-check-double text-success me-2"></i> Keterangan & Catatan Rekonsiliasi</h5>
                            <div class="alert alert-info">
                                <ul class="mb-0 ps-3">
                                    <li>Pembelian DO dihitung dari sisa DO volume di Pertamina dikali HPP akhir bulan.</li>
                                    <li>Sisa stok Pertashop dihitung berdasarkan cm stik tangki dikali faktor skala dikali HPP.</li>
                                    <li>Laba bersih carryover bulan sebelumnya ditambahkan ke profit sharing bulan berjalan investor.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PAGE 5: REKAP MODAL -->
        <div class="tab-pane fade" id="page5" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="card-title text-primary font-weight-bold mb-4 border-bottom pb-3">Rekapitulasi Nilai Modal</h4>
                    
                    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%;">
                        <table class="table table-bordered table-striped" style="white-space: nowrap; font-size: 13px; width: 100%;">
                            <thead>
                                <tr class="text-center bg-dark text-white">
                                    <th>Tahun Ke</th>
                                    <th>Bulan</th>
                                    <th>Nilai Modal Awal</th>
                                    <th>Penyusutan Karena Rugi</th>
                                    <th>Pajak & Biaya Bank</th>
                                    <th>Penambahan (Keuntungan)</th>
                                    <th>Penambahan (Bunga Bank)</th>
                                    <th>Nilai Penambahan/Penyusutan</th>
                                    <th>Akumulasi Modal</th>
                                    <th>Posisi Akhir Modal</th>
                                    <th>Harga Beli Pertamax</th>
                                    <th>Konversi (Liter)</th>
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
                                <?php $__empty_1 = true; $__currentLoopData = $capitalRecaps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recap): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="text-center"><?php echo e($recap->tahun_ke); ?></td>
                                    <td class="text-center"><?php echo e($bulanIndo[$recap->bulan] ?? ''); ?> <?php echo e($recap->tahun); ?></td>
                                    <td class="text-right">Rp <?php echo e(number_format($recap->nilai_modal_awal, 0, ',', '.')); ?></td>
                                    
                                    <td class="text-right" <?php echo $recap->penyusutan_rugi < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->penyusutan_rugi > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : ''); ?>>
                                        Rp <?php echo e(number_format($recap->penyusutan_rugi, 0, ',', '.')); ?>

                                    </td>
                                    <td class="text-right" <?php echo $recap->penyusutan_pajak_bank < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->penyusutan_pajak_bank > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : ''); ?>>
                                        Rp <?php echo e(number_format($recap->penyusutan_pajak_bank, 0, ',', '.')); ?>

                                    </td>
                                    <td class="text-right" <?php echo $recap->penambahan_keuntungan < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->penambahan_keuntungan > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : ''); ?>>
                                        Rp <?php echo e(number_format($recap->penambahan_keuntungan, 0, ',', '.')); ?>

                                    </td>
                                    <td class="text-right" <?php echo $recap->penambahan_bunga_bank < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->penambahan_bunga_bank > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : ''); ?>>
                                        Rp <?php echo e(number_format($recap->penambahan_bunga_bank, 0, ',', '.')); ?>

                                    </td>
                                    
                                    <td class="text-right" <?php echo $recap->nilai_penambahan_penyusutan < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->nilai_penambahan_penyusutan > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : ''); ?>>
                                        Rp <?php echo e(number_format($recap->nilai_penambahan_penyusutan, 0, ',', '.')); ?>

                                    </td>
                                    
                                    <td class="text-right" <?php echo $recap->akumulasi_penambahan_penyusutan < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->akumulasi_penambahan_penyusutan > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : ''); ?>>
                                        Rp <?php echo e(number_format($recap->akumulasi_penambahan_penyusutan, 0, ',', '.')); ?>

                                    </td>
                                    <td class="text-right font-weight-bold">Rp <?php echo e(number_format($recap->posisi_akhir_modal, 0, ',', '.')); ?></td>
                                    <td class="text-right">Rp <?php echo e(number_format($recap->harga_beli_pertamax, 2, ',', '.')); ?></td>
                                    <td class="text-right font-weight-bold"><?php echo e(number_format($recap->konversi_liter, 2, ',', '.')); ?> L</td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="13" class="text-center text-muted">Belum ada data Rekapitulasi Modal.</td>
                                </tr>
                            </tbody>
                            <?php if(count($capitalRecaps) > 0): ?>
                            <?php
                                $sum_rugi = $capitalRecaps->sum('penyusutan_rugi');
                                $sum_pajak = $capitalRecaps->sum('penyusutan_pajak_bank');
                                $sum_keuntungan = $capitalRecaps->sum('penambahan_keuntungan');
                                $sum_bunga = $capitalRecaps->sum('penambahan_bunga_bank');
                                $sum_net = $capitalRecaps->sum('nilai_penambahan_penyusutan');
                                $last_recap = $capitalRecaps->last();
                                $last_akumulasi = $last_recap ? $last_recap->akumulasi_penambahan_penyusutan : 0;
                                $last_posisi = $last_recap ? $last_recap->posisi_akhir_modal : 0;
                                $last_konversi = $last_recap ? $last_recap->konversi_liter : 0;
                            ?>
                            <tfoot>
                                <tr class="font-weight-bold bg-light" style="font-size: 13px;">
                                    <td colspan="2" class="text-center fw-bold" style="font-weight: bold;">TOTAL / AKHIR</td>
                                    <td>-</td>
                                    <td class="text-right" style="color: #dc3545 !important; font-weight: bold;">Rp <?php echo e(number_format($sum_rugi, 0, ',', '.')); ?></td>
                                    <td class="text-right" style="color: #dc3545 !important; font-weight: bold;">Rp <?php echo e(number_format($sum_pajak, 0, ',', '.')); ?></td>
                                    <td class="text-right" style="color: #28a745 !important; font-weight: bold;">Rp <?php echo e(number_format($sum_keuntungan, 0, ',', '.')); ?></td>
                                    <td class="text-right" style="color: #28a745 !important; font-weight: bold;">Rp <?php echo e(number_format($sum_bunga, 0, ',', '.')); ?></td>
                                    <td class="text-right" <?php echo $sum_net < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($sum_net > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : ''); ?>>
                                        Rp <?php echo e(number_format($sum_net, 0, ',', '.')); ?>

                                    </td>
                                    <td class="text-right" <?php echo $last_akumulasi < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($last_akumulasi > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : ''); ?>>
                                        Rp <?php echo e(number_format($last_akumulasi, 0, ',', '.')); ?>

                                    </td>
                                    <td class="text-right" style="color: #0d6efd !important; font-weight: bold;">Rp <?php echo e(number_format($last_posisi, 0, ',', '.')); ?></td>
                                    <td>-</td>
                                    <td class="text-right" style="color: #0d6efd !important; font-weight: bold;"><?php echo e(number_format($last_konversi, 2, ',', '.')); ?> L</td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                                <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    /* Styling Tabs and Cards */
    .custom-tabs {
        border-bottom: none;
        display: flex;
        gap: 5px;
    }
    .custom-tabs .nav-link {
        border-radius: 8px 8px 0 0;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-bottom: none;
        color: #64748b;
        font-weight: 600;
        padding: 12px 20px;
        transition: all 0.15s ease;
    }
    .custom-tabs .nav-link.active {
        background-color: #ffffff;
        border-color: #cbd5e1;
        border-bottom-color: #ffffff;
        color: #4e73df;
        box-shadow: 0 -3px 0 #4e73df;
    }
    .custom-tab-content {
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 0 0 12px 12px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    
    /* Modern Aesthetic Table Styles */
    .custom-aesthetic-table {
        border-collapse: collapse;
        width: 100%;
        background-color: #ffffff;
    }
    .custom-aesthetic-table thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 0.72rem;
        text-transform: uppercase;
        border: 1px solid #e2e8f0;
        padding: 8px;
    }
    .custom-aesthetic-table tbody td {
        border: 1px solid #f1f5f9;
        padding: 8px;
        color: #334155;
        font-size: 0.8rem;
    }
    .custom-aesthetic-table tbody tr:hover td {
        background-color: #f8fafc;
    }
    .table-tfoot-total td {
        background-color: #f8fafc !important;
        font-weight: 700;
        border-top: 2px solid #cbd5e1;
        color: #1e293b;
        padding: 10px 8px;
        font-size: 0.82rem;
    }
    .sticky-col {
        position: sticky;
        left: 0;
        background-color: #ffffff;
        z-index: 5;
    }
    .shadow-sm-right {
        box-shadow: 2px 0 5px rgba(0,0,0,0.02);
    }

    @media print {
        .d-print-none { display: none !important; }
        .tab-content > .tab-pane {
            display: block !important;
            opacity: 1 !important;
            page-break-after: always;
        }
        .collapse:not(.show) {
            display: block !important;
        }
        body {
            background-color: #ffffff;
            color: #000000;
        }
        .custom-tab-content {
            border: none;
            box-shadow: none;
            padding: 0 !important;
        }
    }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/monthly_reports/show.blade.php ENDPATH**/ ?>