<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->timestamp('effective_at')->nullable()->after('harga_jual');
            
            // Composite index for fast active price lookups
            $table->index(['shop_id', 'effective_at']);
        });

        // Copy existing created_at values to effective_at for backward compatibility
        DB::table('prices')->update([
            'effective_at' => DB::raw('created_at')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropIndex(['shop_id', 'effective_at']);
            $table->dropColumn(['shop_id', 'effective_at']);
        });
    }
};
