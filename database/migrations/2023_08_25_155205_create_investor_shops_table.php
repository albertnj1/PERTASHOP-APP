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
        Schema::create('investor_shop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained();
            $table->foreignId('shop_id')->constrained();
            $table->unsignedDecimal('persentase', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_shops');
    }
};
