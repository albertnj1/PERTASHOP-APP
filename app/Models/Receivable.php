<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Receivable extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal'        => 'date',
        'jumlah_piutang' => 'float',
        'jumlah_dibayar' => 'float',
    ];

    protected $appends = [
        'sisa_piutang',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function payments()
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    public function getSisaPiutangAttribute(): float
    {
        return max(0.0, floatval($this->jumlah_piutang) - floatval($this->jumlah_dibayar));
    }
}
