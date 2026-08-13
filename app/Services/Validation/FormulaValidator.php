<?php

namespace App\Services\Validation;

/**
 * FormulaValidator — implements ValidatorInterface
 *
 * Scope: Payroll formula change proxy detection (PAY001).
 */
class FormulaValidator implements ValidatorInterface
{
    public function name(): string { return 'Formula'; }
    public function isCriticalValidator(): bool { return false; }

    public function validate(ValidationContext $context): array
    {
        if ($context->rowCount() === 0) {
            return [];
        }

        $messages = [];

        // Cek snapshot formula payroll tersimpan
        $lastLog = \App\Models\ExcelChangeLog::where('shop_id', $context->shop->id)
            ->where('change_type', 'formula_payroll')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastLog) {
            return [];
        }

        $totalRows      = $context->rowCount();
        $mismatchRows   = 0;
        $totalDeviation = 0.0;

        foreach ($context->parsedRows as $i => $row) {
            $rupiahSistem = (float) ($row['rupiah_sistem'] ?? 0);
            $rupiahExcel  = (float) ($row['rupiah_excel']  ?? 0);

            if ($rupiahSistem > 0 && $rupiahExcel > 0) {
                $deviation = abs(($rupiahSistem - $rupiahExcel) / $rupiahSistem);
                $totalDeviation += $deviation;
                if ($deviation > 0.05) {
                    $mismatchRows++;
                }
            }
        }

        if ($totalRows > 0) {
            $avgDeviation = $totalDeviation / $totalRows;

            if ($avgDeviation > 0.03 && $mismatchRows >= 3) {
                $messages[] = ValidationMessage::make(
                    ValidationCode::PAYROLL_FORMULA_CHANGED,
                    sprintf(
                        '[PAY001] Formula Payroll Berubah: Ditemukan deviasi rupiah (%d dari %d baris, rata-rata: %.1f%%). '
                        . 'Kemungkinan formula payroll atau tarif di Excel telah diperbarui.',
                        $mismatchRows,
                        $totalRows,
                        $avgDeviation * 100
                    ),
                    module:  $this->name(),
                    context: [
                        'total_rows'    => $totalRows,
                        'mismatch_rows' => $mismatchRows,
                        'avg_deviation' => round($avgDeviation * 100, 2) . '%',
                    ]
                );
            } else {
                $messages[] = ValidationMessage::success(
                    ValidationCode::PAYROLL_FORMULA_CHANGED,
                    '✓ Formula Payroll Konsisten: Tidak ada perubahan pola rupiah/volume yang signifikan.',
                    module: $this->name(),
                );
            }
        }

        return $messages;
    }
}
