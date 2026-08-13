<?php

/**
 * Validation Engine Configuration
 *
 * Semua threshold dan bobot yang digunakan Validation Engine.
 * Ubah nilai di sini tanpa perlu deploy ulang (php artisan config:cache untuk refresh).
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Data Quality Score Weights
    |--------------------------------------------------------------------------
    |
    | Bobot setiap komponen dalam perhitungan Data Quality Score (0-100).
    | Jumlah total HARUS = 100.
    |
    */
    'quality_weights' => [
        'input'         => 10,
        'carry_forward' => 20,
        'totalisator'   => 20,
        'stock_volume'  => 20,
        'formula'       => 20,
        'price'         => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Rule Thresholds (BusinessValidator)
    |--------------------------------------------------------------------------
    */
    'business' => [
        // CAL008: Volume penjualan harian maksimum (Liter)
        // Default: 15.000 L — Pertashop kapasitas tangki ~8.000–16.000 L
        'max_volume_per_day' => 15000.0,

        // CAL009: Batas bawah rupiah penjualan harian (Rp)
        // Nilai negatif diizinkan karena mungkin ada hari rugi
        'extreme_income_min' => -5000000.0,

        // CAL009: Batas atas rupiah penjualan harian (Rp)
        'extreme_income_max' => 300000000.0,

        // WARN001: Threshold running balance negatif untuk notifikasi (Rp)
        'running_balance_alert' => -10000000.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Carry Forward Tolerances (CarryForwardValidator)
    |--------------------------------------------------------------------------
    */
    'carry_forward' => [
        // CAL003 & CAL007: Toleransi selisih totalisator (Liter)
        'totalisator_tolerance' => 1.0,

        // CAL005: Toleransi selisih stok awal / stik (cm)
        // 1 cm stik ≈ 25-50 Liter tergantung dimensi tangki
        'stok_tolerance' => 50.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Price Validation (PriceValidator)
    |--------------------------------------------------------------------------
    */
    'price' => [
        // PRICE001: Toleransi selisih harga BBM (Rp/Liter)
        'tolerance' => 100.0,

        // Rentang harga BBM yang masuk akal untuk validasi konteks
        'min_valid_price' => 5000.0,
        'max_valid_price' => 30000.0,
    ],

];
