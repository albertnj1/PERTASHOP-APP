<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReportUpload extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = [
        'volume_terjual',
        'harga_jual',
        'pendapatan_operator',
        'jumlah_pendapatan_bersih',
        'jumlah_disetorkan'
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
    
    public function getVolumeTerjualAttribute()
    {
        return $this->totalisator_akhir - $this->totalisator_awal - $this->test_pump;
    }

    public function getHargaJualAttribute()
    {
        $active = \App\Models\Price::where(function($q) {
            $q->where('shop_id', $this->shop_id)->orWhereNull('shop_id');
        })
        ->where('effective_at', '<=', $this->tanggal . ' 23:59:59')
        ->orderBy('effective_at', 'desc')
        ->first();

        return $active ? $active->harga_jual : 0;
    }

    public function getPendapatanOperatorAttribute()
    {
        return $this->volume_terjual * $this->harga_jual;
    }

    public function getJumlahPendapatanBersihAttribute()
    {
        return $this->pendapatan_operator; // Berdasarkan definisi user: total cash + qris
    }

    public function getJumlahDisetorkanAttribute()
    {
        return $this->pendapatan_operator - $this->qris - $this->pengeluaran;
    }
}
