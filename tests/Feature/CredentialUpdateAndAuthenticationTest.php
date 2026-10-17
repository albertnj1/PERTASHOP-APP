<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CredentialPolicy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CredentialUpdateAndAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_credential_update_and_strict_login_behavior(): void
    {
        $oldEmail = 'tester.lama@pertashop.com';
        $oldPassword = 'PasswordLama!123';
        $newEmail = 'tester.baru@pertashop.com';
        $newPassword = 'PasswordBaru!456';

        $user = User::create([
            'name'      => 'Tester User',
            'email'     => $oldEmail,
            'password'  => Hash::make($oldPassword),
            'role'      => 'operator',
            'is_active' => true,
        ]);

        // 1. Verifikasi login awal berhasil
        $response = $this->post(route('authenticate'), [
            'email'    => $oldEmail,
            'password' => $oldPassword,
        ]);
        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        // 2. Terapkan pembaruan kredensial melalui CredentialPolicy
        $changes = CredentialPolicy::applyToUser($user, $newEmail, $newPassword);
        $this->assertContains('email', $changes);
        $this->assertContains('password', $changes);

        // Reload data dari database untuk memastikan perubahan tersimpan
        $user->refresh();
        $this->assertEquals($newEmail, $user->email);
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check($oldPassword, $user->password));

        // 3. Login menggunakan EMAIL LAMA harus GAGAL
        $responseFailOldEmail = $this->post(route('authenticate'), [
            'email'    => $oldEmail,
            'password' => $newPassword,
        ]);
        $responseFailOldEmail->assertSessionHasErrors('email');
        $this->assertGuest();

        // 4. Login menggunakan PASSWORD LAMA harus GAGAL
        $responseFailOldPassword = $this->post(route('authenticate'), [
            'email'    => $newEmail,
            'password' => $oldPassword,
        ]);
        $responseFailOldPassword->assertSessionHasErrors('email');
        $this->assertGuest();

        // 5. Login menggunakan EMAIL BARU dan PASSWORD BARU harus BERHASIL
        $responseSuccess = $this->post(route('authenticate'), [
            'email'    => $newEmail,
            'password' => $newPassword,
        ]);
        $responseSuccess->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }
}
