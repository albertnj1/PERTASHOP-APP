{{--
    Panel Kredensial Login (dipakai di Edit Operator & Edit Investor, khusus Super Admin)

    Parameter:
      $credUser        : App\Models\User yang kredensialnya dikelola
      $credLabel       : 'Operator' | 'Investor'
      $credUpdateUrl   : URL endpoint update-credentials (PUT/PATCH)
      $credSuggestEmail: saran email format baru (nama@pertashop[lokasi].com)
--}}
@php
    $credPlain   = $credUser ? \App\Support\CredentialPolicy::revealPassword($credUser) : null;
    $credWeak    = \App\Support\CredentialPolicy::isWeak($credPlain);
    $credEmail   = $credUser?->email ?? '';
    $credLegacy  = $credEmail && !preg_match(\App\Support\CredentialPolicy::EMAIL_REGEX, $credEmail);
@endphp

<style>
    .cred-panel { border: 1px solid #e3e8e1; border-radius: 12px; background: linear-gradient(135deg, #fbfdfb 0%, #f3f7f2 100%); }
    .cred-panel .cred-row { display: flex; gap: .5rem; align-items: center; margin-bottom: .35rem; font-size: .85rem; }
    .cred-panel .cred-row > span:first-child { min-width: 110px; color: #6c757d; }
    .cred-panel code { font-size: .85rem; background: #fff; border: 1px solid #e3e8e1; padding: 2px 8px; border-radius: 6px; color: #184b2b; }
    .cred-rules { list-style: none; padding: 0; margin: .5rem 0 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .25rem .75rem; font-size: .8rem; }
    .cred-rules li { color: #9aa39a; transition: color .2s ease; }
    .cred-rules li i { width: 16px; }
    .cred-rules li.ok { color: #1e8e3e; font-weight: 600; }
    .cred-rules li.ok i:before { content: "\f058"; }
    .cred-strength { height: 6px; border-radius: 4px; background: #e9ecef; overflow: hidden; margin-top: .5rem; }
    .cred-strength > div { height: 100%; width: 0; transition: width .25s ease, background .25s ease; }
    .cred-actions .btn { border-radius: 8px; }
</style>

<div class="row">
    <div class="col-lg-8">
        @if(!$credUser)
        <div class="alert alert-warning small mb-3">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <b>Perhatian:</b> Akun login web untuk {{ strtolower($credLabel) }} ini belum terhubung. Silakan isi Email Login dan Sandi di bawah ini lalu klik <b>Simpan Kredensial Saja</b> untuk langsung membuatkan akun login.
        </div>
        @endif

        <div class="cred-panel p-3 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <span class="font-weight-bold text-dark"><i class="fas fa-id-card text-info mr-1"></i> Kredensial Login {{ $credLabel }} (Aktif)</span>
                <div class="cred-actions mt-2 mt-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-copy-creds" title="Salin email & sandi">
                        <i class="fas fa-copy mr-1"></i> Salin Kredensial
                    </button>
                    <button type="button" class="btn btn-sm btn-success" id="btn-generate-copy" title="Generate sandi baru yang memenuhi syarat lalu salin">
                        <i class="fas fa-magic mr-1"></i> Generate &amp; Salin Sandi Baru
                    </button>
                </div>
            </div>

            <div class="cred-row"><span>Email Login</span><code id="cred-current-email">{{ $credEmail ?: '-' }}</code>
                @if($credLegacy)<span class="badge badge-warning">format lama</span>@endif
            </div>
            <div class="cred-row"><span>Nama Akun</span><strong>{{ $credUser?->name ?? '-' }}</strong></div>
            <div class="cred-row"><span>Sandi Aktif</span>
                @if($credPlain !== null)
                    <code id="cred-current-pass" data-visible="0">••••••••••••</code>
                    <button type="button" class="btn btn-xs btn-link p-0" id="btn-reveal-current" title="Tampilkan sandi aktif"><i class="fas fa-eye"></i></button>
                    @if($credWeak)<span class="badge badge-danger">lemah — wajib diganti</span>@endif
                @else
                    <span class="badge badge-secondary">Tidak dapat ditampilkan</span>
                    <small class="text-muted">(tersimpan sebagai hash satu arah — klik <b>Generate &amp; Salin Sandi Baru</b>)</small>
                @endif
            </div>
            <div class="cred-row"><span>Terakhir Diubah</span><span>{{ optional($credUser?->password_changed_at)->translatedFormat('d M Y H:i') ?? 'belum pernah' }}</span></div>
        </div>

        <div class="form-group">
            <label for="cred-email">Email Login Sistem <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="text" class="form-control @error('email') is-invalid @enderror" id="cred-email" name="email"
                       value="{{ old('email', $credEmail) }}" data-original="{{ $credEmail }}"
                       autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="email">
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary" id="btn-suggest-email" data-suggest="{{ $credSuggestEmail }}" title="Gunakan format rekomendasi">
                        <i class="fas fa-lightbulb mr-1"></i> Sarankan
                    </button>
                </div>
            </div>
            <small class="form-text text-muted">{{ \App\Support\CredentialPolicy::EMAIL_HINT }} Contoh: <code>{{ $credSuggestEmail }}</code></small>
            <div class="invalid-feedback d-block" id="cred-email-error">@error('email'){{ $message }}@enderror</div>
        </div>

        <div class="form-group">
            <label for="cred-password">Sandi Baru <small class="text-muted">(kosongkan bila tidak ingin mengganti)</small></label>
            <div class="input-group">
                {{-- autocomplete=new-password mencegah browser mengisi otomatis sandi Super Admin ke akun ini --}}
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="cred-password" name="password"
                       placeholder="Contoh: Kemutug#2026aman" autocomplete="new-password" spellcheck="false">
                <div class="input-group-append">
                    <span class="input-group-text toggle-password" style="cursor:pointer;" title="Tampilkan/Sembunyikan Sandi"><i class="fas fa-eye"></i></span>
                    <button type="button" class="btn btn-outline-success" id="btn-generate" title="Generate sandi kuat"><i class="fas fa-sync-alt"></i></button>
                </div>
            </div>
            <div class="cred-strength"><div id="cred-strength-bar"></div></div>
            <ul class="cred-rules" id="cred-rules">
                <li data-rule="len"><i class="far fa-circle"></i> Minimal 12 karakter</li>
                <li data-rule="upper"><i class="far fa-circle"></i> Huruf pertama KAPITAL</li>
                <li data-rule="lower"><i class="far fa-circle"></i> Mengandung huruf kecil</li>
                <li data-rule="digit"><i class="far fa-circle"></i> Mengandung angka</li>
                <li data-rule="symbol"><i class="far fa-circle"></i> Mengandung simbol (!@#$%&amp;*…)</li>
            </ul>
            <div class="invalid-feedback d-block" id="cred-password-error">@error('password'){{ $message }}@enderror</div>
        </div>

        <div class="alert alert-light border small mb-3">
            <i class="fas fa-shield-alt text-success mr-1"></i>
            Saat disimpan, email &amp; sandi diperbarui langsung di tabel autentikasi dalam satu transaksi, dan
            <b>semua sesi login lama {{ strtolower($credLabel) }} otomatis diputus</b>.
        </div>

        <button type="submit" class="btn btn-success" id="btn-save-creds"
                formaction="{{ $credUpdateUrl }}" formnovalidate data-cred-only="1">
            <i class="fas fa-key mr-1"></i> Simpan Kredensial Saja
        </button>
        <small class="text-muted ml-2">Tidak memerlukan data profil lengkap.</small>
    </div>
</div>

@push('script')
<script>
(function () {
    const EMAIL_RE = /^[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)*@[a-zA-Z0-9]+(\.[a-zA-Z0-9]+)*\.[a-zA-Z]{2,}$/;
    const PASS_RE  = /^[A-Z](?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s]).{11,}$/;
    const CURRENT_PASS = @json($credPlain);
    const LABEL = @json($credLabel);

    const $email = document.getElementById('cred-email');
    const $pass  = document.getElementById('cred-password');
    const $emailErr = document.getElementById('cred-email-error');
    const $passErr  = document.getElementById('cred-password-error');

    function toast(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon, title, text, timer: icon === 'success' ? 2200 : undefined, showConfirmButton: icon !== 'success' });
        } else { alert(title + '\n' + (text || '')); }
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } finally { document.body.removeChild(ta); }
        return Promise.resolve();
    }

    function generatePassword(len = 14) {
        const U = 'ABCDEFGHJKLMNPQRSTUVWXYZ', L = 'abcdefghijkmnpqrstuvwxyz', D = '23456789', S = '!@#$%&*?';
        const all = U + L + D + S;
        const rnd = (set) => { const a = new Uint32Array(1); crypto.getRandomValues(a); return set[a[0] % set.length]; };
        const rest = [rnd(L), rnd(D), rnd(S)];
        while (rest.length < len - 1) rest.push(rnd(all));
        for (let i = rest.length - 1; i > 0; i--) { const a = new Uint32Array(1); crypto.getRandomValues(a); const j = a[0] % (i + 1); [rest[i], rest[j]] = [rest[j], rest[i]]; }
        return rnd(U) + rest.join('');
    }

    function passChecks(v) {
        return { len: v.length >= 12, upper: /^[A-Z]/.test(v), lower: /[a-z]/.test(v), digit: /[0-9]/.test(v), symbol: /[^A-Za-z0-9\s]/.test(v) };
    }

    function renderPass() {
        const v = $pass.value, c = passChecks(v);
        let score = 0;
        document.querySelectorAll('#cred-rules li').forEach(li => { const ok = c[li.dataset.rule]; li.classList.toggle('ok', ok); if (ok) score++; });
        const bar = document.getElementById('cred-strength-bar');
        bar.style.width = v ? (score * 20) + '%' : '0';
        bar.style.background = score >= 5 ? '#1e8e3e' : score >= 3 ? '#f4b400' : '#d93025';
        validatePass(false);
    }

    function validateEmail(show = true) {
        const v = $email.value.trim();
        const unchanged = v.toLowerCase() === ($email.dataset.original || '').toLowerCase();
        let msg = '';
        if (!v) msg = 'Email login wajib diisi.';
        else if (!unchanged && !EMAIL_RE.test(v)) msg = 'Format email tidak valid. Hanya huruf, angka, "@" dan titik. Contoh: dendi@pertashopkemutug.com';
        $email.classList.toggle('is-invalid', !!msg);
        $email.classList.toggle('is-valid', !msg && !unchanged);
        if (show || !msg) $emailErr.textContent = msg;
        return !msg;
    }

    function validatePass(show = true) {
        const v = $pass.value;
        const msg = v && !PASS_RE.test(v) ? 'Sandi belum memenuhi semua syarat di atas.' : '';
        $pass.classList.toggle('is-invalid', !!msg && show);
        $pass.classList.toggle('is-valid', !!v && !msg);
        if (show || !msg) $passErr.textContent = msg;
        return !msg;
    }

    function credentialText(pass) {
        return 'Kredensial Login ' + LABEL + ' SIPERI\n' +
               'Email: ' + $email.value.trim().toLowerCase() + '\n' +
               'Password: ' + pass + '\n' +
               'Link Login: ' + window.location.origin + '/login';
    }

    function showPassInput() {
        $pass.type = 'text';
        const icon = $pass.closest('.input-group').querySelector('.toggle-password i');
        if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
    }

    $email.addEventListener('input', () => validateEmail(false));
    $email.addEventListener('blur', () => validateEmail(true));
    $pass.addEventListener('input', renderPass);
    $pass.addEventListener('blur', () => validatePass(true));

    document.getElementById('btn-suggest-email').addEventListener('click', function () {
        $email.value = this.dataset.suggest; validateEmail(true);
    });

    document.getElementById('btn-generate').addEventListener('click', function () {
        $pass.value = generatePassword(); showPassInput(); renderPass();
    });

    document.getElementById('btn-generate-copy').addEventListener('click', function () {
        if (!validateEmail(true)) { $email.focus(); return; }
        $pass.value = generatePassword(); showPassInput(); renderPass();
        copyText(credentialText($pass.value)).then(() => toast('success', 'Sandi baru disalin!',
            'Klik "Simpan Kredensial Saja" atau "Update" agar sandi baru aktif.'));
    });

    document.getElementById('btn-copy-creds').addEventListener('click', function () {
        if (!validateEmail(true)) { $email.focus(); return; }
        let pass = $pass.value;
        if (pass && !validatePass(true)) { $pass.focus(); return; }
        if (!pass) pass = CURRENT_PASS;
        if (!pass) {
            toast('warning', 'Sandi aktif tidak diketahui', 'Sandi lama hanya tersimpan sebagai hash. Gunakan "Generate & Salin Sandi Baru" lalu simpan.');
            return;
        }
        const note = $pass.value ? ' (belum aktif sampai disimpan)' : '';
        copyText(credentialText(pass)).then(() => toast('success', 'Disalin!', 'Kredensial ' + LABEL.toLowerCase() + ' berhasil disalin' + note + '.'));
    });

    const reveal = document.getElementById('btn-reveal-current');
    if (reveal) reveal.addEventListener('click', function () {
        const el = document.getElementById('cred-current-pass');
        const show = el.dataset.visible !== '1';
        el.textContent = show ? CURRENT_PASS : '••••••••••••';
        el.dataset.visible = show ? '1' : '0';
        this.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
    });

    // Validasi klien sebelum submit (baik "Update" maupun "Simpan Kredensial Saja")
    const form = $email.form;
    form.addEventListener('submit', function (e) {
        const okEmail = validateEmail(true), okPass = validatePass(true);
        if (!okEmail || !okPass) {
            e.preventDefault(); e.stopImmediatePropagation();
            if (window.jQuery) jQuery('#tab-akun').tab('show');
            (okEmail ? $pass : $email).focus();
        }
    }, true);

    renderPass();
})();
</script>
@endpush
