<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * IntelligentExcelRecognitionEngine (v2.4.0)
 *
 * Engine cerdas berbasis Dynamic Template Recognition & Mapping v2.4.0.
 * Mengintegrasikan Scanner, Analyzer, Profile Lookup, Dynamic Mapper, dan Decision Logger.
 */
class IntelligentExcelRecognitionEngine
{
    private WorkbookScannerService $scanner;
    private WorkbookStructureAnalyzerService $analyzer;
    private ImportProfileManager $profileManager;
    private DynamicTemplateRecognitionEngine $dynamicEngine;

    public function __construct(
        ?WorkbookScannerService $scanner = null,
        ?WorkbookStructureAnalyzerService $analyzer = null,
        ?ImportProfileManager $profileManager = null,
        ?DynamicTemplateRecognitionEngine $dynamicEngine = null
    ) {
        $this->scanner        = $scanner ?? new WorkbookScannerService();
        $this->analyzer       = $analyzer ?? new WorkbookStructureAnalyzerService();
        $this->profileManager = $profileManager ?? new ImportProfileManager();
        $this->dynamicEngine  = $dynamicEngine ?? new DynamicTemplateRecognitionEngine();
    }

    public function analyzeMultipleWorkbooks(array $filesInfo, Collection $shops): array
    {
        $allSheets        = [];
        $detectedShopsMap = [];
        $summaryStats     = [
            'total_files'          => count($filesInfo),
            'total_sheets'         => 0,
            'laporan_harian_count' => 0,
            'data_gaji_count'      => 0,
            'pembelian_do_count'    => 0,
            'laporan_keuangan_count' => 0,
            'di_luar_cakupan_count' => 0,
        ];

        foreach ($filesInfo as $fileInfo) {
            $filePath     = $fileInfo['path'];
            $originalName = $fileInfo['original_name'];
            $tempPath     = $fileInfo['temp_path'];

            $scanResult = $this->scanner->scanWorkbook($filePath);
            if (!$scanResult['success']) continue;

            try {
                $spreadsheet = IOFactory::load($filePath);
                $sheetNames  = $spreadsheet->getSheetNames();
            } catch (\Throwable $e) {
                continue;
            }

            foreach ($sheetNames as $index => $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if (!$sheet) continue;

                $analysis = $this->analyzeSingleSheet($sheet, $sheetName, $originalName, $tempPath, $shops, $index);

                if (!empty($analysis['shop'])) {
                    $detectedShopsMap[$analysis['shop']->id] = $analysis['shop'];
                }

                $summaryStats['total_sheets']++;
                $key = $analysis['jenis_kategori'] ?? 'laporan_harian';
                if (isset($summaryStats[$key . '_count'])) {
                    $summaryStats[$key . '_count']++;
                }

                $allSheets[] = $analysis;
            }
        }

        $groupedSheets = [
            'laporan_harian'   => [],
            'data_gaji'        => [],
            'pembelian_do'     => [],
            'laporan_keuangan' => [],
            'di_luar_cakupan'  => [],
        ];

        foreach ($allSheets as $item) {
            $cat = $item['jenis_kategori'] ?? 'di_luar_cakupan';
            if (isset($groupedSheets[$cat])) {
                $groupedSheets[$cat][] = $item;
            } else {
                $groupedSheets['di_luar_cakupan'][] = $item;
            }
        }

        return [
            'summary_stats'  => $summaryStats,
            'detected_shops' => array_values($detectedShopsMap),
            'all_sheets'     => $allSheets,
            'grouped_sheets' => $groupedSheets,
        ];
    }

    public function analyzeSingleSheet(
        $sheet,
        string $sheetName,
        string $fileName,
        string $tempPath,
        Collection $shops,
        int $index
    ): array {
        $structure = $this->analyzer->analyzeSheetStructure($sheet);

        $headers = [];
        $highestCol = $sheet->getHighestColumn();
        for ($c = 'A'; $c <= min('Z', $highestCol); $c++) {
            $v = trim(strval($sheet->getCell($c . $structure['header_row'])->getFormattedValue()));
            if ($v !== '') {
                $headers[$c] = $v;
            }
        }

        // Check Import Profile Lookup
        $signature = $this->profileManager->generateSignature($sheet, $headers);
        $savedProfile = $this->profileManager->findProfileBySignature($signature);

        $recSource = $savedProfile ? 'profile' : 'dynamic';

        $eval = $this->dynamicEngine->evaluateSheet($sheet, $headers, $structure, $shops, $fileName);

        $shopObj = $savedProfile && $savedProfile->shop ? $savedProfile->shop : $eval['shop'];

        // Classify Category
        $sheetLower = strtolower($sheetName);
        $categoryKey = 'laporan_harian';
        if (str_contains($sheetLower, 'gaji')) {
            $categoryKey = 'data_gaji';
        } elseif (str_contains($sheetLower, 'do') || str_contains($sheetLower, 'pembelian')) {
            $categoryKey = 'pembelian_do';
        }

        $rowCount = max(0, $structure['last_data_row'] - $structure['first_data_row'] + 1);

        return [
            'sheet_index'       => $index,
            'sheet_name'        => $sheetName,
            'sumber_file_excel' => $fileName,
            'temp_path'         => $tempPath,
            'workbook_signature'=> $signature,
            'recognition_source'=> $recSource,
            'profile_name'      => $savedProfile ? $savedProfile->profile_name : null,
            'header_row'        => $structure['header_row'],
            'first_data_row'    => $structure['first_data_row'],
            'last_data_row'     => $structure['last_data_row'],
            'shop'              => $shopObj,
            'shop_id'           => $shopObj->id ?? null,
            'shop_nama'         => $shopObj->nama ?? null,
            'shop_kode'         => $shopObj->kode ?? null,
            'jenis_kategori'    => $categoryKey,
            'mapping_config'    => $eval['mapping_config'],
            'confidence_scores' => $eval['confidence_scores'],
            'human_status'      => $eval['human_status'],
            'decision_log'      => $eval['decision_log'],
            'periode_text'      => 'Juli 2026',
            'summary'           => "Terdeteksi ~{$rowCount} baris transaksi untuk outlet " . ($shopObj->nama ?? 'Toko Tidak Dikenali') . ".",
            'include'           => in_array($categoryKey, ['laporan_harian', 'data_gaji']),
        ];
    }
}
