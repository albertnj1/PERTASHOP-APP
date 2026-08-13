@extends('layouts._new_admin')
@section('title', 'Pusat Tindakan')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Pusat Tindakan</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Daftar permohonan dan laporan yang memerlukan verifikasi admin</p>
  </div>
  <div>
    <span class="status-pill" style="font-size: 12px; padding: 6px 14px; background: #fef3c7; color: #b45309;">
      {{ $totalActionItems }} Item Perlu Tindakan
    </span>
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

{{-- Filter Toko untuk Super Admin --}}
@if(Auth::user()->role === 'super-admin')
<div class="panel mb-4" style="padding: 12px 18px;">
  <form method="GET" action="{{ route('action-center.index') }}" class="d-flex align-items-center gap-2">
    <label class="text-muted mb-0 mr-2" style="font-size: 13px; font-weight: 600;">Filter Toko:</label>
    <select name="shop_id" class="form-control form-control-sm" style="width: auto; border-radius: 8px;" onchange="this.form.submit()">
      <option value="">Semua Toko</option>
      @foreach($shops as $shop)
        <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
      @endforeach
    </select>
  </form>
</div>
@endif

<div class="row cols-1-1">
  {{-- 1. Pengajuan Tukar Shift Pending --}}
  <div class="panel mb-0">
    <div class="panel-head">
      <div class="panel-title">Persetujuan Tukar Shift ({{ $pendingSwaps->count() }})</div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: var(--page-bg);">
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Toko</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Operator</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Pengganti</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Alasan</th>
            <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pendingSwaps as $swap)
          <tr>
            <td style="font-size: 13px;"><span class="status-pill" style="background:#e2e8f0; color:#475569;">{{ $swap->shiftSchedule?->shop?->nama }}</span></td>
            <td style="font-size: 13px; font-weight: 600;">{{ $swap->operatorAsal?->user?->name ?? '-' }}</td>
            <td style="font-size: 13px; color: var(--blue);">{{ $swap->operatorPengganti?->user?->name ?? '-' }}</td>
            <td style="font-size: 13px;">
              <span class="status-pill" style="background:#e0f2fe; color:#0369a1;">{{ ucfirst($swap->alasan) }}</span>
              @if($swap->keterangan)<div class="text-muted" style="font-size: 11px; margin-top:2px;">{{ $swap->keterangan }}</div>@endif
            </td>
            <td class="text-center">
              <form action="{{ route('shift-swaps.approve', $swap->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success" style="border-radius: 6px; font-size: 11px; padding: 3px 8px;">Setujui</button>
              </form>
              <form action="{{ route('shift-swaps.reject', $swap->id) }}" method="POST" class="d-inline ml-1">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 6px; font-size: 11px; padding: 3px 8px;">Tolak</button>
              </form>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center text-muted py-3" style="font-size: 13px;">Tidak ada pengajuan tukar shift pending.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- 2. Pengajuan Kasbon Pending --}}
  <div class="panel mb-0">
    <div class="panel-head">
      <div class="panel-title">Approval Kasbon ({{ $pendingLoans->count() }})</div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: var(--page-bg);">
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Operator &amp; Toko</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Tanggal</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Nominal</th>
            <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pendingLoans as $loan)
          <tr>
            <td style="font-size: 13px;">
              <div style="font-weight: 600;">{{ $loan->operator?->user?->name ?? '-' }}</div>
              <div style="font-size: 11px; color: var(--muted);">{{ $loan->operator?->shop?->nama }}</div>
            </td>
            <td style="font-size: 13px;">{{ $loan->tanggal?->format('d/m/Y') }}</td>
            <td style="font-size: 13px; font-weight: 700; color: var(--red);">Rp {{ number_format($loan->jumlah, 0, ',', '.') }}</td>
            <td class="text-center">
              <form action="{{ route('employee-loans.approve', $loan->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success" style="border-radius: 6px; font-size: 11px; padding: 3px 8px;">Setujui</button>
              </form>
              <form action="{{ route('employee-loans.reject', $loan->id) }}" method="POST" class="d-inline ml-1">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 6px; font-size: 11px; padding: 3px 8px;">Tolak</button>
              </form>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="4" class="text-center text-muted py-3" style="font-size: 13px;">Tidak ada pengajuan kasbon pending.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="row cols-1-1 mt-4">
  {{-- 3. Selisih Setoran Kurang & Klarifikasi Kasir --}}
  <div class="panel mb-0">
    <div class="panel-head">
      <div class="panel-title">Selisih Setoran Kurang Bulan Ini ({{ $shortfallReports->count() }})</div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: var(--page-bg);">
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Tanggal &amp; Operator</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Kurang Setor</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Klarifikasi Kasir</th>
            <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($shortfallReports as $report)
          <tr>
            <td style="font-size: 13px;">
              <div style="font-weight: 600;">{{ $report->created_at->format('d/m/Y') }}</div>
              <div style="font-size: 11px; color: var(--muted);">{{ $report->operator?->user?->name }} ({{ $report->shop?->nama }})</div>
            </td>
            <td style="font-size: 13px; font-weight: 700; color: var(--red);">
              - Rp {{ number_format(abs($report->selisih_setoran), 0, ',', '.') }}
            </td>
            <td style="font-size: 13px;">
              @if($report->klarifikasi_selisih)
                <span style="font-size: 12px; color: var(--ink); font-style: italic;">"{{ $report->klarifikasi_selisih }}"</span>
              @else
                <span class="status-pill" style="background:#fef3c7; color:#b45309;">Belum ada klarifikasi</span>
              @endif
            </td>
            <td class="text-center">
              <a href="{{ route('daily-reports.show', $report->id) }}" class="panel-link" style="font-size: 12px;">Review</a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="4" class="text-center text-muted py-3" style="font-size: 13px;">Tidak ada selisih setoran kurang bulan ini.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- 4. Laporan Harian Belum Diverifikasi --}}
  <div class="panel mb-0">
    <div class="panel-head">
      <div class="panel-title">Laporan Belum Diverifikasi ({{ $unverifiedReports->count() }})</div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: var(--page-bg);">
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Tanggal</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Toko</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Operator</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Disetorkan</th>
            <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($unverifiedReports as $report)
          <tr>
            <td style="font-size: 13px;">{{ $report->created_at->format('d/m/Y') }}</td>
            <td style="font-size: 13px;"><span class="status-pill" style="background:#e2e8f0; color:#475569;">{{ $report->shop?->nama }}</span></td>
            <td style="font-size: 13px;">{{ $report->operator?->user?->name ?? '-' }}</td>
            <td style="font-size: 13px; font-weight: 600;">Rp {{ number_format($report->disetorkan, 0, ',', '.') }}</td>
            <td class="text-center">
              <a href="{{ route('daily-reports.show', $report->id) }}" class="btn btn-sm btn-primary" style="border-radius: 6px; font-size: 11px; padding: 3px 8px;">Verifikasi</a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center text-muted py-3" style="font-size: 13px;">Semua laporan harian telah diverifikasi.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
