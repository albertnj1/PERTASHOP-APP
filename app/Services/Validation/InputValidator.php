<?php

namespace App\Services\Validation;

use App\Models\Shop;

/**
 * InputValidator
 *
 * Validator pertama dalam pipeline.
 * Mengecek bahwa input dasar (shop, kolom kunci, data) tersedia dan valid.
 *
 * Menghasilkan pesan Success untuk setiap pemeriksaan yang lolos
 * → ditampilkan di UI sebagai "✓ Shop Ditemukan", "✓ Kolom Kunci Lengkap", dll.
 *
 * Error codes: IMP001–IMP004
 */
class InputValidator implements ValidatorInterface
{
    public function name(): string { return 'Input'; }
    public function isCriticalValidator(): bool { return true; }

    public function validate(ValidationContext $context): array
    {
        $messages = [];

        // IMP003 — Shop valid
        if (!$context->shop || !$context->shop->id) {
            $messages[] = ValidationMessage::make(
                ValidationCode::SHOP_NOT_FOUND,
                'Toko tidak ditemukan di database. Pastikan toko sudah terdaftar sebelum melakukan upload.',
                module: $this->name(),
                context: ['shop_id' => $context->shop?->id]
            );
            return $messages;
        }
        $messages[] = ValidationMessage::success(
            ValidationCode::SHOP_NOT_FOUND,
            "✓ Toko ditemukan: {$context->shop->kode} — {$context->shop->nama}",
            module: $this->name(),
        );

        // IMP002 — 4 Kolom Kunci wajib terpetakan
        $requiredFields = [
            'totalisator_akhir' => 'Totalisator Akhir',
            'test_pump'         => 'Test Pump (Volume)',
            'penerimaan'        => 'Terima BBM (Volume)',
            'stik_akhir'        => 'Stik Akhir Tangki',
        ];
        $missingFields = [];
        foreach ($requiredFields as $field => $label) {
            if (empty($context->mappingConfig[$field])) {
                $missingFields[$field] = $label;
            }
        }
        if (!empty($missingFields)) {
            $messages[] = ValidationMessage::make(
                ValidationCode::HEADER_MISSING,
                'Kolom kunci berikut belum dipetakan: ' . implode(', ', $missingFields) . '.',
                module: $this->name(),
                context: ['missing_fields' => array_keys($missingFields)]
            );
        } else {
            $messages[] = ValidationMessage::success(
                ValidationCode::HEADER_MISSING,
                '✓ Semua 4 kolom kunci sudah dipetakan.',
                module: $this->name(),
            );
        }

        // IMP004 — Minimal 1 baris data
        if ($context->rowCount() === 0) {
            $messages[] = ValidationMessage::make(
                ValidationCode::NO_DATA_ROWS,
                'Tidak ada baris data yang berhasil dibaca dari sheet'
                . ($context->sheetName ? " '{$context->sheetName}'" : '') . '.',
                module: $this->name(),
                context: ['sheet_name' => $context->sheetName]
            );
        } else {
            $messages[] = ValidationMessage::success(
                ValidationCode::NO_DATA_ROWS,
                "✓ {$context->rowCount()} baris data berhasil dibaca.",
                module: $this->name(),
            );
        }

        return $messages;
    }
}
