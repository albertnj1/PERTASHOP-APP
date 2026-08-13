@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 align-items-center">
                <div class="col-12 col-md-6 mb-2 mb-md-0">
                    <h1 style="font-weight: 700; color: #0f172a; font-size: 1.8rem;">Pertashop Investasi</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Donut Charts -->
            <div class="row">
                <div class="col-12 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header border-0 pb-0">
                            <h3 class="card-title" style="font-weight: 600;"><i class="fas fa-chart-pie mr-2 text-muted"></i>Omset per Pertashop</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="omsetDonut" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header border-0 pb-0">
                            <h3 class="card-title" style="font-weight: 600;"><i class="fas fa-wallet mr-2 text-muted"></i>Laba Kotor per Pertashop</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="labaDonut" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Detail Chart Investor per Pertashop -->
            <div class="row mb-4">
                @foreach ($shops as $shop)
                    @if ($shop->investors->count() > 0)
                        <div class="col-md-6 mb-4 shop-chart-container" data-shop-id="{{ $shop->id }}">
                            <div class="card shadow-sm border-0 h-100" style="background: #f8f9fa;">
                                <div class="card-header border-0" style="background: #ffffff; border-bottom: 2px solid #3498db !important;">
                                    <h5 class="mb-0 text-primary font-weight-bold" style="font-size: 15px;"><i class="fas fa-gas-pump mr-2"></i>{{ $shop->nama }} ({{ $shop->kode }})</h5>
                                    <div class="text-muted mt-1" style="font-size: 12px;">Total Investasi: <strong class="text-dark">Rp {{ number_format($shop->total_investasi, 0, ',', '.') }}</strong></div>
                                </div>
                                <div class="card-body bg-white">
                                    <div class="row align-items-center">
                                        <div class="col-sm-5 text-center mb-3 mb-sm-0">
                                            <canvas id="investorChart-{{ $shop->id }}" style="height: 180px; max-height: 180px; width: 100%;"></canvas>
                                        </div>
                                        <div class="col-sm-7">
                                            <ul class="list-unstyled mb-0" style="font-size: 13px;">
                                                @php $colors = ['#00796B', '#2e7d32', '#f57c00', '#d32f2f', '#673ab7', '#1976D2', '#C2185B', '#0097A7', '#FBC02D', '#8D6E63']; @endphp
                                                @foreach ($shop->investors as $idx => $inv)
                                                    @php $c = $colors[$idx % count($colors)]; @endphp
                                                    <li class="mb-2 pb-2 @if(!$loop->last) border-bottom @endif">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="font-weight-600 text-dark">
                                                                <span class="dot mr-1" style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:{{ $c }};"></span> 
                                                                {{ $inv->user->name }}
                                                            </span>
                                                            <span class="badge badge-success px-2 py-1">{{ number_format($inv->pivot->persentase, 2) }}%</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 11px;">
                                                            <span>Nominal:</span>
                                                            <span class="font-weight-bold text-dark">Rp {{ number_format($inv->pivot->nominal, 0, ',', '.') }}</span>
                                                        </div>
                                                        @if ($inv->pivot->sub_investors)
                                                            @php $subData = json_decode($inv->pivot->sub_investors, true); @endphp
                                                            @if(isset($subData['is_hibah']) && $subData['is_hibah'])
                                                                <span class="badge badge-warning" style="font-size: 10px;">Saham Hibah</span>
                                                            @endif
                                                            @if(isset($subData['sub_name']) && $subData['sub_name'])
                                                                <div class="text-muted mt-1" style="font-size: 10px;">Sub: {{ $subData['sub_name'] }}</div>
                                                            @endif
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Daftar Pertashop -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-0 pb-0">
                            <h3 class="card-title" style="font-weight: 600;"><i class="fas fa-store mr-2 text-muted"></i>Daftar Pertashop Investasi</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped table-valign-middle">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Pertashop</th>
                                        <th>Alamat</th>
                                        <th>Badan Usaha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shops as $shop)
                                    <tr>
                                        <td>{{ $shop->kode }}</td>
                                        <td><strong>{{ $shop->nama }}</strong></td>
                                        <td>{{ $shop->alamat }}</td>
                                        <td>{{ $shop->corporation ? $shop->corporation->nama : '-' }}</td>
                                    </tr>
                                    @endforeach
                                    @if(count($shops) == 0)
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Belum ada investasi Pertashop.</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@push('script')
    <script>
        var omsetDonutChart, labaDonutChart;

        const basePalette = ['#00796B', '#2e7d32', '#f57c00', '#d32f2f', '#673ab7', '#1976D2', '#C2185B', '#0097A7', '#FBC02D', '#8D6E63', '#455A64', '#0288D1'];
        function getColor(idx) {
            return basePalette[idx % basePalette.length];
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

        function loadData() {
            $.ajax({
                url: "{{ route('dashboard') }}",
                method: 'GET',
                data: { filter: 'month', shop_id: '' },
                success: function(data) {
                    showDonutCharts(data.summaries);
                }
            });
        }

        $(document).ready(function() {
            loadData();

            // Render Donut Charts
            const chartPalette = ['#00796B', '#2e7d32', '#f57c00', '#d32f2f', '#673ab7', '#1976D2', '#C2185B', '#0097A7', '#FBC02D', '#8D6E63'];
            
            @foreach ($shops as $shop)
                @if ($shop->investors->count() > 0)
                    (function() {
                        const ctx = document.getElementById('investorChart-{{ $shop->id }}');
                        if (ctx) {
                            const labels = {!! json_encode($shop->investors->pluck('user.name')) !!};
                            const data = {!! json_encode($shop->investors->pluck('pivot.persentase')) !!};
                            const bgColors = labels.map((_, i) => chartPalette[i % chartPalette.length]);
                            
                            new Chart(ctx.getContext('2d'), {
                                type: 'doughnut',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        data: data,
                                        backgroundColor: bgColors,
                                        borderWidth: 2,
                                        borderColor: '#ffffff'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return ' ' + context.label + ': ' + context.raw + '%';
                                                }
                                            }
                                        }
                                    },
                                    cutout: '70%'
                                }
                            });
                        }
                    })();
                @endif
            @endforeach
        });
    </script>
@endpush
