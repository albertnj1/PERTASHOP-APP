<?php

namespace App\Services;

/**
 * DynamicFieldMapperService
 *
 * Memetakan header kolom Excel ke field standar sistem secara dinamis
 * menggunakan config/field_aliases.php dan spatial/fuzzy score.
 */
class DynamicFieldMapperService
{
    public function mapHeadersToFields(array $headers, array &$decisionLog = []): array
    {
        $fieldAliases = config('field_aliases', []);

        $mapping    = [];
        $confidence = [];

        foreach ($fieldAliases as $fieldKey => $aliases) {
            $bestCol   = null;
            $bestScore = 0.0;

            foreach ($headers as $colLetter => $headerText) {
                $headerLower = strtolower(trim($headerText));
                if ($headerLower === '') continue;

                foreach ($aliases as $alias) {
                    $aliasLower = strtolower($alias);

                    if ($headerLower === $aliasLower) {
                        $bestScore = 1.0;
                        $bestCol   = $colLetter;
                        break 2;
                    }

                    if (str_contains($headerLower, $aliasLower)) {
                        $score = max(0.85, strlen($aliasLower) / max(strlen($headerLower), 1));
                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $bestCol   = $colLetter;
                        }
                    }
                }
            }

            if ($bestCol && $bestScore >= 0.70) {
                $mapping[$fieldKey]    = $bestCol;
                $confidence[$fieldKey] = round($bestScore, 2);
                $decisionLog[]         = "Field Mapped: '{$fieldKey}' -> Column '{$bestCol}' (Confidence: " . round($bestScore * 100) . "%)";
            }
        }

        return [
            'mapping'    => $mapping,
            'confidence' => $confidence,
        ];
    }
}
