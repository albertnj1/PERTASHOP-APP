<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah kolom status shift_schedules ke VARCHAR(30) agar fleksibel mendukung status libur/diliburkan
        try {
            DB::statement("ALTER TABLE `shift_schedules` MODIFY `status` VARCHAR(30) NOT NULL DEFAULT 'dijadwalkan'");
        } catch (\Throwable $e) {
            // Abaikan jika bukan MySQL
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE `shift_schedules` MODIFY `status` ENUM('dijadwalkan', 'hadir', 'alpha', 'izin', 'sakit') NOT NULL DEFAULT 'dijadwalkan'");
        } catch (\Throwable $e) {}
    }
};
