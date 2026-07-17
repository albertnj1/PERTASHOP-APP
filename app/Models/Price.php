<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'harga_beli',
        'harga_jual',
        'effective_at',
        'totalisator_perubahan',
        'lokasi_device',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
