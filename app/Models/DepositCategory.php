<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositCategory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'termasuk_dalam_setoran' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function deposits()
    {
        return $this->hasMany(DailyReportDeposit::class);
    }
}
