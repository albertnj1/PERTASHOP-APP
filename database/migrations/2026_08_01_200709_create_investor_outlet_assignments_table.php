<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_outlet_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('investor_id')->index(); // User dengan role Investor
            $table->unsignedBigInteger('shop_id')->index();     // Outlet Pertashop
            $table->decimal('ownership_percentage', 5, 2)->default(100.00); // Persentase Kepemilikan (misal: 50.00%)
            $table->timestamps();

            $table->foreign('investor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->unique(['investor_id', 'shop_id'], 'investor_shop_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_outlet_assignments');
    }
};
