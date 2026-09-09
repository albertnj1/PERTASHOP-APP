<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Slip Gaji — {{ $detail->operator->user->name ?? $detail->operator->nama ?? 'Operator' }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; color: #1e293b; padding: 30px; }
    .slip-card {
      background: #ffffff;
      max-width: 680px;
      margin: 0 auto;
      border-radius: 14px;
      border: 1px solid #cbd5e1;
      padding: 0;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      overflow: hidden;
    }

    /* Header */
    .slip-header {
      background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
      padding: 24px 28px;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
    }
    .slip-header .title { font-size: 18px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; }
    .slip-header .subtitle { font-size: 12px; color: #94a3b8; margin-top: 4px; }
    .slip-header .official-badge {
      background: rgba(255,255,255,0.15);
      color: #bfdbfe;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.5px;
      border: 1px solid rgba(255,255,255,0.2);
    }

    /* Scheme badge */
    .scheme-strip {
      padding: 8px 28px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .scheme-strip.komisi_murni { background: #e0f2fe; color: #0369a1; }
    .scheme-strip.gaji_pokok_murni { background: #f3e8ff; color: #6b21a8; }
    .scheme-strip.hibrid { background: #dcfce7; color: #166534; }

    /* Info operator */
    .info-block {
      padding: 18px 28px;
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      gap: 40px;
      font-size: 12.5px;
    }
    .info-block dt { color: #64748b; margin-bottom: 2px; font-weight: 500; }
    .info-block dd { font-weight: 700; color: #0f172a; }

    /* Section title */
    .section-title {
      padding: 14px 28px 6px;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }
    .section-title.penerimaan { color: #0284c7; border-top: 1px solid #f1f5f9; }
    .section-title.potongan { color: #dc2626; border-top: 1px solid #f1f5f9; }

    /* Row items */
    .row-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13px;
      padding: 8px 28px;
      border-bottom: 1px solid #f8fafc;
    }
    .row-item:hover { background: #f8fafc; }
    .row-item .label { color: #475569; }
    .row-item .label small { display: block; font-size: 11px; color: #94a3b8; margin-top: 1px; }
    .row-item .amount { font-weight: 700; color: #0f172a; }
    .row-item .amount.komisi { color: #0284c7; }
    .row-item .amount.deduct { color: #dc2626; }
    .row-item .amount.muted { color: #94a3b8; font-weight: 400; font-size: 12px; }

    /* Garis subtotal */
    .subtotal-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 28px;
      background: #f8fafc;
      font-size: 12.5px;
      border-top: 1px dashed #cbd5e1;
      border-bottom: 1px dashed #cbd5e1;
    }
    .subtotal-row .label { font-weight: 600; color: #334155; }
    .subtotal-row .amount { font-weight: 800; color: #0f172a; }

    /* THP */
    .thp-box {
      margin: 20px 28px;
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
      border: 1.5px solid #86efac;
      border-radius: 12px;
      padding: 18px 22px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .thp-box .thp-label { font-size: 13px; font-weight: 700; color: #166534; }
    .thp-box .thp-label small { display: block; font-size: 11px; font-weight: 500; color: #4ade80; }
    .thp-box .thp-amount { font-size: 26px; font-weight: 900; color: #15803d; }

    /* Kurang bayar warning */
    .kurang-box {
      margin: 0 28px 16px;
      background: #fff7ed;
      border: 1.5px solid #fed7aa;
      border-radius: 10px;
      padding: 12px 18px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 12.5px;
    }
    .kurang-box .kurang-icon { font-size: 18px; }
    .kurang-box .kurang-label { color: #9a3412; font-weight: 700; }
    .kurang-box .kurang-amount { color: #c2410c; font-weight: 900; margin-left: auto; font-size: 14px; }

    /* Footer */
    .slip-footer {
      padding: 14px 28px 20px;
      text-align: center;
      font-size: 11px;
      color: #94a3b8;
      border-top: 1px solid #f1f5f9;
    }
    .slip-footer strong { color: #64748b; }
  </style>
</head>
<body>

@php
  $system      = $detail->period->payrollSystem ?? null;
  $tipeSkema   = $system?->tipe_skema ?? 'komisi_murni';
  $isKomisi    = in_array($tipeSkema, ['komisi_murni', 'hibrid']);
  $isGajiPokok = in_array($tipeSkema, ['gaji_pokok_murni', 'hibrid']);

  // Dual-field fallback: baca gaji_variable (baru) atau total_bonus (lama)
  $gajiVariable      = floatval($detail->gaji_variable ?? $detail->total_bonus ?? 0);
  $gajiPokok         = floatval($detail->gaji_pokok ?? 0);
  $uangTransport     = floatval($detail->uang_transport ?? 0);
  $lembur            = floatval($detail->lembur ?? 0);
  $bonus             = floatval($detail->bonus ?? 0);
  $thr               = floatval($detail->thr ?? 0);

  // Potongan — dual-field fallback
  $potonganAlpha     = floatval($detail->potongan_tidak_masuk ?? $detail->potongan_kasbon ?? 0);
  $kurangSetoran     = floatval($detail->kurang_setoran ?? 0);
  $tabunganSetoran   = floatval($detail->tabungan_setoran ?? 0);
  $potonganHutang    = floatval($detail->potongan_hutang ?? 0);
  $tabunganGaji      = floatval($detail->tabungan_gaji ?? 0);

  $gajiKotor    = $gajiPokok + $gajiVariable + $uangTransport + $lembur + $bonus + $thr;
  $totalPotongan = $potonganAlpha + $kurangSetoran + $tabunganSetoran + $potonganHutang + $tabunganGaji;
  $thp          = floatval($detail->take_home_pay ?? max(0, $gajiKotor - $totalPotongan));
  $thpPembulatan = floatval($detail->thp_pembulatan ?? $detail->thp ?? $thp);
  $sisaKurangBayar = floatval($detail->sisa_kurang_bayar ?? 0);

  $literBagian  = floatval($detail->liter_bagian ?? 0);
  $ratePerLiter = $system ? floatval($system->rate_per_liter ?? 0) : 0;

  $schemeLabelMap = [
    'komisi_murni'    => '⛽ Komisi Murni — Liter × Tarif/Liter',
    'gaji_pokok_murni'=> '💰 Gaji Pokok Murni — Flat Bulanan',
    'hibrid'          => '🔀 Hibrid — Gaji Pokok + Komisi Liter',
  ];
  $schemeLabel = $schemeLabelMap[$tipeSkema] ?? $tipeSkema;
@endphp

<div class="slip-card">

  {{-- ── Header ── --}}
  <div class="slip-header">
    <div>
      <div class="title">Slip Gaji Operator</div>
      <div class="subtitle">
        Pertashop {{ $detail->period->shop->nama ?? '—' }}
        &nbsp;•&nbsp;
        Periode
        @php
          $bulan = $detail->period->bulan ?? null;
          $tahun = $detail->period->tahun ?? null;
          $bln   = $bulan ? \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->translatedFormat('F Y') : ($detail->period->year_month ?? '—');
        @endphp
        {{ $bln }}
      </div>
    </div>
    <div class="official-badge">OFFICIAL SLIP</div>
  </div>

  {{-- ── Badge Tipe Skema ── --}}
  <div class="scheme-strip {{ $tipeSkema }}">
    {{ $schemeLabel }}
    @if($system?->formula_description)
      &nbsp;|&nbsp; {{ $system->formula_description }}
    @endif
  </div>

  {{-- ── Info Operator ── --}}
  <div class="info-block">
    <div>
      <dt>Nama Operator</dt>
      <dd>{{ $detail->operator->user->name ?? $detail->operator->nama ?? '—' }}</dd>
    </div>
    <div>
      <dt>ID / Kode</dt>
      <dd>{{ $detail->operator->kode_operator ?? '#' . $detail->operator->id }}</dd>
    </div>
    <div>
      <dt>Cabang</dt>
      <dd>{{ $detail->period->shop->nama ?? '—' }}</dd>
    </div>
    <div>
      <dt>Hari Kerja</dt>
      <dd>{{ $detail->total_hari_kerja ?? '—' }} hari</dd>
    </div>
  </div>

  {{-- ══ PENERIMAAN ══ --}}
  <div class="section-title penerimaan">▲ Penerimaan (Komponen Gaji)</div>

  {{-- Gaji Pokok — tampil hanya untuk gaji_pokok_murni & hibrid --}}
  @if($isGajiPokok && $gajiPokok > 0)
  <div class="row-item">
    <div class="label">
      Gaji Pokok
      @if($system?->metode_split === 'flat_bulanan_prorata_hari')
        <small>Prorata {{ $detail->total_hari_kerja ?? 0 }} hari kerja</small>
      @else
        <small>Flat Bulanan</small>
      @endif
    </div>
    <div class="amount">Rp {{ number_format($gajiPokok, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- Komisi Liter — tampil hanya untuk komisi_murni & hibrid --}}
  @if($isKomisi)
  <div class="row-item">
    <div class="label">
      Insentif Volume Penjualan
      @if($literBagian > 0 && $ratePerLiter > 0)
        <small>{{ number_format($literBagian, 2, ',', '.') }} L &times; Rp {{ number_format($ratePerLiter, 0, ',', '.') }}/L</small>
      @elseif($literBagian > 0)
        <small>{{ number_format($literBagian, 2, ',', '.') }} L terjual</small>
      @endif
    </div>
    <div class="amount komisi">Rp {{ number_format($gajiVariable, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- Uang Transport --}}
  @if($uangTransport > 0)
  <div class="row-item">
    <div class="label">
      Uang Transport
      @if($system?->rate_transport_per_hari && $detail->total_hari_kerja)
        <small>{{ $detail->total_hari_kerja }} hari &times; Rp {{ number_format($system->rate_transport_per_hari, 0, ',', '.') }}/hari</small>
      @endif
    </div>
    <div class="amount">Rp {{ number_format($uangTransport, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- Lembur (jika ada) --}}
  @if($lembur > 0)
  <div class="row-item">
    <div class="label">Lembur</div>
    <div class="amount">Rp {{ number_format($lembur, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- Bonus (jika ada) --}}
  @if($bonus > 0)
  <div class="row-item">
    <div class="label">Bonus</div>
    <div class="amount">Rp {{ number_format($bonus, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- THR (jika ada) --}}
  @if($thr > 0)
  <div class="row-item">
    <div class="label">Tunjangan Hari Raya (THR)</div>
    <div class="amount">Rp {{ number_format($thr, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- Subtotal Gaji Kotor --}}
  <div class="subtotal-row">
    <div class="label">Total Gaji Kotor</div>
    <div class="amount">Rp {{ number_format($gajiKotor, 0, ',', '.') }}</div>
  </div>

  {{-- ══ POTONGAN ══ --}}
  @if($totalPotongan > 0)
  <div class="section-title potongan">▼ Potongan</div>

  {{-- Potongan Alpha/Tidak Masuk --}}
  @if($potonganAlpha > 0)
  <div class="row-item">
    <div class="label">
      Potongan Ketidakhadiran (Alpha)
      @if($system?->metode_potongan_alpha === 'prorata_gaji_pokok')
        <small>Prorata dari gaji pokok</small>
      @else
        <small>Nominal tetap per hari alpha</small>
      @endif
    </div>
    <div class="amount deduct">- Rp {{ number_format($potonganAlpha, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- Kurang Setoran --}}
  @if($kurangSetoran > 0)
  <div class="row-item">
    <div class="label">Potongan Kurang Setoran</div>
    <div class="amount deduct">- Rp {{ number_format($kurangSetoran, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- Tabungan Setoran --}}
  @if($tabunganSetoran > 0)
  <div class="row-item">
    <div class="label">Tabungan Setoran</div>
    <div class="amount deduct">- Rp {{ number_format($tabunganSetoran, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- Potongan Hutang / Kasbon --}}
  @if($detail->loanDeductions && $detail->loanDeductions->count() > 0)
    @foreach($detail->loanDeductions as $deduction)
    <div class="row-item">
      <div class="label">
        Potongan Kasbon (Cicilan ke-{{ $deduction->cicilan_ke }} dari {{ $deduction->total_tenor }})
        <small>Sisa Kasbon Tersisa : Rp {{ number_format($deduction->sisa_pokok_setelah, 0, ',', '.') }}</small>
      </div>
      <div class="amount deduct">- Rp {{ number_format($deduction->nominal_potongan, 0, ',', '.') }}</div>
    </div>
    @endforeach
  @elseif($potonganHutang > 0)
  <div class="row-item">
    <div class="label">Cicilan Kasbon / Pinjaman</div>
    <div class="amount deduct">- Rp {{ number_format($potonganHutang, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- Tabungan Gaji --}}
  @if($tabunganGaji > 0)
  <div class="row-item">
    <div class="label">Tabungan Gaji</div>
    <div class="amount deduct">- Rp {{ number_format($tabunganGaji, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- Subtotal Total Potongan --}}
  <div class="subtotal-row">
    <div class="label">Total Potongan</div>
    <div class="amount" style="color:#dc2626;">- Rp {{ number_format($totalPotongan, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- ══ TAKE HOME PAY ══ --}}
  <div class="thp-box">
    <div class="thp-label">
      Take Home Pay (Net Diterima)
      <small>Sudah dibulatkan ke ribuan terdekat</small>
    </div>
    <div class="thp-amount">Rp {{ number_format($thpPembulatan > 0 ? $thpPembulatan : $thp, 0, ',', '.') }}</div>
  </div>

  {{-- Warning: Sisa Kurang Bayar --}}
  @if($sisaKurangBayar > 0)
  <div class="kurang-box">
    <div class="kurang-icon">⚠️</div>
    <div class="kurang-label">Sisa Kurang Bayar (dibawa ke periode berikutnya)</div>
    <div class="kurang-amount">Rp {{ number_format($sisaKurangBayar, 0, ',', '.') }}</div>
  </div>
  @endif

  {{-- ── Footer ── --}}
  <div class="slip-footer">
    Dokumen ini dihasilkan secara otomatis oleh <strong>Pertashop Enterprise System</strong>.<br>
    Disahkan secara digital oleh Super Admin pada
    <strong>
      {{ $detail->period->approved_at
         ? \Carbon\Carbon::parse($detail->period->approved_at)->translatedFormat('d F Y, H:i')
         : \Carbon\Carbon::now()->translatedFormat('d F Y') }}
    </strong>.
  </div>

</div>
</body>
</html>
