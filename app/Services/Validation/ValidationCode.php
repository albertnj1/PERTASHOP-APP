<?php

namespace App\Services\Validation;

/**
 * ValidationCode Enum
 *
 * Semua kode error/warning standar sistem Validation Engine.
 * Menggantikan string literal "CAL001" yang tersebar di kode — lebih aman saat refactor.
 *
 * Format kode:
 *   IMP   = Import layer errors (file, sheet, shop)
 *   CAL   = Calculation / carry-forward errors
 *   PRICE = Harga BBM
 *   PAY   = Payroll formula
 *   WARN  = Informational warnings
 */
enum ValidationCode: string
{
    // ── Import Layer ─────────────────────────────────────────────────────────
    case SHEET_MISSING        = 'IMP001';
    case HEADER_MISSING       = 'IMP002';
    case SHOP_NOT_FOUND       = 'IMP003';
    case NO_DATA_ROWS         = 'IMP004';

    // ── Calculation / Business ────────────────────────────────────────────────
    case TOTALISATOR_INVALID  = 'CAL001'; // totalisator_akhir < totalisator_awal
    case VOLUME_NEGATIVE      = 'CAL002'; // volume_aktual < 0
    case CARRY_FORWARD_TOLI   = 'CAL003'; // Totalisator awal Excel ≠ DB kemarin
    case STOCK_NEGATIVE       = 'CAL004'; // stok_teoritis < 0
    case CARRY_FORWARD_STOCK  = 'CAL005'; // Stok awal Excel ≠ stik_akhir DB kemarin
    case DUPLICATE_DATE       = 'CAL006'; // Tanggal duplikat dalam satu file
    case CARRY_BROKEN_INFILE  = 'CAL007'; // Rantai totalisator/stok terputus antar baris dalam file
    case VOLUME_TOO_LARGE     = 'CAL008'; // Volume penjualan melebihi kapasitas tangki
    case INCOME_EXTREME       = 'CAL009'; // Pendapatan bersih terlalu ekstrem (threshold dari Business Rule)

    // ── Price ─────────────────────────────────────────────────────────────────
    case FUEL_PRICE_CHANGED   = 'PRICE001'; // Harga BBM di Excel ≠ harga aktif DB

    // ── Payroll Formula ───────────────────────────────────────────────────────
    case PAYROLL_FORMULA_CHANGED = 'PAY001'; // Formula payroll Excel berbeda dari snapshot

    // ── Running Balance ───────────────────────────────────────────────────────
    case RUNNING_BALANCE_NEGATIVE = 'WARN001'; // Saldo kumulatif belum disetor besar negatif

    /**
     * Mengembalikan kode string (misal: "CAL001").
     */
    public function code(): string
    {
        return $this->value;
    }

    /**
     * Label singkat deskriptif untuk kode ini.
     */
    public function label(): string
    {
        return match($this) {
            self::SHEET_MISSING           => 'Sheet Tidak Ditemukan',
            self::HEADER_MISSING          => 'Header Kolom Hilang',
            self::SHOP_NOT_FOUND          => 'Toko Tidak Ditemukan',
            self::NO_DATA_ROWS            => 'Tidak Ada Baris Data',
            self::TOTALISATOR_INVALID     => 'Totalisator Tidak Valid',
            self::VOLUME_NEGATIVE         => 'Volume Penjualan Negatif',
            self::CARRY_FORWARD_TOLI      => 'Carry Forward Totalisator Mismatch',
            self::STOCK_NEGATIVE          => 'Stok Teoritis Negatif',
            self::CARRY_FORWARD_STOCK     => 'Carry Forward Stok Mismatch',
            self::DUPLICATE_DATE          => 'Tanggal Duplikat',
            self::CARRY_BROKEN_INFILE     => 'Rantai Carry Forward Terputus',
            self::VOLUME_TOO_LARGE        => 'Volume Penjualan Terlalu Besar',
            self::INCOME_EXTREME          => 'Pendapatan Bersih Ekstrem',
            self::FUEL_PRICE_CHANGED      => 'Perubahan Harga BBM',
            self::PAYROLL_FORMULA_CHANGED => 'Formula Payroll Berubah',
            self::RUNNING_BALANCE_NEGATIVE => 'Saldo Belum Setor Negatif',
        };
    }

    /**
     * Severity default untuk kode ini (dapat di-override saat membuat ValidationMessage).
     */
    public function defaultSeverity(): Severity
    {
        return match($this) {
            self::SHEET_MISSING,
            self::HEADER_MISSING,
            self::SHOP_NOT_FOUND,
            self::NO_DATA_ROWS,
            self::TOTALISATOR_INVALID,
            self::VOLUME_NEGATIVE     => Severity::Critical,

            self::CARRY_FORWARD_TOLI,
            self::STOCK_NEGATIVE,
            self::CARRY_FORWARD_STOCK,
            self::DUPLICATE_DATE,
            self::CARRY_BROKEN_INFILE,
            self::VOLUME_TOO_LARGE,
            self::INCOME_EXTREME,
            self::FUEL_PRICE_CHANGED,
            self::PAYROLL_FORMULA_CHANGED => Severity::Warning,

            self::RUNNING_BALANCE_NEGATIVE => Severity::Info,
        };
    }
}
