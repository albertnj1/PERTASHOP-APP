<!-- ================= MOBILE BACKDROP ================= -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleMobileSidebar()"></div>

<div class="wrapper">
    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
        <div class="container-fluid">
            <button type="button" class="mobile-toggle-btn" onclick="toggleMobileSidebar()" aria-label="Toggle Sidebar">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none">
                    <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <a href="/" class="navbar-brand ml-2">
                <img src="{{ asset('images/logo-pertashop.png') }}" alt="Logo"
                    class="brand-image img-circle" style="opacity: .8">
            </a>

            <div class="collapse navbar-collapse order-3" id="navbarCollapse">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('daily-report-uploads.index') }}">
                            <i class="fas fa-fw fa-file-upload"></i>
                            <span>Upload Laporan Harian</span></a>
                    </li>
                </ul>
            </div>

            <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-circle"
                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path>
                            <path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                            <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"></path>
                        </svg>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-header font-weight-bold">{{ Auth::user()->name }}</span>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-user-circle mr-2"></i><span>Profil</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="{{ route('logout') }}">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <aside class="main-sidebar new-sidebar elevation-4" id="mainSidebar">
        @include('partials._sidebar')
    </aside>

    <div class="content-wrapper">
        @yield('content')
    </div>
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
