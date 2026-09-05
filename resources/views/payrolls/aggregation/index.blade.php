@extends('layouts._new_admin')
@section('title', 'Payroll & THP Aggregation Center')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">
      Payroll & THP Aggregation Center
      <span class="badge badge-success" style="font-size: 13px; font-weight: 500;">Fase C.3 Automated THP</span>
    </h1>
    <p class="text-muted mb-0" style="font-size: 13px;">
      Otomatisasi perhitungan daftar pembayaran operator berdasarkan Laporan Harian ter-APPROVED / LOCKED (Fase C.1) dan Aturan Terversi `BR-PAYROLL` (Fase C.2).
    </p>
  </div>
</div>

{{-- 1. HEADER PERIOD & OUTLET SELECTOR --}}
<div class="panel mb-4 p-3" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
  <form action="{{ route('payrolls.aggregation.index') }}" method="GET" class="form-inline d-flex justify-content-between">
    <div class="d-flex align-items-center" style="gap: 15px;">
      <label style="font-size: 12px; font-weight: 700; color: #475569;">📍 OUTLET:</label>
      <select name="shop_id" class="form-control form-control-sm" style="border-radius: 8px; font-weight: 600;" onchange="this.form.submit()">
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
      @if($isLocked)
        <span class="badge badge-dark" style="font-size: 12px; padding: 6px 14px;">🔒 Periode Terkunci Total (LOCKED)</span>
      @else
        <span class="badge badge-warning text-dark" style="font-size: 12px; padding: 6px 14px;">⚠️ Periode Belum Terkunci Total</span>
      @endif
    </div>
  </form>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 10px; border: none; background: #dcfce7; color: #15803d;">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif

@if($errors->any())
  <div class="alert alert-danger mb-4" style="border-radius: 10px;">
    <ul class="mb-0">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif

{{-- BANNER KELAYAKAN GOVERNANCE (ELIGIBILITY CHECKER) --}}
@if(!$eligibility['eligible'])
  <div class="alert alert-warning mb-4" style="border-radius: 10px; border: 1px solid #fcd34d; background: #fffbeb; color: #92400e;">
    <div class="d-flex align-items-center">
      <div style="font-size: 24px; margin-right: 12px;">🚫</div>
      <div>
        <strong>Status Kelayakan Payroll (Prasyarat Governance Fase C.1):</strong>
        <div style="font-size: 12.5px; margin-top: 2px;">{{ $eligibility['message'] }}</div>
      </div>
    </div>
  </div>
@endif

{{-- 2. SUMMARY BANNER AUDIT GOVERNANCE --}}
<div class="panel mb-4" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px;">
  <div class="row align-items-center">
    <div class="col-md-3 border-right">
      <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Status Rekap Payroll</div>
      <div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 2px;">
        @if($payrollPeriod?->approval_status === 'approved')
          <span class="text-success">✅ APPROVED</span>
        @else
          <span class="text-warning">🟡 DRAFT REKAP</span>
        @endif
      </div>
      <div style="font-size: 11px; color: #64748b;">Disahkan oleh Super Admin</div>
    </div>

    <div class="col-md-3 border-right">
      <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Aturan Komisi Terversi</div>
      <div style="font-size: 20px; font-weight: 800; color: #0284c7; margin-top: 2px;">
        Rp {{ number_format($payrollRate, 0, ',', '.') }}/L
      </div>
      <div style="font-size: 11px; color: #64748b;">Versi: {{ $ruleSnapshot['snapshot']['PAYROLL_RATE']['version_code'] ?? 'BR-PAYROLL-v1.0' }}</div>
    </div>

    <div class="col-md-3 border-right">
      <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Total Pengeluaran THP</div>
      <div style="font-size: 20px; font-weight: 800; color: #16a34a; margin-top: 2px;">
        Rp {{ number_format($payrollPeriod?->details->sum('thp') ?? 0, 0, ',', '.') }}
      </div>
      <div style="font-size: 11px; color: #64748b;">Untuk {{ $payrollPeriod?->details->count() ?? 0 }} Operator</div>
    </div>

    <div class="col-md-3 text-right">
      {{-- Tombol Generate Payroll --}}
      <form action="{{ route('payrolls.aggregation.generate') }}" method="POST" class="d-inline">
        @csrf
        <input type="hidden" name="shop_id" value="{{ $shop->id }}">
        <input type="hidden" name="year_month" value="{{ $selectedMonth }}">
        <button type="submit" class="btn btn-sm {{ $eligibility['eligible'] ? 'btn-outline-primary' : 'btn-outline-secondary' }}" {{ !$eligibility['eligible'] ? 'disabled' : '' }} style="border-radius: 8px; font-weight: 600;">
          ⚡ Hitung / Re-calculate THP
        </button>
      </form>

      {{-- Tombol Approve Payroll (Super Admin Only) --}}
      @if($payrollPeriod && $payrollPeriod->approval_status !== 'approved')
        <form action="{{ route('payrolls.aggregation.approve') }}" method="POST" class="d-inline ml-2">
          @csrf
          <input type="hidden" name="payroll_period_id" value="{{ $payrollPeriod->id }}">
          <button type="submit" class="btn btn-sm btn-success" style="border-radius: 8px; font-weight: 700;">
            ✅ Sahkan Payroll (Super Admin)
          </button>
        </form>
      @endif
    </div>
  </div>
</div>

{{-- 3. TABEL REKAP PAYROLL THP OPERATOR --}}
<div class="panel mb-4" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
  <div class="panel-head" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
    <div class="panel-title" style="font-size: 14px; font-weight: 800; color: #0f172a;">
      Daftar Rincian Pembayaran Operator & Slip Gaji (Periode {{ $selectedMonth }})
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr style="background: #f1f5f9; font-size: 11px; text-transform: uppercase; color: #475569;">
          <th>Nama Operator</th>
          <th class="text-right">Gaji Pokok</th>
          <th class="text-right">Insentif Komisi Liter</th>
          <th class="text-right">Uang Transport</th>
          <th class="text-right">Gaji Kotor (Gross)</th>
          <th class="text-right" style="color: #dc2626;">Potongan Kasbon</th>
          <th class="text-right" style="color: #dc2626;">Potongan Selisih</th>
          <th class="text-right" style="font-weight: 800; color: #16a34a;">NETT THP DITERIMA</th>
          <th class="text-center">Aksi Slip</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payrollPeriod?->details ?? [] as $d)
        <tr style="font-size: 12.5px;">
          <td style="font-weight: 700; color: #1e293b;">
            👤 {{ $d->operator->nama ?? 'Operator' }}
            <div class="text-muted" style="font-size: 11px; font-weight: normal;">ID: {{ $d->operator->kode_operator ?? '-' }}</div>
          </td>
          <td class="text-right">
            @if(floatval($d->gaji_pokok) > 0)
              Rp {{ number_format(floatval($d->gaji_pokok), 0, ',', '.') }}
            @else
              <span class="text-muted">—</span>
            @endif
          </td>
          <td class="text-right" style="color: #0284c7; font-weight: 600;">
            @if(floatval($d->total_bonus) > 0)
              Rp {{ number_format(floatval($d->total_bonus), 0, ',', '.') }}
            @else
              <span class="text-muted">—</span>
            @endif
          </td>
          <td class="text-right">Rp {{ number_format(floatval($d->uang_transport), 0, ',', '.') }}</td>
          <td class="text-right" style="font-weight: 600;">Rp {{ number_format(floatval($d->gaji_kotor), 0, ',', '.') }}</td>
          <td class="text-right text-danger">- Rp {{ number_format(floatval($d->potongan_kasbon), 0, ',', '.') }}</td>
          <td class="text-right text-danger">- Rp {{ number_format(floatval($d->kurang_setoran), 0, ',', '.') }}</td>
          <td class="text-right" style="font-size: 14px; font-weight: 800; color: #16a34a; background: #f0fdf4;">
            Rp {{ number_format(floatval($d->thp_pembulatan ?? $d->thp), 0, ',', '.') }}
          </td>
          <td class="text-center">
            <a href="{{ route('payrolls.aggregation.slip', $d->id) }}" target="_blank" class="btn btn-xs btn-outline-dark" style="border-radius: 6px; font-weight: 600; font-size: 11px;">
              📄 Cetak Slip PDF
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" class="text-center text-muted py-4" style="font-size: 13px;">
            Belum ada rekap payroll teragregasi untuk periode ini. Klik tombol <strong>"⚡ Hitung / Re-calculate THP"</strong> di atas.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
