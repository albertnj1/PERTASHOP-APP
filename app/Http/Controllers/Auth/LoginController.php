<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Jumlah maksimal percobaan login per menit.
     */
    protected int $maxAttempts = 5;
    protected int $decayMinutes = 1;

    public function index()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        // ── Rate Limiting: Blokir setelah 5x gagal per IP+input ──
        $throttleKey = Str::lower(trim($request->input('email'))) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ])->onlyInput('email');
        }

        // ── Normalisasi input ──
        $loginInput = trim($request->input('email'));
        $loginLower = Str::lower($loginInput);
        $password   = $request->input('password'); // Jangan trim password — spasi bisa jadi bagian password
        $remember   = $request->boolean('remember');

        // ── Cari pengguna langsung pada tabel users (Single Source of Truth autentikasi) ──
        // Jika input berformat email (mengandung '@'), cari HANYA berdasarkan kolom email di users.
        // Jika bukan email, cari berdasarkan nama akun atau username di users.
        if (Str::contains($loginInput, '@')) {
            $user = User::whereRaw('LOWER(email) = ?', [$loginLower])->first();
        } else {
            $user = User::whereRaw('LOWER(name) = ?', [$loginLower])
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('users', 'username'), function ($q) use ($loginLower) {
                    $q->orWhereRaw('LOWER(username) = ?', [$loginLower]);
                })
                ->first();
        }

        // ── Verifikasi password dengan Hash::check ──
        if ($user && Hash::check($password, $user->password)) {
            // Cek apakah akun aktif
            if (!$user->is_active) {
                // Tidak menghitung rate limit untuk akun non-aktif
                return back()->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Administrator.',
                ])->onlyInput('email');
            }

            // Reset rate limiter setelah login berhasil
            RateLimiter::clear($throttleKey);

            // Login & regenerasi session untuk mencegah session fixation
            Auth::login($user, $remember);
            $request->session()->regenerate();

            // Jika hash password perlu di-rehash (misal: upgrade bcrypt cost)
            if (Hash::needsRehash($user->password)) {
                $user->update(['password' => Hash::make($password)]);
            }

            return redirect()->intended('/');
        }

        // ── Login gagal: catat dan increment rate limiter ──
        RateLimiter::hit($throttleKey, $this->decayMinutes * 60);

        Log::warning('FAILED LOGIN', [
            'input' => $loginInput,
            'ip'    => $request->ip(),
            'user_found' => $user ? 'yes' : 'no',
        ]);

        return back()->withErrors([
            'email' => 'Kredensial login tidak ditemukan atau password salah. Pastikan Email / Nama dan Password Anda benar.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}

