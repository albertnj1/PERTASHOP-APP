@extends('layouts._new_admin')
@section('title', 'Rekap Beban Penggajian')

@push('style')
  <style>
    /* Tab Navigation */
    .investor-payroll-tabs {
      display: flex;
      gap: 8px;
      border-bottom: 2px solid #e2e8f0;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }
    .investor-tab-btn {
      padding: 11px 22px;
      font-size: 13.5px;
      font-weight: 700;
      border: none;
      background: transparent;
      color: #64748b;
      border-radius: 10px 10px 0 0;
      cursor: pointer;
      border-bottom: 2px solid transparent;
      margin-bottom: -2px;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .investor-tab-btn:hover {
      color: #0f172a;
      background: #f8fafc;
    }
    .investor-tab-btn.active {
      color: #059669;
      border-bottom-color: #059669;
      background: #ffffff;
      box-shadow: 0 -2px 6px rgba(0,0,0,0.02);
    }
    .investor-tab-pane { display: none; }
    .investor-tab-pane.active { display: block; }

    /* KPI Summary Cards */
    .kpi-card {
      background: #ffffff;
      border-radius: 16px;
      border: 1px solid #e2e8f0;
      padding: 20px 22px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
      transition: all 0.2s ease;
      height: 100%;
    }
    .kpi-card:hover {
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
      transform: translateY(-2px);
    }
    .kpi-icon-box {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      margin-bottom: 14px;
    }

    /* Table Card */
    .payroll-card {
      background: #ffffff;
      border-radius: 20px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
      overflow: hidden;
    }
    .payroll-card-header {
      padding: 18px 24px;
      background: #ffffff;
      border-bottom: 1px solid #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
    }

    .badge-payment-success {
      background: #dcfce7;
      color: #15803d;
      font-weight: 700;
      font-size: 11.5px;
      padding: 5px 12px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .badge-payment-pending {
      background: #fef3c7;
      color: #b45309;
      font-weight: 700;
      font-size: 11.5px;
      padding: 5px 12px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .avatar-circle {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #f1f5f9;
      color: #475569;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 13px;
    }
  </style>
@endpush

@section('content')
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap" style="gap: 16px;">
    <div>
      <div class="d-flex align-items-center" style="gap: 10px;">
        <h1 class="page-title mb-0" style="font-size: 24px; font-weight: 900; color: #0f172a;">
          Rekap Beban Penggajian
        </h1>
        <span class="badge badge-secondary px-2 py-1" style="border-radius: 6px; font-size: 11px; background: #f1f5f9; color: #475569; font-weight: 700;">
          <i class="fas fa-eye mr-1"></i> Read-Only (Investor)
        </span>
      </div>
      <p class="text-muted mb-0 mt-1" style="font-size: 13px;">
        Ringkasan beban penggajian operator untuk outlet kepemilikan Anda yang telah diverifikasi dan disetujui secara final oleh Super Admin.
      </p>
    </div>
  </div>

  {{-- ═══ TAB NAVIGATION ═══════════════════════════════════════════════════════ --}}
  <div class="investor-payroll-tabs">
    <button class="investor-tab-btn active" data-tab="rekap">
      <i class="fas fa-file-invoice-dollar"></i> Rekap Beban Gaji
    </button>
    <button class="investor-tab-btn" data-tab="perbandingan">
      <i class="fas fa-chart-line"></i> Perbandingan Outlet Saya
    </button>
  </div>

  {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 1: REKAP BEBAN PENGGAJIAN FINAL                                         --}}
  {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
  <div class="investor-tab-pane active" id="tab-rekap">

    {{-- Filter Toolbar: Outlet Milik Investor Saja & Periode --}}
    <div class="payroll-card mb-4" style="padding: 16px 22px;">
      <form method="GET" action="{{ route('payroll-systems.index') }}" class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 14px;">
        <input type="hidden" name="tab" value="rekap">

        <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
          <div class="d-flex align-items-center" style="gap: 8px;">
            <label class="text-muted mb-0" style="font-size: 11.5px; font-weight: 800; text-transform: uppercase;">Outlet Saya:</label>
            <select name="shop_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; min-width: 180px;" onchange="this.form.submit()">
              @forelse($shops as $shop)
                <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>
                  {{ $shop->nama }}
                </option>
              @empty
                <option value="">Belum ada outlet terdaftar</option>
              @endforelse
            </select>
          </div>

          <div class="d-flex align-items-center" style="gap: 8px;">
            <label class="text-muted mb-0" style="font-size: 11.5px; font-weight: 800; text-transform: uppercase;">Tahun:</label>
            <select name="tahun" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; width: 100px;" onchange="this.form.submit()">
              @foreach($availableYears as $y)
                <option value="{{ $y }}" {{ $selectedTahun == $y ? 'selected' : '' }}>{{ $y }}</option>
              @endforeach
            </select>
          </div>

          <div class="d-flex align-items-center" style="gap: 8px;">
            <label class="text-muted mb-0" style="font-size: 11.5px; font-weight: 800; text-transform: uppercase;">Bulan:</label>
            <select name="bulan" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; min-width: 140px;" onchange="this.form.submit()">
              <option value="all" {{ $selectedBulan === 'all' ? 'selected' : '' }}>Semua Bulan ({{ $selectedTahun }})</option>
              @foreach(range(1, 12) as $m)
                @php
                  $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$m];
                @endphp
                <option value="{{ $m }}" {{ (string) $selectedBulan === (string) $m ? 'selected' : '' }}>
                  {{ $namaBulan }}
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="text-muted" style="font-size: 12.5px;">
          <i class="fas fa-store-alt text-success mr-1"></i> Outlet: <strong>{{ $selectedShop?->nama ?? 'Tidak Dipilih' }}</strong>
        </div>
      </form>
    </div>

    @if($hasPendingDraft)
      <div class="alert alert-warning mb-4 d-flex align-items-center" style="border-radius: 12px; border: none; background: #fffbeb; color: #b45309;">
        <i class="fas fa-info-circle mr-3" style="font-size: 18px;"></i>
        <div style="font-size: 13px;">
          <strong>Penggajian Sedang Berjalan:</strong> Periode bulan ini saat ini sedang dalam proses verifikasi operasional / draft oleh Super Admin dan belum difinalisasi. Data di bawah hanya menampilkan periode yang sudah resmi disetujui.
        </div>
      </div>
    @endif

    {{-- Ringkasan Kartu KPI --}}
    <div class="row mb-4">
      <div class="col-md-3 col-sm-6 mb-3">
        <div class="kpi-card">
          <div class="kpi-icon-box" style="background: #ecfdf5; color: #059669;">
            <i class="fas fa-money-bill-wave"></i>
          </div>
          <div class="text-muted" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Total Beban Gaji Final</div>
          <div class="font-weight-bold mt-1" style="font-size: 20px; color: #0f172a;">
            Rp {{ number_format($totalBebanGaji, 0, ',', '.') }}
          </div>
          <div class="text-muted mt-1 small">
            Tahun {{ $selectedTahun }}{{ $selectedBulan !== 'all' ? ' (Bulan ' . $selectedBulan . ')' : '' }}
          </div>
        </div>
      </div>

      <div class="col-md-3 col-sm-6 mb-3">
        <div class="kpi-card">
          <div class="kpi-icon-box" style="background: #eff6ff; color: #2563eb;">
            <i class="fas fa-users"></i>
          </div>
          <div class="text-muted" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Operator Tergaji</div>
          <div class="font-weight-bold mt-1" style="font-size: 20px; color: #0f172a;">
            {{ $totalOperatorTergaji }} <span style="font-size: 14px; font-weight: 500; color: #64748b;">Orang</span>
          </div>
          <div class="text-muted mt-1 small">
            Tercatat di periode final
          </div>
        </div>
      </div>

      <div class="col-md-3 col-sm-6 mb-3">
        <div class="kpi-card">
          <div class="kpi-icon-box" style="background: #faf5ff; color: #9333ea;">
            <i class="fas fa-calendar-check"></i>
          </div>
          <div class="text-muted" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Total Kehadiran</div>
          <div class="font-weight-bold mt-1" style="font-size: 20px; color: #0f172a;">
            {{ $totalHariKerja }} <span style="font-size: 14px; font-weight: 500; color: #64748b;">Hari Kerja</span>
          </div>
          <div class="text-muted mt-1 small">
            ± {{ $totalHariKerja * 8 }} Jam Kerja Kumulatif
          </div>
        </div>
      </div>

      <div class="col-md-3 col-sm-6 mb-3">
        <div class="kpi-card">
          <div class="kpi-icon-box" style="background: #f0fdf4; color: #16a34a;">
            <i class="fas fa-shield-alt"></i>
          </div>
          <div class="text-muted" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Status Verifikasi</div>
          <div class="font-weight-bold mt-1" style="font-size: 16px; color: #15803d;">
            <i class="fas fa-check-circle mr-1"></i> Disetujui &amp; Final
          </div>
          <div class="text-muted mt-1 small">
            Otoritas Super Admin / Finance
          </div>
        </div>
      </div>
    </div>

    {{-- Tabel Rekap Penggajian (Format Bersih & Read-Only) --}}
    <div class="payroll-card">
      <div class="payroll-card-header">
        <div class="font-weight-bold" style="font-size: 16px; color: #0f172a;">
          <i class="fas fa-receipt mr-2 text-success"></i>Daftar Penggajian Operator — {{ $selectedShop?->nama ?? 'Outlet' }}
        </div>
        <div class="text-muted" style="font-size: 12.5px;">
          Hanya menyajikan data yang statusnya <strong>Final</strong>
        </div>
      </div>

      @if($periods->isEmpty())
        <div class="text-center py-5 px-3">
          <div style="width: 56px; height: 56px; border-radius: 16px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; color: #94a3b8; font-size: 24px;">
            <i class="fas fa-clipboard-check"></i>
          </div>
          <div style="font-weight: 700; color: #334155; font-size: 15px;">Belum Ada Data Gaji Final</div>
          <div class="text-muted small mt-1">
            Belum ada rekapan penggajian yang disetujui &amp; difinalisasi oleh Super Admin untuk periode ini.<br>
            Data akan otomatis tersaji di sini begitu periode penggajian telah dikunci oleh bagian keuangan pusat.
          </div>
        </div>
      @else
        @foreach($periods as $period)
          <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap" style="background: #f8fafc; gap: 12px;">
            <div>
              <strong style="font-size: 15px; color: #0f172a;">{{ $period->periode_label }}</strong>
              <span class="badge badge-success ml-2 px-2 py-1" style="border-radius: 6px;">
                <i class="fas fa-lock mr-1"></i> Final &amp; Disetujui
              </span>
              <span class="text-muted ml-2" style="font-size: 12.5px;">
                • Disahkan oleh: {{ $period->approver?->name ?? $period->generatedBy?->name ?? 'Super Admin' }}
              </span>
            </div>
            <div>
              <a href="{{ route('payroll.export-pdf', $period->id) }}" class="btn btn-sm btn-outline-danger font-weight-bold" style="border-radius: 8px; font-size: 12px; padding: 6px 14px;">
                <i class="fas fa-file-pdf mr-1"></i> Unduh Slip Gaji (PDF)
              </a>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr style="background: #ffffff; border-bottom: 2px solid #edf2f7;">
                  <th style="font-size: 11px; text-transform: uppercase; color: #64748b; padding: 12px 20px; width: 60px;">No</th>
                  <th style="font-size: 11px; text-transform: uppercase; color: #64748b;">Nama Operator</th>
                  <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #64748b;">Total Jam Kerja / Kehadiran</th>
                  <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #64748b;">Total Nominal Gaji</th>
                  <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #64748b;">Status Pembayaran</th>
                  <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #64748b; padding-right: 20px;">Rincian</th>
                </tr>
              </thead>
              <tbody>
                @forelse($period->details as $idx => $detail)
                  <tr>
                    <td class="text-muted font-weight-bold" style="font-size: 13px; padding: 14px 20px;">
                      {{ $idx + 1 }}
                    </td>
                    <td>
                      <div class="d-flex align-items-center" style="gap: 12px;">
                        <div class="avatar-circle">
                          {{ strtoupper(substr($detail->operator->user?->name ?? 'O', 0, 1)) }}
                        </div>
                        <div>
                          <div class="font-weight-bold" style="font-size: 13.5px; color: #0f172a;">
                            {{ $detail->operator->user?->name ?? '-' }}
                          </div>
                          <div class="text-muted small">
                            NIK / Username: {{ $detail->operator->user?->username ?? '-' }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <span class="font-weight-bold" style="font-size: 13px; color: #1e293b;">
                        {{ $detail->total_hari_kerja }} Hari Hadir
                      </span>
                      <div class="text-muted" style="font-size: 11.5px;">
                        ± {{ $detail->total_hari_kerja * 8 }} Jam Kerja
                      </div>
                    </td>
                    <td class="text-right">
                      <span class="font-weight-bold" style="font-size: 14px; color: #059669;">
                        Rp {{ number_format($detail->hitungTHP(), 0, ',', '.') }}
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge-payment-success">
                        <i class="fas fa-check-circle"></i> Sudah Dibayar
                      </span>
                    </td>
                    <td class="text-right" style="padding-right: 20px;">
                      <button type="button" class="btn btn-sm btn-light border font-weight-bold" style="border-radius: 8px; font-size: 11.5px; padding: 4px 10px;" data-toggle="modal" data-target="#detailModal-{{ $detail->id }}">
                        <i class="fas fa-search-plus mr-1 text-primary"></i> Detail Komponen
                      </button>

                      {{-- Read-Only Breakdown Modal --}}
                      <div class="modal fade text-left" id="detailModal-{{ $detail->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                          <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                            <div class="modal-header px-4 py-3" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                              <div>
                                <h5 class="modal-title font-weight-bold" style="font-size: 15px; color: #0f172a;">
                                  Rincian Gaji: {{ $detail->operator->user?->name ?? 'Operator' }}
                                </h5>
                                <span class="text-muted small">Periode: {{ $period->periode_label }} • Status: Final</span>
                              </div>
                              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                            </div>
                            <div class="modal-body p-4" style="font-size: 13px;">
                              <div class="mb-3 p-3 rounded" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                                <div class="d-flex justify-content-between align-items-center">
                                  <span class="text-muted">Total Diterima (Take Home Pay):</span>
                                  <span class="font-weight-bold" style="font-size: 17px; color: #15803d;">
                                    Rp {{ number_format($detail->hitungTHP(), 0, ',', '.') }}
                                  </span>
                                </div>
                              </div>

                              <h6 class="font-weight-bold text-success mb-2" style="font-size: 12px; text-transform: uppercase;">
                                <i class="fas fa-plus-circle mr-1"></i> Komponen Pendapatan Kotor
                              </h6>
                              <div class="p-2 mb-3 bg-light rounded" style="font-size: 12.5px;">
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                  <span>Gaji Pokok</span>
                                  <strong>Rp {{ number_format($detail->gaji_pokok, 0, ',', '.') }}</strong>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                  <span>Komisi Penjualan Liter ({{ number_format($detail->liter_bagian, 1, ',', '.') }} L)</span>
                                  <strong>Rp {{ number_format($detail->gaji_variable, 0, ',', '.') }}</strong>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                  <span>Uang Transport</span>
                                  <strong>Rp {{ number_format($detail->uang_transport, 0, ',', '.') }}</strong>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                  <span>Lembur &amp; Hari Raya</span>
                                  <strong>Rp {{ number_format(floatval($detail->lembur) + floatval($detail->lembur_hari_raya), 0, ',', '.') }}</strong>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                  <span>Bonus &amp; THR</span>
                                  <strong>Rp {{ number_format(floatval($detail->bonus) + floatval($detail->thr), 0, ',', '.') }}</strong>
                                </div>
                                <div class="d-flex justify-content-between py-1 font-weight-bold text-dark">
                                  <span>Total Pendapatan Kotor</span>
                                  <span>Rp {{ number_format($detail->total_gaji_kotor, 0, ',', '.') }}</span>
                                </div>
                              </div>

                              <h6 class="font-weight-bold text-danger mb-2" style="font-size: 12px; text-transform: uppercase;">
                                <i class="fas fa-minus-circle mr-1"></i> Komponen Potongan
                              </h6>
                              <div class="p-2 bg-light rounded" style="font-size: 12.5px;">
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                  <span>Potongan Alpha (Tidak Masuk)</span>
                                  <span>- Rp {{ number_format($detail->potongan_tidak_masuk, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                  <span>Kurang Setoran</span>
                                  <span>- Rp {{ number_format($detail->kurang_setoran, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                  <span>Tabungan Gaji / Setoran</span>
                                  <span>- Rp {{ number_format(floatval($detail->tabungan_gaji) + floatval($detail->tabungan_setoran), 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom">
                                  <span>Cicilan Kasbon / Hutang</span>
                                  <span>- Rp {{ number_format($detail->potongan_hutang, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1 font-weight-bold text-danger">
                                  <span>Total Potongan</span>
                                  <span>- Rp {{ number_format($detail->total_potongan, 0, ',', '.') }}</span>
                                </div>
                              </div>
                            </div>
                            <div class="modal-footer px-4 py-2" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                              <button type="button" class="btn btn-secondary btn-sm" style="border-radius: 8px;" data-dismiss="modal">Tutup</button>
                            </div>
                          </div>
                        </div>
                      </div>

                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      Belum ada detail operator pada periode ini.
                    </td>
                  </tr>
                @endforelse
              </tbody>
              <tfoot style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                <tr>
                  <td colspan="3" class="text-right font-weight-bold" style="font-size: 13px; padding: 12px 20px;">
                    Total Beban Gaji Outlet ({{ $period->periode_label }}):
                  </td>
                  <td class="text-right font-weight-bold" style="font-size: 15px; color: #059669;">
                    Rp {{ number_format($period->details->sum(fn($d) => $d->hitungTHP()), 0, ',', '.') }}
                  </td>
                  <td colspan="2"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        @endforeach
      @endif
    </div>

  </div>{{-- /tab-rekap --}}

  {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB 2: PERBANDINGAN OUTLET SAYA                                           --}}
  {{-- ═══════════════════════════════════════════════════════════════════════════ --}}
  <div class="investor-tab-pane" id="tab-perbandingan">

    <div class="payroll-card mb-4" style="padding: 16px 22px;">
      <form method="GET" action="{{ route('payroll-systems.index') }}" class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 12px;">
        <input type="hidden" name="tab" value="perbandingan">
        <div class="d-flex align-items-center" style="gap: 10px;">
          <label class="text-muted mb-0" style="font-size: 12px; font-weight: 800; text-transform: uppercase;">Bulan Periode:</label>
          <input type="month" name="benchmark_month" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; width: auto;"
            value="{{ $benchmarkMonth }}" onchange="this.form.submit()">
        </div>
        <div class="text-muted small">
          <i class="fas fa-lock mr-1 text-primary"></i> Data hanya menampilkan outlet di mana Anda memiliki kepemilikan saham.
        </div>
      </form>
    </div>

    <div class="payroll-card">
      <div class="payroll-card-header">
        <div class="font-weight-bold" style="font-size: 16px; color: #0f172a;">
          <i class="fas fa-chart-line mr-2 text-primary"></i>Performa Bisnis &amp; Beban Gaji Outlet Saya
        </div>
        <div class="text-muted" style="font-size: 12px;">
          Periode: {{ \Carbon\Carbon::createFromFormat('Y-m', $benchmarkMonth)->translatedFormat('F Y') }}
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr style="background: #f8fafc;">
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #475569; padding: 12px 16px; width: 80px;">No</th>
              <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Pertashop / Outlet</th>
              <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569;">Penjualan (L)</th>
              <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569;">Rupiah Penjualan</th>
              <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569;">Beban Gaji Final</th>
              <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569;">Biaya Operasional</th>
              <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569;">Laba Bersih</th>
              <th class="text-right" style="font-size: 11px; text-transform: uppercase; color: #475569; padding-right: 20px;">Losses / Gain</th>
            </tr>
          </thead>
          <tbody>
            @forelse($benchmarks as $idx => $b)
              <tr>
                <td class="text-center font-weight-bold" style="font-size: 13px; padding: 12px 16px;">
                  #{{ $idx + 1 }}
                </td>
                <td style="font-size: 13.5px; font-weight: 700; color: #0f172a;">
                  {{ $b['nama'] }}
                </td>
                <td class="text-right font-weight-bold" style="font-size: 13px;">
                  {{ number_format($b['total_volume'], 1, ',', '.') }} L
                </td>
                <td class="text-right" style="font-size: 13px;">
                  Rp {{ number_format($b['total_rupiah'], 0, ',', '.') }}
                </td>
                <td class="text-right font-weight-bold" style="font-size: 13px; color: #2563eb;">
                  Rp {{ number_format($b['total_gaji'], 0, ',', '.') }}
                </td>
                <td class="text-right" style="font-size: 13px; color: #dc2626;">
                  Rp {{ number_format($b['total_cost'], 0, ',', '.') }}
                </td>
                <td class="text-right font-weight-bold" style="font-size: 13.5px; color: #059669;">
                  Rp {{ number_format($b['total_profit'], 0, ',', '.') }}
                </td>
                <td class="text-right font-weight-bold" style="font-size: 13px; color: {{ $b['total_losses'] < 0 ? '#dc2626' : '#059669' }}; padding-right: 20px;">
                  {{ number_format($b['total_losses'], 2, ',', '.') }} L
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-5 text-muted" style="font-size: 13px;">
                  <i class="fas fa-info-circle mr-1"></i> Tidak ada data perbandingan untuk outlet Anda pada bulan ini.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>{{-- /tab-perbandingan --}}

@endsection

@push('scripts')
  <script>
  (function() {
    const btns  = document.querySelectorAll('.investor-tab-btn');
    const panes = document.querySelectorAll('.investor-tab-pane');

    const urlTab   = new URLSearchParams(window.location.search).get('tab');
    const savedTab = urlTab || localStorage.getItem('investorPayrollTab') || 'rekap';

    function activateTab(tabId) {
      btns.forEach(b  => b.classList.toggle('active',  b.dataset.tab === tabId));
      panes.forEach(p => p.classList.toggle('active', p.id === 'tab-' + tabId));
      localStorage.setItem('investorPayrollTab', tabId);
    }

    activateTab(savedTab);

    btns.forEach(btn => {
      btn.addEventListener('click', () => activateTab(btn.dataset.tab));
    });
  })();
  </script>
@endpush
