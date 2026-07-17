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
        Schema::create('excel_operasionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('excel_uploads')->cascadeOnDelete();
            $table->date('tanggal');
            $table->foreignId('operator_id')->nullable()->constrained('operators')->nullOnDelete();
            $table->string('excel_operator_name');
            $table->double('totalisator_akhir', 15, 4);
            $table->double('test_pump', 12, 4)->default(0);
            $table->double('curah', 12, 4)->default(0);
            $table->double('stik_malam', 12, 4)->nullable();
            $table->double('harga_jual', 12, 2)->default(0);
            $table->double('harga_tebus', 15, 4)->default(0);
            $table->double('pengeluaran', 15, 2)->default(0);
            $table->string('keterangan_pengeluaran')->nullable();
            $table->double('qris', 15, 2)->default(0);
            $table->double('setoran_adjustment', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_operasionals');
    }
};
