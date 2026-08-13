<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\DepositCategory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelTemplateService
{
    /**
     * Generate Single-Sheet Standard Template Excel untuk Data Operasional Harian Toko.
     */
    public function generateStandardTemplate(Shop $shop): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Operasional Harian');

        // Header Baku Sistem
        $baseHeaders = [
            'Tanggal (YYYY-MM-DD)',
            'Operator ID / Nama',
            'Totalisator Awal',
            'Totalisator Akhir',
            'Stik Awal',
            'Stik Akhir',
            'Test Pump / Tera (Liter)',
            'Penerimaan / Incoming (Liter)',
            'No. DO Pertamina',
            'Plat Tangki',
            'Pengeluaran Harian (Rp)',
            'Keterangan Pengeluaran',
        ];

        // Kategori Setoran Dinamis Toko
        $categories = DepositCategory::where('shop_id', $shop->id)
            ->orWhereNull('shop_id')
            ->get();

        $depositHeaders = [];
        foreach ($categories as $cat) {
            $depositHeaders[] = 'Setoran: ' . $cat->nama_kategori;
        }

        $allHeaders = array_merge($baseHeaders, $depositHeaders);

        // Render Header
        $colIndex = 1;
        foreach ($allHeaders as $headerText) {
            $cellCoordinate = $this->getColumnLetter($colIndex) . '1';
            $sheet->setCellValue($cellCoordinate, $headerText);
            $colIndex++;
        }

        // Styling Header
        $lastCol = $this->getColumnLetter(count($allHeaders));
        $headerRange = "A1:{$lastCol}1";

        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Auto Width Columns
        for ($i = 1; $i <= count($allHeaders); $i++) {
            $sheet->getColumnDimension($this->getColumnLetter($i))->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private function getColumnLetter(int $index): string
    {
        $numeric = ($index - 1) % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval(($index - 1) / 26);
        if ($num2 > 0) {
            return $this->getColumnLetter($num2) . $letter;
        } else {
            return $letter;
        }
    }
}
