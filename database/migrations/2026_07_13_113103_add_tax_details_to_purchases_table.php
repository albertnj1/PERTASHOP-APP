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
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total_nominal', 15, 2)->default(0)->after('total_bayar');
            $table->decimal('catatan_debit_credit', 15, 2)->default(0)->after('total_nominal');
            $table->decimal('persen_net', 5, 2)->default(85.06)->after('catatan_debit_credit');
            $table->decimal('persen_ppn', 5, 2)->default(10.11)->after('persen_net');
            $table->decimal('persen_pph', 5, 2)->default(0.23)->after('persen_ppn');
            $table->decimal('persen_pbbkb', 5, 2)->default(4.60)->after('persen_pph');
            $table->decimal('total_kotor', 15, 2)->default(0)->after('persen_pbbkb');
            $table->decimal('total_nett', 15, 2)->default(0)->after('total_kotor');
            $table->decimal('pajak_ppn', 15, 2)->default(0)->after('total_nett');
            $table->decimal('pajak_pph', 15, 2)->default(0)->after('pajak_ppn');
            $table->decimal('pajak_pbbkb', 15, 2)->default(0)->after('pajak_pph');
            $table->decimal('harga_satuan', 15, 3)->default(0)->after('pajak_pbbkb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'total_nominal', 'catatan_debit_credit', 
                'persen_net', 'persen_ppn', 'persen_pph', 'persen_pbbkb',
                'total_kotor', 'total_nett', 
                'pajak_ppn', 'pajak_pph', 'pajak_pbbkb', 'harga_satuan'
            ]);
        });
    }
};
