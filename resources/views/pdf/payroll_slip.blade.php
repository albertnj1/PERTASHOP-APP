<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Slip Gaji — {{ $payroll->periode_label }} — {{ $payroll->shop->nama }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; }

    .page-break { page-break-after: always; }

    .slip-wrapper {
      width: 100%;
      max-width: 720px;
      margin: 0 auto 20px auto;
      border: 1px solid #ccc;
      padding: 20px;
    }

    /* Header Slip */
    .slip-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 3px solid #1a5c2e;
      padding-bottom: 10px;
      margin-bottom: 12px;
    }
    .slip-header .company { font-size: 18px; font-weight: bold; color: #1a5c2e; }
    .slip-header .sub { font-size: 10px; color: #666; }
    .slip-header .period-badge {
      background: #1a5c2e;
      color: white;
      padding: 6px 12px;
      border-radius: 4px;
      text-align: center;
      font-size: 11px;
    }

    /* Operator Info */
    .operator-info {
      background: #f5f5f5;
      padding: 8px 12px;
      border-radius: 4px;
      margin-bottom: 12px;
      display: flex;
      gap: 30px;
    }
    .operator-info .label { font-size: 9px; color: #888; text-transform: uppercase; }
    .operator-info .value { font-size: 12px; font-weight: bold; }

    /* Komponen Gaji */
    table.komponen { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.komponen th {
      background: #e8f5e9;
      color: #1a5c2e;
      font-size: 9px;
      text-transform: uppercase;
      padding: 5px 8px;
      text-align: left;
      border-bottom: 1px solid #a5d6a7;
    }
    table.komponen td {
      padding: 4px 8px;
      font-size: 10px;
      border-bottom: 1px solid #f0f0f0;
    }
    table.komponen td.label { color: #555; }
    table.komponen td.amount { text-align: right; font-weight: bold; }
    table.komponen tr.total-row td {
      border-top: 2px solid #1a5c2e;
      font-weight: bold;
      font-size: 11px;
      padding-top: 6px;
    }
    table.komponen tr.subtotal td {
      background: #f9f9f9;
      font-size: 10px;
      font-style: italic;
    }

    /* THP */
    .thp-box {
      background: #1a5c2e;
      color: white;
      padding: 10px 16px;
      border-radius: 4px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 10px;
    }
    .thp-box .thp-label { font-size: 11px; font-weight: bold; }
    .thp-box .thp-amount { font-size: 18px; font-weight: bold; }

    /* Footer Slip */
    .slip-footer {
      margin-top: 16px;
      font-size: 9px;
      color: #999;
      text-align: center;
      border-top: 1px solid #eee;
      padding-top: 8px;
    }
    .status-badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 10px;
      font-size: 9px;
      font-weight: bold;
    }
    .status-final { background: #d4edda; color: #155724; }
    .status-draft { background: #fff3cd; color: #856404; }

    .negative { color: #c0392b; }
  </style>
</head>
<body>

@foreach($payroll->details as $i => $detail)
<div class="slip-wrapper {{ !$loop->last ? 'page-break' : '' }}">

  {{-- Header --}}
  <div class="slip-header">
    <div>
      <div class="company">{{ $payroll->shop->nama }}</div>
      <div class="sub">{{ $payroll->shop->corporation?->nama ?? 'Pertashop' }}</div>
      <div class="sub" style="margin-top:4px;">
        <span class="status-badge {{ $payroll->isFinal() ? 'status-final' : 'status-draft' }}">
          {{ $payroll->isFinal() ? '✓ Final' : '⚠ Draft' }}
        </span>
        <span style="display:inline-block; margin-left:6px; background:#e2e8f0; color:#1e293b; padding:2px 8px; border-radius:10px; font-size:9px; font-weight:bold;">
          {{ $payroll->payrollSystem->tipe_skema_label }}
        </span>
      </div>
    </div>
    <div class="period-badge">
      <div>SLIP GAJI</div>
      <div style="font-size:14px;font-weight:bold;">{{ $payroll->periode_label }}</div>
    </div>
  </div>

  {{-- Operator Info --}}
  <div class="operator-info">
    <div>
      <div class="label">Nama Operator</div>
      <div class="value">{{ $detail->operator->user?->name ?? '-' }}</div>
    </div>
    <div>
      <div class="label">Hari Kerja</div>
      <div class="value">{{ $detail->total_hari_kerja }} hari</div>
    </div>
    <div>
      <div class="label">Liter Bagian</div>
      <div class="value">{{ number_format($detail->liter_bagian, 2, ',', '.') }} L</div>
    </div>
    <div>
      <div class="label">Rate/Liter</div>
      <div class="value">
        @if($payroll->payrollSystem->isGajiPokokMurni())
          <span style="color:#888;font-size:10px;">— (Gaji Pokok Murni)</span>
        @elseif($payroll->payrollSystem->ada_rate_per_liter)
          Rp {{ number_format($payroll->payrollSystem->rate_per_liter, 0, ',', '.') }}
        @else
          —
        @endif
      </div>
    </div>
  </div>

  {{-- Komponen --}}
  <table class="komponen">
    <thead>
      <tr>
        <th style="width:60%">Komponen</th>
        <th style="width:40%;text-align:right;">Nominal</th>
      </tr>
    </thead>
    <tbody>
      {{-- Pendapatan --}}
      <tr>
        <td class="label" colspan="2" style="background:#e8f5e9;font-weight:bold;font-size:10px;">+ PENDAPATAN</td>
      </tr>
      @if(!$payroll->payrollSystem->isKomisiMurni() && $detail->gaji_pokok > 0)
      <tr>
        <td class="label">Gaji Pokok</td>
        <td class="amount">Rp {{ number_format($detail->gaji_pokok, 0, ',', '.') }}</td>
      </tr>
      @endif
      @if(!$payroll->payrollSystem->isGajiPokokMurni() && ($payroll->payrollSystem->ada_rate_per_liter || $detail->gaji_variable > 0))
      <tr>
        <td class="label">
          Komisi Penjualan Liter
          @if($payroll->payrollSystem->ada_rate_per_liter)
          <span style="color:#888;font-style:italic;">
            ({{ number_format($detail->liter_bagian, 2, ',', '.') }} L × Rp {{ number_format($payroll->payrollSystem->rate_per_liter, 0, ',', '.') }})
          </span>
          @endif
        </td>
        <td class="amount">Rp {{ number_format($detail->gaji_variable, 0, ',', '.') }}</td>
      </tr>
      @endif
      @if($detail->uang_transport > 0)
      <tr>
        <td class="label">Uang Transport ({{ $detail->total_hari_kerja }} hari hadir)</td>
        <td class="amount">Rp {{ number_format($detail->uang_transport, 0, ',', '.') }}</td>
      </tr>
      @endif
      @if($detail->lembur > 0)
      <tr>
        <td class="label">Lembur</td>
        <td class="amount">Rp {{ number_format($detail->lembur, 0, ',', '.') }}</td>
      </tr>
      @endif
      @if($detail->lembur_hari_raya > 0)
      <tr>
        <td class="label">Lembur Hari Raya</td>
        <td class="amount">Rp {{ number_format($detail->lembur_hari_raya, 0, ',', '.') }}</td>
      </tr>
      @endif
      @if($detail->bonus > 0)
      <tr>
        <td class="label">Bonus</td>
        <td class="amount">Rp {{ number_format($detail->bonus, 0, ',', '.') }}</td>
      </tr>
      @endif
      @if($detail->thr > 0)
      <tr>
        <td class="label">THR (Tunjangan Hari Raya)</td>
        <td class="amount">Rp {{ number_format($detail->thr, 0, ',', '.') }}</td>
      </tr>
      @endif
      @foreach($detail->items->where('tipe', 'tambahan') as $item)
      <tr>
        <td class="label">{{ $item->nama_item }} @if($item->keterangan)<span style="color:#888;">({{ $item->keterangan }})</span>@endif</td>
        <td class="amount">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</td>
      </tr>
      @endforeach
      <tr class="subtotal">
        <td class="label">Total Pendapatan Kotor</td>
        <td class="amount">Rp {{ number_format($detail->total_gaji_kotor, 0, ',', '.') }}</td>
      </tr>

      {{-- Potongan --}}
      <tr>
        <td class="label" colspan="2" style="background:#ffeef0;font-weight:bold;font-size:10px;padding-top:8px;">− POTONGAN</td>
      </tr>
      @if($detail->potongan_tidak_masuk > 0)
      <tr>
        <td class="label">Potongan Tidak Masuk (Alpha)</td>
        <td class="amount negative">- Rp {{ number_format($detail->potongan_tidak_masuk, 0, ',', '.') }}</td>
      </tr>
      @endif
      @if($detail->kurang_setoran > 0)
      <tr>
        <td class="label">Potongan Kurang Setoran (Harian)</td>
        <td class="amount negative">- Rp {{ number_format($detail->kurang_setoran, 0, ',', '.') }}</td>
      </tr>
      @endif
      @if($detail->tabungan_gaji > 0)
      <tr>
        <td class="label">Tabungan Gaji</td>
        <td class="amount negative">- Rp {{ number_format($detail->tabungan_gaji, 0, ',', '.') }}</td>
      </tr>
      @endif
      @if($detail->tabungan_setoran > 0)
      <tr>
        <td class="label">Tabungan Setoran</td>
        <td class="amount negative">- Rp {{ number_format($detail->tabungan_setoran, 0, ',', '.') }}</td>
      </tr>
      @endif
      @if($detail->potongan_hutang > 0)
      <tr>
        <td class="label">Potongan Hutang / Cicilan</td>
        <td class="amount negative">- Rp {{ number_format($detail->potongan_hutang, 0, ',', '.') }}</td>
      </tr>
      @endif
      @foreach($detail->items->where('tipe', 'potongan') as $item)
      <tr>
        <td class="label">{{ $item->nama_item }} @if($item->keterangan)<span style="color:#888;">({{ $item->keterangan }})</span>@endif</td>
        <td class="amount negative">- Rp {{ number_format($item->jumlah, 0, ',', '.') }}</td>
      </tr>
      @endforeach
      <tr class="subtotal">
        <td class="label">Total Potongan</td>
        <td class="amount negative">- Rp {{ number_format($detail->total_potongan, 0, ',', '.') }}</td>
      </tr>
    </tbody>
  </table>

  {{-- THP --}}
  <div class="thp-box" style="{{ $detail->take_home_pay < 0 ? 'background:#ffebee;border:1px solid #ef5350;' : '' }}">
    <div class="thp-label">Take Home Pay (Gaji Bersih)</div>
    <div class="thp-amount" style="{{ $detail->take_home_pay < 0 ? 'color:#c62828;' : '' }}">
      Rp {{ number_format($detail->take_home_pay, 0, ',', '.') }}
    </div>
    @if($detail->take_home_pay < 0)
    <div style="font-size:10px;color:#c62828;margin-top:4px;">
      (Sisa Hutang Kurang Bayar Ke Bulan Berikutnya: Rp {{ number_format($detail->sisa_kurang_bayar, 0, ',', '.') }})
    </div>
    @endif
  </div>

  @if($detail->catatan)
  <div style="margin-top:8px;font-size:10px;color:#666;">
    <strong>Catatan:</strong> {{ $detail->catatan }}
  </div>
  @endif

  {{-- Footer --}}
  <div class="slip-footer">
    Digenerate {{ $payroll->generated_at?->format('d M Y, H:i') ?? now()->format('d M Y') }}
    oleh {{ $payroll->generatedBy?->name ?? 'Sistem' }}
    • Sistem: {{ $payroll->payrollSystem->nama_sistem }}
    • {{ $payroll->payrollSystem->metode_split_label }}
  </div>

</div>
@endforeach

</body>
</html>
