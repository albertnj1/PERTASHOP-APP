@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Operator</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('operators.index') }}">Operator</a></li>
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
                    <ul class="nav nav-tabs" id="operator-tabs" role="tablist">
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
                        @if(Auth::user()->role === 'super-admin')
                        <li class="nav-item">
                            <a class="nav-link" id="tab-akun" data-toggle="pill" href="#content-akun" role="tab" aria-controls="content-akun" aria-selected="false"><i class="fas fa-key mr-1"></i>Pengaturan Akun</a>
                        </li>
                        @endif
                    </ul>
                </div>

                <form id="insertForm" action="{{ route('operators.update', $operator->id) }}" method="POST" class="needs-validation" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="tab-content" id="operator-tabs-content">
                            
                            <!-- Tab Data Pribadi -->
                            <div class="tab-pane fade show active" id="content-pribadi" role="tabpanel" aria-labelledby="tab-pribadi">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="shop_id">Penempatan Pertashop <span class="text-danger">*</span></label>
                                            <select name="shop_id" id="shop_id" class="form-control @error('shop_id') is-invalid @enderror" required>
                                                <option value="">--Pilih Pertashop--</option>
                                                @foreach ($shops as $shop)
                                                    <option value="{{ $shop->id }}" @selected($shop->id == old('shop_id', $operator->shop_id))>{{ $shop->kode }} {{ $shop->nama }}</option>
                                                @endforeach
                                            </select>
                                            @error('shop_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="nik">NIK (Nomor Induk Kependudukan) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nik') is-invalid @enderror" id="nik" name="nik" value="{{ old('nik', $operator->nik) }}" required>
                                            @error('nik')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="name">Nama Lengkap (sesuai akta) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $operator->user->name ?? '') }}" required>
                                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="tempat_lahir">Tempat Lahir <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('tempat_lahir') is-invalid @enderror" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir', $operator->tempat_lahir) }}" required>
                                            @error('tempat_lahir')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="tanggal_lahir">Tanggal Lahir <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('tanggal_lahir') is-invalid @enderror" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir', $operator->tanggal_lahir) }}" required>
                                            @error('tanggal_lahir')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="jenis_kelamin">Jenis Kelamin <span class="text-danger">*</span></label>
                                            <select class="form-control @error('jenis_kelamin') is-invalid @enderror" id="jenis_kelamin" name="jenis_kelamin" required>
                                                <option value="">-- Pilih --</option>
                                                <option value="Laki-laki" @selected(old('jenis_kelamin', $operator->jenis_kelamin) == 'Laki-laki')>Laki-laki</option>
                                                <option value="Perempuan" @selected(old('jenis_kelamin', $operator->jenis_kelamin) == 'Perempuan')>Perempuan</option>
                                            </select>
                                            @error('jenis_kelamin')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="agama">Agama <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('agama') is-invalid @enderror" id="agama" name="agama" value="{{ old('agama', $operator->agama) }}" required>
                                            @error('agama')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="status_perkawinan">Status Perkawinan <span class="text-danger">*</span></label>
                                            <select class="form-control @error('status_perkawinan') is-invalid @enderror" id="status_perkawinan" name="status_perkawinan" required>
                                                <option value="">-- Pilih --</option>
                                                <option value="Belum Kawin" @selected(old('status_perkawinan', $operator->status_perkawinan) == 'Belum Kawin')>Belum Kawin</option>
                                                <option value="Kawin" @selected(old('status_perkawinan', $operator->status_perkawinan) == 'Kawin')>Kawin</option>
                                                <option value="Cerai Hidup" @selected(old('status_perkawinan', $operator->status_perkawinan) == 'Cerai Hidup')>Cerai Hidup</option>
                                                <option value="Cerai Mati" @selected(old('status_perkawinan', $operator->status_perkawinan) == 'Cerai Mati')>Cerai Mati</option>
                                            </select>
                                            @error('status_perkawinan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="pekerjaan">Pekerjaan <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('pekerjaan') is-invalid @enderror" id="pekerjaan" name="pekerjaan" value="{{ old('pekerjaan', $operator->pekerjaan) }}" required>
                                            @error('pekerjaan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="kewarganegaraan">Kewarganegaraan <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('kewarganegaraan') is-invalid @enderror" id="kewarganegaraan" name="kewarganegaraan" value="{{ old('kewarganegaraan', $operator->kewarganegaraan ?? 'WNI') }}" required>
                                            @error('kewarganegaraan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="pas_foto">Pas Foto (Opsional)</label>
                                            @if($operator->pas_foto)
                                                <div class="mb-2">
                                                    <img src="{{ Storage::url($operator->pas_foto) }}" alt="Pas Foto" class="img-thumbnail" style="max-height: 150px">
                                                </div>
                                            @endif
                                            <input type="file" class="form-control-file @error('pas_foto') is-invalid @enderror" id="pas_foto" name="pas_foto" accept="image/png, image/jpeg, image/jpg">
                                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto.</small>
                                            @error('pas_foto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="alamat">Alamat Saat Ini Tinggal <span class="text-danger">*</span></label>
                                            <textarea class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" rows="3" required>{{ old('alamat', $operator->alamat) }}</textarea>
                                            @error('alamat')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Data Hubungan Keluarga -->
                            <div class="tab-pane fade" id="content-keluarga" role="tabpanel" aria-labelledby="tab-keluarga">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="no_kk">Nomor Kartu Keluarga (KK) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('no_kk') is-invalid @enderror" id="no_kk" name="no_kk" value="{{ old('no_kk', $operator->no_kk) }}" required>
                                            @error('no_kk')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="nama_ayah">Nama Ayah Kandung <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nama_ayah') is-invalid @enderror" id="nama_ayah" name="nama_ayah" value="{{ old('nama_ayah', $operator->nama_ayah) }}" required>
                                            @error('nama_ayah')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="nama_ibu">Nama Ibu Kandung <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nama_ibu') is-invalid @enderror" id="nama_ibu" name="nama_ibu" value="{{ old('nama_ibu', $operator->nama_ibu) }}" required>
                                            @error('nama_ibu')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status_hubungan">Status Hubungan Keluarga <span class="text-danger">*</span></label>
                                            <select class="form-control @error('status_hubungan') is-invalid @enderror" id="status_hubungan" name="status_hubungan" required>
                                                <option value="">-- Pilih --</option>
                                                <option value="Kepala Keluarga" @selected(old('status_hubungan', $operator->status_hubungan) == 'Kepala Keluarga')>Kepala Keluarga</option>
                                                <option value="Suami" @selected(old('status_hubungan', $operator->status_hubungan) == 'Suami')>Suami</option>
                                                <option value="Istri" @selected(old('status_hubungan', $operator->status_hubungan) == 'Istri')>Istri</option>
                                                <option value="Anak" @selected(old('status_hubungan', $operator->status_hubungan) == 'Anak')>Anak</option>
                                                <option value="Famili Lain" @selected(old('status_hubungan', $operator->status_hubungan) == 'Famili Lain')>Famili Lain</option>
                                            </select>
                                            @error('status_hubungan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
                                            <label for="no_hp">No. HP / Telepon <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('no_hp') is-invalid @enderror" id="no_hp" name="no_hp" value="{{ old('no_hp', $operator->no_hp) }}" required>
                                            @error('no_hp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="email_pribadi">Email Pribadi <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control @error('email_pribadi') is-invalid @enderror" id="email_pribadi" name="email_pribadi" value="{{ old('email_pribadi', $operator->email_pribadi) }}" required>
                                            @error('email_pribadi')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="akun_medsos">Akun Media Sosial (Opsional)</label>
                                            <input type="text" class="form-control @error('akun_medsos') is-invalid @enderror" id="akun_medsos" name="akun_medsos" value="{{ old('akun_medsos', $operator->akun_medsos) }}" placeholder="Instagram: @username, FB: Nama">
                                            @error('akun_medsos')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="text-primary border-bottom pb-2 mb-3">Informasi Lainnya</h5>
                                        <div class="form-group">
                                            <label for="nama_bank">Nama Bank <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nama_bank') is-invalid @enderror" id="nama_bank" name="nama_bank" value="{{ old('nama_bank', $operator->nama_bank) }}" required>
                                            @error('nama_bank')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="no_rekening">No. Rekening <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('no_rekening') is-invalid @enderror" id="no_rekening" name="no_rekening" value="{{ old('no_rekening', $operator->no_rekening) }}" required>
                                            @error('no_rekening')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="atas_nama_rekening">A/N Rekening <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('atas_nama_rekening') is-invalid @enderror" id="atas_nama_rekening" name="atas_nama_rekening" value="{{ old('atas_nama_rekening', $operator->atas_nama_rekening) }}" required>
                                            @error('atas_nama_rekening')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="pendidikan_terakhir">Pendidikan Terakhir <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('pendidikan_terakhir') is-invalid @enderror" id="pendidikan_terakhir" name="pendidikan_terakhir" value="{{ old('pendidikan_terakhir', $operator->pendidikan_terakhir) }}" required>
                                            @error('pendidikan_terakhir')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="asal_sekolah">Asal Sekolah <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('asal_sekolah') is-invalid @enderror" id="asal_sekolah" name="asal_sekolah" value="{{ old('asal_sekolah', $operator->asal_sekolah) }}" required>
                                            @error('asal_sekolah')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="golongan_darah">Golongan Darah <span class="text-danger">*</span></label>
                                            <select class="form-control @error('golongan_darah') is-invalid @enderror" id="golongan_darah" name="golongan_darah" required>
                                                <option value="">-- Pilih --</option>
                                                <option value="A" @selected(old('golongan_darah', $operator->golongan_darah) == 'A')>A</option>
                                                <option value="B" @selected(old('golongan_darah', $operator->golongan_darah) == 'B')>B</option>
                                                <option value="AB" @selected(old('golongan_darah', $operator->golongan_darah) == 'AB')>AB</option>
                                                <option value="O" @selected(old('golongan_darah', $operator->golongan_darah) == 'O')>O</option>
                                                <option value="Belum Tahu" @selected(old('golongan_darah', $operator->golongan_darah) == 'Belum Tahu')>Belum Tahu</option>
                                            </select>
                                            @error('golongan_darah')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Dokumen Fisik -->
                            <div class="tab-pane fade" id="content-dokumen" role="tabpanel" aria-labelledby="tab-dokumen">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="text-primary border-bottom pb-2 mb-3">Nomor Dokumen Penting</h5>
                                        <div class="form-group">
                                            <label for="nomor_paspor">Paspor (Opsional)</label>
                                            <input type="text" class="form-control @error('nomor_paspor') is-invalid @enderror" id="nomor_paspor" name="nomor_paspor" value="{{ old('nomor_paspor', $operator->nomor_paspor) }}">
                                            @error('nomor_paspor')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="nomor_sim">SIM (Surat Izin Mengemudi) (Opsional)</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <select name="jenis_sim" id="jenis_sim" class="form-control @error('jenis_sim') is-invalid @enderror">
                                                        <option value="">Jenis SIM</option>
                                                        <option value="A" @selected(old('jenis_sim', $operator->jenis_sim) == 'A')>A</option>
                                                        <option value="B" @selected(old('jenis_sim', $operator->jenis_sim) == 'B')>B</option>
                                                        <option value="C" @selected(old('jenis_sim', $operator->jenis_sim) == 'C')>C</option>
                                                    </select>
                                                </div>
                                                <input type="text" class="form-control @error('nomor_sim') is-invalid @enderror" id="nomor_sim" name="nomor_sim" value="{{ old('nomor_sim', $operator->nomor_sim) }}">
                                            </div>
                                            @error('jenis_sim')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            @error('nomor_sim')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="nomor_bpjs">BPJS Kesehatan / Ketenagakerjaan (Opsional)</label>
                                            <input type="text" class="form-control @error('nomor_bpjs') is-invalid @enderror" id="nomor_bpjs" name="nomor_bpjs" value="{{ old('nomor_bpjs', $operator->nomor_bpjs) }}">
                                            @error('nomor_bpjs')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="nomor_npwp">NPWP (Nomor Pokok Wajib Pajak) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nomor_npwp') is-invalid @enderror" id="nomor_npwp" name="nomor_npwp" value="{{ old('nomor_npwp', $operator->nomor_npwp) }}" required>
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
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $operator->user->email ?? '') }}">
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
                                                    $enc = $operator->user->encrypted_password ?? null;
                                                    if ($enc) {
                                                        $currentPassword = \Illuminate\Support\Facades\Crypt::decryptString($enc);
                                                    } elseif (\Illuminate\Support\Facades\Hash::check('123', $operator->user->password)) {
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
                        <a href="{{ route('operators.index') }}" class="btn btn-secondary mr-2">Batal</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Operator</button>
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
