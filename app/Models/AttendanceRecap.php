<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceRecap extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Hitung ulang rekap kehadiran per operator per bulan.
     * Pola ini sama dengan CapitalRecap::recalculateForShop().
     * Dipanggil otomatis oleh ShiftSchedule::booted().
     */
    public static function recalculateForOperator(
        int $operatorId,
        int $shopId,
        int $bulan,
        int $tahun
    ): void {
        $totals = ShiftSchedule::where('operator_id', $operatorId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('
                COUNT(*) as total_dijadwalkan,
                SUM(CASE WHEN status = "hadir"  THEN 1 ELSE 0 END) as total_hadir,
                SUM(CASE WHEN status = "alpha"  THEN 1 ELSE 0 END) as total_alpha,
                SUM(CASE WHEN status = "izin"   THEN 1 ELSE 0 END) as total_izin,
                SUM(CASE WHEN status = "sakit"  THEN 1 ELSE 0 END) as total_sakit
            ')
            ->first();

        static::updateOrCreate(
            [
                'operator_id' => $operatorId,
                'bulan'       => $bulan,
                'tahun'       => $tahun,
            ],
            [
                'shop_id'            => $shopId,
                'total_dijadwalkan'  => $totals->total_dijadwalkan ?? 0,
                'total_hadir'        => $totals->total_hadir ?? 0,
                'total_alpha'        => $totals->total_alpha ?? 0,
                'total_izin'         => $totals->total_izin ?? 0,
                'total_sakit'        => $totals->total_sakit ?? 0,
            ]
        );
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
