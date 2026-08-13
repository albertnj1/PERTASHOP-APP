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
        Schema::table('operators', function (Blueprint $table) {
            // Data Kependudukan Dasar
            $table->string('nik')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('agama')->nullable();
            $table->string('status_perkawinan')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('kewarganegaraan')->nullable();

            // Data Hubungan Keluarga
            $table->string('no_kk')->nullable();
            $table->string('nama_ayah')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('status_hubungan')->nullable();

            // Data Kontak & Digital
            $table->string('email_pribadi')->nullable();
            $table->string('akun_medsos')->nullable();

            // Data Pendidikan
            $table->string('pendidikan_terakhir')->nullable();

            // Data Biometrik
            $table->string('golongan_darah')->nullable();
            $table->string('tanda_tangan_digital')->nullable();

            // Kredensial Pertashop
            $table->string('email_pertashop')->nullable();
            $table->string('sandi_email_pertashop')->nullable();

            // Dokumen Fisik (Numbers)
            $table->string('nomor_ktp')->nullable();
            $table->string('nomor_akta_kelahiran')->nullable();
            $table->string('nomor_paspor')->nullable();
            $table->string('nomor_sim')->nullable();
            $table->string('nomor_bpjs')->nullable();
            $table->string('nomor_npwp')->nullable();
        });

        Schema::table('investors', function (Blueprint $table) {
            $table->string('email_pribadi')->nullable();
            $table->string('nama_lengkap_gelar')->nullable();
            $table->text('alamat_domisili')->nullable();
            
            // Kredensial Pertashop
            $table->string('email_pertashop')->nullable();
            $table->string('sandi_email_pertashop')->nullable();

            // Dokumen Fisik (Numbers)
            $table->string('nomor_ktp_paspor')->nullable();
            $table->string('nomor_npwp')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn([
                'nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama', 'status_perkawinan',
                'pekerjaan', 'kewarganegaraan', 'no_kk', 'nama_ayah', 'nama_ibu', 'status_hubungan',
                'email_pribadi', 'akun_medsos', 'pendidikan_terakhir', 'golongan_darah', 'tanda_tangan_digital',
                'email_pertashop', 'sandi_email_pertashop', 'nomor_ktp', 'nomor_akta_kelahiran',
                'nomor_paspor', 'nomor_sim', 'nomor_bpjs', 'nomor_npwp'
            ]);
        });

        Schema::table('investors', function (Blueprint $table) {
            $table->dropColumn([
                'email_pribadi', 'nama_lengkap_gelar', 'alamat_domisili', 'email_pertashop', 'sandi_email_pertashop',
                'nomor_ktp_paspor', 'nomor_npwp'
            ]);
        });
    }
};
