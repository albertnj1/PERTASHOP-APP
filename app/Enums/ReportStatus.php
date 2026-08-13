<?php

namespace App\Enums;

/**
 * ReportStatus Enum / Constant Class
 *
 * Mengelola seluruh konstanta status lifecycle resmi laporan harian Pertashop.
 * Mencegah typo dan string literal mentah di seluruh aplikasi.
 *
 * Flow:
 *   DRAFT → IMPORTED → VALIDATED → APPROVED / REJECTED → LOCKED → REOPENED
 */
class ReportStatus
{
    public const DRAFT     = 'draft';
    public const IMPORTED  = 'imported';
    public const VALIDATED = 'validated';
    public const APPROVED  = 'approved';
    public const REJECTED  = 'rejected';
    public const LOCKED    = 'locked';
    public const REOPENED  = 'reopened';

    public static function labels(): array
    {
        return [
            self::DRAFT     => 'Draft',
            self::IMPORTED  => 'Imported',
            self::VALIDATED => 'Validated',
            self::APPROVED  => 'Approved',
            self::REJECTED  => 'Rejected',
            self::LOCKED    => 'Locked 🔒',
            self::REOPENED  => 'Reopened 🔓',
        ];
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? ucfirst($status);
    }

    public static function badgeClass(string $status): string
    {
        return match($status) {
            self::DRAFT     => 'badge-secondary',
            self::IMPORTED  => 'badge-info',
            self::VALIDATED => 'badge-primary',
            self::APPROVED  => 'badge-success',
            self::REJECTED  => 'badge-danger',
            self::LOCKED    => 'badge-dark',
            self::REOPENED  => 'badge-warning',
            default         => 'badge-secondary',
        };
    }
}
