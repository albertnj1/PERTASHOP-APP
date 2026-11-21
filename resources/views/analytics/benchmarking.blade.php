@extends('layouts.master')
@section('title', 'Benchmarking Outlet')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Benchmarking Antar Outlet</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Perbandingan performa penjualan dan profitabilitas antar outlet</p>
  </div>
  <div>
    <form method="GET" action="{{ route('analytics.benchmarking') }}" class="form-inline">
      <input type="month" name="bulan" value="{{ $selectedMonth }}" class="form-control mr-2" onchange="this.form.submit()">
      <button type="submit" class="btn btn-primary"><i class="fa fa-filter mr-1"></i> Filter</button>
    </form>
  </div>
</div>

<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3">
    <h6 class="m-0 font-weight-bold text-primary">Peringkat Kinerja Outlet &mdash; Periode {{ \Carbon\Carbon::parse($selectedMonth . '-01')->isoFormat('MMMM Y') }}</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="thead-light">
          <tr>
            <th>Rank</th>
            <th>Outlet</th>
            <th class="text-right">Total Volume</th>
            <th class="text-right">Rata-rata Harian</th>
            <th class="text-right">Omset (Rupiah)</th>
            <th class="text-right">Pengeluaran</th>
            <th class="text-right">Net Profit</th>
            <th class="text-right">Profit / Liter</th>
            <th class="text-center">Jml Hari</th>
          </tr>
        </thead>
        <tbody>
          @forelse($benchmarks as $index => $bm)
            <tr>
              <td>
                @if($index === 0)
                  <span class="badge badge-warning text-dark"><i class="fa fa-trophy mr-1"></i>#1</span>
                @elseif($index === 1)
                  <span class="badge badge-secondary">#2</span>
                @elseif($index === 2)
                  <span class="badge badge-light border">#3</span>
                @else
                  <span class="text-muted">#{{ $index + 1 }}</span>
                @endif
              </td>
              <td><strong>{{ $bm['nama'] }}</strong></td>
              <td class="text-right font-weight-bold">{{ number_format($bm['total_volume'], 2, ',', '.') }} L</td>
              <td class="text-right">{{ number_format($bm['avg_daily_volume'], 1, ',', '.') }} L/hari</td>
              <td class="text-right">Rp {{ number_format($bm['total_rupiah'], 0, ',', '.') }}</td>
              <td class="text-right text-danger">Rp {{ number_format($bm['total_cost'], 0, ',', '.') }}</td>
              <td class="text-right font-weight-bold {{ $bm['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                Rp {{ number_format($bm['total_profit'], 0, ',', '.') }}
              </td>
              <td class="text-right">Rp {{ number_format($bm['profit_per_liter'], 0, ',', '.') }}/L</td>
              <td class="text-center">{{ $bm['total_reports'] }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-4 text-muted">Belum ada data benchmarking untuk periode ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

