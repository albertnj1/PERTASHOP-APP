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
        Schema::create('excel_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('excel_uploads')->cascadeOnDelete();
            $table->unsignedBigInteger('row_id');
            $table->string('field');
            $table->text('nilai_lama')->nullable();
            $table->text('nilai_baru')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_change_logs');
    }
};
