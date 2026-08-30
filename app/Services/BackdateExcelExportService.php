<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Models\Shop;
use Carbon\Carbon;

class BackdateExcelExportService
{
    /**
     * Generate file Excel bersih dan seragam dari data summary parsing.
     *
     * @param array $summary Data parsed dari BackdateExcelSummaryService::extract()
     * @param Shop $shop Toko terkait
     * @param string $period Periode (YYYY-MM)
     * @return string Path file Excel yang dihasilkan
     */
    public static function generate(array $summary, Shop $shop, string $period): string
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Ringkasan Penjualan & Laba Kotor per Batch
        self::buildSheet1($spreadsheet, $summary, $shop, $period);

        // Sheet 2: Rincian Biaya Operasional & Profit Sharing
        $sheet2 = $spreadsheet->createSheet();
        self::buildSheet2($sheet2, $summary, $shop, $period);

        // Sheet 3: Posisi Modal Kerja
        $sheet3 = $spreadsheet->createSheet();
        self::buildSheet3($sheet3, $summary, $shop, $period);

        // Sheet 4: Rekapitulasi Historis Modal
        $sheet4 = $spreadsheet->createSheet();
        self::buildSheet4($sheet4, $summary, $shop, $period);

        $spreadsheet->setActiveSheetIndex(0);

        // Simpan ke temp file
        $shopSlug = \Illuminate\Support\Str::slug($shop->nama);
        $periodSlug = str_replace('-', '_', $period);
        $filename = "Laporan_{$shopSlug}_{$periodSlug}_" . time() . ".xlsx";
        $tempPath = storage_path("app/public/backdate_exports/{$filename}");

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    private static function buildSheet1(Spreadsheet $spreadsheet, array $summary, Shop $shop, string $period): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Penjualan & Laba Kotor');

        $periodLabel = Carbon::parse($period . '-01')->translatedFormat('F Y');
        $hal1 = $summary['hal1'] ?? [];
        $segments = $hal1['segments'] ?? [];

        // Header
        $sheet->setCellValue('A1', 'LAPORAN STOK, PENJUALAN & LABA KOTOR');
        $sheet->setCellValue('A2', "Pertashop {$shop->nama} — Periode: {$periodLabel}");
        $sheet->setCellValue('A3', "Kode Outlet: {$shop->kode}");

        self::applyHeaderStyle($sheet, 'A1:H1');
        self::applySubHeaderStyle($sheet, 'A2:H2');

        $row = 5;

        foreach ($segments as $seg) {
            $segIdx = $seg['segmen_index'] ?? 1;
            $sheet->setCellValue("A{$row}", "PEMBELIAN {$segIdx}");
            self::applySectionStyle($sheet, "A{$row}:H{$row}");
            $row++;

            // Harga
            $sheet->setCellValue("A{$row}", 'Harga Beli/Liter');
            $sheet->setCellValue("C{$row}", $seg['harga_beli'] ?? 0);
            $sheet->setCellValue("E{$row}", 'Harga Jual/Liter');
            $sheet->setCellValue("G{$row}", $seg['harga_jual'] ?? 0);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;

            // Detail
            $details = [
                ['Stok Awal (ℓ)', $seg['stok_awal'] ?? 0, 'Stok Awal (Rp)', $seg['stok_awal_rp'] ?? 0],
                ['BBM Datang (ℓ)', $seg['bbm_datang'] ?? 0, 'BBM Datang (Rp)', $seg['bbm_datang_rp'] ?? 0],
                ['Jumlah Pembelian (ℓ)', $seg['jumlah_pembelian'] ?? 0, 'Jumlah Pembelian (Rp)', $seg['jumlah_pembelian_rp'] ?? 0],
                ['Totalisator Awal', $seg['totalisator_awal'] ?? 0, 'Totalisator Akhir', $seg['totalisator_akhir'] ?? 0],
                ['Total Penjualan (a-b)', $seg['total_penjualan'] ?? 0, '', ''],
                ['Test Pump / Tera', $seg['test_pump'] ?? 0, '', ''],
                ['Jumlah Penjualan (ℓ)', $seg['jumlah_penjualan'] ?? 0, 'Jumlah Penjualan (Rp)', $seg['jumlah_penjualan_rp'] ?? 0],
                ['Sisa Stok Teoretis (ℓ)', $seg['sisa_stok_teoretis'] ?? 0, 'Sisa Stok Teoretis (Rp)', $seg['sisa_stok_teoretis_rp'] ?? 0],
                ['Stok Akhir Fisik (ℓ)', $seg['stok_akhir_fisik'] ?? 0, 'Stok Akhir Fisik (Rp)', $seg['stok_akhir_fisik_rp'] ?? 0],
                ['Losses/Gain (ℓ)', $seg['losses_gain'] ?? 0, 'Losses/Gain (Rp)', $seg['losses_gain_rp'] ?? 0],
                ['Laba Kotor Batch', '', '', $seg['laba_kotor'] ?? 0],
            ];

            foreach ($details as $d) {
                $sheet->setCellValue("A{$row}", $d[0]);
                $sheet->setCellValue("C{$row}", $d[1]);
                if (!empty($d[2])) {
                    $sheet->setCellValue("E{$row}", $d[2]);
                    $sheet->setCellValue("G{$row}", $d[3]);
                    $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
                }
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                $row++;
            }

            $row++; // spacing
        }

        // Grand Total
        $sheet->setCellValue("A{$row}", 'GRAND TOTAL LABA KOTOR');
        $sheet->setCellValue("G{$row}", $hal1['grand_total_laba_kotor'] ?? 0);
        self::applySectionStyle($sheet, "A{$row}:H{$row}");
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $sheet->setCellValue("A{$row}", 'Total Liter Terjual');
        $sheet->setCellValue("C{$row}", $hal1['total_liter_terjual'] ?? 0);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        $sheet->setCellValue("A{$row}", 'Rata-rata Omset Harian (ℓ)');
        $sheet->setCellValue("C{$row}", $hal1['rata_rata_omset_harian'] ?? 0);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Auto-width
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private static function buildSheet2(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $summary, Shop $shop, string $period): void
    {
        $sheet->setTitle('Laba Bersih & Profit');
        $periodLabel = Carbon::parse($period . '-01')->translatedFormat('F Y');
        $hal1 = $summary['hal1'] ?? [];
        $hal2 = $summary['hal2'] ?? [];

        $sheet->setCellValue('A1', 'PERHITUNGAN LABA BERSIH & PROFIT SHARING');
        $sheet->setCellValue('A2', "Pertashop {$shop->nama} — Periode: {$periodLabel}");
        self::applyHeaderStyle($sheet, 'A1:F1');
        self::applySubHeaderStyle($sheet, 'A2:F2');

        $row = 4;
        $sheet->setCellValue("A{$row}", 'Grand Total Laba Kotor');
        $sheet->setCellValue("D{$row}", $hal1['grand_total_laba_kotor'] ?? 0);
        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row += 2;

        // Biaya Operasional
        $sheet->setCellValue("A{$row}", 'RINCIAN BIAYA OPERASIONAL');
        self::applySectionStyle($sheet, "A{$row}:F{$row}");
        $row++;

        $expenses = $hal2['pengeluaran_details'] ?? [];
        $expenseItems = [
            ['Gaji Operator', $expenses['gaji_operator'] ?? 0],
            ['Gaji Admin', $expenses['gaji_admin'] ?? 0],
            ['Biaya Curah / Bongkar', $expenses['biaya_curah'] ?? 0],
            ['Biaya Transfer Bank', $expenses['biaya_tf'] ?? 0],
            ['Listrik', $expenses['listrik'] ?? 0],
            ['Air Bersih', $expenses['air'] ?? 0],
            ['Cashback Pengecer', $expenses['cashback'] ?? 0],
            ['Internet / Kuota', $expenses['internet'] ?? 0],
            ['Fotocopy & ATK', $expenses['atk'] ?? 0],
            ['Biaya Lain-lain', $expenses['lain_lain'] ?? 0],
        ];

        $no = 1;
        foreach ($expenseItems as $item) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $item[0]);
            $sheet->setCellValue("D{$row}", $item[1]);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        $sheet->setCellValue("A{$row}", '');
        $sheet->setCellValue("B{$row}", 'TOTAL BIAYA OPERASIONAL');
        $sheet->setCellValue("D{$row}", $hal2['total_biaya'] ?? 0);
        $sheet->getStyle("B{$row}:D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row += 2;

        // Laba Bersih
        $sheet->setCellValue("A{$row}", 'LABA BERSIH');
        $sheet->setCellValue("D{$row}", $hal2['laba_bersih'] ?? 0);
        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $sheet->setCellValue("A{$row}", 'Alokasi Penambahan Modal (10%)');
        $sheet->setCellValue("D{$row}", $hal2['alokasi_penambahan_modal'] ?? 0);
        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $sheet->setCellValue("A{$row}", 'Saldo Laba Bersih yg Dibagi (90%)');
        $sheet->setCellValue("D{$row}", $hal2['saldo_laba_bersih_90'] ?? 0);
        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row += 2;

        // Investor Distributions
        $sheet->setCellValue("A{$row}", 'PEMBAGIAN PROFIT SHARING');
        self::applySectionStyle($sheet, "A{$row}:F{$row}");
        $row++;

        $sheet->setCellValue("A{$row}", 'No');
        $sheet->setCellValue("B{$row}", 'Nama Investor');
        $sheet->setCellValue("C{$row}", 'Persentase');
        $sheet->setCellValue("D{$row}", 'Nominal (Rp)');
        $sheet->setCellValue("E{$row}", 'Bank');
        $sheet->setCellValue("F{$row}", 'No. Rekening');
        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
        $row++;

        $investors = $hal2['investor_distributions'] ?? [];
        $no = 1;
        foreach ($investors as $inv) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $inv['nama'] ?? '-');
            $sheet->setCellValue("C{$row}", ($inv['persen'] ?? 0) . '%');
            $sheet->setCellValue("D{$row}", $inv['nominal'] ?? 0);
            $sheet->setCellValue("E{$row}", $inv['nama_bank'] ?? '-');
            $sheet->setCellValue("F{$row}", $inv['no_rekening'] ?? '-');
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private static function buildSheet3(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $summary, Shop $shop, string $period): void
    {
        $sheet->setTitle('Posisi Modal Kerja');
        $periodLabel = Carbon::parse($period . '-01')->translatedFormat('F Y');
        $hal3 = $summary['hal3'] ?? [];

        $sheet->setCellValue('A1', 'POSISI MODAL KERJA');
        $sheet->setCellValue('A2', "Pertashop {$shop->nama} — Periode: {$periodLabel}");
        self::applyHeaderStyle($sheet, 'A1:D1');
        self::applySubHeaderStyle($sheet, 'A2:D2');

        $row = 4;
        $items = [
            ['A. Saldo Awal Modal', $hal3['saldo_awal_modal'] ?? 0, true],
            ['    DO di Pertamina', $hal3['do_di_pertamina'] ?? 0, false],
            ['    Uang di Bank', $hal3['uang_di_bank'] ?? 0, false],
            ['    Kas Kecil', $hal3['kas_kecil'] ?? 0, false],
            ['    Sisa Stok Pertashop', $hal3['sisa_stok_pertashop_rp'] ?? 0, false],
            ['    Hasil Belum Disetor', $hal3['hasil_belum_disetor'] ?? 0, false],
            ['    Piutang', $hal3['piutang'] ?? 0, false],
            ['', '', false],
            ['B. Penambahan / Pengurangan', '', true],
            ['    Bunga Bank', $hal3['bunga_bank'] ?? 0, false],
            ['    Pajak Bank', $hal3['pajak_bank'] ?? 0, false],
            ['    Profit Sharing yg Dibagi', $hal3['profit_sharing_dibagi'] ?? 0, false],
            ['    Penambahan Keuntungan', $hal3['penambahan_keuntungan'] ?? 0, false],
            ['    Subtotal B', $hal3['subtotal_b'] ?? 0, true],
            ['', '', false],
            ['C. Subtotal (A + B)', $hal3['subtotal_c'] ?? 0, true],
            ['D. Total Saldo Akhir Modal', $hal3['total_saldo_akhir_modal'] ?? 0, true],
        ];

        foreach ($items as $item) {
            $sheet->setCellValue("A{$row}", $item[0]);
            if ($item[1] !== '') {
                $sheet->setCellValue("C{$row}", $item[1]);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
            }
            if ($item[2]) {
                $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
            }
            $row++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private static function buildSheet4(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $summary, Shop $shop, string $period): void
    {
        $sheet->setTitle('Rekap Historis Modal');
        $periodLabel = Carbon::parse($period . '-01')->translatedFormat('F Y');
        $hal4 = $summary['hal4'] ?? [];

        $sheet->setCellValue('A1', 'REKAPITULASI PERTUMBUHAN MODAL');
        $sheet->setCellValue('A2', "Pertashop {$shop->nama} — s/d Periode: {$periodLabel}");
        self::applyHeaderStyle($sheet, 'A1:L1');
        self::applySubHeaderStyle($sheet, 'A2:L2');

        $row = 4;
        $headers = ['Thn Ke', 'Bulan', 'Nilai Modal Awal', 'Penyusutan/Rugi', 'Pajak Bank',
            'Penambahan Keuntungan', 'Bunga Bank', 'Nett Penambahan', 'Akumulasi', 'Posisi Akhir', 'Harga Beli', 'Konversi (L)'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue("{$col}{$row}", $h);
            $col++;
        }
        $sheet->getStyle("A{$row}:L{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $row++;

        $recaps = $hal4['capital_recaps'] ?? [];
        foreach ($recaps as $rec) {
            $monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
            $bulanLabel = ($monthNames[$rec['bulan'] ?? 0] ?? '') . ' ' . ($rec['tahun'] ?? '');

            $sheet->setCellValue("A{$row}", $rec['tahun_ke'] ?? '');
            $sheet->setCellValue("B{$row}", $bulanLabel);
            $sheet->setCellValue("C{$row}", $rec['nilai_modal_awal'] ?? 0);
            $sheet->setCellValue("D{$row}", $rec['penyusutan_rugi'] ?? 0);
            $sheet->setCellValue("E{$row}", $rec['penyusutan_pajak_bank'] ?? 0);
            $sheet->setCellValue("F{$row}", $rec['penambahan_keuntungan'] ?? 0);
            $sheet->setCellValue("G{$row}", $rec['penambahan_bunga_bank'] ?? 0);
            $sheet->setCellValue("H{$row}", $rec['nilai_penambahan_penyusutan'] ?? 0);
            $sheet->setCellValue("I{$row}", $rec['akumulasi_penambahan_penyusutan'] ?? 0);
            $sheet->setCellValue("J{$row}", $rec['posisi_akhir_modal'] ?? 0);
            $sheet->setCellValue("K{$row}", $rec['harga_beli_pertamax'] ?? 0);
            $sheet->setCellValue("L{$row}", $rec['konversi_liter'] ?? 0);

            foreach (range('C', 'L') as $c) {
                $sheet->getStyle("{$c}{$row}")->getNumberFormat()->setFormatCode('#,##0');
            }
            $row++;
        }

        $row++;
        $sheet->setCellValue("A{$row}", 'Modal Awal Dasar');
        $sheet->setCellValue("C{$row}", $hal4['modal_awal_dasar'] ?? 60000000);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row++;
        $sheet->setCellValue("A{$row}", 'Total Akumulasi Penambahan');
        $sheet->setCellValue("C{$row}", $hal4['total_akumulasi_modal'] ?? 0);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row++;
        $sheet->setCellValue("A{$row}", '% Penambahan Modal');
        $sheet->setCellValue("C{$row}", round($hal4['persen_penambahan_modal'] ?? 0, 2) . '%');
        $row++;
        $sheet->setCellValue("A{$row}", 'Grand Total Modal');
        $sheet->setCellValue("C{$row}", $hal4['grand_total_modal'] ?? 0);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    // ── Style Helpers ────────────────────────────────────────────────────────

    private static function applyHeaderStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');
        $sheet->getStyle($range)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->mergeCells($range);
    }

    private static function applySubHeaderStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle($range)->getFont()->getColor()->setRGB('475569');
        $sheet->mergeCells($range);
    }

    private static function applySectionStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $sheet->mergeCells($range);
    }
}
