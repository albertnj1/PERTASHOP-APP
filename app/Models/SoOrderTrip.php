<?php

namespace App\Models;

use App\Models\SoOrder;
use App\Models\Incoming;
use App\Models\DailyReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SoOrderTrip extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'estimated_delivery_date' => 'date',
        'quantity_liter' => 'float',
        'received_at' => 'datetime',
    ];

    // ── Relationships ───────────────────────────────────────────────────

    public function soOrder()
    {
        return $this->belongsTo(SoOrder::class);
    }

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function incoming()
    {
        return $this->belongsTo(Incoming::class);
    }

    // ── Accessors ───────────────────────────────────────────────────────

    /**
     * Label untuk dropdown Operator.
     * Format: "[SO-NUMBER] - Trip 1 dari 2 (Vol: 2.000 L | Tgl Kirim: 26/09/2026)"
     */
    public function getDropdownLabelAttribute(): string
    {
        $so = $this->soOrder;
        $totalTrips = $so->total_trips;
        $volume = number_format($this->quantity_liter, 0, ',', '.');
        $tanggal = $this->estimated_delivery_date->format('d/m/Y');

        return "[{$so->so_number}] - Trip {$this->trip_number} dari {$totalTrips} (Vol: {$volume} L | Tgl Kirim: {$tanggal})";
    }

    // ── Business Logic ──────────────────────────────────────────────────

    /**
     * Tandai trip ini sebagai RECEIVED dan sinkronisasi status parent SO.
     */
    public function markAsReceived(?int $dailyReportId = null, ?int $incomingId = null): void
    {
        $this->status = 'RECEIVED';
        $this->received_at = now();
        $this->daily_report_id = $dailyReportId;
        $this->incoming_id = $incomingId;
        $this->save();

        // Sinkronisasi status parent
        $this->soOrder->syncStatus();
    }

    /**
     * Kembalikan trip ke PENDING (untuk kasus undo / koreksi).
     */
    public function markAsPending(): void
    {
        $this->status = 'PENDING';
        $this->received_at = null;
        $this->daily_report_id = null;
        $this->incoming_id = null;
        $this->save();

        $this->soOrder->syncStatus();
    }
}
