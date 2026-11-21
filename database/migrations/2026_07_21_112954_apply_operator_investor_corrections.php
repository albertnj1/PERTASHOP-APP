<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (!Schema::hasColumn('operators', 'pas_foto')) {
                $table->string('pas_foto')->nullable();
            }
            if (!Schema::hasColumn('operators', 'asal_sekolah')) {
                $table->string('asal_sekolah')->nullable();
            }
            if (!Schema::hasColumn('operators', 'jenis_sim')) {
                $table->string('jenis_sim')->nullable();
            }

            if (Schema::hasColumn('operators', 'nomor_ktp')) {
                $table->dropColumn('nomor_ktp');
            }
            if (Schema::hasColumn('operators', 'nomor_akta_kelahiran')) {
                $table->dropColumn('nomor_akta_kelahiran');
            }

            $table->string('nomor_paspor')->nullable()->change();
            $table->string('nomor_sim')->nullable()->change();
            $table->string('nomor_bpjs')->nullable()->change();
            $table->string('akun_medsos')->nullable()->change();
        });

        if (Schema::hasColumn('investors', 'nomor_ktp_paspor')) {
            Schema::table('investors', function (Blueprint $table) {
                $table->renameColumn('nomor_ktp_paspor', 'nik');
            });

            Schema::table('investors', function (Blueprint $table) {
                $table->string('nik', 255)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};