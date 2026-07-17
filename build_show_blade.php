<?php
$blade = <<<'EOD'
@extends('layouts.app')
@section('title', 'Laporan Bulanan')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
        <h4 class="mb-0">Laporan Bulanan (4 Halaman)</h4>
        <div>
            <a href="{{ route('monthly-reports.index') }}" class="btn btn-secondary me-2"><i class="fas fa-arrow-left"></i> Kembali</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Cetak / Export PDF</button>
        </div>
    </div>

    @php
        $periods = $report->data_parsed ?? [];
        $grandTotals = $report->grand_totals ?? [];
        $investors = $grandTotals['investors'] ?? [];
        
        $monthName = \Carbon\Carbon::parse($report->bulan_tahun)->isoFormat('MMMM YYYY');
        $monthNumStr = \Carbon\Carbon::parse($report->bulan_tahun)->isoFormat('DD-MM YYYY'); 
        $shopName = strtoupper($shop->name);
        $companyName = strtoupper($shop->company_name ?? 'PT SERAYU AGUNG MANDIRI');
        
        // --- Calculate Page 1 Variables ---
        $stokAwalFisik = $report->stok_awal_fisik ?? 0;
        $totalLabaKotor = 0;
        $page1Periods = [];
        
        $currentStokAwal = $stokAwalFisik;
        
        foreach($periods as $idx => $p) {
            $jmlBeliL = $currentStokAwal + $p['tot_bbm_datang'];
            $jmlBeliRp = ($currentStokAwal * $p['harga_beli']) + $p['tot_bbm_datang_rp'];
            
            $totJualL = $p['tot_akhir'] - $p['tot_awal'];
            $jmlJualL = $totJualL - $p['test_pump'];
            
            $sisaStokL = $jmlBeliL - $jmlJualL;
            $sisaStokRp = $sisaStokL * $p['harga_beli'];
            
            $lossesL = $p['stok_aktual'] - $sisaStokL;
            $lossesPersen = $jmlBeliL > 0 ? ($lossesL / $jmlBeliL) * 100 : 0;
            $lossesRp = $lossesL * $p['harga_beli'];
            
            $penjualanBersihRp = $jmlBeliRp - $sisaStokRp + $lossesRp;
            
            $labaKotorRp = ($jmlJualL * $p['harga_jual']) - $penjualanBersihRp;
            $totalLabaKotor += $labaKotorRp;
            
            $page1Periods[$idx] = [
                'stok_awal' => $currentStokAwal,
                'bbm_datang' => $p['tot_bbm_datang'],
                'jml_beli_l' => $jmlBeliL,
                'jml_beli_rp' => $jmlBeliRp,
                'tot_akhir' => $p['tot_akhir'],
                'tot_awal' => $p['tot_awal'],
                'tot_jual' => $totJualL,
                'test_pump' => $p['test_pump'],
                'jml_jual' => $jmlJualL,
                'jml_jual_rp' => $jmlJualL * $p['harga_jual'],
                'sisa_stok' => $sisaStokL,
                'sisa_stok_rp' => $sisaStokRp,
                'losses' => $lossesL,
                'losses_persen' => $lossesPersen,
                'losses_rp' => $lossesRp,
                'penjualan_bersih_rp' => $penjualanBersihRp,
                'laba_kotor' => $labaKotorRp,
                'stok_aktual' => $p['stok_aktual'],
                'harga_beli' => $p['harga_beli'],
                'harga_jual' => $p['harga_jual'],
                'start_date' => $p['start_date'],
                'end_date' => $p['end_date']
            ];
            
            $currentStokAwal = $p['stok_aktual'];
        }
        
        // --- Calculate Page 2 Variables ---
        $rincianAll = [];
        $totalBiaya = 0;
        foreach($periods as $p) {
            if(isset($p['rincian_pengeluaran']) && is_array($p['rincian_pengeluaran'])) {
                foreach($p['rincian_pengeluaran'] as $rinc) {
                    $rincianAll[] = $rinc;
                    $totalBiaya += $rinc['nom'];
                }
            }
        }
        $labaBersih = $totalLabaKotor - $totalBiaya;
        $alokasiModal = $labaBersih > 0 ? $labaBersih * 0.1 : 0;
        $labaDibagi = $labaBersih - $alokasiModal;
        
        // --- Calculate Page 3 Variables (Reconciliation) ---
        $saldoAwal = $report->saldo_awal_modal ?? 0;
        $doPertamina = ($report->do_di_pertamina ?? 0) * ($p['harga_beli'] ?? 0);
        $kasKecil = $report->kas_kecil ?? 0;
        $piutang = $report->piutang ?? 0;
        $sisaStokKerja = $currentStokAwal * ($p['harga_beli'] ?? 0);
        $penjualanBelumDisetor = $report->hasil_penjualan_belum_disetor ?? 0;
        $bungaBank = $report->bunga_bank ?? 0;
        $pajakBank = $report->pajak_bank ?? 0;
        
        $subTotalPenambahan = $bungaBank - $pajakBank + $labaDibagi + $alokasiModal;
        $saldoAkhirPlusPenambahan = $saldoAwal + $subTotalPenambahan;
        $totalModalAkhir = $saldoAkhirPlusPenambahan - $labaDibagi;
        
        // Rekonsiliasi Uang di Bank
        // Aset Fisik = Sisa Stok + Hasil Penjualan + Kas Kecil (Note: Kas Kecil is normally positive asset)
        $uangDiBankReconciled = $saldoAwal + $doPertamina - $kasKecil - $sisaStokKerja - $penjualanBelumDisetor - $piutang;
        
    @endphp

    <style>
        .report-page {
            background: white;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            page-break-after: always;
        }
        .report-header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        table { width: 100%; border-collapse: collapse; }
        .table-bordered th, .table-bordered td { border: 1px solid #000 !important; padding: 4px; }
        .no-border th, .no-border td { border: none !important; padding: 2px 4px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        @media print {
            body { background: white; }
            .container-fluid { padding: 0 !important; }
            .report-page { box-shadow: none; margin: 0; padding: 0; }
        }
    </style>

    <!-- PAGE 1: LABA KOTOR -->
    <div class="report-page">
        <div class="report-header">
            LAPORAN STOCK, PENJUALAN & LABA KOTOR {{ \Carbon\Carbon::parse($report->bulan_tahun)->format('01-t F Y') }}<br>
            {{ $shopName }}<br>
            {{ $companyName }}
        </div>
        
        <table class="no-border mb-3">
            <tr>
                <td class="fw-bold" colspan="2">PERTAMAX :</td>
                <td></td>
            </tr>
            @foreach($page1Periods as $idx => $p)
            <tr>
                <td>Harga Beli {{ $idx }} : Rp {{ number_format($p['harga_beli'], 2) }}</td>
                <td>Harga Jual {{ $idx }} : Rp {{ number_format($p['harga_jual'], 0) }}</td>
                @if($idx == 1)
                <td rowspan="{{ count($page1Periods) }}" class="text-right">Rata-rata omset Harian (L) = {{ number_format($grandTotals['penjualan_liter'] / \Carbon\Carbon::parse($report->bulan_tahun)->daysInMonth, 2) }}</td>
                @endif
            </tr>
            @endforeach
        </table>
        
        @foreach($page1Periods as $idx => $p)
        <table class="no-border mb-3">
            <tr><td colspan="7" class="fw-bold">I. PEMBELIAN {{ $idx }}</td></tr>
            <tr>
                <td width="20%">Stok Awal</td>
                <td width="20%" class="text-right">{{ number_format($p['stok_awal'], 2) }} L</td>
                <td width="5%" class="text-center">x</td>
                <td width="15%">Rp {{ number_format($p['harga_beli'], 2) }}</td>
                <td width="5%" class="text-center">=</td>
                <td width="15%">Rp</td>
                <td width="20%" class="text-right">{{ number_format($p['stok_awal'] * $p['harga_beli'], 0) }}</td>
            </tr>
            <tr>
                <td>BBM Datang</td>
                <td class="text-right">{{ number_format($p['bbm_datang'], 2) }} L</td>
                <td class="text-center">x</td>
                <td>Rp {{ number_format($p['harga_beli'], 2) }}</td>
                <td class="text-center">=</td>
                <td>Rp</td>
                <td class="text-right">{{ number_format($p['bbm_datang'] * $p['harga_beli'], 0) }}</td>
            </tr>
            <tr>
                <td class="fw-bold">A. Jumlah Pembelian {{ $idx }}</td>
                <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($p['jml_beli_l'], 2) }} L</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="fw-bold" style="border-top:1px solid #000 !important">Rp</td>
                <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($p['jml_beli_rp'], 0) }}</td>
            </tr>
            
            <tr><td colspan="7" class="fw-bold pt-3">II. PENJUALAN {{ $idx }}</td></tr>
            <tr>
                <td colspan="4">a. Totalisator Akhir ({{ $p['end_date'] }})</td>
                <td class="text-center">=</td>
                <td colspan="2" class="text-right">{{ number_format($p['tot_akhir'], 2) }}</td>
            </tr>
            <tr>
                <td colspan="4">b. Totalisator Awal ({{ $p['start_date'] }})</td>
                <td class="text-center">=</td>
                <td colspan="2" class="text-right" style="border-bottom:1px solid #000 !important">{{ number_format($p['tot_awal'], 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="fw-bold">c. Total Penjualan {{ $idx }} (a-b)</td>
                <td class="text-center">=</td>
                <td colspan="2" class="text-right fw-bold">{{ number_format($p['tot_jual'], 2) }}</td>
            </tr>
            <tr>
                <td colspan="4">d. Percobaan (Test Pump)</td>
                <td class="text-center">=</td>
                <td colspan="2" class="text-right" style="border-bottom:1px solid #000 !important">{{ number_format($p['test_pump'], 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="fw-bold">B. Jumlah Penjualan {{ $idx }} (c-d)</td>
                <td class="text-center">=</td>
                <td class="fw-bold">{{ number_format($p['jml_jual'], 2) }} L</td>
                <td class="text-center">x</td>
                <td class="fw-bold">Rp {{ number_format($p['harga_jual'], 0) }}</td>
                <td class="text-right fw-bold">= Rp {{ number_format($p['jml_jual_rp'], 0) }}</td>
            </tr>
            <tr>
                <td colspan="2">Sisa Stock {{ $idx }} (A-B)</td>
                <td class="text-center">=</td>
                <td>{{ number_format($p['sisa_stok'], 2) }} L</td>
                <td class="text-center">x</td>
                <td>Rp {{ number_format($p['harga_beli'], 2) }}</td>
                <td class="text-right">= Rp {{ number_format($p['sisa_stok_rp'], 0) }}</td>
            </tr>
            <tr>
                <td colspan="2">Loses / Gain <span style="color:red">({{ number_format($p['losses_persen'], 3) }} %)</span></td>
                <td class="text-center">=</td>
                <td><span style="color:red">({{ number_format(abs($p['losses']), 2) }})</span> L</td>
                <td class="text-center">x</td>
                <td>Rp {{ number_format($p['harga_beli'], 2) }}</td>
                <td class="text-right"><span style="color:red">= Rp ({{ number_format(abs($p['losses_rp']), 0) }})</span></td>
            </tr>
            <tr>
                <td colspan="2" class="fw-bold">C. Jumlah Penjualan Bersih {{ $idx }}</td>
                <td colspan="4"></td>
                <td class="text-right fw-bold" style="border-top:1px solid #000 !important">Rp {{ number_format($p['penjualan_bersih_rp'], 0) }}</td>
            </tr>
            
            <tr><td colspan="7" class="fw-bold pt-3">III. Sisa Stock Akhir {{ $idx }}</td></tr>
            <tr>
                <td colspan="2"></td>
                <td class="text-center">:</td>
                <td>{{ number_format($p['stok_aktual'], 2) }} L</td>
                <td class="text-center">x</td>
                <td>Rp {{ number_format($p['harga_beli'], 2) }}</td>
                <td class="text-right fw-bold">= Rp {{ number_format($p['stok_aktual'] * $p['harga_beli'], 0) }}</td>
            </tr>
        </table>
        @endforeach
        
        <table class="table-bordered mt-4" style="width: 50%; float: right;">
            @foreach($page1Periods as $idx => $p)
            <tr>
                <td>Total Laba Kotor {{ $idx }}</td>
                <td>= Rp</td>
                <td class="text-right">{{ number_format($p['laba_kotor'], 0) }}</td>
            </tr>
            @endforeach
            <tr>
                <td class="fw-bold text-center">Grand Total Laba Kotor</td>
                <td class="fw-bold">= Rp</td>
                <td class="text-right fw-bold">{{ number_format($totalLabaKotor, 0) }}</td>
            </tr>
        </table>
        <div style="clear:both;"></div>
    </div>

    <!-- PAGE 2: LABA BERSIH -->
    <div class="report-page">
        <div class="report-header">
            PERHITUNGAN LABA BERSIH {{ \Carbon\Carbon::parse($report->bulan_tahun)->format('01-t F Y') }}<br>
            {{ $shopName }}<br>
            {{ $companyName }}
        </div>
        
        <table class="no-border">
            <tr>
                <td class="fw-bold" colspan="2">PENDAPATAN</td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td width="5%">1.</td>
                <td width="50%">LABA KOTOR ................................................................</td>
                <td width="5%">=</td>
                <td width="10%">Rp</td>
                <td width="30%" class="text-right">{{ number_format($totalLabaKotor, 0) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right fw-bold">A. Total Laba Kotor =</td>
                <td></td>
                <td class="fw-bold" style="border-top:1px solid #000 !important">Rp</td>
                <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($totalLabaKotor, 0) }}</td>
            </tr>
            
            <tr>
                <td class="fw-bold pt-3" colspan="2">PENGELUARAN</td>
                <td colspan="3"></td>
            </tr>
            @foreach($rincianAll as $i => $rinc)
            <tr>
                <td>{{ $i+1 }}.</td>
                <td>{{ strtoupper($rinc['ket']) }} ................................................................</td>
                <td>=</td>
                <td>Rp</td>
                <td class="text-right">{{ number_format($rinc['nom'], 0) }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="2" class="text-right fw-bold">B. Total Biaya =</td>
                <td></td>
                <td class="fw-bold" style="border-top:1px solid #000 !important">Rp</td>
                <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($totalBiaya, 0) }}</td>
            </tr>
            
            <tr><td colspan="5" style="height:20px;"></td></tr>
            
            <tr>
                <td colspan="2" class="text-right fw-bold">A. Total Laba Kotor =</td>
                <td></td>
                <td>Rp</td>
                <td class="text-right">{{ number_format($totalLabaKotor, 0) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right fw-bold">B. Total Biaya =</td>
                <td></td>
                <td style="border-bottom:1px solid #000 !important">Rp</td>
                <td class="text-right" style="border-bottom:1px solid #000 !important">{{ number_format($totalBiaya, 0) }} -</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right fw-bold">(A-B) LABA BERSIH =</td>
                <td></td>
                <td class="fw-bold">Rp</td>
                <td class="text-right fw-bold">{{ number_format($labaBersih, 0) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right fw-bold" style="color:red">*) Alokasi Penambahan Modal dari 10% Profit =</td>
                <td></td>
                <td class="fw-bold" style="color:red">Rp</td>
                <td class="text-right fw-bold" style="color:red; border-bottom:1px solid #000 !important">{{ number_format($alokasiModal, 0) }} -</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right fw-bold">Saldo Laba Bersih (90%) yg Dibagi =</td>
                <td></td>
                <td class="fw-bold">Rp</td>
                <td class="text-right fw-bold">{{ number_format($labaDibagi, 0) }}</td>
            </tr>
        </table>
        
        <table class="no-border mt-4">
            <tr>
                <td class="fw-bold" colspan="5">Pembagian Laba Bersih :</td>
            </tr>
            @foreach($investors as $i => $inv)
            <tr>
                <td width="5%">{{ $i+1 }}.</td>
                <td width="40%">{{ $inv['nama'] }} ......................................</td>
                <td width="10%">{{ $inv['persen'] }}%</td>
                <td width="5%">=</td>
                <td width="10%">Rp</td>
                <td width="30%" class="text-right">{{ number_format($labaDibagi * ($inv['persen']/100), 0) }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="4"></td>
                <td class="fw-bold" style="border-top:1px solid #000 !important">Rp</td>
                <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($labaDibagi, 0) }}</td>
            </tr>
        </table>
    </div>

    <!-- PAGE 3: POSISI MODAL KERJA -->
    <div class="report-page">
        <div class="report-header">
            POSISI MODAL KERJA PERIODE {{ \Carbon\Carbon::parse($report->bulan_tahun)->format('01-t F Y') }}<br>
            {{ $shopName }}<br>
            {{ $companyName }}
        </div>
        
        <table class="table-bordered mt-4">
            <tr><td colspan="7" class="text-center fw-bold bg-light">POSISI MODAL KERJA</td></tr>
            <tr>
                <td colspan="5" class="text-right fw-bold">Saldo Awal Modal Periode Bulan Sebelumnya :</td>
                <td class="fw-bold">Rp</td>
                <td class="text-right fw-bold">{{ number_format($saldoAwal, 0) }}</td>
            </tr>
            <tr>
                <td width="3%">1.</td>
                <td width="30%">DO yang Masih Ada di Pertamina</td>
                <td width="7%">{{ number_format($report->do_di_pertamina ?? 0, 2) }} L</td>
                <td width="3%">x</td>
                <td width="15%">Rp {{ number_format(end($periods)['harga_beli'] ?? 0, 2) }}</td>
                <td width="5%">: Rp</td>
                <td width="20%" class="text-right">-</td>
            </tr>
            <tr>
                <td>2.</td>
                <td colspan="4">Uang Di Bank Periode Bulan ini</td>
                <td>: Rp</td>
                <td class="text-right">{{ number_format($uangDiBankReconciled, 0) }}</td>
            </tr>
            <tr>
                <td>3.</td>
                <td colspan="4">Kas Kecil di Pertashop (TUNAI)</td>
                <td>: Rp</td>
                <td class="text-right" style="color:red">({{ number_format(abs($kasKecil), 0) }})</td>
            </tr>
            <tr>
                <td>4.</td>
                <td>Sisa Stok yang Masih ada Di Pertashop</td>
                <td>{{ number_format($currentStokAwal, 2) }} L</td>
                <td>x</td>
                <td>Rp {{ number_format(end($periods)['harga_beli'] ?? 0, 2) }}</td>
                <td>: Rp</td>
                <td class="text-right" style="color:red">({{ number_format(abs($sisaStokKerja), 0) }})</td>
            </tr>
            <tr>
                <td>5.</td>
                <td colspan="4">Hasil Penjualan yang Belum Disetor di Akhir Periode (TUNAI)</td>
                <td>: Rp</td>
                <td class="text-right" style="color:red">({{ number_format(abs($penjualanBelumDisetor), 0) }})</td>
            </tr>
            <tr>
                <td>6.</td>
                <td colspan="4">Piutang</td>
                <td>: Rp</td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td colspan="5" class="text-right fw-bold">A. Sub Total Saldo Akhir Modal .</td>
                <td class="fw-bold">: Rp</td>
                <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($saldoAwal, 0) }}</td>
            </tr>
            <tr>
                <td>7.</td>
                <td colspan="4">Bunga Bank Periode Bulan ini</td>
                <td>: Rp</td>
                <td class="text-right">{{ number_format($bungaBank, 0) }}</td>
            </tr>
            <tr>
                <td>8.</td>
                <td colspan="4">Pajak Bank Periode Bulan ini</td>
                <td>: Rp</td>
                <td class="text-right" style="color:red">({{ number_format(abs($pajakBank), 0) }})</td>
            </tr>
            <tr>
                <td>9.</td>
                <td colspan="4">Profit Sharing yang dibagi ke Investor</td>
                <td>: Rp</td>
                <td class="text-right">{{ number_format($labaDibagi, 0) }}</td>
            </tr>
            <tr>
                <td>10.</td>
                <td colspan="4"><span style="color:#0d6efd">Penambahan</span> / <span style="color:red">Pengurangan</span> Modal dari Keuntungan bulan ini</td>
                <td>: Rp</td>
                <td class="text-right">{{ number_format($alokasiModal, 0) }}</td>
            </tr>
            <tr>
                <td colspan="5" class="text-right fw-bold">B. Sub Total Penambahan Modal .</td>
                <td class="fw-bold">: Rp</td>
                <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($subTotalPenambahan, 0) }}</td>
            </tr>
            <tr>
                <td colspan="5" class="text-right fw-bold">C. Sub Total Saldo Akhir Modal (A+B) .</td>
                <td class="fw-bold">: Rp</td>
                <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($saldoAkhirPlusPenambahan, 0) }}</td>
            </tr>
            <tr>
                <td colspan="5" class="text-right fw-bold">D. Total Saldo Akhir Modal (C-9) .</td>
                <td class="fw-bold">: Rp</td>
                <td class="text-right fw-bold" style="border-top:1px solid #000 !important">{{ number_format($totalModalAkhir, 0) }}</td>
            </tr>
        </table>
    </div>

    <!-- PAGE 4: REKAPITULASI -->
    <div class="report-page">
        <div class="report-header">
            REKAPITULASI NILAI MODAL<br>
            {{ $shopName }}<br>
            {{ $companyName }}
        </div>
        
        <table class="table-bordered mt-4">
            <thead class="bg-light text-center">
                <tr>
                    <th>Bulan</th>
                    <th>Nilai Modal Awal</th>
                    <th>Penyusutan Karena Rugi</th>
                    <th>Penyusutan Biaya Bank</th>
                    <th>Penambahan Alokasi 10%</th>
                    <th>Penambahan Bunga Bank</th>
                    <th>Posisi Akhir Modal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($history ?? [] as $hist)
                    @php
                        // To accurately render history, we need the parsed data. For now we use the report data for the current month
                        if ($hist->id == $report->id) {
                            $m_awal = $saldoAwal;
                            $m_rugi = $labaBersih < 0 ? abs($labaBersih) : 0;
                            $m_biaya = abs($pajakBank);
                            $m_alo = $alokasiModal;
                            $m_bunga = $bungaBank;
                            $m_akhir = $totalModalAkhir;
                        } else {
                            $m_awal = $hist->saldo_awal_modal;
                            // Simplify history for past months unless fully parsed
                            $m_rugi = 0; $m_biaya = 0; $m_alo = 0; $m_bunga = 0; $m_akhir = 0;
                        }
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($hist->bulan_tahun)->isoFormat('MMMM YYYY') }}</td>
                        <td class="text-right">Rp {{ number_format($m_awal, 0) }}</td>
                        <td class="text-right">Rp {{ number_format($m_rugi, 0) }}</td>
                        <td class="text-right">Rp {{ number_format($m_biaya, 0) }}</td>
                        <td class="text-right">Rp {{ number_format($m_alo, 0) }}</td>
                        <td class="text-right">Rp {{ number_format($m_bunga, 0) }}</td>
                        <td class="text-right fw-bold">Rp {{ number_format($m_akhir, 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection
EOD;

file_put_contents('C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views\monthly_reports\show.blade.php', $blade);
echo "show.blade.php built completely!\n";
