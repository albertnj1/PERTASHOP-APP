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
        Schema::create('excel_setorans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('excel_uploads')->cascadeOnDelete();
            $table->date('tanggal');
            $table->foreignId('operator_id')->nullable()->constrained('operators')->nullOnDelete();
            $table->foreignId('deposit_destination_id')->constrained('deposit_destinations')->cascadeOnDelete();
            $table->double('nominal', 15, 2);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_setorans');
    }
};
