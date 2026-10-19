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

    public function confirmations()
    {
        return $this->hasMany(PriceConfirmation::class);
    }
}

if (!class_exists('App\Models\PriceConfirmation')) {
    class PriceConfirmation extends Model
    {
        use HasFactory;

        protected $table = 'price_confirmations';

        protected $fillable = [
            'price_id',
            'shop_id',
            'operator_id',
            'status_operator',
            'foto_bukti',
            'dikonfirmasi_pada',
            'catatan',
        ];

        protected $casts = [
            'dikonfirmasi_pada' => 'datetime',
        ];

        public function price()
        {
            return $this->belongsTo(Price::class);
        }

        public function shop()
        {
            return $this->belongsTo(Shop::class);
        }

        public function operator()
        {
            return $this->belongsTo(Operator::class);
        }
    }
}
