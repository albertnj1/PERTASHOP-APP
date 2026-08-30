<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Pertashop — <?php echo e($shop->nama ?? 'Outlet'); ?></title>
<style>
  @page { margin: 18mm 15mm 18mm 15mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 9px;
    color: #1a1a1a;
    line-height: 1.35;
  }

  .page-break { page-break-after: always; }

  /* ── Header Bar ───────────────────────────────────────── */
  .report-header {
    border-bottom: 2.5px solid #1e293b;
    padding-bottom: 6px;
    margin-bottom: 12px;
    display: table;
    width: 100%;
  }
  .report-header .left { display: table-cell; vertical-align: middle; width: 70%; }
  .report-header .right { display: table-cell; vertical-align: middle; text-align: right; width: 30%; }
  .report-header h2 { font-size: 13px; color: #0f172a; margin-bottom: 2px; }
  .report-header .subtitle { font-size: 9.5px; color: #64748b; }
  .report-header .badge {
    display: inline-block; background: #1e293b; color: #fff;
    font-size: 8px; padding: 3px 8px; border-radius: 4px;
    font-weight: 700; letter-spacing: 0.5px;
  }

  /* ── Tables ───────────────────────────────────────────── */
  table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  th, td { padding: 4px 6px; border: 0.5px solid #cbd5e1; font-size: 8.5px; }
  th {
    background: #f1f5f9; color: #334155; font-weight: 700;
    text-align: center; font-size: 8px; text-transform: uppercase;
    letter-spacing: 0.3px;
  }
  td.right, th.right { text-align: right; }
  td.center { text-align: center; }
  td.bold { font-weight: 700; }

  .section-title {
    background: #1e293b; color: #ffffff;
    font-size: 9.5px; font-weight: 700; padding: 5px 8px;
    margin: 12px 0 6px; border-radius: 3px;
    letter-spacing: 0.3px;
  }

  .subsection-title {
    background: #e2e8f0; color: #0f172a;
    font-size: 8.5px; font-weight: 700; padding: 4px 8px;
    margin: 8px 0 4px; border-radius: 2px;
  }

  .grand-total-row td {
    background: #1e293b; color: #ffffff;
    font-weight: 700; font-size: 9.5px;
    border: none; padding: 6px 8px;
  }

  .highlight-row td { background: #fef3c7; font-weight: 600; }
  .laba-row td { background: #dcfce7; font-weight: 700; color: #166534; }
  .rugi-row td { background: #fecaca; font-weight: 700; color: #991b1b; }
  .total-row td { font-weight: 700; background: #f8fafc; border-top: 1.5px solid #94a3b8; }

  /* ── Signature Area ──────────────────────────────────── */
  .signature-area { margin-top: 20px; }
  .signature-area table { border: none; }
  .signature-area td {
    border: none; text-align: center; padding: 6px 10px;
    vertical-align: top; width: 33.33%;
  }
  .sig-line { border-bottom: 1px solid #1a1a1a; width: 120px; margin: 40px auto 4px; }

  /* ── Footer ──────────────────────────────────────────── */
  .page-footer {
    position: fixed; bottom: 0; left: 0; right: 0;
    text-align: center; font-size: 7px; color: #94a3b8;
    border-top: 0.5px solid #e2e8f0; padding-top: 3px;
  }

  .rp { font-family: 'DejaVu Sans', Arial; }
  .text-muted { color: #64748b; font-size: 7.5px; }
  .text-sm { font-size: 8px; }
  .mt-4 { margin-top: 12px; }
  .mb-2 { margin-bottom: 6px; }
</style>
</head>
<body>

<?php
  $hal1 = $summary['hal1'] ?? [];
  $hal2 = $summary['hal2'] ?? [];
  $hal3 = $summary['hal3'] ?? [];
  $hal4 = $summary['hal4'] ?? [];
  $segments = $hal1['segments'] ?? [];
  $periodLabel = \Carbon\Carbon::parse(($period ?? now()->format('Y-m')) . '-01')->translatedFormat('F Y');
  $grandLabaKotor = $hal1['grand_total_laba_kotor'] ?? 0;
  $totalLiter = $hal1['total_liter_terjual'] ?? 0;
  $omsetHarian = $hal1['rata_rata_omset_harian'] ?? 0;
  $expenses = $hal2['pengeluaran_details'] ?? [];
  $investors = $hal2['investor_distributions'] ?? [];
?>


<div>
  <div class="report-header">
    <div class="left">
      <h2>LAPORAN STOK, PENJUALAN & LABA KOTOR</h2>
      <div class="subtitle">Pertashop <?php echo e($shop->nama ?? '-'); ?> — Kode: <?php echo e($shop->kode ?? '-'); ?></div>
    </div>
    <div class="right">
      <div class="badge">HALAMAN 1</div>
      <div class="subtitle mt-4">Periode: <?php echo e($periodLabel); ?></div>
    </div>
  </div>

  <?php $__currentLoopData = $segments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <?php $segIdx = $seg['segmen_index'] ?? 1; ?>
  <div class="subsection-title">
    I. PEMBELIAN <?php echo e($segIdx); ?>

    <?php if(!empty($seg['start_datetime_label']) || !empty($seg['end_datetime_label'])): ?>
      <span class="text-muted" style="font-weight:400; margin-left:8px;">
        Tot. Awal (<?php echo e($seg['start_datetime_label'] ?? '-'); ?>) → Tot. Akhir (<?php echo e($seg['end_datetime_label'] ?? '-'); ?>)
      </span>
    <?php endif; ?>
  </div>
  <table>
    <tr>
      <td style="width:28%">Harga Beli per Liter</td>
      <td class="right" style="width:22%"><span class="rp">Rp</span> <?php echo e(number_format($seg['harga_beli'] ?? 0, 2, ',', '.')); ?></td>
      <td style="width:28%">Harga Jual per Liter</td>
      <td class="right" style="width:22%"><span class="rp">Rp</span> <?php echo e(number_format($seg['harga_jual'] ?? 0, 2, ',', '.')); ?></td>
    </tr>
  </table>
  <table>
    <thead>
      <tr><th colspan="2">KOMPONEN</th><th>VOLUME (ℓ)</th><th>NILAI (Rp)</th></tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="2">a. Stok Awal</td>
        <td class="right"><?php echo e(number_format($seg['stok_awal'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($seg['stok_awal_rp'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr>
        <td colspan="2">b. BBM Datang (DO)</td>
        <td class="right"><?php echo e(number_format($seg['bbm_datang'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($seg['bbm_datang_rp'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr class="total-row">
        <td colspan="2">Jumlah Pembelian (a + b)</td>
        <td class="right"><?php echo e(number_format($seg['jumlah_pembelian'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($seg['jumlah_pembelian_rp'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr><td colspan="4" style="padding:2px; border:none;"></td></tr>
      <tr>
        <td colspan="2">c. Totalisator Awal</td>
        <td class="right"><?php echo e(number_format($seg['totalisator_awal'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right">—</td>
      </tr>
      <tr>
        <td colspan="2">d. Totalisator Akhir</td>
        <td class="right"><?php echo e(number_format($seg['totalisator_akhir'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right">—</td>
      </tr>
      <tr>
        <td colspan="2">e. Total Penjualan (d − c)</td>
        <td class="right"><?php echo e(number_format($seg['total_penjualan'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right">—</td>
      </tr>
      <tr>
        <td colspan="2">f. Test Pump / Tera</td>
        <td class="right"><?php echo e(number_format($seg['test_pump'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right">—</td>
      </tr>
      <tr class="highlight-row">
        <td colspan="2">Jumlah Penjualan Bersih (e − f)</td>
        <td class="right"><?php echo e(number_format($seg['jumlah_penjualan'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($seg['jumlah_penjualan_rp'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr><td colspan="4" style="padding:2px; border:none;"></td></tr>
      <tr>
        <td colspan="2">Sisa Stok Teoretis</td>
        <td class="right"><?php echo e(number_format($seg['sisa_stok_teoretis'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($seg['sisa_stok_teoretis_rp'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr>
        <td colspan="2">Stok Akhir Fisik <?php if($seg['stok_akhir_cm'] ?? 0 > 0): ?>(<?php echo e(number_format($seg['stok_akhir_cm'], 1)); ?> cm)<?php endif; ?></td>
        <td class="right"><?php echo e(number_format($seg['stok_akhir_fisik'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($seg['stok_akhir_fisik_rp'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr>
        <td colspan="2">Losses / Gain <?php if(($seg['losses_gain_persen'] ?? 0) > 0): ?>(<?php echo e(number_format($seg['losses_gain_persen'], 2)); ?>%)<?php endif; ?></td>
        <td class="right"><?php echo e(number_format($seg['losses_gain'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($seg['losses_gain_rp'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr class="<?php echo e(($seg['laba_kotor'] ?? 0) >= 0 ? 'laba-row' : 'rugi-row'); ?>">
        <td colspan="2">LABA KOTOR BATCH <?php echo e($segIdx); ?></td>
        <td class="right">—</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($seg['laba_kotor'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
    </tbody>
  </table>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

  <table>
    <tr class="grand-total-row">
      <td style="width:50%">GRAND TOTAL LABA KOTOR</td>
      <td class="right" style="width:50%"><span class="rp">Rp</span> <?php echo e(number_format($grandLabaKotor, 0, ',', '.')); ?></td>
    </tr>
  </table>

  <table style="margin-top:6px;">
    <tr>
      <td>Total Liter Terjual</td>
      <td class="right"><?php echo e(number_format($totalLiter, 2, ',', '.')); ?> ℓ</td>
      <td>Rata-rata Omset Harian</td>
      <td class="right"><?php echo e(number_format($omsetHarian, 2, ',', '.')); ?> ℓ/hari</td>
    </tr>
  </table>
</div>

<div class="page-break"></div>


<div>
  <div class="report-header">
    <div class="left">
      <h2>SISA DO DI MEES & RIWAYAT PERUBAHAN MARGIN</h2>
      <div class="subtitle">Pertashop <?php echo e($shop->nama ?? '-'); ?> — Periode: <?php echo e($periodLabel); ?></div>
    </div>
    <div class="right">
      <div class="badge">HALAMAN 2</div>
    </div>
  </div>

  <?php $doMees = $hal1['sisa_do_mees'] ?? []; ?>
  <div class="section-title">SISA DO DI MEES PERTAMINA</div>
  <table>
    <thead>
      <tr>
        <th>Stok Awal (KL)</th><th>Setor (KL)</th><th>Setoran Tunai</th>
        <th>Jumlah (KL)</th><th>Datang (KL)</th><th>Sisa (KL)</th><th>Harga Beli 1KL</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="center"><?php echo e(number_format($doMees['stok_awal_kl'] ?? 0, 2, ',', '.')); ?></td>
        <td class="center"><?php echo e(number_format($doMees['setor_kl'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($doMees['setoran_tunai'] ?? 0, 0, ',', '.')); ?></td>
        <td class="center"><?php echo e(number_format($doMees['jumlah_kl'] ?? 0, 2, ',', '.')); ?></td>
        <td class="center"><?php echo e(number_format($doMees['datang_kl'] ?? 0, 2, ',', '.')); ?></td>
        <td class="center"><?php echo e(number_format($doMees['sisa_kl'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($doMees['harga_beli_1kl'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
    </tbody>
  </table>

  <?php $marginHistory = $hal1['margin_history'] ?? []; ?>
  <?php if(count($marginHistory) > 0): ?>
  <div class="section-title" style="margin-top:16px;">RIWAYAT PERUBAHAN MARGIN HARGA BBM</div>
  <table>
    <thead>
      <tr>
        <th>No</th><th>Tanggal Berlaku</th><th>Harga Beli (Rp)</th><th>Harga Jual (Rp)</th>
        <th>Margin (Rp)</th><th>Perubahan</th><th>Arah</th>
      </tr>
    </thead>
    <tbody>
      <?php $__currentLoopData = $marginHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $mh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr>
        <td class="center"><?php echo e($idx + 1); ?></td>
        <td><?php echo e($mh['tanggal'] ?? '-'); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($mh['harga_beli'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($mh['harga_jual'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($mh['margin'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($mh['diff'] ?? 0, 2, ',', '.')); ?></td>
        <td class="center"><?php echo e($mh['arah'] ?? '-'); ?></td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div style="margin-top:14px;">
    <table>
      <tr>
        <td><strong>Sisa Stok Akhir Fisik</strong></td>
        <td class="right"><?php echo e(number_format($hal1['final_stok_liter'] ?? 0, 2, ',', '.')); ?> ℓ</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal1['final_stok_rp'] ?? 0, 0, ',', '.')); ?></td>
        <td>HPP Terakhir: <span class="rp">Rp</span> <?php echo e(number_format($hal1['final_harga_beli'] ?? 0, 2, ',', '.')); ?>/ℓ</td>
      </tr>
    </table>
  </div>
</div>

<div class="page-break"></div>


<div>
  <div class="report-header">
    <div class="left">
      <h2>PERHITUNGAN LABA BERSIH & PROFIT SHARING</h2>
      <div class="subtitle">Pertashop <?php echo e($shop->nama ?? '-'); ?> — Periode: <?php echo e($periodLabel); ?></div>
    </div>
    <div class="right">
      <div class="badge">HALAMAN 3</div>
    </div>
  </div>

  <table>
    <tr class="grand-total-row">
      <td>Grand Total Laba Kotor (dari Halaman 1)</td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($grandLabaKotor, 0, ',', '.')); ?></td>
    </tr>
  </table>

  <div class="section-title">RINCIAN BEBAN OPERASIONAL (11 POS)</div>
  <table>
    <thead>
      <tr><th style="width:30px">No</th><th>Pos Biaya</th><th style="width:35%">Nominal (Rp)</th></tr>
    </thead>
    <tbody>
      <?php
        $expenseItems = [
          ['Gaji 1 Operator', $expenses['gaji_operator'] ?? 0],
          ['Gaji Admin', $expenses['gaji_admin'] ?? 0],
          ['Ongkos Bongkar / Biaya Curah', $expenses['biaya_curah'] ?? 0],
          ['Biaya Transfer Bank', $expenses['biaya_tf'] ?? 0],
          ['Listrik', $expenses['listrik'] ?? 0],
          ['Air Bersih', $expenses['air'] ?? 0],
          ['Cashback Pengecer', $expenses['cashback'] ?? 0],
          ['Internet / Kuota', $expenses['internet'] ?? 0],
          ['Fotocopy & ATK', $expenses['atk'] ?? 0],
          ['Biaya Operasional Lain-lain', $expenses['lain_lain'] ?? 0],
        ];
        if (!empty($expenses['lain_lain_notes'])) {
          $expenseItems[9][0] .= ' (' . $expenses['lain_lain_notes'] . ')';
        }
      ?>
      <?php $__currentLoopData = $expenseItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr>
        <td class="center"><?php echo e($idx + 1); ?></td>
        <td><?php echo e($item[0]); ?></td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($item[1], 0, ',', '.')); ?></td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <tr class="total-row">
        <td colspan="2">TOTAL BIAYA OPERASIONAL</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal2['total_biaya'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
    </tbody>
  </table>

  <table style="margin-top:8px;">
    <tr class="<?php echo e(($hal2['laba_bersih'] ?? 0) >= 0 ? 'laba-row' : 'rugi-row'); ?>">
      <td style="width:65%">LABA BERSIH (Laba Kotor − Total Biaya)</td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal2['laba_bersih'] ?? 0, 0, ',', '.')); ?></td>
    </tr>
    <tr>
      <td>Alokasi Penambahan Modal Dasar (10%)</td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal2['alokasi_penambahan_modal'] ?? 0, 0, ',', '.')); ?></td>
    </tr>
    <tr>
      <td>Saldo Laba Bersih yang Dibagi (90%)</td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal2['saldo_laba_bersih_90'] ?? 0, 0, ',', '.')); ?></td>
    </tr>
    <?php if(($hal2['saldo_laba_sebelumnya'] ?? 0) > 0): ?>
    <tr>
      <td>Saldo Laba Periode Sebelumnya (Hold)</td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal2['saldo_laba_sebelumnya'] ?? 0, 0, ',', '.')); ?></td>
    </tr>
    <?php endif; ?>
    <tr class="highlight-row">
      <td>Total Saldo Laba Bersih yg Dibagi</td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal2['total_saldo_laba_dibagi'] ?? 0, 0, ',', '.')); ?></td>
    </tr>
  </table>

  <div class="section-title" style="margin-top:12px;">PEMBAGIAN LABA BERSIH (PROFIT SHARING)</div>
  <table>
    <thead>
      <tr>
        <th>No</th><th>Nama Pemegang Saham</th><th>Persentase</th><th>Nominal (Rp)</th>
        <th>Bank</th><th>No. Rekening</th><th>a/n</th>
      </tr>
    </thead>
    <tbody>
      <?php $__currentLoopData = $investors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr>
        <td class="center"><?php echo e($idx + 1); ?></td>
        <td><?php echo e($inv['nama'] ?? '-'); ?></td>
        <td class="center"><?php echo e(number_format($inv['persen'] ?? 0, 0)); ?>%</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($inv['nominal'] ?? 0, 0, ',', '.')); ?></td>
        <td><?php echo e($inv['nama_bank'] ?? '-'); ?></td>
        <td><?php echo e($inv['no_rekening'] ?? '-'); ?></td>
        <td><?php echo e($inv['atas_nama_rekening'] ?? '-'); ?></td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
  </table>

  <div class="signature-area">
    <table>
      <tr>
        <td>
          <div style="font-size:8px; color:#64748b;">Mengetahui,</div>
          <div style="font-weight:700;">Direktur PT. SAM</div>
          <div class="sig-line"></div>
          <div style="font-size:8px;">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
        </td>
        <td>
          <div style="font-size:8px; color:#64748b;">Menyetujui,</div>
          <div style="font-weight:700;">Komisaris</div>
          <div class="sig-line"></div>
          <div style="font-size:8px;">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
        </td>
        <td>
          <div style="font-size:8px; color:#64748b;">Dibuat Oleh,</div>
          <div style="font-weight:700;">Admin Pertashop</div>
          <div class="sig-line"></div>
          <div style="font-size:8px;">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
        </td>
      </tr>
    </table>
  </div>
</div>

<div class="page-break"></div>


<div>
  <div class="report-header">
    <div class="left">
      <h2>POSISI MODAL KERJA</h2>
      <div class="subtitle">Pertashop <?php echo e($shop->nama ?? '-'); ?> — Akhir Periode: <?php echo e($periodLabel); ?></div>
    </div>
    <div class="right">
      <div class="badge">HALAMAN 4</div>
    </div>
  </div>

  <div class="section-title">A. SALDO AWAL MODAL KERJA</div>
  <table>
    <tr class="highlight-row">
      <td style="width:60%"><strong>Saldo Awal Modal</strong></td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['saldo_awal_modal'] ?? 0, 0, ',', '.')); ?></td>
    </tr>
  </table>

  <div class="subsection-title">Rincian Posisi Aset</div>
  <table>
    <thead>
      <tr><th>Komponen</th><th>Nominal (Rp)</th></tr>
    </thead>
    <tbody>
      <tr>
        <td>DO yang Masih Ada di Pertamina</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['do_di_pertamina'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr>
        <td>Uang di Bank</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['uang_di_bank'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr>
        <td>Kas Kecil</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['kas_kecil'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr>
        <td>Sisa Stok yang Masih Ada di Pertashop</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['sisa_stok_pertashop_rp'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr>
        <td>Hasil Penjualan Belum Disetor</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['hasil_belum_disetor'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr>
        <td>Piutang</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['piutang'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr class="total-row">
        <td>Subtotal A</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['subtotal_a'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
    </tbody>
  </table>

  <div class="section-title">B. PENAMBAHAN / PENGURANGAN</div>
  <table>
    <tbody>
      <tr>
        <td style="width:60%">Bunga Bank (+)</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['bunga_bank'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr>
        <td>Pajak Bank (−)</td>
        <td class="right"><span class="rp">Rp</span> (<?php echo e(number_format(abs($hal3['pajak_bank'] ?? 0), 0, ',', '.')); ?>)</td>
      </tr>
      <tr>
        <td>Profit Sharing yang Dibagikan (−)</td>
        <td class="right"><span class="rp">Rp</span> (<?php echo e(number_format(abs($hal3['profit_sharing_dibagi'] ?? 0), 0, ',', '.')); ?>)</td>
      </tr>
      <tr>
        <td>Penambahan Keuntungan Bulan Ini (+)</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['penambahan_keuntungan'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
      <tr class="total-row">
        <td>Subtotal B</td>
        <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['subtotal_b'] ?? 0, 0, ',', '.')); ?></td>
      </tr>
    </tbody>
  </table>

  <table style="margin-top:8px;">
    <tr>
      <td style="width:60%"><strong>C. Subtotal (A + B)</strong></td>
      <td class="right"><strong><span class="rp">Rp</span> <?php echo e(number_format($hal3['subtotal_c'] ?? 0, 0, ',', '.')); ?></strong></td>
    </tr>
    <tr class="grand-total-row">
      <td>D. TOTAL SALDO AKHIR MODAL KERJA</td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal3['total_saldo_akhir_modal'] ?? 0, 0, ',', '.')); ?></td>
    </tr>
  </table>
</div>

<div class="page-break"></div>


<div>
  <div class="report-header">
    <div class="left">
      <h2>REKAPITULASI PERTUMBUHAN MODAL</h2>
      <div class="subtitle">Pertashop <?php echo e($shop->nama ?? '-'); ?> — s/d Periode: <?php echo e($periodLabel); ?></div>
    </div>
    <div class="right">
      <div class="badge">HALAMAN 5</div>
    </div>
  </div>

  <?php $recaps = $hal4['capital_recaps'] ?? []; ?>
  <?php if(count($recaps) > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Thn Ke</th><th>Bulan</th><th>Nilai Modal Awal</th>
        <th>Penyusutan / Rugi</th><th>Pajak Bank</th><th>Penambahan Keuntungan</th>
        <th>Bunga Bank</th><th>Nett</th><th>Akumulasi</th>
        <th>Posisi Akhir</th><th>Harga Beli</th><th>Konversi (ℓ)</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
      ?>
      <?php $__currentLoopData = $recaps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr>
        <td class="center"><?php echo e($rec['tahun_ke'] ?? ''); ?></td>
        <td class="center"><?php echo e(($monthNames[$rec['bulan'] ?? 0] ?? '') . ' ' . ($rec['tahun'] ?? '')); ?></td>
        <td class="right"><?php echo e(number_format($rec['nilai_modal_awal'] ?? 0, 0, ',', '.')); ?></td>
        <td class="right"><?php echo e(number_format($rec['penyusutan_rugi'] ?? 0, 0, ',', '.')); ?></td>
        <td class="right"><?php echo e(number_format($rec['penyusutan_pajak_bank'] ?? 0, 0, ',', '.')); ?></td>
        <td class="right"><?php echo e(number_format($rec['penambahan_keuntungan'] ?? 0, 0, ',', '.')); ?></td>
        <td class="right"><?php echo e(number_format($rec['penambahan_bunga_bank'] ?? 0, 0, ',', '.')); ?></td>
        <td class="right"><?php echo e(number_format($rec['nilai_penambahan_penyusutan'] ?? 0, 0, ',', '.')); ?></td>
        <td class="right"><?php echo e(number_format($rec['akumulasi_penambahan_penyusutan'] ?? 0, 0, ',', '.')); ?></td>
        <td class="right"><?php echo e(number_format($rec['posisi_akhir_modal'] ?? 0, 0, ',', '.')); ?></td>
        <td class="right"><?php echo e(number_format($rec['harga_beli_pertamax'] ?? 0, 2, ',', '.')); ?></td>
        <td class="right"><?php echo e(number_format($rec['konversi_liter'] ?? 0, 2, ',', '.')); ?></td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
  </table>
  <?php else: ?>
  <div style="text-align:center; padding:30px; color:#94a3b8; font-size:10px;">
    Belum ada data historis rekapitulasi modal untuk outlet ini.
  </div>
  <?php endif; ?>

  <table style="margin-top:12px;">
    <tr>
      <td style="width:55%">Modal Awal Dasar</td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal4['modal_awal_dasar'] ?? 60000000, 0, ',', '.')); ?></td>
    </tr>
    <tr>
      <td>Total Akumulasi Penambahan / Penyusutan</td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal4['total_akumulasi_modal'] ?? 0, 0, ',', '.')); ?></td>
    </tr>
    <tr>
      <td>Persentase Penambahan Modal</td>
      <td class="right"><?php echo e(number_format($hal4['persen_penambahan_modal'] ?? 0, 2, ',', '.')); ?>%</td>
    </tr>
    <tr class="grand-total-row">
      <td>GRAND TOTAL POSISI AKHIR MODAL</td>
      <td class="right"><span class="rp">Rp</span> <?php echo e(number_format($hal4['grand_total_modal'] ?? 0, 0, ',', '.')); ?></td>
    </tr>
    <tr>
      <td>Persentase Grand Total</td>
      <td class="right"><?php echo e(number_format($hal4['persen_grand_total'] ?? 100, 2, ',', '.')); ?>%</td>
    </tr>
  </table>

  <div style="margin-top:20px; text-align:center; font-size:8px; color:#94a3b8;">
    Dokumen ini dicetak secara otomatis oleh Sistem Informasi Pertashop Indonesia (SIPERI)<br>
    pada <?php echo e(now()->translatedFormat('l, d F Y — H:i')); ?> WIB
  </div>
</div>

</body>
</html>
<?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/pdf/backdate_report.blade.php ENDPATH**/ ?>