<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'generated_at'          => 'datetime',
        'approved_at'           => 'datetime',
        'total_penjualan_liter' => 'decimal:2',
        'rule_version_snapshot' => 'array',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payrollSystem()
    {
        return $this->belongsTo(PayrollSystem::class);
    }

    public function details()
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function dailySplits()
    {
        return $this->hasMany(PayrollDailySplit::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Nama bulan dalam bahasa Indonesia.
     */
    public function getNamaBulanAttribute(): string
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return $bulan[$this->bulan] ?? '-';
    }

    public function getPeriodeLabelAttribute(): string
    {
        return $this->nama_bulan . ' ' . $this->tahun;
    }
}
