<?php

namespace App\Services\Validation;

/**
 * ValidationMessage
 *
 * Satu pesan hasil validasi. Immutable setelah dibuat.
 * Digunakan oleh semua validator untuk melaporkan setiap temuan.
 *
 * Field tambahan `module`, `row`, `field` memudahkan UI menampilkan
 * tepat di baris mana masalah terjadi — jauh lebih berguna dari sekadar pesan teks.
 *
 * Contoh lengkap:
 * {
 *   code:     "CAL003",
 *   severity: "warning",
 *   module:   "CarryForward",
 *   row:      1,
 *   field:    "totalisator_awal",
 *   message:  "Carry Forward mismatch: Excel 119500 ≠ DB 120000",
 *   context:  { excel: 119500, db: 120000, selisih: 500 }
 * }
 */
final class ValidationMessage
{
    public readonly ValidationCode $code;
    public readonly Severity       $severity;
    public readonly string         $module;
    public readonly string         $message;
    public readonly array          $context;

    /** Nomor baris dalam parsedRows yang bermasalah (null = berlaku global). */
    public readonly ?int    $row;

    /** Nama field/kolom yang bermasalah (null = berlaku pada baris/global). */
    public readonly ?string $field;

    private function __construct(
        ValidationCode $code,
        Severity       $severity,
        string         $module,
        string         $message,
        array          $context = [],
        ?int           $row     = null,
        ?string        $field   = null,
    ) {
        $this->code     = $code;
        $this->severity = $severity;
        $this->module   = $module;
        $this->message  = $message;
        $this->context  = $context;
        $this->row      = $row;
        $this->field    = $field;
    }

    /**
     * Factory — severity menggunakan defaultSeverity dari ValidationCode jika tidak diisi.
     *
     * @param ValidationCode $code
     * @param string         $message   Pesan yang mudah dibaca operator.
     * @param string         $module    Nama modul validator (misal: 'CarryForward').
     * @param Severity|null  $severity  Override severity (null = gunakan default ValidationCode).
     * @param array          $context   Data konteks untuk debugging/UI.
     * @param int|null       $row       Index baris dalam parsedRows (0-based), null = global.
     * @param string|null    $field     Nama field yang bermasalah, null = tidak spesifik.
     */
    public static function make(
        ValidationCode $code,
        string         $message,
        string         $module    = '',
        ?Severity      $severity  = null,
        array          $context   = [],
        ?int           $row       = null,
        ?string        $field     = null,
    ): self {
        return new self(
            $code,
            $severity ?? $code->defaultSeverity(),
            $module,
            $message,
            $context,
            $row,
            $field,
        );
    }

    /**
     * Factory untuk pesan Success (audit trail positif).
     * Contoh: "✓ Carry Forward Valid — totalisator awal cocok dengan DB"
     */
    public static function success(
        ValidationCode $code,
        string         $message,
        string         $module  = '',
        array          $context = [],
    ): self {
        return new self($code, Severity::Success, $module, $message, $context);
    }

    // ── Type Checks ────────────────────────────────────────────────────────────

    public function isCritical(): bool { return $this->severity === Severity::Critical; }
    public function isWarning(): bool  { return $this->severity === Severity::Warning; }
    public function isInfo(): bool     { return $this->severity === Severity::Info; }
    public function isSuccess(): bool  { return $this->severity === Severity::Success; }

    /**
     * Representasi array untuk JSON response / audit log / UI.
     */
    public function toArray(): array
    {
        return [
            'code'     => $this->code->value,
            'label'    => $this->code->label(),
            'severity' => $this->severity->value,
            'module'   => $this->module,
            'row'      => $this->row,
            'field'    => $this->field,
            'message'  => $this->message,
            'context'  => $this->context,
        ];
    }
}
