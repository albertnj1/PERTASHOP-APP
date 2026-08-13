<?php

namespace App\Services\Validation;

/**
 * ValidationResultDTO
 *
 * Immutable result dari ValidationPipeline::validate().
 * Menyertakan ValidationMetrics untuk tampilan UI yang informatif.
 *
 * Contoh tampilan UI:
 *   ✔ 14 Passed   ⚠ 2 Warning   ✖ 0 Critical   Score: 95%
 *
 * Workflow Engine membaca DTO ini untuk memutuskan lanjut atau rollback:
 *   if (!$result->passed()) { rollback(); }
 */
final class ValidationResultDTO
{
    /**
     * @param ValidationMessage[] $messages Semua pesan dari semua validator (termasuk Success).
     * @param int    $score          Data Quality Score 0–100.
     * @param string $scoreRating    'excellent' | 'good' | 'fair' | 'poor'
     * @param array  $scoreBreakdown Rincian pengurangan skor per komponen.
     */
    public function __construct(
        public readonly array  $messages,
        public readonly int    $score,
        public readonly string $scoreRating,
        public readonly array  $scoreBreakdown = []
    ) {}

    // ── Message Filtering ──────────────────────────────────────────────────────

    /** @return ValidationMessage[] */
    public function errors(): array
    {
        return array_values(array_filter($this->messages, fn($m) => $m->isCritical()));
    }

    /** @return ValidationMessage[] */
    public function warnings(): array
    {
        return array_values(array_filter($this->messages, fn($m) => $m->isWarning()));
    }

    /** @return ValidationMessage[] */
    public function infos(): array
    {
        return array_values(array_filter($this->messages, fn($m) => $m->isInfo()));
    }

    /** @return ValidationMessage[] */
    public function successes(): array
    {
        return array_values(array_filter($this->messages, fn($m) => $m->isSuccess()));
    }

    // ── Gate ──────────────────────────────────────────────────────────────────

    /**
     * Import BOLEH lanjut jika tidak ada pesan Critical.
     */
    public function passed(): bool
    {
        return empty($this->errors());
    }

    // ── Convenience Strings (untuk response JSON / view) ───────────────────────

    public function errorMessages(): array
    {
        return array_map(fn($m) => "[{$m->code->value}] {$m->message}", $this->errors());
    }

    public function warningMessages(): array
    {
        return array_map(fn($m) => "[{$m->code->value}] {$m->message}", $this->warnings());
    }

    public function infoMessages(): array
    {
        return array_map(fn($m) => "[{$m->code->value}] {$m->message}", $this->infos());
    }

    // ── Validation Metrics ─────────────────────────────────────────────────────

    /**
     * Metrics ringkas untuk ditampilkan di UI.
     *
     * Contoh:
     *   ✔ 14 Passed  ⚠ 2 Warning  ✖ 0 Critical  📊 Score: 95%
     */
    public function metrics(): array
    {
        $total    = count($this->messages);
        $critical = count($this->errors());
        $warning  = count($this->warnings());
        $info     = count($this->infos());
        $success  = count($this->successes());

        return [
            'total'    => $total,
            'passed'   => $success,
            'warning'  => $warning,
            'info'     => $info,
            'critical' => $critical,
            'score'    => $this->score,
            'rating'   => $this->scoreRating,
            // Display-ready strings
            'summary'  => "✔ {$success} Passed  ⚠ {$warning} Warning  ✖ {$critical} Critical  Score: {$this->score}%",
        ];
    }

    /**
     * Representasi lengkap untuk JSON response / log / audit.
     */
    public function toArray(): array
    {
        return [
            'passed'          => $this->passed(),
            'score'           => $this->score,
            'score_rating'    => $this->scoreRating,
            'score_breakdown' => $this->scoreBreakdown,
            'metrics'         => $this->metrics(),
            'errors'          => array_map(fn($m) => $m->toArray(), $this->errors()),
            'warnings'        => array_map(fn($m) => $m->toArray(), $this->warnings()),
            'infos'           => array_map(fn($m) => $m->toArray(), $this->infos()),
            'successes'       => array_map(fn($m) => $m->toArray(), $this->successes()),
            'total_messages'  => count($this->messages),
        ];
    }

    public function scoreLabel(): string
    {
        return match($this->scoreRating) {
            'excellent' => '🟢 Excellent',
            'good'      => '🟡 Good',
            'fair'      => '🟠 Fair',
            default     => '🔴 Poor',
        };
    }
}
