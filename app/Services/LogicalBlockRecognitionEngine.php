<?php

namespace App\Services;

/**
 * LogicalBlockRecognitionEngine
 *
 * Mengidentifikasi blok-blok data dinamis (Penjualan, Stok, Test Pump, Setoran,
 * Pengeluaran, Payroll) berbasis Logical Block Registry tanpa hardcoding.
 */
class LogicalBlockRecognitionEngine
{
    public function detectBlocks(array $detectedFields): array
    {
        $registry   = config('excel_templates.logical_blocks', []);
        $foundBlocks= [];

        foreach ($registry as $blockKey => $blockMeta) {
            $reqFields = $blockMeta['required_fields'] ?? [];
            $optFields = $blockMeta['optional_fields'] ?? [];

            $hasAllReq = true;
            foreach ($reqFields as $rf) {
                if (!isset($detectedFields[$rf])) {
                    $hasAllReq = false;
                    break;
                }
            }

            if ($hasAllReq && !empty($reqFields)) {
                $matchedOpt = 0;
                foreach ($optFields as $of) {
                    if (isset($detectedFields[$of])) {
                        $matchedOpt++;
                    }
                }

                $foundBlocks[$blockKey] = [
                    'key'           => $blockKey,
                    'label'         => $blockMeta['label'],
                    'matched_req'   => count($reqFields),
                    'matched_opt'   => $matchedOpt,
                ];
            }
        }

        return $foundBlocks;
    }
}
