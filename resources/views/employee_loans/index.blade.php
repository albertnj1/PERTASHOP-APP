@extends('layouts._new_admin')
@section('title', 'Kasbon & Pinjaman Karyawan')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Kasbon &amp; Pinjaman Karyawan</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Pengajuan, tracking cicilan, dan alur persetujuan kasbon operator Pertashop</p>
  </div>
  <div>
    <button type="button" class="btn btn-primary" style="border-radius: 9px; font-weight: 600; font-size: 13px;" data-toggle="modal" data-target="#modalPengajuanKasbon">
      Ajukan Kasbon Baru
    </button>
  </div>
</div>

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
        <tr>
          <td style="font-size: 13px;">{{ $loop->iteration }}</td>
          <td style="font-size: 13px; font-weight: 600;">{{ $loan->operator?->user?->name ?? '-' }}</td>
          <td style="font-size: 13px;"><span class="status-pill" style="background:#e2e8f0; color:#475569;">{{ $loan->operator?->shop?->nama }}</span></td>
          <td style="font-size: 13px;">{{ $loan->tanggal?->format('d/m/Y') }}</td>
          <td style="font-size: 13px; font-weight: 700; color: var(--red);">Rp {{ number_format($loan->jumlah, 0, ',', '.') }}</td>
          <td style="font-size: 12px;">
            <div style="font-weight: 600; color: #1e293b;">{{ $loan->tenor_bulan ?? 1 }} Bulan</div>
            <small class="text-muted">Rp {{ number_format($loan->nominal_angsuran ?: ($loan->jumlah / max(1, $loan->tenor_bulan)), 0, ',', '.') }} / bln</small>
          </td>
          <td style="font-size: 12px;">
            <div style="font-weight: 600; color: #0284c7;">Ke-{{ $loan->angsuran_ke ?? 0 }} dari {{ $loan->tenor_bulan ?? 1 }}</div>
            <small class="text-muted">Sisa: Rp {{ number_format($loan->sisa_pokok ?? $loan->jumlah, 0, ',', '.') }}</small>
          </td>
          <td style="font-size: 12px; color: var(--muted); max-width: 180px;">{{ $loan->keterangan ?? '-' }}</td>
          <td class="text-center">
            @if($loan->status === 'lunas' || floatval($loan->sisa_pokok) <= 0 && $loan->status !== 'pending' && $loan->status !== 'rejected')
              <span class="status-pill" style="background:#dcfce7; color:#15803d; font-weight:700;">✓ Lunas</span>
            @elseif($loan->status === 'active')
              <span class="status-pill" style="background:#e0f2fe; color:#0369a1; font-weight:600;">⚡ Aktif</span>
            @elseif($loan->status === 'approved')
              <span class="status-pill" style="background:#dcfce7; color:#15803d;">Disetujui</span>
            @elseif($loan->status === 'rejected')
              <span class="status-pill" style="background:#fee2e2; color:#b91c1c;">Ditolak</span>
            @else
              <span class="status-pill" style="background:#fef3c7; color:#b45309;">Menunggu Approval</span>
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
          <td class="text-center">
            @if($loan->status === 'pending')
              <form action="{{ route('employee-loans.approve', $loan->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success" style="border-radius: 6px; font-size: 11px; padding: 3px 8px;" onclick="return confirm('Setujui pengajuan kasbon ini?')">Setujui</button>
              </form>
              <form action="{{ route('employee-loans.reject', $loan->id) }}" method="POST" class="d-inline ml-1">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 6px; font-size: 11px; padding: 3px 8px;" onclick="return confirm('Tolak pengajuan kasbon ini?')">Tolak</button>
              </form>
            @else
              <span class="text-muted" style="font-size: 11px;">Selesai</span>
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
    <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
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
            <input type="number" id="input_jumlah_kasbon" name="jumlah" class="form-control" style="border-radius: 8px;" placeholder="Contoh: 600000" min="0" step="any" required>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Tenor / Jumlah Angsuran (Bulan)</label>
            <select name="tenor_bulan" id="loan_tenor_bulan" class="form-control" style="border-radius: 8px;" required>
              <option value="1" selected>1 Bulan (Langsung Lunas Bulan Ini)</option>
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
              <option value="12">12 Bulan</option>
            </select>
          </div>

          {{-- Kalkulasi Otomatis (Read-only Info) --}}
          <div class="mb-3 p-3" id="kalkulasi_cicilan_box" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span style="font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Estimasi Potongan per Bulan:</span>
              <span id="label_angsuran_per_bulan" style="font-size: 14px; font-weight: 800; color: #15803d;">Rp 0 / bulan</span>
            </div>
            <small class="text-muted d-block" id="keterangan_angsuran" style="font-size: 11px;">
              Cicilan otomatis dipotong langsung dari slip gaji operator setiap bulannya.
            </small>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Alasan / Keterangan</label>
            <textarea name="keterangan" class="form-control" style="border-radius: 8px;" rows="3" placeholder="Contoh: Pinjaman darurat pengobatan keluarga" required></textarea>
          </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid var(--border);">
          <button type="button" class="btn btn-light btn-sm" style="border-radius: 8px;" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 8px; font-weight: 600;">Kirim Pengajuan</button>
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
      keterangan.innerHTML = `Kasbon <strong>Rp ${formattedTotal}</strong> dicicil selama <strong>${tenor} bulan</strong> &rarr; Otomatis tampil: <em>Cicilan Rp ${formattedCicilan} / bulan</em>.`;
    } else {
      labelAngsuran.textContent = 'Rp 0 / bulan';
      keterangan.textContent = 'Cicilan otomatis dipotong langsung dari slip gaji operator setiap bulannya.';
    }
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
