<?php

namespace App\Services;

/**
 * ColumnAutoDetector (v2.4.0)
 *
 * Mendeteksi mapping kolom Excel ke field sistem secara otomatis
 * menggunakan kamus terpusat di config('field_aliases').
 */
class ColumnAutoDetector
{
    public const THRESHOLD_HIGH = 0.75;
    public const THRESHOLD_LOW  = 0.50;

    public function autoDetect(array $headers, array $savedMapping = []): array
    {
        $fieldAliases = config('field_aliases', []);

        $mapping    = [];
        $lowConf    = [];
        $notFound   = [];
        $confidence = [];
        $source     = [];

        foreach ($fieldAliases as $fieldKey => $aliases) {
            $bestCol   = null;
            $bestScore = 0.0;

            foreach ($headers as $colLetter => $headerText) {
                $score = $this->scoreMatch(strtolower(trim($headerText)), $aliases);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestCol   = $colLetter;
                }
            }

            if ($bestScore >= self::THRESHOLD_HIGH) {
                $mapping[$fieldKey]    = $bestCol;
                $confidence[$fieldKey] = round($bestScore, 3);
                $source[$fieldKey]     = 'auto';
            } elseif ($bestScore >= self::THRESHOLD_LOW) {
                $lowConf[$fieldKey]    = $bestCol;
                $confidence[$fieldKey] = round($bestScore, 3);
                $source[$fieldKey]     = 'auto_low';
            } else {
                if (!empty($savedMapping[$fieldKey])) {
                    $mapping[$fieldKey]    = $savedMapping[$fieldKey];
                    $confidence[$fieldKey] = 0.0;
                    $source[$fieldKey]     = 'saved';
                } else {
                    $notFound[] = $fieldKey;
                }
            }
        }

        return [
            'mapping'    => $mapping,
            'low_conf'   => $lowConf,
            'not_found'  => $notFound,
            'confidence' => $confidence,
            'source'     => $source,
        ];
    }

    private function scoreMatch(string $headerLower, array $aliases): float
    {
        $bestScore = 0.0;

        foreach ($aliases as $alias) {
            $aliasLower = strtolower($alias);

            if ($headerLower === $aliasLower) {
                return 1.0;
            }

            if (preg_match('/\b' . preg_quote($aliasLower, '/') . '\b/', $headerLower)) {
                $bestScore = max($bestScore, 0.92);
            }

            if (str_contains($headerLower, $aliasLower)) {
                $score = strlen($aliasLower) / max(strlen($headerLower), 1);
                $score = max($score, 0.85);
                $bestScore = max($bestScore, $score);
            }
        }

        return $bestScore;
    }
}
