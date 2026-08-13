<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'liter_bagian'          => 'decimal:2',
        'gaji_variable'         => 'decimal:2',
        'gaji_pokok'            => 'decimal:2',
        'lembur'                => 'decimal:2',
        'lembur_hari_raya'      => 'decimal:2',
        'bonus'                 => 'decimal:2',
        'thr'                   => 'decimal:2',
        'uang_transport'        => 'decimal:2',
        'potongan_tidak_masuk'  => 'decimal:2',
        'kurang_setoran'        => 'decimal:2',
        'tabungan_gaji'         => 'decimal:2',
        'tabungan_setoran'      => 'decimal:2',
        'potongan_hutang'       => 'decimal:2',
        'take_home_pay'         => 'decimal:2',
        'sisa_kurang_bayar'     => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function items()
    {
        return $this->hasMany(PayrollDetailItem::class, 'payroll_detail_id');
    }

    /**
     * Total Gaji Kotor = Gaji Pokok + Gaji Variable + Uang Transport + Lembur + Lembur HR + Bonus + THR + Item Tambahan
     * Uang Transport dihitung per hari kerja (bukan flat bulanan).
     */
    public function getTotalGajiKotorAttribute(): float
    {
        $tambahanItems = $this->relationLoaded('items') 
            ? $this->items->where('tipe', 'tambahan')->sum('jumlah')
            : $this->items()->where('tipe', 'tambahan')->sum('jumlah');

        return floatval($this->gaji_pokok)
            + floatval($this->gaji_variable)
            + floatval($this->uang_transport)
            + floatval($this->lembur)
            + floatval($this->lembur_hari_raya)
            + floatval($this->bonus)
            + floatval($this->thr)
            + floatval($tambahanItems);
    }

    /**
     * Total Potongan = Potongan Tidak Masuk + Kurang Setoran + Tabungan Gaji + Tabungan Setoran + Potongan Hutang + Item Potongan
     */
    public function getTotalPotonganAttribute(): float
    {
        $potonganItems = $this->relationLoaded('items')
            ? $this->items->where('tipe', 'potongan')->sum('jumlah')
            : $this->items()->where('tipe', 'potongan')->sum('jumlah');

        return floatval($this->potongan_tidak_masuk)
            + floatval($this->kurang_setoran)
            + floatval($this->tabungan_gaji)
            + floatval($this->tabungan_setoran)
            + floatval($this->potongan_hutang)
            + floatval($potonganItems);
    }

    /**
     * Take Home Pay = Total Gaji Kotor - Total Potongan (Bisa Negatif jika potongan > kotor)
     */
    public function hitungTHP(): float
    {
        return round($this->total_gaji_kotor - $this->total_potongan, 2);
    }

    /**
     * Re-hitung dan simpan THP ke database beserta sisa_kurang_bayar.
     */
    public function recalculateTHP(): void
    {
        $thp = $this->hitungTHP();
        $this->take_home_pay = $thp;
        $this->sisa_kurang_bayar = $thp < 0 ? abs($thp) : 0;
        $this->save();
    }
}
