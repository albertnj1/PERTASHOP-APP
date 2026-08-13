<?php

namespace App\Services\Validation;

use App\Models\Shop;
use App\Models\DailyReport;
use App\Models\Price;

/**
 * ValidationContext
 *
 * Satu object tunggal yang membawa SEMUA informasi yang dibutuhkan oleh validator.
 * Menggantikan parameter $parsedRows, $shop, $mappingConfig yang dikirim terpisah.
 *
 * Dibuat sekali oleh ValidationPipeline dan dibagikan ke semua validator.
 * Immutable setelah konstruksi — validator hanya membaca, tidak menulis.
 *
 * Keuntungan:
 *   - Signature ValidatorInterface::validate(ValidationContext) selalu pendek dan konsisten
 *   - Menambah data baru (misal: $shiftData, $businessRules) tidak mengubah interface
 *   - Pipeline tinggal buat satu context → kirim ke semua validator
 *
 * Contoh penggunaan di validator:
 *   $context->shop->id
 *   $context->parsedRows[0]['totalisator_awal']
 *   $context->previousReport?->totalisator_akhir
 *   $context->activePrice?->harga_jual
 */
final class ValidationContext
{
    /**
     * @param Shop        $shop          Toko yang sedang diimpor.
     * @param array       $parsedRows    Baris data dari Pass 1 (sudah terurut asc tanggal).
     * @param array       $mappingConfig Konfigurasi kolom aktif.
     * @param string|null $sheetName     Nama sheet yang diproses.
     * @param DailyReport|null $previousReport Record terakhir DB untuk toko ini sebelum periode ini.
     * @param Price|null  $activePrice   Harga BBM aktif dari DB.
     * @param array       $businessConfig Threshold bisnis dari config('validation.business').
     * @param array       $uploadInfo    Metadata upload (file_name, uploaded_by, dll).
     */
    public function __construct(
        public readonly Shop         $shop,
        public readonly array        $parsedRows,
        public readonly array        $mappingConfig,
        public readonly ?string      $sheetName     = null,
        public readonly ?DailyReport $previousReport = null,
        public readonly ?Price       $activePrice    = null,
        public readonly array        $businessConfig = [],
        public readonly array        $uploadInfo     = [],
    ) {}

    /**
     * Apakah ini laporan pertama untuk toko ini (tidak ada data historis)?
     */
    public function isFirstReport(): bool
    {
        return $this->previousReport === null;
    }

    /**
     * Jumlah baris data yang di-parse.
     */
    public function rowCount(): int
    {
        return count($this->parsedRows);
    }

    /**
     * Baris data pertama (hari pertama dalam file).
     */
    public function firstRow(): ?array
    {
        return $this->parsedRows[0] ?? null;
    }
}
