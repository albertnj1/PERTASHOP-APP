<?php

namespace App\Services\Validation;

use Illuminate\Support\Carbon;

/**
 * PriceValidator — implements ValidatorInterface
 *
 * Menggunakan context->activePrice (di-preload oleh Pipeline) — tidak query DB sendiri.
 */
class PriceValidator implements ValidatorInterface
{
    public function name(): string { return 'Price'; }
    public function isCriticalValidator(): bool { return false; }

    private float $priceTolerance;
    private float $minValidPrice;
    private float $maxValidPrice;

    public function __construct(
        float $priceTolerance = 100.0,
        float $minValidPrice  = 5000.0,
        float $maxValidPrice  = 30000.0
    ) {
        $this->priceTolerance = $priceTolerance;
        $this->minValidPrice  = $minValidPrice;
        $this->maxValidPrice  = $maxValidPrice;
    }

    public function validate(ValidationContext $context): array
    {
        if ($context->rowCount() === 0 || !$context->activePrice) {
            return [];
        }

        $messages = [];
        $dbHarga  = (float) $context->activePrice->harga_jual;
        $detected = false;

        foreach ($context->parsedRows as $i => $row) {
            $volSistem    = (float) ($row['vol_sistem']    ?? 0);
            $rupiahExcel  = (float) ($row['rupiah_excel']  ?? 0);
            $rupiahSistem = (float) ($row['rupiah_sistem'] ?? 0);

            if ($volSistem <= 0) continue;

            // Context Check: estimasi harga per liter dari Excel (bukan angka mentah formula)
            $estimasiHarga = $rupiahExcel > 0
                ? round($rupiahExcel  / $volSistem)
                : round($rupiahSistem / $volSistem);

            // Rentang masuk akal untuk harga BBM Pertashop
            if ($estimasiHarga < $this->minValidPrice || $estimasiHarga > $this->maxValidPrice) {
                continue; // Skip — estimasi tidak masuk akal, hindari false positive
            }

            $selisih = abs($dbHarga - $estimasiHarga);
            if ($selisih > $this->priceTolerance) {
                $tanggal = Carbon::parse($row['tanggal'])->format('d M Y');
                $messages[] = ValidationMessage::make(
                    ValidationCode::FUEL_PRICE_CHANGED,
                    sprintf(
                        '[PRICE001] %s: Estimasi harga Excel (Rp %s/L) ≠ harga aktif DB (Rp %s/L). '
                        . 'Selisih Rp %s/L. Jika harga berubah, perbarui di menu Harga BBM.',
                        $tanggal,
                        number_format($estimasiHarga, 0, ',', '.'),
                        number_format($dbHarga, 0, ',', '.'),
                        number_format($selisih, 0, ',', '.')
                    ),
                    module:  $this->name(),
                    row:     $i,
                    field:   'rupiah_excel',
                    context: ['db_harga' => $dbHarga, 'excel_estimasi' => $estimasiHarga, 'selisih' => $selisih]
                );
                $detected = true;
                break; // Satu PRICE001 per upload sudah cukup
            }
        }

        if (!$detected) {
            $messages[] = ValidationMessage::success(
                ValidationCode::FUEL_PRICE_CHANGED,
                "✓ Harga BBM Cocok: Estimasi dari Excel ≈ harga DB aktif Rp " . number_format($dbHarga, 0, ',', '.') . "/L.",
                module: $this->name(),
            );
        }

        return $messages;
    }
}
