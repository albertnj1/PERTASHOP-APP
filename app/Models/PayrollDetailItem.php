<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDetailItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    public function detail()
    {
        return $this->belongsTo(PayrollDetail::class, 'payroll_detail_id');
    }
}
