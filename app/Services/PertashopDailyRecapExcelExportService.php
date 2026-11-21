<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PertashopDailyRecapExcelExportService
{
    /**
     * Menghasilkan Spreadsheet PHPSpreadsheet lengkap persis sesuai template Excel REKAP KMT.
     */
    public static function generateSpreadsheet(array $recapData): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($recapData['rekap_title'], 0, 31));

        $shop = $recapData['shop'];
        $params = $recapData['parameters'];
        $rows = $recapData['rows'];
        $summary = $recapData['summary'];
        $daysInMonth = $recapData['days_in_month'];

        // 1. Header Judul Laporan (Baris 1 - 3)
        $sheet->mergeCells('A1:AY1');
        $sheet->setCellValue('A1', $recapData['rekap_title']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('003366'));

        $sheet->mergeCells('A2:AY2');
        $sheet->setCellValue('A2', sprintf('%s (%s) — %s | Tarif Jual: Rp %s | Tarif Beli: Rp %s | Komisi: Rp %s/L | Tangki: %s L/cm',
            $shop->nama,
            $shop->kode ?: 'KMT',
            $shop->alamat ?: '-',
            number_format($params['harga_jual'], 0, ',', '.'),
            number_format($params['harga_beli_pertamina'], 2, ',', '.'),
            number_format($params['komisi_operator_per_liter'], 0, ',', '.'),
            number_format($params['koefisien_tangki'], 2, ',', '.')
        ));
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));

        // 2. Baris Judul Kolom Bertingkat (Baris 4 & 5)
        $multiHeadersTop = [
            'A4:A5' => 'Tgl',
            'B4:B5' => 'No',
            'C4:C5' => 'Tanggal',
            'D4:D5' => 'Totalisator Awal',
            'E4:E5' => 'Totalisator Akhir',
            'F4:G4' => 'Teoritis Penjualan',
            'H4:I4' => 'TEST PUMP',
            'J4:K4' => $recapData['kirim_label'],
            'L4:M4' => 'Aktual Penjualan (L)',
            'N4:N5' => "STOK\nAWAL (ℓ)",
            'O4:O5' => "Pembelian\nBBM (ℓ)",
            'P4:P5' => "Terima\nBBM (ℓ)",
            'Q4:Q5' => "STOK\nTEORITIS (ℓ)",
            'R4:S4' => 'STOK AKTUAL AKHIR',
            'T4:T5' => "NOMINAL\nLOSSES / GAIN (ℓ)",
            'U4:U5' => "NOMINAL\nRUPIAH",
            'V4:V5' => 'LOSSES / GAIN',
            'W4:W5' => "STOK\nAKHIR (ℓ)",
            'X4:X5' => 'Jumlah Actual Penjualan',
            'Y4:AF4' => 'PENGELUARAN',
            'AG4:AG5' => "Total\nPengeluaran",
            'AH4:AI5' => 'Keterangan Lain2',
            'AJ4:AK5' => "Pendapatan (Dikurangi)\nPengeluaran",
            'AL4:AN4' => 'JUMLAH DISETORKAN',
            'AO4:AO5' => "Selisih Uang Yang\nDisetorkan dg Setoran",
            'AP4:AP5' => "Total uang yang\nbelum disetorkan",
            'AQ4:AQ5' => 'KETERANGAN',
            'AR4:AR5' => 'Tanggal',
            'AS4:AS5' => 'OMSET KOTOR',
            'AT4:AU4' => 'Losses / Gain (C)',
            'AV4:AV5' => 'OMSET + LOSSES',
            'AW4:AX4' => sprintf('Penjualan(D) x Rp%s (Gaji Opr.)', round($params['komisi_operator_per_liter'])),
            'AY4:AY5' => 'Penjualan + Losses - Pengeluaran',
        ];

        foreach ($multiHeadersTop as $range => $text) {
            $sheet->mergeCells($range);
            $firstCell = explode(':', $range)[0];
            $sheet->setCellValue($firstCell, $text);
        }

        // Sub-judul Baris 5
        $subHeaders = [
            'F5' => 'Volume (ℓ)', 'G5' => 'Rupiah',
            'H5' => 'Volume (ℓ)', 'I5' => 'Rupiah',
            'J5' => 'Volume (ℓ)', 'K5' => 'Rupiah',
            'L5' => 'Volume (ℓ)', 'M5' => 'Rupiah',
            'R5' => 'CM', 'S5' => 'Volume (ℓ)',
            'Y5' => 'Ongkos Bongkar', 'Z5' => 'Biaya Transfer', 'AA5' => 'Fotocopy & ATK',
            'AB5' => 'Listrik', 'AC5' => 'Air Bersih', 'AD5' => 'Cashback', 'AE5' => 'Internet', 'AF5' => 'Lain-lain',
            'AL5' => 'BRI', 'AM5' => 'QRIS BRI', 'AN5' => 'TRANSFER BY OPERATOR',
            'AT5' => 'Status', 'AU5' => 'Liter',
            'AW5' => 'Operator 1', 'AX5' => 'Operator 2',
        ];

        foreach ($subHeaders as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Styling Headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0F172A']]],
        ];
        $sheet->getStyle('A4:AY5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(26);
        $sheet->getRowDimension(5)->setRowHeight(22);

        // 3. Masukkan Data 31 Hari (Baris 6 s/d 6 + daysInMonth - 1)
        $startDataRow = 6;
        $currentRow = $startDataRow;

        foreach ($rows as $dayNum => $r) {
            $sheet->setCellValue("A{$currentRow}", $r['A_nomor_hari']);
            $sheet->setCellValue("B{$currentRow}", $r['B_no_urut']);
            $sheet->setCellValue("C{$currentRow}", $r['C_tanggal']);
            $sheet->setCellValue("D{$currentRow}", $r['D_totalisator_awal']);
            $sheet->setCellValue("E{$currentRow}", $r['E_totalisator_akhir']);
            $sheet->setCellValue("F{$currentRow}", $r['F_penjualan_teoritis_vol']);
            $sheet->setCellValue("G{$currentRow}", $r['G_penjualan_teoritis_rp']);
            $sheet->setCellValue("H{$currentRow}", $r['H_test_pump_vol']);
            $sheet->setCellValue("I{$currentRow}", $r['I_test_pump_rp']);
            $sheet->setCellValue("J{$currentRow}", $r['J_bbm_keluar_lain_vol']);
            $sheet->setCellValue("K{$currentRow}", $r['K_bbm_keluar_lain_rp']);
            $sheet->setCellValue("L{$currentRow}", $r['L_penjualan_aktual_vol']);
            $sheet->setCellValue("M{$currentRow}", $r['M_penjualan_aktual_rp']);
            $sheet->setCellValue("N{$currentRow}", $r['N_stok_awal']);
            $sheet->setCellValue("O{$currentRow}", $r['O_pembelian_bbm']);
            $sheet->setCellValue("P{$currentRow}", $r['P_terima_bbm']);
            $sheet->setCellValue("Q{$currentRow}", $r['Q_stok_teoritis']);
            $sheet->setCellValue("R{$currentRow}", $r['R_stok_aktual_cm']);
            $sheet->setCellValue("S{$currentRow}", $r['S_stok_aktual_vol']);
            $sheet->setCellValue("T{$currentRow}", $r['T_losses_gain_liter']);
            $sheet->setCellValue("U{$currentRow}", $r['U_losses_gain_rp']);
            $sheet->setCellValue("V{$currentRow}", $r['V_status_losses_gain']);
            $sheet->setCellValue("W{$currentRow}", $r['W_stok_akhir']);
            $sheet->setCellValue("X{$currentRow}", $r['X_jumlah_actual_penjualan']);
            $sheet->setCellValue("Y{$currentRow}", $r['Y_ongkos_bongkar']);
            $sheet->setCellValue("Z{$currentRow}", $r['Z_biaya_transfer']);
            $sheet->setCellValue("AA{$currentRow}", $r['AA_fotocopy_atk']);
            $sheet->setCellValue("AB{$currentRow}", $r['AB_listrik']);
            $sheet->setCellValue("AC{$currentRow}", $r['AC_air_bersih']);
            $sheet->setCellValue("AD{$currentRow}", $r['AD_cashback']);
            $sheet->setCellValue("AE{$currentRow}", $r['AE_internet']);
            $sheet->setCellValue("AF{$currentRow}", $r['AF_lain_lain']);
            $sheet->setCellValue("AG{$currentRow}", $r['AG_total_pengeluaran']);
            $sheet->mergeCells("AH{$currentRow}:AI{$currentRow}");
            $sheet->setCellValue("AH{$currentRow}", $r['AH_keterangan_lain_lain']);
            $sheet->mergeCells("AJ{$currentRow}:AK{$currentRow}");
            $sheet->setCellValue("AJ{$currentRow}", $r['AJ_pendapatan_minus_pengeluaran']);
            $sheet->setCellValue("AL{$currentRow}", $r['AL_setoran_bri']);
            $sheet->setCellValue("AM{$currentRow}", $r['AM_setoran_qris_bri']);
            $sheet->setCellValue("AN{$currentRow}", $r['AN_setoran_transfer_operator']);
            $sheet->setCellValue("AO{$currentRow}", $r['AO_selisih_setoran']);
            $sheet->setCellValue("AP{$currentRow}", $r['AP_total_uang_belum_disetor']);
            $sheet->setCellValue("AQ{$currentRow}", $r['AQ_keterangan_tanggal']);
            $sheet->setCellValue("AR{$currentRow}", $r['AR_tanggal_gaji']);
            $sheet->setCellValue("AS{$currentRow}", $r['AS_omset_kotor']);
            $sheet->setCellValue("AT{$currentRow}", $r['AT_status_gaji']);
            $sheet->setCellValue("AU{$currentRow}", $r['AU_losses_gain_gaji']);
            $sheet->setCellValue("AV{$currentRow}", $r['AV_omset_plus_losses']);
            $sheet->setCellValue("AW{$currentRow}", $r['AW_gaji_harian']);
            $sheet->setCellValue("AX{$currentRow}", $r['AX_gaji_harian_operator_2']);
            $sheet->setCellValue("AY{$currentRow}", $r['AY_total_gaji_harian']);

            // Formatting
            $sheet->getStyle("A{$currentRow}:B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("V{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AT{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Volume (3 desimal)
            $sheet->getStyle("D{$currentRow}:F{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
            $sheet->getStyle("H{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
            $sheet->getStyle("J{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
            $sheet->getStyle("L{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
            $sheet->getStyle("N{$currentRow}:Q{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
            $sheet->getStyle("S{$currentRow}:T{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
            $sheet->getStyle("W{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
            $sheet->getStyle("AS{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
            $sheet->getStyle("AU{$currentRow}:AV{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');

            // CM (2 desimal)
            $sheet->getStyle("R{$currentRow}")->getNumberFormat()->setFormatCode('0.00');

            // Rupiah (integer / tanpa desimal)
            $rupiahCols = ['G', 'I', 'K', 'M', 'U', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AJ', 'AL', 'AM', 'AN', 'AO', 'AP', 'AW', 'AX', 'AY'];
            foreach ($rupiahCols as $col) {
                $sheet->getStyle("{$col}{$currentRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0;[Red]"Rp "-#,##0');
            }

            // Gaji 2 desimal jika ada sen
            $sheet->getStyle("AW{$currentRow}:AY{$currentRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0.00;[Red]"Rp "-#,##0.00');

            // Background zebra & Sunday highlight
            if ($r['meta']['is_sunday']) {
                $sheet->getStyle("A{$currentRow}:AY{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
            } elseif ($currentRow % 2 == 0) {
                $sheet->getStyle("A{$currentRow}:AY{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $currentRow++;
        }

        $endDataRow = $currentRow - 1;

        // 4. Baris Total (Baris Rekap 35 di Excel)
        $totalRow = $currentRow;
        $t = $summary['row_total_35'];

        $sheet->mergeCells("A{$totalRow}:E{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        $sheet->setCellValue("F{$totalRow}", $t['F_penjualan_teoritis_vol']);
        $sheet->setCellValue("G{$totalRow}", $t['G_penjualan_teoritis_rp']);
        $sheet->setCellValue("H{$totalRow}", $t['H_test_pump_vol']);
        $sheet->setCellValue("I{$totalRow}", $t['I_test_pump_rp']);
        $sheet->setCellValue("J{$totalRow}", $t['J_bbm_keluar_lain_vol']);
        $sheet->setCellValue("K{$totalRow}", $t['K_bbm_keluar_lain_rp']);
        $sheet->setCellValue("L{$totalRow}", $t['L_penjualan_aktual_vol']);
        $sheet->setCellValue("M{$totalRow}", $t['M_penjualan_aktual_rp']);
        $sheet->setCellValue("O{$totalRow}", $t['O_pembelian_bbm']);
        $sheet->setCellValue("P{$totalRow}", $t['P_terima_bbm']);
        $sheet->setCellValue("T{$totalRow}", $t['T_losses_gain_liter']);
        $sheet->setCellValue("U{$totalRow}", $t['U_losses_gain_rp']);
        $sheet->setCellValue("V{$totalRow}", $t['V_status_losses_gain']);
        $sheet->setCellValue("X{$totalRow}", $t['X_jumlah_actual_penjualan']);
        $sheet->setCellValue("Y{$totalRow}", $t['Y_ongkos_bongkar']);
        $sheet->setCellValue("Z{$totalRow}", $t['Z_biaya_transfer']);
        $sheet->setCellValue("AA{$totalRow}", $t['AA_fotocopy_atk']);
        $sheet->setCellValue("AB{$totalRow}", $t['AB_listrik']);
        $sheet->setCellValue("AC{$totalRow}", $t['AC_air_bersih']);
        $sheet->setCellValue("AD{$totalRow}", $t['AD_cashback']);
        $sheet->setCellValue("AE{$totalRow}", $t['AE_internet']);
        $sheet->setCellValue("AF{$totalRow}", $t['AF_lain_lain']);
        $sheet->setCellValue("AG{$totalRow}", $t['AG_total_pengeluaran']);
        $sheet->mergeCells("AJ{$totalRow}:AK{$totalRow}");
        $sheet->setCellValue("AJ{$totalRow}", $t['AJ_pendapatan_minus_pengeluaran']);
        $sheet->setCellValue("AL{$totalRow}", $t['AL_setoran_bri']);
        $sheet->setCellValue("AM{$totalRow}", $t['AM_setoran_qris_bri']);
        $sheet->setCellValue("AN{$totalRow}", $t['AN_setoran_transfer_operator']);
        $sheet->setCellValue("AO{$totalRow}", $t['AO_selisih_setoran']);
        $sheet->setCellValue("AP{$totalRow}", $t['AP_total_uang_belum_disetor']);
        $sheet->setCellValue("AU{$totalRow}", $t['AU_losses_gain_gaji']);
        $sheet->setCellValue("AV{$totalRow}", $t['AV_omset_plus_losses']);
        $sheet->setCellValue("AW{$totalRow}", $t['AW_gaji_harian']);
        $sheet->setCellValue("AY{$totalRow}", $t['AY_total_gaji_harian']);

        $totalStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'borders' => [
                'top'    => ['borderStyle' => Border::BORDER_THIN],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
            ],
        ];
        $sheet->getStyle("A{$totalRow}:AY{$totalRow}")->applyFromArray($totalStyle);

        // Formats for Total Row
        $sheet->getStyle("F{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
        $sheet->getStyle("H{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
        $sheet->getStyle("J{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
        $sheet->getStyle("L{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
        $sheet->getStyle("O{$totalRow}:P{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
        $sheet->getStyle("T{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');
        $sheet->getStyle("AU{$totalRow}:AV{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.000;[Red]-#,##0.000');

        foreach (['G', 'I', 'K', 'M', 'U', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AJ', 'AL', 'AM', 'AN', 'AO', 'AP', 'AW', 'AY'] as $c) {
            $sheet->getStyle("{$c}{$totalRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0;[Red]"Rp "-#,##0');
        }

        // 5. Baris Rata-rata & Penutupan (Baris 36 - 39 di Excel)
        $avgRow = $totalRow + 1;
        $sheet->mergeCells("A{$avgRow}:E{$avgRow}");
        $sheet->setCellValue("A{$avgRow}", 'Rata-rata Penjualan Harian (>0)');
        $sheet->setCellValue("F{$avgRow}", $summary['row_averages_closing_36_39']['F36_avg_liter']);
        $sheet->setCellValue("G{$avgRow}", $summary['row_averages_closing_36_39']['G36_avg_rupiah']);
        $sheet->mergeCells("AH{$avgRow}:AK{$avgRow}");
        $sheet->setCellValue("AH{$avgRow}", 'Total Disetorkan (AL+AM+AN):');
        $sheet->setCellValue("AL{$avgRow}", $summary['row_averages_closing_36_39']['AL36_total_disetor']);

        $diffRow = $avgRow + 1;
        $sheet->mergeCells("AH{$diffRow}:AK{$diffRow}");
        $sheet->setCellValue("AH{$diffRow}", 'Selisih Total Setoran dg Kewajiban (AJ36):');
        $sheet->setCellValue("AL{$diffRow}", $summary['row_averages_closing_36_39']['AJ36_selisih_disetor']);

        $closeRow = $diffRow + 1;
        $sheet->mergeCells("AH{$closeRow}:AK{$closeRow}");
        $sheet->setCellValue("AH{$closeRow}", 'Setoran Closing (AJ37):');
        $sheet->setCellValue("AL{$closeRow}", $summary['row_averages_closing_36_39']['AJ37_setoran_closing']);

        $remainRow = $closeRow + 1;
        $sheet->mergeCells("AH{$remainRow}:AK{$remainRow}");
        $sheet->setCellValue("AH{$remainRow}", 'Sisa Setoran yg Belum Disetor (AJ38):');
        $sheet->setCellValue("AL{$remainRow}", $summary['row_averages_closing_36_39']['AJ38_sisa_setoran']);

        // Pecahan 1KL & 2KL
        $sheet->mergeCells("M{$avgRow}:N{$avgRow}");
        $sheet->setCellValue("M{$avgRow}", 'Pecahan 1.000 L:');
        $sheet->setCellValue("O{$avgRow}", $summary['row_averages_closing_36_39']['O38_pembelian_1kl']);
        $sheet->setCellValue("P{$avgRow}", $summary['row_averages_closing_36_39']['P38_terima_1kl']);

        $sheet->mergeCells("M{$diffRow}:N{$diffRow}");
        $sheet->setCellValue("M{$diffRow}", 'Pecahan 2.000 L:');
        $sheet->setCellValue("O{$diffRow}", $summary['row_averages_closing_36_39']['O39_pembelian_2kl']);
        $sheet->setCellValue("P{$diffRow}", $summary['row_averages_closing_36_39']['P39_terima_2kl']);

        // Gaji Operator (Baris 37-39 di Excel)
        $opRow = $avgRow;
        foreach ($summary['operator_salaries'] as $opSal) {
            $sheet->mergeCells("AR{$opRow}:AU{$opRow}");
            $sheet->setCellValue("AR{$opRow}", 'Gaji ' . $opSal['operator_name']);
            $sheet->setCellValue("AV{$opRow}", $opSal['total_av']);
            $sheet->setCellValue("AW{$opRow}", $opSal['gaji']);
            $sheet->getStyle("AV{$opRow}")->getNumberFormat()->setFormatCode('#,##0.000');
            $sheet->getStyle("AW{$opRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0.00');
            $opRow++;
        }

        // Freeze Panes: freeze kolom A-C dan baris 1-5
        $sheet->freezePane('D6');

        // Auto-fit kolom width
        foreach (range('A', 'AY') as $colID) {
            $sheet->getColumnDimension($colID)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}

