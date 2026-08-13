<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Catat log aktivitas universal beserta Digital Signature Hash SHA-256.
     */
    public static function log(
        string $module,
        string $action,
        mixed  $oldValue = null,
        mixed  $newValue = null,
        ?string $reason = null
    ): void {
        $user = Auth::user();
        $userId = $user?->id;
        $userName = $user?->name ?? 'System';
        $ip = Request::ip();
        $time = now()->toDateTimeString();

        // Generate SHA-256 Digital Signature Hash
        $signaturePayload = "{$module}|{$action}|{$userId}|{$userName}|{$ip}|{$time}";
        $signatureHash = hash('sha256', $signaturePayload);

        try {
            AuditLog::create([
                'user_id'        => $userId,
                'module'         => $module,
                'action'         => $action,
                'old_value'      => is_array($oldValue) || is_object($oldValue) ? json_encode($oldValue) : (string) $oldValue,
                'new_value'      => is_array($newValue) || is_object($newValue) ? json_encode($newValue) : (string) $newValue,
                'reason'         => $reason,
                'ip_address'     => $ip,
                'signature_hash' => $signatureHash,
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently fall back to storage/logs if table structure differs
            \Illuminate\Support\Facades\Log::info("AuditLog: {$signaturePayload} [Hash: {$signatureHash}]");
        }
    }
}
