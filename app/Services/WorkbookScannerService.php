<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * WorkbookScannerService
 *
 * Mengikuti Single Responsibility Principle (SRP):
 * Khusus membaca metadata mentah workbook (jumlah sheet, hidden sheets,
 * protected sheets, used range, merged cells, properti berkas).
 */
class WorkbookScannerService
{
    public function scanWorkbook(string $filePath): array
    {
        $startTime = microtime(true);

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => 'Gagal membaca berkas Excel: ' . $e->getMessage(),
            ];
        }

        $sheetNames    = $spreadsheet->getSheetNames();
        $totalSheets   = count($sheetNames);
        $hiddenSheets  = 0;
        $protectedSheets = 0;
        $sheetsMeta    = [];

        foreach ($sheetNames as $index => $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) continue;

            $state = $sheet->getSheetState();
            $isHidden = ($state !== Worksheet::SHEETSTATE_VISIBLE);
            if ($isHidden) {
                $hiddenSheets++;
            }

            $protection  = $sheet->getProtection();
            $isProtected = method_exists($protection, 'isProtectionEnabled')
                ? $protection->isProtectionEnabled()
                : (method_exists($protection, 'getSheet') ? (bool) $protection->getSheet() : false);
            if ($isProtected) {
                $protectedSheets++;
            }

            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $mergedCells = count($sheet->getMergeCells());

            $sheetsMeta[] = [
                'index'            => $index,
                'name'             => $sheetName,
                'highest_row'      => $highestRow,
                'highest_col'      => $highestCol,
                'is_hidden'        => $isHidden,
                'is_protected'     => $isProtected,
                'merged_cell_count'=> $mergedCells,
            ];
        }

        $scanTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'success'          => true,
            'file_name'        => basename($filePath),
            'file_size_bytes'  => file_exists($filePath) ? filesize($filePath) : 0,
            'total_sheets'     => $totalSheets,
            'hidden_sheets'    => $hiddenSheets,
            'protected_sheets' => $protectedSheets,
            'sheets_meta'      => $sheetsMeta,
            'scan_time_ms'     => $scanTimeMs,
        ];
    }
}
