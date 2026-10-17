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
