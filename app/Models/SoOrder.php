<?php

namespace App\Models;

use App\Models\Shop;
use App\Models\Supplier;
use App\Models\SoOrderTrip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SoOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'order_date' => 'date',
        'total_quantity_liter' => 'float',
        'total_amount' => 'float',
    ];

    // ── Relationships ───────────────────────────────────────────────────

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function trips()
    {
        return $this->hasMany(SoOrderTrip::class)->orderBy('trip_number');
    }

    // ── Accessors ───────────────────────────────────────────────────────

    /**
     * Jumlah trip yang sudah berstatus RECEIVED.
     */
    public function getReceivedTripsCountAttribute(): int
    {
        return $this->trips()->where('status', 'RECEIVED')->count();
    }

    /**
     * Jumlah trip yang masih PENDING.
     */
    public function getPendingTripsCountAttribute(): int
    {
        return $this->trips()->where('status', 'PENDING')->count();
    }

    // ── Business Logic ──────────────────────────────────────────────────

    /**
     * Sinkronisasi status parent berdasarkan status seluruh child trips.
     * Dipanggil setiap kali sebuah trip di-update statusnya.
     */
    public function syncStatus(): void
    {
        $totalTrips = $this->trips()->count();
        $receivedTrips = $this->trips()->where('status', 'RECEIVED')->count();

        if ($receivedTrips === 0) {
            $this->status = 'PENDING';
        } elseif ($receivedTrips < $totalTrips) {
            $this->status = 'PARTIAL';
        } else {
            $this->status = 'COMPLETED';
        }

        $this->save();
    }
}
