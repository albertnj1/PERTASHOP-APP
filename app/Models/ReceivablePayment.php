<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReceivablePayment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_bayar' => 'date',
        'jumlah_bayar'  => 'float',
    ];

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
