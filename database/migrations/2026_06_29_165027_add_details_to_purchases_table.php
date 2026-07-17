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
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('purchase_date')->nullable()->after('id');
            $table->string('no_lo')->nullable()->after('no_so');
            $table->string('trip')->nullable()->after('no_lo');
            $table->date('delivery_date')->nullable()->after('volume');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['purchase_date', 'no_lo', 'trip', 'delivery_date']);
        });
    }
};
