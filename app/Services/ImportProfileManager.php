<?php

namespace App\Services;

use App\Models\ImportProfile;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * ImportProfileManager
 *
 * Mengelola pencocokan profil impor resmi berbasis workbook_signature.
 * Jika ditemukan profil yang pernah sukses diimpor sebelumnya,
 * sistem langsung menggunakan konfigurasi mapping profil tersebut.
 */
class ImportProfileManager
{
    public function generateSignature(Worksheet $sheet, array $headers): string
    {
        $sheetName   = strtolower(trim($sheet->getTitle()));
        $headerKeys  = implode('|', array_map('strtolower', array_values($headers)));
        $colCount    = count($headers);

        return md5($sheetName . ':' . $colCount . ':' . substr($headerKeys, 0, 300));
    }

    public function findProfileBySignature(string $signature): ?ImportProfile
    {
        $profile = ImportProfile::where('workbook_signature', $signature)->first();
        if ($profile) {
            $profile->increment('use_count');
            $profile->update(['last_used_at' => now()]);
        }
        return $profile;
    }

    public function saveProfile(string $profileName, string $signature, ?int $shopId, array $mappingConfig, int $headerRow = 1): ImportProfile
    {
        return ImportProfile::updateOrCreate(
            ['workbook_signature' => $signature],
            [
                'profile_name'   => $profileName,
                'shop_id'        => $shopId,
                'mapping_config' => $mappingConfig,
                'header_row'     => $headerRow,
                'last_used_at'   => now(),
            ]
        );
    }
}
