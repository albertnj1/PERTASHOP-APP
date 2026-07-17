@extends('layouts.app')

@push('style')
<style>
    .transition-hover {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .transition-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 20px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.03) !important;
    }
    .bg-light-primary {
        background-color: #e0f2fe;
        color: #0369a1;
    }
    .bg-light-info {
        background-color: #e0f7fa;
        color: #00838f;
    }
    .bg-light-danger {
        background-color: #fee2e2;
        color: #b91c1c;
    }
    .bg-light-success {
        background-color: #dcfce7;
        color: #15803d;
    }
</style>
@endpush

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-4 align-items-center mt-2">
                <div class="col-12">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle p-2 mr-3 shadow-sm d-flex justify-content-center align-items-center" style="width: 54px; height: 54px; background: linear-gradient(135deg, #0ea5e9, #2563eb) !important;">
                            <i class="fas fa-gas-pump" style="font-size: 20px;"></i>
                        </div>
                        <div>
                            <h3 class="font-weight-bold mb-0 text-dark" style="letter-spacing: -0.5px;">{{ Auth::user()->operator->shop->nama }}</h3>
                            <p class="text-muted mb-0" style="font-size: 14px; font-weight: 500;"><i class="fas fa-hashtag mr-1" style="font-size: 11px;"></i>{{ Auth::user()->operator->shop->kode }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="row mb-4">
                <div class="col-6 col-sm-6 col-xl mb-3 px-2">
                    <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid #10b981 !important; border-radius: 12px;">
                        <div class="card-body p-2 p-sm-3">
                            <p class="text-muted mb-1 text-xs font-weight-600"><i class="fas fa-gas-pump mr-1 text-success"></i>Sisa Stok</p>
                            <h4 class="font-weight-bold text-dark mb-0" style="font-size: 1.1rem;"><span class="number">{{ $stok_akhir }}</span> <small class="text-muted" style="font-size: 12px;">&ell;</small></h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-6 col-xl mb-3 px-2">
                    <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid #3b82f6 !important; border-radius: 12px;">
                        <div class="card-body p-2 p-sm-3">
                            <p class="text-muted mb-1 text-xs font-weight-600"><i class="fas fa-tags mr-1 text-primary"></i>Harga Jual</p>
                            <h4 class="font-weight-bold text-dark mb-0" style="font-size: 1.1rem;"><span class="currency">{{ $harga_jual }}</span> <small class="text-muted" style="font-size: 12px;">/ &ell;</small></h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-6 col-xl mb-3 px-2">
                    <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid #06b6d4 !important; border-radius: 12px;">
                        <div class="card-body p-2 p-sm-3">
                            <p class="text-muted mb-1 text-xs font-weight-600"><i class="fas fa-chart-bar mr-1 text-info"></i>Penj. Hari Ini</p>
                            <h4 class="font-weight-bold text-dark mb-0" style="font-size: 1.1rem;"><span class="number">{{ $volume_penjualan }}</span> <small class="text-muted" style="font-size: 12px;">&ell;</small></h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-6 col-xl mb-3 px-2">
                    <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid #ef4444 !important; border-radius: 12px;">
                        <div class="card-body p-2 p-sm-3">
                            <p class="text-muted mb-1 text-xs font-weight-600"><i class="fas fa-money-bill-wave mr-1 text-danger"></i>Belum Disetor</p>
                            <h4 class="font-weight-bold text-dark mb-0 currency" style="font-size: 1.1rem;">{{ $belum_disetorkan }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-xl mb-3 px-2">
                    <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid #f59e0b !important; border-radius: 12px;">
                        <div class="card-body p-2 p-sm-3">
                            <p class="text-muted mb-1 text-xs font-weight-600"><i class="fas fa-wallet mr-1 text-warning"></i>Kolektan (Bulan Ini)</p>
                            <h4 class="font-weight-bold text-dark mb-0 currency" style="font-size: 1.1rem;">{{ $total_setor_kolektan }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white border-0 pb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title" style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">
                                    <i class="fas fa-chart-line mr-2 text-muted"></i>Grafik Penjualan
                                </h3>
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
            </div>
            <div class="row">

                <div class="col-lg-3 col-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm transition-hover" style="border-radius: 16px;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                            <div class="bg-light-primary rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download"
                                    width="36" height="36" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                                    <path d="M7 11l5 5l5 -5"></path>
                                    <path d="M12 4l0 12"></path>
                                </svg>
                            </div>
                            <h5 class="font-weight-bold text-gray-800 mb-1" style="font-size: 1rem;">Penerimaan</h5>
                            <p class="text-muted text-xs mb-0">Catat BBM datang dari tangki supply</p>
                            <a href="{{ route('incomings.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm transition-hover" style="border-radius: 16px;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                            <div class="bg-light-info rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-test-pipe-2"
                                    width="36" height="36" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M15 3v15a3 3 0 0 1 -6 0v-15"></path>
                                    <path d="M9 12h6"></path>
                                    <path d="M8 3h8"></path>
                                </svg>
                            </div>
                            <h5 class="font-weight-bold text-gray-800 mb-1" style="font-size: 1rem;">Test Pump</h5>
                            <p class="text-muted text-xs mb-0">Catat hasil tera volume BBM</p>
                            <a href="{{ route('test-pumps.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm transition-hover" style="border-radius: 16px;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                            <div class="bg-light-danger rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt-2"
                                    width="36" height="36" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2">
                                    </path>
                                    <path d="M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5m2 0v1.5m0 -9v1.5">
                                    </path>
                                </svg>
                            </div>
                            <h5 class="font-weight-bold text-gray-800 mb-1" style="font-size: 1rem;">Pengeluaran</h5>
                            <p class="text-muted text-xs mb-0">Catat biaya operasional harian</p>
                            <a href="{{ route('spendings.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm transition-hover" style="border-radius: 16px;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                            <div class="bg-light-success rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-report"
                                    width="36" height="36" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M8 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h5.697"></path>
                                    <path d="M18 14v4h4"></path>
                                    <path d="M18 11v-4a2 2 0 0 0 -2 -2h-2"></path>
                                    <path d="M8 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z">
                                    </path>
                                    <path d="M18 18m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                                    <path d="M8 11h4"></path>
                                    <path d="M8 15h3"></path>
                                </svg>
                            </div>
                            <h5 class="font-weight-bold text-gray-800 mb-1" style="font-size: 1rem;">Laporan Harian</h5>
                            <p class="text-muted text-xs mb-0">Entri data & setoran per shift</p>
                            <a href="{{ route('daily-reports.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm transition-hover" style="border-radius: 16px;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                            <div class="bg-light-warning rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-report-money"
                                    width="36" height="36" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"></path>
                                    <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"></path>
                                    <path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5"></path>
                                    <path d="M12 17v1m0 -8v1"></path>
                                </svg>
                            </div>
                            <h5 class="font-weight-bold text-gray-800 mb-1" style="font-size: 1rem;">Perubahan Harga</h5>
                            <p class="text-muted text-xs mb-0">Catat harga baru dari Pertamina</p>
                            <a href="{{ route('prices.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var ctxSalesChart = document.getElementById('salesChart').getContext('2d');
        var salesChart;

        const outletColors = {
            'Kalitapen':  '#00796B',
            'Kalibenda':  '#2e7d32',
            'Pageralang': '#f57c00',
            'Gumelar':    '#d32f2f',
            'Kemutug Lor':'#673ab7'
        };

        function showSalesChart(data) {
            var filter = $('input[name=filter]').val();
            if (salesChart) salesChart.destroy();
            const datasets = data.datasets.map((ds) => {
                const color = outletColors[ds.label] || '#00796B';
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

        function getData() {
            var filter = $('input[name=filter]').val();
            $.ajax({
                type: "GET",
                url: "/",
                data: { filter: filter },
                success: function(response) {
                    showSalesChart(response.sales);
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

            $('input[name=filter]').on('change', function() { getData(); });

            getData();
        });
    </script>
@endpush
