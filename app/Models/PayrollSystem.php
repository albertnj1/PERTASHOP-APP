<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollSystem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'ada_rate_per_liter'      => 'boolean',
        'ada_gaji_pokok'          => 'boolean',
        'aktif'                   => 'boolean',
        'rate_per_liter'          => 'decimal:2',
        'nominal_gaji_pokok'      => 'decimal:2',
        'potongan_per_hari_alpha'  => 'decimal:2',
        'rate_transport_per_hari' => 'decimal:2',
        'metode_potongan_alpha'   => 'string',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function assignments()
    {
        return $this->hasMany(PayrollOperatorAssignment::class);
    }

    public function periods()
    {
        return $this->hasMany(PayrollPeriod::class);
    }

    /**
     * Hitung volume_dihitung berdasarkan konfigurasi perlakuan_losses_gain.
     *
     * Sesuai spesifikasi:
     * - "Losses" (losses_gain < 0): Volume_Dihitung = Volume_Aktual + losses_gain (losses_gain negatif, jadi mengurangi)
     * - "Gain"   (losses_gain > 0): Volume_Dihitung = Volume_Aktual (gain diabaikan pada losses_only)
     * - "Pas"    (losses_gain = 0): Volume_Dihitung = Volume_Aktual (Pas diperlakukan sama dengan Gain — tidak mengurangi)
     *
     * Catatan: Kondisi `$lossesGain < 0` sudah secara otomatis mengeksklusi kasus "Pas" (== 0)
     * sehingga tidak ada perubahan logika yang diperlukan — hanya dokumentasi diperjelas.
     *
     * @param float $volumePenjualanAktual
     * @param float $lossesGain  (negatif = losses/Losses, nol = Pas, positif = Gain)
     * @return float
     */
    public function hitungVolumeDihitung(float $volumePenjualanAktual, float $lossesGain): float
    {
        return match ($this->perlakuan_losses_gain) {
            // Losses dikurangi, Gain & Pas diabaikan
            // lossesGain < 0  → Losses: kurangi
            // lossesGain == 0 → Pas:    tidak mengurangi (masuk cabang else)
            // lossesGain > 0  → Gain:   tidak menambah (masuk cabang else)
            'losses_only' => $lossesGain < 0
                ? $volumePenjualanAktual + $lossesGain
                : $volumePenjualanAktual,
            // Keduanya berlaku (plus/minus) — Pas tetap 0 efeknya
            'losses_dan_gain' => $volumePenjualanAktual + $lossesGain,
            // Losses/gain tidak dihitung ke gaji sama sekali
            'abaikan_losses_gain' => $volumePenjualanAktual,
            default => $volumePenjualanAktual,
        };
    }

    public function getPerlakuanLossesGainLabelAttribute(): string
    {
        return match ($this->perlakuan_losses_gain) {
            'losses_only'          => 'Losses Saja (Gain Diabaikan)',
            'losses_dan_gain'      => 'Losses & Gain (Plus/Minus)',
            'abaikan_losses_gain'  => 'Abaikan Losses/Gain',
            default                => $this->perlakuan_losses_gain,
        };
    }

    public function getMetodeSplitLabelAttribute(): string
    {
        return match ($this->metode_split) {
            'per_hari_penuh'            => 'Satu Hari Penuh',
            'proporsional_jam_kerja'    => 'Proporsional per Shift',
            'flat_bulanan_prorata_hari' => 'Flat Bulanan Prorata Hari Kerja',
            default                     => $this->metode_split,
        };
    }
}
