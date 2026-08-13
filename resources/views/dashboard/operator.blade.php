@extends('layouts._new_admin')

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
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="page-title mb-1">{{ Auth::user()->operator?->shop?->nama ?? 'Dashboard Operator' }}</h1>
            <p class="text-muted mb-0" style="font-size: 13px;">Kode Outlet: {{ Auth::user()->operator?->shop?->kode ?? '-' }}</p>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="summary-grid">
        <div class="metric-card pos">
            <div class="metric-label">Sisa Stok</div>
            <div class="metric-value pos">{{ number_format($stok_akhir, 0, ',', '.') }} ℓ</div>
            <div class="metric-sub">Berdasarkan Penerimaan / Laporan</div>
        </div>
        <div class="metric-card neutral">
            <div class="metric-label">Harga Jual</div>
            <div class="metric-value">Rp {{ number_format($harga_jual, 0, ',', '.') }}</div>
            <div class="metric-sub">Per liter</div>
        </div>
        <div class="metric-card neutral">
            <div class="metric-label">Penjualan Hari Ini</div>
            <div class="metric-value">{{ number_format($volume_penjualan, 0, ',', '.') }} ℓ</div>
            <div class="metric-sub">Volume</div>
        </div>
        <div class="metric-card {{ $belum_disetorkan < 0 ? 'neg' : 'pos' }}">
            <div class="metric-label">Belum Disetor</div>
            <div class="metric-value {{ $belum_disetorkan < 0 ? 'neg' : 'pos' }}">Rp {{ number_format($belum_disetorkan, 0, ',', '.') }}</div>
            <div class="metric-sub">Tagihan berjalan</div>
        </div>
        <div class="metric-card neutral">
            <div class="metric-label">Kolektan (Bulan Ini)</div>
            <div class="metric-value">Rp {{ number_format($total_setor_kolektan, 0, ',', '.') }}</div>
            <div class="metric-sub">Total setoran kolektan</div>
        </div>
    </div>

    {{-- WIDGET FITUR 2 & 3: Estimasi Gaji Live & Saldo Tabungan --}}
    <div class="row cols-1-1 mb-4">
        <div>
            <div class="panel h-100 mb-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="status-pill" style="background:#fef3c7; color:#b45309; font-size: 11px;">LIVE ESTIMATE</span>
                        <div class="panel-title mt-1">Estimasi Gaji Bulan Ini (Real-time)</div>
                    </div>
                    <div class="text-right">
                        <div style="font-size: 11px; color: var(--muted);">Proyeksi Bersih (THP)</div>
                        <div style="font-size: 20px; font-weight: 800; color: var(--green);">Rp {{ number_format($estimasi_thp, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div style="background: var(--page-bg); border-radius: 10px; height: 6px; overflow: hidden;" class="mb-3">
                    <div style="width: 75%; background: var(--green); height: 100%;"></div>
                </div>
                <div class="d-flex justify-content-between pt-2" style="border-top: 1px solid var(--border); font-size: 12px;">
                    <div>
                        <span class="text-muted">Estimasi Kotor:</span>
                        <strong class="ml-1">Rp {{ number_format($estimasi_gaji_kotor, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <span class="text-muted">Potensi Potongan Kurang Setor:</span>
                        <strong class="ml-1" style="color: var(--red);">- Rp {{ number_format($estimasi_kurang_setoran, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="panel h-100 mb-0 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="panel-title">Saldo Tabungan Saya</div>
                        <div class="text-muted" style="font-size: 12px;">Terakumulasi dari potongan gaji</div>
                    </div>
                    <span class="status-pill" style="background:#dcfce7; color:#15803d;">Aktif</span>
                </div>
                <div class="my-2">
                    <div style="font-size: 24px; font-weight: 800; color: var(--blue);">Rp {{ number_format($saldo_tabungan, 0, ',', '.') }}</div>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm btn-block font-weight-bold" style="border-radius: 8px;" data-toggle="modal" data-target="#modalTukarShift">
                        Ajukan Tukar Shift
                    </button>
                </div>
            </div>
        </div>
    </div>

            {{-- MODAL FITUR 1: Pengajuan Tukar Shift Mandiri Operator --}}
            <div class="modal fade" id="modalTukarShift" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
                        <div class="modal-header" style="background: var(--page-bg); border-bottom: 1px solid var(--border);">
                            <h5 class="modal-title font-weight-bold" style="font-size: 15px;">Pengajuan Tukar Shift Mandiri</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <form action="{{ route('shift-schedules.request-swap') }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <p class="text-muted small">Pilih jadwal shift Anda dan rekan pengganti dari toko yang sama. Pengajuan akan masuk ke antrian persetujuan Admin.</p>
                                
                                <div class="form-group">
                                    <label class="font-weight-bold">Pilih Jadwal Shift Anda</label>
                                    @php
                                        $opId = Auth::user()->operator?->id ?? 0;
                                        $opShopId = Auth::user()->operator?->shop_id ?? 0;
                                        $mySchedules = \App\Models\ShiftSchedule::where('operator_id', $opId)
                                            ->where('tanggal', '>=', now()->toDateString())
                                            ->orderBy('tanggal', 'asc')
                                            ->get();
                                    @endphp
                                    <select name="shift_schedule_id" class="form-control" required>
                                        @forelse($mySchedules as $sch)
                                            <option value="{{ $sch->id }}">
                                                {{ $sch->tanggal->format('d M Y') }} — Shift Ke-{{ $sch->shift_ke }}
                                            </option>
                                        @empty
                                            <option value="">(Tidak ada jadwal shift mendatang)</option>
                                        @endforelse
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Pilih Rekan Pengganti</label>
                                    @php
                                        $coOperators = \App\Models\Operator::with('user')
                                            ->where('shop_id', $opShopId)
                                            ->where('id', '!=', $opId)
                                            ->get();
                                    @endphp
                                    <select name="operator_pengganti_id" class="form-control" required>
                                        @foreach($coOperators as $coOp)
                                            <option value="{{ $coOp->id }}">{{ $coOp->user?->name ?? 'Operator #'.$coOp->id }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Alasan Halangan</label>
                                    <select name="alasan" class="form-control" required>
                                        <option value="izin">Izin</option>
                                        <option value="sakit">Sakit</option>
                                        <option value="keperluan_pribadi">Keperluan Pribadi</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Catatan Keterangan Tambahan</label>
                                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Sakit demam, sudah janji tukar shift dengan Budi"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary btn-sm font-weight-bold">Kirim Pengajuan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="panel mb-4" style="margin-bottom: 24px;">
                <div class="panel-head">
                    <div class="panel-title">
                        <i class="fas fa-chart-line text-muted"></i>Grafik Penjualan
                    </div>
                    <input name="filter" type="text" value="day" hidden>
                    <div class="custom-dropdown" tabindex="0">
                        <div class="dropdown-selected" id="chartFilterText">
                            <span>Harian</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </div>
                        <div class="dropdown-options">
                            <div class="dropdown-item filter-day" style="padding:8px 12px; cursor:pointer;">Harian</div>
                            <div class="dropdown-item filter-week" style="padding:8px 12px; cursor:pointer;">Mingguan</div>
                            <div class="dropdown-item filter-month" style="padding:8px 12px; cursor:pointer;">Bulanan</div>
                        </div>
                    </div>
                </div>
                <div>
                    <canvas id="salesChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                </div>
            <div class="action-grid mb-4">
                <div>
                    <div class="card action-card h-100 border-0 shadow-sm transition-hover" style="border-radius: 16px; background: white;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-3 p-md-4">
                            <div class="icon-wrapper bg-light-primary rounded-circle d-flex align-items-center justify-content-center mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download"
                                    width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                                    <path d="M7 11l5 5l5 -5"></path>
                                    <path d="M12 4l0 12"></path>
                                </svg>
                            </div>
                            <h5 class="font-weight-bold text-gray-800 mb-1" style="font-size: 0.95rem;">Penerimaan</h5>
                            <p class="text-muted text-xs mb-0">Catat BBM datang</p>
                            <a href="{{ route('incomings.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="card action-card h-100 border-0 shadow-sm transition-hover" style="border-radius: 16px; background: white;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-3 p-md-4">
                            <div class="icon-wrapper bg-light-info rounded-circle d-flex align-items-center justify-content-center mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-test-pipe-2"
                                    width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M15 3v15a3 3 0 0 1 -6 0v-15"></path>
                                    <path d="M9 12h6"></path>
                                    <path d="M8 3h8"></path>
                                </svg>
                            </div>
                            <h5 class="font-weight-bold text-gray-800 mb-1" style="font-size: 0.95rem;">Test Pump</h5>
                            <p class="text-muted text-xs mb-0">Catat tera volume</p>
                            <a href="{{ route('test-pumps.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="card action-card h-100 border-0 shadow-sm transition-hover" style="border-radius: 16px; background: white;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-3 p-md-4">
                            <div class="icon-wrapper bg-light-danger rounded-circle d-flex align-items-center justify-content-center mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt-2"
                                    width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2">
                                    </path>
                                    <path d="M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5m2 0v1.5m0 -9v1.5">
                                    </path>
                                </svg>
                            </div>
                            <h5 class="font-weight-bold text-gray-800 mb-1" style="font-size: 0.95rem;">Pengeluaran</h5>
                            <p class="text-muted text-xs mb-0">Catat biaya operasional</p>
                            <a href="{{ route('spendings.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="card action-card h-100 border-0 shadow-sm transition-hover" style="border-radius: 16px; background: white;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-3 p-md-4">
                            <div class="icon-wrapper bg-light-success rounded-circle d-flex align-items-center justify-content-center mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-report"
                                    width="28" height="28" viewBox="0 0 24 24" stroke-width="2"
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
                            <h5 class="font-weight-bold text-gray-800 mb-1" style="font-size: 0.95rem;">Laporan Harian</h5>
                            <p class="text-muted text-xs mb-0">Entri data per shift</p>
                            <a href="{{ route('daily-reports.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="card action-card h-100 border-0 shadow-sm transition-hover" style="border-radius: 16px; background: white;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-3 p-md-4">
                            <div class="icon-wrapper bg-light-warning rounded-circle d-flex align-items-center justify-content-center mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-report-money"
                                    width="28" height="28" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"></path>
                                    <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"></path>
                                    <path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5"></path>
                                    <path d="M12 17v1m0 -8v1"></path>
                                </svg>
                            </div>
                            <h5 class="font-weight-bold text-gray-800 mb-1" style="font-size: 0.95rem;">Perubahan Harga</h5>
                            <p class="text-muted text-xs mb-0">Catat harga Pertamina</p>
                            <a href="{{ route('prices.index') }}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
            </div>
@endsection

@push('scripts')
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
            // Update active state in custom dropdown
            $('.dropdown-item').css('background', 'transparent');
            $(`.filter-${filter}`).css('background', 'rgba(59, 110, 165, 0.08)');
        }

        function getData() {
            var filter = $('input[name=filter]').val();
            $.ajax({
                type: "GET",
                url: "{{ route('dashboard') }}",
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
            $(".filter-week").on('click', function() { 
                $('input[name=filter]').val('week').trigger('change'); 
                $('#chartFilterText span').text('Mingguan');
                document.activeElement.blur();
            });
            $(".filter-day").on('click',  function() { 
                $('input[name=filter]').val('day').trigger('change');  
                $('#chartFilterText span').text('Harian');
                document.activeElement.blur();
            });
            $(".filter-month").on('click',function() { 
                $('input[name=filter]').val('month').trigger('change');
                $('#chartFilterText span').text('Bulanan');
                document.activeElement.blur();
            });

            $('input[name=filter]').on('change', function() { getData(); });

            getData();
        });
    </script>
@endpush
