<div class="wrapper">

    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
        <div class="container-fluid">
            <a href="/" class="navbar-brand">
                <img src="{{ asset('images/logo-pertashop.png') }}" alt="Logo"
                    class="brand-image img-circle" style="opacity: .8">
            </a>

            <div class="collapse navbar-collapse order-3" id="navbarCollapse">
                <ul class="navbar-nav">
         <!-- Nav Item - Laporan Harian (Upload) -->
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
                        <a href="#" class="dropdown-item text-danger" href="{{ route('logout') }}"
                            onclick="logout(event)">
                            <i class="nav-icon fas fa-sign-out-alt mr-2"></i>
                            <span>Logout</span>
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </nav>


    <div class="content-wrapper">


        @yield('content')


    </div>


    <aside class="control-sidebar control-sidebar-dark">

    </aside>


    <footer class="main-footer">
        <div class="container-fluid">
            <div class="text-center">
                <strong>&copy; 2023 <a href="#">{{ Auth::user()->operator->shop->corporation->nama }}</a>
            </div>
        </div>

    </footer>
</div>
