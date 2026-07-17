@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 style="font-weight:700;color:#0f172a;">
                        <a href="{{ route('prices.index') }}" class="btn btn-outline-secondary mr-2" style="border-radius: 50%; width: 40px; height: 40px; padding: 0; line-height: 38px; text-align: center;">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        Edit Harga BBM
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('prices.index') }}">Harga BBM</a></li>
                        <li class="breadcrumb-item active">Edit</li>
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
                        <h3 class="card-title">Harga BBM</h3>
                    </div>

                </div>
                <form id="insertForm" action="{{ route('prices.update', $price->id) }}" method="POST"
                    class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="card-body">

                        @if(Auth::user()->role == 'operator')
                            <input type="hidden" name="shop_id" value="{{ Auth::user()->operator->shop_id }}">
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Pertashop</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" value="{{ Auth::user()->operator->shop->nama ?? '' }}" readonly>
                                </div>
                            </div>
                        @else
                            <div class="form-group row">
                                <label for="shop_id" class="col-sm-4 col-form-label">Pertashop</label>
                                <div class="col-sm-8">
                                    <select class="form-control @error('shop_id') is-invalid @enderror" id="shop_id" name="shop_id" required>
                                        <option value="">-- Pilih Pertashop --</option>
                                        @foreach($shops as $shop)
                                            <option value="{{ $shop->id }}" {{ old('shop_id', $price->shop_id) == $shop->id ? 'selected' : '' }}>
                                                {{ $shop->kode }} {{ $shop->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('shop_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <div class="form-group row">
                            <label for="created_at" class="col-sm-4 col-form-label">Tanggal Perubahan</label>
                            <div class="col-sm-8">
                                <input type="date" class="form-control @error('created_at') is-invalid @enderror"
                                    id="created_at" name="created_at"
                                    value="{{ old('created_at', \Carbon\Carbon::parse($price->effective_at ?? $price->created_at)->format('Y-m-d')) }}" required>
                                @error('created_at')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="jam" class="col-sm-4 col-form-label">Waktu Perubahan</label>
                            <div class="col-sm-8">
                                <input type="time" class="form-control @error('jam') is-invalid @enderror"
                                    id="jam" name="jam" value="{{ old('jam', \Carbon\Carbon::parse($price->effective_at)->format('H:i')) }}" required>
                                <small class="text-muted">Masukkan jam terjadinya perubahan harga (contoh: 14:00)</small>
                                @error('jam')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="totalisator_perubahan" class="col-sm-4 col-form-label">Totalisator Saat Berubah</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="0.001" class="form-control @error('totalisator_perubahan') is-invalid @enderror"
                                        id="totalisator_perubahan" name="totalisator_perubahan" value="{{ old('totalisator_perubahan', $price->totalisator_perubahan) }}" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                                <small class="text-muted">Angka totalisator tepat pada saat harga baru mulai berlaku</small>
                                @error('totalisator_perubahan')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="harga_beli" class="col-sm-4 col-form-label">Harga Beli Baru</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control @error('harga_beli') is-invalid @enderror"
                                        id="harga_beli" name="harga_beli" value="{{ old('harga_beli', $price->harga_beli) }}" {{ Auth::user()->role == 'operator' ? 'readonly' : 'required' }}>
                                </div>
                                @if(Auth::user()->role == 'operator')
                                    <small class="text-muted">Operator tidak perlu mengubah harga beli (otomatis mengikuti sebelumnya atau diset Admin).</small>
                                @endif
                                @error('harga_beli')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="harga_jual" class="col-sm-4 col-form-label">Harga Jual</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control @error('harga_jual') is-invalid @enderror"
                                        id="harga_jual" name="harga_jual" value="{{ old('harga_jual', $price->harga_jual) }}">
                                </div>
                                @error('harga_jual')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
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
    </section>
@endsection
