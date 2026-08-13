<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorOutletAssignment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'ownership_percentage' => 'float',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}
