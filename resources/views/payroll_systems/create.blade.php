@extends('layouts._new_admin')
@section('title', 'Tambah Sistem Penggajian')

@push('style')
<style>
  .scheme-selector-card {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #ffffff;
    position: relative;
    height: 100%;
  }
  .scheme-selector-card:hover {
    border-color: #94a3b8;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.05);
  }
  .scheme-selector-card.active {
    border-color: #059669;
    background: #f0fdf4;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.15);
  }
  .scheme-selector-card input[type="radio"] {
    position: absolute;
    top: 16px;
    right: 16px;
    accent-color: #059669;
    width: 18px;
    height: 18px;
  }
  .scheme-badge-pill {
    display: inline-block;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 6px;
    margin-top: 6px;
  }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1" style="font-size: 24px; font-weight: 900; color: #0f172a;">Tambah Sistem Penggajian Baru</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Konfigurasi formula kompensasi (Komisi Murni, Gaji Pokok Murni, atau Hibrid) berdasarkan cabang penempatan</p>
  </div>
  <a href="{{ route('payroll-systems.index') }}" class="btn btn-secondary btn-sm" style="border-radius: 8px; font-weight: 600;">
    <i class="fas fa-arrow-left mr-1"></i> Kembali
  </a>
</div>

<div class="content">
  <div class="container-fluid px-0">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm" style="border-radius: 16px; border: 1px solid #e2e8f0;">
          <div class="card-header bg-white py-3" style="border-bottom: 1px solid #f1f5f9;">
            <h5 class="mb-0 font-weight-bold" style="color: #0f172a;">
              <i class="fas fa-sliders-h text-success mr-2"></i>Form Pengaturan Skema Penggajian
            </h5>
          </div>
          <div class="card-body p-4">

            @if($errors->any())
              <div class="alert alert-danger" style="border-radius: 10px;">
                <ul class="mb-0">
                  @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
              </div>
            @endif

            <form action="{{ route('payroll-systems.store') }}" method="POST" id="form-payroll-system">
              @csrf

              {{-- 1. PILIH CABANG PERTASHOP --}}
              <div class="form-group mb-4">
                <label class="font-weight-bold" style="font-size: 13px; color: #334155;">
                  Pertashop / Cabang Penempatan <span class="text-danger">*</span>
                </label>
                <select name="shop_id" id="shop_id_select" class="form-control form-control-lg font-weight-bold" required onchange="handleShopChange(this.value)">
                  <option value="">— Pilih Cabang Pertashop —</option>
                  @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" 
                            data-nama="{{ $shop->nama }}"
                            data-default-scheme="{{ $shop->getDefaultPayrollScheme() }}"
                            {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                      {{ $shop->nama }}
                    </option>
                  @endforeach
                </select>
                <small class="form-text text-muted">Sistem otomatis merekomendasikan skema hitung default saat cabang dipilih.</small>
              </div>

              {{-- 2. NAMA SISTEM --}}
              <div class="form-group mb-4">
                <label class="font-weight-bold" style="font-size: 13px; color: #334155;">
                  Nama Pengaturan Sistem <span class="text-danger">*</span>
                </label>
                <input type="text" name="nama_sistem" id="nama_sistem_input" class="form-control" 
                  value="{{ old('nama_sistem') }}" placeholder="Contoh: Sistem Penggajian Kalitapen" required>
                <small class="form-text text-muted">Label identifikasi sistem penggajian.</small>
              </div>

              {{-- 3. PEMILIHAN TIPE SKEMA PENGGAJIAN (3 PILIHAN) --}}
              <div class="form-group mb-4">
                <label class="font-weight-bold d-block" style="font-size: 13px; color: #334155;">
                  Tipe Skema Penggajian <span class="text-danger">*</span>
                </label>
                <div class="row">
                  {{-- Opsi 1: Komisi Murni --}}
                  <div class="col-md-4 mb-3">
                    <div class="scheme-selector-card {{ old('tipe_skema', 'komisi_murni') === 'komisi_murni' ? 'active' : '' }}" 
                         id="card-komisi_murni" onclick="selectScheme('komisi_murni')">
                      <input type="radio" name="tipe_skema" value="komisi_murni" id="radio-komisi_murni"
                             {{ old('tipe_skema', 'komisi_murni') === 'komisi_murni' ? 'checked' : '' }}>
                      <div class="d-flex align-items-center mb-2">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 10px;">
                          <i class="fas fa-gas-pump"></i>
                        </div>
                        <div>
                          <div class="font-weight-bold" style="font-size: 14px; color: #0f172a;">Komisi Murni</div>
                          <div style="font-size: 11px; color: #64748b;">Tanpa Gaji Pokok</div>
                        </div>
                      </div>
                      <p style="font-size: 12px; color: #475569; margin-bottom: 8px;">
                        Rumus: <strong>Total Liter Penjualan × Tarif/Liter</strong>.
                      </p>
                      <span class="scheme-badge-pill" style="background: #e0f2fe; color: #0369a1;">
                        Cabang: Kalitapen, Kalibenda, Pageralang, Kemutug
                      </span>
                    </div>
                  </div>

                  {{-- Opsi 2: Gaji Pokok Murni --}}
                  <div class="col-md-4 mb-3">
                    <div class="scheme-selector-card {{ old('tipe_skema') === 'gaji_pokok_murni' ? 'active' : '' }}" 
                         id="card-gaji_pokok_murni" onclick="selectScheme('gaji_pokok_murni')">
                      <input type="radio" name="tipe_skema" value="gaji_pokok_murni" id="radio-gaji_pokok_murni"
                             {{ old('tipe_skema') === 'gaji_pokok_murni' ? 'checked' : '' }}>
                      <div class="d-flex align-items-center mb-2">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #f3e8ff; color: #7e22ce; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 10px;">
                          <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div>
                          <div class="font-weight-bold" style="font-size: 14px; color: #0f172a;">Gaji Pokok Murni</div>
                          <div style="font-size: 11px; color: #64748b;">Tanpa Komisi Liter</div>
                        </div>
                      </div>
                      <p style="font-size: 12px; color: #475569; margin-bottom: 8px;">
                        Rumus: <strong>Nominal Gaji Pokok</strong> flat bulanan.
                      </p>
                      <span class="scheme-badge-pill" style="background: #f3e8ff; color: #6b21a8;">
                        Cabang: Gumelar
                      </span>
                    </div>
                  </div>

                  {{-- Opsi 3: Hibrid --}}
                  <div class="col-md-4 mb-3">
                    <div class="scheme-selector-card {{ old('tipe_skema') === 'hibrid' ? 'active' : '' }}" 
                         id="card-hibrid" onclick="selectScheme('hibrid')">
                      <input type="radio" name="tipe_skema" value="hibrid" id="radio-hibrid"
                             {{ old('tipe_skema') === 'hibrid' ? 'checked' : '' }}>
                      <div class="d-flex align-items-center mb-2">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 10px;">
                          <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                          <div class="font-weight-bold" style="font-size: 14px; color: #0f172a;">Sistem Hibrid</div>
                          <div style="font-size: 11px; color: #64748b;">Gaji Pokok + Komisi</div>
                        </div>
                      </div>
                      <p style="font-size: 12px; color: #475569; margin-bottom: 8px;">
                        Rumus: <strong>Gaji Pokok + (Total Liter × Tarif/Liter)</strong>.
                      </p>
                      <span class="scheme-badge-pill" style="background: #dcfce7; color: #166534;">
                        Cabang: Sumingkir
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {{-- 4. NOMINAL PENGGAJIAN (FLEKSIBEL INPUT) --}}
              <div class="p-3 mb-4" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                <h6 class="font-weight-bold mb-3" style="color: #0f172a;">
                  <i class="fas fa-coins text-warning mr-1"></i> Parameter Nominal &amp; Tarif Komisi
                </h6>

                <div class="form-row">
                  {{-- Field Rate Komisi per Liter --}}
                  <div class="form-group col-md-6" id="wrap-rate-liter">
                    <label class="font-weight-bold" style="font-size: 12.5px;">Tarif Komisi per Liter (Rp)</label>
                    <div class="input-group">
                      <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                      <input type="number" name="rate_per_liter" id="input_rate_per_liter" class="form-control font-weight-bold" 
                             value="{{ old('rate_per_liter', 200) }}" min="0" step="1">
                    </div>
                    <small class="form-text text-muted">Contoh: 200 = Rp 200 per liter penjualan.</small>
                  </div>

                  {{-- Field Gaji Pokok --}}
                  <div class="form-group col-md-6" id="wrap-gaji-pokok">
                    <label class="font-weight-bold" style="font-size: 12.5px;">Nominal Gaji Pokok (Rp)</label>
                    <div class="input-group">
                      <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                      <input type="number" name="nominal_gaji_pokok" id="input_nominal_gaji_pokok" class="form-control font-weight-bold" 
                             value="{{ old('nominal_gaji_pokok', 1500000) }}" min="0" step="1000">
                    </div>
                    <small class="form-text text-muted">Nominal gaji pokok per bulan.</small>
                  </div>
                </div>

                <div class="form-row">
                  {{-- Field Uang Transport per Hari --}}
                  <div class="form-group col-md-6">
                    <label class="font-weight-bold" style="font-size: 12.5px;">Uang Transport per Hari Hadir (Rp)</label>
                    <div class="input-group">
                      <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                      <input type="number" name="rate_transport_per_hari" class="form-control" 
                             value="{{ old('rate_transport_per_hari', 0) }}" min="0" step="1000">
                    </div>
                    <small class="form-text text-muted">Dikalikan jumlah hari hadir kerja (0 jika tidak ada transport).</small>
                  </div>

                  {{-- Field Potongan Alpha --}}
                  <div class="form-group col-md-6">
                    <label class="font-weight-bold" style="font-size: 12.5px;">Potongan Alpha per Hari (Rp)</label>
                    <div class="input-group">
                      <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                      <input type="number" name="potongan_per_hari_alpha" class="form-control" 
                             value="{{ old('potongan_per_hari_alpha', 0) }}" min="0" step="1000">
                    </div>
                    <small class="form-text text-muted">Nominal potongan jika menggunakan metode nominal tetap.</small>
                  </div>
                </div>

                {{-- Metode Potongan Alpha --}}
                <div class="form-group mb-0">
                  <label class="font-weight-bold" style="font-size: 12.5px;">Metode Potongan Absensi / Alpha <span class="text-danger">*</span></label>
                  <select name="metode_potongan_alpha" class="form-control" required>
                    <option value="nominal_tetap" {{ old('metode_potongan_alpha', 'nominal_tetap') === 'nominal_tetap' ? 'selected' : '' }}>
                      Nominal Tetap (Pakai input Potongan Alpha per Hari di atas)
                    </option>
                    <option value="prorata_gaji_pokok" {{ old('metode_potongan_alpha') === 'prorata_gaji_pokok' ? 'selected' : '' }}>
                      Prorata Gaji Pokok Dinamis: (Gaji Pokok + Komisi) / Standar Hari Kerja
                    </option>
                  </select>
                </div>
              </div>

              {{-- 5. ATURAN SPLIT & LOSSES/GAIN --}}
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label class="font-weight-bold" style="font-size: 12.5px;">Perlakuan Losses/Gain <span class="text-danger">*</span></label>
                  <select name="perlakuan_losses_gain" class="form-control" required>
                    <option value="losses_only" {{ old('perlakuan_losses_gain', 'losses_only') === 'losses_only' ? 'selected' : '' }}>
                      Losses Saja (Gain Diabaikan)
                    </option>
                    <option value="losses_dan_gain" {{ old('perlakuan_losses_gain') === 'losses_dan_gain' ? 'selected' : '' }}>
                      Losses &amp; Gain (Plus/Minus)
                    </option>
                    <option value="abaikan_losses_gain" {{ old('perlakuan_losses_gain') === 'abaikan_losses_gain' ? 'selected' : '' }}>
                      Abaikan Losses/Gain Sama Sekali
                    </option>
                  </select>
                </div>

                <div class="form-group col-md-6">
                  <label class="font-weight-bold" style="font-size: 12.5px;">Metode Pembagian Antar Operator <span class="text-danger">*</span></label>
                  <select name="metode_split" class="form-control" required onchange="toggleStandarHariKerja(this)">
                    <option value="per_hari_penuh" {{ old('metode_split', 'per_hari_penuh') === 'per_hari_penuh' ? 'selected' : '' }}>
                      Satu Hari Penuh (Shift Berdasarkan Kehadiran Riil)
                    </option>
                    <option value="proporsional_jam_kerja" {{ old('metode_split') === 'proporsional_jam_kerja' ? 'selected' : '' }}>
                      Proporsional Jam Kerja per Shift
                    </option>
                    <option value="flat_bulanan_prorata_hari" {{ old('metode_split') === 'flat_bulanan_prorata_hari' ? 'selected' : '' }}>
                      Flat Bulanan Prorata Hari Kerja
                    </option>
                  </select>
                </div>
              </div>

              <div class="form-group" id="standar-hari-wrap" style="{{ old('metode_split') === 'flat_bulanan_prorata_hari' ? '' : 'display:none;' }}">
                <label class="font-weight-bold" style="font-size: 12.5px;">Standar Hari Kerja per Bulan (Hari)</label>
                <input type="number" name="standar_hari_kerja" class="form-control"
                  value="{{ old('standar_hari_kerja', 26) }}" min="1" max="31">
                <small class="form-text text-muted">Digunakan sebagai pembagi tarif harian prorata (default 26 hari).</small>
              </div>

              <div class="form-group mb-4">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="aktif"
                    name="aktif" value="1" {{ old('aktif', true) ? 'checked' : '' }}>
                  <label class="custom-control-label font-weight-bold" for="aktif">Status Aktif Sebagai Sistem Penggajian Toko</label>
                </div>
              </div>

              <div class="d-flex align-items-center pt-3 border-top">
                <button type="submit" class="btn btn-success px-4 py-2" style="border-radius: 10px; font-weight: 700;">
                  <i class="fas fa-save mr-1"></i> Simpan Sistem Penggajian
                </button>
                <a href="{{ route('payroll-systems.index') }}" class="btn btn-outline-secondary ml-2 px-4 py-2" style="border-radius: 10px; font-weight: 600;">
                  Batal
                </a>
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
function selectScheme(scheme) {
  // Update Radio
  document.querySelectorAll('input[name="tipe_skema"]').forEach(r => {
    r.checked = (r.value === scheme);
  });

  // Update Card Visuals
  document.querySelectorAll('.scheme-selector-card').forEach(c => {
    c.classList.remove('active');
  });
  const activeCard = document.getElementById('card-' + scheme);
  if (activeCard) activeCard.classList.add('active');

  // Show / Hide Input Fields
  const wrapRate = document.getElementById('wrap-rate-liter');
  const wrapGaji = document.getElementById('wrap-gaji-pokok');

  if (scheme === 'komisi_murni') {
    wrapRate.style.display = '';
    wrapGaji.style.display = 'none';
  } else if (scheme === 'gaji_pokok_murni') {
    wrapRate.style.display = 'none';
    wrapGaji.style.display = '';
  } else if (scheme === 'hibrid') {
    wrapRate.style.display = '';
    wrapGaji.style.display = '';
  }
}

function handleShopChange(shopId) {
  if (!shopId) return;
  const select = document.getElementById('shop_id_select');
  const selectedOption = select.options[select.selectedIndex];
  const shopName = selectedOption.getAttribute('data-nama') || '';
  const defaultScheme = selectedOption.getAttribute('data-default-scheme') || 'komisi_murni';

  // Auto-fill nama sistem jika kosong atau default
  const namaInput = document.getElementById('nama_sistem_input');
  if (!namaInput.value || namaInput.value.startsWith('Sistem Penggajian')) {
    namaInput.value = 'Sistem Penggajian ' + shopName;
  }

  // Pilih skema otomatis
  selectScheme(defaultScheme);

  // Ambil parameter rekomendasi via AJAX jika tersedia
  fetch('{{ url("payroll-systems/defaults-by-shop") }}/' + shopId)
    .then(res => res.json())
    .then(data => {
      if (data) {
        if (data.rate_per_liter !== undefined) {
          document.getElementById('input_rate_per_liter').value = data.rate_per_liter;
        }
        if (data.nominal_gaji_pokok !== undefined && data.nominal_gaji_pokok > 0) {
          document.getElementById('input_nominal_gaji_pokok').value = data.nominal_gaji_pokok;
        }
      }
    })
    .catch(() => {});
}

function toggleStandarHariKerja(el) {
  const wrap = document.getElementById('standar-hari-wrap');
  wrap.style.display = el.value === 'flat_bulanan_prorata_hari' ? '' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
  const currentScheme = document.querySelector('input[name="tipe_skema"]:checked')?.value || 'komisi_murni';
  selectScheme(currentScheme);
});
</script>
@endpush
