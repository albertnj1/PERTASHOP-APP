<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReportDeposit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function category()
    {
        return $this->belongsTo(DepositCategory::class, 'deposit_category_id');
    }
}
