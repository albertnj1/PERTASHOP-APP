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
        Schema::create('backdate_excel_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->string('bulan_tahun');
            $table->string('original_filename');
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (Schema::hasTable('daily_reports') && Schema::hasColumn('daily_reports', 'backdate_file_id')) {
            Schema::table('daily_reports', function (Blueprint $table) {
                try {
                    $table->foreign('backdate_file_id')->references('id')->on('backdate_excel_files')->nullOnDelete();
                } catch (\Throwable $e) {}
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backdate_excel_files');
    }
};
