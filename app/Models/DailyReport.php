<?php

namespace App\Models;

use App\Models\Shop;
use App\Models\Price;
use App\Models\Incoming;
use App\Models\Operator;
use App\Models\Spending;
use App\Models\TestPump;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyReport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::saved(function ($report) {
            if ($report->sumber_data === 'import_excel_arsip') {
                return; // Data arsip backdate, menghentikan seluruh proses operasional lanjutan
            }
        });
    }

    protected $appends = [
        'totalisator_awal',
        'stik_awal',
        'stok_awal',
        'stok_akhir_aktual',
        'test_pump',
        'penerimaan',
        'volume_penjualan',
        'stok_akhir_teoritis',
        'losses_gain',
        'losses_gain_rupiah',
        'losses_gain_kategori',
        'pengeluaran',
        'rupiah_penjualan',
        'pendapatan',
        'disetorkan',
        'selisih_setoran',
        'belum_disetorkan',
        'volume_penjualan_teoritis',
        'rupiah_penjualan_teoritis',
        'volume_penjualan_aktual',
        'rupiah_penjualan_aktual'
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function incomings()
    {
        return $this->hasMany(Incoming::class);
    }

    public function testPumps()
    {
        return $this->hasMany(TestPump::class);
    }

    public function spendings()
    {
        return $this->hasMany(Spending::class);
    }

    public function price()
    {
        return $this->belongsTo(Price::class);
    }

    public function excelUpload()
    {
        return $this->belongsTo(ExcelUpload::class, 'excel_upload_id');
    }

    public function periods()
    {
        return $this->hasMany(DailyReportPeriod::class);
    }

    protected static $allReportsByShop = [];
    protected static $allReportsByOperator = [];

    public function latestByShop()
    {
        if (!isset(self::$allReportsByShop[$this->shop_id])) {
            self::$allReportsByShop[$this->shop_id] = self::where('shop_id', $this->shop_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $currentTimestamp = strtotime($this->created_at);
        return self::$allReportsByShop[$this->shop_id]
            ->first(function ($report) use ($currentTimestamp) {
                return strtotime($report->created_at) < $currentTimestamp;
            });
    }

    public function latestByOperator()
    {
        if (!isset(self::$allReportsByOperator[$this->operator_id])) {
            self::$allReportsByOperator[$this->operator_id] = self::where('operator_id', $this->operator_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $currentTimestamp = strtotime($this->created_at);
        return self::$allReportsByOperator[$this->operator_id]
            ->first(function ($report) use ($currentTimestamp) {
                return strtotime($report->created_at) < $currentTimestamp;
            });
    }

    public function getTotalisatorAwalAttribute()
    {
        if (isset($this->attributes['totalisator_awal']) && $this->attributes['totalisator_awal'] !== null) {
            return floatval($this->attributes['totalisator_awal']);
        }
        return $this->latestByShop() ?  $this->latestByShop()->totalisator_akhir : $this->shop->totalisator_awal;
    }

    public function getStikAwalAttribute()
    {
        return  $this->latestByShop() ?  $this->latestByShop()->stik_akhir : $this->shop->stik_awal;
    }

    public function getStokAwalAttribute()
    {
        if (isset($this->attributes['stok_awal']) && $this->attributes['stok_awal'] !== null) {
            return floatval($this->attributes['stok_awal']);
        }
        return $this->stik_awal * $this->shop->skala;
    }

    public function getStokAkhirAktualAttribute()
    {
        return $this->stik_akhir * $this->shop->skala;
    }

    public function getTestPumpAttribute()
    {
        if (isset($this->attributes['test_pump_volume']) && floatval($this->attributes['test_pump_volume']) > 0) {
            return floatval($this->attributes['test_pump_volume']);
        }
        return $this->testPumps->sum('volume');
    }

    public function getPenerimaanAttribute()
    {
        if (isset($this->attributes['penerimaan_volume']) && floatval($this->attributes['penerimaan_volume']) > 0) {
            return floatval($this->attributes['penerimaan_volume']);
        }
        return $this->incomings->sum('volume');
    }

    public function getVolumePenjualanTeoritisAttribute()
    {
        $ta = floatval($this->totalisator_akhir);
        if ($ta <= 0) {
            return 0;
        }
        $awal = floatval($this->totalisator_awal);
        
        if ($this->periods()->exists()) {
            $totalVolume = 0;
            foreach ($this->periods as $period) {
                $totalVolume += (floatval($period->totalisator_akhir) - floatval($period->totalisator_awal));
            }
            return $totalVolume;
        }
        return max(0.0, $ta - $awal);
    }

    public function getRupiahPenjualanTeoritisAttribute()
    {
        if ($this->periods()->exists()) {
            $totalRupiah = 0;
            foreach ($this->periods as $period) {
                $vol = floatval($period->totalisator_akhir) - floatval($period->totalisator_awal);
                $totalRupiah += ($vol * ($period->price ? floatval($period->price->harga_jual) : 0));
            }
            return $totalRupiah;
        }
        $priceJual = $this->price ? floatval($this->price->harga_jual) : 0;
        return $this->volume_penjualan_teoritis * $priceJual;
    }

    public function getVolumePenjualanAktualAttribute()
    {
        // Volume Aktual = Volume_Teoritis - Test_Pump - BBM_Lain2
        // BBM_Lain2 = BBM keluar tapi bukan penjualan (jarang, default 0)
        $bbmLain2 = floatval($this->attributes['bbm_keluar_lain'] ?? 0);
        return max(0.0, $this->volume_penjualan_teoritis - $this->test_pump - $bbmLain2);
    }

    public function getRupiahPenjualanAktualAttribute()
    {
        if ($this->periods()->exists()) {
            $totalRupiah = 0;
            $lastPriceJual = 0;
            foreach ($this->periods as $period) {
                $vol = floatval($period->totalisator_akhir) - floatval($period->totalisator_awal);
                $totalRupiah += ($vol * ($period->price ? floatval($period->price->harga_jual) : 0));
                $lastPriceJual = $period->price ? floatval($period->price->harga_jual) : 0;
            }
            return max(0.0, $totalRupiah - ($this->test_pump * $lastPriceJual));
        }
        $priceJual = $this->price ? floatval($this->price->harga_jual) : 0;
        return $this->volume_penjualan_aktual * $priceJual;
    }

    public function getVolumePenjualanAttribute()
    {
        return $this->volume_penjualan_aktual;
    }

    public function getRupiahPenjualanAttribute()
    {
        return $this->rupiah_penjualan_aktual;
    }

    public function getStokAkhirTeoritisAttribute()
    {
        // Stok_Teoritis = Stok_Awal + Terima_BBM - BBM_Lain2 - Volume_Aktual
        // BBM_Lain2 dikurangi karena stok berkurang akibat BBM keluar non-penjualan
        $bbmLain2 = floatval($this->attributes['bbm_keluar_lain'] ?? 0);
        return round($this->stok_awal + $this->penerimaan - $bbmLain2 - $this->volume_penjualan_aktual, 3);
    }

    public function getPenjualanLossesDAttribute()
    {
        return max(0.0, $this->volume_penjualan_aktual + $this->losses_gain);
    }

    public function getLossesGainAttribute()
    {
        return round($this->stok_akhir_aktual - $this->stok_akhir_teoritis, 3);
    }

    /**
     * Kategori Losses/Gain dengan 3 nilai: "Losses", "Gain", "Pas".
     * - "Pas"    : losses_gain == 0 (tepat, tidak ada selisih stok)
     * - "Losses" : losses_gain < 0  (stok aktual < teoritis — dikurangi ke gaji jika perlakuan_losses_gain = losses_only)
     * - "Gain"   : losses_gain > 0  (stok aktual > teoritis — tidak menambah gaji pada perlakuan_losses_gain = losses_only)
     */
    public function getLossesGainKategoriAttribute(): string
    {
        $lg = $this->losses_gain;
        if ($lg == 0) return 'Pas';
        return $lg < 0 ? 'Losses' : 'Gain';
    }

    public function getLossesGainRupiahAttribute()
    {
        $priceBeli = 0;
        if ($this->periods()->exists() && $this->periods->last() && $this->periods->last()->price) {
            $priceBeli = floatval($this->periods->last()->price->harga_beli);
        } else {
            $priceBeli = $this->price ? floatval($this->price->harga_beli) : 0;
        }
        return $this->losses_gain * $priceBeli;
    }

    public function getPengeluaranAttribute()
    {
        return $this->spendings->sum('jumlah');
    }

    public function getPendapatanAttribute()
    {
        return $this->rupiah_penjualan_aktual - $this->pengeluaran;
    }

    public function kolektan()
    {
        return $this->belongsTo(Kolektan::class);
    }

    public function deposits()
    {
        return $this->hasMany(DailyReportDeposit::class);
    }

    public function getDisetorkanAttribute()
    {
        $hasDeposits = $this->relationLoaded('deposits') ? $this->deposits->isNotEmpty() : $this->deposits()->exists();
        if ($hasDeposits) {
            $deposits = $this->relationLoaded('deposits') ? $this->deposits : $this->deposits()->with('category')->get();
            return floatval($deposits->filter(fn($d) => $d->category && $d->category->termasuk_dalam_setoran)->sum('jumlah'));
        }
        return floatval($this->setor_tunai ?? 0) + floatval($this->setor_qris ?? 0) + floatval($this->setor_transfer ?? 0) + floatval($this->setor_kolektan ?? 0);
    }

    public function getSelisihSetoranAttribute()
    {
        // Refactor (bukan bug fix) — formula eksplisit dari komponen primitif:
        // Selisih_Setoran = (Total_Pengeluaran + Total_Setoran) - Rupiah_Aktual
        // Ini matematically equivalent dengan: disetorkan - (rupiah_aktual - pengeluaran)
        // = disetorkan - pendapatan  (karena pendapatan = rupiah_aktual - pengeluaran)
        // Positif = lebih setor, Negatif = kurang setor.
        return ($this->pengeluaran + $this->disetorkan) - $this->rupiah_penjualan_aktual;
    }

    public function getBelumDisetorkanAttribute()
    {
        $kemarin = $this->latestByOperator() ? floatval($this->latestByOperator()->belum_disetorkan) : 0;
        return $kemarin + $this->pendapatan - $this->disetorkan;
    }
}
