<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * ImportSessionManager
 *
 * Mengelola state sesi impor (import_session_id) dengan masa berlaku 24 jam.
 * Mendukung fitur Resume Import jika koneksi/browser terputus.
 */
class ImportSessionManager
{
    private int $ttl;

    public function __construct()
    {
        $this->ttl = config('excel_templates.session.ttl', 86400);
    }

    public function createSession(array $sessionData): string
    {
        $sessionId = 'imp_sess_' . Str::random(24);
        $sessionData['id']          = $sessionId;
        $sessionData['created_at']  = now()->toIso8601String();
        $sessionData['expires_at']  = now()->addSeconds($this->ttl)->toIso8601String();

        Cache::put('import_session:' . $sessionId, $sessionData, $this->ttl);

        return $sessionId;
    }

    public function getSession(string $sessionId): ?array
    {
        return Cache::get('import_session:' . $sessionId);
    }

    public function updateSession(string $sessionId, array $newData): bool
    {
        $existing = $this->getSession($sessionId);
        if (!$existing) return false;

        $updated = array_merge($existing, $newData);
        $updated['updated_at'] = now()->toIso8601String();

        Cache::put('import_session:' . $sessionId, $updated, $this->ttl);

        return true;
    }

    public function forgetSession(string $sessionId): bool
    {
        return Cache::forget('import_session:' . $sessionId);
    }
}
