<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollDailySplit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal'                  => 'date',
        'volume_penjualan_aktual'  => 'decimal:3',
        'volume_dihitung'          => 'decimal:3',
        'liter_bagian'             => 'decimal:3',
        'proporsi'                 => 'decimal:4',
    ];

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
