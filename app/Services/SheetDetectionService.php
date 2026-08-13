<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * SheetDetectionService
 *
 * Bertanggung jawab men-scan semua sheet dalam 1 atau banyak file Excel,
 * mencocokkannya ke toko terdaftar di database, dan mengklasifikasikan
 * jenis setiap sheet (data_harian / data_gaji / di_luar_cakupan / tidak_dikenali).
 */
class SheetDetectionService
{
    /**
     * Data Master Alias Toko (dapat disesuaikan / ditambah variasi kodenya)
     */
    public static array $shopAliases = [
        // Kemutug Lor
        'KMT'        => 'Kemutug Lor',
        'KMTG'       => 'Kemutug Lor',
        'KEMUTUG'    => 'Kemutug Lor',

        // Kalibenda
        'KLB'        => 'Kalibenda',
        'KALIBENDA'  => 'Kalibenda',

        // Kalitapen
        'KLT'        => 'Kalitapen',
        'KALITAPEN'  => 'Kalitapen',

        // Pageralang
        'PGR'        => 'Pageralang',
        'PGL'        => 'Pageralang',
        'PAGERALANG' => 'Pageralang',

        // Gumelar
        'GML'        => 'Gumelar',
        'GUMELAR'    => 'Gumelar',

        // Sumingkir
        'SMK'        => 'Sumingkir',
        'SMG'        => 'Sumingkir',
        'SUMINGKIR'  => 'Sumingkir',
    ];

    /**
     * Kata kunci sheet yang di luar cakupan (Out of Scope)
     */
    public static array $outOfScopeKeywords = [
        'pembelian do',
        'laba kotor',
        'laba bersih',
        'rekap modal',
        'modal',
        'profit sharing',
        'hutang bbptu',
        'bbptu',
    ];

    /**
     * Kamus Bulan Indonesia & Singkatannya
     */
    public static array $monthKeywords = [
        'jan', 'januari',
        'feb', 'februari',
        'mar', 'maret',
        'apr', 'april',
        'mei',
        'jun', 'juni',
        'jul', 'juli',
        'ags', 'agu', 'agustus',
        'sep', 'sept', 'september',
        'okt', 'oktober',
        'nov', 'november',
        'des', 'desember',
    ];

    /**
     * Scan multiple files sekaligus.
     *
     * @param array $filesInfo Array of ['path' => string, 'original_name' => string, 'temp_path' => string]
     * @param Collection $shops
     * @return array
     */
    public function scanMultipleFiles(array $filesInfo, Collection $shops): array
    {
        $results = [];

        foreach ($filesInfo as $fileInfo) {
            $filePath     = $fileInfo['path'];
            $originalName = $fileInfo['original_name'];
            $tempPath     = $fileInfo['temp_path'];

            try {
                $spreadsheet = IOFactory::load($filePath);
                $sheetNames  = $spreadsheet->getSheetNames();
            } catch (\Throwable $e) {
                continue;
            }

            foreach ($sheetNames as $index => $sheetName) {
                $detection = $this->detectSingleSheet($sheetName, $originalName, $shops);
                $detection['sheet_index']       = $index;
                $detection['sumber_file_excel'] = $originalName;
                $detection['temp_path']         = $tempPath;
                $results[] = $detection;
            }
        }

        return $results;
    }

    /**
     * Scan 1 file Excel (backward compatibility).
     */
    public function scanSheets(string $filePath, Collection $shops, string $originalName = ''): array
    {
        return $this->scanMultipleFiles([
            [
                'path'          => $filePath,
                'original_name' => $originalName ?: basename($filePath),
                'temp_path'     => $filePath,
            ]
        ], $shops);
    }

    /**
     * Deteksi 1 sheet berdasarkan nama sheet dan nama file.
     */
    public function detectSingleSheet(string $sheetName, string $fileName, Collection $shops): array
    {
        $sheetLower = strtolower(trim($sheetName));

        // 1. Cek apakah sheet di luar cakupan
        foreach (self::$outOfScopeKeywords as $kw) {
            if ($sheetLower === $kw || str_contains($sheetLower, $kw)) {
                return [
                    'sheet_name'       => $sheetName,
                    'sumber_file_excel'=> $fileName,
                    'shop_id'          => null,
                    'shop_nama'        => null,
                    'shop_kode'        => null,
                    'jenis'            => 'di_luar_cakupan',
                    'confidence_score' => 0.0,
                    'include'          => false,
                    'status_label'     => 'Di Luar Cakupan',
                ];
            }
        }

        // 2. Cocokkan Toko (Prioritas: Nama Sheet -> Nama File -> Alias Kode)
        $match = $this->matchShop($sheetName, $fileName, $shops);

        // 3. Klasifikasikan jenis sheet (data_harian / data_gaji / tidak_dikenali)
        $jenis = $this->classifyJenis($sheetName, $match);

        $statusLabel = match ($jenis) {
            'data_harian'     => 'Data Harian',
            'data_gaji'       => 'Data Gaji Arsip',
            'di_luar_cakupan' => 'Di Luar Cakupan',
            default           => 'Tidak Dikenali',
        };

        return [
            'sheet_name'       => $sheetName,
            'sumber_file_excel'=> $fileName,
            'shop_id'          => $match ? $match['shop']->id : null,
            'shop_nama'        => $match ? $match['shop']->nama : null,
            'shop_kode'        => $match ? $match['shop']->kode : null,
            'jenis'            => $jenis,
            'confidence_score' => $match ? $match['score'] : 0.0,
            'include'          => in_array($jenis, ['data_harian', 'data_gaji']) && ($match !== null),
            'status_label'     => $statusLabel,
        ];
    }

    /**
     * Match shop dari sheet name atau file name.
     */
    private function matchShop(string $sheetName, string $fileName, Collection $shops): ?array
    {
        $sheetLower = strtolower($sheetName);
        $fileLower  = strtolower($fileName);
        $combined   = $sheetLower . ' ' . $fileLower;

        // 1. Pencocokan Nama Toko Lengkap
        foreach ($shops as $shop) {
            $shopNamaLower = strtolower($shop->nama);
            if (str_contains($sheetLower, $shopNamaLower)) {
                return ['shop' => $shop, 'score' => 1.0];
            }
            if (str_contains($fileLower, $shopNamaLower)) {
                return ['shop' => $shop, 'score' => 0.9];
            }
        }

        // 2. Pencocokan Kode Outlet dari DB
        foreach ($shops as $shop) {
            if (!empty($shop->kode)) {
                $kodeLower = strtolower($shop->kode);
                if (str_contains($sheetLower, $kodeLower)) {
                    return ['shop' => $shop, 'score' => 0.95];
                }
                if (str_contains($fileLower, $kodeLower)) {
                    return ['shop' => $shop, 'score' => 0.85];
                }
            }
        }

        // 3. Pencocokan Data Master Alias Toko
        foreach (self::$shopAliases as $alias => $targetShopName) {
            $aliasLower = strtolower($alias);
            // Gunakan preg_match word boundary atau string contains
            if (str_contains($sheetLower, $aliasLower) || str_contains($fileLower, $aliasLower)) {
                $targetShop = $shops->first(fn($s) => strtolower($s->nama) === strtolower($targetShopName));
                if ($targetShop) {
                    return ['shop' => $targetShop, 'score' => 0.85];
                }
            }
        }

        return null;
    }

    /**
     * Klasifikasikan jenis sheet (data_harian / data_gaji / tidak_dikenali)
     */
    private function classifyJenis(string $sheetName, ?array $match): string
    {
        if ($match === null) {
            return 'tidak_dikenali';
        }

        $sheetLower = strtolower($sheetName);

        // Cek kata "gaji"
        if (str_contains($sheetLower, 'gaji')) {
            return 'data_gaji';
        }

        // Cek pola bulan+tahun (misal: jul25, ags25, agu25, sep25, sept25, okt25, nov25, des25, jan26, feb26, mar26)
        $monthPattern = implode('|', self::$monthKeywords);
        if (preg_match('/.*(' . $monthPattern . ')\s*\d{2,4}.*/i', trim($sheetLower))) {
            return 'data_gaji';
        }

        return 'data_harian';
    }

    /**
     * Baca header row dari sheet.
     */
    public function readHeaderRow(string $filePath, string $sheetName, int $userHeaderRow = 1): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getSheetByName($sheetName);

        if (!$sheet) {
            return [];
        }

        $highestCol = $sheet->getHighestColumn();
        $keywords   = ['totalisator', 'tot', 'stik', 'stok', 'pump', 'test', 'terima', 'bbm', 'tanggal', 'tgl', 'volume', 'rupiah', 'pengeluaran', 'setoran', 'qris', 'curah'];

        $bestRow  = $userHeaderRow;
        $maxScore = -1;

        for ($r = 1; $r <= 5; $r++) {
            $score = 0;
            $col   = 'A';
            while (true) {
                $val = strtolower(trim(strval($sheet->getCell($col . $r)->getFormattedValue())));
                foreach ($keywords as $kw) {
                    if ($val !== '' && str_contains($val, $kw)) {
                        $score++;
                    }
                }
                if ($col === $highestCol) break;
                $col++;
            }

            if ($score > $maxScore) {
                $maxScore = $score;
                $bestRow  = $r;
            }
        }

        if ($maxScore <= 0) {
            $bestRow = $userHeaderRow;
        }

        $headers = [];
        $col     = 'A';

        while (true) {
            $parts = [];
            for ($r = $bestRow; $r <= min($bestRow + 2, 8); $r++) {
                $val = trim(strval($sheet->getCell($col . $r)->getFormattedValue()));
                if ($val !== '' && !str_starts_with(strtoupper($val), 'PS.') && !in_array($val, $parts)) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $val)) {
                        $parts[] = $val;
                    }
                }
            }

            $combinedHeader = implode(' ', $parts);
            $headers[$col] = $combinedHeader ?: "Kolom {$col}";

            if ($col === $highestCol) {
                break;
            }
            $col++;
        }

        return $headers;
    }
}
