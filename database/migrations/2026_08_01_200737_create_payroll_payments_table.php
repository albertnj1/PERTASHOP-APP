<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id')->index();
            $table->unsignedBigInteger('operator_id')->index();
            $table->string('payment_method', 30)->default('bank_transfer'); // 'bank_transfer', 'cash'
            $table->string('payment_reference', 100)->nullable();          // No Referensi Transfer TRX00192
            $table->decimal('paid_amount', 12, 2);
            $table->unsignedBigInteger('paid_by')->constrained('users');
            $table->timestamp('paid_at');
            $table->string('status', 30)->default('paid');                  // 'pending', 'paid', 'cancelled'
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->onDelete('cascade');
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payments');
    }
};
