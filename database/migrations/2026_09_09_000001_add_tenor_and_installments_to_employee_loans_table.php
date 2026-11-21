<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_loans', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_loans', 'tenor_bulan')) {
                $table->unsignedInteger('tenor_bulan')->default(1)->after('jumlah');
            }
            if (!Schema::hasColumn('employee_loans', 'nominal_angsuran')) {
                $table->decimal('nominal_angsuran', 12, 2)->default(0)->after('tenor_bulan');
            }
            if (!Schema::hasColumn('employee_loans', 'angsuran_ke')) {
                $table->unsignedInteger('angsuran_ke')->default(0)->after('nominal_angsuran');
            }
            if (!Schema::hasColumn('employee_loans', 'sisa_pokok')) {
                $table->decimal('sisa_pokok', 12, 2)->default(0)->after('angsuran_ke');
            }
        });

        // Ubah status agar mendukung pending, approved, active, lunas, rejected
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE employee_loans DROP CONSTRAINT IF EXISTS employee_loans_status_check');
        }
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });

        // Backfill data existing jika ada
        DB::table('employee_loans')
            ->where(function ($q) {
                $q->where('sisa_pokok', 0)->orWhereNull('sisa_pokok');
            })
            ->update([
                'tenor_bulan' => 1,
                'nominal_angsuran' => DB::raw('jumlah'),
                'sisa_pokok' => DB::raw('jumlah'),
            ]);

        // Tabel tracking pemotongan cicilan per payroll period & detail
        if (!Schema::hasTable('payroll_loan_deductions')) {
            Schema::create('payroll_loan_deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_period_id')->constrained('payroll_periods')->onDelete('cascade');
                $table->foreignId('payroll_detail_id')->constrained('payroll_details')->onDelete('cascade');
                $table->foreignId('employee_loan_id')->constrained('employee_loans')->onDelete('cascade');
                $table->unsignedInteger('cicilan_ke')->default(1);
                $table->unsignedInteger('total_tenor')->default(1);
                $table->decimal('nominal_potongan', 12, 2)->default(0);
                $table->decimal('sisa_pokok_sebelum', 12, 2)->default(0);
                $table->decimal('sisa_pokok_setelah', 12, 2)->default(0);
                $table->boolean('is_finalized')->default(false);
                $table->timestamps();

                $table->index(['payroll_period_id', 'employee_loan_id']);
                $table->index(['payroll_detail_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_loan_deductions');

        Schema::table('employee_loans', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('employee_loans', 'tenor_bulan')) $columnsToDrop[] = 'tenor_bulan';
            if (Schema::hasColumn('employee_loans', 'nominal_angsuran')) $columnsToDrop[] = 'nominal_angsuran';
            if (Schema::hasColumn('employee_loans', 'angsuran_ke')) $columnsToDrop[] = 'angsuran_ke';
            if (Schema::hasColumn('employee_loans', 'sisa_pokok')) $columnsToDrop[] = 'sisa_pokok';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
