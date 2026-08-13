<?php

namespace App\Services\Validation;

/**
 * Severity Enum
 *
 * Menentukan tingkat keparahan sebuah ValidationMessage.
 *
 * - Critical : Import HARUS dibatalkan (rollback). Tidak ada data yang tersimpan.
 * - Warning  : Import tetap lanjut, tetapi ada hal yang perlu diperhatikan.
 * - Info     : Informasi tambahan untuk audit/debugging, tidak mempengaruhi keputusan import.
 * - Success  : Pemeriksaan berhasil lolos — ditampilkan di UI & audit trail sebagai ✓ konfirmasi.
 */
enum Severity: string
{
    case Critical = 'critical';
    case Warning  = 'warning';
    case Info     = 'info';
    case Success  = 'success';

    public function label(): string
    {
        return match($this) {
            self::Critical => 'Critical',
            self::Warning  => 'Warning',
            self::Info     => 'Info',
            self::Success  => 'Success',
        };
    }

    public function isCritical(): bool
    {
        return $this === self::Critical;
    }

    public function isSuccess(): bool
    {
        return $this === self::Success;
    }
}

