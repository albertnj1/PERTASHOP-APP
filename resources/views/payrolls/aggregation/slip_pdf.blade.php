<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Slip Gaji Operator — {{ $detail->operator->nama ?? 'Operator' }}</title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8fafc; color: #1e293b; padding: 30px; }
    .slip-card { background: #ffffff; max-width: 650px; margin: 0 auto; border-radius: 12px; border: 1px solid #cbd5e1; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .header { border-bottom: 2px solid #0284c7; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .title { font-size: 20px; font-weight: 800; color: #0f172a; text-transform: uppercase; }
    .subtitle { font-size: 12px; color: #64748b; }
    .row-item { display: flex; justify-content: space-between; font-size: 13px; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
    .total-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 15px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
    .total-title { font-size: 13px; font-weight: 700; color: #166534; }
    .total-amount { font-size: 22px; font-weight: 900; color: #15803d; }
    .footer-note { font-size: 11px; color: #94a3b8; margin-top: 25px; text-center: center; }
  </style>
</head>
<body>

<div class="slip-card">
  <div class="header">
    <div>
      <div class="title">SLIP GAJI OPERATOR</div>
      <div class="subtitle">Pertashop {{ $detail->period->shop->nama ?? 'Kali Benda' }} • Periode {{ $detail->period->year_month }}</div>
    </div>
    <div style="text-align: right;">
      <span style="font-size: 11px; background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 6px; font-weight: 700;">OFFICIAL SLIP</span>
    </div>
  </div>

  <div style="margin-bottom: 20px; font-size: 13px;">
    <strong>Nama Operator:</strong> {{ $detail->operator->nama ?? 'Operator' }}<br>
    <strong>ID Operator:</strong> {{ $detail->operator->kode_operator ?? '-' }}
  </div>

  <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">PENERIMAAN (GAJI GROSS)</div>
  @if(floatval($detail->gaji_pokok) > 0)
  <div class="row-item">
    <span>Gaji Pokok</span>
    <strong>Rp {{ number_format(floatval($detail->gaji_pokok), 0, ',', '.') }}</strong>
  </div>
  @endif
  @if(floatval($detail->total_bonus) > 0)
  <div class="row-item">
    <span>Insentif Komisi Volume Liter</span>
    <strong style="color: #0284c7;">Rp {{ number_format(floatval($detail->total_bonus), 0, ',', '.') }}</strong>
  </div>
  @endif
  @if(floatval($detail->uang_transport) > 0)
  <div class="row-item">
    <span>Uang Transport</span>
    <strong>Rp {{ number_format(floatval($detail->uang_transport), 0, ',', '.') }}</strong>
  </div>
  @endif

  <div style="font-size: 12px; font-weight: 700; color: #dc2626; text-transform: uppercase; margin-top: 20px; margin-bottom: 8px;">POTONGAN</div>
  <div class="row-item">
    <span>Potongan Cicilan Kasbon</span>
    <strong style="color: #dc2626;">- Rp {{ number_format(floatval($detail->potongan_kasbon), 0, ',', '.') }}</strong>
  </div>
  <div class="row-item">
    <span>Potongan Kurang Setoran</span>
    <strong style="color: #dc2626;">- Rp {{ number_format(floatval($detail->kurang_setoran), 0, ',', '.') }}</strong>
  </div>

  <div class="total-box">
    <div class="total-title">TAKE HOME PAY (NET DITERIMA)</div>
    <div class="total-amount">Rp {{ number_format(floatval($detail->thp_pembulatan ?? $detail->thp), 0, ',', '.') }}</div>
  </div>

  <div class="footer-note" style="text-align: center;">
    Dokumen ini dihasilkan secara otomatis oleh Pertashop Enterprise System.<br>
    Disahkan secara digital oleh Super Admin pada {{ $detail->period->approved_at ? \Carbon\Carbon::parse($detail->period->approved_at)->format('d M Y H:i') : now()->format('d M Y') }}.
  </div>
</div>

</body>
</html>
