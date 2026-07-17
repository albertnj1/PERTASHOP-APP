@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Tambah Data Kolektan</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('kolektans.index') }}">Data Kolektan</a></li>
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
                <h3 class="card-title">Form Tambah Kolektan</h3>
            </div>
            <form action="{{ route('kolektans.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group row">
                        <label for="shop_id" class="col-sm-3 col-form-label">Pertashop</label>
                        <div class="col-sm-9">
                            <select name="shop_id" id="shop_id" class="form-control @error('shop_id') is-invalid @enderror">
                                <option value="">-- Pilih Pertashop --</option>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
                                @endforeach
                            </select>
                            @error('shop_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label for="nama_kolektan" class="col-sm-3 col-form-label">Nama Kolektan</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control @error('nama_kolektan') is-invalid @enderror" id="nama_kolektan" name="nama_kolektan" value="{{ old('nama_kolektan') }}" placeholder="Masukkan Nama Kolektan">
                            @error('nama_kolektan')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="pin" class="col-sm-3 col-form-label">PIN (6 Digit)</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control @error('pin') is-invalid @enderror" id="pin" name="pin" placeholder="Masukkan 6 Digit PIN">
                            @error('pin')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('kolektans.index') }}" class="btn btn-secondary mr-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
