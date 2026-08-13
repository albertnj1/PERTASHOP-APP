<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * DynamicTemplateRecognitionEngine
 *
 * Menghasilkan Workbook Signature Fingerprint, Granular Confidence Score Breakdown,
 * serta Engine Decision Log untuk transparansi audit.
 */
class DynamicTemplateRecognitionEngine
{
    private DynamicFieldMapperService $fieldMapper;
    private LogicalBlockRecognitionEngine $blockEngine;

    public function __construct(
        ?DynamicFieldMapperService $fieldMapper = null,
        ?LogicalBlockRecognitionEngine $blockEngine = null
    ) {
        $this->fieldMapper = $fieldMapper ?? new DynamicFieldMapperService();
        $this->blockEngine = $blockEngine ?? new LogicalBlockRecognitionEngine();
    }

    public function evaluateSheet(
        Worksheet $sheet,
        array $headers,
        array $structure,
        Collection $shops,
        string $fileName
    ): array {
        $decisionLog = [];
        $startTime = microtime(true);

        $sheetName = $sheet->getTitle();
        $decisionLog[] = "Sheet Name: '{$sheetName}', Header Row Detected: {$structure['header_row']}";

        // 1. Outlet Recognition
        $outletMatch = $this->recognizeOutlet($sheetName, $fileName, $headers, $shops, $decisionLog);

        // 2. Field Mapping
        $mappingResult = $this->fieldMapper->mapHeadersToFields($headers, $decisionLog);
        $mappedFields  = $mappingResult['mapping'];
        $confidenceMap = $mappingResult['confidence'];

        // 3. Logical Block Detection
        $detectedBlocks = $this->blockEngine->detectBlocks($mappedFields);
        $decisionLog[]  = "Logical Blocks Detected: " . implode(', ', array_keys($detectedBlocks));

        // 4. Granular Confidence Score Breakdown
        $scores = $this->calculateGranularScores($outletMatch, $headers, $confidenceMap, $detectedBlocks);

        $evalTimeMs = round((microtime(true) - $startTime) * 1000, 2);
        $decisionLog[] = "Evaluation completed in {$evalTimeMs} ms. Overall Confidence: {$scores['overall']}%";

        return [
            'sheet_name'        => $sheetName,
            'header_row'        => $structure['header_row'],
            'first_data_row'    => $structure['first_data_row'],
            'last_data_row'     => $structure['last_data_row'],
            'shop'              => $outletMatch['shop'],
            'shop_id'           => $outletMatch['shop']->id ?? null,
            'shop_nama'         => $outletMatch['shop']->nama ?? null,
            'mapping_config'    => $mappedFields,
            'detected_blocks'   => $detectedBlocks,
            'confidence_scores' => $scores,
            'human_status'      => $scores['human_status'],
            'decision_log'      => $decisionLog,
            'eval_time_ms'      => $evalTimeMs,
        ];
    }

    private function recognizeOutlet(
        string $sheetName,
        string $fileName,
        array $headers,
        Collection $shops,
        array &$decisionLog
    ): array {
        $outletAliases = config('outlet_aliases', []);

        $headerText = implode(' ', $headers);
        $combinedText = strtolower($sheetName . ' ' . $fileName . ' ' . $headerText);

        foreach ($shops as $shop) {
            $shopNameLower = strtolower($shop->nama);

            // Match exact shop name
            if (str_contains($combinedText, $shopNameLower)) {
                $decisionLog[] = "Outlet Matched: '{$shop->nama}' via Direct Name Match";
                return ['shop' => $shop, 'score' => 1.0, 'method' => 'Direct Name Match'];
            }

            // Match via Outlet Alias Dictionary
            $aliases = $outletAliases[$shop->nama] ?? [];
            foreach ($aliases as $alias) {
                if (str_contains($combinedText, strtolower($alias))) {
                    $decisionLog[] = "Outlet Matched: '{$shop->nama}' via Alias '{$alias}'";
                    return ['shop' => $shop, 'score' => 0.95, 'method' => "Alias '{$alias}'"];
                }
            }
        }

        $decisionLog[] = "WARNING: Outlet could not be determined automatically.";
        return ['shop' => null, 'score' => 0.0, 'method' => 'None'];
    }

    private function calculateGranularScores(
        array $outletMatch,
        array $headers,
        array $confidenceMap,
        array $detectedBlocks
    ): array {
        $outletScore = round($outletMatch['score'] * 100, 1);
        $headerScore = empty($headers) ? 0.0 : 95.0;

        $fieldScores = array_values($confidenceMap);
        $fieldScore  = empty($fieldScores) ? 0.0 : round((array_sum($fieldScores) / count($fieldScores)) * 100, 1);

        $templateScore = !empty($detectedBlocks) ? 95.0 : 50.0;

        $overall = round(($outletScore * 0.35) + ($fieldScore * 0.35) + ($headerScore * 0.15) + ($templateScore * 0.15), 1);

        $humanStatus = match (true) {
            $overall >= 95.0 => ['badge' => 'success', 'label' => 'Ready (Auto Commit Ready)'],
            $overall >= 85.0 => ['badge' => 'warning', 'label' => 'Ready with Warning'],
            $overall >= 70.0 => ['badge' => 'info',    'label' => 'Needs Review'],
            default          => ['badge' => 'secondary','label' => 'Manual Mapping Required'],
        };

        return [
            'overall'        => $overall,
            'outlet_score'   => $outletScore,
            'header_score'   => $headerScore,
            'field_score'    => $fieldScore,
            'template_score' => $templateScore,
            'human_status'   => $humanStatus,
        ];
    }
}
