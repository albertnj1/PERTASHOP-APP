<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Pertashop App — @yield('title', 'Dashboard')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/sidebar-only.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('css/new-admin.css') }}?v={{ time() }}">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

@stack('style')


<script>
    function formatNumber(data, fractionDigit = 0) {
        if (data === null || data === undefined) return '';
        return parseFloat(data).toLocaleString('id-ID', {
            minimumFractionDigits: fractionDigit,
            maximumFractionDigits: fractionDigit
        });
    }

    function formatCurrency(data, fractionDigit = 0) {
        if (data === null || data === undefined) return '';
        return parseFloat(data).toLocaleString('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: fractionDigit,
            maximumFractionDigits: fractionDigit
        });
    }

    function formatYearMonth(data) {
        if (!data) return '';
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const parts = data.toString().split('-');
        if (parts.length >= 2) {
            const year = parts[0];
            const month = parseInt(parts[1], 10) - 1;
            if (month >= 0 && month < 12) {
                return months[month] + ' ' + year;
            }
        }
        return data;
    }
</script>
</head>
<body>

  <!-- ================= MOBILE BACKDROP ================= -->
  <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleMobileSidebar()"></div>

  <!-- ================= SIDEBAR ================= -->
  <div class="sidebar new-sidebar" id="mainSidebar">
    @include('partials._sidebar')
  </div>

  <!-- ================= MAIN ================= -->
  <div class="main">
    <div class="topbar">
      <div class="d-flex align-items-center">
        <button type="button" class="mobile-toggle-btn" onclick="toggleMobileSidebar()" aria-label="Toggle Sidebar">
          <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none">
            <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
        <div class="crumbs">Home <span style="opacity:.5">/</span> <b>@yield('title', 'Dashboard')</b></div>
      </div>
      <div class="badge-pill">{{ Auth::user()->role ?? 'SUPER ADMIN' }}</div>
    </div>

    <div class="content">
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

  @stack('script')
  @stack('scripts')
</body>
</html>
