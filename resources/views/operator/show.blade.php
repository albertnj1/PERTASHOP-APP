@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Detail Operator</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('operators.index') }}">Operator</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline card-outline-tabs">
                <div class="card-header p-0 border-bottom-0 d-flex justify-content-between align-items-center">
                    <ul class="nav nav-tabs border-bottom-0" id="operator-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-pribadi" data-toggle="pill" href="#content-pribadi" role="tab" aria-controls="content-pribadi" aria-selected="true">Data Pribadi</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-keluarga" data-toggle="pill" href="#content-keluarga" role="tab" aria-controls="content-keluarga" aria-selected="false">Keluarga</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-kontak" data-toggle="pill" href="#content-kontak" role="tab" aria-controls="content-kontak" aria-selected="false">Kontak & Bank</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-dokumen" data-toggle="pill" href="#content-dokumen" role="tab" aria-controls="content-dokumen" aria-selected="false">Dokumen Pendukung</a>
                        </li>
                    </ul>
                    @if(Auth::user()->role != 'investor')
                    <div class="p-2 mr-2">
                        <a href="{{ route('operators.edit', $operator->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-edit mr-1"></i> Edit Data</a>
                    </div>
                    @endif
                </div>

                <div class="card-body">
                    <div class="tab-content" id="operator-tabs-content">
                        <!-- Tab Data Pribadi -->
                        <div class="tab-pane fade show active" id="content-pribadi" role="tabpanel" aria-labelledby="tab-pribadi">
                            <div class="row">
                                <div class="col-md-3 text-center mb-4">
                                    @if($operator->pas_foto)
                                        <img src="{{ Storage::url($operator->pas_foto) }}" alt="Pas Foto" class="img-fluid img-thumbnail rounded shadow-sm" style="max-height: 250px;">
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center rounded shadow-sm text-muted" style="height: 250px; width: 100%;">
                                            <div class="text-center">
                                                <i class="fas fa-user-circle fa-4x mb-2"></i>
                                                <p class="mb-0">Tidak ada foto</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-9">
                                    <table class="table table-borderless table-sm">
                                        <tr><th width="30%">Penempatan Pertashop</th><td>: {{ $operator->shop->kode ?? '' }} {{ $operator->shop->nama ?? '' }}</td></tr>
                                        <tr><th>NIK</th><td>: {{ $operator->nik }}</td></tr>
                                        <tr><th>Nama Lengkap</th><td>: {{ $operator->user->name ?? '' }}</td></tr>
                                        <tr><th>Tempat, Tanggal Lahir</th><td>: {{ $operator->tempat_lahir }}, {{ \Carbon\Carbon::parse($operator->tanggal_lahir)->format('d-m-Y') }}</td></tr>
                                        <tr><th>Jenis Kelamin</th><td>: {{ $operator->jenis_kelamin }}</td></tr>
                                        <tr><th>Agama</th><td>: {{ $operator->agama }}</td></tr>
                                        <tr><th>Status Perkawinan</th><td>: {{ $operator->status_perkawinan }}</td></tr>
                                        <tr><th>Pekerjaan</th><td>: {{ $operator->pekerjaan }}</td></tr>
                                        <tr><th>Kewarganegaraan</th><td>: {{ $operator->kewarganegaraan }}</td></tr>
                                        <tr><th>Alamat Saat Ini Tinggal</th><td>: {{ $operator->alamat }}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Data Hubungan Keluarga -->
                        <div class="tab-pane fade" id="content-keluarga" role="tabpanel" aria-labelledby="tab-keluarga">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless table-sm">
                                        <tr><th width="40%">No. Kartu Keluarga</th><td>: {{ $operator->no_kk }}</td></tr>
                                        <tr><th>Status Hubungan</th><td>: {{ $operator->status_hubungan }}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless table-sm">
                                        <tr><th width="40%">Nama Ayah Kandung</th><td>: {{ $operator->nama_ayah }}</td></tr>
                                        <tr><th>Nama Ibu Kandung</th><td>: {{ $operator->nama_ibu }}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Kontak & Bank -->
                        <div class="tab-pane fade" id="content-kontak" role="tabpanel" aria-labelledby="tab-kontak">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Kontak & Digital</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr><th width="40%">No. HP / Telepon</th><td>: {{ $operator->no_hp }}</td></tr>
                                        <tr><th>Email Pribadi</th><td>: {{ $operator->email_pribadi }}</td></tr>
                                        <tr><th>Email Login (Sistem)</th><td>: {{ $operator->user->email ?? '' }}</td></tr>
                                        <tr><th>Akun Media Sosial</th><td>: {{ $operator->akun_medsos ?? '-' }}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Informasi Bank & Lainnya</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr><th width="40%">Nama Bank</th><td>: {{ $operator->nama_bank }}</td></tr>
                                        <tr><th>No. Rekening</th><td>: {{ $operator->no_rekening }}</td></tr>
                                        <tr><th>Atas Nama Rekening</th><td>: {{ $operator->atas_nama_rekening }}</td></tr>
                                        <tr><th>Pendidikan Terakhir</th><td>: {{ $operator->pendidikan_terakhir }}</td></tr>
                                        <tr><th>Asal Sekolah</th><td>: {{ $operator->asal_sekolah ?? '-' }}</td></tr>
                                        <tr><th>Golongan Darah</th><td>: {{ $operator->golongan_darah }}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Dokumen Pendukung -->
                        <div class="tab-pane fade" id="content-dokumen" role="tabpanel" aria-labelledby="tab-dokumen">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary font-weight-bold mb-3 border-bottom pb-2">Nomor Dokumen Penting</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr><th width="40%">NPWP</th><td>: {{ $operator->nomor_npwp ?? '-' }}</td></tr>
                                        <tr><th>Paspor</th><td>: {{ $operator->nomor_paspor ?? '-' }}</td></tr>
                                        <tr><th>SIM ({{ $operator->jenis_sim ?? '-' }})</th><td>: {{ $operator->nomor_sim ?? '-' }}</td></tr>
                                        <tr><th>BPJS</th><td>: {{ $operator->nomor_bpjs ?? '-' }}</td></tr>
                                    </table>
                                </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-footer bg-white text-right border-top">
                    <a href="{{ route('operators.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.toggle-password').click(function() {
            let input = $(this).closest('.input-group').find('input');
            let icon = $(this).find('i');
            
            if (input.attr('type') == 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });
</script>
@endpush
