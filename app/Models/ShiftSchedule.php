<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShiftSchedule extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal'    => 'date',
        'jam_mulai'  => 'string',
        'jam_selesai' => 'string',
    ];

    /**
     * Boot: setelah status diubah menjadi 'hadir' / 'alpha' / dll,
     * trigger recalculate AttendanceRecap untuk operator tersebut.
     */
    protected static function booted(): void
    {
        static::saved(function (ShiftSchedule $schedule) {
            $date = \Carbon\Carbon::parse($schedule->tanggal);
            AttendanceRecap::recalculateForOperator(
                $schedule->operator_id,
                $schedule->shop_id,
                (int) $date->format('m'),
                (int) $date->format('Y')
            );
        });

        static::deleted(function (ShiftSchedule $schedule) {
            $date = \Carbon\Carbon::parse($schedule->tanggal);
            AttendanceRecap::recalculateForOperator(
                $schedule->operator_id,
                $schedule->shop_id,
                (int) $date->format('m'),
                (int) $date->format('Y')
            );
        });
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function swaps()
    {
        return $this->hasMany(ShiftSwap::class);
    }

    /**
     * Apakah slot ini pernah diganti operatornya?
     */
    public function wasSwapped(): bool
    {
        return $this->swaps()->exists();
    }
}
