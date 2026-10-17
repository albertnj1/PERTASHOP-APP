<?php

namespace App\Http\Controllers;

use App\Support\CredentialPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Halaman pengaturan akun khusus Super Admin (nama, email login, sandi).
 * Setiap perubahan wajib dikonfirmasi dengan sandi saat ini.
 */
class AccountSettingController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return view('account.settings', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => CredentialPolicy::emailRules($user, $request->input('email')),
            'current_password' => ['required', 'string'],
            'password'         => CredentialPolicy::passwordRules(false, true),
        ], CredentialPolicy::messages() + [
            'current_password.required' => 'Masukkan sandi saat ini untuk konfirmasi.',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Sandi saat ini salah.'])
                ->withInput($request->except(['current_password', 'password', 'password_confirmation']));
        }

        $changes = DB::transaction(function () use ($user, $validated) {
            $changes = CredentialPolicy::applyToUser(
                $user,
                $validated['email'],
                $validated['password'] ?? null,
                $validated['name']
            );

            // Sinkronkan profil admin bila ada relasi
            if ($user->admin && $user->admin->isFillable('email')) {
                $user->admin->update(['email' => $user->email]);
            }

            return $changes;
        });

        // Refresh sesi Super Admin agar tetap login dengan data user terbaru
        Auth::login($user);

        if (in_array('password', $changes, true)) {
            // Pertahankan sesi perangkat ini; sesi lain diputus oleh middleware auth.session.
            $request->session()->put('password_hash_' . Auth::getDefaultDriver(), $user->getAuthPassword());
            $request->session()->regenerate();
        }

        $msg = $changes
            ? 'Pengaturan akun berhasil disimpan (' . implode(' & ', $changes) . ' diperbarui). Sesi di perangkat lain otomatis keluar.'
            : 'Profil berhasil disimpan.';

        return redirect()->route('account.settings')->with('success', $msg);
    }
}
