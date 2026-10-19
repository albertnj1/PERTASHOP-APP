@extends('layouts._new_admin')
@section('title', 'Kasbon & Pinjaman Karyawan')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Kasbon &amp; Pinjaman Karyawan</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">
      @if(Auth::user()->role === 'investor')
        Monitoring transparansi kasbon operator di outlet Anda (hanya lihat / read-only)
      @else
        Pengajuan, tracking cicilan, dan alur persetujuan kasbon operator Pertashop
      @endif
    </p>
  </div>
  @if(Auth::user()->role !== 'investor')
  <div>
    <button type="button" class="btn btn-primary" style="border-radius: 9px; font-weight: 600; font-size: 13px;" data-toggle="modal" data-target="#modalPengajuanKasbon">
      Ajukan Kasbon Baru
    </button>
  </div>
  @endif
</div>

@if(Auth::user()->role === 'investor' && !empty($investorSummary))
<div class="row mb-4">
  <div class="col-12 col-md-4 mb-3 mb-md-0">
    <div class="panel h-100" style="padding: 18px 20px; border-left: 4px solid #dc2626;">
      <div class="text-muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: .4px;">Total Kasbon Berjalan (Piutang Karyawan)</div>
      <div style="font-size: 24px; font-weight: 800; color: #b91c1c; margin-top: 6px;">Rp {{ number_format($investorSummary['piutang_aktif'], 0, ',', '.') }}</div>
      <small class="text-muted">Sisa kasbon yang belum lunas di outlet Anda</small>
    </div>
  </div>
  <div class="col-12 col-md-4 mb-3 mb-md-0">
    <div class="panel h-100" style="padding: 18px 20px; border-left: 4px solid #0284c7;">
      <div class="text-muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: .4px;">Potongan Cicilan Masuk Bulan Ini</div>
      <div style="font-size: 24px; font-weight: 800; color: #0369a1; margin-top: 6px;">Rp {{ number_format($investorSummary['cicilan_bulan_ini'], 0, ',', '.') }}</div>
      <small class="text-muted">Estimasi pengembalian via potong gaji bulan berjalan</small>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="panel h-100" style="padding: 18px 20px; border-left: 4px solid #16a34a;">
      <div class="text-muted" style="font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: .4px;">Total Kasbon Selesai (Lunas)</div>
      <div style="font-size: 24px; font-weight: 800; color: #15803d; margin-top: 6px;">Rp {{ number_format($investorSummary['total_lunas'], 0, ',', '.') }}</div>
      <small class="text-muted">{{ $investorSummary['jumlah_lunas'] }} pengajuan telah lunas</small>
    </div>
  </div>
</div>
@endif

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

<div class="panel">
  <div class="panel-head">
    <div class="panel-title">Daftar Pengajuan &amp; Tracking Cicilan Kasbon</div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr style="background: var(--page-bg);">
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">#</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Operator</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Toko</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Tanggal</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Total Kasbon</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Tenor &amp; Angsuran</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Progress Cicilan</th>
          <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Keterangan</th>
          <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Status</th>
          <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Disetujui Oleh</th>
          @if(collect(['super-admin','admin'])->contains(Auth::user()->role))
          <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Aksi</th>
          @endif
        </tr>
      </thead>
      <tbody>
        @forelse($loans as $loan)
        @php
          $tenor = max(1, (int)($loan->tenor_bulan ?: 1));
          $ke = (int)($loan->angsuran_ke ?? 0);
          $pct = min(100, round(($ke / $tenor) * 100));
          $isLunas = $loan->status === 'lunas' || ($ke > 0 && floatval($loan->sisa_pokok) <= 0 && !in_array($loan->status, ['pending', 'rejected']));
          $sisa = floatval($loan->sisa_pokok > 0 ? $loan->sisa_pokok : ($isLunas ? 0 : $loan->jumlah));
        @endphp
        <tr>
          <td style="font-size: 13px;">{{ $loop->iteration }}</td>
          <td style="font-size: 13px; font-weight: 600;">{{ $loan->operator?->user?->name ?? '-' }}</td>
          <td style="font-size: 13px;"><span class="status-pill" style="background:#e2e8f0; color:#475569;">{{ $loan->operator?->shop?->nama }}</span></td>
          <td style="font-size: 13px;">{{ $loan->tanggal?->format('d/m/Y') }}</td>
          <td style="font-size: 13px; font-weight: 700; color: var(--red);">Rp {{ number_format($loan->jumlah, 0, ',', '.') }}</td>
          <td style="font-size: 12px;">
            <div style="font-weight: 700; color: #1e293b;">{{ $tenor }} Bulan</div>
            <small class="text-muted">Rp {{ number_format($loan->nominal_angsuran ?: round($loan->jumlah / $tenor, 2), 0, ',', '.') }} / bln</small>
          </td>
          <td style="font-size: 12px; min-width: 170px;">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <strong style="color: {{ $isLunas ? '#15803d' : '#0284c7' }};">
                Ke-{{ $ke }} dari {{ $tenor }}
              </strong>
              <span style="font-size: 10.5px; font-weight: 700; color: #64748b;">{{ $pct }}%</span>
            </div>
            <div class="progress mb-1" style="height: 6px; border-radius: 9999px; background: #e2e8f0;">
              <div class="progress-bar {{ $isLunas ? 'bg-success' : 'bg-primary' }}" style="width: {{ $pct }}%;"></div>
            </div>
            <small class="text-muted d-block">
              Sisa: <strong style="color: {{ $sisa > 0 ? '#b91c1c' : '#15803d' }};">Rp {{ number_format($sisa, 0, ',', '.') }}</strong>
            </small>
          </td>
          <td style="font-size: 12px; color: var(--muted); max-width: 180px;">{{ $loan->keterangan ?? '-' }}</td>
          <td class="text-center">
            @if($isLunas)
              <span class="status-pill" style="background:#dcfce7; color:#15803d; font-weight:700;"><i class="fas fa-check-double mr-1"></i> Lunas</span>
            @elseif($loan->status === 'active')
              <span class="status-pill" style="background:#dbeafe; color:#1e40af; font-weight:700;"><i class="fas fa-sync-alt mr-1"></i> Berjalan</span>
            @elseif($loan->status === 'approved')
              <span class="status-pill" style="background:#e0f2fe; color:#0369a1; font-weight:600;"><i class="fas fa-check mr-1"></i> Disetujui</span>
            @elseif($loan->status === 'rejected')
              <span class="status-pill" style="background:#fee2e2; color:#b91c1c;"><i class="fas fa-times mr-1"></i> Ditolak</span>
            @else
              <span class="status-pill" style="background:#fef3c7; color:#b45309;"><i class="fas fa-clock mr-1"></i> Menunggu Approval</span>
            @endif
          </td>
          <td class="text-center text-muted" style="font-size: 12px;">
            @if($loan->approver)
              {{ $loan->approver->name }} <span style="font-size: 11px; opacity:0.8;">({{ $loan->approved_at?->format('d/m/Y H:i') }})</span>
            @else
              —
            @endif
          </td>
          @if(collect(['super-admin','admin'])->contains(Auth::user()->role))
          <td class="text-center" style="white-space: nowrap;">
            @if($loan->status === 'pending')
              {{-- Tombol Setujui --}}
              <form action="{{ route('employee-loans.approve', $loan->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success font-weight-bold" style="border-radius: 6px; font-size: 11px; padding: 4px 10px;" onclick="return confirm('Setujui pengajuan kasbon sebesar Rp {{ number_format($loan->jumlah, 0, ',', '.') }} untuk {{ addslashes($loan->operator?->user?->name) }}?')">
                  <i class="fas fa-check mr-1"></i> Setujui
                </button>
              </form>
              {{-- Tombol Tolak --}}
              <form action="{{ route('employee-loans.reject', $loan->id) }}" method="POST" class="d-inline ml-1">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger font-weight-bold" style="border-radius: 6px; font-size: 11px; padding: 4px 10px;" onclick="return confirm('Tolak pengajuan kasbon ini?')">
                  <i class="fas fa-times mr-1"></i> Tolak
                </button>
              </form>
              {{-- Tombol Ubah Tenor --}}
              <button type="button" class="btn btn-sm btn-outline-secondary ml-1" style="border-radius: 6px; font-size: 11px; padding: 4px 8px;" onclick="openEditTenorModal({{ $loan->id }}, '{{ addslashes($loan->operator?->user?->name) }}', {{ $tenor }}, {{ $sisa }})" title="Sesuaikan Tenor">
                <i class="fas fa-edit"></i> Tenor
              </button>
            @elseif(!$isLunas && in_array($loan->status, ['approved', 'active']))
              {{-- Tombol Langsung Bayar Cicilan (Tinggal pencet centang) --}}
              <form action="{{ route('employee-loans.pay-installment', $loan->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary font-weight-bold" style="border-radius: 6px; font-size: 11px; padding: 4px 10px; background: #0284c7; border: none;" onclick="return confirm('Catat pembayaran cicilan ke-{{ $ke + 1 }} dari {{ $tenor }} (Rp {{ number_format($loan->nominal_angsuran ?: round($sisa / max(1, $tenor - $ke)), 0, ',', '.') }}) untuk {{ addslashes($loan->operator?->user?->name) }}?')">
                  <i class="fas fa-check-circle mr-1"></i> Bayar Cicilan Ke-{{ $ke + 1 }}
                </button>
              </form>

              {{-- Tombol Ubah / Sesuaikan Tenor Langsung --}}
              <button type="button" class="btn btn-sm btn-outline-secondary ml-1" style="border-radius: 6px; font-size: 11px; padding: 4px 8px;" onclick="openEditTenorModal({{ $loan->id }}, '{{ addslashes($loan->operator?->user?->name) }}', {{ $tenor }}, {{ $sisa }})" title="Sesuaikan Tenor">
                <i class="fas fa-edit"></i> Tenor
              </button>

              {{-- Dropdown Aksi Lanjutan: Lunasi Sekaligus --}}
              <div class="dropdown d-inline ml-1">
                <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-toggle="dropdown" style="border-radius: 6px; font-size: 11px; padding: 4px 8px; border: 1px solid #cbd5e1;" title="Menu Lainnya">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right" style="border-radius: 10px; font-size: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                  <a class="dropdown-item" href="javascript:void(0)" onclick="openEditTenorModal({{ $loan->id }}, '{{ addslashes($loan->operator?->user?->name) }}', {{ $tenor }}, {{ $sisa }})">
                    <i class="fas fa-calendar-alt text-primary mr-2"></i> Sesuaikan Tenor ({{ $tenor }} bln)
                  </a>
                  <div class="dropdown-divider"></div>
                  <form action="{{ route('employee-loans.pay-full', $loan->id) }}" method="POST" onsubmit="return confirm('Konfirmasi pelunasan penuh kasbon Rp {{ number_format($sisa, 0, ',', '.') }} untuk {{ addslashes($loan->operator?->user?->name) }}?')">
                    @csrf
                    <button type="submit" class="dropdown-item text-success font-weight-bold">
                      <i class="fas fa-check-double mr-2"></i> Lunasi Penuh Sekaligus
                    </button>
                  </form>
                </div>
              </div>
            @else
              <span class="badge badge-success px-2 py-1" style="border-radius: 6px; font-size: 11px;"><i class="fas fa-check-double mr-1"></i> Lunas</span>
            @endif
          </td>
          @endif
        </tr>
        @empty
        <tr>
          <td colspan="11" class="text-center text-muted py-4" style="font-size: 13px;">Belum ada riwayat pengajuan kasbon.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- MODAL PENGAJUAN KASBON --}}
<div class="modal fade" id="modalPengajuanKasbon" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
      <div class="modal-header" style="background: var(--page-bg); border-bottom: 1px solid var(--border);">
        <h5 class="modal-title" style="font-weight: 700; font-size: 15px;">Form Pengajuan Kasbon</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form action="{{ route('employee-loans.store') }}" method="POST">
        @csrf
        <div class="modal-body" style="padding: 20px;">
          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Operator</label>
            @if(Auth::user()->role === 'operator')
              <input type="text" class="form-control" style="border-radius: 8px; background: var(--page-bg);" value="{{ Auth::user()->name }} ({{ Auth::user()->operator?->shop?->nama }})" readonly>
              <input type="hidden" name="operator_id" value="{{ Auth::user()->operator?->id }}">
            @else
              <select name="operator_id" class="form-control" style="border-radius: 8px;" required>
                @foreach($operators as $op)
                  <option value="{{ $op->id }}">{{ $op->user?->name ?? 'Operator #'.$op->id }} ({{ $op->shop?->nama }})</option>
                @endforeach
              </select>
            @endif
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Tanggal Pengajuan</label>
            <input type="date" name="tanggal" class="form-control" style="border-radius: 8px;" value="{{ now()->format('Y-m-d') }}" required>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Nominal Kasbon (Rp)</label>
            <input type="number" id="input_jumlah_kasbon" name="jumlah" class="form-control" style="border-radius: 8px;" placeholder="Contoh: 5000000" min="1000" step="any" required>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Tenor / Jumlah Angsuran (Bulan)</label>
            <select name="tenor_bulan" id="loan_tenor_bulan" class="form-control font-weight-bold" style="border-radius: 8px;" required>
              <option value="1">1 Bulan (Langsung Lunas Bulan Ini)</option>
              <option value="2">2 Bulan</option>
              <option value="3">3 Bulan</option>
              <option value="4">4 Bulan</option>
              <option value="5">5 Bulan</option>
              <option value="6">6 Bulan</option>
              <option value="7">7 Bulan</option>
              <option value="8">8 Bulan</option>
              <option value="9">9 Bulan</option>
              <option value="10">10 Bulan</option>
              <option value="11">11 Bulan</option>
              <option value="12" selected>12 Bulan (1 Tahun)</option>
              <option value="18">18 Bulan (1,5 Tahun)</option>
              <option value="24">24 Bulan (2 Tahun)</option>
            </select>
          </div>

          {{-- Kalkulasi Otomatis (Read-only Info) --}}
          <div class="mb-3 p-3" id="kalkulasi_cicilan_box" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">Estimasi Potongan per Bulan:</span>
              <span id="label_angsuran_per_bulan" style="font-size: 15px; font-weight: 800; color: #15803d;">Rp 0 / bulan</span>
            </div>
            <small class="text-muted d-block" id="keterangan_angsuran" style="font-size: 11.5px;">
              Cicilan otomatis dipotong langsung dari slip gaji operator setiap bulannya.
            </small>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Alasan / Keterangan</label>
            <textarea name="keterangan" class="form-control" style="border-radius: 8px;" rows="3" placeholder="Contoh: Keperluan pribadi / perbaikan kendaraan" required></textarea>
          </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid var(--border);">
          <button type="button" class="btn btn-light btn-sm" style="border-radius: 8px;" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm font-weight-bold" style="border-radius: 8px;">Kirim Pengajuan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL UBAH / SESUAIKAN TENOR --}}
<div class="modal fade" id="modalEditTenor" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
      <div class="modal-header px-4 py-3" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
        <div>
          <h6 class="modal-title font-weight-bold mb-0" style="font-size: 14px; color: #0f172a;">
            <i class="fas fa-calendar-alt text-primary mr-1"></i> Sesuaikan Tenor Kasbon
          </h6>
          <small class="text-muted" id="edit-tenor-subtitle">-</small>
        </div>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="formEditTenor" method="POST" action="">
        @csrf
        <div class="modal-body p-4">
          <input type="hidden" id="edit_tenor_sisa_pokok" value="0">
          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Pilih Tenor Baru</label>
            <select name="tenor_bulan" id="edit_select_tenor" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px;" onchange="hitungEstimasiEditTenor()" required>
              @for($i = 1; $i <= 24; $i++)
                <option value="{{ $i }}">{{ $i }} Bulan</option>
              @endfor
            </select>
          </div>
          <div class="p-3" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;">
            <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">Estimasi Cicilan Baru:</div>
            <div id="edit_preview_angsuran" style="font-size: 15px; font-weight: 800; color: #15803d; margin-top: 2px;">Rp 0 / bulan</div>
          </div>
        </div>
        <div class="modal-footer px-4 py-3" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
          <button type="button" class="btn btn-light btn-sm" style="border-radius: 8px;" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm font-weight-bold" style="border-radius: 8px; padding: 6px 16px;">
            <i class="fas fa-save mr-1"></i> Simpan Tenor
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('script')
<script>
  function hitungCicilanKasbon() {
    const jumlahInput = document.getElementById('input_jumlah_kasbon');
    const tenorSelect = document.getElementById('loan_tenor_bulan');
    const labelAngsuran = document.getElementById('label_angsuran_per_bulan');
    const keterangan = document.getElementById('keterangan_angsuran');

    if (!jumlahInput || !tenorSelect || !labelAngsuran) return;

    const jumlah = parseFloat(jumlahInput.value) || 0;
    const tenor = parseInt(tenorSelect.value) || 1;

    if (jumlah > 0) {
      const cicilan = Math.ceil(jumlah / tenor);
      const formattedCicilan = new Intl.NumberFormat('id-ID').format(cicilan);
      const formattedTotal = new Intl.NumberFormat('id-ID').format(jumlah);

      labelAngsuran.textContent = 'Rp ' + formattedCicilan + ' / bulan';
      keterangan.innerHTML = `Kasbon <strong>Rp ${formattedTotal}</strong> dicicil selama <strong>${tenor} bulan</strong> &rarr; <em>Cicilan Rp ${formattedCicilan} / bulan</em>.`;
    } else {
      labelAngsuran.textContent = 'Rp 0 / bulan';
      keterangan.textContent = 'Cicilan otomatis dipotong langsung dari slip gaji operator setiap bulannya.';
    }
  }

  function openEditTenorModal(loanId, operatorName, currentTenor, sisaPokok) {
    const form = document.getElementById('formEditTenor');
    form.action = `/employee-loans/${loanId}/update-tenor`;
    document.getElementById('edit-tenor-subtitle').textContent = operatorName;
    document.getElementById('edit_select_tenor').value = currentTenor;
    document.getElementById('edit_tenor_sisa_pokok').value = sisaPokok;
    hitungEstimasiEditTenor();
    $('#modalEditTenor').modal('show');
  }

  function hitungEstimasiEditTenor() {
    const sisa = parseFloat(document.getElementById('edit_tenor_sisa_pokok').value) || 0;
    const tenor = parseInt(document.getElementById('edit_select_tenor').value) || 1;
    const cicilan = Math.ceil(sisa / tenor);
    document.getElementById('edit_preview_angsuran').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(cicilan) + ' / bulan';
  }

  document.addEventListener('DOMContentLoaded', function() {
    const jumlahInput = document.getElementById('input_jumlah_kasbon');
    const tenorSelect = document.getElementById('loan_tenor_bulan');

    if (jumlahInput) {
      jumlahInput.addEventListener('input', hitungCicilanKasbon);
    }
    if (tenorSelect) {
      tenorSelect.addEventListener('change', hitungCicilanKasbon);
    }
  });
</script>
@endpush
