<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyReport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'pengeluaran_extra' => 'array',
        'grand_totals' => 'array',
        'data_parsed' => 'array',
        'bbm_datang' => 'array',
        'do_di_pertamina' => 'decimal:2',
        'uang_di_bank' => 'decimal:2',
        'kas_kecil' => 'decimal:2',
        'piutang' => 'decimal:2',
        'bunga_bank' => 'decimal:2',
        'pajak_bank' => 'decimal:2',
        'saldo_awal_modal' => 'decimal:2',
        'penyusutan_modal' => 'decimal:2',
        'penambahan_modal' => 'decimal:2',
        'saldo_akhir_modal' => 'decimal:2',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
