<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReportDeposit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updated(function ($deposit) {
            if (class_exists(\App\Models\AuditLog::class) && \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
                $dirty = $deposit->getDirty();
                if (!empty($dirty)) {
                    $original = array_intersect_key($deposit->getOriginal(), $dirty);
                    \App\Models\AuditLog::log(
                        action: 'UPDATE_DEPOSIT',
                        modelType: 'DailyReportDeposit',
                        modelId: $deposit->id,
                        description: "Perubahan data rincian setoran ID #{$deposit->id} (laporan {$deposit->daily_report_id})",
                        oldValues: $original,
                        newValues: $dirty
                    );
                }
            }
        });
    }

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function category()
    {
        return $this->belongsTo(DepositCategory::class, 'deposit_category_id');
    }
}
