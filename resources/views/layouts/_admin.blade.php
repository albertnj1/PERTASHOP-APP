<!-- ================= MOBILE BACKDROP ================= -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleMobileSidebar()"></div>

<div class="wrapper">
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="{{ asset('images/logo-pertashop.png') }}" alt="pertashop-logo" height="80"
            width="80">
    </div>
    <nav class="main-header navbar navbar-expand navbar-light">
        <ul class="navbar-nav align-items-center">
            <li class="nav-item">
                <button type="button" class="mobile-toggle-btn" onclick="toggleMobileSidebar()" aria-label="Toggle Sidebar">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none">
                        <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="/" class="nav-link">Home</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link">Contact</a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item">
                <div class="px-3 py-1 rounded-pill" style="background: rgba(0, 121, 107, 0.1);">
                    <p class="mb-0 font-weight-bold text-uppercase" style="color: #00796B; font-size: 13px; letter-spacing: 0.5px;">
                        @if (Auth::user()->role == 'operator')
                            {{ Auth::user()->operator?->shop?->corporation?->nama ?? 'Pertashop' }}
                        @elseif (Auth::user()->role == 'admin')
                            {{ Auth::user()->admin?->shop?->corporation?->nama ?? 'Pertashop' }}
                        @elseif (Auth::user()->role == 'investor')
                            Investor Pertashop
                        @else
                            Super Admin Pertashop
                        @endif
                    </p>
                </div>
            </li>
        </ul>
    </nav>
    <aside class="main-sidebar new-sidebar elevation-4" id="mainSidebar">
        @include('partials._sidebar')
    </aside>
    <div class="content-wrapper">
        @yield('content')
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            v2.0
        </div>
        <strong>&copy; 2026 <a href="#">Pertashop App</a>. </strong> Developed By Albert Nestor J.
    </footer>
</div>

<script>
  function toggleMobileSidebar() {
    if (window.innerWidth > 1024) {
      document.body.classList.toggle('sidebar-collapsed');
    } else {
      document.body.classList.toggle('sidebar-open');
    }
  }
</script>
