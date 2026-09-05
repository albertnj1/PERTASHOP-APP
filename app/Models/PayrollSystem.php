<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollSystem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tipe_skema'              => 'string',
        'ada_rate_per_liter'      => 'boolean',
        'ada_gaji_pokok'          => 'boolean',
        'aktif'                   => 'boolean',
        'rate_per_liter'          => 'decimal:2',
        'nominal_gaji_pokok'      => 'decimal:2',
        'potongan_per_hari_alpha'  => 'decimal:2',
        'rate_transport_per_hari' => 'decimal:2',
        'metode_potongan_alpha'   => 'string',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            // Sinkronisasi boolean flag berdasarkan tipe_skema
            if ($model->tipe_skema === 'komisi_murni') {
                $model->ada_rate_per_liter = true;
                $model->ada_gaji_pokok     = false;
                $model->nominal_gaji_pokok = null;
            } elseif ($model->tipe_skema === 'gaji_pokok_murni') {
                $model->ada_rate_per_liter = false;
                $model->rate_per_liter     = 0;
                $model->ada_gaji_pokok     = true;
            } elseif ($model->tipe_skema === 'hibrid') {
                $model->ada_rate_per_liter = true;
                $model->ada_gaji_pokok     = true;
            } else {
                if ($model->ada_gaji_pokok && $model->ada_rate_per_liter) {
                    $model->tipe_skema = 'hibrid';
                } elseif ($model->ada_gaji_pokok) {
                    $model->tipe_skema = 'gaji_pokok_murni';
                } else {
                    $model->tipe_skema = 'komisi_murni';
                }
            }
        });
    }

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

    public function isKomisiMurni(): bool
    {
        return ($this->tipe_skema ?? 'komisi_murni') === 'komisi_murni';
    }

    public function isGajiPokokMurni(): bool
    {
        return ($this->tipe_skema ?? '') === 'gaji_pokok_murni';
    }

    public function isHibrid(): bool
    {
        return ($this->tipe_skema ?? '') === 'hibrid';
    }

    public function getTipeSkemaLabelAttribute(): string
    {
        return match ($this->tipe_skema) {
            'gaji_pokok_murni' => 'Sistem Gaji Pokok Murni',
            'hibrid'           => 'Sistem Hibrid (Gaji Pokok + Komisi Liter)',
            default            => 'Sistem Komisi Murni (Total Liter × Tarif/L)',
        };
    }

    public function getTipeSkemaBadgeColorAttribute(): string
    {
        return match ($this->tipe_skema) {
            'gaji_pokok_murni' => '#8b5cf6', // purple
            'hibrid'           => '#059669', // emerald
            default            => '#0284c7', // blue
        };
    }

    public function getFormulaDescriptionAttribute(): string
    {
        return match ($this->tipe_skema) {
            'gaji_pokok_murni' => 'Gaji Pokok Rp ' . number_format($this->nominal_gaji_pokok ?? 0, 0, ',', '.'),
            'hibrid'           => 'Gaji Pokok Rp ' . number_format($this->nominal_gaji_pokok ?? 0, 0, ',', '.') . ' + (Total Liter × Rp ' . number_format($this->rate_per_liter ?? 0, 0, ',', '.') . '/L)',
            default            => 'Total Liter × Rp ' . number_format($this->rate_per_liter ?? 0, 0, ',', '.') . '/L',
        };
    }
}

