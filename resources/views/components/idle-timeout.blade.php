{{-- ============================================================
     AUTO-LOGOUT: Deteksi Idle 15 Menit (Client-Side Safeguard)
     Dipasang di layout utama dashboard.
     Jika 15 menit tanpa aktivitas (mousemove, keydown, click, scroll),
     otomatis logout & redirect ke /login dengan flash message.
     ============================================================ --}}
@auth
<div id="idle-timeout-overlay" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,.85); backdrop-filter:blur(8px); justify-content:center; align-items:center; flex-direction:column;">
    <div style="background:#fff; border-radius:16px; padding:40px 48px; box-shadow:0 25px 50px rgba(0,0,0,.25); text-align:center; max-width:420px;">
        <div style="font-size:48px; margin-bottom:16px;">🔒</div>
        <h4 style="font-weight:700; color:#0f172a; margin-bottom:8px;">Sesi Berakhir</h4>
        <p style="color:#64748b; font-size:14px; line-height:1.6;">
            Sesi Anda telah berakhir karena tidak ada aktivitas selama 15 menit.<br>
            Silakan login kembali demi keamanan.
        </p>
        <div style="margin-top:20px;">
            <a href="{{ route('login') }}" style="display:inline-block; padding:10px 32px; background:linear-gradient(135deg,#0ea5e9,#2563eb); color:#fff; border-radius:8px; font-weight:600; text-decoration:none; font-size:14px;">
                <i class="fas fa-sign-in-alt mr-1"></i> Login Kembali
            </a>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var IDLE_LIMIT_MS = {{ (int) config('session.lifetime', 15) * 60 * 1000 }};
    var WARNING_BEFORE_MS = 60000; // Tampilkan warning 1 menit sebelum logout
    var idleTimer = null;
    var warningTimer = null;
    var isLoggingOut = false;

    function resetIdleTimer() {
        if (isLoggingOut) return;
        clearTimeout(idleTimer);
        clearTimeout(warningTimer);

        // Warning 1 menit sebelum batas
        warningTimer = setTimeout(function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Akan Berakhir',
                    text: 'Anda akan otomatis logout dalam 1 menit karena tidak ada aktivitas.',
                    timer: 55000,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    confirmButtonText: 'Tetap Login',
                    allowOutsideClick: true,
                }).then(function(result) {
                    if (result.isConfirmed || result.dismiss === Swal.DismissReason.close) {
                        resetIdleTimer();
                    }
                });
            }
        }, Math.max(0, IDLE_LIMIT_MS - WARNING_BEFORE_MS));

        // Auto-logout saat batas tercapai
        idleTimer = setTimeout(function() {
            performAutoLogout();
        }, IDLE_LIMIT_MS);
    }

    function performAutoLogout() {
        if (isLoggingOut) return;
        isLoggingOut = true;

        // Tampilkan overlay
        var overlay = document.getElementById('idle-timeout-overlay');
        if (overlay) overlay.style.display = 'flex';

        // Tutup SweetAlert jika sedang tampil
        if (typeof Swal !== 'undefined') Swal.close();

        // Kirim request logout via POST
        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            fetch('{{ route("logout") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).catch(function() {
                // Jika fetch gagal, langsung redirect
                window.location.href = '{{ route("login") }}';
            });
        }
    }

    // Deteksi aktivitas user
    var activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart', 'mousedown'];
    activityEvents.forEach(function(evt) {
        document.addEventListener(evt, resetIdleTimer, { passive: true });
    });

    // Mulai timer saat halaman dimuat
    resetIdleTimer();
})();
</script>
@endauth
