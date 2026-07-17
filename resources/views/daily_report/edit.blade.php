@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Laporan Harian</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('daily-reports.index') }}">Laporan Harian</a></li>
                        <li class="breadcrumb-item
                                active">Edit</li>
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
                    </div>

                </div>
                <form id="insertForm" action="{{ route('daily-reports.update', $dailyReport->id) }}" method="POST"
                    class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="card-body">

                        <div class="form-group row">
                            <label for="tanggal" class="col-sm-4 col-form-label">Tanggal</label>
                            <div class="col-sm-8">
                                <input type="date" class="form-control @error('tanggal') is-invalid @enderror"
                                    id="tanggal" name="tanggal"
                                    value="{{ old('tanggal', $dailyReport->created_at->format('Y-m-d')) }}" readonly>
                                @error('tanggal')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if (Auth::user()->role != 'operator')
                            <div class="form-group row">
                                <label for="operator" class="col-sm-4 col-form-label">Operator</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="operator" name="operator"
                                        value="{{ $dailyReport->operator->user->name }}" readonly>
                                </div>
                            </div>
                        @endif

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Perubahan Harga BBM</label>
                            <div class="col-sm-8">
                                <div class="card card-warning card-outline">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <h3 class="card-title text-sm m-0 text-dark">Data Perubahan Harga Hari Ini</h3>
                                    </div>
                                    <div class="card-body p-3">
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
                                        value="{{ $dailyReport->totalisator_awal }}" readonly>
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
                                    <input type="number" step="0.001"
                                        class="form-control @error('totalisator_akhir') is-invalid @enderror"
                                        id="totalisator_akhir" name="totalisator_akhir"
                                        value="{{ old('totalisator_akhir', $dailyReport->totalisator_akhir) }}" required>
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
                                    <input type="number" step="0.001" class="form-control" id="test_pump" name="test_pump_volume"
                                        value="{{ old('test_pump_volume', $dailyReport->test_pump_volume) }}">
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
                                    <input type="number" step="0.001" class="form-control" id="volume_penjualan" name="volume_penjualan"
                                        value="{{ old('volume_penjualan', $dailyReport->volume_penjualan) }}" readonly>
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
                                    <input type="text" class="form-control" id="harga"
                                        value="{{ $dailyReport->price ? $dailyReport->price->harga_jual : 0 }}" readonly>
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
                                        value="{{ old('rupiah_penjualan', $dailyReport->rupiah_penjualan) }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="stik_awal" class="col-sm-4 col-form-label">Stik Awal</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="stik_awal" name="stik_awal"
                                        value="{{ $dailyReport->stik_awal }}" readonly>
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
                                        value="{{ $dailyReport->stok_awal }}" readonly>
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
                                    <input type="number" step="0.01" class="form-control" id="penerimaan" name="penerimaan_volume"
                                        value="{{ old('penerimaan_volume', $dailyReport->penerimaan_volume) }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
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
                                                <input type="number" name="spendings[3]" class="form-control form-control-sm spending-input" value="{{ isset($spendings[3]) ? intval($spendings[3]->jumlah) : 0 }}">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Biaya Transfer</label>
                                                <input type="number" name="spendings[4]" class="form-control form-control-sm spending-input" value="{{ isset($spendings[4]) ? intval($spendings[4]->jumlah) : 0 }}">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Fotocopy & ATK</label>
                                                <input type="number" name="spendings[5]" class="form-control form-control-sm spending-input" value="{{ isset($spendings[5]) ? intval($spendings[5]->jumlah) : 0 }}">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Listrik</label>
                                                <input type="number" name="spendings[6]" class="form-control form-control-sm spending-input" value="{{ isset($spendings[6]) ? intval($spendings[6]->jumlah) : 0 }}">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Air Bersih</label>
                                                <input type="number" name="spendings[7]" class="form-control form-control-sm spending-input" value="{{ isset($spendings[7]) ? intval($spendings[7]->jumlah) : 0 }}">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Cashback</label>
                                                <input type="number" name="spendings[8]" class="form-control form-control-sm spending-input" value="{{ isset($spendings[8]) ? intval($spendings[8]->jumlah) : 0 }}">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="text-xs">Internet</label>
                                                <input type="number" name="spendings[9]" class="form-control form-control-sm spending-input" value="{{ isset($spendings[9]) ? intval($spendings[9]->jumlah) : 0 }}">
                                            </div>
                                            <div class="col-md-12 mb-2">
                                                <label class="text-xs">Lain-lain</label>
                                                <div class="row">
                                                    <div class="col-md-7">
                                                        <input type="text" name="spending_lain_ket" class="form-control form-control-sm" placeholder="Keterangan" value="{{ isset($spendings[99]) ? $spendings[99]->keterangan : '' }}">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="number" name="spending_lain_nom" class="form-control form-control-sm spending-input" value="{{ isset($spendings[99]) ? intval($spendings[99]->jumlah) : 0 }}">
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
                                        name="stok_akhir_teoritis"
                                        value="{{ old('stok_akhir_teoritis', $dailyReport->stok_akhir_teoritis) }}"
                                        readonly>
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
                                    <input type="number" class="form-control @error('stik_akhir') is-invalid @enderror"
                                        id="stik_akhir" name="stik_akhir"
                                        value="{{ old('stik_akhir', $dailyReport->stik_akhir) }}">
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
                            <label for="stok_akhir_aktual" class="col-sm-4 col-form-label">Stok Akhir Aktual</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number"
                                        class="form-control @error('stok_akhir_aktual') is-invalid @enderror"
                                        id="stok_akhir_aktual" name="stok_akhir_aktual"
                                        value="{{ old('stok_akhir_aktual', $dailyReport->stok_akhir_aktual) }}" readonly>
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
                            <label for="losses_gain" class="col-sm-4 col-form-label">Losses / Gain</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control @error('losses_gain') is-invalid @enderror"
                                        id="losses_gain" name="losses_gain"
                                        value="{{ old('losses_gain', $dailyReport->losses_gain) }}" readonly>
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
                                        value="{{ old('pengeluaran', $dailyReport->pengeluaran) }}" readonly>
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
                                        value="{{ old('pendapatan', $dailyReport->pendapatan) }}" readonly>
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
                                                <input type="number" name="setor_tunai" id="setor_tunai" class="form-control form-control-sm setoran-input" value="{{ old('setor_tunai', intval($dailyReport->setor_tunai)) }}">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">Transfer QR Mandiri</label>
                                                <input type="number" name="setor_qris" id="setor_qris" class="form-control form-control-sm setoran-input" value="{{ old('setor_qris', intval($dailyReport->setor_qris)) }}">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">Transfer Operator</label>
                                                <input type="number" name="setor_transfer" id="setor_transfer" class="form-control form-control-sm setoran-input" value="{{ old('setor_transfer', intval($dailyReport->setor_transfer)) }}">
                                            </div>
                                        </div>
                                        <hr class="mt-2 mb-3">
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">Setoran ke Kolektan</label>
                                                <select name="kolektan_id" class="form-control form-control-sm">
                                                    <option value="">-- Pilih Kolektan --</option>
                                                    @foreach($kolektans as $kolektan)
                                                        <option value="{{ $kolektan->id }}" {{ old('kolektan_id', $dailyReport->kolektan_id) == $kolektan->id ? 'selected' : '' }}>{{ $kolektan->nama_kolektan }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">Nominal Setor Kolektan</label>
                                                <input type="number" name="setor_kolektan" id="setor_kolektan" class="form-control form-control-sm setoran-input" value="{{ old('setor_kolektan', intval($dailyReport->setor_kolektan)) }}">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="text-xs">PIN Kolektan (Isi jika ada perubahan setoran)</label>
                                                <input type="password" name="kolektan_pin" id="kolektan_pin" class="form-control form-control-sm @error('kolektan_pin') is-invalid @enderror" placeholder="Ketik PIN jika nominal berubah">
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
                                    <input type="number" class="form-control" id="disetorkan" name="disetorkan" value="{{ old('disetorkan', $dailyReport->disetorkan) }}" readonly>
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
                                        name="selisih_setoran"
                                        value="{{ old('selisih_setoran', $dailyReport->selisih_setoran) }}" readonly>
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
                                        name="belum_disetorkan"
                                        value="{{ old('belum_disetorkan', $dailyReport->belum_disetorkan) }}" readonly>
                                </div>
                            </div>
                        </div>

                        @if (Auth::user()->role == 'admin')
                            <div class="form-group row">
                                <label for="diverifikasi" class="col-sm-4 col-form-label">Diverifikasi</label>
                                <div class="col-sm-8">
                                    <select name="diverifikasi" id="diverifikasi"
                                        class="form-control @error('diverifikasi') is-invalid @enderror">
                                        <option value="0" @selected(0 == old('diverifikasi', $dailyReport->diverifikasi))>Belum</option>
                                        <option value="1" @selected(1 == old('diverifikasi', $dailyReport->diverifikasi))>Sudah</option>
                                    </select>
                                    @error('diverifikasi')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endif

                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </div>


                </form>
            </div>
        </div>

    </section>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            let changeIndex = 0;
            const baseTotalisatorAwal = {{ $dailyReport->totalisator_awal }};

            function addChangeRow(jam = '', hargaJual = '', hargaBeli = '', totalisator = '') {
                const html = `
                    <div class="row price-change-row mb-3 align-items-end" id="change-row-${changeIndex}">
                        <div class="col-md-2">
                            <label class="text-xs">Jam Berlaku</label>
                            <input type="time" name="price_changes[${changeIndex}][jam]" class="form-control form-control-sm change-jam" value="${jam}" required readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="text-xs">Harga Jual Baru</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="number" name="price_changes[${changeIndex}][harga_jual]" class="form-control change-harga-jual" value="${hargaJual}" required readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="text-xs">Harga Beli Baru</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="number" name="price_changes[${changeIndex}][harga_beli]" class="form-control change-harga-beli" value="${hargaBeli}" required readonly>
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
                    const hargaBeli = parseFloat(row.find('.change-harga-beli').val()) || 0;
                    const totalisator = parseFloat(row.find('.change-totalisator').val()) || 0;

                    if (jam && hargaJual > 0 && hargaBeli > 0 && totalisator > 0) {
                        validChanges.push({ jam, hargaJual, hargaBeli, totalisator });
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
                $('#stok_akhir_teoritis').val(stok_akhir_teoritis.toFixed(2));

                const stik_akhir = $('#stik_akhir').val() * 1;
                const skala = {{ $dailyReport->shop->skala }};
                const stok_akhir_aktual = stik_akhir * skala;
                $('#stok_akhir_aktual').val(stok_akhir_aktual);

                const losses_gain = stok_akhir_aktual - stok_akhir_teoritis;
                $('#losses_gain').val(losses_gain.toFixed(2));

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

                const belum_disetorkan_kemarin = {{ $dailyReport->latestByOperator() ? $dailyReport->latestByOperator()->belum_disetorkan : 0 }};
                $('#belum_disetorkan').val(belum_disetorkan_kemarin + pendapatan - total_setoran);
            }



            $(document).on('input', '.change-harga-jual, .change-harga-beli, .change-totalisator', function() {
                updateTotals();
            });

            $(document).on('change', '.change-jam', function() {
                updateTotals();
            });

            // Load existing periods as price changes
            @if ($dailyReport->periods()->count() > 1)
                @php
                    $periodsArray = $dailyReport->periods()->orderBy('totalisator_awal', 'asc')->get();
                @endphp
                @foreach ($periodsArray as $index => $period)
                    @if ($index > 0)
                        addChangeRow(
                            "{{ \Carbon\Carbon::parse($period->price->created_at)->format('H:i') }}",
                            "{{ intval($period->price->harga_jual) }}",
                            "{{ intval($period->price->harga_beli) }}",
                            "{{ $period->totalisator_awal }}"
                        );
                    @endif
                @endforeach
            @endif

            $('#totalisator_akhir, #stik_akhir, #test_pump, #penerimaan').on('input', function() {
                updateTotals();
            });

            $(document).on('input', '.spending-input, .setoran-input', function() {
                updateTotals();
            });

            // Trigger initial calculation
            updateTotals();
        });
    </script>
@endpush
