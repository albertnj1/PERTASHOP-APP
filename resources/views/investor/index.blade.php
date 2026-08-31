@extends('layouts._new_admin')
@section('title', 'Master Investor & Portofolio Modal')

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
     SHOWCASE WRAPPER & RODA BACKGROUND (FULL WIDTH EXPANSIVE)
     ========================================================================== */
  .showcase-wrapper {
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

  /* Roda Background Berputar (Tepat di Tengah & Belakang Card) */
  .wheel-backdrop-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 580px;
    height: 580px;
    pointer-events: none;
    z-index: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  #gear-wheel-bg {
    width: 580px !important;
    height: 580px !important;
    border-radius: 50%;
    border: 2px dashed rgba(16, 185, 129, 0.45);
    transition: transform 0.7s cubic-bezier(0.34, 1.56, 0.64, 1);
    transform-origin: center center;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .wheel-inner-circle {
    width: 460px;
    height: 460px;
    border-radius: 50%;
    border: 1px solid rgba(16, 185, 129, 0.25);
    background: rgba(236, 253, 245, 0.35);
  }

  /* Badge Persentase di Atas Roda (Bebas Tabrakan Teks) */
  .wheel-percent-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(8px);
    padding: 6px 18px;
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
     SWIPER SLIDE & CARD STYLING (420PX WIDE & 1.08 SCALE ACTIVE)
     ========================================================================== */
  .investor-swiper {
    position: relative;
    z-index: 10;
    width: 100%;
    padding-top: 28px;
    padding-bottom: 40px;
  }

  /* 1. Perlebar Ukuran Slide Kartu */
  .investor-swiper .swiper-slide {
    width: 420px !important; /* Diperlebar ke 420px agar gagah dan mengisi kontainer luas */
    max-width: 90vw;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
  }

  /* 2. Beri Tinggi Kartu yang Cukup & Hilangkan Scrollbar Jelek */
  .investor-card {
    border-radius: 24px;
    padding: 26px;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(203, 213, 225, 0.8);
    background: #ffffff;
    color: #1e293b;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
    transform: scale(0.9);
    opacity: 0.7;
    min-height: 480px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  /* Hilangkan scrollbar bawaan browser tapi tetap bisa scroll halus jika cabang banyak */
  .branch-list-container {
    max-height: 220px;
    overflow-y: auto;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
  }

  .branch-list-container::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Edge */
  }

  .avatar-box {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 15px;
    background-color: #ecfdf5;
    color: #065f46;
    transition: all 0.4s ease;
  }

  .badge-cabang {
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    background-color: #f1f5f9;
    color: #475569;
    transition: all 0.4s ease;
  }

  .card-title {
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -0.01em;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .nominal-box {
    padding: 12px 16px;
    border-radius: 14px;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    margin-bottom: 14px;
    transition: all 0.4s ease;
  }

  .share-pill {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    background-color: #f1f5f9;
    color: #334155;
    transition: all 0.4s ease;
  }

  .btn-action {
    display: block;
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 12.5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    text-align: center;
    background-color: #065f46;
    color: #ffffff;
    border: none;
    box-shadow: 0 4px 12px rgba(6, 95, 70, 0.2);
    text-decoration: none !important;
    transition: all 0.3s ease;
  }

  .btn-action:hover {
    background-color: #047857;
    color: #ffffff;
    transform: translateY(-2px);
  }

  /* ==========================================================================
     ACTIVE STATE KARTU DI TENGAH (STARBUCKS EMERALD SIGNATURE)
     ========================================================================== */
  .swiper-slide-active .investor-card {
    background-color: #064e3b !important; /* Hijau Starbucks */
    color: #ffffff !important;
    transform: scale(1.08) !important;
    opacity: 1 !important;
    box-shadow: 0 25px 50px -12px rgba(6, 78, 59, 0.45), 0 0 0 1px rgba(52, 211, 153, 0.4) !important;
    border-color: rgba(52, 211, 153, 0.5) !important;
    z-index: 20;
  }

  .swiper-slide-active .avatar-box {
    background-color: #ffffff !important;
    color: #064e3b !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  .swiper-slide-active .badge-cabang {
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

  .swiper-slide-active .btn-action {
    background-color: #ffffff !important;
    color: #064e3b !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
  }

  .swiper-slide-active .btn-action:hover {
    background-color: #d1fae5 !important;
    color: #064e3b !important;
  }

  .swiper-slide-active .card-subtext {
    color: #a7f3d0 !important;
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

  .card-inactive .btn-action {
    background-color: #64748b !important;
    color: #ffffff !important;
    cursor: not-allowed;
  }

  .swiper-slide-active .card-inactive {
    background-color: #334155 !important; /* Abu-abu slate gelap */
    filter: grayscale(0);
    opacity: 0.85 !important;
    border-color: rgba(148, 163, 184, 0.4) !important;
    box-shadow: 0 20px 40px -10px rgba(51, 65, 85, 0.4) !important;
  }

  .swiper-slide-active .card-inactive .avatar-box {
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

  /* Navigasi Panah */
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

  .swiper-button-prev { left: 12px !important; }
  .swiper-button-next { right: 12px !important; }

  /* ==========================================================================
     DATA LIST / TABULAR SECTION
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

  .table-investor-custom th {
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

  .table-investor-custom td {
    vertical-align: middle !important;
    padding: 14px 16px;
    font-size: 13px;
    border-bottom: 1px solid #f1f5f9;
  }

  .table-investor-custom tbody tr:hover {
    background-color: #f8fafc;
  }
</style>
@endpush

@section('content')
<div class="container-fluid py-2">

  {{-- TOP TITLE BAR --}}
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap" style="gap: 16px;">
    <div>
      <span class="badge" style="background: #d1fae5; color: #065f46; font-size: 11px; font-weight: 700; border-radius: 12px; padding: 5px 12px; text-transform: uppercase; letter-spacing: 0.05em;">
        <i class="fas fa-crown text-warning mr-1"></i> Executive Investor Showcase
      </span>
      <h1 class="page-title mt-2 mb-1" style="font-size: 26px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em;">
        Master Investor &amp; Portofolio Modal
      </h1>
      <p class="text-muted mb-0" style="font-size: 13.5px;">
        Visualisasi interaktif porsi kepemilikan modal, rekapitulasi dividen, dan pembagian saham per cabang Pertashop.
      </p>
    </div>

    <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
      @if(Auth::user()->role !== 'investor')
      <a href="{{ route('investors.create') }}" class="btn font-weight-bold" style="background: var(--sb-emerald-main); color: #ffffff; border-radius: 9999px; padding: 10px 22px; font-size: 13px; box-shadow: 0 4px 14px rgba(0, 98, 65, 0.25);">
        <i class="fas fa-user-plus mr-1"></i> Tambah Investor Baru
      </a>
      @endif
    </div>
  </div>

  {{-- SUMMARY STAT PILLS (GRID 3 KOLOM) --}}
  <div class="row mb-4">
    <div class="col-md-4 mb-3 mb-md-0">
      <div class="p-3 d-flex align-items-center justify-content-between" style="background: #ffffff; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
        <div class="d-flex align-items-center" style="gap: 14px;">
          <div style="width: 48px; height: 48px; border-radius: 14px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px;">
            <i class="fas fa-users"></i>
          </div>
          <div>
            <div class="text-muted" style="font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Total Pemodal</div>
            <div style="font-size: 20px; font-weight: 800; color: #0f172a;">{{ $totalInvestorsCount }} Investor</div>
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
            <div class="text-muted" style="font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Total Modal Terhimpun</div>
            <div style="font-size: 20px; font-weight: 800; color: #0f172a;">Rp {{ number_format($totalCapitalAll, 0, ',', '.') }}</div>
          </div>
        </div>
        <span class="badge badge-warning px-2 py-1 text-white" style="border-radius: 8px; background: #f59e0b;">Master</span>
      </div>
    </div>

    <div class="col-md-4">
      <div class="p-3 d-flex align-items-center justify-content-between" style="background: #ffffff; border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
        <div class="d-flex align-items-center" style="gap: 14px;">
          <div style="width: 48px; height: 48px; border-radius: 14px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 20px;">
            <i class="fas fa-gas-pump"></i>
          </div>
          <div>
            <div class="text-muted" style="font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Jaringan Cabang</div>
            <div style="font-size: 20px; font-weight: 800; color: #0f172a;">{{ $totalShopsCount }} Outlet Pertashop</div>
          </div>
        </div>
        <span class="badge badge-primary px-2 py-1" style="border-radius: 8px;">Terdistribusi</span>
      </div>
    </div>
  </div>

  {{-- =========================================================================
       SHOWCASE WRAPPER (CENTERED ROTATING WHEEL + SWIPER)
       ========================================================================= --}}
  <div class="showcase-wrapper">
    
    <!-- 1. RODA BACKGROUND BERPUTAR (Tepat di Tengah & di Belakang Card) -->
    <div class="wheel-backdrop-container">
      <div id="gear-wheel-bg">
        <div class="wheel-inner-circle"></div>
      </div>
      
      <!-- Angka Persentase di Atas Roda (Bebas Tabrakan Teks) -->
      <div class="wheel-percent-badge">
        <span style="font-size: 11px; font-weight: 700; color: #065f46; text-transform: uppercase;">Porsi Modal:</span>
        <span id="wheel-percent-label" style="font-size: 14px; font-weight: 900; color: #059669;">0%</span>
      </div>
    </div>

    <!-- 2. SWIPER CAROUSEL CONTAINER -->
    <div class="swiper investor-swiper">
      <div class="swiper-wrapper align-items-center">
        
        @foreach($investorsList as $inv)
          @php
            $totNominal = $inv->shops->sum('pivot.nominal');
            $shopCount = $inv->shops->count();
            $percentageShare = $totalCapitalAll > 0 ? ($totNominal / $totalCapitalAll) * 100 : 0;
            $initials = strtoupper(substr($inv->user->name ?? $inv->name ?? 'IN', 0, 2));
            $isActive = $inv->is_active ?? ($inv->user->is_active ?? true);
          @endphp
          <div class="swiper-slide" 
               data-percent="{{ number_format($percentageShare, 1) }}%">
            
            <div class="investor-card {{ !$isActive ? 'card-inactive' : '' }}">
              
              <div>
                <!-- Header Card: Avatar + Status + Toggle + Tag Cabang -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div class="d-flex align-items-center" style="gap: 8px;">
                    <div class="avatar-box">
                      {{ $initials }}
                    </div>
                    <span id="badge-status-{{ $inv->id }}" class="status-badge-pill {{ $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}" style="padding: 3px 9px; border-radius: 9999px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
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
                    <span class="badge-cabang">
                      <i class="fas fa-gas-pump mr-1"></i> {{ $shopCount }} Cabang
                    </span>

                    @if(Auth::user()->role !== 'investor')
                    <label class="status-toggle-label" title="Ubah Status Aktif / Non-Aktif">
                      <input type="checkbox" 
                             class="status-toggle-input toggle-status-btn" 
                             data-id="{{ $inv->id }}" 
                             data-endpoint="{{ route('investors.toggle-status', $inv->id) }}" 
                             {{ $isActive ? 'checked' : '' }}>
                      <div class="status-toggle-track"></div>
                    </label>
                    @endif
                  </div>
                </div>

                <!-- Nama & Subtitle ID -->
                <h3 class="card-title text-truncate" title="{{ $inv->user->name ?? $inv->name }}">
                  {{ $inv->user->name ?? $inv->name }}
                </h3>
                <p class="card-subtext mb-3" style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; color: #64748b;">
                  Portfolio Pemodal • ID: #{{ str_pad($inv->id, 3, '0', STR_PAD_LEFT) }}
                </p>
                
                <!-- Nominal Box -->
                <div class="nominal-box">
                  <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: block; opacity: 0.8; margin-bottom: 2px;">
                    Total Modal Ditempatkan
                  </span>
                  <span style="font-size: 17px; font-weight: 900; letter-spacing: -0.01em;">
                    Rp {{ number_format($totNominal, 0, ',', '.') }}
                  </span>
                </div>

                <!-- Rincian Cabang (Maksimal 3-4 Baris) -->
                <div class="branch-list-container mb-4">
                  @forelse($inv->shops as $sh)
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size: 12px; border-color: rgba(226, 232, 240, 0.4) !important;">
                      <span class="text-truncate pr-2" style="max-width: 180px; font-weight: 600;">
                        {{ $sh->nama }}
                      </span>
                      <span class="share-pill">
                        {{ number_format($sh->pivot->persentase ?? 0, 1) }}%
                      </span>
                    </div>
                  @empty
                    <div class="text-center py-2 text-muted" style="font-size: 11.5px;">
                      Belum ada alokasi cabang
                    </div>
                  @endforelse
                </div>
              </div>

              <!-- Tombol Edit Data -->
              <div>
                @if(Auth::user()->role !== 'investor')
                  <a href="{{ route('investors.edit', $inv->id) }}" class="btn-action">
                    <i class="fas fa-edit mr-1"></i> Edit Data Investor
                  </a>
                @else
                  <a href="{{ route('investors.show', $inv->id) }}" class="btn-action">
                    <i class="fas fa-eye mr-1"></i> Detail Portofolio
                  </a>
                @endif
              </div>

            </div>

          </div>
        @endforeach

      </div>
    </div>

    <!-- Tombol Panah Navigasi Kiri & Kanan -->
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
          <i class="fas fa-list-ul mr-2 text-success"></i>Daftar Lengkap Pemegang Saham &amp; Modal
        </h5>
        <p class="text-muted mb-0" style="font-size: 13px;">
          Tabel data master profil investor, rekening penerimaan dividen, dan status investasi.
        </p>
      </div>

      <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
        <div class="input-group input-group-sm" style="width: 220px;">
          <div class="input-group-prepend">
            <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px;"><i class="fas fa-search text-muted"></i></span>
          </div>
          <input type="text" id="investorSearchInput" class="form-control border-left-0" placeholder="Cari nama / bank..." style="border-radius: 0 8px 8px 0;">
        </div>

        <select id="filterShopSelect" class="form-control form-control-sm" style="width: 180px; border-radius: 8px;">
          <option value="">Semua Pertashop</option>
          @foreach($shops as $shop)
            <option value="{{ $shop->nama }}">{{ $shop->nama }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover table-investor-custom mb-0" id="investorMasterTable">
        <thead>
          <tr>
            <th class="text-center" style="width: 50px;">No</th>
            <th>Nama Investor &amp; Gelar</th>
            <th>Kontak &amp; Identitas</th>
            <th>Portofolio Saham Cabang</th>
            <th class="text-right">Total Investasi (Rp)</th>
            <th>Rekening Bank</th>
            <th class="text-center" style="width: 160px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($investorsList as $idx => $inv)
            @php
              $totNominal = $inv->shops->sum('pivot.nominal');
              $initials = strtoupper(substr($inv->user->name ?? $inv->name ?? 'IN', 0, 2));
            @endphp
            <tr>
              <td class="text-center align-middle font-weight-bold text-muted">{{ $idx + 1 }}</td>
              
              <td class="align-middle">
                <div class="d-flex align-items-center" style="gap: 10px;">
                  <div style="width: 38px; height: 38px; border-radius: 50%; background: #ecfdf5; color: #059669; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 13px; border: 1px solid #a7f3d0;">
                    {{ $initials }}
                  </div>
                  <div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 14px;">{{ $inv->user->name ?? $inv->name }}</div>
                    <small class="text-muted">{{ $inv->nama_lengkap_gelar ?: '-' }}</small>
                  </div>
                </div>
              </td>

              <td class="align-middle">
                <div style="font-size: 12.5px; color: #334155;">
                  <i class="fas fa-envelope mr-1 text-muted"></i> {{ $inv->user->email ?? $inv->email_pribadi ?? '-' }}
                </div>
                <div class="text-muted" style="font-size: 11.5px; margin-top: 2px;">
                  <i class="fas fa-phone mr-1 text-muted"></i> {{ $inv->no_hp ?? '-' }}
                </div>
              </td>

              <td class="align-middle">
                <div class="d-flex flex-wrap" style="gap: 4px; max-width: 260px;">
                  @foreach($inv->shops as $sh)
                    <span class="badge" style="background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; font-size: 11px; padding: 4px 8px; border-radius: 6px;">
                      {{ $sh->nama }} <strong>({{ number_format($sh->pivot->persentase ?? 0, 1) }}%)</strong>
                    </span>
                  @endforeach
                </div>
              </td>

              <td class="align-middle text-right font-weight-bold" style="font-size: 14px; color: #065f46;">
                Rp {{ number_format($totNominal, 0, ',', '.') }}
              </td>

              <td class="align-middle">
                <div style="font-weight: 600; color: #1e293b; font-size: 12.5px;">{{ $inv->nama_bank ?? 'Bank' }}</div>
                <div class="text-muted" style="font-size: 11.5px;">{{ $inv->no_rekening ?? '-' }}</div>
                <small class="text-muted" style="font-size: 10.5px;">a.n {{ $inv->atas_nama_rekening ?? '-' }}</small>
              </td>

              <td class="align-middle text-center">
                <div class="btn-group" role="group">
                  <a href="{{ route('investors.show', $inv->id) }}" class="btn btn-sm btn-outline-info" title="Detail Portofolio" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fas fa-eye"></i>
                  </a>
                  
                  @if(Auth::user()->role !== 'investor')
                  <a href="{{ route('investors.edit', $inv->id) }}" class="btn btn-sm btn-outline-primary ml-1" title="Edit Data" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fas fa-edit"></i>
                  </a>

                  <a href="{{ route('investors.export-pdf', $inv->id) }}" class="btn btn-sm btn-outline-success ml-1" title="Download Ringkasan PDF" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fas fa-file-pdf"></i>
                  </a>

                  <button type="button" onclick="confirmDeleteInvestor({{ $inv->id }}, '{{ addslashes($inv->user->name ?? $inv->name) }}')" class="btn btn-sm btn-outline-danger ml-1" title="Hapus" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                  <form id="delete-form-{{ $inv->id }}" action="{{ route('investors.destroy', $inv->id) }}" method="POST" style="display: none;">
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
  // SWIPER INITIALIZATION (STARBUCKS COVERFLOW SYNC)
  // =========================================================================
  const swiper = new Swiper('.investor-swiper', {
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
        syncWheelWithSlide(this);
      },
      slideChange: function () {
        syncWheelWithSlide(this);
      }
    }
  });

  function syncWheelWithSlide(swiperInstance) {
    const activeIndex = swiperInstance.realIndex;
    const activeSlide = swiperInstance.slides[swiperInstance.activeIndex];
    
    // 1. Putar Roda Background
    const wheel = document.getElementById('gear-wheel-bg');
    if (wheel) {
      wheel.style.transform = `rotate(${activeIndex * 60}deg)`;
    }

    // 2. Perbarui Nilai Persentase
    if (activeSlide) {
      const percent = activeSlide.getAttribute('data-percent') || '0%';
      const label = document.getElementById('wheel-percent-label');
      if (label) {
        label.innerText = percent;
      }
    }
  }

  // =========================================================================
  // TABLE FILTER & SEARCH
  // =========================================================================
  const searchInput = document.getElementById('investorSearchInput');
  const shopFilter = document.getElementById('filterShopSelect');
  const tableRows = document.querySelectorAll('#investorMasterTable tbody tr');

  function filterTable() {
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedShop = shopFilter ? shopFilter.value.toLowerCase().trim() : '';

    tableRows.forEach(row => {
      const text = row.textContent.toLowerCase();
      const matchesSearch = query === '' || text.includes(query);
      const matchesShop = selectedShop === '' || text.includes(selectedShop);

      if (matchesSearch && matchesShop) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }

  if (searchInput) searchInput.addEventListener('input', filterTable);
  if (shopFilter) shopFilter.addEventListener('change', filterTable);

  // =========================================================================
  // TOGGLE STATUS AJAX (TANPA RELOAD HALAMAN)
  // =========================================================================
  document.querySelectorAll('.toggle-status-btn').forEach(function (toggle) {
    toggle.addEventListener('change', function () {
      const isChecked = this.checked;
      const itemId = this.getAttribute('data-id');
      const endpoint = this.getAttribute('data-endpoint');
      const card = this.closest('.investor-card');
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
        console.error('Gagal update status investor:', err);
        this.checked = !isChecked;
      });
    });
  });

});

// =========================================================================
// SWEETALERT DELETE CONFIRMATION
// =========================================================================
function confirmDeleteInvestor(id, name) {
  const form = document.getElementById('delete-form-' + id);
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Hapus Investor?',
      text: `Apakah Anda yakin ingin menghapus data investor "${name}"? Seluruh alokasi saham di cabang akan terhapus.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus Investor',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm(`Hapus data investor "${name}"?`)) {
      form.submit();
    }
  }
}
</script>
@endpush
