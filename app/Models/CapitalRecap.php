<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapitalRecap extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
    
    protected static function booted()
    {
        static::saved(function ($recap) {
            static::recalculateForShop($recap->shop_id);
        });

        static::deleted(function ($recap) {
            static::recalculateForShop($recap->shop_id);
        });
    }

    public static function recalculateForShop($shopId)
    {
        $recaps = static::where('shop_id', $shopId)
            ->orderBy('tahun', 'asc')
            ->orderBy('bulan', 'asc')
            ->get();
        
        $accumulated = 0;
        $prevPosisiAkhir = null;
        
        foreach ($recaps as $index => $recap) {
            $nilaiModalAwal = floatval($recap->nilai_modal_awal);
            
            if ($index > 0 && $prevPosisiAkhir !== null) {
                $nilaiModalAwal = $prevPosisiAkhir;
                $recap->nilai_modal_awal = $nilaiModalAwal;
            }
            
            $nilaiPenambahan = floatval($recap->penyusutan_rugi) 
                + floatval($recap->penyusutan_pajak_bank) 
                + floatval($recap->penambahan_keuntungan) 
                + floatval($recap->penambahan_bunga_bank);
                
            $recap->nilai_penambahan_penyusutan = $nilaiPenambahan;
            
            $accumulated += $nilaiPenambahan;
            $recap->akumulasi_penambahan_penyusutan = $accumulated;
            
            $posisiAkhir = $nilaiModalAwal + $nilaiPenambahan;
            $recap->posisi_akhir_modal = $posisiAkhir;
            $prevPosisiAkhir = $posisiAkhir;
            
            if (floatval($recap->harga_beli_pertamax) > 0) {
                $recap->konversi_liter = $posisiAkhir / floatval($recap->harga_beli_pertamax);
            } else {
                $recap->konversi_liter = 0;
            }
            
            $recap->saveQuietly();
        }
    }
    
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
