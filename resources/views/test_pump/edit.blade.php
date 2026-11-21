@extends('layouts.master')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Test Pump</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('test-pumps.index') }}">Test Pump</a></li>
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
                        <h3 class="card-title">Test Pump</h3>
                    </div>

                </div>
                <form id="insertForm" action="{{ route('test-pumps.update', $testPump->id) }}" method="POST"
                    class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="card-body">

                        <div class="form-group row">
                            <label for="created_at" class="col-sm-4 col-form-label">Tanggal</label>
                            <div class="col-sm-8">
                                <input type="date" class="form-control @error('created_at') is-invalid @enderror"
                                    id="created_at" name="created_at"
                                    value="{{ old('created_at', $testPump->created_at->format('Y-m-d')) }}" readonly>
                                @error('created_at')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if (Auth::user()->role != 'operator')
                            <div class="form-group row">
                                <label for="operator" class="col-sm-4 col-form-label">Operator</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="operator" name="operator"
                                        value="{{ $testPump->operator->user->name }}" readonly>
                                </div>
                            </div>
                        @endif

                        <div class="form-group row">
                            <label for="totalisator_awal" class="col-sm-4 col-form-label">Totalisator Awal</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number"
                                        class="form-control @error('totalisator_awal') is-invalid @enderror"
                                        id="totalisator_awal" name="totalisator_awal"
                                        value="{{ old('totalisator_awal', $testPump->totalisator_awal) }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                </div>
                                @error('totalisator_awal')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="totalisator_akhir" class="col-sm-4 col-form-label">Totalisator Akhir</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number"
                                        class="form-control @error('totalisator_akhir') is-invalid @enderror"
                                        id="totalisator_akhir" name="totalisator_akhir"
                                        value="{{ old('totalisator_akhir', $testPump->totalisator_akhir) }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                </div>
                                @error('totalisator_akhir')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="volume" class="col-sm-4 col-form-label">Volume</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="volume" name="volume"
                                        value="{{ old('volume', $testPump->totalisator_akhir - $testPump->totalisator_awal) }}"
                                        readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
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
    </section>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            //calculate volume
            $('#totalisator_awal, #totalisator_akhir').on('input', function() {
                let totalisator_awal = $('#totalisator_awal').val() * 1;
                let totalisator_akhir = $('#totalisator_akhir').val() * 1;
                let volume = totalisator_akhir - totalisator_awal;
                $('#volume').val(volume);
            })
        })
    </script>
@endpush
