<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perluas presisi kolom rupiah di payroll_details agar nilai desimal
 * (komisi per liter) tersimpan utuh tanpa pembulatan di DB.
 *
 * PENTING: Migration ini HANYA dijalankan ke database _test.
 * Jangan jalankan ke database produksi (sal_pertashop) tanpa persetujuan pemilik.
 *
 * Sebelumnya: DECIMAL(12,2) — cukup untuk pembulatan ke rupiah
 * Sesudahnya : DECIMAL(18,4) — cukup untuk nilai pecahan sen (desimal murni)
 */
return new class extends Migration
{
    /**
     * Koneksi eksplisit ke pgsql supaya tidak salah koneksi.
     * @var string
     */
    public $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection($this->connection)->table('payroll_details', function (Blueprint $table) {
            // Volume liter — 3 desimal sudah cukup, tidak diubah
            // Semua kolom rupiah/nilai uang: dari DECIMAL(12,2) → DECIMAL(18,4)
            $table->decimal('gaji_variable',        18, 4)->default(0)->change();
            $table->decimal('gaji_pokok',           18, 4)->default(0)->change();
            $table->decimal('lembur',               18, 4)->default(0)->change();
            $table->decimal('lembur_hari_raya',     18, 4)->default(0)->change();
            $table->decimal('bonus',                18, 4)->default(0)->change();
            $table->decimal('potongan_tidak_masuk', 18, 4)->default(0)->change();
            $table->decimal('tabungan_gaji',        18, 4)->default(0)->change();
            $table->decimal('tabungan_setoran',     18, 4)->default(0)->change();
            $table->decimal('potongan_hutang',      18, 4)->default(0)->change();
            $table->decimal('take_home_pay',        18, 4)->default(0)->change();
        });

        // Kolom tambahan yang di-add di migration berikutnya (jika sudah ada)
        if (Schema::connection($this->connection)->hasColumn('payroll_details', 'thr')) {
            Schema::connection($this->connection)->table('payroll_details', function (Blueprint $table) {
                $table->decimal('thr',              18, 4)->default(0)->change();
            });
        }
        if (Schema::connection($this->connection)->hasColumn('payroll_details', 'kurang_setoran')) {
            Schema::connection($this->connection)->table('payroll_details', function (Blueprint $table) {
                $table->decimal('kurang_setoran',   18, 4)->default(0)->change();
            });
        }
        if (Schema::connection($this->connection)->hasColumn('payroll_details', 'uang_transport')) {
            Schema::connection($this->connection)->table('payroll_details', function (Blueprint $table) {
                $table->decimal('uang_transport',   18, 4)->default(0)->change();
            });
        }
        if (Schema::connection($this->connection)->hasColumn('payroll_details', 'sisa_kurang_bayar')) {
            Schema::connection($this->connection)->table('payroll_details', function (Blueprint $table) {
                $table->decimal('sisa_kurang_bayar', 18, 4)->default(0)->change();
            });
        }
        // liter_bagian: volume operator, cukup 3 desimal (DECIMAL(15,3))
        if (Schema::connection($this->connection)->hasColumn('payroll_details', 'liter_bagian')) {
            Schema::connection($this->connection)->table('payroll_details', function (Blueprint $table) {
                $table->decimal('liter_bagian',     15, 3)->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('payroll_details', function (Blueprint $table) {
            $table->decimal('gaji_variable',        12, 2)->default(0)->change();
            $table->decimal('gaji_pokok',           12, 2)->default(0)->change();
            $table->decimal('lembur',               12, 2)->default(0)->change();
            $table->decimal('lembur_hari_raya',     12, 2)->default(0)->change();
            $table->decimal('bonus',                12, 2)->default(0)->change();
            $table->decimal('potongan_tidak_masuk', 12, 2)->default(0)->change();
            $table->decimal('tabungan_gaji',        12, 2)->default(0)->change();
            $table->decimal('tabungan_setoran',     12, 2)->default(0)->change();
            $table->decimal('potongan_hutang',      12, 2)->default(0)->change();
            $table->decimal('take_home_pay',        12, 2)->default(0)->change();
        });
    }
};
