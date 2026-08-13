<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollOperatorAssignment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function payrollSystem()
    {
        return $this->belongsTo(PayrollSystem::class);
    }

    /**
     * Scope: assignment yang aktif pada tanggal tertentu.
     */
    public function scopeAktifPadaTanggal($query, string $tanggal)
    {
        return $query->where('tanggal_mulai', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', $tanggal);
            });
    }
}
