<?php

namespace App\Models;

use App\Models\Shop;
use App\Models\Operator;
use App\Models\Purchase;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Incoming extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_pertamax' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updated(function ($incoming) {
            if (class_exists(\App\Models\AuditLog::class) && \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
                $dirty = $incoming->getDirty();
                if (!empty($dirty)) {
                    $original = array_intersect_key($incoming->getOriginal(), $dirty);
                    \App\Models\AuditLog::log(
                        action: 'UPDATE_INCOMING_PURCHASE',
                        modelType: 'Incoming',
                        modelId: $incoming->id,
                        description: "Perubahan data penerimaan/pembelian BBM ID #{$incoming->id} (laporan {$incoming->daily_report_id})",
                        oldValues: $original,
                        newValues: $dirty
                    );
                }
            }
        });
    }

    protected $appends = ['volume_aktual'];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function soOrderTrip()
    {
        return $this->hasOne(SoOrderTrip::class);
    }

    public function getVolumeAktualAttribute()
    {
        return ($this->stik_akhir - $this->stik_awal) * $this->shop->skala;
    }
}
