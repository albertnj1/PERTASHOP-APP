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
        Schema::create('monthly_report_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monthly_report_id');
            $table->string('component');
            $table->double('system_value');
            $table->double('recalculated_value');
            $table->double('diff');
            $table->string('status'); // 'valid', 'invalid'
            $table->timestamps();

            $table->foreign('monthly_report_id')
                  ->references('id')
                  ->on('monthly_reports')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_report_validations');
    }
};
