<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSavings extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah'  => 'decimal:2',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
