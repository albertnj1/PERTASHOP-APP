@extends('layouts._new_admin')
@section('title', 'Proses Penggajian Operator')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Proses Penggajian Operator</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Generate dan kelola periode penggajian bulanan operator Pertashop</p>
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

<div class="row cols-2-1">

  {{-- ===== PANEL GENERATE ===== --}}
  <div>
    <div class="panel mb-4">
      <div class="panel-head">
        <div class="panel-title">Generate Gaji Baru</div>
      </div>
      <form action="{{ route('payroll.generate') }}" method="POST" id="form-generate">
        @csrf

        <div class="form-group mb-3">
          <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Pertashop</label>
          <select name="shop_id" id="gen-shop" class="form-control" style="border-radius: 8px;" required onchange="this.form.submit()">
            @foreach($shops as $shop)
              <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
            @endforeach
          </select>
        </div>

              <div class="form-row">
                <div class="form-group col-6">
                  <label class="font-weight-bold">Bulan</label>
                  <select name="bulan" id="gen-bulan" class="form-control" required onchange="checkFinalStatus()">
                    @foreach(range(1,12) as $b)
                      @php
                        $namaB = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$b];
                        $statusB = $periodStatusMap->get($b);
                      @endphp
                      <option value="{{ $b }}"
                        data-status="{{ $statusB ?? '' }}"
                        {{ now()->month == $b ? 'selected' : '' }}>
                        {{ $namaB }} {{ $statusB ? '(' . ucfirst($statusB) . ')' : '' }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group col-6">
                  <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Tahun</label>
                  <select name="tahun" class="form-control" style="border-radius: 8px;" required>
                    @foreach($availableYears as $y)
                      <option value="{{ $y }}" {{ $selectedTahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              {{-- Alert status periode yang dipilih --}}
              <div id="final-alert" class="alert alert-danger py-2 small d-none" style="border-radius: 8px;">
                <strong>Periode sudah Final.</strong> Data terkunci, tidak bisa di-generate ulang.
              </div>
              <div id="draft-alert" class="alert alert-warning py-2 small d-none" style="border-radius: 8px;">
                Sudah ada draft untuk bulan ini. Generate ulang akan menimpa draft yang ada.
              </div>

              {{-- Komponen Tambahan / Potongan Dinamis (Keterangan + Nominal) --}}
              <div class="card my-3" style="border: 1px dashed #cbd5e1; border-radius: 10px; background: #f8fafc;">
                <div class="card-body p-3">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size: 12px; font-weight: 700; color: #1e293b;">➕ Komponen Tambahan (Opsional)</span>
                    <button type="button" class="btn btn-xs btn-outline-primary btn-add-custom-row" style="border-radius: 6px; font-weight: 600; font-size: 11px;">
                      + Tambah Baris
                    </button>
                  </div>
                  <div id="custom-items-container-index">
                    {{-- Baris akan ditambahkan via JavaScript --}}
                  </div>
                </div>
              </div>

              <p class="text-muted small">
                Sistem akan otomatis menarik data laporan harian &amp; jadwal shift untuk menghitung gaji.
              </p>

              <button type="submit" id="btn-submit-gen" class="btn btn-success btn-block font-weight-bold" style="border-radius: 8px;">
                Generate Gaji (Toko Terpilih)
              </button>
            </form>

            <hr class="my-3">

            <form action="{{ route('payroll.bulk-generate') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memproses bulk generate gaji untuk SELURUH toko pada bulan dan tahun terpilih? Toko berstatus Final akan otomatis dilewati.')">
              @csrf
              <input type="hidden" name="bulan" value="{{ now()->month }}">
              <input type="hidden" name="tahun" value="{{ $selectedTahun }}">
              <button type="submit" class="btn btn-outline-primary btn-block font-weight-bold" style="border-radius: 8px;">
                Generate Semua Toko (Draft)
              </button>
            </form>

          </div>
        </div>

        {{-- Shortcut ke Data Master --}}
        <div class="panel">
          <div class="panel-head">
            <div class="panel-title">Setup Data Master</div>
          </div>
          <div class="py-1">
            <a href="{{ route('payroll-systems.index') }}" class="btn btn-outline-primary btn-sm btn-block mb-1" style="border-radius: 6px;">
              Sistem Penggajian
            </a>
            <a href="{{ route('payroll-operator-assignments.index') }}" class="btn btn-outline-secondary btn-sm btn-block">
              <i class="fas fa-user-tag"></i> Assign Operator
            </a>
          </div>
        </div>
      </div>

      {{-- ===== DAFTAR PERIODE ===== --}}
      <div class="col-lg-8">

        {{-- Filter Tahun --}}
        <div class="card mb-3">
          <div class="card-body py-2">
            <form method="GET" class="form-inline">
              <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
              <label class="mr-2 font-weight-bold">Tahun:</label>
              <select name="tahun" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                @foreach($availableYears as $y)
                  <option value="{{ $y }}" {{ $selectedTahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
              </select>
              <label class="mr-2 font-weight-bold">Toko:</label>
              <select name="shop_id" class="form-control form-control-sm" onchange="this.form.submit()">
                @foreach($shops as $shop)
                  <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
                @endforeach
              </select>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Riwayat Penggajian — {{ $selectedTahun }}</h5>
          </div>
          <div class="card-body p-0">
            @if($periods->isEmpty())
              <div class="text-center text-muted py-5">
                <i class="fas fa-file-invoice-dollar fa-3x mb-3 d-block" style="opacity:.3"></i>
                Belum ada data penggajian untuk tahun {{ $selectedTahun }}.<br>
                Gunakan form di sebelah kiri untuk generate gaji pertama.
              </div>
            @else
            <table class="table table-hover mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Periode</th>
                  <th>Toko</th>
                  <th>Total Liter</th>
                  <th>Operator</th>
                  <th>Status</th>
                  <th>Generate</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($periods as $period)
                <tr>
                  <td><strong>{{ $period->periode_label }}</strong></td>
                  <td>{{ $period->shop->nama }}</td>
                  <td>
                    <span class="number">{{ $period->total_penjualan_liter }}</span> L
                  </td>
                  <td>{{ $period->details->count() }} operator</td>
                  <td>
                    @if($period->isFinal())
                      <span class="badge badge-success"><i class="fas fa-lock mr-1"></i>Final</span>
                    @else
                      <span class="badge badge-warning"><i class="fas fa-edit mr-1"></i>Draft</span>
                    @endif
                  </td>
                  <td class="text-muted small">
                    {{ $period->generated_at ? $period->generated_at->format('d/m/Y H:i') : '-' }}
                    <br><span class="text-muted">{{ $period->generatedBy?->name ?? '-' }}</span>
                  </td>
                  <td>
                    <a href="{{ route('payroll.show', $period->id) }}" class="btn-action-modern btn-info-modern" title="Detail">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="{{ route('payroll.export-pdf', $period->id) }}" class="btn-action-modern" title="Export PDF"
                      style="background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%); color: white;">
                      <i class="fas fa-file-pdf"></i>
                    </a>
                    @if($period->isDraft())
                    <button class="btn-action-modern btn-delete-modern btn-delete-period" data-id="{{ $period->id }}" title="Hapus Draft">
                      <i class="fa fa-trash"></i>
                    </button>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
            @endif
          </div>
        </div>

      </div>
    </div>

  </div>
</div>
@endsection

@push('script')
<script>
// ─── Cek status periode saat bulan dipilih ───────────────────────────────────
function checkFinalStatus() {
  const sel       = document.getElementById('gen-bulan');
  const opt       = sel.options[sel.selectedIndex];
  const status    = opt ? opt.dataset.status : '';
  const btnGen    = document.getElementById('btn-generate');
  const alertFin  = document.getElementById('final-alert');
  const alertDraf = document.getElementById('draft-alert');

  alertFin.classList.add('d-none');
  alertDraf.classList.add('d-none');
  btnGen.disabled = false;
  btnGen.classList.remove('btn-secondary');
  btnGen.classList.add('btn-success');

  if (status === 'final') {
    alertFin.classList.remove('d-none');
    btnGen.disabled = true;
    btnGen.classList.remove('btn-success');
    btnGen.classList.add('btn-secondary');
  } else if (status === 'draft') {
    alertDraf.classList.remove('d-none');
  }
}

// Jalankan saat halaman load
document.addEventListener('DOMContentLoaded', function() {
  checkFinalStatus();

  // Konfirmasi sebelum generate (double-check jika status draft)
  document.getElementById('form-generate').addEventListener('submit', function(e) {
    const sel    = document.getElementById('gen-bulan');
    const opt    = sel.options[sel.selectedIndex];
    const status = opt ? opt.dataset.status : '';

    if (status === 'final') {
      e.preventDefault();
      Swal.fire('Tidak Bisa', 'Periode sudah final dan terkunci.', 'error');
      return;
    }

    if (status === 'draft') {
      // Sudah ada draft, minta konfirmasi
      e.preventDefault();
      const form = this;
      Swal.fire({
        title: 'Generate Ulang?',
        text: 'Sudah ada draft untuk bulan ini. Data draft lama akan diganti. Lanjutkan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Generate Ulang',
        cancelButtonText: 'Batal'
      }).then(result => { if (result.isConfirmed) form.submit(); });
    }
  });
});

// ─── Hapus Draft Period (Event Delegation) ───────────────────────────────────
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-delete-period');
  if (!btn) return;
  e.preventDefault();

  const id = btn.dataset.id;
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

  Swal.fire({
    title: 'Hapus Draft Penggajian?',
    text: 'Data draft yang belum difinalisasi akan dihapus permanen.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonText: 'Batal',
    confirmButtonText: 'Hapus Draft'
  }).then(result => {
    if (result.isConfirmed) {
      fetch(`/payroll/${id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })
      .then(async r => {
        const data = await r.json().catch(() => ({}));
        if (!r.ok || data.error) {
          Swal.fire('Gagal', data.error || data.message || 'Gagal menghapus draft.', 'error');
        } else {
          Swal.fire({
            title: 'Berhasil!',
            text: 'Draft penggajian berhasil dihapus.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
          }).then(() => location.reload());
        }
      })
      .catch(err => {
        Swal.fire('Gagal', 'Terjadi kesalahan sistem: ' + err.message, 'error');
      });
    }
  });
});

// ─── Dynamic Custom Items Row Handler ──────────────────────────────────────
let customRowIndexIndex = 0;

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-add-custom-row');
  if (!btn) return;
  e.preventDefault();

  const container = btn.closest('.card-body').querySelector('#custom-items-container-index, #custom-items-container-sys');
  if (!container) return;

  const idx = customRowIndexIndex++;
  const rowHtml = `
    <div class="custom-item-row p-2 mb-2 border rounded" style="background:#ffffff; font-size:12px; border-color:#cbd5e1!important;" id="custom-row-${idx}">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <select name="custom_items[${idx}][tipe]" class="form-control form-control-sm py-0 px-2" style="width: auto; height: 24px; font-size: 11px; font-weight:700;">
          <option value="tambahan">+ Pendapatan Tambahan</option>
          <option value="potongan">− Potongan Lain</option>
        </select>
        <button type="button" class="btn btn-xs text-danger btn-remove-custom-row p-0 ml-2" style="font-weight:bold; font-size:16px; line-height:1;" title="Hapus baris">&times;</button>
      </div>
      <div class="form-row">
        <div class="col-7 pr-1">
          <input type="text" name="custom_items[${idx}][nama_item]" class="form-control form-control-sm" placeholder="1. Keterangan (ex: Bonus Oli)" style="font-size:11px;" required>
        </div>
        <div class="col-5 pl-1">
          <input type="number" name="custom_items[${idx}][jumlah]" class="form-control form-control-sm" placeholder="2. Nominal (Rp)" style="font-size:11px;" min="0" step="1000" required>
        </div>
      </div>
    </div>
  `;

  container.insertAdjacentHTML('beforeend', rowHtml);
});

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-remove-custom-row');
  if (!btn) return;
  e.preventDefault();
  const row = btn.closest('.custom-item-row');
  if (row) row.remove();
});
</script>
@endpush
