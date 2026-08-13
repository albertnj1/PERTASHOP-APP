<a href="{{ route('dashboard') }}" class="brand" style="display:flex;">
  <div class="mark">P</div>
  <b>PERTASHOP</b>
</a>

<div class="profile">
  <div class="avatar">{{ strtoupper(substr(Auth::user()->name ?? 'User', 0, 2)) }}</div>
  <div>
    <div class="name">{{ Auth::user()->name ?? 'User' }}</div>
    <div class="role text-uppercase">
      @if(in_array(Auth::user()->role, ['super-admin', 'super_admin', 'admin']))
        Super Admin
      @elseif(Auth::user()->role === 'investor')
        Investor
      @elseif(Auth::user()->role === 'operator')
        Operator
      @else
        {{ Auth::user()->role }}
      @endif
    </div>
  </div>
</div>

<nav style="text-transform: uppercase;">

{{-- 1. SUPER ADMIN MENU (ALL ACCESS) --}}
@if(in_array(Auth::user()->role, ['super-admin', 'super_admin', 'admin']))

  <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
    DASHBOARD
  </a>

  <a href="{{ route('action-center.index') }}" class="nav-item {{ request()->routeIs('action-center.*') ? 'active' : '' }}">
    ACTION CENTER
  </a>

  <div class="nav-group-label">OPERASIONAL</div>
  <a href="{{ route('daily-reports.index') }}" class="nav-item {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}">
    LAPORAN HARIAN
  </a>
  <a href="{{ route('backdate-excel-files.index') }}" class="nav-item {{ request()->routeIs('backdate-excel-files.*') ? 'active' : '' }}">
    UPLOAD FILE BACKDATE
  </a>
  <a href="{{ route('purchases.index') }}" class="nav-item {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
    PEMBELIAN
  </a>
  <a href="{{ route('incomings.index') }}" class="nav-item {{ request()->routeIs('incomings.*') ? 'active' : '' }}">
    PENERIMAAN
  </a>
  <a href="{{ route('test-pumps.index') }}" class="nav-item {{ request()->routeIs('test-pumps.*') ? 'active' : '' }}">
    TEST PUMP
  </a>
  <a href="{{ route('spendings.index') }}" class="nav-item {{ request()->routeIs('spendings.*') ? 'active' : '' }}">
    PENGELUARAN
  </a>
  <a href="{{ route('prices.index') }}" class="nav-item {{ request()->routeIs('prices.*') ? 'active' : '' }}">
    PERUBAHAN HARGA
  </a>

  <div class="nav-group-label">LAPORAN</div>
  <a href="{{ route('laba-kotor.index') }}" class="nav-item {{ request()->routeIs('laba-kotor.*') ? 'active' : '' }}">LABA KOTOR</a>
  <a href="{{ route('laba-bersih.index') }}" class="nav-item {{ request()->routeIs('laba-bersih.*') ? 'active' : '' }}">LABA BERSIH</a>
  <a href="{{ route('modal.index') }}" class="nav-item {{ request()->routeIs('modal.*') ? 'active' : '' }}">REKAP MODAL</a>
  <a href="{{ route('profit-sharing.index') }}" class="nav-item {{ request()->routeIs('profit-sharing.*') ? 'active' : '' }}">PROFIT SHARING</a>
  <a href="{{ route('monthly-reports.index') }}" class="nav-item {{ request()->routeIs('monthly-reports.*') ? 'active' : '' }}">LAPORAN BULANAN</a>

  <div class="nav-group-label">MASTER DATA</div>
  <a href="{{ route('operators.index') }}" class="nav-item {{ request()->routeIs('operators.*') ? 'active' : '' }}">OPERATOR</a>
  <a href="{{ route('investors.index') }}" class="nav-item {{ request()->routeIs('investors.*') ? 'active' : '' }}">INVESTOR</a>
  <a href="{{ route('shops.index') }}" class="nav-item {{ request()->routeIs('shops.*') ? 'active' : '' }}">PERTASHOP</a>
  <a href="{{ route('corporations.index') }}" class="nav-item {{ request()->routeIs('corporations.*') ? 'active' : '' }}">BADAN USAHA</a>
  <a href="{{ route('prices.index') }}" class="nav-item {{ request()->routeIs('prices.index') ? 'active' : '' }}">HARGA MASTER</a>
  <a href="{{ route('kolektans.index') }}" class="nav-item {{ request()->routeIs('kolektans.*') ? 'active' : '' }}">KOLEKTAN</a>

  <div class="nav-group-label">HUMAN RESOURCE</div>
  <a href="{{ route('shift-schedules.index') }}" class="nav-item {{ request()->routeIs('shift-schedules.*') ? 'active' : '' }}">JADWAL SHIFT</a>
  <a href="{{ route('payroll-systems.index') }}" class="nav-item {{ request()->routeIs('payroll-systems.*') || request()->routeIs('payroll.*') || request()->routeIs('payroll-operator-assignments.*') ? 'active' : '' }}">SISTEM PENGGAJIAN</a>
  <a href="{{ route('employee-loans.index') }}" class="nav-item {{ request()->routeIs('employee-loans.*') ? 'active' : '' }}">KASBON &amp; APPROVAL</a>

@endif

{{-- 2. INVESTOR MENU (BUSINESS VIEWER ONLY) --}}
@if(Auth::user()->role === 'investor')

  <a href="{{ route('investor.dashboard') }}" class="nav-item {{ request()->routeIs('investor.dashboard') ? 'active' : '' }}">
    DASHBOARD INVESTOR
  </a>

  <div class="nav-group-label">OUTLET SAYA</div>
  <a href="{{ route('investor.dashboard') }}" class="nav-item {{ request()->routeIs('investor.dashboard') ? 'active' : '' }}">PERFORMA OUTLET</a>
  <a href="{{ route('daily-reports.index') }}" class="nav-item {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}">LAPORAN HARIAN</a>
  <a href="{{ route('backdate-excel-files.index') }}" class="nav-item {{ request()->routeIs('backdate-excel-files.*') ? 'active' : '' }}">FILE BACKDATE</a>
  <a href="{{ route('incomings.index') }}" class="nav-item {{ request()->routeIs('incomings.*') ? 'active' : '' }}">PENERIMAAN</a>
  <a href="{{ route('spendings.index') }}" class="nav-item {{ request()->routeIs('spendings.*') ? 'active' : '' }}">PENGELUARAN</a>

  <div class="nav-group-label">OPERATOR OUTLET</div>
  <a href="{{ route('operators.index') }}" class="nav-item {{ request()->routeIs('operators.*') ? 'active' : '' }}">DATA OPERATOR</a>
  <a href="{{ route('shift-schedules.index') }}" class="nav-item {{ request()->routeIs('shift-schedules.*') ? 'active' : '' }}">JADWAL SHIFT</a>
  <a href="{{ route('employee-loans.index') }}" class="nav-item {{ request()->routeIs('employee-loans.*') ? 'active' : '' }}">KASBON</a>
  <a href="{{ route('payroll-systems.index') }}?tab=perbandingan" class="nav-item {{ request()->routeIs('payroll-systems.*') ? 'active' : '' }}">SISTEM PENGGAJIAN</a>

  <div class="nav-group-label">FINANCE</div>
  <a href="{{ route('laba-kotor.index') }}" class="nav-item {{ request()->routeIs('laba-kotor.*') ? 'active' : '' }}">LABA KOTOR</a>
  <a href="{{ route('laba-bersih.index') }}" class="nav-item {{ request()->routeIs('laba-bersih.*') ? 'active' : '' }}">LABA BERSIH</a>
  <a href="{{ route('profit-sharing.index') }}" class="nav-item {{ request()->routeIs('profit-sharing.*') ? 'active' : '' }}">PROFIT SHARING</a>
  <a href="{{ route('finance.cashflow') }}" class="nav-item {{ request()->routeIs('finance.cashflow') ? 'active' : '' }}">DASHBOARD CASH FLOW</a>

@endif

{{-- 3. OPERATOR MENU (FIELD EXECUTOR ONLY) --}}
@if(Auth::user()->role === 'operator')

  <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
    DASHBOARD OPERATOR
  </a>

  <div class="nav-group-label">OPERASIONAL</div>
  <a href="{{ route('daily-reports.index') }}" class="nav-item {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}">
    LAPORAN HARIAN
  </a>
  <a href="{{ route('incomings.index') }}" class="nav-item {{ request()->routeIs('incomings.*') ? 'active' : '' }}">
    PENERIMAAN
  </a>
  <a href="{{ route('test-pumps.index') }}" class="nav-item {{ request()->routeIs('test-pumps.*') ? 'active' : '' }}">
    TEST PUMP
  </a>
  <a href="{{ route('spendings.index') }}" class="nav-item {{ request()->routeIs('spendings.*') ? 'active' : '' }}">
    PENGELUARAN
  </a>
  <a href="{{ route('prices.index') }}" class="nav-item {{ request()->routeIs('prices.*') ? 'active' : '' }}">
    PERUBAHAN HARGA
  </a>

  <div class="nav-group-label">PERSONAL</div>
  <a href="{{ route('operator.performa') }}" class="nav-item {{ request()->routeIs('operator.performa') ? 'active' : '' }}">
    PERFORMA SAYA
  </a>
  <a href="{{ route('shift-schedules.index') }}" class="nav-item {{ request()->routeIs('shift-schedules.*') ? 'active' : '' }}">
    JADWAL SHIFT
  </a>
  <a href="{{ route('employee-loans.index') }}" class="nav-item {{ request()->routeIs('employee-loans.*') ? 'active' : '' }}">
    KASBON SAYA
  </a>

@endif

  <div class="nav-group-label">AKUN</div>
  <a href="javascript:void(0)" onclick="confirmLogout(event)" class="nav-item">
    LOGOUT
  </a>

  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
  </form>

</nav>

<script>
  function confirmLogout(e) {
      e.preventDefault();
      if (typeof Swal !== 'undefined') {
          Swal.fire({
              title: 'Konfirmasi Logout',
              text: 'Anda yakin ingin logout?',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#184b2b',
              cancelButtonColor: '#7c8a79',
              confirmButtonText: 'Ya, Logout',
              cancelButtonText: 'Batal'
          }).then((result) => {
              if (result.isConfirmed) {
                  document.getElementById('logout-form').submit();
              }
          });
      } else {
          if (confirm('Anda yakin ingin logout?')) {
              document.getElementById('logout-form').submit();
          }
      }
  }

  // Preserve sidebar scroll position across page transitions
  (function() {
    function initSidebarScrollMemory() {
      const navEl = document.querySelector('.new-sidebar nav') || document.querySelector('.main-sidebar nav') || document.querySelector('nav');
      if (!navEl) return;

      // 1. Restore saved scroll position
      const savedScroll = sessionStorage.getItem('sidebar_scroll_pos');
      if (savedScroll !== null) {
        navEl.scrollTop = parseInt(savedScroll, 10);
      } else {
        // Fallback: Bring active menu item into view if first visit
        const activeItem = navEl.querySelector('.nav-item.active');
        if (activeItem) {
          activeItem.scrollIntoView({ block: 'nearest' });
        }
      }

      // 2. Save scroll position on scrolling
      navEl.addEventListener('scroll', function() {
        sessionStorage.setItem('sidebar_scroll_pos', navEl.scrollTop);
      }, { passive: true });

      // 3. Save scroll position when clicking any link
      navEl.querySelectorAll('a.nav-item').forEach(function(link) {
        link.addEventListener('click', function() {
          sessionStorage.setItem('sidebar_scroll_pos', navEl.scrollTop);
        });
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initSidebarScrollMemory);
    } else {
      initSidebarScrollMemory();
    }
  })();
</script>
