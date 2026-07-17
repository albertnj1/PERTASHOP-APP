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
        Schema::table('incomings', function (Blueprint $table) {
            $table->string('asal_pengirim')->nullable()->after('no_polisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incomings', function (Blueprint $table) {
            $table->dropColumn('asal_pengirim');
        });
    }
};
