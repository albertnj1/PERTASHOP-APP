<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShiftSwap extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'waktu_perubahan' => 'datetime',
        'approved_at'     => 'datetime',
    ];

    public function shiftSchedule()
    {
        return $this->belongsTo(ShiftSchedule::class);
    }

    public function operatorAsal()
    {
        return $this->belongsTo(Operator::class, 'operator_asal_id');
    }

    public function operatorPengganti()
    {
        return $this->belongsTo(Operator::class, 'operator_pengganti_id');
    }

    /**
     * Admin yang memproses pergantian shift.
     */
    public function diubahOleh()
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
