<?php

/**
 * Excel Templates Registry & Importer Configuration
 *
 * Registri resmi versi engine, template signatures, dan opsi impor.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Engine Versions
    |--------------------------------------------------------------------------
    */
    'engine_versions' => [
        'recognition' => '2.4.0',
        'validation'  => '2.5.0',
        'calculation' => '1.3.0',
        'importer'    => '2.4.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Session Configuration
    |--------------------------------------------------------------------------
    */
    'session' => [
        // Masa berlaku sesi impor (detik) — Default: 24 Jam (86400 detik)
        'ttl' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logical Block Registry
    |--------------------------------------------------------------------------
    | Blok-blok data dinamis yang dikenali oleh LogicalBlockRecognitionEngine.
    */
    'logical_blocks' => [
        'penjualan_harian' => [
            'label' => 'Blok Transaksi Penjualan BBM',
            'required_fields' => ['totalisator_akhir', 'tanggal'],
            'optional_fields' => ['totalisator_awal', 'volume_excel', 'rupiah_excel'],
        ],
        'stok_tangki' => [
            'label' => 'Blok Ukur Dipstik / Stok Tangki',
            'required_fields' => ['stik_akhir'],
            'optional_fields' => ['stik_awal', 'penerimaan', 'bbm_keluar_lain'],
        ],
        'test_pump' => [
            'label' => 'Blok Uji Tera Pompa (Test Pump)',
            'required_fields' => ['test_pump'],
            'optional_fields' => [],
        ],
        'setoran_deposit' => [
            'label' => 'Blok Setoran & Deposit Finansial',
            'required_fields' => ['disetorkan'],
            'optional_fields' => ['qris', 'transfer', 'kolektan'],
        ],
        'pengeluaran' => [
            'label' => 'Blok Pengeluaran Operasional',
            'required_fields' => ['pengeluaran'],
            'optional_fields' => ['keterangan_pengeluaran'],
        ],
        'payroll_gaji' => [
            'label' => 'Blok Penggajian Operator (Payroll)',
            'required_fields' => ['gaji_pokok', 'take_home_pay'],
            'optional_fields' => ['uang_transport', 'gaji_variable'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates Registry Standar
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'standard_monthly' => [
            'id'    => 'standard_monthly',
            'name'  => 'Standard Monthly Daily Report Template',
            'blocks' => ['penjualan_harian', 'stok_tangki', 'test_pump', 'setoran_deposit'],
        ],
        'multi_table_sheet' => [
            'id'    => 'multi_table_sheet',
            'name'  => 'Multi Table Sheet Layout',
            'blocks' => ['penjualan_harian', 'stok_tangki', 'pengeluaran', 'setoran_deposit'],
        ],
        'payroll_archive' => [
            'id'    => 'payroll_archive',
            'name'  => 'Payroll Archive Template',
            'blocks' => ['payroll_gaji'],
        ],
    ],

];
