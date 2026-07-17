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
        Schema::create('price_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // 'CREATE', 'UPDATE', 'DELETE'
            $table->decimal('harga_beli_lama', 10, 2)->nullable();
            $table->decimal('harga_jual_lama', 10, 2)->nullable();
            $table->decimal('harga_beli_baru', 10, 2);
            $table->decimal('harga_jual_baru', 10, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_audit_logs');
    }
};
