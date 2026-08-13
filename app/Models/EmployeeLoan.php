<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal'     => 'date',
        'jumlah'      => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
