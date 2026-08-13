<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 1. Tambah kolom encrypted_password (AES-256 via Laravel Crypt)
     * 2. Tambah kolom password_changed_at untuk audit
     * 3. Migrate data dari plaintext_password → encrypted_password
     * 4. Drop kolom plaintext_password
     */
    public function up(): void
    {
        // Langkah 1 & 2: Tambah kolom baru
        Schema::table('users', function (Blueprint $table) {
            $table->text('encrypted_password')->nullable()->after('password');
            $table->timestamp('password_changed_at')->nullable()->after('encrypted_password');
        });

        // Langkah 3: Migrate data yang ada dari plaintext_password ke encrypted_password
        // Hanya jika kolom plaintext_password masih ada
        if (Schema::hasColumn('users', 'plaintext_password')) {
            $users = DB::table('users')->whereNotNull('plaintext_password')->get(['id', 'plaintext_password']);
            foreach ($users as $user) {
                if (!empty($user->plaintext_password)) {
                    DB::table('users')->where('id', $user->id)->update([
                        'encrypted_password' => Crypt::encryptString($user->plaintext_password),
                    ]);
                }
            }

            // Langkah 4: Drop kolom plaintext_password
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('plaintext_password');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kembalikan kolom plaintext_password
            $table->string('plaintext_password')->nullable()->after('password');
        });

        // Restore data dari encrypted_password ke plaintext_password (best effort)
        $users = DB::table('users')->whereNotNull('encrypted_password')->get(['id', 'encrypted_password']);
        foreach ($users as $user) {
            try {
                $plain = Crypt::decryptString($user->encrypted_password);
                DB::table('users')->where('id', $user->id)->update([
                    'plaintext_password' => $plain,
                ]);
            } catch (\Throwable $e) {
                // Jika dekripsi gagal (APP_KEY berbeda), biarkan null
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['encrypted_password', 'password_changed_at']);
        });
    }
};
