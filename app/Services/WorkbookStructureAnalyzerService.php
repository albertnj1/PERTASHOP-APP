<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * WorkbookStructureAnalyzerService
 *
 * Mengidentifikasi baris pre-header (judul, logo, alamat), offset header,
 * baris data pertama (first_data_row), dan baris data terakhir (last_data_row)
 * secara presisi tanpa mengasumsikan nomor baris/kolom statis.
 */
class WorkbookStructureAnalyzerService
{
    public function analyzeSheetStructure(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        $headerRow     = 1;
        $firstDataRow  = 2;
        $lastDataRow   = $highestRow;
        $headerScores  = [];
        $preHeaderTexts= [];

        $fieldAliases  = config('field_aliases', []);

        // 1. Scan baris 1 s/d 20 untuk mendeteksi baris dengan skor header terbanyak
        for ($r = 1; $r <= min(20, $highestRow); $r++) {
            $matchCount = 0;
            $rowTexts   = [];

            for ($c = 'A'; $c <= min('Z', $highestCol); $c++) {
                $cellValue = trim(strval($sheet->getCell($c . $r)->getFormattedValue()));
                if ($cellValue === '') continue;

                $valLower = strtolower($cellValue);
                $rowTexts[] = $cellValue;

                foreach ($fieldAliases as $fieldKey => $aliases) {
                    foreach ($aliases as $alias) {
                        if (str_contains($valLower, strtolower($alias))) {
                            $matchCount++;
                            break 2;
                        }
                    }
                }
            }

            $headerScores[$r] = $matchCount;

            if ($matchCount === 0 && !empty($rowTexts)) {
                $preHeaderTexts[] = implode(' | ', $rowTexts);
            }
        }

        // Cari baris dengan match count tertinggi sebagai Header Row
        if (!empty($headerScores)) {
            arsort($headerScores);
            $bestHeaderRow = key($headerScores);
            if ($headerScores[$bestHeaderRow] > 0) {
                $headerRow = $bestHeaderRow;
            }
        }

        $firstDataRow = $headerRow + 1;

        // 2. Cari Last Data Row (baris non-kosong terakhir di kolom tanggal/totalisator)
        for ($r = $highestRow; $r >= $firstDataRow; $r--) {
            $hasData = false;
            for ($c = 'A'; $c <= min('M', $highestCol); $c++) {
                $val = trim(strval($sheet->getCell($c . $r)->getFormattedValue()));
                if ($val !== '' && !in_array(strtolower($val), ['total', 'jumlah', 'rata-rata', 'average'])) {
                    $hasData = true;
                    break;
                }
            }
            if ($hasData) {
                $lastDataRow = $r;
                break;
            }
        }

        return [
            'header_row'       => $headerRow,
            'first_data_row'   => $firstDataRow,
            'last_data_row'    => $lastDataRow,
            'pre_header_texts' => array_slice($preHeaderTexts, 0, 5),
            'header_score'     => $headerScores[$headerRow] ?? 0,
        ];
    }
}
