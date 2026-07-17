@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 align-items-center">
                <div class="col-12 col-md-6 mb-2 mb-md-0">
                    <h1 style="font-weight: 700; color: #0f172a; font-size: 1.8rem;">Dashboard</h1>
                </div>
                <div class="col-12 col-md-6">
                    @if (Auth::user()->role == 'operator')
                        <h3 class="text-right text-md font-weight-bold text-dark mb-0">
                            {{ Auth::user()->operator->shop->kode . ' ' . Auth::user()->operator->shop->nama }}</h3>
                    @elseif (Auth::user()->role == 'admin')
                        <h3 class="text-right text-md font-weight-bold text-dark mb-0">
                            {{ Auth::user()->admin->shop->kode . ' ' . Auth::user()->admin->shop->nama }}</h3>
                    @else
                        <select name="shop_id" id="shop_id_select" class="form-control" style="border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 500;">
                            <option value="">Semua Pertashop</option>
                            @foreach ($shops as $shop)
                                <option value="{{ $shop->id }}">{{ $shop->kode . ' ' . $shop->nama }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Summary Header Row -->
            <div id="summary" class="summary-container">
                <!-- Dynamically populated by AJAX -->
            </div>

            <!-- Chart and Stock Row -->
            <div class="row">
                <div class="col-12 col-md-9 mb-4">
                    <div class="card h-100">
                        <div class="card-header border-0 pb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title" style="font-weight: 600;"><i class="fas fa-chart-line mr-2 text-muted"></i>Grafik Penjualan</h3>
                                <input name="filter" type="text" value="day" hidden>
                                <div class="btn-group" style="background-color: #f1f5f9; padding: 4px; border-radius: 8px;">
                                    <button class="btn btn-sm btn-filter filter-day btn-primary">Harian</button>
                                    <button class="btn btn-sm btn-filter filter-week btn-link">Mingguan</button>
                                    <button class="btn btn-sm btn-filter filter-month btn-link">Bulanan</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-header border-0 pb-0">
                            <h3 class="card-title" style="font-weight: 600;"><i class="fas fa-gas-pump mr-2 text-muted"></i>Stok (&ell;)</h3>
                        </div>
                        <div class="card-body">
                            <div id="stock-container" class="d-flex flex-column justify-content-start pt-2">
                                <!-- Dynamically populated by AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Harga Aktif per Outlet -->
            <div class="row" id="active-price-row">
                <!-- Dynamically populated by AJAX -->
            </div>

            {{-- ===== KELOLA HARGA BBM (Super Admin & Admin Only) ===== --}}
            @if(in_array(Auth::user()->role, ['super_admin', 'admin']))
            <div class="row mb-4" id="kelola-harga-section">
                <div class="col-12">
                    <div class="card" style="border-top: 4px solid #00796B; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,121,107,0.08);">
                        <div class="card-header border-0 pb-2 pt-3 px-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg,#f0fdf4,#e8f5e9);">
                            <div>
                                <h4 class="mb-0" style="font-weight:700;color:#00796B;font-size:16px;">
                                    <i class="fas fa-tags mr-2"></i>Kelola Harga BBM
                                </h4>
                                <small class="text-muted">Perubahan harga akan otomatis berlaku untuk semua laporan berikutnya</small>
                            </div>
                            <a href="{{ route('prices.index') }}" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-history mr-1"></i>Riwayat Lengkap
                            </a>
                        </div>
                        <div class="card-body px-4 pb-4 pt-3">
                            <div id="price-form-container">
                                <!-- Populated after page load via AJAX -->
                                <div class="text-center py-3"><i class="fas fa-spinner fa-spin text-muted"></i> Memuat form harga...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif



            <!-- Bottom Row: Donut Charts and Recent Reports -->
            <div class="row">
                <!-- Left column: Donut charts -->
                <div class="col-12 col-md-8">
                    <div class="row">
                        <div class="col-12 col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header border-0 pb-0">
                                    <h3 class="card-title" style="font-weight: 600;"><i class="fas fa-chart-pie mr-2 text-muted"></i>Omset per Pertashop</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="omsetDonut" style="min-height: 220px; height: 220px; max-height: 220px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header border-0 pb-0">
                                    <h3 class="card-title" style="font-weight: 600;"><i class="fas fa-wallet mr-2 text-muted"></i>Laba Kotor per Pertashop</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="labaDonut" style="min-height: 220px; height: 220px; max-height: 220px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profit Sharing & Gain/Losses Cards -->
                    <div class="row">
                        <div class="col-12 col-md-6 mb-4">
                            <div class="card" style="border-left: 4px solid #2196f3;">
                                <div class="card-header border-0 pb-0">
                                    <h3 class="card-title" style="font-weight:600;"><i class="fas fa-handshake mr-2 text-primary"></i>Profit Sharing</h3>
                                </div>
                                <div class="card-body p-3">
                                    <div id="profit-sharing-container">
                                        <p class="text-muted text-sm">Memuat data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <div class="card" style="border-left: 4px solid #ff9800;">
                                <div class="card-header border-0 pb-0">
                                    <h3 class="card-title" style="font-weight:600;"><i class="fas fa-balance-scale mr-2 text-warning"></i>Gain / Losses</h3>
                                </div>
                                <div class="card-body p-3">
                                    <div id="gain-losses-container">
                                        <p class="text-muted text-sm">Memuat data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right column: Recent reports -->
                <div class="col-12 col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                            <h3 class="card-title" style="font-weight: 600;"><i class="fas fa-file-invoice mr-2 text-muted"></i>Laporan Terbaru</h3>
                            <a href="{{ route('daily-reports.index') }}" class="btn btn-xs btn-link text-primary font-weight-bold" style="font-size: 12px; text-decoration: none;">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0 pt-2">
                            <ul class="list-group list-group-flush">
                                @foreach ($recent_reports as $report)
                                    <li class="list-group-item d-flex justify-content-between align-items-center" style="border: 0; border-bottom: 1px solid #f1f5f9; padding: 14px 20px;">
                                        <div>
                                            <div class="font-weight-600 text-dark" style="font-size: 14px;">{{ $report->shop->kode }} {{ $report->shop->nama }}</div>
                                            <div class="text-muted" style="font-size: 11px; margin-top: 2px;">{{ \Carbon\Carbon::parse($report->created_at)->format('d F Y H:i') }}</div>
                                        </div>
                                        <span class="badge-success-premium">Selesai</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@push('script')
    <script>
        var ctxSalesChart = document.getElementById('salesChart').getContext('2d');
        var salesChart;
        var omsetDonutChart, labaDonutChart;

        const basePalette = ['#00796B', '#2e7d32', '#f57c00', '#d32f2f', '#673ab7', '#1976D2', '#C2185B', '#0097A7', '#FBC02D', '#8D6E63', '#455A64', '#0288D1'];
        function getColor(idx) {
            return basePalette[idx % basePalette.length];
        }

        function showSalesChart(data) {
            var filter = $('input[name=filter]').val();
            if (salesChart) salesChart.destroy();
            const datasets = data.datasets.map((ds, idx) => {
                const color = getColor(idx);
                return {
                    label: ds.label, data: ds.data,
                    borderColor: color, backgroundColor: color + '15',
                    borderWidth: 3, tension: 0.4,
                    pointRadius: 4, pointHoverRadius: 6,
                    pointBackgroundColor: '#ffffff', pointBorderColor: color,
                    pointBorderWidth: 2, fill: true
                };
            });
            salesChart = new Chart(ctxSalesChart, {
                type: 'line',
                data: { labels: data.labels, datasets: datasets },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        y: { min: 0, grid: { drawBorder: false, color: '#f1f5f9' }, ticks: { color: '#64748b', font: { family: "'Inter', sans-serif" } } },
                        x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: "'Inter', sans-serif" } } }
                    },
                    plugins: { legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true, font: { family: "'Inter', sans-serif", size: 12 } } } }
                }
            });
            $('.btn-filter').removeClass('btn-primary').addClass('btn-link');
            $(`.filter-${filter}`).removeClass('btn-link').addClass('btn-primary');
        }

        function showStocks(data) {
            let html = '';
            data.datasets[0].data.forEach((val, idx) => {
                let shopName = data.labels[idx];
                let percentage = Math.min((val / 3500) * 100, 100);
                let color = getColor(idx);
                html += `
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="font-weight-600 text-secondary d-flex align-items-center" style="font-size: 13px;">
                            <span class="dot mr-2" style="background-color: ${color};"></span> ${shopName}
                        </span>
                        <span class="font-weight-700" style="font-size: 13px;">${formatNumber(val, 0)} &ell;</span>
                    </div>
                    <div class="progress" style="height: 6px; border-radius: 3px; background-color: #edf2f7;">
                        <div class="progress-bar" style="width: ${percentage}%; background-color: ${color}; border-radius: 3px;"></div>
                    </div>
                </div>`;
            });
            $('#stock-container').html(html);
        }

        function showActivePrices(summaries) {
            let html = '';
            summaries.forEach((s, idx) => {
                const color = getColor(idx);
                const effAt = s.effective_at ? new Date(s.effective_at).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'}) : '—';
                html += `
                <div class="col-12 col-sm-6 col-md-4 col-lg-2-4 mb-3">
                    <div class="card" style="border-top: 3px solid ${color}; border-radius: 10px;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="dot mr-2" style="background-color:${color};"></span>
                                <strong style="font-size:13px;">${s.shop.nama}</strong>
                            </div>
                            <div class="d-flex justify-content-between" style="font-size:12px; color:#555;">
                                <span>Harga Jual</span>
                                <span class="font-weight-700 text-success">${formatCurrency(s.harga_jual_aktif, 0)}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2" style="font-size:12px; color:#555;">
                                <span>Harga Beli</span>
                                <span class="font-weight-700">${formatCurrency(s.harga_beli_aktif, 0)}</span>
                            </div>
                            <div class="border-top pt-2 mt-1">
                                <div class="d-flex justify-content-between" style="font-size:12px; color:#555;">
                                    <span title="Belum Disetorkan Operator"><i class="fas fa-exclamation-circle text-danger mr-1"></i>Belum Disetor</span>
                                    <span class="font-weight-700 text-danger">${formatCurrency(s.belum_disetorkan, 0)}</span>
                                </div>
                                <div class="d-flex justify-content-between" style="font-size:12px; color:#555;">
                                    <span title="Telah Disetor ke Kolektan (Bulan Ini)"><i class="fas fa-wallet text-warning mr-1"></i>Kolektan</span>
                                    <span class="font-weight-700 text-warning">${formatCurrency(s.total_setor_kolektan, 0)}</span>
                                </div>
                            </div>
                            <div class="text-muted mt-2" style="font-size:10px;">Harga berlaku sejak: ${effAt}</div>
                        </div>
                    </div>
                </div>`;
            });
            $('#active-price-row').html(html);
        }

        function showDonutCharts(summaries) {
            const labels  = summaries.map(s => s.shop.nama);
            const omsetData = summaries.map(s => s.jumlah_penjualan_bersih_rp || 0);
            const labaData  = summaries.map(s => s.laba_kotor || 0);
            const colors    = labels.map((l, idx) => getColor(idx));

            if (omsetDonutChart) omsetDonutChart.destroy();
            omsetDonutChart = new Chart(document.getElementById('omsetDonut').getContext('2d'), {
                type: 'doughnut',
                data: { labels, datasets: [{ data: omsetData, backgroundColor: colors, borderWidth: 2, borderColor: '#ffffff' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 10, usePointStyle: true, font: { family: "'Inter', sans-serif", size: 11 } } } } }
            });

            if (labaDonutChart) labaDonutChart.destroy();
            labaDonutChart = new Chart(document.getElementById('labaDonut').getContext('2d'), {
                type: 'doughnut',
                data: { labels, datasets: [{ data: labaData, backgroundColor: colors, borderWidth: 2, borderColor: '#ffffff' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 10, usePointStyle: true, font: { family: "'Inter', sans-serif", size: 11 } } } } }
            });
        }

        function showProfitSharing(summaries, totals) {
            let html = `
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted" style="font-size:12px;">Laba Bersih Total</span>
                    <strong class="text-dark" style="font-size:13px;">${formatCurrency(totals.laba_bersih, 0)}</strong>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted" style="font-size:12px;"><i class="fas fa-building mr-1"></i>Corporate Share</span>
                    <strong class="text-primary" style="font-size:13px;">${formatCurrency(totals.corporate_share, 0)}</strong>
                </div>
                <div class="d-flex justify-content-between pb-1">
                    <span class="text-muted" style="font-size:12px;"><i class="fas fa-users mr-1"></i>Investor Share</span>
                    <strong class="text-success" style="font-size:13px;">${formatCurrency(totals.investor_share, 0)}</strong>
                </div>`;
            $('#profit-sharing-container').html(html);
        }

        function showGainLosses(summaries, totals) {
            const gainLossVolume = totals.losses_gain_vol || 0;
            const gainLossRp     = totals.losses_gain_rp  || 0;
            const isGain  = gainLossVolume >= 0;
            const badge   = isGain ? 'success' : 'danger';
            const label   = isGain ? 'Gain'    : 'Losses';
            const icon    = isGain ? 'fa-arrow-up' : 'fa-arrow-down';

            let outletHtml = summaries.map(s => {
                const vol = s.total_losses_gain_vol || 0;
                const isG = vol >= 0;
                return `
                <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid #f1f5f9; font-size:12px;">
                    <span class="text-muted">${s.shop.nama}</span>
                    <span class="font-weight-700 text-${isG ? 'success':'danger'}">
                        <i class="fas fa-${isG ? 'arrow-up':'arrow-down'} mr-1" style="font-size:9px;"></i>
                        ${Math.abs(vol).toFixed(2)} ℓ
                    </span>
                </div>`;
            }).join('');

            let html = `
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted" style="font-size:12px;">Total ${label}</span>
                    <span class="badge badge-${badge} px-2 py-1" style="font-size:12px;">
                        <i class="fas ${icon} mr-1"></i>${Math.abs(gainLossVolume).toFixed(2)} ℓ
                    </span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted" style="font-size:12px;">Estimasi Rupiah</span>
                    <strong class="text-${badge === 'danger' ? 'danger':'success'}" style="font-size:13px;">${formatCurrency(Math.abs(gainLossRp), 0)}</strong>
                </div>
                <div class="mt-1">${outletHtml}</div>`;
            $('#gain-losses-container').html(html);
        }

        function getData() {
            var shop_id = $('select[name=shop_id]').val();
            var filter  = $('input[name=filter]').val();

            $.ajax({
                url: "{{ route('dashboard') }}",
                method: 'GET',
                data: { filter, shop_id },
                success: function(data) {
                    showSalesChart(data.sales);
                    showStocks(data.stocks);
                    showDonutCharts(data.summaries);
                    showActivePrices(data.summaries);
                    showProfitSharing(data.summaries, data.totals);
                    showGainLosses(data.summaries, data.totals);

                    const t = data.totals;
                    const gl = t.losses_gain_vol || 0;
                    const isGain = gl >= 0;
                    const glColor = isGain ? '#2ed573' : '#ff4d5e';
                    const glLabel = isGain ? 'Gain' : 'Losses';
                    const glIcon  = isGain ? 'fa-arrow-up' : 'fa-arrow-down';

                    let html_summary = `
                        <div class="summary-card style-gold">
                            <div class="summary-icon-wrapper"><i class="fas fa-upload"></i></div>
                            <div class="summary-info">
                                <span class="summary-label">Penjualan Bersih</span>
                                <span class="summary-value">${formatCurrency(t.penjualan_bersih, 0)}</span>
                                <span class="summary-subtext">Bulan Berjalan</span>
                            </div>
                        </div>
                        <div class="summary-card style-dark">
                            <div class="summary-icon-wrapper"><i class="fas fa-download"></i></div>
                            <div class="summary-info">
                                <span class="summary-label">Pembelian</span>
                                <span class="summary-value">${formatCurrency(t.pembelian, 0)}</span>
                                <span class="summary-subtext">Bulan Berjalan</span>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon-wrapper bg-green-dark"><i class="fas fa-wallet"></i></div>
                            <div class="summary-info">
                                <span class="summary-label">Laba Kotor</span>
                                <span class="summary-value">${formatCurrency(t.laba_kotor, 0)}</span>
                                <span class="summary-subtext">Bulan Berjalan</span>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon-wrapper bg-amber-dark"><i class="fas fa-chart-bar"></i></div>
                            <div class="summary-info">
                                <span class="summary-label">Volume Penjualan</span>
                                <span class="summary-value">${formatNumber(t.volume, 2)} ℓ</span>
                                <span class="summary-subtext">Total Volume</span>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon-wrapper" style="background:${glColor};"><i class="fas ${glIcon}"></i></div>
                            <div class="summary-info">
                                <span class="summary-label">${glLabel}</span>
                                <span class="summary-value" style="color:${glColor};">${Math.abs(gl).toFixed(2)} ℓ</span>
                                <span class="summary-subtext">${formatCurrency(Math.abs(t.losses_gain_rp||0), 0)}</span>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon-wrapper" style="background:#2196f3;"><i class="fas fa-handshake"></i></div>
                            <div class="summary-info">
                                <span class="summary-label">Laba Bersih</span>
                                <span class="summary-value">${formatCurrency(t.laba_bersih, 0)}</span>
                                <span class="summary-subtext">Corporate + Investor</span>
                            </div>
                        </div>
                    `;
                    $('#summary').html(html_summary);
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        }

        $(document).ready(function() {
            $(".filter-week").on('click', function() { $('input[name=filter]').val('week').trigger('change'); });
            $(".filter-day").on('click',  function() { $('input[name=filter]').val('day').trigger('change');  });
            $(".filter-month").on('click',function() { $('input[name=filter]').val('month').trigger('change');});

            $('select[name=shop_id], input[name=filter]').on('change', function() { getData(); });

            getData();

            @if(in_array(Auth::user()->role, ['super_admin', 'admin']))
            loadPriceForms();
            @endif
        });

        // ===== KELOLA HARGA: Load current price per outlet as editable form =====
        function loadPriceForms() {
            $.getJSON('/dashboard/prices', function(shops) {
                const today = new Date().toISOString().split('T')[0];
                const now   = new Date().toTimeString().slice(0,5);

                let html = `<div class="row" id="price-form-rows">`;

                shops.forEach((s, idx) => {
                    const color = getColor(idx);
                    const spread = s.harga_jual - s.harga_beli;
                    html += `
                    <div class="col-12 col-md-6 col-lg-4 mb-3" id="price-col-${s.shop_id}">
                        <div class="price-outlet-card" style="border:1px solid #e2e8f0;border-radius:10px;padding:14px;background:#fff;border-left:4px solid ${color};">
                            <div class="d-flex align-items-center mb-2">
                                <span style="width:9px;height:9px;border-radius:50%;background:${color};display:inline-block;margin-right:6px;"></span>
                                <strong style="font-size:13px;color:#1e293b;">${s.shop_kode} — ${s.shop_nama}</strong>
                                <span class="ml-auto badge badge-pill" style="background:#e8f5e9;color:#2e7d32;font-size:10px;">
                                    Margin: +${formatNumber(spread)}
                                </span>
                            </div>
                            <div class="row no-gutters" style="gap:0;">
                                <div class="col-6 pr-1">
                                    <label style="font-size:11px;color:#64748b;margin-bottom:2px;">Harga Beli (Rp)</label>
                                    <input type="number" class="form-control form-control-sm price-beli-input"
                                           id="harga_beli_${s.shop_id}" value="${s.harga_beli}"
                                           data-shop="${s.shop_id}" placeholder="Harga Beli">
                                </div>
                                <div class="col-6 pl-1">
                                    <label style="font-size:11px;color:#64748b;margin-bottom:2px;">Harga Jual (Rp)</label>
                                    <input type="number" class="form-control form-control-sm price-jual-input"
                                           id="harga_jual_${s.shop_id}" value="${s.harga_jual}"
                                           data-shop="${s.shop_id}" placeholder="Harga Jual">
                                </div>
                                <div class="col-6 pr-1 mt-2">
                                    <label style="font-size:11px;color:#64748b;margin-bottom:2px;">Tanggal Berlaku</label>
                                    <input type="date" class="form-control form-control-sm price-tanggal-input"
                                           id="tanggal_${s.shop_id}" value="${today}">
                                </div>
                                <div class="col-6 pl-1 mt-2">
                                    <label style="font-size:11px;color:#64748b;margin-bottom:2px;">Jam Berlaku</label>
                                    <input type="time" class="form-control form-control-sm price-jam-input"
                                           id="jam_${s.shop_id}" value="${now}">
                                </div>
                            </div>
                            <div class="mt-2 d-flex align-items-center">
                                <button class="btn btn-sm btn-success btn-save-price flex-grow-1"
                                        data-shop="${s.shop_id}" data-nama="${s.shop_nama}"
                                        style="font-size:12px;font-weight:600;">
                                    <i class="fas fa-save mr-1"></i>Simpan Perubahan
                                </button>
                                <span class="ml-2 save-status-${s.shop_id}" style="font-size:11px;display:none;"></span>
                            </div>
                        </div>
                    </div>`;
                });

                html += `</div>
                <div class="mt-1 text-muted" style="font-size:11px;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Setiap perubahan harga akan otomatis tersimpan di histori dan berlaku mulai jam yang ditentukan. Laporan operator yang masuk setelah jam berlaku akan menggunakan harga baru.
                </div>`;

                $('#price-form-container').html(html);

                // Live spread badge update
                $(document).on('input', '.price-beli-input, .price-jual-input', function() {
                    const shopId = $(this).data('shop');
                    const beli = parseFloat($('#harga_beli_' + shopId).val()) || 0;
                    const jual = parseFloat($('#harga_jual_' + shopId).val()) || 0;
                    const spread = jual - beli;
                    const card   = $(this).closest('.price-outlet-card');
                    card.find('.badge').text('Margin: +' + formatNumber(spread));
                });

                // Save handler
                $(document).on('click', '.btn-save-price', function() {
                    const shopId  = $(this).data('shop');
                    const shopNama= $(this).data('nama');
                    const hargaBeli  = parseFloat($('#harga_beli_' + shopId).val());
                    const hargaJual  = parseFloat($('#harga_jual_' + shopId).val());
                    const tanggal    = $('#tanggal_' + shopId).val();
                    const jam        = $('#jam_' + shopId).val();
                    const btn        = $(this);
                    const status     = $('.save-status-' + shopId);

                    if (!hargaBeli || !hargaJual || hargaJual <= hargaBeli) {
                        Swal.fire('Validasi Gagal', 'Harga jual harus lebih besar dari harga beli.', 'warning');
                        return;
                    }

                    Swal.fire({
                        title: `Ubah Harga ${shopNama}?`,
                        html: `Harga Beli: <b>Rp ${formatNumber(hargaBeli)}</b><br>Harga Jual: <b>Rp ${formatNumber(hargaJual)}</b><br>Berlaku: <b>${tanggal} ${jam}</b>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#00796B',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Batal'
                    }).then(result => {
                        if (!result.isConfirmed) return;

                        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

                        $.ajax({
                            url: '/dashboard/prices',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                shop_id: shopId,
                                harga_beli: hargaBeli,
                                harga_jual: hargaJual,
                                tanggal_berlaku: tanggal,
                                jam_berlaku: jam
                            },
                            success: function(res) {
                                btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Tersimpan!');
                                status.show().css('color','#2e7d32').text('✓ Berhasil');
                                setTimeout(() => {
                                    btn.html('<i class="fas fa-save mr-1"></i>Simpan Perubahan');
                                    status.hide();
                                }, 3000);
                                // Refresh dashboard data
                                getData();
                                Swal.fire({
                                    toast: true, position: 'top-end', icon: 'success',
                                    title: res.message,
                                    showConfirmButton: false, timer: 3000
                                });
                            },
                            error: function(xhr) {
                                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Perubahan');
                                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                                Swal.fire('Gagal', msg, 'error');
                            }
                        });
                    });
                });
            });
        }
    </script>

    <style>
        .col-lg-2-4 { flex: 0 0 20%; max-width: 20%; }
        @media (max-width: 991px) { .col-lg-2-4 { flex: 0 0 50%; max-width: 50%; } }
        @media (max-width: 575px) { .col-lg-2-4 { flex: 0 0 100%; max-width: 100%; } }
        .price-outlet-card { transition: box-shadow .2s; }
        .price-outlet-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,0.08); }
        .price-outlet-card input.form-control-sm { font-size: 13px; font-weight: 600; }
    </style>
@endpush

