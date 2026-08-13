@extends('layouts._new_admin')
@section('title', 'Sistem Penggajian')

@push('style')
<style>
  /* ─── Tab Navigation ─────────────────────────────────── */
  .payroll-tabs {
    display: flex;
    gap: 4px;
    border-bottom: 2px solid var(--border);
    margin-bottom: 24px;
    flex-wrap: wrap;
  }
  .payroll-tab-btn {
    padding: 9px 20px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    background: transparent;
    color: var(--muted);
    border-radius: 8px 8px 0 0;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: color .15s, border-color .15s, background .15s;
  }
  .payroll-tab-btn:hover {
    color: var(--text);
    background: var(--page-bg);
  }
  .payroll-tab-btn.active {
    color: var(--green);
    border-bottom-color: var(--green);
    background: var(--page-bg);
  }
  .payroll-tab-pane { display: none; }
  .payroll-tab-pane.active { display: block; }

  /* ─── Status Pills ───────────────────────────────────── */
  .status-ok   { color: #15803d; font-weight: 600; }
  .status-warn { color: #b45309; font-weight: 600; }
  .status-bad  { color: #b91c1c; font-weight: 600; }

  /* ─── Tab 3 Lock Info ─────────────────────────────────── */
  .lock-info-card {
    background: var(--page-bg);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 18px 22px;
    margin-bottom: 16px;
    font-size: 14px;
  }
  .lock-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
  }
  .lock-info-row:last-child { border-bottom: none; }
  .lock-info-label { color: var(--muted); font-size: 13px; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Sistem Penggajian</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Pengaturan, proses, dan persetujuan penggajian operator</p>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 10px; border: none; background: #dcfce7; color: #15803d;">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif
@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius: 10px; border: none; background: #fee2e2; color: #b91c1c;">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif

{{-- ═══ TAB NAVIGATION ═══════════════════════════════════════════════════════ --}}
<div class="payroll-tabs">
  <button class="payroll-tab-btn active" data-tab="pengaturan">Pengaturan Gaji</button>
  <button class="payroll-tab-btn" data-tab="proses">Proses Gaji Bulanan</button>
  <button class="payroll-tab-btn" data-tab="kunci">Kunci &amp; Setujui</button>
  @if($canViewBenchmark)
    <button class="payroll-tab-btn" data-tab="perbandingan">Perbandingan Toko</button>
  @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 1: PENGATURAN GAJI                                                    --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
<div class="payroll-tab-pane active" id="tab-pengaturan">

  {{-- Filter Toko --}}
  <div class="panel mb-4" style="padding: 12px 18px;">
    <form method="GET" action="{{ route('payroll-systems.index') }}" class="d-flex align-items-center" style="gap: 12px; flex-wrap: wrap;">
      <input type="hidden" name="tab" value="pengaturan">
      <label class="text-muted mb-0" style="font-size: 12px; font-weight: 600; white-space: nowrap;">Filter Toko:</label>
      <select name="shop_id" class="form-control form-control-sm" style="border-radius: 8px; width: auto; min-width: 160px;" onchange="this.form.submit()">
        @if(in_array(Auth::user()->role, ['super-admin', 'super_admin']))
          <option value="">Semua Toko</option>
        @endif
        @foreach($shops as $shop)
          <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
        @endforeach
      </select>
      @if(collect(['super-admin', 'super_admin', 'admin'])->contains(Auth::user()->role))
        <a href="{{ route('payroll-systems.create') }}" class="btn btn-primary btn-sm ml-auto" style="border-radius: 8px; font-weight: 600; font-size: 13px; white-space: nowrap;">
          Tambah Pengaturan Baru
        </a>
      @endif
    </form>
  </div>

  {{-- Daftar Pengaturan Gaji --}}
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">Daftar Pengaturan Gaji per Toko</div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: var(--page-bg);">
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Toko</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Nama Pengaturan</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Rate/Liter</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Gaji Pokok</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Transport/Hari</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Potongan Absen/Hari</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Metode Potongan Absen</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Perlakuan Losses</th>
            <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Status</th>
            <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($payrollSystems as $ps)
          <tr>
            <td style="font-size: 13px;">
              <span class="status-pill" style="background:#e2e8f0; color:#475569;">{{ $ps->shop->nama }}</span>
            </td>
            <td style="font-size: 13px; font-weight: 600;">{{ $ps->nama_sistem }}</td>
            <td style="font-size: 13px;">
              @if($ps->ada_rate_per_liter)
                Rp {{ number_format($ps->rate_per_liter, 0, ',', '.') }}
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td style="font-size: 13px;">
              @if($ps->ada_gaji_pokok)
                Rp {{ number_format($ps->nominal_gaji_pokok, 0, ',', '.') }}
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td style="font-size: 13px;">
              @if(floatval($ps->rate_transport_per_hari ?? 0) > 0)
                Rp {{ number_format($ps->rate_transport_per_hari, 0, ',', '.') }}
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td style="font-size: 13px; color: var(--red);">
              Rp {{ number_format($ps->potongan_per_hari_alpha, 0, ',', '.') }}
            </td>
            <td style="font-size: 13px;">
              @if(($ps->metode_potongan_alpha ?? 'nominal_tetap') === 'prorata_gaji_pokok')
                Prorata dari Gaji
              @else
                Nominal Tetap
              @endif
            </td>
            <td style="font-size: 13px;">
              {{ $ps->perlakuan_losses_gain_label }}
            </td>
            <td class="text-center">
              @if($ps->aktif)
                <span class="status-pill" style="background:#dcfce7; color:#15803d;">Aktif</span>
              @else
                <span class="status-pill" style="background:#fee2e2; color:#b91c1c;">Nonaktif</span>
              @endif
            </td>
            <td class="text-center">
              @if(collect(['super-admin', 'super_admin', 'admin'])->contains(Auth::user()->role))
                <a href="{{ route('payroll-systems.edit', $ps->id) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px; font-size: 11px; padding: 3px 10px;">
                  Edit
                </a>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="10" class="text-center text-muted py-4" style="font-size: 13px;">
              Belum ada pengaturan gaji.
              @if(collect(['super-admin', 'super_admin', 'admin'])->contains(Auth::user()->role))
                <a href="{{ route('payroll-systems.create') }}" style="color: var(--blue);">Tambah sekarang</a>.
              @endif
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>{{-- /tab-pengaturan --}}


{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 2: PROSES GAJI BULANAN                                                --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
<div class="payroll-tab-pane" id="tab-proses">

  <div class="row">

    {{-- Panel Generate --}}
    <div class="col-lg-4">
      <div class="panel mb-4">
        <div class="panel-head">
          <div class="panel-title">Hitung Gaji Bulan Ini</div>
        </div>
        <form action="{{ route('payroll.generate') }}" method="POST" id="form-generate">
          @csrf
          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Toko</label>
            <select name="shop_id" id="gen-shop" class="form-control" style="border-radius: 8px;" required onchange="window.location.href='?shop_id=' + this.value + '&tahun={{ $selectedTahun }}'">
              @foreach($shops as $shop)
                <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-row">
            <div class="form-group col-6">
              <label class="font-weight-bold" style="font-size: 12px; color: var(--muted); text-transform: uppercase;">Bulan</label>
              <select name="bulan" id="gen-bulan" class="form-control" required onchange="checkFinalStatus()">
                @foreach(range(1,12) as $b)
                  @php
                    $namaB = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$b];
                    $statusB = $periodStatusMap->get($b);
                  @endphp
                  <option value="{{ $b }}"
                    data-status="{{ $statusB ?? '' }}"
                    {{ now()->month == $b ? 'selected' : '' }}>
                    {{ $namaB }}{{ $statusB ? ' (' . ($statusB === 'final' ? 'Final' : 'Draft') . ')' : '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-6">
              <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Tahun</label>
              <select name="tahun" class="form-control" style="border-radius: 8px;" required>
                @foreach($availableYears as $y)
                  <option value="{{ $y }}" {{ $selectedTahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div id="final-alert" class="alert alert-danger py-2 small d-none" style="border-radius: 8px;">
            Periode sudah final dan terkunci, tidak bisa dihitung ulang.
          </div>
          <div id="draft-alert" class="alert alert-warning py-2 small d-none" style="border-radius: 8px;">
            Sudah ada draft untuk bulan ini. Menghitung ulang akan mengganti data draft yang ada.
          </div>

          {{-- Komponen Tambahan / Potongan Dinamis (Keterangan + Nominal) --}}
          <div class="card my-3" style="border: 1px dashed #cbd5e1; border-radius: 10px; background: #f8fafc;">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span style="font-size: 12px; font-weight: 700; color: #1e293b;">➕ Komponen Tambahan (Opsional)</span>
                <button type="button" class="btn btn-xs btn-outline-primary btn-add-custom-row" style="border-radius: 6px; font-weight: 600; font-size: 11px;">
                  + Tambah Baris
                </button>
              </div>
              <div id="custom-items-container-sys">
                {{-- Baris akan ditambahkan via JavaScript --}}
              </div>
            </div>
          </div>

          <p class="text-muted small">Sistem akan otomatis menarik data laporan harian dan jadwal shift untuk menghitung gaji.</p>

          <button type="button" onclick="confirmSingleGenerate(event)" id="btn-submit-gen" class="btn btn-success btn-block font-weight-bold" style="border-radius: 8px;">
            Hitung Gaji Bulan Ini
          </button>
        </form>

        <hr class="my-3">

        <form id="form-bulk-generate" action="{{ route('payroll.bulk-generate') }}" method="POST">
          @csrf
          <input type="hidden" name="bulan" value="{{ now()->month }}">
          <input type="hidden" name="tahun" value="{{ $selectedTahun }}">
          <button type="button" onclick="confirmBulkGenerate(event)" class="btn btn-outline-primary btn-block font-weight-bold" style="border-radius: 8px;">
            Hitung Semua Toko Sekaligus
          </button>
        </form>
      </div>

      {{-- Link cepat ke Assign Operator --}}
      <div class="panel">
        <div class="panel-head">
          <div class="panel-title">Pengaturan Tambahan</div>
        </div>
        <div class="py-1">
          <a href="{{ route('payroll-operator-assignments.index') }}" class="btn btn-outline-secondary btn-sm btn-block" style="border-radius: 6px;">
            Kelola Penugasan Operator
          </a>
        </div>
      </div>
    </div>

    {{-- Daftar Riwayat Penggajian --}}
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-body py-2">
          <form method="GET" action="{{ route('payroll-systems.index') }}" class="form-inline">
            <input type="hidden" name="tab" value="proses">
            <label class="mr-2 font-weight-bold" style="font-size: 13px;">Tahun:</label>
            <select name="tahun" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
              @foreach($availableYears as $y)
                <option value="{{ $y }}" {{ $selectedTahun == $y ? 'selected' : '' }}>{{ $y }}</option>
              @endforeach
            </select>
            <label class="mr-2 font-weight-bold" style="font-size: 13px;">Toko:</label>
            <select name="shop_id" class="form-control form-control-sm" onchange="this.form.submit()">
              @foreach($shops as $shop)
                <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
              @endforeach
            </select>
          </form>
        </div>
      </div>

        <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Riwayat Penggajian — {{ $selectedTahun }}</h5>
        </div>
        <div class="card-body p-0">
          @if($periods->isEmpty())
            <div class="text-center text-muted py-5">
              <i class="fas fa-file-invoice-dollar fa-3x mb-3 d-block" style="opacity:.3"></i>
              Belum ada data penggajian untuk tahun {{ $selectedTahun }}.<br>
              Gunakan form di sebelah kiri untuk menghitung gaji.
            </div>
          @else
            @foreach($periods as $period)
            <div class="px-3 pt-3 pb-1 border-bottom d-flex justify-content-between align-items-center">
              <div>
                <strong style="font-size: 14px;">{{ $period->periode_label }}</strong>
                <span class="ml-2">
                  @if($period->isFinal())
                    <span class="badge badge-success">Final</span>
                  @else
                    <span class="badge badge-warning">Draft</span>
                  @endif
                </span>
                <span class="text-muted ml-2" style="font-size: 12px;">— {{ $period->shop->nama }}</span>
              </div>
              <div class="d-flex" style="gap: 6px;">
                <a href="{{ route('payroll.show', $period->id) }}" class="btn btn-sm btn-outline-secondary" style="font-size: 12px;">
                  Lihat Detail
                </a>
                <a href="{{ route('payroll.export-pdf', $period->id) }}" class="btn btn-sm btn-outline-danger" style="font-size: 12px;">
                  Cetak Slip Gaji
                </a>
                @if($period->isDraft())
                <button class="btn btn-sm btn-outline-danger btn-delete-period" data-id="{{ $period->id }}" style="font-size: 12px;">
                  Hapus Draft
                </button>
                @endif
              </div>
            </div>
            <div class="table-responsive mb-3">
              <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th style="font-size: 11px;">Nama Operator</th>
                    <th class="text-right" style="font-size: 11px;">Gaji Pokok</th>
                    <th class="text-right" style="font-size: 11px;">Komisi Liter</th>
                    <th class="text-right" style="font-size: 11px;">Transport</th>
                    <th class="text-right" style="font-size: 11px;">Potongan</th>
                    <th class="text-right" style="font-size: 11px; color: var(--green); font-weight: 700;">Total Diterima</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($period->details as $detail)
                  <tr>
                    <td style="font-size: 13px; font-weight: 600;">{{ $detail->operator->user?->name ?? '-' }}</td>
                    <td class="text-right" style="font-size: 13px;">
                      @if(floatval($detail->gaji_pokok) > 0)
                        Rp {{ number_format($detail->gaji_pokok, 0, ',', '.') }}
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td class="text-right" style="font-size: 13px;">
                      @if(floatval($detail->gaji_variable) > 0)
                        Rp {{ number_format($detail->gaji_variable, 0, ',', '.') }}
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td class="text-right" style="font-size: 13px;">
                      @if(floatval($detail->uang_transport) > 0)
                        Rp {{ number_format($detail->uang_transport, 0, ',', '.') }}
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td class="text-right" style="font-size: 13px; color: var(--red);">
                      @php $totalPotongan = floatval($detail->potongan_tidak_masuk) + floatval($detail->kurang_setoran) + floatval($detail->tabungan_gaji) + floatval($detail->tabungan_setoran) + floatval($detail->potongan_hutang); @endphp
                      @if($totalPotongan > 0)
                        - Rp {{ number_format($totalPotongan, 0, ',', '.') }}
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td class="text-right font-weight-bold" style="font-size: 14px; color: var(--green);">
                      Rp {{ number_format($detail->hitungTHP(), 0, ',', '.') }}
                    </td>
                  </tr>
                  @endforeach
                  @if($period->details->isEmpty())
                  <tr>
                    <td colspan="6" class="text-center text-muted py-3" style="font-size: 13px;">
                      Belum ada data operator. Klik "Lihat Detail" untuk cek.
                    </td>
                  </tr>
                  @endif
                </tbody>
                <tfoot class="thead-light">
                  <tr>
                    <td colspan="5" class="text-right font-weight-bold" style="font-size: 13px;">Total Diterima</td>
                    <td class="text-right font-weight-bold" style="font-size: 14px; color: var(--green);">
                      Rp {{ number_format($period->details->sum(fn($d) => $d->hitungTHP()), 0, ',', '.') }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
            @endforeach
          @endif
        </div>
      </div>
    </div>

  </div>{{-- /row --}}
</div>{{-- /tab-proses --}}


{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 3: KUNCI & SETUJUI                                                    --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
<div class="payroll-tab-pane" id="tab-kunci">

  {{-- Filter Toko & Bulan --}}
  <div class="panel mb-4" style="padding: 14px 18px;">
    <form method="GET" action="{{ route('payroll-systems.index') }}" class="d-flex align-items-center" style="gap: 16px; flex-wrap: wrap;">
      <input type="hidden" name="tab" value="kunci">
      <div class="d-flex align-items-center" style="gap: 8px;">
        <label class="text-muted mb-0" style="font-size: 12px; font-weight: 600; white-space: nowrap;">Toko:</label>
        <select name="shop_id" class="form-control form-control-sm" style="border-radius: 8px; min-width: 160px;" onchange="this.form.submit()">
          @foreach($shops as $shop)
            <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
          @endforeach
        </select>
      </div>
      <div class="d-flex align-items-center" style="gap: 8px;">
        <label class="text-muted mb-0" style="font-size: 12px; font-weight: 600; white-space: nowrap;">Bulan:</label>
        <input type="month" name="year_month" class="form-control form-control-sm" style="border-radius: 8px;"
          value="{{ $selectedMonth }}" onchange="this.form.submit()">
      </div>
    </form>
  </div>

  <div class="row">
    <div class="col-md-6">
      {{-- Status Laporan --}}
      <div class="panel mb-4">
        <div class="panel-head">
          <div class="panel-title">Status Laporan Bulan Ini</div>
        </div>
        <div class="lock-info-card">
          <div class="lock-info-row">
            <span class="lock-info-label">Jumlah laporan harian masuk</span>
            <span>
              <strong>{{ $jumlahLaporanMasuk }}</strong> dari {{ $jumlahHariBulan }} hari
              @if($jumlahLaporanMasuk >= $jumlahHariBulan)
                <span class="status-ok ml-1">(Lengkap)</span>
              @else
                <span class="status-warn ml-1">(Belum lengkap)</span>
              @endif
            </span>
          </div>
          <div class="lock-info-row">
            <span class="lock-info-label">Semua laporan sudah diverifikasi</span>
            <span>
              @if($semuaLaporanTerverifikasi)
                <span class="status-ok">Ya</span>
              @else
                <span class="status-bad">Belum</span>
              @endif
            </span>
          </div>
          <div class="lock-info-row">
            <span class="lock-info-label">Status kunci periode</span>
            <span>
              @if($isLocked)
                <span class="status-ok">Terkunci</span>
              @else
                <span class="text-muted">Terbuka</span>
              @endif
            </span>
          </div>
          @if($isLocked && $periodLockObj)
            <div class="lock-info-row">
              <span class="lock-info-label">Dikunci oleh</span>
              <span class="text-muted">{{ $periodLockObj->locker?->name ?? '-' }}</span>
            </div>
            <div class="lock-info-row">
              <span class="lock-info-label">Waktu dikunci</span>
              <span class="text-muted">{{ $periodLockObj->locked_at ? \Carbon\Carbon::parse($periodLockObj->locked_at)->format('d M Y, H:i') : '-' }}</span>
            </div>
          @endif
        </div>
      </div>

      {{-- Aksi Kunci --}}
      <div class="panel">
        <div class="panel-head">
          <div class="panel-title">Tindakan</div>
        </div>
        <div>
          @if(!$isLocked)
            <p class="text-muted small mb-3">Setelah dikunci, data bulan ini tidak bisa diubah lagi tanpa membuka kunci ulang.</p>
            <form action="{{ route('reports.approval.lock-period') }}" method="POST" id="form-lock">
              @csrf
              <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
              <input type="hidden" name="year_month" value="{{ $selectedMonth }}">
              <button type="button" class="btn btn-dark btn-block font-weight-bold" style="border-radius: 8px;" onclick="confirmLock()">
                Kunci Periode Ini
              </button>
            </form>
          @else
            <p class="text-muted small mb-3">Periode ini sudah terkunci. Untuk membuka kembali, diperlukan alasan tertulis.</p>
            <button type="button" class="btn btn-warning btn-block font-weight-bold text-dark" style="border-radius: 8px;" data-toggle="modal" data-target="#reopenModal">
              Buka Kunci Periode
            </button>
          @endif
        </div>
      </div>
    </div>

    {{-- Timeline audit singkat --}}
    <div class="col-md-6">
      <div class="panel h-100">
        <div class="panel-head">
          <div class="panel-title">Riwayat Persetujuan</div>
        </div>
        @php
          $approvalHistories = \App\Models\ReportApprovalHistory::with('actor')
            ->where('shop_id', $selectedShopId)
            ->where('year_month', $selectedMonth)
            ->orderBy('created_at', 'desc')
            ->get();
        @endphp
        <div style="max-height: 320px; overflow-y: auto;">
          @forelse($approvalHistories as $h)
            <div style="padding: 12px 0; border-bottom: 1px solid var(--border);">
              <div style="font-size: 13px; font-weight: 600; color: var(--text);">
                {{ strtoupper($h->from_status ?? '') }} → {{ strtoupper($h->to_status ?? '') }}
              </div>
              <div class="text-muted" style="font-size: 12px;">
                Oleh: {{ $h->actor->name ?? '-' }} — {{ \Carbon\Carbon::parse($h->created_at)->format('d M Y H:i') }}
              </div>
              @if($h->reason)
                <div style="font-size: 12px; color: var(--text); margin-top: 4px; font-style: italic;">"{{ $h->reason }}"</div>
              @endif
            </div>
          @empty
            <div class="text-center text-muted py-4" style="font-size: 13px;">Belum ada riwayat persetujuan untuk periode ini.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- Modal Buka Kunci --}}
  <div class="modal fade" id="reopenModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content" style="border-radius: 12px;">
        <div class="modal-header" style="background: #d97706; color: #ffffff;">
          <h5 class="modal-title" style="font-size: 15px; font-weight: 700;">Buka Kunci Periode</h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <form action="{{ route('reports.approval.reopen-period') }}" method="POST">
          @csrf
          <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
          <input type="hidden" name="year_month" value="{{ $selectedMonth }}">
          <div class="modal-body" style="font-size: 13px;">
            <p class="mb-2">Membuka kembali kunci periode memerlukan alasan tertulis:</p>
            <textarea name="reason" class="form-control" rows="3" required
              placeholder="Contoh: Koreksi pengeluaran tanggal 14..." style="border-radius: 8px; font-size: 13px;"></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-warning text-dark font-weight-bold">Buka Kunci</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>{{-- /tab-kunci --}}


{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB 4: PERBANDINGAN TOKO (super-admin & investor only)                    --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
@if($canViewBenchmark)
<div class="payroll-tab-pane" id="tab-perbandingan">

  {{-- Filter Bulan --}}
  <div class="panel mb-4" style="padding: 12px 18px;">
    <form method="GET" action="{{ route('payroll-systems.index') }}" class="d-flex align-items-center" style="gap: 12px;">
      <input type="hidden" name="tab" value="perbandingan">
      <label class="text-muted mb-0" style="font-size: 12px; font-weight: 600; white-space: nowrap;">Bulan:</label>
      <input type="month" name="benchmark_month" class="form-control form-control-sm" style="border-radius: 8px; width: auto;"
        value="{{ $benchmarkMonth }}" onchange="this.form.submit()">
    </form>
  </div>

  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">Perbandingan Kinerja Antar Toko</div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: var(--page-bg);">
            <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Peringkat</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Toko</th>
            <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Penjualan (L)</th>
            <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Rupiah Penjualan</th>
            <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Biaya Operasional</th>
            <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Laba Bersih</th>
            <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Losses / Gain (L)</th>
          </tr>
        </thead>
        <tbody>
          @forelse($benchmarks as $idx => $b)
          <tr>
            <td class="text-center font-weight-bold" style="font-size: 13px;">#{{ $idx + 1 }}</td>
            <td style="font-size: 13px; font-weight: 600;">{{ $b['nama'] }}</td>
            <td class="text-right font-weight-bold" style="font-size: 13px;">
              {{ number_format($b['total_volume'], 1, ',', '.') }} L
            </td>
            <td class="text-right" style="font-size: 13px;">
              Rp {{ number_format($b['total_rupiah'], 0, ',', '.') }}
            </td>
            <td class="text-right" style="font-size: 13px; color: var(--red);">
              Rp {{ number_format($b['total_cost'], 0, ',', '.') }}
            </td>
            <td class="text-right font-weight-bold" style="font-size: 13px; color: var(--green);">
              Rp {{ number_format($b['total_profit'], 0, ',', '.') }}
            </td>
            <td class="text-right font-weight-bold" style="font-size: 13px; color: {{ $b['total_losses'] < 0 ? 'var(--red)' : 'var(--green)' }};">
              {{ number_format($b['total_losses'], 2, ',', '.') }} L
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center py-4 text-muted" style="font-size: 13px;">
              Belum ada data laporan untuk bulan ini.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>{{-- /tab-perbandingan --}}
@endif

@endsection

@push('script')
<script>
// ─── Tab Navigation ──────────────────────────────────────────────────────────
(function() {
  const btns  = document.querySelectorAll('.payroll-tab-btn');
  const panes = document.querySelectorAll('.payroll-tab-pane');

  // Restore tab dari URL atau localStorage
  const urlTab   = new URLSearchParams(window.location.search).get('tab');
  const savedTab = urlTab || localStorage.getItem('payrollActiveTab') || 'pengaturan';

  function activateTab(tabId) {
    btns.forEach(b  => b.classList.toggle('active',  b.dataset.tab === tabId));
    panes.forEach(p => p.classList.toggle('active', p.id === 'tab-' + tabId));
    localStorage.setItem('payrollActiveTab', tabId);
  }

  activateTab(savedTab);

  btns.forEach(btn => {
    btn.addEventListener('click', () => activateTab(btn.dataset.tab));
  });
})();

// ─── Cek status periode (Tab 2) ──────────────────────────────────────────────
function checkFinalStatus() {
  const sel    = document.getElementById('gen-bulan');
  if (!sel) return;
  const opt    = sel.options[sel.selectedIndex];
  const status = opt ? opt.dataset.status : '';
  const btnGen = document.getElementById('btn-submit-gen');
  const aFin   = document.getElementById('final-alert');
  const aDraf  = document.getElementById('draft-alert');

  aFin.classList.add('d-none');
  aDraf.classList.add('d-none');
  btnGen.disabled = false;
  btnGen.classList.replace('btn-secondary', 'btn-success');

  if (status === 'final') {
    aFin.classList.remove('d-none');
    btnGen.disabled = true;
    btnGen.classList.replace('btn-success', 'btn-secondary');
  } else if (status === 'draft') {
    aDraf.classList.remove('d-none');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  checkFinalStatus();

  const formGen = document.getElementById('form-generate');
  if (formGen) {
    formGen.addEventListener('submit', function(e) {
      const sel    = document.getElementById('gen-bulan');
      const opt    = sel ? sel.options[sel.selectedIndex] : null;
      const status = opt ? opt.dataset.status : '';

      if (status === 'final') {
        e.preventDefault();
        Swal.fire('Tidak Bisa', 'Periode sudah final dan terkunci.', 'error');
        return;
      }

      if (status === 'draft') {
        e.preventDefault();
        const form = this;
        Swal.fire({
          title: 'Hitung Ulang?',
          text: 'Sudah ada draft untuk bulan ini. Data draft lama akan diganti. Lanjutkan?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#28a745',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Ya, Hitung Ulang',
          cancelButtonText: 'Batal'
        }).then(result => { if (result.isConfirmed) form.submit(); });
      }
    });
  }

  // Hapus Draft Period (Event Delegation)
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete-period');
    if (!btn) return;
    e.preventDefault();

    const id = btn.dataset.id;
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    Swal.fire({
      title: 'Hapus Draft Penggajian?',
      text: 'Data draft yang belum difinalisasi akan dihapus permanen.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonText: 'Batal',
      confirmButtonText: 'Hapus Draft'
    }).then(result => {
      if (result.isConfirmed) {
        fetch(`/payroll/${id}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })
        .then(async r => {
          const data = await r.json().catch(() => ({}));
          if (!r.ok || data.error) {
            Swal.fire('Gagal', data.error || data.message || 'Gagal menghapus draft.', 'error');
          } else {
            Swal.fire({
              title: 'Berhasil!',
              text: 'Draft penggajian berhasil dihapus.',
              icon: 'success',
              timer: 1500,
              showConfirmButton: false
            }).then(() => location.reload());
          }
        })
        .catch(err => {
          Swal.fire('Gagal', 'Terjadi kesalahan sistem: ' + err.message, 'error');
        });
      }
    });
  });
});

// ─── Dynamic Custom Items Row Handler ──────────────────────────────────────
let customRowIndex = 0;

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-add-custom-row');
  if (!btn) return;
  e.preventDefault();

  const container = btn.closest('.card-body').querySelector('#custom-items-container-sys, #custom-items-container-index');
  if (!container) return;

  const idx = customRowIndex++;
  const rowHtml = `
    <div class="custom-item-row p-2 mb-2 border rounded" style="background:#ffffff; font-size:12px; border-color:#cbd5e1!important;" id="custom-row-${idx}">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <select name="custom_items[${idx}][tipe]" class="form-control form-control-sm py-0 px-2" style="width: auto; height: 24px; font-size: 11px; font-weight:700;">
          <option value="tambahan">+ Pendapatan Tambahan</option>
          <option value="potongan">− Potongan Lain</option>
        </select>
        <button type="button" class="btn btn-xs text-danger btn-remove-custom-row p-0 ml-2" style="font-weight:bold; font-size:16px; line-height:1;" title="Hapus baris">&times;</button>
      </div>
      <div class="form-row">
        <div class="col-7 pr-1">
          <input type="text" name="custom_items[${idx}][nama_item]" class="form-control form-control-sm" placeholder="1. Keterangan (ex: Bonus Oli)" style="font-size:11px;" required>
        </div>
        <div class="col-5 pl-1">
          <input type="number" name="custom_items[${idx}][jumlah]" class="form-control form-control-sm" placeholder="2. Nominal (Rp)" style="font-size:11px;" min="0" step="1000" required>
        </div>
      </div>
    </div>
  `;

  container.insertAdjacentHTML('beforeend', rowHtml);
});

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-remove-custom-row');
  if (!btn) return;
  e.preventDefault();
  const row = btn.closest('.custom-item-row');
  if (row) row.remove();
});

function confirmBulkGenerate(e) {
  e.preventDefault();
  const form = document.getElementById('form-bulk-generate');
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Hitung Gaji Semua Toko',
      text: 'Hitung gaji untuk SELURUH toko bulan ini? Toko yang sudah Final akan otomatis dilewati.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#184b2b',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Hitung Semua',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm('Hitung gaji untuk SELURUH toko bulan ini? Toko yang sudah Final akan dilewati.')) {
      form.submit();
    }
  }
}
</script>
@endpush
