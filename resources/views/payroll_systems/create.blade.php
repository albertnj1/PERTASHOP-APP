@extends('layouts._new_admin')
@section('title', 'Tambah Sistem Penggajian')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Tambah Sistem Penggajian Baru</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Konfigurasi skema kompensasi dan aturan potongan untuk outlet Pertashop</p>
  </div>
  <a href="{{ route('payroll-systems.index') }}" class="btn btn-secondary btn-sm">
    <i class="fas fa-arrow-left"></i> Kembali
  </a>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Form Sistem Penggajian Baru</h5>
          </div>
          <div class="card-body">

            @if($errors->any())
              <div class="alert alert-danger">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
              </div>
            @endif

            <form action="{{ route('payroll-systems.store') }}" method="POST">
              @csrf

              <div class="form-group">
                <label class="font-weight-bold">Pertashop <span class="text-danger">*</span></label>
                <select name="shop_id" class="form-control" required>
                  <option value="">— Pilih Toko —</option>
                  @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group">
                <label class="font-weight-bold">Nama Sistem <span class="text-danger">*</span></label>
                <input type="text" name="nama_sistem" class="form-control" value="{{ old('nama_sistem') }}"
                  placeholder="Contoh: Sistem Per-Liter Standar" required>
                <small class="form-text text-muted">Label bebas, hanya untuk identifikasi.</small>
              </div>

              <div class="form-group">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="ada_rate_per_liter"
                    name="ada_rate_per_liter" value="1" {{ old('ada_rate_per_liter', '1') ? 'checked' : '' }}
                    onchange="toggleRatePerLiter(this)">
                  <label class="custom-control-label font-weight-bold" for="ada_rate_per_liter">Ada Rate per Liter?</label>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6" id="rate-per-liter-wrap" style="{{ old('ada_rate_per_liter', '1') ? '' : 'display:none;' }}">
                  <label class="font-weight-bold">Rate per Liter (Rp)</label>
                  <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                    <input type="number" name="rate_per_liter" class="form-control" value="{{ old('rate_per_liter', 200) }}"
                      min="0" step="1">
                  </div>
                </div>
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">Potongan Alpha per Hari (Rp)</label>
                  <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                    <input type="number" name="potongan_per_hari_alpha" class="form-control"
                      value="{{ old('potongan_per_hari_alpha', 0) }}" min="0" step="1">
                  </div>
                  <small class="form-text text-muted">0 = tidak ada potongan alpha.</small>
                </div>
              </div>

              <div class="form-group">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="ada_gaji_pokok"
                    name="ada_gaji_pokok" value="1" {{ old('ada_gaji_pokok') ? 'checked' : '' }}
                    onchange="toggleGajiPokok(this)">
                  <label class="custom-control-label font-weight-bold" for="ada_gaji_pokok">Ada Gaji Pokok?</label>
                </div>
              </div>

              <div class="form-group" id="nominal-gaji-pokok-wrap" style="{{ old('ada_gaji_pokok') ? '' : 'display:none;' }}">
                <label class="font-weight-bold">Nominal Gaji Pokok (Rp)</label>
                <div class="input-group">
                  <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                  <input type="number" name="nominal_gaji_pokok" class="form-control"
                    value="{{ old('nominal_gaji_pokok') }}" min="0" step="1000">
                </div>
              </div>

              <div class="form-group">
                <label class="font-weight-bold">Perlakuan Losses/Gain <span class="text-danger">*</span></label>
                <select name="perlakuan_losses_gain" class="form-control" required>
                  <option value="losses_only" {{ old('perlakuan_losses_gain', 'losses_only') === 'losses_only' ? 'selected' : '' }}>
                    Losses Saja (Gain Diabaikan) — seperti Kalitapen
                  </option>
                  <option value="losses_dan_gain" {{ old('perlakuan_losses_gain') === 'losses_dan_gain' ? 'selected' : '' }}>
                    Losses & Gain (Plus/Minus)
                  </option>
                  <option value="abaikan_losses_gain" {{ old('perlakuan_losses_gain') === 'abaikan_losses_gain' ? 'selected' : '' }}>
                    Abaikan Losses/Gain Sama Sekali
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="font-weight-bold">Metode Pembagian Antar Operator <span class="text-danger">*</span></label>
                <select name="metode_split" class="form-control" required onchange="toggleStandarHariKerja(this)">
                  <option value="per_hari_penuh" {{ old('metode_split', 'per_hari_penuh') === 'per_hari_penuh' ? 'selected' : '' }}>
                    Satu Hari Penuh
                  </option>
                  <option value="proporsional_jam_kerja" {{ old('metode_split') === 'proporsional_jam_kerja' ? 'selected' : '' }}>
                    Proporsional per Shift
                  </option>
                  <option value="flat_bulanan_prorata_hari" {{ old('metode_split') === 'flat_bulanan_prorata_hari' ? 'selected' : '' }}>
                    Flat Bulanan Prorata Hari Kerja
                  </option>
                </select>
              </div>

              <div class="form-group" id="standar-hari-wrap" style="{{ old('metode_split') === 'flat_bulanan_prorata_hari' ? '' : 'display:none;' }}">
                <label class="font-weight-bold">Standar Hari Kerja (Hari)</label>
                <input type="number" name="standar_hari_kerja" class="form-control"
                  value="{{ old('standar_hari_kerja', 26) }}" min="1" max="31">
                <small class="form-text text-muted">Contoh: 26 hari. Gaji harian = Gaji Pokok / Standar Hari Kerja.</small>
              </div>

              <div class="form-group">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="aktif"
                    name="aktif" value="1" {{ old('aktif', true) ? 'checked' : '' }}>
                  <label class="custom-control-label font-weight-bold" for="aktif">Status Aktif</label>
                </div>
              </div>

              <div class="mt-3">
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-save"></i> Simpan Sistem Penggajian
                </button>
                <a href="{{ route('payroll-systems.index') }}" class="btn btn-secondary ml-2">Batal</a>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('script')
<script>
function toggleRatePerLiter(el) {
  const wrap = document.getElementById('rate-per-liter-wrap');
  wrap.style.display = el.checked ? '' : 'none';
  if (!el.checked) {
    document.querySelector('[name="rate_per_liter"]').value = '0';
  }
}

function toggleStandarHariKerja(el) {
  const wrap = document.getElementById('standar-hari-wrap');
  wrap.style.display = el.value === 'flat_bulanan_prorata_hari' ? '' : 'none';
}

function toggleGajiPokok(el) {
  const wrap = document.getElementById('nominal-gaji-pokok-wrap');
  wrap.style.display = el.checked ? '' : 'none';
  if (!el.checked) {
    document.querySelector('[name="nominal_gaji_pokok"]').value = '';
  }
}
</script>
@endpush
