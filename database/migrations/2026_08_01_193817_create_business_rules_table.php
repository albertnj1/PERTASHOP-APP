<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // 'PAYROLL_RATE', 'LOSS_TOLERANCE', 'TOLI_TOLERANCE', 'MAX_DAILY_VOLUME'
            $table->string('name', 100);          // 'Tarif Payroll Operator per Liter'
            $table->string('category', 50)->default('payroll'); // 'payroll', 'inventory', 'pricing', 'validation'
            $table->string('data_type', 20)->default('decimal'); // 'decimal', 'integer', 'percentage', 'currency'
            $table->boolean('is_system_rule')->default(true);   // System rule tidak boleh dihapus
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_rules');
    }
};
