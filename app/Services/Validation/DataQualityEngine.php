<?php

namespace App\Services\Validation;

/**
 * DataQualityEngine
 *
 * Menghitung Data Quality Score (0–100) dari kumpulan ValidationMessage.
 *
 * Bobot komponen dibaca dari config('validation.quality_weights')
 * agar dapat dikonfigurasi tanpa deploy ulang.
 *
 * Default bobot (total = 100):
 *   input         = 10
 *   carry_forward = 20
 *   totalisator   = 20
 *   stock_volume  = 20
 *   formula       = 20
 *   price         = 10
 *
 * Rating:
 *   90–100 = 🟢 Excellent
 *   70–89  = 🟡 Good
 *   50–69  = 🟠 Fair
 *   0–49   = 🔴 Poor
 */
class DataQualityEngine
{
    private array $weights;

    public function __construct(array $customWeights = [])
    {
        // Coba baca dari config, fallback ke default
        $configWeights = config('validation.quality_weights', []);
        $this->weights = array_merge([
            'input'         => 10,
            'carry_forward' => 20,
            'totalisator'   => 20,
            'stock_volume'  => 20,
            'formula'       => 20,
            'price'         => 10,
        ], $configWeights, $customWeights);
    }

    /**
     * Hitung score dari daftar ValidationMessage yang terkumpul.
     *
     * @param ValidationMessage[] $messages
     * @return array ['score' => int, 'rating' => string, 'breakdown' => array]
     */
    public function calculate(array $messages): array
    {
        $codes = array_map(fn($m) => $m->code, $messages);

        $deductions  = [];
        $totalScore  = 100;

        // ── Input Layer ──────────────────────────────────────────────────────
        $inputCodes = [
            ValidationCode::SHEET_MISSING,
            ValidationCode::HEADER_MISSING,
            ValidationCode::SHOP_NOT_FOUND,
            ValidationCode::NO_DATA_ROWS,
        ];
        if ($this->hasAny($codes, $inputCodes)) {
            $deduct = $this->weights['input'];
            $totalScore -= $deduct;
            $deductions['input'] = [
                'bobot'    => $this->weights['input'],
                'dikurangi' => $deduct,
                'alasan'   => 'Masalah input dasar ditemukan',
            ];
        }

        // ── Carry Forward ────────────────────────────────────────────────────
        $carryForwardCodes = [
            ValidationCode::CARRY_FORWARD_TOLI,
            ValidationCode::CARRY_FORWARD_STOCK,
            ValidationCode::CARRY_BROKEN_INFILE,
        ];
        if ($this->hasAny($codes, $carryForwardCodes)) {
            $matchedCount = $this->countMatches($codes, $carryForwardCodes);
            // 1 masalah carry forward = 50% bobot, 2+ = 100% bobot
            $deduct = $matchedCount >= 2
                ? $this->weights['carry_forward']
                : (int) round($this->weights['carry_forward'] * 0.5);
            $totalScore -= $deduct;
            $deductions['carry_forward'] = [
                'bobot'    => $this->weights['carry_forward'],
                'dikurangi' => $deduct,
                'alasan'   => "Carry forward mismatch ({$matchedCount} masalah)",
            ];
        }

        // ── Totalisator ──────────────────────────────────────────────────────
        if ($this->hasAny($codes, [ValidationCode::TOTALISATOR_INVALID])) {
            $deduct = $this->weights['totalisator'];
            $totalScore -= $deduct;
            $deductions['totalisator'] = [
                'bobot'    => $this->weights['totalisator'],
                'dikurangi' => $deduct,
                'alasan'   => 'Totalisator tidak valid',
            ];
        }

        // ── Stock & Volume ───────────────────────────────────────────────────
        $stockVolCodes = [
            ValidationCode::VOLUME_NEGATIVE,
            ValidationCode::STOCK_NEGATIVE,
            ValidationCode::VOLUME_TOO_LARGE,
            ValidationCode::INCOME_EXTREME,
        ];
        if ($this->hasAny($codes, $stockVolCodes)) {
            $matchedCount = $this->countMatches($codes, $stockVolCodes);
            $deduct = $matchedCount >= 2
                ? $this->weights['stock_volume']
                : (int) round($this->weights['stock_volume'] * 0.5);
            $totalScore -= $deduct;
            $deductions['stock_volume'] = [
                'bobot'    => $this->weights['stock_volume'],
                'dikurangi' => $deduct,
                'alasan'   => "Masalah volume/stok ({$matchedCount} masalah)",
            ];
        }

        // ── Formula ──────────────────────────────────────────────────────────
        if ($this->hasAny($codes, [ValidationCode::PAYROLL_FORMULA_CHANGED])) {
            $deduct = $this->weights['formula'];
            $totalScore -= $deduct;
            $deductions['formula'] = [
                'bobot'    => $this->weights['formula'],
                'dikurangi' => $deduct,
                'alasan'   => 'Formula payroll berubah',
            ];
        }

        // ── Price ────────────────────────────────────────────────────────────
        if ($this->hasAny($codes, [ValidationCode::FUEL_PRICE_CHANGED])) {
            $deduct = $this->weights['price'];
            $totalScore -= $deduct;
            $deductions['price'] = [
                'bobot'    => $this->weights['price'],
                'dikurangi' => $deduct,
                'alasan'   => 'Harga BBM berubah',
            ];
        }

        $score  = max(0, $totalScore);
        $rating = $this->rating($score);

        return [
            'score'     => $score,
            'rating'    => $rating,
            'breakdown' => $deductions,
            'weights'   => $this->weights,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function hasAny(array $foundCodes, array $targetCodes): bool
    {
        foreach ($targetCodes as $target) {
            if (in_array($target, $foundCodes, strict: true)) {
                return true;
            }
        }
        return false;
    }

    private function countMatches(array $foundCodes, array $targetCodes): int
    {
        $count = 0;
        foreach ($targetCodes as $target) {
            if (in_array($target, $foundCodes, strict: true)) {
                $count++;
            }
        }
        return $count;
    }

    private function rating(int $score): string
    {
        return match(true) {
            $score >= 90 => 'excellent',
            $score >= 70 => 'good',
            $score >= 50 => 'fair',
            default      => 'poor',
        };
    }
}
