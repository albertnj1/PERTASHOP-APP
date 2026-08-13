<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class BusinessRule extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_system_rule' => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(BusinessRuleVersion::class)->orderBy('effective_from', 'desc');
    }

    /**
     * Resolves versi aturan bisnis yang aktif pada tanggal tertentu tanpa tumpang tindih (no overlapping).
     */
    public function resolveActiveVersion(mixed $date = null): ?BusinessRuleVersion
    {
        $evalDateStr = Carbon::parse($date ?? now())->format('Y-m-d H:i:s');

        return BusinessRuleVersion::where('business_rule_id', $this->id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $evalDateStr)
            ->where(function ($query) use ($evalDateStr) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', $evalDateStr);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }
}
