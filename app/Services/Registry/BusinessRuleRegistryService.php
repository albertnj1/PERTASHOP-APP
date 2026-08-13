<?php

namespace App\Services\Registry;

use App\Models\BusinessRule;
use App\Models\BusinessRuleVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * BusinessRuleRegistryService
 *
 * Pusat tata kelola Aturan Bisnis Terversi.
 * Menyediakan resolver nilai aktif berbasis skema waktu (effective_from & effective_until)
 * serta mengelola rilis versi baru tanpa mengubah histori kalkulasi lama.
 */
class BusinessRuleRegistryService
{
    /**
     * Dapatkan nilai aturan bisnis aktif pada tanggal tertentu.
     *
     * @param string $ruleCode Kode aturan (misal: 'PAYROLL_RATE')
     * @param mixed  $date     Tanggal evaluasi (default: hari ini)
     * @param mixed  $default  Fallback jika aturan tidak ditemukan
     * @return mixed Nilai mentah (numeric / string / array)
     */
    public function getValue(string $ruleCode, mixed $date = null, mixed $default = null): mixed
    {
        $version = $this->getVersion($ruleCode, $date);
        return $version ? $version->getRawValue() : $default;
    }

    /**
     * Dapatkan objek versi aturan bisnis aktif pada tanggal tertentu.
     */
    public function getVersion(string $ruleCode, mixed $date = null): ?BusinessRuleVersion
    {
        $rule = BusinessRule::where('code', $ruleCode)->first();
        return $rule ? $rule->resolveActiveVersion($date) : null;
    }

    /**
     * Dapatkan snapshot lengkap seluruh aturan bisnis aktif beserta Version ID untuk 1 tanggal.
     * Digunakan oleh Calculation Engine saat membuat Laporan Harian baru.
     *
     * @param mixed $date Tanggal evaluasi
     * @return array ['snapshot' => [...], 'version_ids' => [...]]
     */
    public function getActiveSnapshot(mixed $date = null): array
    {
        $rules = BusinessRule::with('versions')->get();
        $snapshot   = [];
        $versionIds = [];

        foreach ($rules as $rule) {
            $ver = $rule->resolveActiveVersion($date);
            if ($ver) {
                $snapshot[$rule->code] = [
                    'version_code'   => $ver->version_code,
                    'value'          => $ver->getRawValue(),
                    'effective_from' => is_string($ver->effective_from) ? $ver->effective_from : $ver->effective_from?->toDateTimeString(),
                ];
                $versionIds[$rule->code] = $ver->id;
            }
        }

        return [
            'snapshot'    => $snapshot,
            'version_ids' => $versionIds,
        ];
    }

    /**
     * Rilis versi aturan bisnis baru (Owner Only).
     * Otomatis membatasi `effective_until` versi lama untuk mencegah overlapping period.
     */
    public function createNewVersion(
        string $ruleCode,
        mixed  $value,
        string $effectiveFrom,
        int    $createdByUserId,
        string $changeReason
    ): BusinessRuleVersion {
        $rule = BusinessRule::where('code', $ruleCode)->firstOrFail();
        $effFromDate = Carbon::parse($effectiveFrom);

        // Cari versi terakhir yang aktif untuk membatasi effective_until
        $previousVersion = $rule->versions()
            ->where('is_active', true)
            ->where('effective_from', '<', $effFromDate->format('Y-m-d H:i:s'))
            ->whereNull('effective_until')
            ->first();

        if ($previousVersion) {
            \Illuminate\Support\Facades\DB::table('business_rule_versions')
                ->where('id', $previousVersion->id)
                ->update([
                    'effective_until' => $effFromDate->copy()->subSecond()->format('Y-m-d H:i:s'),
                    'updated_at'      => now(),
                ]);
        }

        // Generate version code otomatis
        $versionCount = $rule->versions()->count() + 1;
        $requestCode  = 'BR-' . Str::slug($rule->code) . '-v' . sprintf('%d.0', $versionCount);

        return BusinessRuleVersion::create([
            'business_rule_id' => $rule->id,
            'version_code'     => $requestCode,
            'value_numeric'    => is_numeric($value) ? (float) $value : null,
            'value_string'     => is_string($value) && !is_numeric($value) ? $value : null,
            'value_json'       => is_array($value) ? $value : null,
            'effective_from'   => $effFromDate->format('Y-m-d H:i:s'),
            'effective_until'  => null,
            'is_active'        => true,
            'created_by'       => $createdByUserId,
            'change_reason'    => $changeReason,
        ]);
    }
}
