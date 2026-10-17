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
        // ── Parent: Order SO ────────────────────────────────────────────
        if (!Schema::hasTable('so_orders')) {
            Schema::create('so_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shop_id');
                $table->date('order_date');
                $table->unsignedBigInteger('supplier_id');
                $table->string('so_number', 100);
                $table->string('lo_number', 100);
                $table->decimal('total_quantity_liter', 12, 2);
                $table->decimal('total_amount', 15, 2);
                $table->integer('total_trips')->default(1);
                $table->enum('status', ['PENDING', 'PARTIAL', 'COMPLETED'])->default('PENDING');
                $table->timestamps();

                $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            });
        }

        // ── Child: Trip / Delivery Schedule ─────────────────────────────
        if (!Schema::hasTable('so_order_trips')) {
            Schema::create('so_order_trips', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('so_order_id');
                $table->integer('trip_number');
                $table->date('estimated_delivery_date');
                $table->decimal('quantity_liter', 12, 2);
                $table->enum('status', ['PENDING', 'RECEIVED'])->default('PENDING');
                $table->timestamp('received_at')->nullable();
                $table->unsignedBigInteger('daily_report_id')->nullable();
                $table->unsignedBigInteger('incoming_id')->nullable();
                $table->timestamps();

                $table->foreign('so_order_id')->references('id')->on('so_orders')->onDelete('cascade');
                $table->foreign('daily_report_id')->references('id')->on('daily_reports')->onDelete('set null');
                $table->foreign('incoming_id')->references('id')->on('incomings')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('so_order_trips');
        Schema::dropIfExists('so_orders');
    }
};
