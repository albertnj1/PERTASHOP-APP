<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tahap 2 — Gap Analysis Fix:
     * 1. Menambahkan kolom `report_date` (DATE) sebagai tanggal operasional laporan,
     *    terpisah dari `created_at` (timestamp input).
     * 2. Menambahkan snapshot harga per-row agar data historis tidak berubah
     *    jika tabel `prices` diedit retroaktif.
     * 3. UNIQUE constraint mencegah duplikasi laporan pada toko + tanggal yang sama.
     */
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_reports', 'report_date')) {
                $table->date('report_date')
                      ->nullable()
                      ->after('shop_id')
                      ->comment('Tanggal operasional laporan (bukan timestamp input). Digunakan untuk semua query filtering.');
            }

            if (!Schema::hasColumn('daily_reports', 'harga_jual_snapshot')) {
                $table->decimal('harga_jual_snapshot', 10, 2)
                      ->nullable()
                      ->after('price_id')
                      ->comment('Snapshot harga jual BBM saat transaksi terjadi. Immutable setelah disimpan.');
            }

            if (!Schema::hasColumn('daily_reports', 'harga_beli_snapshot')) {
                $table->decimal('harga_beli_snapshot', 10, 2)
                      ->nullable()
                      ->after('harga_jual_snapshot')
                      ->comment('Snapshot harga beli/HPP BBM saat transaksi terjadi. Immutable setelah disimpan.');
            }
        });

        // Populate report_date dari created_at untuk data existing
        try {
            DB::statement("UPDATE daily_reports SET report_date = DATE(created_at) WHERE report_date IS NULL");
        } catch (\Throwable $e) {}

        // Populate snapshot harga dari relasi prices
        try {
            DB::statement("
                UPDATE daily_reports dr
                JOIN prices p ON dr.price_id = p.id
                SET dr.harga_jual_snapshot = p.harga_jual,
                    dr.harga_beli_snapshot = p.harga_beli
                WHERE dr.harga_jual_snapshot IS NULL
            ");
        } catch (\Throwable $e) {}

        // Fallback untuk data lama yang price_id nya NULL: ambil dari harga aktif toko terkait atau harga terbaru
        try {
            DB::statement("
                UPDATE daily_reports dr
                JOIN prices p ON p.id = (
                    SELECT p2.id FROM prices p2 
                    WHERE (p2.shop_id = dr.shop_id OR p2.shop_id IS NULL)
                    ORDER BY (p2.shop_id = dr.shop_id) DESC, p2.effective_at DESC, p2.id DESC
                    LIMIT 1
                )
                SET dr.harga_jual_snapshot = p.harga_jual,
                    dr.harga_beli_snapshot = p.harga_beli,
                    dr.price_id = p.id
                WHERE dr.harga_jual_snapshot IS NULL
            ");
        } catch (\Throwable $e) {}

        // Setelah data terpopulasi, tambahkan index jika belum ada
        try {
            Schema::table('daily_reports', function (Blueprint $table) {
                $table->index('report_date', 'idx_daily_reports_report_date');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('daily_reports', function (Blueprint $table) {
                $table->unique(['shop_id', 'report_date'], 'uq_daily_reports_shop_date');
            });
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropUnique('uq_daily_reports_shop_date');
            $table->dropIndex('idx_daily_reports_report_date');
            $table->dropColumn(['report_date', 'harga_jual_snapshot', 'harga_beli_snapshot']);
        });
    }
};
