<?php

namespace App\Models;

use App\Models\Operator;
use App\Models\SpendingCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Spending extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updated(function ($spending) {
            if (class_exists(\App\Models\AuditLog::class) && \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
                $dirty = $spending->getDirty();
                if (!empty($dirty)) {
                    $original = array_intersect_key($spending->getOriginal(), $dirty);
                    \App\Models\AuditLog::log(
                        action: 'UPDATE_SPENDING',
                        modelType: 'Spending',
                        modelId: $spending->id,
                        description: "Perubahan data pengeluaran ID #{$spending->id} (kategori {$spending->spending_category_id})",
                        oldValues: $original,
                        newValues: $dirty
                    );
                }
            }
        });
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(SpendingCategory::class, 'spending_category_id');
    }

    public function spendingCategory()
    {
        return $this->category();
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
