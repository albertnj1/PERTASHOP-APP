<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PeriodLock extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Safety Guard Helper: Cek apakah periode (shop_id + tanggal/bulan) sedang terkunci.
     */
    public static function isLocked(int $shopId, string $dateOrMonth): bool
    {
        $yearMonth = strlen($dateOrMonth) === 7 
            ? $dateOrMonth 
            : Carbon::parse($dateOrMonth)->format('Y-m');

        $lock = self::where('shop_id', $shopId)
            ->where('year_month', $yearMonth)
            ->first();

        return $lock ? (bool) $lock->is_locked : false;
    }
}
