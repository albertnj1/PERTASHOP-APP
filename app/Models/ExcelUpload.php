<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExcelUpload extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function operasionals()
    {
        return $this->hasMany(ExcelOperasional::class, 'upload_id');
    }

    public function setorans()
    {
        return $this->hasMany(ExcelSetoran::class, 'upload_id');
    }

    public function rekap()
    {
        return $this->hasOne(ExcelRekap::class, 'upload_id');
    }

    public function changeLogs()
    {
        return $this->hasMany(ExcelChangeLog::class, 'upload_id');
    }

    /**
     * Get operasional rows with 100% accurate dynamic calculations.
     */
    public function getCalculatedOperasionals()
    {
        $rows = $this->operasionals()->orderBy('tanggal')->orderBy('id')->get();
        $setorans = $this->setorans()->get();
        
        // Map setorans by date and operator ID for fast lookup
        $setoransMap = [];
        foreach ($setorans as $s) {
            $key = $s->tanggal . '_' . $s->operator_id;
            $setoransMap[$key] = $s;
        }
        
        $last_tot_akhir = $this->initial_totalisator;
        $stok_awal_day = $this->initial_stock;
        $skala = $this->skala;
        $belum_disetor_prev = $this->initial_balance;
        
        // First Pass: Calculate row-level fields and cumulative balance
        foreach ($rows as $row) {
            $tot_awal = $last_tot_akhir;
            $tot_akhir = $row->totalisator_akhir;
            if ($tot_akhir == 0) {
                $tot_akhir = $tot_awal;
            }
            $last_tot_akhir = $tot_akhir;
            
            $liter_terjual = max(0.0, $tot_akhir - $tot_awal - $row->test_pump);
            $rupiah_jual = $liter_terjual * $row->harga_jual;
            
            $stok_akhir = $row->stik_malam !== null ? ($row->stik_malam * $skala) : $stok_awal_day;
            
            $gain_loss = 0.0;
            if ($row->stik_malam !== null) {
                $gain_loss = ($stok_akhir + $liter_terjual) - ($stok_awal_day + $row->curah);
            }
            
            $pendapatan = $rupiah_jual - $row->pengeluaran;
            
            $setoran_calc = ceil(($pendapatan - $row->qris) / 500) * 500;
            if ($setoran_calc < 0) $setoran_calc = 0;
            
            $setoran_actual = $setoran_calc + $row->setoran_adjustment;
            
            // Retrieve disetor_sinergy dynamically
            $key = $row->tanggal . '_' . $row->operator_id;
            $disetor_sinergy = isset($setoransMap[$key]) ? floatval($setoransMap[$key]->nominal) : 0.0;
            
            // Calculate belum_disetor balance
            $belum_disetor = $belum_disetor_prev + $disetor_sinergy - $setoran_actual;
            $belum_disetor_prev = $belum_disetor;
            
            // Attach computed attributes
            $row->computed_tot_awal = $tot_awal;
            $row->computed_liter_terjual = $liter_terjual;
            $row->computed_rupiah_jual = $rupiah_jual;
            $row->computed_stok_awal = $stok_awal_day;
            $row->computed_stok_akhir = $stok_akhir;
            $row->computed_gain_loss = $gain_loss;
            $row->computed_pendapatan = $pendapatan;
            $row->computed_setoran_yusuf = $setoran_actual;
            $row->computed_disetor_sinergy = $disetor_sinergy;
            $row->computed_belum_disetor = $belum_disetor;
            
            // Update stock level for next row
            $stok_awal_day = $stok_akhir;
        }
        
        // Second Pass: Calculate daily totals and attach to daily blocks
        $grouped = $rows->groupBy('tanggal');
        foreach ($grouped as $date => $dateRows) {
            $daily_liter = 0;
            $daily_rupiah = 0;
            $daily_gain_loss = 0;
            $daily_pendapatan = 0;
            $daily_setoran = 0;
            $daily_disetor_sinergy = 0;
            
            foreach ($dateRows as $row) {
                $daily_liter += $row->computed_liter_terjual;
                $daily_rupiah += $row->computed_rupiah_jual;
                $daily_gain_loss += $row->computed_gain_loss;
                $daily_pendapatan += $row->computed_pendapatan;
                $daily_setoran += $row->computed_setoran_yusuf;
                $daily_disetor_sinergy += $row->computed_disetor_sinergy;
            }
            
            // Daily Gross Profit
            $tebus_price = $dateRows->first()->harga_tebus;
            $actual_discharged_volume = $daily_liter - $daily_gain_loss;
            $cost_of_goods_sold = $actual_discharged_volume * $tebus_price;
            $gross_profit = $daily_pendapatan - $cost_of_goods_sold;
            
            // Set daily variables on the first row of each group
            $first = $dateRows->first();
            $first->daily_total_liter = $daily_liter;
            $first->daily_total_rupiah = $daily_rupiah;
            $first->daily_total_gain_loss = $daily_gain_loss;
            $first->daily_total_pendapatan = $daily_pendapatan;
            $first->daily_total_setoran = $daily_setoran;
            $first->daily_total_disetor_sinergy = $daily_disetor_sinergy;
            $first->daily_gross_profit = $gross_profit;
        }
        
        return $rows;
    }

    /**
     * Calculate the final Rekap dynamically based on valid operational data.
     */
    public function getCalculatedRekaps()
    {
        $rows = $this->getCalculatedOperasionals();
        
        $total_gross_profit = 0;
        foreach ($rows as $row) {
            if (isset($row->daily_gross_profit)) {
                $total_gross_profit += $row->daily_gross_profit;
            }
        }
        
        $rekap = $this->rekap;
        $pengeluaran_rutin_total = 0;
        $detail_pengeluaran = [];
        
        if ($rekap && is_array($rekap->detail_pengeluaran_rutin)) {
            foreach ($rekap->detail_pengeluaran_rutin as $p) {
                // exclude empty labels
                if (!empty(trim($p['label']))) {
                    $pengeluaran_rutin_total += $p['rupiah'];
                    $detail_pengeluaran[] = $p;
                }
            }
        }
        
        // Calculate correct Laba Bersih
        $laba_bersih = $total_gross_profit - $pengeluaran_rutin_total;
        
        $detail_pembagian = [];
        if ($rekap && is_array($rekap->detail_pembagian_hasil)) {
            foreach ($rekap->detail_pembagian_hasil as $p) {
                $label = trim($p['label']);
                if (empty($label)) continue;
                
                // Determine percentage dynamically based on label or hardcoded rules if needed
                // Excel uses 95% for KOKO, 5% for eko
                $percentage = 0;
                if (strtolower($label) === 'koko') {
                    $percentage = 0.95;
                } elseif (strtolower($label) === 'eko') {
                    $percentage = 0.05;
                }
                
                $detail_pembagian[] = [
                    'label' => $label,
                    'percentage' => $percentage * 100 . '%',
                    'rupiah' => $laba_bersih * $percentage
                ];
            }
        }
        
        $lastRow = $rows->last();
        $belum_disetor_akhir = $lastRow ? $lastRow->computed_belum_disetor : 0;
        
        return [
            'total_gross_profit' => $total_gross_profit,
            'pengeluaran_rutin_total' => $pengeluaran_rutin_total,
            'detail_pengeluaran_rutin' => $detail_pengeluaran,
            'laba_bersih' => $laba_bersih,
            'detail_pembagian_hasil' => $detail_pembagian,
            'belum_disetor_akhir' => $belum_disetor_akhir
        ];
    }
}
