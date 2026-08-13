@extends('layouts._new_admin')
@section('title', 'Executive Financial Dashboard — Investor Monitoring')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">
      Executive Financial Dashboard
      <span class="badge badge-info" style="font-size: 13px; font-weight: 500;">Investor Business Viewer</span>
    </h1>
    <p class="text-muted mb-0" style="font-size: 13px;">
      Monitoring performa finansial, total omset, efisiensi payroll, dan keuntungan bersih bisnis Pertashop tanpa kerumitan rincian teknis operator.
    </p>
  </div>
</div>

{{-- 1. EXECUTIVE FILTER BAR --}}
<div class="panel mb-4 p-3" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
  <form action="{{ route('investor.dashboard') }}" method="GET" class="form-inline d-flex justify-content-between">
    <div class="d-flex align-items-center" style="gap: 15px;">
      <label style="font-size: 12px; font-weight: 700; color: #475569;">📍 FILTER OUTLET:</label>
      <select name="shop_id" class="form-control form-control-sm" style="border-radius: 8px; font-weight: 600;" onchange="this.form.submit()">
        <option value="all" {{ $selectedShopId === 'all' ? 'selected' : '' }}>Semua Outlet Pertashop (Konsolidasi)</option>
        @foreach($shops as $s)
          <option value="{{ $s->id }}" {{ $s->id == $selectedShopId ? 'selected' : '' }}>
            {{ $s->nama }} ({{ $s->kode }})
          </option>
        @endforeach
      </select>

      <label style="font-size: 12px; font-weight: 700; color: #475569; margin-left: 10px;">📅 PERIODE BULAN:</label>
      <input type="month" name="year_month" class="form-control form-control-sm" value="{{ $selectedMonth }}" style="border-radius: 8px; font-weight: 600;" onchange="this.form.submit()">
    </div>

    <div>
      @if($isPeriodLocked)
        <span class="badge badge-dark" style="font-size: 12px; padding: 6px 14px;">🔒 Financial Data Terkunci Total (LOCKED)</span>
      @else
        <span class="badge badge-success" style="font-size: 12px; padding: 6px 14px;">🟢 Operational Period Active</span>
      @endif
    </div>
  </form>
</div>

{{-- 2. 4 KEY EXECUTIVE CARDS --}}
<div class="row mb-4">
  <div class="col-md-3">
    <div class="panel p-3" style="background: #ffffff; border-radius: 12px; border-left: 4px solid #0284c7; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
      <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Total Penjualan Volume</div>
      <div style="font-size: 24px; font-weight: 900; color: #0284c7; margin-top: 4px;">
        {{ number_format($totalVolume, 2, ',', '.') }} L
      </div>
      <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
        Periode {{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('M Y') }}
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="panel p-3" style="background: #ffffff; border-radius: 12px; border-left: 4px solid #16a34a; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
      <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Total Omset (Revenue)</div>
      <div style="font-size: 24px; font-weight: 900; color: #16a34a; margin-top: 4px;">
        Rp {{ number_format($totalRevenue, 0, ',', '.') }}
      </div>
      <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
        Total Penerimaan Kotor
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="panel p-3" style="background: #ffffff; border-radius: 12px; border-left: 4px solid #d97706; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
      <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Pengeluaran Gaji Payroll</div>
      <div style="font-size: 24px; font-weight: 900; color: #d97706; margin-top: 4px;">
        Rp {{ number_format($totalPayrollTHP, 0, ',', '.') }}
      </div>
      <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
        Total THP Operator Disahkan
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="panel p-3" style="background: #ffffff; border-radius: 12px; border-left: 4px solid #7c3aed; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
      <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Estimasi Net Profit (Laba)</div>
      <div style="font-size: 24px; font-weight: 900; color: #7c3aed; margin-top: 4px;">
        Rp {{ number_format($estNettProfit, 0, ',', '.') }}
      </div>
      <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
        Est Margin - Payroll THP
      </div>
    </div>
  </div>
</div>

{{-- 3. KONSOLIDASI FINANSIAL OUTLET --}}
<div class="panel mb-4" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
  <div class="panel-head" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
    <div class="panel-title" style="font-size: 14px; font-weight: 800; color: #0f172a;">
      📊 Performa Finansial Outlets (Periode {{ $selectedMonth }})
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr style="background: #f1f5f9; font-size: 11px; text-transform: uppercase; color: #475569;">
          <th>Nama Outlet</th>
          <th class="text-right">Total Volume Penjualan</th>
          <th class="text-right">Total Omset Penjualan</th>
          <th class="text-right">Biaya Gaji Operator (THP)</th>
          <th class="text-right" style="color: #7c3aed;">Estimasi Laba Bersih</th>
          <th class="text-center">Status Audit</th>
        </tr>
      </thead>
      <tbody>
        @foreach($shops as $s)
        @php
          $shopReports = \App\Models\DailyReport::where('shop_id', $s->id)
              ->whereDate('created_at', 'like', "{$selectedMonth}%")
              ->get();

          $vol = $shopReports->sum(fn($r) => (float) ($r->volume_penjualan ?? $r->volume_terjual ?? 0));
          $rev = $shopReports->sum(fn($r) => (float) ($r->rupiah_penjualan ?? $r->pendapatan_operator ?? 0));

          $evalDate = \Carbon\Carbon::parse($selectedMonth . '-01');
          $payroll = \App\Models\PayrollPeriod::with('details')
              ->where('shop_id', $s->id)
              ->where('tahun', (int)$evalDate->format('Y'))
              ->where('bulan', (int)$evalDate->format('m'))
              ->first();

          $pTHP = $payroll?->details->sum('thp') ?? 0;
          $margin = ($rev * 0.08) - $pTHP;
          $isShopLocked = \App\Models\PeriodLock::isLocked($s->id, $selectedMonth);
        @endphp
        <tr style="font-size: 12.5px;">
          <td style="font-weight: 700; color: #1e293b;">
            🏢 {{ $s->nama }} ({{ $s->kode }})
          </td>
          <td class="text-right" style="font-weight: 600; color: #0284c7;">
            {{ number_format($vol, 2, ',', '.') }} L
          </td>
          <td class="text-right" style="font-weight: 700; color: #16a34a;">
            Rp {{ number_format($rev, 0, ',', '.') }}
          </td>
          <td class="text-right" style="color: #d97706;">
            Rp {{ number_format($pTHP, 0, ',', '.') }}
          </td>
          <td class="text-right" style="font-weight: 800; color: #7c3aed; background: #faf5ff;">
            Rp {{ number_format(max(0, $margin), 0, ',', '.') }}
          </td>
          <td class="text-center">
            @if($isShopLocked)
              <span class="badge badge-dark" style="font-size: 10.5px;">🔒 LOCKED</span>
            @else
              <span class="badge badge-success" style="font-size: 10.5px;">🟢 ACTIVE</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
