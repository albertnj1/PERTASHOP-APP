@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Upload Laporan Harian Pertashop</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Form Input Laporan</h3>
                    </div>
                </div>
                
                <form action="{{ route('daily-report-uploads.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Pilihan Pertashop</label>
                            <div class="col-sm-8">
                                <select name="shop_id" class="form-control" required>
                                    <option value="">-- Pilih Outlet --</option>
                                    @foreach ($shops as $shop)
                                        <option value="{{ $shop->id }}">{{ $shop->nama }} ({{ $shop->kode }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Tanggal Laporan</label>
                            <div class="col-sm-8">
                                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Totalisator Awal (Opsional)</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="any" name="totalisator_awal" class="form-control" placeholder="Otomatis dari data kemarin jika kosong">
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Totalisator Akhir</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="any" name="totalisator_akhir" class="form-control" placeholder="Contoh: 121500.5" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Test Pump (Liter)</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="any" name="test_pump" class="form-control" placeholder="Contoh: 10" value="0">
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Pembayaran QRIS (Rp)</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" step="any" name="qris" class="form-control" value="0" required>
                                </div>
                                <small class="text-muted">Isi 0 jika tidak ada transaksi QRIS.</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Keterangan Pengeluaran</label>
                            <div class="col-sm-8">
                                <input type="text" name="keterangan_pengeluaran" class="form-control" placeholder="Contoh: Beli Sapu Lidi">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Nominal Pengeluaran</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" step="any" name="pengeluaran" class="form-control" value="0" required>
                                </div>
                                <small class="text-muted">Biaya operasional harian yang dipotong dari kas.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Penguploadan Excel / Gambar Bukti (Opsional)</label>
                            <div class="col-sm-8">
                                <div class="custom-file">
                                    <input type="file" name="file_bukti" class="custom-file-input" id="customFile" accept=".xlsx,.xls,.png,.jpg,.jpeg">
                                    <label class="custom-file-label" for="customFile">Pilih file</label>
                                </div>
                                <small class="text-muted mt-1 d-block">Mendukung format gambar (PNG/JPG) atau dokumen Excel.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan & Hitung</button>
                        <a href="{{ route('daily-report-uploads.index') }}" class="btn btn-secondary ml-1">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('script')
<script>
    // To show file name in custom-file-input
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
</script>
@endpush
