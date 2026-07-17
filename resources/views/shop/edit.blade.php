@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Pertashop</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('shops.index') }}">Pertashop</a></li>
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
                        <h3 class="card-title">Pertashop</h3>
                    </div>

                </div>
                <form id="insertForm" action="{{ route('shops.update', $shop->id) }}" method="POST"
                    class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="card-body">

                        <div class="form-group row">
                            <label for="corporation_id" class="col-sm-4 col-form-label">Badan Usaha</label>
                            <div class="col-sm-8">
                                <select name="corporation_id" id="corporation_id"
                                    class="form-control @error('corporation_id') is-invalid @enderror">
                                    <option value="">--Pilih Badan Usaha--</option>
                                    @foreach ($corporations as $corporation)
                                        <option value="{{ $corporation->id }}" @selected($corporation->id == old('corporation_id', $shop->corporation_id))>
                                            {{ $corporation->nama }}</option>
                                    @endforeach
                                </select>
                                @error('corporation_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="kode" class="col-sm-4 col-form-label">Kode</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('kode') is-invalid @enderror"
                                    id="kode" name="kode" value="{{ old('kode', $shop->kode) }}">
                                @error('kode')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="nama" class="col-sm-4 col-form-label">Nama</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('nama') is-invalid @enderror"
                                    id="nama" name="nama" value="{{ old('nama', $shop->nama) }}">
                                @error('nama')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="alamat" class="col-sm-4 col-form-label">Alamat</label>
                            <div class="col-sm-8">
                                <textarea class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" rows="3"
                                    required>{{ old('alamat', $shop->alamat) }}</textarea>
                                @error('alamat')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="kapasitas" class="col-sm-4 col-form-label">Kapasitas</label>
                            <div class="col-sm-8">
                                <div class="input-group">

                                    <input type="number" class="form-control @error('kapasitas') is-invalid @enderror"
                                        id="kapasitas" name="kapasitas" value="{{ old('kapasitas', $shop->kapasitas) }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                                @error('kapasitas')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="totalisator_awal" class="col-sm-4 col-form-label">Totalisator Awal</label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control @error('totalisator_awal') is-invalid @enderror"
                                    id="totalisator_awal" name="totalisator_awal"
                                    value="{{ old('totalisator_awal', $shop->totalisator_awal) }}">
                                @error('totalisator_awal')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="stik_awal" class="col-sm-4 col-form-label">Stik Awal</label>
                            <div class="col-sm-8">
                                <div class="input-group">

                                    <input type="number" class="form-control @error('stik_awal') is-invalid @enderror"
                                        id="stik_awal" name="stik_awal" value="{{ old('stik_awal', $shop->stik_awal) }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                </div>
                                @error('stik_awal')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="skala" class="col-sm-4 col-form-label">Skala</label>
                            <div class="col-sm-8">

                                <input type="number" class="form-control @error('skala') is-invalid @enderror"
                                    id="skala" name="skala" value="{{ old('skala', $shop->skala) }}">
                                @error('skala')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="modal_awal" class="col-sm-4 col-form-label">Modal Awal</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control @error('modal_awal') is-invalid @enderror"
                                        id="modal_awal" name="modal_awal"
                                        value="{{ old('modal_awal', $shop->modal_awal) }}">
                                </div>
                                @error('modal_awal')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="tanggal_mulai_operasional" class="col-sm-4 col-form-label">Tanggal Mulai Operasional</label>
                            <div class="col-sm-8">
                                <input type="date" class="form-control @error('tanggal_mulai_operasional') is-invalid @enderror"
                                    id="tanggal_mulai_operasional" name="tanggal_mulai_operasional"
                                    value="{{ old('tanggal_mulai_operasional', $shop->tanggal_mulai_operasional) }}">
                                @error('tanggal_mulai_operasional')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- <div class="form-group row">
                            <label for="investors" class="col-sm-4 col-form-label">Investor</label>
                            <div class="col-sm-8">
                                <div class="row mb-3">
                                    <div class="col-10">
                                        <select name="investor[]" id="shop_id" class="form-control">
                                            <option value="">--Pilih Investor--</option>
                                        </select>

                                    </div>
                                    <div class="col-2">
                                        <input type="number" class="form-control" id="persentase" name="persentase[]"
                                            placeholder="%" value="">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-10">
                                        <select name="investor[]" id="shop_id" class="form-control">
                                            <option value="">--Pilih Investor--</option>
                                        </select>

                                    </div>
                                    <div class="col-2">
                                        <input type="number" class="form-control" id="persentase" name="persentase[]"
                                            placeholder="%" value="">
                                    </div>
                                </div>

                            </div>

                        </div> --}}

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
