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
        Schema::create('excel_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->string('file_path');
            $table->string('file_hash')->unique();
            $table->bigInteger('file_size');
            $table->string('periode');
            $table->double('initial_totalisator', 15, 4)->default(0);
            $table->double('initial_stock', 12, 4)->default(0);
            $table->double('skala', 10, 2)->default(21);
            $table->double('initial_balance', 15, 2)->default(0);
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_uploads');
    }
};
