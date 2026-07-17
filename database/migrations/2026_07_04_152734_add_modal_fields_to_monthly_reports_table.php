<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->json('bbm_datang')->nullable()->after('grand_totals');
            $table->decimal('do_di_pertamina', 20, 2)->default(0)->after('bbm_datang');
            $table->decimal('uang_di_bank', 20, 2)->default(0)->after('do_di_pertamina');
            $table->decimal('kas_kecil', 20, 2)->default(0)->after('uang_di_bank');
            $table->decimal('piutang', 20, 2)->default(0)->after('kas_kecil');
            $table->decimal('bunga_bank', 20, 2)->default(0)->after('piutang');
            $table->decimal('pajak_bank', 20, 2)->default(0)->after('bunga_bank');
            
            // Modal Tracking
            $table->decimal('saldo_awal_modal', 20, 2)->default(0)->after('pajak_bank');
            $table->decimal('penyusutan_modal', 20, 2)->default(0)->after('saldo_awal_modal');
            $table->decimal('penambahan_modal', 20, 2)->default(0)->after('penyusutan_modal');
            $table->decimal('saldo_akhir_modal', 20, 2)->default(0)->after('penambahan_modal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropColumn([
                'bbm_datang',
                'do_di_pertamina',
                'uang_di_bank',
                'kas_kecil',
                'piutang',
                'bunga_bank',
                'pajak_bank',
                'saldo_awal_modal',
                'penyusutan_modal',
                'penambahan_modal',
                'saldo_akhir_modal'
            ]);
        });
    }
};
