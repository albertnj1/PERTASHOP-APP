<?php

namespace App\Services\Validation;

use Illuminate\Support\Carbon;

/**
 * CarryForwardValidator
 *
 * Implements ValidatorInterface — menerima ValidationContext, bukan parameter terpisah.
 *
 * Dua level validasi:
 *
 * Level 1 — DB vs Baris Pertama Excel (CAL003, CAL005)
 *   context->previousReport (sudah di-preload oleh Pipeline) vs parsedRows[0]
 *
 * Level 2 — Antar-Baris dalam File (CAL007)
 *   Setiap baris harus berkesinambungan: baris[N].totalisator_akhir == baris[N+1].totalisator_awal
 *
 * Menghasilkan Success message jika semua lolos:
 *   ✓ Carry Forward Totalisator Valid
 *   ✓ Rantai Carry Forward Lengkap
 */
class CarryForwardValidator implements ValidatorInterface
{
    public function name(): string { return 'CarryForward'; }
    public function isCriticalValidator(): bool { return false; }

    private float $toliTolerance;
    private float $stokTolerance;

    public function __construct(float $toliTolerance = 1.0, float $stokTolerance = 50.0)
    {
        $this->toliTolerance = $toliTolerance;
        $this->stokTolerance = $stokTolerance;
    }

    public function validate(ValidationContext $context): array
    {
        if ($context->rowCount() === 0) {
            return [];
        }

        $messages   = [];
        $firstRow   = $context->firstRow();
        $firstDate  = Carbon::parse($firstRow['tanggal']);

        // ── Level 1: DB vs Baris Pertama Excel ─────────────────────────────────
        if ($context->previousReport) {
            $dbToli    = (float) $context->previousReport->totalisator_akhir;
            $excelToli = (float) ($firstRow['totalisator_awal'] ?? 0);
            $toliSelisih = abs($dbToli - $excelToli);

            if ($toliSelisih > $this->toliTolerance) {
                $messages[] = ValidationMessage::make(
                    ValidationCode::CARRY_FORWARD_TOLI,
                    sprintf(
                        '[CAL003] Carry Forward Totalisator: Excel %.3f L ≠ DB %.3f L (selisih %.3f L). '
                        . 'Kemungkinan ada hari yang terlewat atau data kemarin tidak akurat.',
                        $excelToli, $dbToli, $toliSelisih
                    ),
                    module:  $this->name(),
                    row:     0,
                    field:   'totalisator_awal',
                    context: [
                        'tanggal'              => $firstDate->format('d M Y'),
                        'db_totalisator_akhir'  => $dbToli,
                        'excel_totalisator_awal' => $excelToli,
                        'selisih_liter'         => $toliSelisih,
                        'db_tanggal'           => Carbon::parse($context->previousReport->created_at)->format('d M Y'),
                    ]
                );
            } else {
                $messages[] = ValidationMessage::success(
                    ValidationCode::CARRY_FORWARD_TOLI,
                    "✓ Carry Forward Totalisator Valid: Excel {$excelToli} ≈ DB {$dbToli} (selisih {$toliSelisih} L ≤ toleransi).",
                    module: $this->name(),
                );
            }

            // Stok Awal
            $dbStik    = (float) $context->previousReport->stik_akhir;
            $excelStik = (float) ($firstRow['stik_awal'] ?? 0);
            $stikSelisih = abs($dbStik - $excelStik);

            if ($excelStik > 0 && $stikSelisih > $this->stokTolerance) {
                $messages[] = ValidationMessage::make(
                    ValidationCode::CARRY_FORWARD_STOCK,
                    sprintf(
                        '[CAL005] Carry Forward Stok: Excel stik %.2f cm ≠ DB stik %.2f cm (selisih %.2f cm).',
                        $excelStik, $dbStik, $stikSelisih
                    ),
                    module:  $this->name(),
                    row:     0,
                    field:   'stik_awal',
                    context: ['db_stik_akhir' => $dbStik, 'excel_stik_awal' => $excelStik, 'selisih_cm' => $stikSelisih]
                );
            } elseif ($excelStik > 0) {
                $messages[] = ValidationMessage::success(
                    ValidationCode::CARRY_FORWARD_STOCK,
                    "✓ Carry Forward Stok Valid: Excel stik {$excelStik} cm ≈ DB stik {$dbStik} cm.",
                    module: $this->name(),
                );
            }
        }
        // Laporan pertama → skip Level 1, tidak ada warning

        // ── Level 2: Rantai Antar-Baris dalam File (CAL007) ────────────────────
        $parsedRows  = $context->parsedRows;
        $brokenLinks = [];

        for ($i = 0; $i < count($parsedRows) - 1; $i++) {
            $current     = $parsedRows[$i];
            $next        = $parsedRows[$i + 1];
            $currentEnd  = (float) ($current['totalisator_akhir'] ?? 0);
            $nextStart   = (float) ($next['totalisator_awal']     ?? 0);
            $linkSelisih = abs($currentEnd - $nextStart);

            if ($linkSelisih > $this->toliTolerance) {
                $brokenLinks[] = [
                    'row'       => $i + 1,
                    'desc'      => sprintf(
                        '%s→%s (akhir: %.3f, awal: %.3f, selisih: %.3f L)',
                        Carbon::parse($current['tanggal'])->format('d/m'),
                        Carbon::parse($next['tanggal'])->format('d/m'),
                        $currentEnd, $nextStart, $linkSelisih
                    ),
                ];
            }
        }

        if (!empty($brokenLinks)) {
            $messages[] = ValidationMessage::make(
                ValidationCode::CARRY_BROKEN_INFILE,
                sprintf(
                    '[CAL007] Rantai Carry Forward Terputus: %d putus rantai dalam file. '
                    . 'Ada hari hilang, urutan salah, atau copy-paste salah. Detail: %s.',
                    count($brokenLinks),
                    implode('; ', array_map(fn($b) => $b['desc'], array_slice($brokenLinks, 0, 3)))
                    . (count($brokenLinks) > 3 ? '...' : '')
                ),
                module:  $this->name(),
                context: ['jumlah_putus' => count($brokenLinks), 'detail' => $brokenLinks]
            );
        } elseif (count($parsedRows) > 1) {
            $messages[] = ValidationMessage::success(
                ValidationCode::CARRY_BROKEN_INFILE,
                '✓ Rantai Carry Forward Lengkap: Semua ' . (count($parsedRows) - 1) . ' sambungan antar hari valid.',
                module: $this->name(),
            );
        }

        return $messages;
    }
}
