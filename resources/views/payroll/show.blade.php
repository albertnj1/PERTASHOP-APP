@extends('layouts._new_admin')
@section('title', 'Detail Penggajian — ' . $payroll->periode_label)

@push('style')
<style>
  .payroll-table th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em; white-space: nowrap; }
  .payroll-table td { font-size: 0.85rem; vertical-align: middle; }
  .editable-cell {
    border-bottom: 2px dashed var(--green);
    cursor: pointer;
    min-width: 80px;
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    transition: background .15s;
  }
  .editable-cell:hover { background: #dcfce7; }
  .editable-cell.editing { display: none; }
  .edit-input { width: 110px; }
  .thp-cell { font-weight: 800; color: var(--green); font-size: 0.9rem; }
  .section-label { font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">
      Penggajian — {{ $payroll->periode_label }}
      <span class="ml-2">
        @if($payroll->isFinal())
          <span class="status-pill" style="background:#dcfce7; color:#15803d;">Final</span>
        @else
          <span class="status-pill" style="background:#fef3c7; color:#b45309;">Draft</span>
        @endif
      </span>
    </h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Ringkasan dan detail komputasi gaji operator</p>
  </div>
  <div>
    <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px; font-weight: 600;">
      Kembali
    </a>
    <a href="{{ route('payroll.export-pdf', $payroll->id) }}" class="btn btn-outline-primary btn-sm ml-1" style="border-radius: 8px; font-weight: 600;">
      Export PDF
    </a>
    @if($payroll->isDraft())
    <form action="{{ route('payroll.finalize', $payroll->id) }}" method="POST" class="d-inline ml-1">
      @csrf
      <button type="submit" class="btn btn-success btn-sm" style="border-radius: 8px; font-weight: 600;" id="btn-finalize">
        Finalisasi
      </button>
    </form>
    @endif
  </div>
</div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif

    @if($payroll->isDraft())
    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i>
      <strong>Mode Draft:</strong> Kolom yang bergaris bawah hijau bisa langsung diklik untuk diedit.
      THP akan terhitung ulang otomatis. Klik <strong>Finalisasi</strong> untuk mengunci data.
    </div>
    @else
    <div class="alert alert-success">
      <i class="fas fa-lock"></i>
      <strong>Data sudah difinalisasi</strong> oleh {{ $payroll->generatedBy?->name ?? '-' }}
      pada {{ $payroll->generated_at?->format('d M Y, H:i') ?? '-' }}.
    </div>
    @endif

    {{-- ===== INFO SISTEM ===== --}}
    <div class="row mb-3">
      <div class="col-md-4">
        <div class="card summary-card">
          <div class="card-body py-2">
            <div class="section-label">Toko</div>
            <div class="font-weight-bold">{{ $payroll->shop->nama }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card summary-card">
          <div class="card-body py-2">
            <div class="section-label">Sistem Penggajian</div>
            <div class="font-weight-bold">{{ $payroll->payrollSystem->nama_sistem }}</div>
            <div class="small text-muted">
              @if($payroll->payrollSystem->ada_rate_per_liter)
                Rate: <strong>Rp {{ number_format($payroll->payrollSystem->rate_per_liter, 0, ',', '.') }}/L</strong>
              @else
                Rate per Liter: <strong>Nonaktif</strong>
              @endif
              • {{ $payroll->payrollSystem->metode_split_label }}
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card summary-card">
          <div class="card-body py-2">
            <div class="section-label">Total Volume Bulan Ini</div>
            <div class="font-weight-bold">{{ number_format($payroll->total_penjualan_liter, 2, ',', '.') }} Liter</div>
            <div class="small text-muted">{{ $payroll->details->count() }} operator aktif</div>
          </div>
        </div>
      </div>
    </div>

    {{-- ===== TABEL UTAMA PENGGAJIAN ===== --}}
    <div class="panel mb-4">
      <div class="panel-head">
        <div>
          <div class="panel-title">Rincian Gaji per Operator</div>
          @if($payroll->isDraft())
          <small class="text-muted" style="font-size: 12px;">Klik nilai bergaris putus-putus untuk edit langsung</small>
          @endif
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover payroll-table mb-0" style="min-width:1100px;">
          <thead>
            <tr style="background: var(--page-bg);">
              <th rowspan="2" class="align-middle" style="color: var(--muted);">Operator</th>
              <th rowspan="2" class="align-middle text-center" style="color: var(--muted);">Hari Kerja</th>
              <th rowspan="2" class="align-middle text-right" style="color: var(--muted);">Liter Bagian</th>
              <th colspan="6" class="text-center" style="background:#e7f3ec; color:#15803d; font-weight:700;">+ Pendapatan</th>
              <th colspan="6" class="text-center" style="background:#fee2e2; color:#b91c1c; font-weight:700;">− Potongan</th>
              <th rowspan="2" class="align-middle text-right" style="background:#fef3c7; color:#b45309; font-weight:700;">THP</th>
            </tr>
            <tr style="background: var(--page-bg);">
              <th class="text-right" style="color: var(--muted);">Gaji Pokok</th>
              <th class="text-right" style="color: var(--muted);">Gaji Variable</th>
              <th class="text-right" style="color: var(--muted);">Lembur + HR</th>
              <th class="text-right" style="color: var(--muted);">Bonus</th>
              <th class="text-right" style="color: var(--muted);">THR</th>
              <th class="text-right" style="color: #15803d; background: #f0fdf4;">Komponen Tambahan (+)</th>
              <th class="text-right" style="color: var(--muted);">Alpha</th>
              <th class="text-right" style="color: var(--muted);">Kurang Setor</th>
              <th class="text-right" style="color: var(--muted);">Tab. Gaji</th>
              <th class="text-right" style="color: var(--muted);">Tab. Setoran</th>
              <th class="text-right" style="color: var(--muted);">Hutang</th>
              <th class="text-right" style="color: #b91c1c; background: #fef2f2;">Potongan Lain (−)</th>
            </tr>
          </thead>
          <tbody>
            @foreach($payroll->details as $detail)
            <tr id="row-detail-{{ $detail->id }}">
              <td>
                <strong>{{ $detail->operator->user?->name ?? '-' }}</strong>
              </td>
              <td class="text-center">{{ $detail->total_hari_kerja }}</td>
              <td class="text-right">{{ number_format($detail->liter_bagian, 2, ',', '.') }} L</td>

              {{-- Gaji Pokok (readonly) --}}
              <td class="text-right">
                @if($detail->gaji_pokok > 0)
                  Rp {{ number_format($detail->gaji_pokok, 0, ',', '.') }}
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>

              {{-- Gaji Variable (readonly) --}}
              <td class="text-right">
                <strong>Rp {{ number_format($detail->gaji_variable, 0, ',', '.') }}</strong>
              </td>

              {{-- Lembur (editable) --}}
              <td class="text-right">
                @if($payroll->isDraft())
                  @php $lemburTotal = floatval($detail->lembur) + floatval($detail->lembur_hari_raya); @endphp
                  <span class="editable-cell" data-detail="{{ $detail->id }}" data-field="lembur"
                    onclick="startEdit(this)">
                    Rp {{ number_format($lemburTotal, 0, ',', '.') }}
                  </span>
                  <div class="edit-wrapper d-none" id="edit-lembur-{{ $detail->id }}">
                    <div class="small text-muted mb-1">Lembur biasa:</div>
                    <input type="number" class="form-control form-control-sm edit-input mb-1"
                      id="inp-lembur-{{ $detail->id }}" value="{{ $detail->lembur }}" min="0" step="1000">
                    <div class="small text-muted mb-1">Lembur hari raya:</div>
                    <input type="number" class="form-control form-control-sm edit-input mb-1"
                      id="inp-lembur-hr-{{ $detail->id }}" value="{{ $detail->lembur_hari_raya }}" min="0" step="1000">
                    <button class="btn btn-xs btn-success btn-save-field" data-detail="{{ $detail->id }}"
                      data-fields="lembur,lembur_hari_raya">✓</button>
                    <button class="btn btn-xs btn-secondary btn-cancel-field" data-detail="{{ $detail->id }}" data-field="lembur">✕</button>
                  </div>
                @else
                  Rp {{ number_format(floatval($detail->lembur) + floatval($detail->lembur_hari_raya), 0, ',', '.') }}
                @endif
              </td>

              {{-- Bonus (editable) --}}
              <td class="text-right">
                @if($payroll->isDraft())
                  <span class="editable-cell" data-detail="{{ $detail->id }}" data-field="bonus" onclick="startEdit(this)">
                    Rp {{ number_format($detail->bonus, 0, ',', '.') }}
                  </span>
                  <div class="edit-wrapper d-none" id="edit-bonus-{{ $detail->id }}">
                    <input type="number" class="form-control form-control-sm edit-input"
                      id="inp-bonus-{{ $detail->id }}" value="{{ $detail->bonus }}" min="0" step="1000">
                    <button class="btn btn-xs btn-success btn-save-field mt-1" data-detail="{{ $detail->id }}" data-fields="bonus">✓</button>
                    <button class="btn btn-xs btn-secondary btn-cancel-field mt-1" data-detail="{{ $detail->id }}" data-field="bonus">✕</button>
                  </div>
                @else
                  Rp {{ number_format($detail->bonus, 0, ',', '.') }}
                @endif
              </td>

              {{-- THR (editable) --}}
              <td class="text-right">
                @if($payroll->isDraft())
                  <span class="editable-cell" data-detail="{{ $detail->id }}" data-field="thr" onclick="startEdit(this)">
                    Rp {{ number_format($detail->thr, 0, ',', '.') }}
                  </span>
                  <div class="edit-wrapper d-none" id="edit-thr-{{ $detail->id }}">
                    <input type="number" class="form-control form-control-sm edit-input"
                      id="inp-thr-{{ $detail->id }}" value="{{ $detail->thr }}" min="0" step="1000">
                    <button class="btn btn-xs btn-success btn-save-field mt-1" data-detail="{{ $detail->id }}" data-fields="thr">✓</button>
                    <button class="btn btn-xs btn-secondary btn-cancel-field mt-1" data-detail="{{ $detail->id }}" data-field="thr">✕</button>
                  </div>
                @else
                  Rp {{ number_format($detail->thr, 0, ',', '.') }}
                @endif
              </td>

              {{-- Komponen Tambahan (+) (Dinamis) --}}
              <td class="text-right" id="items-tambahan-{{ $detail->id }}" style="background:#f0fdf4;">
                <div class="d-flex flex-column align-items-end" style="gap:4px;">
                  @foreach($detail->items->where('tipe', 'tambahan') as $item)
                    <span class="badge badge-success d-inline-flex align-items-center" style="font-size:11px; padding: 4px 8px; border-radius: 6px; font-weight: 600;" id="item-badge-{{ $item->id }}">
                      {{ $item->nama_item }}: Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                      @if($payroll->isDraft())
                      <button type="button" class="btn btn-xs text-white p-0 ml-2 btn-delete-item" data-detail="{{ $detail->id }}" data-item="{{ $item->id }}" style="line-height:1; font-weight:bold;">&times;</button>
                      @endif
                    </span>
                  @endforeach
                  @if($payroll->isDraft())
                  <button type="button" class="btn btn-xs btn-outline-success mt-1 btn-open-item-modal" data-detail="{{ $detail->id }}" data-operator="{{ $detail->operator->user?->name ?? 'Operator' }}" data-type="tambahan" style="border-radius: 6px; font-weight: 600; font-size: 11px;">
                    + Tambah
                  </button>
                  @endif
                </div>
              </td>

              {{-- Potongan Tidak Masuk (editable) --}}
              <td class="text-right text-danger">
                @if($payroll->isDraft())
                  <span class="editable-cell" data-detail="{{ $detail->id }}" data-field="potongan_tidak_masuk" onclick="startEdit(this)">
                    Rp {{ number_format($detail->potongan_tidak_masuk, 0, ',', '.') }}
                  </span>
                  <div class="edit-wrapper d-none" id="edit-potongan_tidak_masuk-{{ $detail->id }}">
                    <input type="number" class="form-control form-control-sm edit-input"
                      id="inp-potongan_tidak_masuk-{{ $detail->id }}" value="{{ $detail->potongan_tidak_masuk }}" min="0" step="1000">
                    <button class="btn btn-xs btn-success btn-save-field mt-1" data-detail="{{ $detail->id }}" data-fields="potongan_tidak_masuk">✓</button>
                    <button class="btn btn-xs btn-secondary btn-cancel-field mt-1" data-detail="{{ $detail->id }}" data-field="potongan_tidak_masuk">✕</button>
                  </div>
                @else
                  Rp {{ number_format($detail->potongan_tidak_masuk, 0, ',', '.') }}
                @endif
              </td>

              {{-- Kurang Setoran (editable + informative clarification context) --}}
              <td class="text-right text-danger">
                @if($payroll->isDraft())
                  <span class="editable-cell" data-detail="{{ $detail->id }}" data-field="kurang_setoran" onclick="startEdit(this)">
                    Rp {{ number_format($detail->kurang_setoran, 0, ',', '.') }}
                  </span>
                  <div class="edit-wrapper d-none" id="edit-kurang_setoran-{{ $detail->id }}">
                    <input type="number" class="form-control form-control-sm edit-input"
                      id="inp-kurang_setoran-{{ $detail->id }}" value="{{ $detail->kurang_setoran }}" min="0" step="1000">
                    <button class="btn btn-xs btn-success btn-save-field mt-1" data-detail="{{ $detail->id }}" data-fields="kurang_setoran">✓</button>
                    <button class="btn btn-xs btn-secondary btn-cancel-field mt-1" data-detail="{{ $detail->id }}" data-field="kurang_setoran">✕</button>
                  </div>
                @else
                  Rp {{ number_format($detail->kurang_setoran, 0, ',', '.') }}
                @endif
              </td>

              {{-- Tabungan Gaji (editable) --}}
              <td class="text-right text-danger">
                @if($payroll->isDraft())
                  <span class="editable-cell" data-detail="{{ $detail->id }}" data-field="tabungan_gaji" onclick="startEdit(this)">
                    Rp {{ number_format($detail->tabungan_gaji, 0, ',', '.') }}
                  </span>
                  <div class="edit-wrapper d-none" id="edit-tabungan_gaji-{{ $detail->id }}">
                    <input type="number" class="form-control form-control-sm edit-input"
                      id="inp-tabungan_gaji-{{ $detail->id }}" value="{{ $detail->tabungan_gaji }}" min="0" step="1000">
                    <button class="btn btn-xs btn-success btn-save-field mt-1" data-detail="{{ $detail->id }}" data-fields="tabungan_gaji">✓</button>
                    <button class="btn btn-xs btn-secondary btn-cancel-field mt-1" data-detail="{{ $detail->id }}" data-field="tabungan_gaji">✕</button>
                  </div>
                @else
                  Rp {{ number_format($detail->tabungan_gaji, 0, ',', '.') }}
                @endif
              </td>

              {{-- Tabungan Setoran (editable) --}}
              <td class="text-right text-danger">
                @if($payroll->isDraft())
                  <span class="editable-cell" data-detail="{{ $detail->id }}" data-field="tabungan_setoran" onclick="startEdit(this)">
                    Rp {{ number_format($detail->tabungan_setoran, 0, ',', '.') }}
                  </span>
                  <div class="edit-wrapper d-none" id="edit-tabungan_setoran-{{ $detail->id }}">
                    <input type="number" class="form-control form-control-sm edit-input"
                      id="inp-tabungan_setoran-{{ $detail->id }}" value="{{ $detail->tabungan_setoran }}" min="0" step="1000">
                    <button class="btn btn-xs btn-success btn-save-field mt-1" data-detail="{{ $detail->id }}" data-fields="tabungan_setoran">✓</button>
                    <button class="btn btn-xs btn-secondary btn-cancel-field mt-1" data-detail="{{ $detail->id }}" data-field="tabungan_setoran">✕</button>
                  </div>
                @else
                  Rp {{ number_format($detail->tabungan_setoran, 0, ',', '.') }}
                @endif
              </td>

              {{-- Potongan Hutang (editable) --}}
              <td class="text-right text-danger">
                @if($payroll->isDraft())
                  <span class="editable-cell" data-detail="{{ $detail->id }}" data-field="potongan_hutang" onclick="startEdit(this)">
                    Rp {{ number_format($detail->potongan_hutang, 0, ',', '.') }}
                  </span>
                  <div class="edit-wrapper d-none" id="edit-potongan_hutang-{{ $detail->id }}">
                    <input type="number" class="form-control form-control-sm edit-input"
                      id="inp-potongan_hutang-{{ $detail->id }}" value="{{ $detail->potongan_hutang }}" min="0" step="1000">
                    <button class="btn btn-xs btn-success btn-save-field mt-1" data-detail="{{ $detail->id }}" data-fields="potongan_hutang">✓</button>
                    <button class="btn btn-xs btn-secondary btn-cancel-field mt-1" data-detail="{{ $detail->id }}" data-field="potongan_hutang">✕</button>
                  </div>
                @else
                  Rp {{ number_format($detail->potongan_hutang, 0, ',', '.') }}
                @endif
              </td>

              {{-- Potongan Lain (−) (Dinamis) --}}
              <td class="text-right" id="items-potongan-{{ $detail->id }}" style="background:#fef2f2;">
                <div class="d-flex flex-column align-items-end" style="gap:4px;">
                  @foreach($detail->items->where('tipe', 'potongan') as $item)
                    <span class="badge badge-danger d-inline-flex align-items-center" style="font-size:11px; padding: 4px 8px; border-radius: 6px; font-weight: 600;" id="item-badge-{{ $item->id }}">
                      {{ $item->nama_item }}: Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                      @if($payroll->isDraft())
                      <button type="button" class="btn btn-xs text-white p-0 ml-2 btn-delete-item" data-detail="{{ $detail->id }}" data-item="{{ $item->id }}" style="line-height:1; font-weight:bold;">&times;</button>
                      @endif
                    </span>
                  @endforeach
                  @if($payroll->isDraft())
                  <button type="button" class="btn btn-xs btn-outline-danger mt-1 btn-open-item-modal" data-detail="{{ $detail->id }}" data-operator="{{ $detail->operator->user?->name ?? 'Operator' }}" data-type="potongan" style="border-radius: 6px; font-weight: 600; font-size: 11px;">
                    + Tambah
                  </button>
                  @endif
                </div>
              </td>

              {{-- THP --}}
              <td class="text-right thp-cell" id="thp-{{ $detail->id }}" style="background:#f0fff4;">
                Rp {{ number_format($detail->take_home_pay, 0, ',', '.') }}
              </td>
            </tr>
            @endforeach
          </tbody>
          <tfoot class="thead-light">
            <tr>
              <td colspan="4" class="text-right font-weight-bold">Total</td>
              <td class="text-right font-weight-bold">
                Rp {{ number_format($payroll->details->sum('gaji_variable'), 0, ',', '.') }}
              </td>
              <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
              <td class="text-right font-weight-bold" style="background:#f0fff4;">
                Rp {{ number_format($payroll->details->sum('take_home_pay'), 0, ',', '.') }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    {{-- ===== RINCIAN HARIAN ===== --}}
    <div class="panel mb-4">
      <div class="panel-head">
        <div class="panel-title">Rincian Harian (Audit Trail)</div>
      </div>
      <div class="card-body p-0">
        <div style="max-height: 350px; overflow-y:auto;">
          <table class="table table-sm table-bordered mb-0" style="font-size:0.78rem;">
            <thead class="thead-light" style="position:sticky;top:0;z-index:1;">
              <tr>
                <th>Tanggal</th>
                <th>Operator</th>
                <th class="text-right">Vol. Aktual (L)</th>
                <th class="text-right">Vol. Dihitung (L)</th>
                <th class="text-right">Liter Bagian</th>
                <th class="text-center">Proporsi</th>
                <th>Sumber</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              @foreach($payroll->dailySplits->sortBy('tanggal') as $split)
              <tr>
                <td>{{ $split->tanggal->format('d M') }}</td>
                <td>{{ $split->operator->user?->name ?? '-' }}</td>
                <td class="text-right">{{ number_format($split->volume_penjualan_aktual, 3, ',', '.') }}</td>
                <td class="text-right">{{ number_format($split->volume_dihitung, 3, ',', '.') }}</td>
                <td class="text-right"><strong>{{ number_format($split->liter_bagian, 3, ',', '.') }}</strong></td>
                <td class="text-center">{{ number_format($split->proporsi * 100, 1) }}%</td>
                <td>
                  @if($split->sumber === 'manual')
                    <span class="badge badge-warning">Manual</span>
                  @else
                    <span class="badge badge-secondary">Otomatis</span>
                  @endif
                </td>
                <td class="text-muted">{{ $split->keterangan ?? '-' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('script')
<script>
const PAYROLL_ID = {{ $payroll->id }};
const IS_FINAL   = {{ $payroll->isFinal() ? 'true' : 'false' }};

function startEdit(el) {
  if (IS_FINAL) return;
  const field    = el.dataset.field;
  const detailId = el.dataset.detail;
  el.classList.add('editing');
  el.style.display = 'none';
  document.getElementById(`edit-${field}-${detailId}`).classList.remove('d-none');
}

function cancelEdit(detailId, field) {
  const wrapper = document.getElementById(`edit-${field}-${detailId}`);
  wrapper.classList.add('d-none');
  // Restore span
  document.querySelector(`.editable-cell[data-detail="${detailId}"][data-field="${field}"]`).style.display = '';
}

function saveField(detailId, fields) {
  const payload = {};
  fields.split(',').forEach(f => {
    const inp = document.getElementById(`inp-${f.trim()}-${detailId}`);
    if (inp) payload[f.trim()] = parseFloat(inp.value) || 0;
  });

  fetch(`/payroll/${PAYROLL_ID}/detail/${detailId}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(data => {
    if (data.error) {
      Swal.fire('Gagal', data.error, 'error');
      return;
    }

    // Update THP display
    const thp = Math.round(data.take_home_pay);
    document.getElementById(`thp-${detailId}`).textContent = 'Rp ' + thp.toLocaleString('id-ID');

    // Update semua editable cell yang terdampak
    if (fields.includes('lembur')) {
      const totalLembur = (payload.lembur || 0) + (payload.lembur_hari_raya || 0);
      const span = document.querySelector(`.editable-cell[data-detail="${detailId}"][data-field="lembur"]`);
      if (span) span.textContent = 'Rp ' + Math.round(totalLembur).toLocaleString('id-ID');
    } else {
      fields.split(',').forEach(f => {
        f = f.trim();
        const span = document.querySelector(`.editable-cell[data-detail="${detailId}"][data-field="${f}"]`);
        if (span) span.textContent = 'Rp ' + Math.round(payload[f] || 0).toLocaleString('id-ID');
      });
    }

    // Sembunyikan form edit
    const firstField = fields.split(',')[0].trim();
    cancelEdit(detailId, firstField);
  })
  .catch(() => Swal.fire('Error', 'Gagal menyimpan data.', 'error'));
}

// Attach save buttons
document.querySelectorAll('.btn-save-field').forEach(btn => {
  btn.addEventListener('click', function() {
    saveField(this.dataset.detail, this.dataset.fields);
  });
});

// Attach cancel buttons
document.querySelectorAll('.btn-cancel-field').forEach(btn => {
  btn.addEventListener('click', function() {
    cancelEdit(this.dataset.detail, this.dataset.field);
  });
});

// ─── Modal Tambah Komponen Tambahan (Keterangan + Nominal) ────────────────
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-open-item-modal');
  if (!btn) return;
  e.preventDefault();

  const detailId = btn.dataset.detail;
  const operatorName = btn.dataset.operator;
  const type = btn.dataset.type; // 'tambahan' atau 'potongan'
  const typeLabel = type === 'tambahan' ? 'Pendapatan Tambahan (+)' : 'Potongan Lain (−)';
  const badgeClass = type === 'tambahan' ? 'badge-success' : 'badge-danger';

  Swal.fire({
    title: `Tambah ${typeLabel}`,
    html: `
      <div class="text-left mb-3">
        <label class="small font-weight-bold mb-1">Operator:</label>
        <input type="text" class="form-control form-control-sm" value="${operatorName}" readonly style="background:#f1f5f9;">
      </div>
      <div class="text-left mb-3">
        <label class="small font-weight-bold mb-1">1. Keterangan / Nama Komponen <span class="text-danger">*</span></label>
        <input type="text" id="swal-nama-item" class="form-control form-control-sm" placeholder="Contoh: Bonus Penjualan Oli / Pinjaman Kas" required>
      </div>
      <div class="text-left mb-2">
        <label class="small font-weight-bold mb-1">2. Nominal (Rp) <span class="text-danger">*</span></label>
        <input type="number" id="swal-jumlah-item" class="form-control form-control-sm" placeholder="Contoh: 50000" min="0" step="1000" required>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Simpan Komponen',
    cancelButtonText: 'Batal',
    confirmButtonColor: type === 'tambahan' ? '#166534' : '#991b1b',
    preConfirm: () => {
      const namaItem = document.getElementById('swal-nama-item').value.trim();
      const jumlah   = document.getElementById('swal-jumlah-item').value;
      if (!namaItem) {
        Swal.showValidationMessage('Silakan isi Keterangan / Nama Komponen.');
        return false;
      }
      if (!jumlah || parseFloat(jumlah) <= 0) {
        Swal.showValidationMessage('Silakan isi Nominal yang valid (> 0).');
        return false;
      }
      return { tipe: type, nama_item: namaItem, jumlah: parseFloat(jumlah) };
    }
  }).then(result => {
    if (result.isConfirmed) {
      const payload = result.value;
      const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

      fetch(`/payroll/${PAYROLL_ID}/detail/${detailId}/items`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token
        },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          Swal.fire('Gagal', data.error, 'error');
          return;
        }

        // Update THP
        const thp = Math.round(data.take_home_pay);
        document.getElementById(`thp-${detailId}`).textContent = 'Rp ' + thp.toLocaleString('id-ID');

        // Append Badge ke container cell
        const container = document.querySelector(`#items-${type}-${detailId} .d-flex`);
        if (container) {
          const newBadge = document.createElement('span');
          newBadge.className = `badge ${badgeClass} d-inline-flex align-items-center`;
          newBadge.style.cssText = 'font-size:11px; padding: 4px 8px; border-radius: 6px; font-weight: 600;';
          newBadge.id = `item-badge-${data.item.id}`;
          newBadge.innerHTML = `
            ${data.item.nama_item}: Rp ${Math.round(data.item.jumlah).toLocaleString('id-ID')}
            <button type="button" class="btn btn-xs text-white p-0 ml-2 btn-delete-item" data-detail="${detailId}" data-item="${data.item.id}" style="line-height:1; font-weight:bold;">&times;</button>
          `;
          container.insertBefore(newBadge, btn);
        }

        Swal.fire({
          title: 'Berhasil!',
          text: 'Komponen tambahan berhasil disimpan.',
          icon: 'success',
          timer: 1200,
          showConfirmButton: false
        });
      })
      .catch(() => Swal.fire('Error', 'Gagal menyimpan komponen.', 'error'));
    }
  });
});

// ─── Hapus Komponen Tambahan Dinamis ──────────────────────────────────────
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn-delete-item');
  if (!btn) return;
  e.preventDefault();

  const detailId = btn.dataset.detail;
  const itemId   = btn.dataset.item;
  const token    = document.querySelector('meta[name="csrf-token"]')?.content || '';

  Swal.fire({
    title: 'Hapus Komponen Ini?',
    text: 'Komponen tambahan ini akan dihapus dari perhitungan gaji.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonText: 'Batal',
    confirmButtonText: 'Ya, Hapus'
  }).then(result => {
    if (result.isConfirmed) {
      fetch(`/payroll/${PAYROLL_ID}/detail/${detailId}/items/${itemId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token
        }
      })
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          Swal.fire('Gagal', data.error, 'error');
          return;
        }

        // Hapus elemen badge
        const badge = document.getElementById(`item-badge-${itemId}`);
        if (badge) badge.remove();

        // Update THP
        const thp = Math.round(data.take_home_pay);
        document.getElementById(`thp-${detailId}`).textContent = 'Rp ' + thp.toLocaleString('id-ID');
      })
      .catch(() => Swal.fire('Error', 'Gagal menghapus komponen.', 'error'));
    }
  });
});

// Konfirmasi finalisasi
const btnFinalize = document.getElementById('btn-finalize');
if (btnFinalize) {
  btnFinalize.closest('form').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    Swal.fire({
      title: 'Finalisasi Penggajian?',
      text: 'Data yang sudah difinalisasi tidak bisa diedit lagi. Pastikan semua komponen sudah benar.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Finalisasi!',
      cancelButtonText: 'Cek Dulu'
    }).then(result => {
      if (result.isConfirmed) form.submit();
    });
  });
}
</script>
@endpush
