<?php

namespace App\Services\Validation;

/**
 * ValidatorInterface
 *
 * Contract yang harus diimplementasikan oleh semua validator dalam pipeline.
 * ValidationPipeline hanya mengenal interface ini — tidak tahu implementasi spesifik.
 *
 * Ini mengikuti Open/Closed Principle:
 *   - Pipeline TERTUTUP untuk modifikasi
 *   - Pipeline TERBUKA untuk perluasan (tambah validator baru tanpa ubah Pipeline)
 *
 * Contoh penggunaan:
 *
 *   class MyCustomValidator implements ValidatorInterface
 *   {
 *       public function validate(ValidationContext $context): array
 *       {
 *           $messages = [];
 *           // ... logic validasi ...
 *           return $messages;
 *       }
 *
 *       public function name(): string { return 'MyCustom'; }
 *       public function isCriticalValidator(): bool { return false; }
 *   }
 *
 * Untuk mendaftarkan validator baru ke pipeline, cukup tambahkan ke array
 * $validators di ValidationPipeline::__construct() atau di AppServiceProvider.
 *
 * @return ValidationMessage[]
 */
interface ValidatorInterface
{
    /**
     * Jalankan validasi dan kembalikan array ValidationMessage.
     * Kembalikan array kosong [] jika tidak ada masalah.
     *
     * @param ValidationContext $context Semua data yang dibutuhkan validator.
     * @return ValidationMessage[]
     */
    public function validate(ValidationContext $context): array;

    /**
     * Nama modul validator (digunakan di ValidationMessage::module dan log).
     * Contoh: 'Input', 'CarryForward', 'Business', 'Price', 'Formula'
     */
    public function name(): string;

    /**
     * Apakah validator ini dianggap Critical-gating?
     * Jika true dan ada pesan Critical → Pipeline berhenti (fail-fast).
     * Jika false → Pipeline terus meski ada Critical dari validator ini.
     */
    public function isCriticalValidator(): bool;
}
