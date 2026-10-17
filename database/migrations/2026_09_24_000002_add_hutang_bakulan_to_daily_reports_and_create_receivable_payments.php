<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahap 4 — Hutang Bakulan & Nett Cashflow:
     * 1. Menambahkan kolom `hutang_bakulan` & `pelunasan_bakulan` pada `daily_reports`.
     * 2. Menghubungkan tabel `receivables` ke `daily_reports`.
     * 3. Membuat tabel `receivable_payments` untuk mencatat riwayat cicilan/pelunasan piutang pelanggan.
     */
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_reports', 'hutang_bakulan')) {
                $table->decimal('hutang_bakulan', 12, 2)->default(0)->after('disetorkan')->comment('Kasbon / piutang BBM baru yang belum dibayar pelanggan hari ini');
            }
            if (!Schema::hasColumn('daily_reports', 'pelunasan_bakulan')) {
                $table->decimal('pelunasan_bakulan', 12, 2)->default(0)->after('hutang_bakulan')->comment('Pelunasan piutang bakulan lama yang diterima tunai hari ini');
            }
        });

        // Tambahkan daily_report_id ke receivables jika belum ada
        if (!Schema::hasColumn('receivables', 'daily_report_id')) {
            Schema::table('receivables', function (Blueprint $table) {
                $table->foreignId('daily_report_id')->nullable()->after('shop_id')->constrained('daily_reports')->nullOnDelete();
            });
        }

        // Buat tabel receivable_payments jika belum ada
        if (!Schema::hasTable('receivable_payments')) {
            Schema::create('receivable_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('receivable_id')->constrained('receivables')->cascadeOnDelete();
                $table->foreignId('daily_report_id')->nullable()->constrained('daily_reports')->nullOnDelete();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->date('tanggal_bayar');
                $table->decimal('jumlah_bayar', 12, 2);
                $table->string('metode_bayar')->default('tunai');
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_payments');

        if (Schema::hasColumn('receivables', 'daily_report_id')) {
            Schema::table('receivables', function (Blueprint $table) {
                $table->dropForeign(['daily_report_id']);
                $table->dropColumn('daily_report_id');
            });
        }

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn(['hutang_bakulan', 'pelunasan_bakulan']);
        });
    }
};
