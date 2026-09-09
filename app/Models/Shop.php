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

    protected static function booted()
    {
        static::saved(function ($shop) {
            if ($shop->wasChanged('modal_awal') && floatval($shop->modal_awal) > 0) {
                $shop->syncInvestorNominalsFromModal();
            }
        });
    }

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
        return $this->belongsToMany(Investor::class)->withPivot('persentase', 'nominal', 'investasi_awal', 'sub_investors', 'keterangan');
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

    /**
     * Tentukan apakah cabang ini menggunakan sistem shift rotasi kerja.
     * Hanya berlaku untuk Gumelar dan Sumingkir.
     * Cabang lainnya (Kemutug Lor, Kalitapen, Kalibenda, Pageralang) menggunakan jam kerja reguler/non-shift.
     */
    public function isShiftBased(): bool
    {
        $nama = strtolower($this->nama ?? '');
        return str_contains($nama, 'gumelar') || str_contains($nama, 'sumingkir');
    }

    /**
     * Dapatkan konfigurasi jam kerja dan shift operasional cabang:
     * - Sumingkir: 2 Shift (Shift 1: 06.00-13.30, Shift 2: 13.30-21.00)
     * - Gumelar: 2 Shift (Shift 1: 07.00-15.00, Shift 2: 14.30-22.30)
     * - Non-Shift (Kemutug Lor, Kalitapen, dll): 1 Jam kerja operasional harian reguler
     */
    public function getShiftConfig(): array
    {
        $nama = strtolower($this->nama ?? '');

        if (str_contains($nama, 'sumingkir')) {
            return [
                'type'         => 'shift',
                'is_shift'     => true,
                'total_shifts' => 2,
                'shifts'       => [
                    1 => [
                        'shift_ke'    => 1,
                        'nama'        => 'Shift 1',
                        'label'       => 'Shift 1 (06:00 - 13:30 WIB)',
                        'jam_mulai'   => '06:00:00',
                        'jam_selesai' => '13:30:00',
                        'jam_label'   => '06:00 - 13:30 WIB (7.5 Jam)',
                        'badge'       => 'Shift 1',
                        'durasi_jam'  => 7.5,
                        'css_class'   => 'shift-1',
                    ],
                    2 => [
                        'shift_ke'    => 2,
                        'nama'        => 'Shift 2',
                        'label'       => 'Shift 2 (13:30 - 21:00 WIB)',
                        'jam_mulai'   => '13:30:00',
                        'jam_selesai' => '21:00:00',
                        'jam_label'   => '13:30 - 21:00 WIB (7.5 Jam)',
                        'badge'       => 'Shift 2',
                        'durasi_jam'  => 7.5,
                        'css_class'   => 'shift-2',
                    ],
                ],
            ];
        }

        if (str_contains($nama, 'gumelar')) {
            return [
                'type'         => 'shift',
                'is_shift'     => true,
                'total_shifts' => 2,
                'shifts'       => [
                    1 => [
                        'shift_ke'    => 1,
                        'nama'        => 'Shift 1 (Pagi)',
                        'label'       => 'Shift 1 - Pagi (07:00 - 15:00 WIB)',
                        'jam_mulai'   => '07:00:00',
                        'jam_selesai' => '15:00:00',
                        'jam_label'   => '07:00 - 15:00 WIB (8 Jam)',
                        'badge'       => 'Pagi',
                        'durasi_jam'  => 8.0,
                        'css_class'   => 'shift-1',
                    ],
                    2 => [
                        'shift_ke'    => 2,
                        'nama'        => 'Shift 2 (Siang)',
                        'label'       => 'Shift 2 - Siang (14:30 - 22:30 WIB)',
                        'jam_mulai'   => '14:30:00',
                        'jam_selesai' => '22:30:00',
                        'jam_label'   => '14:30 - 22:30 WIB (8 Jam)',
                        'badge'       => 'Siang',
                        'durasi_jam'  => 8.0,
                        'css_class'   => 'shift-2',
                    ],
                ],
            ];
        }

        // Non-Shift (Kemutug Lor, Kalitapen, Kalibenda, Pageralang)
        return [
            'type'         => 'non_shift',
            'is_shift'     => false,
            'total_shifts' => 1,
            'shifts'       => [
                1 => [
                    'shift_ke'    => 1,
                    'nama'        => 'Reguler / Harian',
                    'label'       => 'Jam Kerja Operasional Harian Penuh',
                    'jam_mulai'   => '07:00:00',
                    'jam_selesai' => '21:00:00',
                    'jam_label'   => 'Operasional Harian Penuh',
                    'badge'       => 'Reguler',
                    'durasi_jam'  => 14.0,
                    'css_class'   => 'shift-1',
                ],
            ],
        ];
    }


    /**
     * Sinkronkan nominal investasi masing-masing pemodal di tabel pivot investor_shop
     * berdasarkan modal_awal toko dan persentase saham masing-masing.
     */
    public function syncInvestorNominalsFromModal(): void
    {
        $modalAwal = floatval($this->modal_awal);
        if ($modalAwal <= 0) {
            return;
        }

        $investors = $this->investors()->get();
        if ($investors->isEmpty()) {
            \Illuminate\Support\Facades\DB::table('shops')
                ->where('id', $this->id)
                ->update(['total_investasi' => $modalAwal]);
            return;
        }

        foreach ($investors as $investor) {
            $pct = floatval($investor->pivot->persentase ?? 0);
            $nominal = round(($pct / 100) * $modalAwal, 2);

            $updatePivot = [
                'nominal'        => $nominal,
                'investasi_awal' => $nominal,
            ];

            if (!empty($investor->pivot->sub_investors)) {
                $subData = json_decode($investor->pivot->sub_investors, true);
                if (is_array($subData)) {
                    foreach ($subData as &$sub) {
                        $subPct = floatval($sub['persentase'] ?? 0);
                        $sub['nominal'] = round(($subPct / 100) * $modalAwal, 2);
                    }
                    unset($sub);
                    $updatePivot['sub_investors'] = json_encode($subData);
                }
            }

            $this->investors()->updateExistingPivot($investor->id, $updatePivot);
        }

        $totalNominal = $this->investors()->sum('investor_shop.nominal');
        \Illuminate\Support\Facades\DB::table('shops')
            ->where('id', $this->id)
            ->update(['total_investasi' => ($totalNominal > 0 ? $totalNominal : $modalAwal)]);
    }

    /**
     * Sinkronkan kolom modal_awal dan total_investasi pada tabel shops
     * dari akumulasi nominal investasi pemegang saham.
     */
    public function syncTotalInvestasiFromInvestors(): void
    {
        $totalNominal = $this->investors()->sum('investor_shop.nominal');
        if ($totalNominal > 0) {
            \Illuminate\Support\Facades\DB::table('shops')
                ->where('id', $this->id)
                ->update([
                    'total_investasi' => $totalNominal,
                    'modal_awal'      => $totalNominal,
                ]);
            $this->modal_awal = $totalNominal;
            $this->total_investasi = $totalNominal;
        }
    }
}

