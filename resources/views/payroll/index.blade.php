@extends('layouts._new_admin')
@section('title', 'Proses Penggajian Operator')

@push('style')
<style>
  .payroll-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    overflow: hidden;
  }

  .payroll-card-header {
    padding: 18px 22px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
  }

  .btn-emerald-primary {
    background: #059669;
    color: #ffffff;
    font-weight: 700;
    border: none;
    border-radius: 10px;
    padding: 10px 18px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
  }
  .btn-emerald-primary:hover {
    background: #047857;
    color: #ffffff;
    transform: translateY(-1px);
  }

  .btn-slate-outline {
    background: #ffffff;
    color: #334155;
    font-weight: 700;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    padding: 9px 18px;
    transition: all 0.2s ease;
  }
  .btn-slate-outline:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #94a3b8;
  }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap" style="gap: 16px;">
  <div>
    <h1 class="page-title mb-1" style="font-size: 24px; font-weight: 900; color: #0f172a;">Proses Penggajian Operator</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Generate dan kelola periode penggajian bulanan operator Pertashop</p>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 12px; border: none; background: #dcfce7; color: #15803d; font-weight: 600;">
    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif
@if(isset($errors) && $errors->any())
  <div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius: 12px; border: none; background: #fee2e2; color: #b91c1c; font-weight: 600;">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif

<div class="row">

  {{-- ===== KOLOM KIRI (38%): PARAMETER GENERATE ===== --}}
  <div class="col-lg-5 mb-4">
    <div class="payroll-card mb-4">
      <div class="payroll-card-header">
        <div class="font-weight-bold" style="font-size: 15px; color: #0f172a;">
          <i class="fas fa-calculator text-success mr-2"></i>Hitung Gaji Bulanan
        </div>
      </div>
      
      <div style="padding: 22px;">
        <form action="{{ route('payroll.generate') }}" method="POST" id="form-generate">
          @csrf

          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Pertashop / Outlet</label>
            <select name="shop_id" id="gen-shop" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required onchange="this.form.submit()">
              @foreach($shops as $shop)
                <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-row">
            <div class="form-group col-6 mb-3">
              <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Bulan Periode</label>
              <select name="bulan" id="gen-bulan" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required onchange="checkFinalStatus()">
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
            
            <div class="form-group col-6 mb-3">
              <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Tahun</label>
              <select name="tahun" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required>
                @foreach($availableYears as $y)
                  <option value="{{ $y }}" {{ $selectedTahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- Alerts Status Periode --}}
          <div id="final-alert" class="alert alert-danger py-2 small d-none" style="border-radius: 10px; font-weight: 600;">
            <i class="fas fa-lock mr-1"></i> Periode sudah Final dan terkunci, tidak bisa dihitung ulang.
          </div>
          <div id="draft-alert" class="alert alert-warning py-2 small d-none" style="border-radius: 10px; font-weight: 600;">
            <i class="fas fa-info-circle mr-1"></i> Sudah ada draft untuk bulan ini. Menghitung ulang akan menimpa data draft lama.
          </div>

          {{-- Komponen Tambahan / Potongan Dinamis --}}
          <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span style="font-size: 12px; font-weight: 700; color: #1e293b;">
                <i class="fas fa-plus-circle text-primary mr-1"></i> Komponen Tambahan (Opsional)
              </span>
              <button type="button" class="btn btn-xs btn-slate-outline btn-add-custom-row" style="padding: 3px 8px; font-size: 11px; border-radius: 6px;">
                + Tambah Baris
              </button>
            </div>
            <div id="custom-items-container-index">
              {{-- Baris akan ditambahkan via JavaScript --}}
            </div>
            <small class="text-muted d-block mt-1" style="font-size: 11px;">
              Tambahkan bonus insidental atau potongan khusus bulan ini.
            </small>
          </div>

          <p class="text-muted small mb-3">
            <i class="fas fa-info-circle mr-1 text-primary"></i> Sistem otomatis mengagregasi volume penjualan liter dan rekap kehadiran operator.
          </p>

          <button type="submit" id="btn-submit-gen" class="btn btn-block btn-emerald-primary font-weight-bold" style="border-radius: 10px; padding: 12px;">
            <i class="fas fa-bolt mr-1"></i> Hitung Gaji Bulan Ini
          </button>
        </form>

        <hr class="my-3" style="border-color: #f1f5f9;">

        <form action="{{ route('payroll.bulk-generate') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memproses bulk generate gaji untuk SELURUH outlet pada bulan dan tahun terpilih? Outlet berstatus Final akan otomatis dilewati.')">
          @csrf
          <input type="hidden" name="bulan" value="{{ now()->month }}">
          <input type="hidden" name="tahun" value="{{ $selectedTahun }}">
          <button type="submit" class="btn btn-block btn-slate-outline font-weight-bold" style="border-radius: 10px; padding: 10px; font-size: 13px;">
            <i class="fas fa-layer-group mr-1"></i> Hitung Seluruh Outlet Sekaligus (Draft)
          </button>
        </form>
      </div>
    </div>

    {{-- Shortcut ke Data Master --}}
    <div class="payroll-card">
      <div class="payroll-card-header">
        <div class="font-weight-bold" style="font-size: 14px; color: #0f172a;">
          <i class="fas fa-cog text-muted mr-1"></i>Setup Data Master
        </div>
      </div>
      <div style="padding: 16px;">
        <a href="{{ route('payroll-systems.index') }}" class="btn btn-block btn-slate-outline d-flex align-items-center justify-content-between text-decoration-none mb-2" style="font-size: 13px; padding: 10px 14px;">
          <span><i class="fas fa-sliders-h text-success mr-2"></i>Master Sistem Penggajian</span>
          <i class="fas fa-chevron-right text-muted" style="font-size: 11px;"></i>
        </a>
        <a href="{{ route('payroll-operator-assignments.index') }}" class="btn btn-block btn-slate-outline d-flex align-items-center justify-content-between text-decoration-none" style="font-size: 13px; padding: 10px 14px;">
          <span><i class="fas fa-user-tag text-primary mr-2"></i>Kelola Penugasan Operator</span>
          <i class="fas fa-chevron-right text-muted" style="font-size: 11px;"></i>
        </a>
      </div>
    </div>
  </div>

  {{-- ===== KOLOM KANAN (62%): DAFTAR PERIODE RIWAYAT ===== --}}
  <div class="col-lg-7">

    <div class="payroll-card">
      
      {{-- Header dengan Filter Terintegrasi --}}
      <div class="payroll-card-header">
        <div class="font-weight-bold" style="font-size: 16px; color: #0f172a;">
          <i class="fas fa-history mr-2 text-primary"></i>Riwayat Penggajian — {{ $selectedTahun }}
        </div>

        {{-- Filter Inline Tahun & Outlet --}}
        <form method="GET" class="d-flex align-items-center flex-wrap" style="gap: 8px;">
          <select name="tahun" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; width: 90px;" onchange="this.form.submit()">
            @foreach($availableYears as $y)
              <option value="{{ $y }}" {{ $selectedTahun == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
          </select>

          <select name="shop_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; min-width: 140px;" onchange="this.form.submit()">
            @foreach($shops as $shop)
              <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
            @endforeach
          </select>
        </form>
      </div>

      <div style="padding: 0;">
        @if($periods->isEmpty())
          <div class="text-center py-5 px-3">
            <div style="width: 56px; height: 56px; border-radius: 16px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; color: #94a3b8; font-size: 24px;">
              <i class="fas fa-receipt"></i>
            </div>
            <div style="font-weight: 700; color: #334155; font-size: 15px;">Belum Ada Riwayat Penggajian</div>
            <div class="text-muted small mt-1">
              Belum ada data penggajian untuk tahun {{ $selectedTahun }}.<br>
              Gunakan formulir di sebelah kiri untuk generate gaji pertama.
            </div>
          </div>
        @else
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead style="background: #f8fafc;">
              <tr>
                <th style="font-size: 11px; text-transform: uppercase; color: #64748b; padding: 12px 16px;">Periode</th>
                <th style="font-size: 11px; text-transform: uppercase; color: #64748b;">Pertashop</th>
                <th style="font-size: 11px; text-transform: uppercase; color: #64748b;">Total Liter</th>
                <th style="font-size: 11px; text-transform: uppercase; color: #64748b;">Operator</th>
                <th style="font-size: 11px; text-transform: uppercase; color: #64748b;">Status</th>
                <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #64748b; width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($periods as $period)
              <tr>
                <td style="font-size: 13.5px; font-weight: 700; color: #0f172a; padding: 12px 16px;">
                  {{ $period->periode_label }}
                </td>
                <td style="font-size: 13px;">
                  <span class="badge" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 4px 8px; border-radius: 6px;">
                    {{ $period->shop->nama }}
                  </span>
                </td>
                <td style="font-size: 13px; font-weight: 600;">
                  {{ number_format($period->total_penjualan_liter, 0, ',', '.') }} L
                </td>
                <td style="font-size: 13px;">{{ $period->details->count() }} orang</td>
                <td>
                  @if($period->isFinal())
                    <span class="badge badge-success px-2 py-1" style="border-radius: 6px;"><i class="fas fa-lock mr-1"></i>Final</span>
                  @else
                    <span class="badge badge-warning px-2 py-1" style="border-radius: 6px;"><i class="fas fa-edit mr-1"></i>Draft</span>
                  @endif
                </td>
                <td class="text-center">
                  <div class="btn-group" role="group">
                    <a href="{{ route('payroll.show', $period->id) }}" class="btn btn-sm btn-outline-info" title="Detail Penggajian" style="border-radius: 6px; padding: 4px 8px;">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('payroll.export-pdf', $period->id) }}" class="btn btn-sm btn-outline-success ml-1" title="Export PDF Slip Gaji" style="border-radius: 6px; padding: 4px 8px;">
                      <i class="fas fa-file-pdf"></i>
                    </a>
                    @if($period->isDraft())
                    <button class="btn btn-sm btn-outline-danger ml-1 btn-delete-period" data-id="{{ $period->id }}" title="Hapus Draft" style="border-radius: 6px; padding: 4px 8px;">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                    @endif
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
function checkFinalStatus() {
  const sel       = document.getElementById('gen-bulan');
  if (!sel) return;
  const opt       = sel.options[sel.selectedIndex];
  const status    = opt ? opt.dataset.status : '';
  const btnGen    = document.getElementById('btn-submit-gen');
  const alertFin  = document.getElementById('final-alert');
  const alertDraf = document.getElementById('draft-alert');

  if (alertFin) alertFin.classList.add('d-none');
  if (alertDraf) alertDraf.classList.add('d-none');
  
  if (btnGen) {
    btnGen.disabled = false;
    btnGen.style.opacity = '1';
  }

  if (status === 'final') {
    if (alertFin) alertFin.classList.remove('d-none');
    if (btnGen) {
      btnGen.disabled = true;
      btnGen.style.opacity = '0.6';
    }
  } else if (status === 'draft') {
    if (alertDraf) alertDraf.classList.remove('d-none');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  checkFinalStatus();

  // Hapus Draft Period (Event Delegation)
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete-period');
    if (!btn) return;
    e.preventDefault();

    const id = btn.dataset.id;
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Hapus Draft Penggajian?',
        text: 'Data draft yang belum difinalisasi akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        cancelButtonText: 'Batal',
        confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus Draft'
      }).then(result => {
        if (result.isConfirmed) {
          executeDeletePayroll(id, token);
        }
      });
    } else {
      if (confirm('Hapus draft penggajian ini?')) {
        executeDeletePayroll(id, token);
      }
    }
  });
});

function executeDeletePayroll(id, token) {
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
      alert(data.error || data.message || 'Gagal menghapus draft.');
    } else {
      location.reload();
    }
  })
  .catch(err => {
    alert('Terjadi kesalahan sistem: ' + err.message);
  });
}

// ─── Dynamic Custom Items Row Handler ──────────────────────────────────────
let customRowIndex = 0;

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-add-custom-row');
  if (!btn) return;
  e.preventDefault();

  const container = document.getElementById('custom-items-container-index');
  if (!container) return;

  const idx = customRowIndex++;
  const rowHtml = `
    <div class="custom-item-row p-2 mb-2 border rounded" style="background:#ffffff; font-size:12px; border-color:#e2e8f0!important;" id="custom-row-${idx}">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <select name="custom_items[${idx}][tipe]" class="form-control form-control-sm py-0 px-2 font-weight-bold" style="width: auto; height: 26px; font-size: 11px;">
          <option value="tambahan">+ Pendapatan Tambahan</option>
          <option value="potongan">− Potongan Lain</option>
        </select>
        <button type="button" class="btn btn-xs text-danger btn-remove-custom-row p-0 ml-2" style="font-weight:bold; font-size:16px; line-height:1;" title="Hapus baris">&times;</button>
      </div>
      <div class="form-row">
        <div class="col-7 pr-1">
          <input type="text" name="custom_items[${idx}][nama_item]" class="form-control form-control-sm" placeholder="Keterangan (ex: Bonus Target)" style="font-size:11.5px; border-radius:6px;" required>
        </div>
        <div class="col-5 pl-1">
          <input type="number" name="custom_items[${idx}][jumlah]" class="form-control form-control-sm" placeholder="Nominal (Rp)" style="font-size:11.5px; border-radius:6px;" min="0" step="1000" required>
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
