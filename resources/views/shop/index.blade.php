@extends('layouts._new_admin')
@section('title', 'Master Pertashop & Jaringan Outlet')

@push('style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<style>
  :root {
    --sb-emerald-dark: #022c22;
    --sb-emerald-deep: #064e3b;
    --sb-emerald-main: #006241;
    --sb-emerald-light: #059669;
    --sb-emerald-subtle: #ecfdf5;
  }

  body {
    background-color: #f8fafc;
  }

  /* ==========================================================================
     SHOWCASE WRAPPER & DONUT WHEEL
     ========================================================================== */
  .outlet-showcase-wrapper {
    position: relative;
    width: 100%;
    max-width: 100%;
    margin: 0 0 36px 0;
    padding: 44px 28px 52px;
    background: radial-gradient(circle at 50% 25%, #d1fae5 0%, #f0fdf4 45%, #f8fafc 85%);
    border-radius: 28px;
    border: 1px solid rgba(16, 185, 129, 0.2);
    box-shadow: 0 10px 30px -10px rgba(0, 98, 65, 0.08);
    overflow: hidden;
  }

  /* Roda Donut Persentase di Background */
  .outlet-wheel-backdrop {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 540px;
    height: 540px;
    pointer-events: none;
    z-index: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  #outlet-wheel-bg {
    width: 540px !important;
    height: 540px !important;
    border-radius: 50%;
    border: 2px dashed rgba(16, 185, 129, 0.5);
    transition: transform 0.7s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform-origin: center center;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .outlet-wheel-inner {
    width: 420px;
    height: 420px;
    border-radius: 50%;
    border: 1px solid rgba(16, 185, 129, 0.3);
    background: rgba(255, 255, 255, 0.45);
    backdrop-filter: blur(4px);
  }

  /* Badge Porsi / Status di Atas Roda */
  .outlet-wheel-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(8px);
    padding: 6px 20px;
    border-radius: 9999px;
    border: 1px solid rgba(16, 185, 129, 0.35);
    box-shadow: 0 4px 14px rgba(0, 98, 65, 0.12);
    z-index: 10;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  /* ==========================================================================
     SWIPER CAROUSEL & CARDS
     ========================================================================== */
  .outlet-swiper {
    position: relative;
    z-index: 10;
    width: 100%;
    padding-top: 24px;
    padding-bottom: 38px;
  }

  .outlet-swiper .swiper-slide {
    width: 400px !important;
    max-width: 90vw;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
  }

  .outlet-card {
    border-radius: 24px;
    padding: 26px;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(203, 213, 225, 0.8);
    background: #ffffff;
    color: #1e293b;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
    transform: scale(0.9);
    opacity: 0.75;
    min-height: 520px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  /* Active State (Starbucks Emerald Signature) */
  .swiper-slide-active .outlet-card {
    background-color: #064e3b !important;
    color: #ffffff !important;
    transform: scale(1.06) !important;
    opacity: 1 !important;
    box-shadow: 0 25px 50px -12px rgba(6, 78, 59, 0.45), 0 0 0 1px rgba(52, 211, 153, 0.4) !important;
    border-color: rgba(52, 211, 153, 0.5) !important;
    z-index: 20;
  }

  .swiper-slide-active .code-badge {
    background-color: #ffffff !important;
    color: #064e3b !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .swiper-slide-active .operator-badge {
    background-color: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
  }

  .swiper-slide-active .nominal-box {
    background-color: rgba(255, 255, 255, 0.1) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
  }

  .swiper-slide-active .nominal-box span:last-child {
    color: #fef08a !important;
  }

  .swiper-slide-active .share-pill {
    background-color: #10b981 !important;
    color: #ffffff !important;
  }

  .swiper-slide-active .investor-row {
    border-color: rgba(255, 255, 255, 0.12) !important;
  }

  .swiper-slide-active .outlet-subtext {
    color: #a7f3d0 !important;
  }

  .swiper-slide-active .btn-manage {
    background-color: #ffffff !important;
    color: #064e3b !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
  }

  .swiper-slide-active .btn-manage:hover {
    background-color: #d1fae5 !important;
    color: #064e3b !important;
  }

  .swiper-slide-active .btn-edit-secondary {
    border-color: rgba(255, 255, 255, 0.3) !important;
    color: #ffffff !important;
  }

  .swiper-slide-active .btn-edit-secondary:hover {
    background-color: rgba(255, 255, 255, 0.15) !important;
  }

  /* ==========================================================================
     VISUAL STATE NON-AKTIF / SUSPEND (ADAPTIF & TERPADU)
     ========================================================================== */
  .card-inactive {
    filter: grayscale(0.85);
    opacity: 0.55 !important;
    background-color: #f8fafc !important;
    border-style: dashed !important;
  }

  .card-inactive .btn-manage {
    background-color: #64748b !important;
    color: #ffffff !important;
    cursor: not-allowed;
  }

  .swiper-slide-active .card-inactive {
    background-color: #334155 !important;
    filter: grayscale(0);
    opacity: 0.85 !important;
    border-color: rgba(148, 163, 184, 0.4) !important;
    box-shadow: 0 20px 40px -10px rgba(51, 65, 85, 0.4) !important;
  }

  .swiper-slide-active .card-inactive .code-badge {
    background-color: #475569 !important;
    color: #f8fafc !important;
  }

  .swiper-slide-active .card-inactive .nominal-box {
    background-color: rgba(255, 255, 255, 0.06) !important;
  }

  .swiper-slide-active .card-inactive .nominal-box span:last-child {
    color: #cbd5e1 !important;
  }

  /* Status Toggle Switch */
  .status-toggle-label {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    margin-bottom: 0;
  }

  .status-toggle-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
  }

  .status-toggle-track {
    width: 38px;
    height: 20px;
    background-color: #cbd5e1;
    border-radius: 9999px;
    position: relative;
    transition: all 0.3s ease;
  }

  .status-toggle-track:after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    background-color: #ffffff;
    border-radius: 50%;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
  }

  .status-toggle-input:checked + .status-toggle-track {
    background-color: #10b981;
  }

  .status-toggle-input:checked + .status-toggle-track:after {
    transform: translateX(18px);
  }

  .pulse-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background-color: #10b981;
    display: inline-block;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    animation: pulse-dot 2s infinite;
  }

  @keyframes pulse-dot {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    70% { box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
  }

  /* Card Inner Elements */
  .code-badge {
    padding: 5px 14px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 800;
    background-color: #ecfdf5;
    color: #065f46;
    letter-spacing: 0.04em;
    transition: all 0.3s ease;
  }

  .operator-badge {
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    background-color: #f1f5f9;
    color: #475569;
    transition: all 0.3s ease;
  }

  .nominal-box {
    padding: 12px 16px;
    border-radius: 14px;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    margin-bottom: 14px;
    transition: all 0.3s ease;
  }

  .investors-scroll-container {
    max-height: 175px;
    overflow-y: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-right: 4px;
  }

  .investors-scroll-container::-webkit-scrollbar {
    display: none;
  }

  .investor-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12px;
  }

  .share-pill {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    background-color: #f1f5f9;
    color: #334155;
    transition: all 0.3s ease;
  }

  .btn-manage {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 11px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 12.5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background-color: #065f46;
    color: #ffffff;
    border: none;
    box-shadow: 0 4px 12px rgba(6, 95, 70, 0.2);
    text-decoration: none !important;
    transition: all 0.3s ease;
  }

  .btn-edit-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 14px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 12px;
    border: 1.5px solid #cbd5e1;
    color: #475569;
    background: transparent;
    text-decoration: none !important;
    transition: all 0.3s ease;
  }

  /* Nav Buttons */
  .swiper-button-prev, .swiper-button-next {
    width: 44px !important;
    height: 44px !important;
    border-radius: 50% !important;
    background-color: #ffffff !important;
    box-shadow: 0 6px 16px rgba(0, 98, 65, 0.15) !important;
    color: #064e3b !important;
    border: 1px solid rgba(16, 185, 129, 0.3) !important;
    z-index: 30 !important;
    pointer-events: auto !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
  }

  .swiper-button-prev:after, .swiper-button-next:after {
    font-size: 15px !important;
    font-weight: 800;
  }

  .swiper-button-prev:hover, .swiper-button-next:hover {
    background-color: #064e3b !important;
    color: #ffffff !important;
    transform: scale(1.08);
  }

  .swiper-button-prev { left: 14px !important; }
  .swiper-button-next { right: 14px !important; }

  /* ==========================================================================
     TABLE REKAP AUDIT
     ========================================================================== */
  .data-list-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    overflow: hidden;
  }

  .data-list-header {
    padding: 20px 24px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
  }

  .table-outlet-custom th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-top: none !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 14px 16px;
  }

  .table-outlet-custom td {
    vertical-align: middle !important;
    padding: 14px 16px;
    font-size: 13px;
    border-bottom: 1px solid #f1f5f9;
  }

  .table-outlet-custom tbody tr:hover {
    background-color: #f8fafc;
  }
</style>
@endpush

@section('content')
<div class="container-fluid py-2">

  {{-- TOP TITLE & STATS HEADER --}}
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap" style="gap: 16px;">
    <div>
      <span class="badge" style="background: #d1fae5; color: #065f46; font-size: 11px; font-weight: 700; border-radius: 12px; padding: 5px 12px; text-transform: uppercase; letter-spacing: 0.05em;">
        <i class="fas fa-gas-pump text-success mr-1"></i> Outlet Network Showcase
      </span>
      <h1 class="page-title mt-2 mb-1" style="font-size: 26px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em;">
        Master Pertashop &amp; Jaringan Cabang
      </h1>
      <p class="text-muted mb-0" style="font-size: 13.5px;">
        Visualisasi interaktif struktur kepemilikan modal, penugasan operator, dan data master seluruh outlet Pertashop.
      </p>
    </div>

    <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
      @if(Auth::user()->role !== 'investor')
      <a href="{{ route('shops.create') }}" class="btn font-weight-bold" style="background: var(--sb-emerald-main); color: #ffffff; border-radius: 9999px; padding: 10px 22px; font-size: 13px; box-shadow: 0 4px 14px rgba(0, 98, 65, 0.25);">
        <i class="fas fa-plus mr-1"></i> Tambah Pertashop Baru
      </a>
      @endif
    </div>
  </div>

  {{-- 3 SUMMARY STAT PILLS --}}
  <div class="row mb-4">
    <div class="col-md-4 mb-3 mb-md-0">
      <div class="p-3 d-flex align-items-center justify-content-between" style="background: #ffffff; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
        <div class="d-flex align-items-center" style="gap: 14px;">
          <div style="width: 48px; height: 48px; border-radius: 14px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px;">
            <i class="fas fa-gas-pump"></i>
          </div>
          <div>
            <div class="text-muted" style="font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Total Jaringan Outlet</div>
            <div style="font-size: 20px; font-weight: 800; color: #0f172a;">{{ $totalShopsCount }} Pertashop</div>
          </div>
        </div>
        <span class="badge badge-success px-2 py-1" style="border-radius: 8px;">Aktif</span>
      </div>
    </div>

    <div class="col-md-4 mb-3 mb-md-0">
      <div class="p-3 d-flex align-items-center justify-content-between" style="background: #ffffff; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
        <div class="d-flex align-items-center" style="gap: 14px;">
          <div style="width: 48px; height: 48px; border-radius: 14px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 20px;">
            <i class="fas fa-coins"></i>
          </div>
          <div>
            <div class="text-muted" style="font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Akumulasi Modal Cabang</div>
            <div style="font-size: 20px; font-weight: 800; color: #0f172a;">Rp {{ number_format($totalInvestasiAll, 0, ',', '.') }}</div>
          </div>
        </div>
        <span class="badge badge-warning px-2 py-1 text-white" style="border-radius: 8px; background: #f59e0b;">Master</span>
      </div>
    </div>

    <div class="col-md-4">
      <div class="p-3 d-flex align-items-center justify-content-between" style="background: #ffffff; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
        <div class="d-flex align-items-center" style="gap: 14px;">
          <div style="width: 48px; height: 48px; border-radius: 14px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 20px;">
            <i class="fas fa-user-friends"></i>
          </div>
          <div>
            <div class="text-muted" style="font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Operator Bertugas</div>
            <div style="font-size: 20px; font-weight: 800; color: #0f172a;">{{ $totalOperatorsCount }} Personil</div>
          </div>
        </div>
        <span class="badge badge-primary px-2 py-1" style="border-radius: 8px;">Terdistribusi</span>
      </div>
    </div>
  </div>

  {{-- =========================================================================
       SHOWCASE CAROUSEL OUTLET (CENTERED ROTATING DONUT WHEEL)
       ========================================================================= --}}
  <div class="outlet-showcase-wrapper">

    <!-- Roda Donut Persentase di Background -->
    <div class="outlet-wheel-backdrop">
      <div id="outlet-wheel-bg">
        <div class="outlet-wheel-inner"></div>
      </div>
      
      <!-- Angka Persentase / Status di Atas Roda -->
      <div class="outlet-wheel-badge">
        <span style="font-size: 11px; font-weight: 700; color: #065f46; text-transform: uppercase;">Outlet Aktif:</span>
        <span id="outlet-wheel-label" style="font-size: 13.5px; font-weight: 900; color: #059669;">Kemutug Lor</span>
      </div>
    </div>

    <!-- Swiper Carousel -->
    <div class="swiper outlet-swiper">
      <div class="swiper-wrapper align-items-center">

        @foreach($shops as $shop)
          @php
            $totInvestasi = $shop->investors->sum('pivot.nominal') ?: ($shop->modal_awal ?? 0);
            $operatorsCount = $shop->operators->count();
            $investorsList = $shop->investors;
            $isActive = $shop->is_active ?? true;
          @endphp
          <div class="swiper-slide" 
               data-shop-name="{{ $shop->nama }}"
               data-shop-code="{{ $shop->kode }}"
               data-shop-total="{{ number_format($totInvestasi, 0, ',', '.') }}">
            
            <div class="outlet-card {{ !$isActive ? 'card-inactive' : '' }}">
              
              <div>
                <!-- Header Card: Kode Outlet & Badge Operator + Status & Toggle -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div class="d-flex align-items-center" style="gap: 8px;">
                    <span class="code-badge">
                      <i class="fas fa-tag mr-1"></i> {{ $shop->kode }}
                    </span>
                    <span id="badge-status-{{ $shop->id }}" class="status-badge-pill {{ $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}" style="padding: 3px 9px; border-radius: 9999px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                      @if($isActive)
                        <span class="pulse-dot"></span>
                        <span class="status-text">Aktif</span>
                      @else
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; display: inline-block;"></span>
                        <span class="status-text">Non-Aktif</span>
                      @endif
                    </span>
                  </div>

                  <div class="d-flex align-items-center" style="gap: 8px;">
                    <span class="operator-badge">
                      <i class="fas fa-users mr-1"></i> {{ $operatorsCount }} Operator
                    </span>

                    @if(Auth::user()->role !== 'investor')
                    <label class="status-toggle-label" title="Ubah Status Aktif / Non-Aktif">
                      <input type="checkbox" 
                             class="status-toggle-input toggle-status-btn" 
                             data-id="{{ $shop->id }}" 
                             data-endpoint="{{ route('shops.toggle-status', $shop->id) }}" 
                             {{ $isActive ? 'checked' : '' }}>
                      <div class="status-toggle-track"></div>
                    </label>
                    @endif
                  </div>
                </div>

                <!-- Nama Cabang & Lokasi -->
                <h3 class="font-weight-bold mb-1 text-truncate" style="font-size: 19px; letter-spacing: -0.01em;" title="{{ $shop->nama }}">
                  {{ $shop->nama }}
                </h3>
                <p class="outlet-subtext mb-3 text-truncate" style="font-size: 11.5px; color: #64748b;" title="{{ $shop->alamat }}">
                  <i class="fas fa-map-marker-alt mr-1 text-danger"></i> {{ $shop->alamat }}
                </p>

                <!-- Total Modal Investasi -->
                <div class="nominal-box">
                  <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: block; opacity: 0.8; margin-bottom: 2px;">
                    Total Investasi Cabang
                  </span>
                  <span style="font-size: 17.5px; font-weight: 900; letter-spacing: -0.01em;">
                    Rp {{ number_format($totInvestasi, 0, ',', '.') }}
                  </span>
                </div>

                <!-- List Pemegang Saham & Persentase -->
                <div class="mb-4">
                  <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.75; display: block; margin-bottom: 6px;">
                    Komposisi Pemegang Saham ({{ $investorsList->count() }} Pemodal)
                  </span>

                  <div class="investors-scroll-container">
                    @forelse($investorsList as $inv)
                      @php
                        $invName = $inv->user->name ?? $inv->name;
                        $pct = (float)($inv->pivot->persentase ?? 0);
                        $nom = (float)($inv->pivot->nominal ?? 0);
                      @endphp
                      <div class="investor-row">
                        <div class="text-truncate pr-2" style="max-width: 220px;">
                          <span style="font-weight: 600;">{{ $invName }}</span>
                          @if($nom > 0)
                            <small class="d-block text-muted" style="font-size: 10px; opacity: 0.8;">Rp {{ number_format($nom, 0, ',', '.') }}</small>
                          @endif
                        </div>
                        <span class="share-pill">
                          {{ number_format($pct, 1) }}%
                        </span>
                      </div>
                    @empty
                      <div class="text-center py-3 text-muted" style="font-size: 11.5px;">
                        Belum ada alokasi investor
                      </div>
                    @endforelse
                  </div>
                </div>
              </div>

              <!-- Tombol Aksi -->
              <div class="d-flex" style="gap: 8px;">
                <a href="{{ route('shops.investors', $shop->id) }}" class="btn-manage flex-grow-1" title="Kelola Komposisi Investor">
                  <i class="fas fa-users-cog mr-1"></i> Kelola Investor
                </a>
                
                @if(Auth::user()->role !== 'investor')
                <a href="{{ route('shops.edit', $shop->id) }}" class="btn-edit-secondary" title="Edit Pertashop">
                  <i class="fas fa-edit"></i>
                </a>
                @endif
              </div>

            </div>

          </div>
        @endforeach

      </div>
    </div>

    <!-- Tombol Navigasi Slider -->
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>

  </div>

  {{-- =========================================================================
       DATA LIST / TABULAR AUDIT SECTION
       ========================================================================= --}}
  <div class="data-list-card">
    
    <div class="data-list-header d-flex align-items-center justify-content-between flex-wrap" style="gap: 14px;">
      <div>
        <h5 class="mb-1 font-weight-bold" style="font-size: 17px; color: #0f172a;">
          <i class="fas fa-list-ul mr-2 text-success"></i>Daftar Lengkap Outlet Pertashop &amp; Legalitas
        </h5>
        <p class="text-muted mb-0" style="font-size: 13px;">
          Tabel master outlet Pertashop, alamat operasional, modal awal, dan operator yang bertugas.
        </p>
      </div>

      <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
        <div class="input-group input-group-sm" style="width: 240px;">
          <div class="input-group-prepend">
            <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px;"><i class="fas fa-search text-muted"></i></span>
          </div>
          <input type="text" id="outletSearchInput" class="form-control border-left-0" placeholder="Cari kode / nama outlet..." style="border-radius: 0 8px 8px 0;">
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover table-outlet-custom mb-0" id="outletMasterTable">
        <thead>
          <tr>
            <th class="text-center" style="width: 50px;">No</th>
            <th>Kode Outlet</th>
            <th>Nama Pertashop</th>
            <th>Alamat Lokasi</th>
            <th class="text-right">Total Investasi (Rp)</th>
            <th>Operator Bertugas</th>
            <th class="text-center" style="width: 170px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($shops as $idx => $shop)
            @php
              $totInvestasi = $shop->investors->sum('pivot.nominal') ?: ($shop->modal_awal ?? 0);
              $opNames = $shop->operators->map(fn($o) => $o->user->short_name ?? $o->user->name)->join(', ');
            @endphp
            <tr>
              <td class="text-center align-middle font-weight-bold text-muted">{{ $idx + 1 }}</td>
              
              <td class="align-middle">
                <span class="badge badge-success px-2 py-1" style="font-size: 12px; font-weight: 800; border-radius: 6px;">
                  {{ $shop->kode }}
                </span>
              </td>

              <td class="align-middle font-weight-bold" style="font-size: 14px; color: #0f172a;">
                {{ $shop->nama }}
              </td>

              <td class="align-middle text-muted" style="font-size: 12.5px;">
                <i class="fas fa-map-marker-alt text-danger mr-1"></i> {{ $shop->alamat }}
              </td>

              <td class="align-middle text-right font-weight-bold" style="font-size: 14px; color: #065f46;">
                Rp {{ number_format($totInvestasi, 0, ',', '.') }}
              </td>

              <td class="align-middle">
                @if($opNames)
                  <span class="text-dark font-weight-600" style="font-size: 12.5px;">{{ $opNames }}</span>
                @else
                  <span class="text-muted" style="font-size: 12px;">Belum ditugaskan</span>
                @endif
              </td>

              <td class="align-middle text-center">
                <div class="btn-group" role="group">
                  <a href="{{ route('shops.investors', $shop->id) }}" class="btn btn-sm btn-outline-success" title="Komposisi Investor" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fas fa-users"></i>
                  </a>

                  @if(Auth::user()->role !== 'investor')
                  <a href="{{ route('shops.edit', $shop->id) }}" class="btn btn-sm btn-outline-primary ml-1" title="Edit Pertashop" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fas fa-edit"></i>
                  </a>

                  <a href="{{ route('payroll-operator-assignments.index') }}" class="btn btn-sm btn-outline-info ml-1" title="Kelola Penugasan Operator" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fas fa-user-tag"></i>
                  </a>

                  <button type="button" onclick="confirmDeleteShop({{ $shop->id }}, '{{ addslashes($shop->nama) }}')" class="btn btn-sm btn-outline-danger ml-1" title="Hapus" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                  <form id="delete-shop-form-{{ $shop->id }}" action="{{ route('shops.destroy', $shop->id) }}" method="POST" style="display: none;">
                    @csrf
                    @method('DELETE')
                  </form>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  
  // =========================================================================
  // SWIPER INITIALIZATION (CENTERED DONUT WHEEL SYNC)
  // =========================================================================
  const swiper = new Swiper('.outlet-swiper', {
    centeredSlides: true,
    slidesPerView: 'auto',
    spaceBetween: 28,
    slideToClickedSlide: true,
    initialSlide: 0,
    grabCursor: true,
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    on: {
      init: function () {
        syncOutletWheel(this);
      },
      slideChange: function () {
        syncOutletWheel(this);
      }
    }
  });

  function syncOutletWheel(swiperInstance) {
    const activeIndex = swiperInstance.realIndex;
    const activeSlide = swiperInstance.slides[swiperInstance.activeIndex];
    
    // 1. Putar Roda Background
    const wheel = document.getElementById('outlet-wheel-bg');
    if (wheel) {
      wheel.style.transform = `rotate(${activeIndex * 60}deg)`;
    }

    // 2. Perbarui Nama Outlet di Label Atas Roda
    if (activeSlide) {
      const shopName = activeSlide.getAttribute('data-shop-name') || 'Outlet';
      const label = document.getElementById('outlet-wheel-label');
      if (label) {
        label.innerText = shopName;
      }
    }
  }

  // =========================================================================
  // LIVE SEARCH FILTER TABLE
  // =========================================================================
  const searchInput = document.getElementById('outletSearchInput');
  const tableRows = document.querySelectorAll('#outletMasterTable tbody tr');

  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const query = this.value.toLowerCase().trim();
      tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (query === '' || text.includes(query)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  }

  // =========================================================================
  // TOGGLE STATUS AJAX (TANPA RELOAD HALAMAN)
  // =========================================================================
  document.querySelectorAll('.toggle-status-btn').forEach(function (toggle) {
    toggle.addEventListener('change', function () {
      const isChecked = this.checked;
      const itemId = this.getAttribute('data-id');
      const endpoint = this.getAttribute('data-endpoint');
      const card = this.closest('.outlet-card');
      const badge = document.getElementById(`badge-status-${itemId}`);

      // Optimistic UI Update
      if (isChecked) {
        if (card) card.classList.remove('card-inactive');
        if (badge) {
          badge.className = 'status-badge-pill bg-emerald-100 text-emerald-800';
          badge.innerHTML = '<span class="pulse-dot"></span><span class="status-text">Aktif</span>';
        }
      } else {
        if (card) card.classList.add('card-inactive');
        if (badge) {
          badge.className = 'status-badge-pill bg-slate-200 text-slate-600';
          badge.innerHTML = '<span style="width:6px; height:6px; border-radius:50%; background:#94a3b8; display:inline-block;"></span><span class="status-text">Non-Aktif</span>';
        }
      }

      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
      fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({ is_active: isChecked ? 1 : 0 })
      })
      .then(res => res.json())
      .catch(err => {
        console.error('Gagal update status outlet:', err);
        this.checked = !isChecked;
      });
    });
  });

});

// =========================================================================
// SWEETALERT DELETE CONFIRMATION
// =========================================================================
function confirmDeleteShop(id, name) {
  const form = document.getElementById('delete-shop-form-' + id);
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Hapus Pertashop?',
      text: `Apakah Anda yakin ingin menghapus outlet "${name}"? Seluruh data operasional cabang ini akan terhapus.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus Pertashop',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm(`Hapus outlet "${name}"?`)) {
      form.submit();
    }
  }
}
</script>
@endpush
