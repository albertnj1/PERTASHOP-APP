@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Tambah Laporan Harian</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('daily-reports.index') }}">Laporan Harian</a></li>
                        <li class="breadcrumb-item active">Tambah</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <div class=" d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Laporan Harian</h3>
                        <div>
                            <span id="draft-indicator" class="badge badge-secondary" style="display:none;">💾 Draft tersimpan</span>
                            <button type="button" id="btn-clear-draft" class="btn btn-xs btn-outline-danger ml-2" style="display:none;" onclick="clearDraft()">🗑 Hapus Draft</button>
                        </div>
                    </div>

                </div>
                <form id="insertForm" action="{{ route('daily-reports.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation"
                    novalidate>
                    @csrf
                    <div class="card-body">

                        <div class="form-group row">
                            <label for="tanggal" class="col-sm-4 col-form-label">Tanggal</label>
                            <div class="col-sm-8">
                                <input type="date" class="form-control @error('tanggal') is-invalid @enderror"
                                    id="tanggal" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" readonly>
                                @error('tanggal')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Perubahan Harga BBM</label>
                            <div class="col-sm-8">
                                <div class="card card-warning card-outline mb-0">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center" style="cursor:pointer;" onclick="toggleHargaSection()">
                                        <h3 class="card-title text-sm m-0 text-dark">Data Perubahan Harga Hari Ini
                                            @if(isset($todayPriceChanges) && $todayPriceChanges->count() > 0)
                                                <span class="badge badge-warning ml-2">{{ $todayPriceChanges->count() }} perubahan</span>
                                            @endif
                                        </h3>
                                        <span id="harga-section-toggle-icon">{{ (isset($todayPriceChanges) && $todayPriceChanges->count() > 0) ? '▲' : '▼' }}</span>
                                    </div>
                                    <div class="card-body p-3" id="harga-section-body" style="display:{{ (isset($todayPriceChanges) && $todayPriceChanges->count() > 0) ? 'block' : 'none' }};">
                                        @if(isset($todayPriceChanges) && $todayPriceChanges->count() > 0)
                                            <div class="alert alert-info py-2 px-3 mb-3">
                                                <i class="fas fa-info-circle mr-1"></i> 
                                                <strong>Perhatian:</strong> Terdapat perubahan harga pada hari ini:
                                                <ul class="mb-0 mt-1 pl-3">
                                                    @foreach($todayPriceChanges as $change)
                                                        <li>Tanggal {{ \Carbon\Carbon::parse($change->effective_at)->isoFormat('DD MMMM YYYY') }}, Hari {{ \Carbon\Carbon::parse($change->effective_at)->isoFormat('dddd') }}, Jam {{ \Carbon\Carbon::parse($change->effective_at)->format('H:i:s') }} (Harga Baru: Rp {{ number_format($change->harga_jual, 0, ',', '.') }}, Totalisator: {{ number_format($change->totalisator_perubahan, 3, ',', '.') }})</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @else
                                            <p class="text-muted text-sm mb-0">Tidak ada data perubahan harga BBM hari ini.</p>
                                        @endif
                                        <div id="price-changes-container">
                                            <!-- Dynamic Price Change Rows will go here -->
                                        </div>
                                        <small class="text-muted d-block mt-2">Data perubahan harga ini otomatis diambil dari entri menu Perubahan Harga yang dilakukan hari ini.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="totalisator_awal" class="col-sm-4 col-form-label">Totalisator Awal</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="totalisator_awal" name="totalisator_awal"
                                        value="{{ number_format($totalisator_awal, 3, '.', '') }}" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="form-group row">
                            <label for="totalisator_akhir" class="col-sm-4 col-form-label">Totalisator Akhir</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="0.001" inputmode="decimal"
                                        class="form-control @error('totalisator_akhir') is-invalid @enderror"
                                        id="totalisator_akhir" name="totalisator_akhir"
                                        value="{{ old('totalisator_akhir') }}" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                                @error('totalisator_akhir')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="test_pump" class="col-sm-4 col-form-label">Test Pump</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="0.001" inputmode="decimal" class="form-control" id="test_pump" name="test_pump_volume"
                                        value="{{ old('test_pump_volume', 0) }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="volume_penjualan" class="col-sm-4 col-form-label">Volume Penjualan</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="0.001" inputmode="decimal" class="form-control" id="volume_penjualan" name="volume_penjualan"
                                        value="{{ old('volume_penjualan') }}" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="harga" class="col-sm-4 col-form-label">Harga per Liter</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" class="form-control" id="harga" value="{{ $harga }}"
                                        readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="rupiah_penjualan" class="col-sm-4 col-form-label">Rupiah Penjualan</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" class="form-control" id="rupiah_penjualan"
                                        value="{{ old('rupiah_penjualan') }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="stik_awal" class="col-sm-4 col-form-label">Stik Awal</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="stik_awal" name="stik_awal"
                                        value="{{ number_format($stik_awal, 3, '.', '') }}" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="stok_awal" class="col-sm-4 col-form-label">Stok Awal</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="stok_awal" name="stok_awal"
                                        value="{{ number_format($stik_awal * $shop->skala, 3, '.', '') }}" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="penerimaan" class="col-sm-4 col-form-label">Penerimaan</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="0.01" inputmode="decimal" class="form-control" id="penerimaan" name="penerimaan_volume"
                                        value="{{ old('penerimaan_volume', 0) }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                                @if($pendingPurchases->count() > 0)
                                    <div class="alert alert-info py-2 mt-2 mb-0">
                                        <strong>📝 Ada Pembelian (SO) yang belum diterima!</strong><br>
                                        @foreach($pendingPurchases as $p)
                                            <div class="d-flex align-items-center mt-2">
                                                <div class="form-check mr-3">
                                                    <input class="form-check-input pending-so-check" type="checkbox" name="received_purchases_ids[]" value="{{ $p->id }}" data-target="#vol_{{ $p->id }}" id="so_{{ $p->id }}">
                                                    <label class="form-check-label" style="font-size: 0.9rem;" for="so_{{ $p->id }}">
                                                        SO: <strong>{{ $p->no_so }}</strong> (Sisa: {{ number_format($p->sisa, 0, ',', '.') }} L)
                                                    </label>
                                                </div>
                                                <div class="input-group input-group-sm" style="width: 150px; display: none;" id="wrap_vol_{{ $p->id }}">
                                                    <input type="number" step="0.01" class="form-control so-volume-input" name="received_purchases_volumes[{{ $p->id }}]" id="vol_{{ $p->id }}" value="{{ $p->sisa }}" max="{{ $p->sisa }}" placeholder="Volume Diterima">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">&ell;</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                        <small class="text-muted d-block mt-2">Centang SO lalu sesuaikan volumenya (misal dari 6.000 L, baru diterima 2.000 L). Total penerimaan di atas otomatis menyesuaikan.</small>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Rincian Pengeluaran Hari Ini</label>
                            <div class="col-sm-8">
                                <div class="card card-info card-outline">
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Ongkos Bongkar</label>
                                                <input type="number" name="spendings[1]" class="form-control form-control-sm spending-input" value="0">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Biaya Transfer</label>
                                                <input type="number" name="spendings[2]" class="form-control form-control-sm spending-input" value="0">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Fotocopy & ATK</label>
                                                <input type="number" name="spendings[3]" class="form-control form-control-sm spending-input" value="0">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Listrik</label>
                                                <input type="number" name="spendings[4]" class="form-control form-control-sm spending-input" value="0">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Air Bersih</label>
                                                <input type="number" name="spendings[5]" class="form-control form-control-sm spending-input" value="0">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Cashback</label>
                                                <input type="number" name="spendings[6]" class="form-control form-control-sm spending-input" value="0">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Internet</label>
                                                <input type="number" name="spendings[7]" class="form-control form-control-sm spending-input" value="0">
                                            </div>
                                            <div class="col-md-12 mb-2">
                                                <label class="text-xs">Lain-lain</label>
                                                <div class="row">
                                                    <div class="col-md-7">
                                                        <input type="text" name="spending_lain_ket" class="form-control form-control-sm" placeholder="Keterangan">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="number" name="spending_lain_nom" class="form-control form-control-sm spending-input" value="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="stok_akhir_teoritis" class="col-sm-4 col-form-label">Stok Akhir Teoritis</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="stok_akhir_teoritis"
                                        name="stok_akhir_teoritis" value="{{ old('stok_akhir_teoritis') }}" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="stik_akhir" class="col-sm-4 col-form-label">Stik Akhir</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="0.01" inputmode="decimal" class="form-control @error('stik_akhir') is-invalid @enderror"
                                        id="stik_akhir" name="stik_akhir" value="{{ old('stik_akhir') }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                </div>
                                @error('stik_akhir')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="foto_stik" class="col-sm-4 col-form-label">Foto Bukti Stik Tangki</label>
                            <div class="col-sm-8">
                                <input type="file" class="form-control-file @error('foto_stik') is-invalid @enderror"
                                    id="foto_stik" name="foto_stik" accept="image/*" capture="environment">
                                <small class="text-muted">📷 Ambil foto stik dari kamera HP atau upload file sebagai bukti fisik.</small>
                                @error('foto_stik')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="stok_akhir_aktual" class="col-sm-4 col-form-label">Stok Akhir Aktual</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number"
                                        class="form-control @error('stok_akhir_aktual') is-invalid @enderror"
                                        id="stok_akhir_aktual" name="stok_akhir_aktual"
                                        value="{{ old('stok_akhir_aktual') }}" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                                @error('stok_akhir_aktual')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                        <div class="form-group row">
                            <label for="losses_gain" class="col-sm-4 col-form-label">Gain/Losses</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control @error('losses_gain') is-invalid @enderror"
                                        id="losses_gain" name="losses_gain" value="{{ old('losses_gain') }}" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                                @error('losses_gain')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>

                        <div class="form-group row">
                            <label for="pengeluaran" class="col-sm-4 col-form-label">Pengeluaran</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" id="pengeluaran" name="pengeluaran"
                                        value="{{ old('pengeluaran') }}" readonly>
                                </div>
                            </div>

                        </div>

                        <div class="form-group row">
                            <label for="pendapatan" class="col-sm-4 col-form-label">Total Pendapatan</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" class="form-control" id="pendapatan" name="pendapatan"
                                        value="{{ old('pendapatan') }}" readonly>
                                </div>
                            </div>

                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Rincian Setoran Hari Ini</label>
                            <div class="col-sm-8">
                                <div class="card card-success card-outline">
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">Setoran Mandiri (Cash)</label>
                                                <input type="number" name="setor_tunai" id="setor_tunai" class="form-control form-control-sm setoran-input" value="0">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">Transfer QR Mandiri</label>
                                                <input type="number" name="setor_qris" id="setor_qris" class="form-control form-control-sm setoran-input" value="0">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">Transfer Operator</label>
                                                <input type="number" name="setor_transfer" id="setor_transfer" class="form-control form-control-sm setoran-input" value="0">
                                            </div>
                                        </div>
                                        <hr class="mt-2 mb-3">
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">Setoran ke Kolektan</label>
                                                <select name="kolektan_id" class="form-control form-control-sm">
                                                    <option value="">-- Pilih Kolektan --</option>
                                                    @foreach($kolektans as $kolektan)
                                                        <option value="{{ $kolektan->id }}" {{ old('kolektan_id') == $kolektan->id ? 'selected' : '' }}>{{ $kolektan->nama_kolektan }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">Nominal Setor Kolektan</label>
                                                <input type="number" name="setor_kolektan" id="setor_kolektan" class="form-control form-control-sm setoran-input" value="{{ old('setor_kolektan', 0) }}">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">PIN Kolektan</label>
                                                <input type="password" name="kolektan_pin" id="kolektan_pin" class="form-control form-control-sm @error('kolektan_pin') is-invalid @enderror" placeholder="Ketik PIN jika ada setoran">
                                                @error('kolektan_pin')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="disetorkan" class="col-sm-4 col-form-label">Disetorkan</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" id="disetorkan" name="disetorkan" value="0" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="selisih_setoran" class="col-sm-4 col-form-label">Selisih Setoran</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" id="selisih_setoran"
                                        name="selisih_setoran" value="{{ old('selisih_setoran') }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="belum_disetorkan" class="col-sm-4 col-form-label">Belum Disetorkan</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" id="belum_disetorkan"
                                        name="belum_disetorkan" value="{{ old('belum_disetorkan', $belum_disetorkan) }}"
                                        readonly>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </div>


                </form>
            </div>
        </div>

        {{-- ======== PANEL PREVIEW RINGKASAN REAL-TIME ======== --}}
        <div class="card card-success card-outline mt-3" id="preview-panel" style="position:sticky;bottom:20px;z-index:100;">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ringkasan Sementara <small class="text-muted" style="font-size:0.7rem;">(diperbarui otomatis)</small></h5>
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="$('#preview-body').toggle()">Perkecil</button>
            </div>
            <div class="card-body py-2" id="preview-body">
                <div class="row text-center">
                    <div class="col-6 col-md-3 mb-2">
                        <div class="text-muted" style="font-size:0.7rem;">Volume Terjual</div>
                        <div class="font-weight-bold" id="prev-volume">—</div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="text-muted" style="font-size:0.7rem;">Pendapatan</div>
                        <div class="font-weight-bold text-success" id="prev-pendapatan">—</div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="text-muted" style="font-size:0.7rem;">Losses/Gain</div>
                        <div class="font-weight-bold" id="prev-losses">—</div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="text-muted" style="font-size:0.7rem;">Belum Disetor</div>
                        <div class="font-weight-bold text-danger" id="prev-belum-setor">—</div>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            let changeIndex = 0;
            const baseTotalisatorAwal = {{ $totalisator_awal }};

            function addChangeRow(jam = '', hargaJual = '', totalisator = '') {
                const html = `
                    <div class="row price-change-row mb-3 align-items-end" id="change-row-${changeIndex}">
                        <div class="col-md-3">
                            <label class="text-xs">Jam Berlaku</label>
                            <input type="time" name="price_changes[${changeIndex}][jam]" class="form-control form-control-sm change-jam" value="${jam}" required readonly>
                        </div>
                        <div class="col-md-5">
                            <label class="text-xs">Harga Jual Baru (Rp/Liter)</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="number" name="price_changes[${changeIndex}][harga_jual]" class="form-control change-harga-jual" value="${hargaJual}" required readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-xs">Totalisator Perubahan</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.001" name="price_changes[${changeIndex}][totalisator]" class="form-control change-totalisator" value="${totalisator}" required readonly>
                                <div class="input-group-append"><span class="input-group-text">&ell;</span></div>
                            </div>
                        </div>
                    </div>
                `;

                $('#price-changes-container').append(html);
                changeIndex++;
                updateTotals();
            }

            // Auto-fill price changes from database
            @if(isset($todayPriceChanges) && $todayPriceChanges->count() > 0)
                @foreach($todayPriceChanges as $index => $change)
                    addChangeRow('{{ \Carbon\Carbon::parse($change->effective_at)->format('H:i') }}', '{{ $change->harga_jual }}', '{{ $change->totalisator_perubahan }}');
                @endforeach
                updateTotals();
            @endif

            function updateTotals() {
                const totalisator_awal = baseTotalisatorAwal;
                const totalisator_akhir = parseFloat($('#totalisator_akhir').val()) || 0;
                const test_pump = parseFloat($('#test_pump').val()) || 0;
                const default_harga = parseFloat($('#harga').val()) || 0;

                const rows = $('.price-change-row');
                let validChanges = [];

                rows.each(function(index, element) {
                    const row = $(element);
                    const jam = row.find('.change-jam').val();
                    const hargaJual = parseFloat(row.find('.change-harga-jual').val()) || 0;
                    const totalisator = parseFloat(row.find('.change-totalisator').val()) || 0;

                    if (jam && hargaJual > 0 && totalisator > 0) {
                        validChanges.push({ jam, hargaJual, totalisator });
                    }
                });

                // Sort by totalisator
                validChanges.sort((a, b) => a.totalisator - b.totalisator);

                let volume_penjualan = 0;
                let rupiah_penjualan = 0;

                if (validChanges.length > 0) {
                    let currentAwal = totalisator_awal;
                    let lastHargaJual = default_harga;

                    validChanges.forEach(function(change) {
                        if (change.totalisator > currentAwal && change.totalisator < totalisator_akhir) {
                            const vol = change.totalisator - currentAwal;
                            volume_penjualan += vol;
                            rupiah_penjualan += vol * lastHargaJual;
                            
                            currentAwal = change.totalisator;
                            lastHargaJual = change.hargaJual;
                        }
                    });

                    if (totalisator_akhir > currentAwal) {
                        const vol = totalisator_akhir - currentAwal;
                        volume_penjualan += vol;
                        rupiah_penjualan += vol * lastHargaJual;
                    }

                    // Subtract test pump using last applicable price
                    volume_penjualan = volume_penjualan - test_pump;
                    rupiah_penjualan = rupiah_penjualan - (test_pump * lastHargaJual);
                } else {
                    volume_penjualan = totalisator_akhir - totalisator_awal - test_pump;
                    rupiah_penjualan = volume_penjualan * default_harga;
                }

                $('#volume_penjualan').val(volume_penjualan.toFixed(3));
                $('#rupiah_penjualan').val(rupiah_penjualan.toFixed(2));

                const stok_awal = $('#stok_awal').val() * 1;
                const penerimaan = $('#penerimaan').val() * 1;
                const stok_akhir_teoritis = stok_awal + penerimaan - volume_penjualan;
                $('#stok_akhir_teoritis').val(stok_akhir_teoritis.toFixed(3));

                const stik_akhir = $('#stik_akhir').val() * 1;
                const skala = {{ $shop->skala }};
                const stok_akhir_aktual = stik_akhir * skala;
                $('#stok_akhir_aktual').val(stok_akhir_aktual.toFixed(3));

                const losses_gain = stok_akhir_aktual - stok_akhir_teoritis;
                $('#losses_gain').val(losses_gain.toFixed(3));

                // Recalculate spendings dynamically
                let total_spendings = 0;
                $('.spending-input').each(function() {
                    total_spendings += parseFloat($(this).val()) || 0;
                });
                $('#pengeluaran').val(total_spendings);

                const pendapatan = rupiah_penjualan - total_spendings;
                $('#pendapatan').val(pendapatan);

                // Recalculate setoran breakdown dynamically
                let total_setoran = 0;
                $('.setoran-input').each(function() {
                    total_setoran += parseFloat($(this).val()) || 0;
                });
                $('#disetorkan').val(total_setoran);

                const selisih = total_setoran - pendapatan;
                $('#selisih_setoran').val(selisih);

                const belum_disetorkan_kemarin = {{ $belum_disetorkan }};
                $('#belum_disetorkan').val(belum_disetorkan_kemarin + pendapatan - total_setoran);
            }

            $(document).on('input', '.change-harga-jual, .change-totalisator', function() {
                updateTotals();
            });

            $(document).on('change', '.change-jam', function() {
                updateTotals();
            });

            let basePenerimaan = 0;

            function updatePenerimaan() {
                let extra = 0;
                $('.pending-so-check:checked').each(function() {
                    const targetId = $(this).data('target');
                    const vol = parseFloat($(targetId).val()) || 0;
                    extra += vol;
                });
                $('#penerimaan').val(basePenerimaan + extra);
                updateTotals();
            }

            $('.pending-so-check').on('change', function() {
                const wrapId = '#wrap_' + $(this).data('target').substring(1);
                if ($(this).is(':checked')) {
                    $(wrapId).show();
                } else {
                    $(wrapId).hide();
                }
                updatePenerimaan();
            });

            $('.so-volume-input').on('input', function() {
                updatePenerimaan();
            });

            $('#totalisator_akhir, #stik_akhir, #test_pump, #penerimaan').on('input', function() {
                updateTotals();
                // Update basePenerimaan if user manually changes Penerimaan input
                // without using the SO checkboxes. We'll track it, but it gets tricky.
                // Usually they either use SO checkboxes or manual input.
            });

            $(document).on('input', '.spending-input, .setoran-input', function() {
                updateTotals();
            });

            // Trigger initial calculation
            updateTotals();

            // ============================================================
            // TOGGLE SECTION PERUBAHAN HARGA
            // ============================================================
            window.toggleHargaSection = function() {
                const body = document.getElementById('harga-section-body');
                const icon = document.getElementById('harga-section-toggle-icon');
                if (body.style.display === 'none') {
                    body.style.display = 'block';
                    icon.textContent = '▲';
                } else {
                    body.style.display = 'none';
                    icon.textContent = '▼';
                }
            };

            // ============================================================
            // PREVIEW PANEL UPDATE
            // ============================================================
            function updatePreviewPanel(volumePenjualan, pendapatan, lossesGain, belumDisetor) {
                const fmt = (n) => n.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 3});
                const fmtRp = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');

                document.getElementById('prev-volume').textContent = fmt(volumePenjualan) + ' ℓ';
                document.getElementById('prev-pendapatan').textContent = fmtRp(pendapatan);

                const lossEl = document.getElementById('prev-losses');
                lossEl.textContent = fmt(lossesGain) + ' ℓ';
                lossEl.className = 'font-weight-bold ' + (lossesGain < -5 ? 'text-danger' : lossesGain > 5 ? 'text-warning' : 'text-success');

                document.getElementById('prev-belum-setor').textContent = fmtRp(belumDisetor);
            }

            // Patch updateTotals to also update preview panel
            const origUpdateTotals = updateTotals;
            updateTotals = function() {
                origUpdateTotals();
                const vp = parseFloat($('#volume_penjualan').val()) || 0;
                const pd = parseFloat($('#pendapatan').val()) || 0;
                const lg = parseFloat($('#losses_gain').val()) || 0;
                const bs = parseFloat($('#belum_disetorkan').val()) || 0;
                updatePreviewPanel(vp, pd, lg, bs);
                saveDraft();
            };

            // ============================================================
            // AUTO-SAVE DRAFT ke localStorage
            // ============================================================
            const DRAFT_KEY = 'daily_report_draft_{{ Auth::user()->operator->shop_id ?? 0 }}_{{ Auth::user()->id }}';

            function saveDraft() {
                const data = {};
                document.querySelectorAll('#insertForm input:not([type=hidden]), #insertForm select, #insertForm textarea').forEach(el => {
                    if (el.name && !el.readOnly && el.type !== 'submit') {
                        data[el.name] = el.value;
                    }
                });
                data['_saved_at'] = new Date().toISOString();
                localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
                document.getElementById('draft-indicator').style.display = 'inline-block';
                document.getElementById('btn-clear-draft').style.display = 'inline-block';
            }

            window.clearDraft = function() {
                localStorage.removeItem(DRAFT_KEY);
                document.getElementById('draft-indicator').style.display = 'none';
                document.getElementById('btn-clear-draft').style.display = 'none';
            };

            // Restore draft jika ada
            (function() {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return;
                const draft = JSON.parse(raw);
                const savedAt = draft['_saved_at'] ? new Date(draft['_saved_at']).toLocaleString('id-ID') : '?';
                if (confirm('Ditemukan draft yang belum dikirim (disimpan: ' + savedAt + '). Pulihkan data draft?')) {
                    Object.entries(draft).forEach(([name, val]) => {
                        if (name === '_saved_at') return;
                        const el = document.querySelector('#insertForm [name="' + name + '"]');
                        if (el && !el.readOnly) el.value = val;
                    });
                    updateTotals();
                    document.getElementById('draft-indicator').style.display = 'inline-block';
                    document.getElementById('btn-clear-draft').style.display = 'inline-block';
                } else {
                    clearDraft();
                }
            })();

            // Clear draft saat berhasil submit
            document.getElementById('insertForm').addEventListener('submit', function(e) {
                // ============================================================
                // SWEETALERT KONFIRMASI LOSSES/GAIN TIDAK WAJAR
                // ============================================================
                const lossesGain = parseFloat($('#losses_gain').val()) || 0;
                const THRESHOLD = 50; // Liter

                if (Math.abs(lossesGain) > THRESHOLD) {
                    e.preventDefault();
                    const arah = lossesGain < 0 ? 'Losses' : 'Gain';
                    const warnMsg = `${arah} terdeteksi: ${Math.abs(lossesGain).toFixed(3)} Liter.\n\nNilai ini melebihi batas wajar (${THRESHOLD} L).\nApakah data sudah benar?`;

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '⚠️ ' + arah + ' Tidak Wajar!',
                            html: `<p>${arah} terdeteksi: <strong>${Math.abs(lossesGain).toFixed(3)} Liter</strong>.</p><p>Nilai ini melebihi batas wajar (${THRESHOLD} L). Apakah data sudah benar?</p>`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Lanjutkan Submit',
                            cancelButtonText: 'Periksa Lagi',
                            confirmButtonColor: '#e74c3c',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                clearDraft();
                                document.getElementById('insertForm').submit();
                            }
                        });
                    } else {
                        if (confirm(warnMsg)) {
                            clearDraft();
                            document.getElementById('insertForm').submit();
                        }
                    }
                } else {
                    clearDraft();
                }
            });
        });
    </script>
@endpush
