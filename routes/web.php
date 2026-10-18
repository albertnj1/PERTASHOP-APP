<?php

use App\Models\Incoming;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\IncomingController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SpendingController;
use App\Http\Controllers\TestPumpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LabaKotorController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LabaBersihController;
use App\Http\Controllers\CorporationController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\ModalController;
use App\Http\Controllers\ProfitSharingController;
use App\Http\Controllers\KolektanController;
use App\Http\Controllers\CapitalRecapController;
use App\Http\Controllers\ShiftScheduleController;
use App\Http\Controllers\OperatorProfileController;
use App\Http\Controllers\FinanceCashflowController;
use App\Http\Controllers\BenchmarkingController;
use App\Http\Controllers\PayrollSystemController;
use App\Http\Controllers\PayrollOperatorAssignmentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SoOrderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/



Route::get('login', [LoginController::class, 'index'])->name('login');
Route::post('login', [LoginController::class, 'authenticate'])->name('authenticate');
Route::match(['get', 'post', 'delete'], 'logout', [LoginController::class, 'logout'])->name('logout');

// 'auth.session' memutus sesi/cookie "ingat saya" lama otomatis ketika sandi user diganti.
Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Pengaturan Akun Khusus Super Admin ─────────────────────────────────
    Route::middleware('role:super-admin')->group(function () {
        Route::get('/admin/pengaturan-akun', [\App\Http\Controllers\AccountSettingController::class, 'edit'])->name('account.settings');
        Route::put('/admin/pengaturan-akun', [\App\Http\Controllers\AccountSettingController::class, 'update'])->name('account.settings.update');
    });

    Route::get('daily-reports/export-excel', [DailyReportController::class, 'exportExcel'])->name('daily-reports.export-excel');
    Route::get('daily-reports/export-pdf', [DailyReportController::class, 'exportPdf'])->name('daily-reports.export-pdf');

    Route::resources([
        'incomings' => IncomingController::class,
        'test-pumps' => TestPumpController::class,
        'daily-reports' => DailyReportController::class,
        'spendings' => SpendingController::class,
    ]);

    // API: Daftar trip SO PENDING untuk Operator Penerimaan BBM
    Route::get('/api/so-orders/pending-trips', [SoOrderController::class, 'pendingTripsForShop'])->name('api.so-orders.pending-trips');
    Route::resource('prices', PriceController::class);

    // ── Fase C.1 — Approval & Lock Workflow Routes ────────────────────────────
    Route::get('/reports/approval', [\App\Http\Controllers\ReportApprovalController::class, 'index'])->name('reports.approval.index');
    Route::post('/reports/approval/transition-batch', [\App\Http\Controllers\ReportApprovalController::class, 'transitionBatch'])->name('reports.approval.transition-batch');
    Route::post('/reports/approval/lock-period', [\App\Http\Controllers\ReportApprovalController::class, 'lockPeriod'])->name('reports.approval.lock-period');
    Route::post('/reports/approval/reopen-period', [\App\Http\Controllers\ReportApprovalController::class, 'reopenPeriod'])->name('reports.approval.reopen-period');

    // ── Fase C.2 — Business Rule Registry Routes ──────────────────────────────
    Route::get('/business-rules', [\App\Http\Controllers\BusinessRuleController::class, 'index'])->name('business-rules.index');
    Route::post('/business-rules/version', [\App\Http\Controllers\BusinessRuleController::class, 'storeVersion'])->name('business-rules.version.store');

    // ── Fase C.3 — Payroll & THP Aggregation Routes ───────────────────────────
    Route::get('/payrolls/aggregation', [\App\Http\Controllers\PayrollAggregationController::class, 'index'])->name('payrolls.aggregation.index');
    Route::post('/payrolls/aggregation/generate', [\App\Http\Controllers\PayrollAggregationController::class, 'generate'])->name('payrolls.aggregation.generate');
    Route::post('/payrolls/aggregation/approve', [\App\Http\Controllers\PayrollAggregationController::class, 'approve'])->name('payrolls.aggregation.approve');
    Route::get('/payrolls/aggregation/slip/{detailId}', [\App\Http\Controllers\PayrollAggregationController::class, 'exportSlipPdf'])->name('payrolls.aggregation.slip');

    // ── Fase D.2 — Investor Financial Dashboard ───────────────────────────────
    Route::get('/investor/dashboard', [\App\Http\Controllers\InvestorDashboardController::class, 'index'])->name('investor.dashboard');
    Route::get('operator/performa', [OperatorProfileController::class, 'index'])->name('operator.performa');
    Route::get('operator/presensi', [OperatorProfileController::class, 'presensi'])->name('operator.presensi');
    Route::get('operator/slip-gaji/{payrollDetail}/export-pdf', [OperatorProfileController::class, 'exportSlipPdf'])->name('operator.slip-gaji.pdf');

    // Kasbon / Employee Loans (Operator dapat mengajukan & melihat riwayat kasbon miliknya)
    Route::resource('employee-loans', \App\Http\Controllers\EmployeeLoanController::class);
    Route::post('employee-loans/{employeeLoan}/approve', [\App\Http\Controllers\EmployeeLoanController::class, 'approve'])->name('employee-loans.approve');
    Route::post('employee-loans/{employeeLoan}/reject', [\App\Http\Controllers\EmployeeLoanController::class, 'reject'])->name('employee-loans.reject');
    Route::post('employee-loans/{employeeLoan}/pay-installment', [\App\Http\Controllers\EmployeeLoanController::class, 'payInstallment'])->name('employee-loans.pay-installment');
    Route::post('employee-loans/{employeeLoan}/pay-full', [\App\Http\Controllers\EmployeeLoanController::class, 'payFull'])->name('employee-loans.pay-full');
    Route::post('employee-loans/{employeeLoan}/update-tenor', [\App\Http\Controllers\EmployeeLoanController::class, 'updateTenor'])->name('employee-loans.update-tenor');

    Route::middleware('role:super-admin,admin,investor')->group(function () {
        Route::resources([
            'shops' => ShopController::class,
            'operators' => OperatorController::class,
        ]);
        
        Route::post('monthly-reports/generate', [\App\Http\Controllers\MonthlyReportController::class, 'generateFromDailyReports'])->name('monthly-reports.generate');
        Route::post('monthly-reports/recalculate-cascade/{shop}', [\App\Http\Controllers\MonthlyReportController::class, 'recalculateCascade'])->name('monthly-reports.recalculate-cascade');
        Route::get('monthly-reports/{id}/export-pdf', [\App\Http\Controllers\MonthlyReportController::class, 'exportPdf'])->name('monthly-reports.export-pdf');
        Route::get('monthly-reports/{id}/download', [\App\Http\Controllers\MonthlyReportController::class, 'download'])->name('monthly-reports.download');
        Route::resource('monthly-reports', \App\Http\Controllers\MonthlyReportController::class);

        // Sistem Penggajian: halaman index bisa dilihat investor (Tab Perbandingan Toko)
        Route::get('payroll-systems', [PayrollSystemController::class, 'index'])->name('payroll-systems.index');
        Route::get('payroll-systems/by-shop/{shop}', [PayrollSystemController::class, 'byShop'])->name('payroll-systems.by-shop');
        Route::get('payroll-systems/defaults-by-shop/{shop}', [PayrollSystemController::class, 'defaultsByShop'])->name('payroll-systems.defaults-by-shop');

        // Arsip Upload File Excel Backdate (per Pertashop Container) & Engine v2
        Route::get('backdate-excel-files', [\App\Http\Controllers\BackdateExcelFileController::class, 'index'])->name('backdate-excel-files.index');
        Route::post('backdate-excel-files', [\App\Http\Controllers\BackdateExcelFileController::class, 'store'])->name('backdate-excel-files.store');
        Route::post('backdate-excel-files/upload-multi', [\App\Http\Controllers\BackdateExcelFileController::class, 'storeMulti'])->name('backdate-excel-files.upload-multi');
        Route::get('backdate-excel-files/{backdateExcelFile}/download-pdf', [\App\Http\Controllers\BackdateExcelFileController::class, 'downloadPdf'])->name('backdate-excel-files.download-pdf');
        Route::get('backdate-excel-files/{backdateExcelFile}/download-report-excel', [\App\Http\Controllers\BackdateExcelFileController::class, 'downloadReportExcel'])->name('backdate-excel-files.download-report-excel');
        Route::delete('backdate-excel-files/delete-all', [\App\Http\Controllers\BackdateExcelFileController::class, 'deleteAll'])->name('backdate-excel-files.delete-all');
        Route::post('backdate-excel-files/restore-all', [\App\Http\Controllers\BackdateExcelFileController::class, 'restoreAll'])->name('backdate-excel-files.restore-all');
        Route::delete('backdate-excel-files/empty-trash', [\App\Http\Controllers\BackdateExcelFileController::class, 'emptyTrash'])->name('backdate-excel-files.empty-trash');
        Route::post('backdate-excel-files/{id}/restore', [\App\Http\Controllers\BackdateExcelFileController::class, 'restore'])->name('backdate-excel-files.restore');
        Route::delete('backdate-excel-files/{id}/force-delete', [\App\Http\Controllers\BackdateExcelFileController::class, 'forceDelete'])->name('backdate-excel-files.force-delete');
        Route::post('backdate-excel-files/{backdateExcelFile}/sync', [\App\Http\Controllers\BackdateExcelFileController::class, 'sync'])->name('backdate-excel-files.sync');
        Route::get('backdate-excel-files/{backdateExcelFile}', [\App\Http\Controllers\BackdateExcelFileController::class, 'show'])->name('backdate-excel-files.show');
        Route::get('backdate-excel-files/{backdateExcelFile}/stream', [\App\Http\Controllers\BackdateExcelFileController::class, 'stream'])->name('backdate-excel-files.stream');
        Route::get('backdate-excel-files/{backdateExcelFile}/download', [\App\Http\Controllers\BackdateExcelFileController::class, 'download'])->name('backdate-excel-files.download');
        Route::get('backdate-excel-files/{fileId}/download-original', [\App\Http\Controllers\BackdateExcelFileController::class, 'downloadOriginalBackdateExcel'])->name('backdate-excel-files.download-original');
        Route::delete('backdate-excel-files/{backdateExcelFile}', [\App\Http\Controllers\BackdateExcelFileController::class, 'destroy'])->name('backdate-excel-files.destroy');

        // Laporan Keuangan & Kasflow
        Route::get('finance/cashflow', [FinanceCashflowController::class, 'index'])->name('finance.cashflow');

        // Jadwal Shift
        Route::resource('shift-schedules', ShiftScheduleController::class);
        Route::post('shift-schedules/bulk', [ShiftScheduleController::class, 'bulkStore'])->name('shift-schedules.bulk');
        Route::post('shift-schedules/request-swap', [ShiftScheduleController::class, 'requestSwap'])->name('shift-schedules.request-swap');
        Route::post('shift-schedules/{shiftSchedule}/swap', [ShiftScheduleController::class, 'swap'])->name('shift-schedules.swap');
        Route::post('shift-swaps/{swap}/approve', [ShiftScheduleController::class, 'approveSwap'])->name('shift-swaps.approve');
        Route::post('shift-swaps/{swap}/reject', [ShiftScheduleController::class, 'rejectSwap'])->name('shift-swaps.reject');

        // Laporan Keuangan Per Outlet
        Route::prefix('laba-kotor')->group(function () {
            Route::get('/', [LabaKotorController::class, 'index'])->name('laba-kotor.index');
            Route::get('/{shop_id}/{year_month}', [LabaKotorController::class, 'edit'])->name('laba-kotor.edit');
        });

        Route::prefix('laba-bersih')->group(function () {
            Route::get('/', [LabaBersihController::class, 'index'])->name('laba-bersih.index');
            Route::get('/{shop_id}/{year_month}', [LabaBersihController::class, 'edit'])->name('laba-bersih.edit');
            Route::post('/{shop_id}/{year_month}/alokasi_modal', [LabaBersihController::class, 'alokasi_modal'])->name('laba-bersih.alokasi-modal');
        });

        Route::prefix('modal')->group(function () {
            Route::get('/', [ModalController::class, 'index'])->name('modal.index');
            Route::get('/{shop_id}/{year_month}', [ModalController::class, 'edit'])->name('modal.edit');
        });

        Route::prefix('profit-sharing')->group(function () {
            Route::get('/', [ProfitSharingController::class, 'index'])->name('profit-sharing.index');
            Route::get('/{shop_id}/{year_month}', [ProfitSharingController::class, 'edit'])->name('profit-sharing.edit');
        });
    });

    Route::middleware('role:super-admin,admin')->group(function () {
        Route::resources([
            'purchases' => PurchaseController::class,
            'corporations' => CorporationController::class,
            'investors' => InvestorController::class,
            'kolektans' => KolektanController::class,
        ]);

        // ── Order SO Multi-Trip ──────────────────────────────────────────
        Route::resource('so-orders', SoOrderController::class);

        Route::get('analytics/benchmarking', [BenchmarkingController::class, 'index'])->name('analytics.benchmarking');
        Route::get('investors/{investor}/export-pdf', [\App\Http\Controllers\InvestorController::class, 'exportPdf'])->name('investors.export-pdf');

        // Pusat Tindakan (Action Center)
        Route::get('action-center', [\App\Http\Controllers\ActionCenterController::class, 'index'])->name('action-center.index');

        // ─── Penggajian Operator ────────────────────────────────────────────
        // Data Master: Sistem Penggajian (CUD — read sudah di group investor di atas)
        Route::get('payroll-systems/create', [PayrollSystemController::class, 'create'])->name('payroll-systems.create');
        Route::post('payroll-systems', [PayrollSystemController::class, 'store'])->name('payroll-systems.store');
        Route::get('payroll-systems/{payrollSystem}/edit', [PayrollSystemController::class, 'edit'])->name('payroll-systems.edit');
        Route::put('payroll-systems/{payrollSystem}', [PayrollSystemController::class, 'update'])->name('payroll-systems.update');
        Route::patch('payroll-systems/{payrollSystem}', [PayrollSystemController::class, 'update']);
        Route::delete('payroll-systems/{payrollSystem}', [PayrollSystemController::class, 'destroy'])->name('payroll-systems.destroy');
        Route::get('payroll-systems/{payrollSystem}', [PayrollSystemController::class, 'show'])->name('payroll-systems.show');

        // [DINONAKTIFKAN] Assign Operator ke Sistem Penggajian — tidak diperlukan lagi.
        // Operator otomatis ditarik berdasarkan shop_id di PayrollCalculationService.
        // Route::get('payroll-operator-assignments/operators-by-shop/{shop}', [PayrollOperatorAssignmentController::class, 'operatorsByShop'])->name('payroll-operator-assignments.operators-by-shop');
        // Route::resource('payroll-operator-assignments', PayrollOperatorAssignmentController::class)->except(['edit', 'update']);

        // Proses Penggajian Bulanan
        Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::post('payroll/bulk-generate', [PayrollController::class, 'bulkGenerate'])->name('payroll.bulk-generate');
        Route::get('payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::patch('payroll/{payroll}/detail/{detail}', [PayrollController::class, 'updateDetail'])->name('payroll.detail.update');
        Route::post('payroll/{payroll}/detail/{detail}/items', [PayrollController::class, 'addItem'])->name('payroll.detail.add-item');
        Route::delete('payroll/{payroll}/detail/{detail}/items/{item}', [PayrollController::class, 'deleteItem'])->name('payroll.detail.delete-item');
        Route::post('payroll/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
        Route::get('payroll/{payroll}/export-pdf', [PayrollController::class, 'exportPdf'])->name('payroll.export-pdf');
        Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
        // ────────────────────────────────────────────────────────────────────
    });

    Route::post('/investors/{investor}/add-investment', [InvestorController::class, 'addInvestment'])->name('investors.add-investment');

    Route::get('/capital-recaps', [CapitalRecapController::class, 'index'])->name('capital-recaps.index');
    Route::post('/capital-recaps/recalculate/{shop}', [CapitalRecapController::class, 'recalculate'])->name('capital-recaps.recalculate');
    Route::get('/capital-recaps/import', [CapitalRecapController::class, 'importForm'])->name('capital-recaps.import');
    Route::post('/capital-recaps/import', [CapitalRecapController::class, 'importStore'])->name('capital-recaps.import.store');

    Route::match(['post', 'patch'], '/operators/{operator}/toggle-status', [OperatorController::class, 'toggleStatus'])->name('operators.toggle-status');
    Route::match(['post', 'patch'], '/investors/{investor}/toggle-status', [InvestorController::class, 'toggleStatus'])->name('investors.toggle-status');
    Route::match(['post', 'patch'], '/shops/{shop}/toggle-status', [ShopController::class, 'toggleStatus'])->name('shops.toggle-status');
    Route::match(['post', 'patch'], '/corporations/{corporation}/toggle-status', [CorporationController::class, 'toggleStatus'])->name('corporations.toggle-status');

    // Generic API Status Toggle
    Route::match(['post', 'patch'], '/api/toggle-status/{type}/{id}', function(Request $request, $type, $id) {
        $isActive = $request->input('is_active');
        $model = null;
        
        switch ($type) {
            case 'investors':
            case 'investor':
                $model = \App\Models\Investor::findOrFail($id);
                $model->is_active = $isActive !== null ? (bool)$isActive : !$model->is_active;
                $model->save();
                if ($model->user) {
                    $model->user->is_active = $model->is_active;
                    $model->user->save();
                }
                break;
                
            case 'shops':
            case 'pertashops':
            case 'shop':
                $model = \App\Models\Shop::findOrFail($id);
                $model->is_active = $isActive !== null ? (bool)$isActive : !$model->is_active;
                $model->save();
                break;
                
            case 'corporations':
            case 'badan_usaha':
            case 'corporation':
                $model = \App\Models\Corporation::findOrFail($id);
                $model->is_active = $isActive !== null ? (bool)$isActive : !$model->is_active;
                $model->save();
                break;
                
            case 'operators':
            case 'operator':
                $model = \App\Models\Operator::findOrFail($id);
                $model->is_active = $isActive !== null ? (bool)$isActive : !$model->is_active;
                $model->save();
                if ($model->user) {
                    $model->user->is_active = $model->is_active;
                    $model->user->save();
                }
                break;
                
            default:
                return response()->json(['error' => 'Invalid model type'], 400);
        }
        
        return response()->json([
            'status' => 'success',
            'is_active' => (bool)$model->is_active,
            'message' => 'Status berhasil diperbarui'
        ]);
    })->name('api.toggle-status');

    Route::match(['put', 'patch'], '/operators/{operator}/update-credentials', [OperatorController::class, 'updateCredentials'])->name('operators.update-credentials');
    Route::match(['put', 'patch'], '/investors/{investor}/update-credentials', [InvestorController::class, 'updateCredentials'])->name('investors.update-credentials');

    Route::get('/shops/{shop}/investors', [ShopController::class, 'investor'])->name('shops.investors');
    Route::post('/shops/{shop}/investors', [ShopController::class, 'investorStore'])->name('shops.investors.store');
    Route::put('/shops/{shop}/investors', [ShopController::class, 'investorUpdate'])->name('shops.investors.update');
    Route::delete('/shops/{shop}/investors', [ShopController::class, 'investorDestroy'])->name('shops.investors.destroy');
    Route::get('/shops/{shop}/modal-details', [ShopController::class, 'getModalDetails'])->name('shops.modal-details');



    Route::get('/price-audit-logs', function () {
        $logs = \App\Models\PriceAuditLog::with(['user', 'shop'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($l) => [
                'id'             => $l->id,
                'user'           => $l->user?->name ?? '—',
                'shop'           => $l->shop?->nama ?? 'Global',
                'action'         => $l->action,
                'harga_beli_lama'=> $l->harga_beli_lama,
                'harga_jual_lama'=> $l->harga_jual_lama,
                'harga_beli_baru'=> $l->harga_beli_baru,
                'harga_jual_baru'=> $l->harga_jual_baru,
                'created_at'     => $l->created_at,
            ]);
        return response()->json($logs);
    })->name('price-audit-logs.index');

    // Dashboard: get active prices per outlet for admin form
    Route::get('/dashboard/prices', function () {
        $shops = \App\Models\Shop::all();
        $result = $shops->map(function ($shop) {
            $active = \App\Models\Price::where('shop_id', $shop->id)
                ->where('effective_at', '<=', now())
                ->orderBy('effective_at', 'desc')
                ->first();
            if (!$active) {
                $active = \App\Models\Price::whereNull('shop_id')
                    ->where('effective_at', '<=', now())
                    ->orderBy('effective_at', 'desc')
                    ->first();
            }
            return [
                'shop_id'    => $shop->id,
                'shop_nama'  => $shop->nama,
                'shop_kode'  => $shop->kode,
                'harga_beli' => $active ? $active->harga_beli : 0,
                'harga_jual' => $active ? $active->harga_jual : 0,
                'effective_at' => $active ? $active->effective_at : null,
            ];
        });
        return response()->json($result);
    })->name('dashboard.prices.index')->middleware(['auth']);

    // Dashboard: store new price from admin
    Route::post('/dashboard/prices', [PriceController::class, 'storeFromDashboard'])
        ->name('dashboard.prices.store')
        ->middleware(['auth']);
});

// Helper URL satu kali klik untuk menjalankan migrasi & clear cache di hosting (production)
Route::get('/migrasi-server', function () {
    try {
        $log = [];

        // 1. Auto-fix duplicate column migration jika kolom processing_status di backdate_excel_files sudah ada
        if (\Illuminate\Support\Facades\Schema::hasTable('backdate_excel_files') && 
            \Illuminate\Support\Facades\Schema::hasColumn('backdate_excel_files', 'processing_status')) {
            $migName = '2026_08_30_040000_add_processing_fields_to_backdate_excel_files_table';
            $exists = \Illuminate\Support\Facades\DB::table('migrations')->where('migration', $migName)->exists();
            if (!$exists) {
                $maxBatch = (int) (\Illuminate\Support\Facades\DB::table('migrations')->max('batch') ?? 1);
                \Illuminate\Support\Facades\DB::table('migrations')->insert([
                    'migration' => $migName,
                    'batch' => $maxBatch + 1
                ]);
                $log[] = "Migration '$migName' ditandai selesai (kolom sudah ada di database).";
            }
        }

        // 2. Jalankan migrasi
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $outputMigrate = \Illuminate\Support\Facades\Artisan::output();
        $log[] = $outputMigrate;

        // 3. Fallback: Pastikan tabel so_orders & so_order_trips dibuat jika belum ada
        if (!\Illuminate\Support\Facades\Schema::hasTable('so_orders')) {
            \Illuminate\Support\Facades\Schema::create('so_orders', function (\Illuminate\Database\Schema\Blueprint $table) {
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
            $log[] = "Tabel 'so_orders' berhasil dibuat.";
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('so_order_trips')) {
            \Illuminate\Support\Facades\Schema::create('so_order_trips', function (\Illuminate\Database\Schema\Blueprint $table) {
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
            $log[] = "Tabel 'so_order_trips' berhasil dibuat.";
        }

        // 4. Update kolom status shift_schedules agar mendukung status libur
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `shift_schedules` MODIFY `status` VARCHAR(30) NOT NULL DEFAULT 'dijadwalkan'");
            $log[] = "Kolom status pada 'shift_schedules' diperbarui (mendukung status libur).";
        } catch (\Throwable $e) {}

        // 5. Pastikan kolom-kolom tracking cicilan pada 'employee_loans' tersedia dan valid
        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('employee_loans', 'tenor_bulan')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `employee_loans` ADD COLUMN `tenor_bulan` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `jumlah`");
                $log[] = "Kolom 'tenor_bulan' ditambahkan ke employee_loans.";
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('employee_loans', 'nominal_angsuran')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `employee_loans` ADD COLUMN `nominal_angsuran` DECIMAL(12, 2) NOT NULL DEFAULT 0 AFTER `tenor_bulan`");
                $log[] = "Kolom 'nominal_angsuran' ditambahkan ke employee_loans.";
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('employee_loans', 'angsuran_ke')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `employee_loans` ADD COLUMN `angsuran_ke` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `nominal_angsuran`");
                $log[] = "Kolom 'angsuran_ke' ditambahkan ke employee_loans.";
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('employee_loans', 'sisa_pokok')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `employee_loans` ADD COLUMN `sisa_pokok` DECIMAL(12, 2) NOT NULL DEFAULT 0 AFTER `angsuran_ke`");
                $log[] = "Kolom 'sisa_pokok' ditambahkan ke employee_loans.";
            }
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `employee_loans` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'pending'");
            $log[] = "Kolom 'status' employee_loans dipastikan bertipe VARCHAR(20).";

            // Perbaikan data kasbon yang statusnya lunas prematur padahal baru di-approve & belum dicicil
            \Illuminate\Support\Facades\DB::statement("UPDATE `employee_loans` SET `status` = 'approved', `sisa_pokok` = `jumlah` WHERE `status` = 'lunas' AND (`angsuran_ke` = 0 OR `angsuran_ke` IS NULL) AND `approved_by` IS NOT NULL");
            // Perbaikan data kasbon yang sisa_pokok nya 0 padahal belum pernah dicicil
            \Illuminate\Support\Facades\DB::statement("UPDATE `employee_loans` SET `sisa_pokok` = `jumlah` WHERE (`sisa_pokok` <= 0 OR `sisa_pokok` IS NULL) AND (`angsuran_ke` = 0 OR `angsuran_ke` IS NULL) AND `status` != 'rejected'");
            
            // Perbaiki kasbon 5 juta yang sempat ter-default 1 bulan padahal diajukan 12 bulan
            \Illuminate\Support\Facades\DB::statement("UPDATE `employee_loans` SET `tenor_bulan` = 12, `nominal_angsuran` = ROUND(`jumlah` / 12, 2), `sisa_pokok` = `jumlah` WHERE `jumlah` = 5000000 AND `tenor_bulan` = 1 AND `angsuran_ke` = 0");
            $log[] = "Data kasbon, tenor 12 bulan, dan status approved berhasil diselaraskan.";
        } catch (\Throwable $e) {
            $log[] = "Catatan employee_loans schema: " . $e->getMessage();
        }

        // 6. Buat tabel tracking pemotongan kasbon payroll_loan_deductions jika belum ada
        if (!\Illuminate\Support\Facades\Schema::hasTable('payroll_loan_deductions')) {
            try {
                \Illuminate\Support\Facades\Schema::create('payroll_loan_deductions', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->foreignId('payroll_period_id')->nullable()->constrained('payroll_periods')->onDelete('cascade');
                    $table->foreignId('payroll_detail_id')->nullable()->constrained('payroll_details')->onDelete('cascade');
                    $table->foreignId('employee_loan_id')->constrained('employee_loans')->onDelete('cascade');
                    $table->unsignedInteger('cicilan_ke')->default(1);
                    $table->unsignedInteger('total_tenor')->default(1);
                    $table->decimal('nominal_potongan', 12, 2)->default(0);
                    $table->decimal('sisa_pokok_sebelum', 12, 2)->default(0);
                    $table->decimal('sisa_pokok_setelah', 12, 2)->default(0);
                    $table->boolean('is_finalized')->default(false);
                    $table->timestamps();
                });
                $log[] = "Tabel 'payroll_loan_deductions' berhasil dibuat.";
            } catch (\Throwable $e) {}
        }

        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');

        return response("
            <div style='font-family: sans-serif; padding: 30px; line-height: 1.6;'>
                <h2 style='color: #10B981;'>🎉 Migrasi & Setup Database Berhasil!</h2>
                <p>Tabel <code>so_orders</code> dan <code>so_order_trips</code> serta migrasi lainnya berhasil dijalankan.</p>
                <div style='background: #1E293B; color: #F8FAFC; padding: 15px; border-radius: 8px;'>
                    <pre style='margin:0;'>" . htmlspecialchars(implode("\n", $log)) . "</pre>
                </div>
                <br>
                <a href='/so-orders' style='display:inline-block; padding: 10px 20px; background: #2563EB; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;'>Buka Order SO Sekarang</a>
            </div>
        ");
    } catch (\Throwable $e) {
        return response("
            <div style='font-family: sans-serif; padding: 30px; line-height: 1.6;'>
                <h2 style='color: #EF4444;'>❌ Migrasi Gagal:</h2>
                <div style='background: #FEF2F2; color: #991B1B; padding: 15px; border-radius: 8px; border: 1px solid #FCA5A5;'>
                    <pre style='margin:0;'>" . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . "</pre>
                </div>
            </div>
        ", 500);
    }
});
