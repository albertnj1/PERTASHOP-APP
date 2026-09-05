<?php

namespace App\Models;

use App\Models\Investor;
use App\Models\Operator;
use App\Models\Corporation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shop extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function operators()
    {
        return $this->hasMany(Operator::class);
    }

    public function corporation()
    {
        return $this->belongsTo(Corporation::class);
    }
    public function investors()
    {
        return $this->belongsToMany(Investor::class)->withPivot('persentase', 'nominal', 'sub_investors');
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function kolektans()
    {
        return $this->hasMany(Kolektan::class);
    }

    public function backdateExcelFiles()
    {
        return $this->hasMany(BackdateExcelFile::class);
    }

    public function payrollSystems()
    {
        return $this->hasMany(PayrollSystem::class);
    }

    public function activePayrollSystem()
    {
        return $this->hasOne(PayrollSystem::class)->where('aktif', true)->latestOfMany();
    }

    /**
     * Dapatkan default skema penggajian berdasarkan nama cabang:
     * - Gumelar   -> gaji_pokok_murni
     * - Sumingkir -> hibrid
     * - Kalitapen, Kalibenda, Pageralang, Kemutug -> komisi_murni
     */
    public function getDefaultPayrollScheme(): string
    {
        $nama = strtolower($this->nama ?? '');

        if (str_contains($nama, 'gumelar')) {
            return 'gaji_pokok_murni';
        }
        if (str_contains($nama, 'sumingkir')) {
            return 'hibrid';
        }
        return 'komisi_murni';
    }

    /**
     * Rekomendasi parameter awal penggajian cabang
     */
    public function getDefaultPayrollNominals(): array
    {
        $skema = $this->getDefaultPayrollScheme();

        return match ($skema) {
            'gaji_pokok_murni' => [
                'tipe_skema'         => 'gaji_pokok_murni',
                'ada_gaji_pokok'     => true,
                'nominal_gaji_pokok' => 1500000,
                'ada_rate_per_liter' => false,
                'rate_per_liter'     => 0,
            ],
            'hibrid' => [
                'tipe_skema'         => 'hibrid',
                'ada_gaji_pokok'     => true,
                'nominal_gaji_pokok' => 1500000,
                'ada_rate_per_liter' => true,
                'rate_per_liter'     => 200,
            ],
            default => [
                'tipe_skema'         => 'komisi_murni',
                'ada_gaji_pokok'     => false,
                'nominal_gaji_pokok' => 0,
                'ada_rate_per_liter' => true,
                'rate_per_liter'     => 200,
            ],
        };
    }
}

