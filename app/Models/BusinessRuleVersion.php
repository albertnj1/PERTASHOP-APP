<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessRuleVersion extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'value_numeric' => 'float',
        'value_json'    => 'array',
        'is_active'     => 'boolean',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BusinessRule::class, 'business_rule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Dapatkan nilai mentah sesuai tipe data aturan.
     */
    public function getRawValue(): mixed
    {
        if ($this->value_numeric !== null) return (float) $this->value_numeric;
        if ($this->value_json !== null) return $this->value_json;
        return $this->value_string;
    }
}
