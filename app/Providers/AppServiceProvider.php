<?php

namespace App\Providers;

use App\Services\Validation\BusinessValidator;
use App\Services\Validation\CarryForwardValidator;
use App\Services\Validation\DataQualityEngine;
use App\Services\Validation\FormulaValidator;
use App\Services\Validation\InputValidator;
use App\Services\Validation\PriceValidator;
use App\Services\Validation\ValidationPipeline;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind CarryForwardValidator dengan toleransi dari config
        $this->app->bind(CarryForwardValidator::class, function () {
            return new CarryForwardValidator(
                toliTolerance: (float) config('validation.carry_forward.totalisator_tolerance', 1.0),
                stokTolerance: (float) config('validation.carry_forward.stok_tolerance', 50.0)
            );
        });

        // Bind PriceValidator dengan toleransi dari config
        $this->app->bind(PriceValidator::class, function () {
            return new PriceValidator(
                priceTolerance: (float) config('validation.price.tolerance', 100.0)
            );
        });

        // ValidationPipeline: modular array of ValidatorInterface
        $this->app->bind(ValidationPipeline::class, function ($app) {
            return new ValidationPipeline(
                validators: [
                    $app->make(InputValidator::class),
                    $app->make(CarryForwardValidator::class),
                    $app->make(BusinessValidator::class),
                    $app->make(PriceValidator::class),
                    $app->make(FormulaValidator::class),
                ],
                dataQualityEngine: $app->make(DataQualityEngine::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \App\Models\DailyReport::observe(\App\Observers\DailyReportObserver::class);

        // RBAC Gate: Super Admin ALL-ACCESS (Melihat & Mengelola Semua Modul Tanpa Pembatasan)
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (in_array($user->role, ['super-admin', 'super_admin', 'admin'])) {
                return true;
            }
        });
    }
}

