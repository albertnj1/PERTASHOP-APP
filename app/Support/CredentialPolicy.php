<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Kebijakan kredensial login terpusat (email + password).
 *
 * - Email  : hanya huruf, angka, satu '@', dan titik. Contoh: dendi@pertashopkemutug.com
 * - Sandi  : min. 12 karakter, diawali huruf kapital, mengandung huruf kecil, angka & simbol.
 */
class CredentialPolicy
{
    public const EMAIL_REGEX = '/^[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)*@[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)*\.[a-zA-Z]{2,}$/';

    public const PASSWORD_REGEX = '/^[A-Z](?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s]).{11,}$/';

    public const PASSWORD_MIN = 12;

    public const EMAIL_HINT = 'Hanya huruf, angka, "@" dan titik. Format: nama@pertashop[lokasi].com';

    public const PASSWORD_HINT = 'Min. 12 karakter, huruf pertama KAPITAL, mengandung huruf kecil, angka & simbol (!@#$%^&* dll).';

    /**
     * Aturan validasi email login.
     * Regex format baru hanya diwajibkan bila email benar-benar diubah,
     * agar akun lama (format legacy) tetap bisa disimpan tanpa mengganti email.
     */
    public static function emailRules(?User $user, ?string $incoming, bool $required = true): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'max:255', 'email'];
        $rules[] = 'unique:users,email' . ($user ? ',' . $user->id : '');

        $changed = !$user || strtolower(trim((string) $incoming)) !== strtolower((string) $user->email);
        if ($changed) {
            $rules[] = 'regex:' . self::EMAIL_REGEX;
        }

        return $rules;
    }

    public static function passwordRules(bool $required = false, bool $confirmed = false): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'min:' . self::PASSWORD_MIN, 'max:255', 'regex:' . self::PASSWORD_REGEX];
        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    public static function messages(): array
    {
        return [
            'email.regex'       => 'Format email tidak valid. ' . self::EMAIL_HINT,
            'email.unique'      => 'Email ini sudah dipakai akun lain.',
            'password.min'      => 'Sandi minimal ' . self::PASSWORD_MIN . ' karakter.',
            'password.regex'    => 'Sandi tidak memenuhi syarat. ' . self::PASSWORD_HINT,
            'password.confirmed' => 'Konfirmasi sandi baru tidak cocok.',
        ];
    }

    /**
     * Generate sandi acak yang selalu lolos PASSWORD_REGEX.
     */
    public static function generatePassword(int $length = 14): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghijkmnpqrstuvwxyz';
        $digits  = '23456789';
        $symbols = '!@#$%&*?';
        $all     = $upper . $lower . $digits . $symbols;

        $pick = fn (string $set) => $set[random_int(0, strlen($set) - 1)];

        $rest = [$pick($lower), $pick($digits), $pick($symbols)];
        while (count($rest) < $length - 1) {
            $rest[] = $pick($all);
        }
        shuffle($rest);

        return $pick($upper) . implode('', $rest);
    }

    /**
     * Saran email unik: namadepan@pertashop[lokasi].com
     */
    public static function suggestEmail(string $name, ?string $location, ?int $ignoreUserId = null): string
    {
        $words  = array_values(array_filter(preg_split('/\s+/', Str::ascii(strtolower(trim($name))))));
        $clean  = fn ($s) => preg_replace('/[^a-z0-9]/', '', (string) $s);
        $first  = $clean($words[0] ?? '') ?: 'user';
        $second = $clean($words[1] ?? '');

        $domain = 'pertashop' . ($clean(Str::ascii(strtolower((string) $location))) ?: 'pusat') . '.com';

        $candidates = [$first];
        if ($second) {
            $candidates[] = $first . $second;
        }
        foreach ($candidates as $local) {
            $email = "{$local}@{$domain}";
            if (!self::emailTaken($email, $ignoreUserId)) {
                return $email;
            }
        }

        $base = end($candidates);
        $i = 2;
        while (self::emailTaken("{$base}{$i}@{$domain}", $ignoreUserId)) {
            $i++;
        }

        return "{$base}{$i}@{$domain}";
    }

    protected static function emailTaken(string $email, ?int $ignoreUserId): bool
    {
        return User::whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->exists();
    }

    /**
     * Perbarui kredensial pada tabel `users` (sumber kebenaran autentikasi).
     * Bila sandi/email berubah, remember_token di-rotate sehingga cookie "ingat saya"
     * lama tidak valid lagi; sesi aktif diputus oleh middleware `auth.session`.
     *
     * Harus dipanggil di dalam DB::transaction().
     */
    public static function applyToUser(User $user, ?string $email, ?string $password, ?string $name = null): array
    {
        return DB::transaction(function () use ($user, $email, $password, $name) {
            $changes = [];

            if ($name !== null && $name !== '' && $name !== $user->name) {
                $user->name = trim($name);
                $changes[] = 'name';
            }

            if (!empty($email) && strtolower(trim($email)) !== strtolower((string) $user->email)) {
                $user->email = strtolower(trim($email));
                $changes[] = 'email';
            }

            if (!empty($password)) {
                $user->password = Hash::make($password);
                $user->encrypted_password = Crypt::encryptString($password);
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'password_changed_at')) {
                    $user->password_changed_at = now();
                }
                $changes[] = 'password';
            }

            if (!empty($changes)) {
                $user->setRememberToken(Str::random(60));
                $user->save();
            }

            return $changes;
        });
    }

    /**
     * Sandi aktif dalam bentuk teks (hanya bila tersimpan terenkripsi AES/APP_KEY).
     * Mengembalikan null jika tidak diketahui (akun lama yang hanya punya hash bcrypt).
     */
    public static function revealPassword(?User $user): ?string
    {
        if (!$user) {
            return null;
        }
        try {
            if ($user->encrypted_password) {
                return Crypt::decryptString($user->encrypted_password);
            }
            if (Hash::check('123', $user->password)) {
                return '123';
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /** Sandi lemah / tidak memenuhi kebijakan baru. */
    public static function isWeak(?string $plain): bool
    {
        return $plain === null || !preg_match(self::PASSWORD_REGEX, $plain);
    }
}
