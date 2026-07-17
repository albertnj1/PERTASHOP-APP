@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Tambah Investor</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('investors.index') }}">Investor</a></li>
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
                        <h3 class="card-title">Investor</h3>
                    </div>

                </div>
                <form id="insertForm" action="{{ route('investors.store') }}" method="POST" class="needs-validation"
                    novalidate>
                    @csrf
                    <div class="card-body">

                        <div class="form-group row">
                            <label for="name" class="col-sm-4 col-form-label">Nama</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="email" class="col-sm-4 col-form-label">Email</label>
                            <div class="col-sm-8">
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    id="email" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="no_hp" class="col-sm-4 col-form-label">No. HP</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('no_hp') is-invalid @enderror"
                                    id="no_hp" name="no_hp" value="{{ old('no_hp') }}">
                                @error('no_hp')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        <div class="form-group row">
                            <label for="nama_bank" class="col-sm-4 col-form-label">Nama Bank</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('nama_bank') is-invalid @enderror"
                                    id="nama_bank" name="nama_bank" value="{{ old('nama_bank') }}">
                                @error('nama_bank')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="no_rekening" class="col-sm-4 col-form-label">No. Rekening</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('no_rekening') is-invalid @enderror"
                                    id="no_rekening" name="no_rekening" value="{{ old('no_rekening') }}">
                                @error('no_rekening')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="atas_nama_rekening" class="col-sm-4 col-form-label">a/n Rekening</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('atas_nama_rekening') is-invalid @enderror"
                                    id="atas_nama_rekening" name="atas_nama_rekening"
                                    value="{{ old('atas_nama_rekening') }}">
                                @error('atas_nama_rekening')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3" style="color: #2C4643; font-weight: 600;"><i class="fas fa-hand-holding-usd mr-2" style="color: #C89B3C;"></i>Informasi Investasi</h5>

                        <div class="form-group row">
                            <label for="shop_id" class="col-sm-4 col-form-label">Pertashop Tujuan</label>
                            <div class="col-sm-8">
                                <select class="form-control @error('shop_id') is-invalid @enderror" id="shop_id" name="shop_id">
                                    <option value="" data-total="0">-- Pilih Pertashop --</option>
                                    @foreach($shops as $shop)
                                        <option value="{{ $shop->id }}" data-total="{{ $shop->total_investasi }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                                            {{ $shop->kode }} {{ $shop->nama }} (Total Modal: Rp {{ number_format($shop->total_investasi, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('shop_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="nominal" class="col-sm-4 col-form-label">Nominal Investasi (Rp)</label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control @error('nominal') is-invalid @enderror"
                                    id="nominal" name="nominal" value="{{ old('nominal') }}" placeholder="Contoh: 50000000">
                                @error('nominal')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted mt-2 d-block">Estimasi Profit Sharing: <strong id="estimasi_persentase" class="text-success">0.00%</strong></small>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary float-right">Simpan</button>
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
        function hitungPersentase() {
            let totalModal = parseFloat($('#shop_id').find(':selected').data('total')) || 0;
            let nominal = parseFloat($('#nominal').val()) || 0;
            let estimasi = 0;

            if (totalModal > 0 && nominal > 0) {
                estimasi = (nominal / totalModal) * 100;
            }

            $('#estimasi_persentase').text(estimasi.toFixed(2) + '%');
        }

        $('#shop_id').on('change', hitungPersentase);
        $('#nominal').on('input', hitungPersentase);
        
        // Init on load in case of validation errors
        hitungPersentase();
    });
</script>
@endpush
