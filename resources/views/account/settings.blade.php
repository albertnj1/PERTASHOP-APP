@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="font-weight-bold"><i class="fas fa-user-shield text-primary mr-2"></i>Pengaturan Akun Super Admin</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Pengaturan Akun</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <b><i class="fas fa-exclamation-triangle mr-1"></i> Terjadi Kesalahan:</b>
                <ul class="mb-0 mt-1 pl-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-outline card-primary shadow-sm" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h3 class="card-title font-weight-bold text-dark mb-0">
                            <i class="fas fa-id-card-alt mr-2 text-primary"></i>Kredensial Login & Keamanan Akun
                        </h3>
                    </div>
                    <form action="{{ route('account.settings.update') }}" method="POST" id="adminAccountForm" autocomplete="off">
                        @csrf
                        @method('PUT')
                        <div class="card-body p-4">
                            
                            {{-- Info Banner --}}
                            <div class="alert alert-light border d-flex align-items-center mb-4" style="border-radius: 8px;">
                                <div class="mr-3 text-warning" style="font-size: 1.8rem;">
                                    <i class="fas fa-user-lock"></i>
                                </div>
                                <div class="small text-muted">
                                    Ini adalah akun tingkat <strong>Super Admin</strong> dengan otoritas tertinggi di sistem. Mengubah email atau kata sandi akan otomatis <strong>memutuskan sesi login lama</strong> di seluruh perangkat lain demi keamanan.
                                </div>
                            </div>

                            {{-- Nama Super Admin --}}
                            <div class="form-group mb-3">
                                <label for="name" class="font-weight-bold text-dark">
                                    Nama Super Admin <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $user->name) }}" 
                                       required 
                                       placeholder="Nama Lengkap">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Email Login --}}
                            <div class="form-group mb-4">
                                <label for="email" class="font-weight-bold text-dark">
                                    Email Login Sistem <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', $user->email) }}" 
                                       data-original="{{ $user->email }}"
                                       required 
                                       placeholder="admin@pertashop.com"
                                       spellcheck="false">
                                <small class="form-text text-muted">
                                    Format email: hanya huruf, angka, pemisah standar <code>@</code> dan titik <code>.</code> (misal: <code>admin@pertashop.com</code>).
                                </small>
                                <div class="invalid-feedback" id="email-client-error">
                                    @error('email'){{ $message }}@enderror
                                </div>
                            </div>

                            <hr class="my-4">

                            {{-- Password Baru --}}
                            <h5 class="font-weight-bold text-dark mb-3">
                                <i class="fas fa-key text-success mr-2"></i>Ubah Password Baru <small class="text-muted font-weight-normal">(Kosongkan bila tidak ingin diubah)</small>
                            </h5>

                            <div class="form-group mb-3">
                                <label for="password" class="font-weight-bold text-dark">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Buat password baru yang aman"
                                           autocomplete="new-password">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-pass-btn" type="button" data-target="#password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" type="button" id="btn-generate-admin-pass" title="Generate Password Kuat">
                                            <i class="fas fa-magic mr-1"></i> Generate
                                        </button>
                                    </div>
                                </div>

                                {{-- Password Requirements Checklist --}}
                                <div class="cred-strength mt-2" style="height: 6px; border-radius: 4px; background: #e9ecef; overflow: hidden;">
                                    <div id="admin-pass-strength" style="height: 100%; width: 0; transition: width .3s ease, background .3s ease;"></div>
                                </div>
                                <ul class="list-unstyled mt-2 small text-muted d-flex flex-wrap" id="admin-pass-rules" style="gap: 8px 16px;">
                                    <li data-rule="len"><i class="far fa-circle mr-1"></i> Minimal 12 Karakter</li>
                                    <li data-rule="upper"><i class="far fa-circle mr-1"></i> Huruf Pertama KAPITAL</li>
                                    <li data-rule="lower"><i class="far fa-circle mr-1"></i> Mengandung Huruf Kecil</li>
                                    <li data-rule="digit"><i class="far fa-circle mr-1"></i> Mengandung Angka</li>
                                    <li data-rule="symbol"><i class="far fa-circle mr-1"></i> Karakter Khusus / Simbol</li>
                                </ul>
                                <div class="invalid-feedback d-block" id="pass-client-error">
                                    @error('password'){{ $message }}@enderror
                                </div>
                            </div>

                            {{-- Konfirmasi Password Baru --}}
                            <div class="form-group mb-4">
                                <label for="password_confirmation" class="font-weight-bold text-dark">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation" 
                                           placeholder="Ketik ulang password baru">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-pass-btn" type="button" data-target="#password_confirmation">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-danger d-none" id="pass-confirm-error">Konfirmasi password tidak cocok!</small>
                            </div>

                            <hr class="my-4">

                            {{-- Password Saat Ini (Proteksi Keamanan) --}}
                            <div class="p-3 border rounded bg-light">
                                <div class="form-group mb-0">
                                    <label for="current_password" class="font-weight-bold text-dark">
                                        <i class="fas fa-lock text-danger mr-1"></i> Masukkan Password Saat Ini <span class="text-danger">*</span>
                                    </label>
                                    <p class="small text-muted mb-2">
                                        Diperlukan untuk memvalidasi identitas Anda sebelum perubahan disimpan.
                                    </p>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control @error('current_password') is-invalid @enderror" 
                                               id="current_password" 
                                               name="current_password" 
                                               required 
                                               placeholder="Password saat ini">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-pass-btn" type="button" data-target="#current_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @error('current_password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        </div>

                        <div class="card-footer bg-white border-top d-flex justify-content-between py-3">
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary px-4 font-weight-bold" id="btn-save-admin-account">
                                <i class="fas fa-save mr-1"></i> Simpan Perubahan Akun
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('style')
<style>
    #admin-pass-rules li.ok {
        color: #28a745 !important;
        font-weight: 600;
    }
    #admin-pass-rules li.ok i:before {
        content: "\f058";
    }
</style>
@endpush

@push('script')
<script>
$(document).ready(function() {
    const EMAIL_RE = /^[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)*@[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)*\.[a-zA-Z]{2,}$/;
    const PASS_RE  = /^[A-Z](?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s]).{11,}$/;

    // Toggle Password Visibility
    $('.toggle-pass-btn').click(function() {
        const target = $($(this).data('target'));
        const icon = $(this).find('i');
        if (target.attr('type') === 'password') {
            target.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            target.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Generator Password
    function generatePassword(len = 14) {
        const U = 'ABCDEFGHJKLMNPQRSTUVWXYZ', L = 'abcdefghijkmnpqrstuvwxyz', D = '23456789', S = '!@#$%&*?';
        const all = U + L + D + S;
        const rnd = (set) => { const a = new Uint32Array(1); crypto.getRandomValues(a); return set[a[0] % set.length]; };
        const rest = [rnd(L), rnd(D), rnd(S)];
        while (rest.length < len - 1) rest.push(rnd(all));
        for (let i = rest.length - 1; i > 0; i--) { 
            const a = new Uint32Array(1); crypto.getRandomValues(a); 
            const j = a[0] % (i + 1); [rest[i], rest[j]] = [rest[j], rest[i]]; 
        }
        return rnd(U) + rest.join('');
    }

    $('#btn-generate-admin-pass').click(function() {
        const pass = generatePassword();
        $('#password').val(pass).attr('type', 'text');
        $('#password_confirmation').val(pass).attr('type', 'text');
        $('.toggle-pass-btn[data-target="#password"] i, .toggle-pass-btn[data-target="#password_confirmation"] i')
            .removeClass('fa-eye').addClass('fa-eye-slash');
        checkPasswordRules();
    });

    // Check Password Rules
    function checkPasswordRules() {
        const val = $('#password').val();
        if (!val) {
            $('#admin-pass-strength').css('width', '0%');
            $('#admin-pass-rules li').removeClass('ok');
            $('#pass-client-error').text('');
            return;
        }

        const checks = {
            len: val.length >= 12,
            upper: /^[A-Z]/.test(val),
            lower: /[a-z]/.test(val),
            digit: /[0-9]/.test(val),
            symbol: /[^A-Za-z0-9\s]/.test(val)
        };

        let score = 0;
        $('#admin-pass-rules li').each(function() {
            const rule = $(this).data('rule');
            if (checks[rule]) {
                $(this).addClass('ok');
                score++;
            } else {
                $(this).removeClass('ok');
            }
        });

        const pct = (score / 5) * 100;
        $('#admin-pass-strength').css({
            width: pct + '%',
            backgroundColor: score >= 5 ? '#28a745' : (score >= 3 ? '#ffc107' : '#dc3545')
        });

        if (val && !PASS_RE.test(val)) {
            $('#pass-client-error').text('Password baru belum memenuhi semua kriteria di atas.');
        } else {
            $('#pass-client-error').text('');
        }

        checkConfirmMatch();
    }

    function checkConfirmMatch() {
        const p1 = $('#password').val();
        const p2 = $('#password_confirmation').val();
        if (p1 && p2 && p1 !== p2) {
            $('#pass-confirm-error').removeClass('d-none');
        } else {
            $('#pass-confirm-error').addClass('d-none');
        }
    }

    $('#password').on('input', checkPasswordRules);
    $('#password_confirmation').on('input', checkConfirmMatch);

    // Form Client-side Validation on Submit
    $('#adminAccountForm').on('submit', function(e) {
        const emailVal = $('#email').val().trim();
        const originalEmail = ($('#email').data('original') || '').trim();
        const passVal = $('#password').val();
        const passConfirmVal = $('#password_confirmation').val();

        let hasError = false;

        // Validasi Email jika diubah
        if (emailVal.toLowerCase() !== originalEmail.toLowerCase()) {
            if (!EMAIL_RE.test(emailVal)) {
                $('#email').addClass('is-invalid');
                $('#email-client-error').text('Format email tidak valid (hanya huruf, angka, pemisah standar @ dan titik .).');
                hasError = true;
            } else {
                $('#email').removeClass('is-invalid');
                $('#email-client-error').text('');
            }
        }

        // Validasi Password Baru jika diisi
        if (passVal) {
            if (!PASS_RE.test(passVal)) {
                $('#password').addClass('is-invalid');
                $('#pass-client-error').text('Password baru belum memenuhi syarat (minimal 12 karakter, huruf kapital di awal, mengandung simbol).');
                hasError = true;
            } else {
                $('#password').removeClass('is-invalid');
                $('#pass-client-error').text('');
            }

            if (passVal !== passConfirmVal) {
                $('#pass-confirm-error').removeClass('d-none');
                hasError = true;
            }
        }

        if (hasError) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    text: 'Periksa kembali isian form sebelum menyimpan perubahan.',
                });
            }
        }
    });
});
</script>
@endpush
