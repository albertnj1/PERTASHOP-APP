@extends('layouts._new_admin')
@section('title', 'Persetujuan Laporan Harian')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Persetujuan & Penguncian Laporan</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Kelola status siklus hidup (lifecycle) laporan harian dan kunci periode buku</p>
  </div>
  <div>
    <form method="GET" action="{{ route('reports.approval.index') }}" class="form-inline">
      <select name="shop_id" class="form-control mr-2" onchange="this.form.submit()">
        @foreach($shops as $s)
          <option value="{{ $s->id }}" {{ $s->id == $selectedShopId ? 'selected' : '' }}>
            {{ $s->nama }}
          </option>
        @endforeach
      </select>
      <input type="month" name="year_month" value="{{ $selectedMonth }}" class="form-control mr-2" onchange="this.form.submit()">
      <button type="submit" class="btn btn-primary"><i class="fa fa-filter mr-1"></i> Pilih</button>
    </form>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 10px;">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif

@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius: 10px;">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted font-weight-bold">STATUS PERIODE</small>
        <div class="mt-2">
          @if($isLocked)
            <span class="badge badge-danger p-2" style="font-size: 13px;"><i class="fa fa-lock mr-1"></i> DIKUNCI (LOCKED)</span>
          @else
            <span class="badge badge-success p-2" style="font-size: 13px;"><i class="fa fa-unlock mr-1"></i> TERBUKA (OPEN)</span>
          @endif
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted font-weight-bold">TOTAL LAPORAN</small>
        <div class="h4 font-weight-bold mt-2 text-dark">{{ $reports->count() }} Hari</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted font-weight-bold">VOLUME PENJUALAN</small>
        <div class="h4 font-weight-bold mt-2 text-primary">{{ number_format($totalVol, 2, ',', '.') }} L</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-0">
      <div class="card-body">
        <small class="text-muted font-weight-bold">TOTAL OMSET</small>
        <div class="h4 font-weight-bold mt-2 text-success">Rp {{ number_format($totalRupiah, 0, ',', '.') }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="m-0 font-weight-bold text-dark">Daftar Laporan Harian {{ $shop->nama }} &mdash; {{ \Carbon\Carbon::parse($selectedMonth . '-01')->isoFormat('MMMM Y') }}</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="thead-light">
          <tr>
            <th>Tanggal</th>
            <th>Operator</th>
            <th class="text-right">Vol Aktual</th>
            <th class="text-right">Rupiah Penjualan</th>
            <th class="text-right">Losses / Gain</th>
            <th class="text-right">Selisih Setoran</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reports as $r)
            <tr>
              <td>
                <strong>{{ \Carbon\Carbon::parse($r->report_date ?? $r->created_at)->isoFormat('dddd, D MMM Y') }}</strong>
              </td>
              <td>{{ $r->operator?->user?->name ?? 'Operator' }}</td>
              <td class="text-right">{{ number_format($r->penjualan_aktual_volume ?? $r->volume_penjualan ?? 0, 2, ',', '.') }} L</td>
              <td class="text-right">Rp {{ number_format($r->penjualan_aktual_rupiah ?? $r->rupiah_penjualan ?? 0, 0, ',', '.') }}</td>
              <td class="text-right {{ ($r->losses_gain_liter ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                {{ number_format($r->losses_gain_liter ?? 0, 2, ',', '.') }} L
              </td>
              <td class="text-right {{ ($r->selisih_setoran ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                Rp {{ number_format($r->selisih_setoran ?? 0, 0, ',', '.') }}
              </td>
              <td class="text-center">
                <span class="badge badge-{{ ($r->status_lifecycle ?? 'draft') === 'approved' ? 'success' : (($r->status_lifecycle ?? '') === 'locked' ? 'danger' : 'secondary') }}">
                  {{ strtoupper($r->status_lifecycle ?? 'DRAFT') }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">Belum ada laporan harian pada periode ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@if($approvalHistories->isNotEmpty())
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3">
    <h6 class="m-0 font-weight-bold text-dark"><i class="fa fa-history mr-1"></i> Riwayat Audit Persetujuan</h6>
  </div>
  <div class="card-body">
    <ul class="list-group list-group-flush">
      @foreach($approvalHistories as $h)
        <li class="list-group-item px-0">
          <div class="d-flex justify-content-between">
            <div>
              <strong>{{ $h->actor?->name ?? 'System' }}</strong> mengubah status ke
              <span class="badge badge-info">{{ strtoupper($h->to_status) }}</span>
              @if($h->reason)
                <p class="text-muted small mb-0 mt-1">&ldquo;{{ $h->reason }}&rdquo;</p>
              @endif
            </div>
            <small class="text-muted">{{ $h->created_at->diffForHumans() }}</small>
          </div>
        </li>
      @endforeach
    </ul>
  </div>
</div>
@endif
@endsection

