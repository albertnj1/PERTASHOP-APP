<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopStartingBalance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'year'               => 'integer',
        'month'              => 'integer',
        'totalisator_awal'   => 'float',
        'stok_awal'          => 'float',
        'uang_belum_disetor' => 'float',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}

