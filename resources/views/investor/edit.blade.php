@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Investor</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('investors.index') }}">Investor</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline card-outline-tabs">
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs" id="investor-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-pribadi" data-toggle="pill" href="#content-pribadi" role="tab" aria-controls="content-pribadi" aria-selected="true">Data Pribadi</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-kontak" data-toggle="pill" href="#content-kontak" role="tab" aria-controls="content-kontak" aria-selected="false">Kontak & Bank</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-dokumen" data-toggle="pill" href="#content-dokumen" role="tab" aria-controls="content-dokumen" aria-selected="false">Dokumen Pendukung</a>
                        </li>
                        @if(Auth::user()->role === 'super-admin')
                        <li class="nav-item">
                            <a class="nav-link" id="tab-akun" data-toggle="pill" href="#content-akun" role="tab" aria-controls="content-akun" aria-selected="false"><i class="fas fa-key mr-1"></i>Pengaturan Akun</a>
                        </li>
                        @endif
                    </ul>
                </div>

                <form id="insertForm" action="{{ route('investors.update', $investor->id) }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="tab-content" id="investor-tabs-content">
                            
                            <!-- Tab Data Pribadi -->
                            <div class="tab-pane fade show active" id="content-pribadi" role="tabpanel" aria-labelledby="tab-pribadi">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Nama Panggilan / Singkat <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $investor->user->name ?? '') }}" required>
                                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="nama_lengkap_gelar">Nama Lengkap & Gelar (Sesuai Identitas Resmi) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nama_lengkap_gelar') is-invalid @enderror" id="nama_lengkap_gelar" name="nama_lengkap_gelar" value="{{ old('nama_lengkap_gelar', $investor->nama_lengkap_gelar) }}" required>
                                            @error('nama_lengkap_gelar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="nik">NIK (Nomor Induk Kependudukan)</label>
                                            <input type="text" class="form-control @error('nik') is-invalid @enderror" id="nik" name="nik" value="{{ old('nik', $investor->nik) }}" placeholder="16 digit NIK sesuai KTP" maxlength="20">
                                            @error('nik')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="alamat_domisili">Alamat Saat Ini Tinggal <span class="text-danger">*</span></label>
                                            <textarea class="form-control @error('alamat_domisili') is-invalid @enderror" id="alamat_domisili" name="alamat_domisili" rows="3" required>{{ old('alamat_domisili', $investor->alamat_domisili) }}</textarea>
                                            @error('alamat_domisili')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Kontak & Digital -->
                            <div class="tab-pane fade" id="content-kontak" role="tabpanel" aria-labelledby="tab-kontak">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="text-primary border-bottom pb-2 mb-3">Kontak & Digital</h5>
                                        <div class="form-group">
                                            <label for="no_hp">No. Telepon / HP <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('no_hp') is-invalid @enderror" id="no_hp" name="no_hp" value="{{ old('no_hp', $investor->no_hp) }}" required>
                                            @error('no_hp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="email_pribadi">Email Pribadi (Opsional)</label>
                                            <input type="email" class="form-control @error('email_pribadi') is-invalid @enderror" id="email_pribadi" name="email_pribadi" value="{{ old('email_pribadi', $investor->email_pribadi) }}">
                                            @error('email_pribadi')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="text-primary border-bottom pb-2 mb-3">Informasi Bank</h5>
                                        <div class="form-group">
                                            <label for="nama_bank">Nama Bank <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nama_bank') is-invalid @enderror" id="nama_bank" name="nama_bank" value="{{ old('nama_bank', $investor->nama_bank) }}" required>
                                            @error('nama_bank')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="no_rekening">No. Rekening <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('no_rekening') is-invalid @enderror" id="no_rekening" name="no_rekening" value="{{ old('no_rekening', $investor->no_rekening) }}" required>
                                            @error('no_rekening')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="atas_nama_rekening">Atas Nama Rekening <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('atas_nama_rekening') is-invalid @enderror" id="atas_nama_rekening" name="atas_nama_rekening" value="{{ old('atas_nama_rekening', $investor->atas_nama_rekening) }}" required>
                                            @error('atas_nama_rekening')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Dokumen Pendukung -->
                            <div class="tab-pane fade" id="content-dokumen" role="tabpanel" aria-labelledby="tab-dokumen">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="text-primary border-bottom pb-2 mb-3">Nomor Dokumen Penting</h5>
                                        <div class="form-group">
                                            <label for="nomor_npwp">Nomor Pokok Wajib Pajak (NPWP)</label>
                                            <input type="text" class="form-control @error('nomor_npwp') is-invalid @enderror" id="nomor_npwp" name="nomor_npwp" value="{{ old('nomor_npwp', $investor->nomor_npwp) }}">
                                            @error('nomor_npwp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @if(Auth::user()->role === 'super-admin')
                            <!-- Tab Pengaturan Akun -->
                            <div class="tab-pane fade" id="content-akun" role="tabpanel" aria-labelledby="tab-akun">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email Login Sistem <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $investor->user->email ?? '') }}">
                                            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="password">Sandi Baru (Kosongkan jika tidak diubah)</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                                <div class="input-group-append">
                                                    <span class="input-group-text toggle-password" style="cursor: pointer;" title="Tampilkan/Sembunyikan Sandi">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            @php
                                                $currentPassword = '(tidak diketahui)';
                                                try {
                                                    $enc = $investor->user->encrypted_password ?? null;
                                                    if ($enc) {
                                                        $currentPassword = \Illuminate\Support\Facades\Crypt::decryptString($enc);
                                                    } elseif (\Illuminate\Support\Facades\Hash::check('123', $investor->user->password)) {
                                                        $currentPassword = '123';
                                                    } else {
                                                        $currentPassword = '(sandi lama, silakan buat sandi baru)';
                                                    }
                                                } catch (\Throwable $e) {
                                                    $currentPassword = '(tidak dapat didekripsi — APP_KEY mungkin berubah)';
                                                }
                                            @endphp
                                            <small class="text-muted">Sandi saat ini: <strong>{{ $currentPassword }}</strong></small>
                                            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>

                    <div class="card-footer bg-white border-top text-right">
                        <a href="{{ route('investors.index') }}" class="btn btn-secondary mr-2">Batal</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Investor</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('script')
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
