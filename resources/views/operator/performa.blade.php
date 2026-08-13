@extends('layouts._new_admin')
@section('title', 'Performa Saya')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Performa &amp; Rekap Kerja Saya</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Statistik penjualan, setoran, dan jadwal shift operator</p>
  </div>
  <div>
    <form method="GET" action="{{ route('operator.performa') }}" class="d-flex align-items-center gap-2">
      <label class="text-muted mb-0 mr-2" style="font-size: 12px; font-weight: 600;">Bulan:</label>
      <input type="month" name="bulan" class="form-control form-control-sm" style="border-radius: 8px;" value="{{ $selectedMonth }}" onchange="this.form.submit()">
    </form>
  </div>
</div>

<div class="summary-grid mb-4">
  <div class="metric-card pos">
    <div class="metric-label">Total Volume Dijual</div>
    <div class="metric-value pos">{{ number_format($totalSalesVol, 1, ',', '.') }} L</div>
    <div class="metric-sub">Bulan berjalan</div>
  </div>
  <div class="metric-card neutral">
    <div class="metric-label">Total Kehadiran</div>
    <div class="metric-value">{{ $recap->total_hadir ?? 0 }} Hari</div>
    <div class="metric-sub">Shift dijalankan</div>
  </div>
  <div class="metric-card neutral">
    <div class="metric-label">Total Setoran</div>
    <div class="metric-value" style="color: var(--blue);">Rp {{ number_format($reports->sum('disetorkan'), 0, ',', '.') }}</div>
    <div class="metric-sub">Kasir ke admin</div>
  </div>
  <div class="metric-card {{ $reports->sum('selisih_setoran') < 0 ? 'neg' : 'pos' }}">
    <div class="metric-label">Akumulasi Selisih</div>
    <div class="metric-value {{ $reports->sum('selisih_setoran') < 0 ? 'neg' : 'pos' }}">Rp {{ number_format($reports->sum('selisih_setoran'), 0, ',', '.') }}</div>
    <div class="metric-sub">Selisih setoran</div>
  </div>
</div>

<div class="row cols-1-1">
  {{-- TABEL RIWAYAT LAPORAN HARIAN OPERATOR --}}
  <div class="panel mb-0">
    <div class="panel-head">
      <div class="panel-title">Riwayat Laporan Harian Saya</div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: var(--page-bg);">
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Tanggal</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Volume (L)</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Rupiah Penjualan</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Losses/Gain (L)</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Setoran</th>
            <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Status Setoran</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reports as $r)
          <tr>
            <td style="font-size: 13px;">{{ \Carbon\Carbon::parse($r->created_at)->format('d M Y') }}</td>
            <td style="font-size: 13px;">{{ number_format($r->volume_penjualan_teoritis, 2, ',', '.') }}</td>
            <td style="font-size: 13px;">Rp {{ number_format($r->rupiah_penjualan, 0, ',', '.') }}</td>
            <td style="font-size: 13px; color: {{ $r->losses_gain < 0 ? 'var(--red)' : 'var(--green)' }}; font-weight: 600;">
              {{ number_format($r->losses_gain, 3, ',', '.') }}
            </td>
            <td style="font-size: 13px;">Rp {{ number_format($r->disetorkan, 0, ',', '.') }}</td>
            <td style="font-size: 13px;">
              @if(abs((float)$r->selisih_setoran) < 1000)
                <span class="status-pill" style="background:#dcfce7; color:#15803d;">Sesuai</span>
              @elseif($r->selisih_setoran < 0)
                <span class="status-pill" style="background:#fee2e2; color:#b91c1c;">Kurang (Rp {{ number_format(abs($r->selisih_setoran), 0, ',', '.') }})</span>
              @else
                <span class="status-pill" style="background:#fef3c7; color:#b45309;">Lebih (Rp {{ number_format($r->selisih_setoran, 0, ',', '.') }})</span>
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center text-muted py-4" style="font-size: 13px;">Belum ada laporan harian pada bulan ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- JADWAL SHIFT MENDATANG --}}
  <div class="panel mb-0">
    <div class="panel-head">
      <div class="panel-title">Jadwal Shift Mendatang</div>
    </div>
    <ul class="list-group list-group-flush">
      @forelse($upcomingShifts as $shift)
      <li class="list-group-item d-flex justify-content-between align-items-center px-0">
        <div>
          <strong style="font-size: 13px;">{{ $shift->tanggal->translatedFormat('l, d M Y') }}</strong><br>
          <small class="text-muted" style="font-size: 11px;">Shift {{ $shift->shift_ke }}</small>
        </div>
        <span class="status-pill" style="background:#e0f2fe; color:#0369a1;">Terjadwal</span>
      </li>
      @empty
      <li class="list-group-item text-center text-muted py-4 px-0" style="font-size: 13px;">Belum ada jadwal shift mendatang.</li>
      @endforelse
    </ul>
  </div>
</div>
@endsection
