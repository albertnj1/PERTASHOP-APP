<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — Pertashop App</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 4.6.2 CSS (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

    <!-- Font Awesome 5 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- DataTables & Responsive CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

    <!-- Core App Styles & AdminLTE -->
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/custom-theme.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar-only.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/new-admin.css') }}?v={{ time() }}">

    <!-- Layout Master Responsive Enhancements -->
    <style>
        :root {
            --app-sidebar-width: 250px;
            --app-topbar-height: 60px;
            --app-bg: #f8fafc;
            --app-card-bg: #ffffff;
            --app-border: #e2e8f0;
            --app-primary: #00796B;
            --app-primary-dark: #004D40;
            --app-ink: #1e293b;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            max-width: 100%;
            width: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            min-height: 100%;
            box-sizing: border-box;
        }

        body.layout-responsive {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--app-bg);
            color: var(--app-ink);
            min-height: 100vh;
            display: block;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* ===== DESKTOP (>= 1024px): Konten Bebas dari Tertimpa Sidebar ===== */
        @media (min-width: 1024px) {

            .layout-responsive #mainSidebar,
            aside#mainSidebar.new-sidebar {
                position: fixed !important;
                top: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                width: var(--app-sidebar-width) !important;
                height: 100vh !important;
                z-index: 1030 !important;
                overflow-y: auto !important;
                transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), margin-left 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease !important;
            }

            .layout-responsive .app-main,
            .app-main,
            .content-wrapper,
            .main-content,
            .main {
                margin-left: var(--app-sidebar-width) !important;
                width: calc(100% - var(--app-sidebar-width)) !important;
                max-width: calc(100% - var(--app-sidebar-width)) !important;
                min-width: 0 !important;
                min-height: 100vh !important;
                display: flex !important;
                flex-direction: column !important;
                overflow-x: hidden !important;
                box-sizing: border-box !important;
                transition: margin-left 0.3s cubic-bezier(0.16, 1, 0.3, 1), width 0.3s cubic-bezier(0.16, 1, 0.3, 1), max-width 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
            }

            body.sidebar-collapsed .layout-responsive #mainSidebar,
            body.sidebar-collapsed aside#mainSidebar.new-sidebar {
                transform: translateX(calc(-1 * var(--app-sidebar-width))) !important;
                margin-left: calc(-1 * var(--app-sidebar-width)) !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }

            body.sidebar-collapsed .layout-responsive .app-main,
            body.sidebar-collapsed .app-main,
            body.sidebar-collapsed .content-wrapper,
            body.sidebar-collapsed .main-content,
            body.sidebar-collapsed .main {
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            /* Pastikan semua card & container di laptop tidak melebar melebihi wadahnya */
            .app-content, .container-fluid, .card, .panel {
                max-width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
            }
        }

        /* KUNCI KRITIS: Semua kolom grid Bootstrap & container HARUS min-width: 0 agar child table/pre tidak meledakkan kolom */
        .row,
        .row > [class*="col-"],
        .col,
        .col-1, .col-2, .col-3, .col-4, .col-5, .col-6,
        .col-7, .col-8, .col-9, .col-10, .col-11, .col-12,
        [class*="col-sm-"],
        [class*="col-md-"],
        [class*="col-lg-"],
        [class*="col-xl-"] {
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        /* ===== MOBILE / TABLET (< 1024px): Off-Canvas Drawer Tanpa Overflow ===== */
        @media (max-width: 1023.98px) {

            .layout-responsive #mainSidebar,
            aside#mainSidebar.new-sidebar {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                bottom: 0 !important;
                height: 100vh !important;
                width: 280px !important;
                max-width: 80vw !important;
                transform: translateX(-100%) !important;
                visibility: hidden !important;
                pointer-events: none !important;
                z-index: 9999 !important;
                box-shadow: none !important;
                margin: 0 !important;
                transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.3s ease !important;
            }

            body.sidebar-open .layout-responsive #mainSidebar,
            body.sidebar-open aside#mainSidebar.new-sidebar {
                transform: translateX(0) !important;
                visibility: visible !important;
                pointer-events: auto !important;
                box-shadow: 4px 0 24px rgba(0, 0, 0, 0.5) !important;
            }

            .layout-responsive .app-main,
            .app-main,
            .content-wrapper,
            .main {
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100vw !important;
                min-width: 0 !important;
                min-height: 100vh !important;
                display: flex !important;
                flex-direction: column !important;
            }

            .layout-responsive .app-content {
                padding: 14px 12px 30px !important;
            }

            .layout-responsive .app-topbar {
                padding: 0 12px !important;
            }

            .layout-responsive .topbar-brand-badge {
                font-size: 11px;
                padding: 4px 8px;
                max-width: 180px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* ===== TOPBAR / HEADER ===== */
        .layout-responsive .app-topbar {
            height: var(--app-topbar-height);
            background: var(--app-card-bg);
            border-bottom: 1px solid var(--app-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .layout-responsive .topbar-brand-badge {
            background: rgba(0, 121, 107, 0.08);
            color: var(--app-primary);
            font-size: 12px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* ===== CONTENT AREA (Full Vertical Scroll) ===== */
        .layout-responsive .app-content {
            flex: 1 0 auto;
            padding: 24px;
            width: 100%;
            max-width: 100%;
        }

        /* ===== FOOTER ===== */
        .layout-responsive .app-footer {
            flex-shrink: 0;
            background: var(--app-card-bg);
            border-top: 1px solid var(--app-border);
            padding: 14px 24px;
            font-size: 13px;
            color: #64748b;
            width: 100%;
        }

        /* ===== DESKTOP & TABLET (>= 768px): Hapus word-break agresif dari elemen umum agar teks tidak patah ===== */
        @media (min-width: 768px) {
            label,
            .badge,
            .btn,
            button,
            h1, h2, h3, h4, h5, h6,
            .h1, .h2, .h3, .h4, .h5, .h6,
            .card-title,
            .card-header,
            .topbar-brand-badge,
            .panel-title,
            .metric-value,
            .table th,
            .table td,
            .nav-link,
            .card,
            .alert,
            p,
            span {
                word-break: normal !important;
                overflow-wrap: normal !important;
            }
        }

        /* ===== RAPIKAN INPUT FILE & FORM GLOBAL DI HP ===== */
        .custom-file {
            max-width: 100%;
            overflow: hidden;
        }

        .custom-file-label {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            padding-right: 85px !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        .form-control,
        .custom-select,
        input,
        select,
        textarea {
            max-width: 100%;
            box-sizing: border-box;
        }

        /* ===== RESPONSIVE UTILITIES ===== */
        .touch-target {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Pastikan Tabel Lebar (Kalender 31 Hari / Rekap 49 Kolom) Scroll di Dalam Card, Bukan Mendorong Halaman */
        .table-responsive,
        .table-responsive-sm,
        .table-responsive-md,
        .table-responsive-lg,
        .table-responsive-xl,
        .table-rekap-container {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            box-sizing: border-box !important;
            margin-bottom: 1rem;
        }

        /* ==========================================================================
           TAMPILAN KHUSUS MOBILE (HP: max-width: 767.98px)
           ========================================================================== */
        @media (max-width: 767.98px) {

            /* 1. Paksa Semua Teks Membungkus ke Bawah (Cegah Meluber ke Kanan) */
            .card *,
            .alert *,
            .table td,
            .table th,
            .empty-state,
            .text-center,
            span,
            p,
            div {
                max-width: 100%;
                overflow-wrap: break-word !important;
                word-wrap: break-word !important;
            }

            td[colspan],
            .card-body,
            .card-body *,
            .notice-card,
            .presence-hero,
            .panel {
                white-space: normal !important;
            }

            /* 2. Perkecil Padding Samping Halaman & Kartu di HP */
            .layout-responsive .app-content,
            .app-content,
            .container-fluid,
            .content {
                padding-left: 10px !important;
                padding-right: 10px !important;
                padding-top: 14px !important;
                padding-bottom: 24px !important;
            }

            .card-body,
            .alert {
                padding: 0.75rem !important;
            }

            /* 3. Grid Custom CSS Collapse ke 1 Kolom Penuh di HP */
            .summary-grid,
            .summary-grid.cols-6,
            .kpi-grid-6,
            .outlet-grid-6,
            .row.cols-2-1,
            .row.cols-1-1,
            .row.cols-1-1-1,
            .outlet-price-grid,
            #active-price-row,
            .cred-rules,
            .stat-grid,
            .action-grid {
                grid-template-columns: 1fr !important;
                display: grid !important;
                gap: 12px !important;
                width: 100% !important;
            }

            /* 4. Perkecil Ukuran Tombol (.btn) */
            .btn {
                font-size: 0.8125rem !important;
                padding: 0.375rem 0.75rem !important;
                min-height: unset !important;
                line-height: 1.3 !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }

            .btn:not(.btn-block) {
                width: auto !important;
            }

            .btn-block {
                width: 100% !important;
            }

            .btn-lg {
                font-size: 0.875rem !important;
                padding: 0.45rem 0.9rem !important;
                min-height: unset !important;
            }

            .btn-sm,
            .btn-xs {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.5rem !important;
                min-height: unset !important;
            }

            /* 5. Perkecil Input Form & Select */
            .form-control,
            .custom-select,
            .custom-file-label {
                font-size: 0.875rem !important;
                height: calc(1.5em + 0.65rem + 2px) !important;
                padding: 0.325rem 0.65rem !important;
                min-height: unset !important;
            }

            .custom-file-label::after {
                height: calc(1.5em + 0.65rem) !important;
                padding: 0.325rem 0.65rem !important;
                font-size: 0.8125rem !important;
                display: flex !important;
                align-items: center !important;
            }

            /* 6. Padatkan Ukuran Teks & Ruang Kartu */
            h1,
            .h1 {
                font-size: 1.35rem !important;
            }

            h2,
            .h2 {
                font-size: 1.2rem !important;
            }

            h3,
            .h3 {
                font-size: 1.05rem !important;
            }

            h4,
            .h4 {
                font-size: 0.95rem !important;
            }

            .badge {
                font-size: 0.72rem !important;
                padding: 0.25em 0.5em !important;
            }

            .touch-target {
                min-height: unset !important;
            }
        }
    </style>

    @stack('style')
    @stack('styles')

    <!-- ==========================================================================
         ULTIMATE GLOBAL LAYOUT & MOBILE SCALE OVERRIDE (Must be last before </head>)
         ========================================================================== -->
    <style>
        /* 1. Kunci Lebar Konten Utama di Laptop (>= 1024px) Agar Tidak Meluber ke Kanan */
        @media (min-width: 1024px) {
            .app-main, .content-wrapper, .main-content, .main {
                margin-left: 250px !important;
                width: calc(100% - 250px) !important;
                max-width: calc(100% - 250px) !important;
                min-width: 0 !important;
                overflow-x: hidden !important;
                box-sizing: border-box !important;
            }
            body.sidebar-collapsed .app-main,
            body.sidebar-collapsed .content-wrapper,
            body.sidebar-collapsed .main-content,
            body.sidebar-collapsed .main {
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            /* Pastikan semua card & container di laptop tidak melebar melebihi wadahnya */
            .app-content, .container-fluid, .card, .panel {
                max-width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
            }
        }

        /* KUNCI KRITIS: Semua kolom grid Bootstrap & container HARUS min-width: 0 agar child table tidak meledakkan kolom */
        .row,
        .row > [class*="col-"],
        .col,
        .col-1, .col-2, .col-3, .col-4, .col-5, .col-6,
        .col-7, .col-8, .col-9, .col-10, .col-11, .col-12,
        [class*="col-sm-"],
        [class*="col-md-"],
        [class*="col-lg-"],
        [class*="col-xl-"] {
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        /* 2. Hapus word-break: break-word dari Elemen Umum di Laptop & Tablet (>= 768px) */
        @media (min-width: 768px) {
            label,
            .badge,
            .btn,
            button,
            h1, h2, h3, h4, h5, h6,
            .h1, .h2, .h3, .h4, .h5, .h6,
            .card-title,
            .card-header,
            .topbar-brand-badge,
            .panel-title,
            .metric-value,
            .table th,
            .table td,
            .nav-link,
            .card,
            .alert,
            p,
            span {
                word-break: normal !important;
                overflow-wrap: normal !important;
            }
        }

        /* 3. Pastikan Tabel Lebar Scroll di Dalam Card, Bukan Mendorong Halaman */
        .table-responsive,
        .table-responsive-sm,
        .table-responsive-md,
        .table-responsive-lg,
        .table-responsive-xl,
        .table-rekap-container {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            box-sizing: border-box !important;
        }

        /* 4. KURUNG SEMUA ATURAN MOBILE HANYA DI @media (max-width: 767.98px) */
        @media (max-width: 767.98px) {
            /* 1. Kecilkan Skala Dasar Seluruh Aplikasi di HP (12px agar seluruh UI padat dan pas satu layar) */
            html {
                font-size: 12px !important;
            }
            body {
                font-size: 0.82rem !important;
                line-height: 1.35 !important;
            }

            /* 2. Kunci posisi teks kosong di dalam tabel agar pas di tengah layar HP, bukan tengah lebar tabel */
            .table-responsive td[colspan] {
                text-align: left !important;
                white-space: normal !important;
                max-width: calc(100vw - 55px) !important;
                position: sticky !important;
                left: 0 !important;
            }
            .table-responsive td[colspan] > div,
            .table-responsive td[colspan] > * {
                white-space: normal !important;
                max-width: calc(100vw - 75px) !important;
                word-break: break-word !important;
                text-align: left !important;
            }

            /* 3. Grid 2 Kolom x 2 Baris untuk Kartu KPI / Metrik di HP */
            .summary-grid, .summary-grid.cols-6, .kpi-grid-6, .outlet-grid-6,
            .outlet-price-grid, #active-price-row, .stat-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                display: grid !important;
                gap: 8px !important;
                width: 100% !important;
            }

            /* 4. Topbar / Navbar Lebih Ringkas */
            .main-header, .app-topbar, .topbar {
                min-height: 44px !important;
                height: 44px !important;
                padding: 0 10px !important;
            }

            /* 5. Judul Halaman & Card */
            h1, .h1 {
                font-size: 1.15rem !important;
            }
            h2, .h2 {
                font-size: 1.05rem !important;
            }
            h3, .h3, .card-title {
                font-size: 0.9rem !important;
            }
            h4, .h4, h5, .h5 {
                font-size: 0.82rem !important;
            }

            /* 6. Angka Besar / KPI */
            .kpi-value, .metric-value, .display-4, .display-3 {
                font-size: 1.15rem !important;
            }

            /* 7. Card Header, Body, & Margin Padat (p-2) */
            .card-header {
                padding: 0.4rem 0.65rem !important;
            }
            .card-body, .alert {
                padding: 0.5rem 0.65rem !important;
            }
            .card {
                margin-bottom: 0.65rem !important;
            }

            /* 8. Tabel Lebih Kompak */
            .table td, .table th {
                padding: 0.35rem 0.45rem !important;
                font-size: 0.78rem !important;
            }

            /* 9. Tombol & Input Lebih Ringkas */
            .btn {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.55rem !important;
                min-height: unset !important;
                line-height: 1.25 !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
            }
            .btn:not(.btn-block) {
                width: auto !important;
            }
            .btn-block {
                width: 100% !important;
            }
            .btn-lg {
                font-size: 0.82rem !important;
                padding: 0.35rem 0.7rem !important;
                min-height: unset !important;
            }
            .btn-sm, .btn-xs {
                font-size: 0.7rem !important;
                padding: 0.2rem 0.4rem !important;
                min-height: unset !important;
            }
            .form-control, .custom-select {
                font-size: 0.8rem !important;
                height: 30px !important;
                padding: 0.2rem 0.5rem !important;
                min-height: unset !important;
            }
            .custom-file-label {
                font-size: 0.8rem !important;
                height: 30px !important;
                padding: 0.2rem 0.5rem !important;
            }
            .custom-file-label::after {
                height: 28px !important;
                padding: 0.2rem 0.5rem !important;
                font-size: 0.75rem !important;
            }

            /* 10. Menu Sidebar di HP */
            .nav-sidebar .nav-link, .sidebar-link, .new-sidebar .nav-item {
                padding: 0.4rem 0.65rem !important;
                font-size: 0.8rem !important;
                min-height: unset !important;
            }

            /* 11. Area Konten & Padding Lebih Ramping */
            .layout-responsive .app-content,
            .app-content,
            .container-fluid,
            .content {
                padding-left: 8px !important;
                padding-right: 8px !important;
                padding-top: 8px !important;
                padding-bottom: 16px !important;
            }
        }
    </style>
</head>

<body class="layout-responsive">

    <!-- Mobile Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleMobileSidebar()"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar main-sidebar new-sidebar elevation-4" id="mainSidebar">
        @include('partials._sidebar')
    </aside>

    <!-- Main Content Wrapper -->
    <div class="app-main content-wrapper main">

        <!-- Topbar Navbar -->
        <header class="app-topbar topbar">
            <div class="d-flex align-items-center">
                <!-- Hamburger Toggle Button -->
                <button type="button" class="mobile-toggle-btn touch-target mr-2" onclick="toggleMobileSidebar()"
                    aria-label="Toggle Menu">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <!-- Breadcrumbs & Title -->
                <div class="d-none d-sm-block">
                    <span class="text-muted small">Pertashop App &bull;</span>
                    <strong class="text-dark">@yield('title', 'Dashboard')</strong>
                </div>
                <div class="d-block d-sm-none font-weight-bold text-truncate"
                    style="max-width: 130px; font-size: 14px;">
                    @yield('title', 'Dashboard')
                </div>
            </div>

            <!-- Topbar Right Items -->
            <div class="d-flex align-items-center">
                <!-- Unit / Corporation Badge -->
                <div class="topbar-brand-badge mr-2 mr-md-3">
                    <i class="fas fa-gas-pump"></i>
                    <span>
                        @if(Auth::check())
                            @if (Auth::user()->role == 'operator')
                                {{ Auth::user()->operator?->shop?->nama ?? 'Operator' }}
                            @elseif (Auth::user()->role == 'investor')
                                Investor Pertashop
                            @else
                                {{ in_array(Auth::user()->role, ['super-admin', 'super_admin']) ? 'Super Admin' : 'Admin' }}
                            @endif
                        @else
                            Pertashop
                        @endif
                    </span>
                </div>

                <!-- User Dropdown Profile -->
                @if(Auth::check())
                    <div class="dropdown">
                        <button
                            class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center touch-target px-2 px-md-3"
                            type="button" id="userMenuDropdown" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false" style="border-radius: 20px;">
                            <div class="bg-primary text-white rounded-circle mr-1 mr-md-2 d-flex align-items-center justify-content-center"
                                style="width: 28px; height: 28px; font-size: 11px; font-weight: 700;">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <span class="d-none d-md-inline font-weight-bold text-dark mr-1" style="font-size: 13px;">
                                {{ Str::limit(Auth::user()->name, 15) }}
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow-sm border-0 mt-2"
                            aria-labelledby="userMenuDropdown" style="min-width: 200px;">
                            <div class="px-3 py-2 border-bottom">
                                <div class="font-weight-bold text-dark text-truncate">{{ Auth::user()->name }}</div>
                                <div class="small text-muted text-uppercase">{{ Auth::user()->role }}</div>
                            </div>
                            <a class="dropdown-item py-2" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt fa-fw text-muted mr-2"></i> Dashboard
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item py-2 text-danger font-weight-bold" href="{{ route('logout') }}"
                                onclick="logout(event)">
                                <i class="fas fa-sign-out-alt fa-fw mr-2"></i> Keluar (Logout)
                            </a>
                        </div>
                    </div>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                @endif
            </div>
        </header>

        <!-- Flash Messages Alert -->
        @if(session('success') || session('error') || $errors->any())
            <div class="px-3 pt-3 px-md-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-2" role="alert">
                        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-2" role="alert">
                        <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif

        <!-- Main Dynamic Content -->
        <main class="app-content content">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer
            class="app-footer main-footer d-flex flex-column flex-sm-row justify-content-between align-items-center">
            <div class="mb-2 mb-sm-0">
                &copy; {{ date('Y') }} <strong>Pertashop App</strong> &bull; All rights reserved.
            </div>
            <div class="text-muted small">
                Versi 2.0 &bull; Responsive Multi-Device
            </div>
        </footer>

    </div>

    <!-- Core Scripts: jQuery, Bootstrap 4 Bundle, SweetAlert2, Chart.js, AdminLTE -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script src="{{ asset('js/adminlte.min.js') }}"></script>

    <!-- DataTables JS & Responsive Extension -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

    <!-- Global Helper Functions -->
    <script>
        function toggleMobileSidebar() {
            if (window.innerWidth > 1024) {
                document.body.classList.toggle('sidebar-collapsed');
            } else {
                document.body.classList.toggle('sidebar-open');
            }
        }

        // Close sidebar when clicking backdrop on mobile
        document.addEventListener('DOMContentLoaded', function () {
            var backdrop = document.getElementById('sidebarBackdrop');
            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    document.body.classList.remove('sidebar-open');
                });
            }
        });

        // Setup CSRF header for all jQuery Ajax calls
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function formatDate(inputDate) {
            const date = new Date(inputDate);
            const options = {
                year: 'numeric',
                month: 'long',
                day: '2-digit',
                weekday: 'long',
            };
            return date.toLocaleDateString('id-ID', options);
        }

        function formatNumber(data, fractionDigit = 2) {
            if (data === null || data === undefined || isNaN(data)) return '0';
            return parseFloat(data).toLocaleString('id-ID', {
                minimumFractionDigits: fractionDigit,
                maximumFractionDigits: fractionDigit
            });
        }

        function formatCurrency(data, fractionDigit = 2) {
            if (data === null || data === undefined || isNaN(data)) return 'Rp 0';
            return parseFloat(data).toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: fractionDigit,
                maximumFractionDigits: fractionDigit
            });
        }

        function formatYearMonth(data) {
            if (!data) return '';
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const parts = data.toString().split('-');
            if (parts.length >= 2) {
                const year = parts[0];
                const month = parseInt(parts[1], 10) - 1;
                if (month >= 0 && month < 12) {
                    return months[month] + ' ' + year;
                }
            }
            return data;
        }

        $(document).ready(function () {
            $('.currency').each(function () {
                var value = parseFloat($(this).text());
                if (!isNaN(value)) {
                    var formattedValue = formatCurrency(value, 0);
                    $(this).text(formattedValue);
                }
            });

            $('.number').each(function () {
                var value = parseFloat($(this).text());
                if (!isNaN(value)) {
                    var formattedValue = formatNumber(value);
                    $(this).text(formattedValue);
                }
            });

            $('.number-int').each(function () {
                var value = parseFloat($(this).text());
                if (!isNaN(value)) {
                    var formattedValue = formatNumber(value, 0);
                    $(this).text(formattedValue);
                }
            });

            $('.number-abs').each(function () {
                var value = Math.abs(parseFloat($(this).text()));
                if (!isNaN(value)) {
                    var formattedValue = formatNumber(value);
                    $(this).text(formattedValue);
                }
            });
        });

        function logout(e) {
            if (e) e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Anda yakin ingin keluar dari sistem?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#logout-form').submit();
                }
            });
        }

        @if (session('success'))
            var popupId = "{{ uniqid() }}";
            if (!sessionStorage.getItem('shown-' + popupId)) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '{{ session('success') }}',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
            sessionStorage.setItem('shown-' + popupId, '1');
        @endif
    </script>

    @stack('script')
    @stack('scripts')

    {{-- Auto-Logout: Idle Detection 15 Menit --}}
    @include('components.idle-timeout')

</body>

</html>