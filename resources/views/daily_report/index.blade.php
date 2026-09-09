@extends('layouts.app')

@push('style')
<style>
    .btn-quick-filter {
        transition: all 0.2s ease-in-out;
        font-size: 12px;
        font-weight: 600;
        border-width: 1.5px;
    }
    .btn-quick-filter.active {
        background-color: #00529C !important;
        border-color: #00529C !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(0, 82, 156, 0.35) !important;
    }
    .btn-quick-filter:hover:not(.active) {
        background-color: #e0edfa;
        color: #00529C;
    }
    #table thead th {
        background-color: #f1f5f9;
        color: #1e293b;
        font-weight: 700;
        font-size: 12px;
        vertical-align: middle;
        text-align: center;
        border-bottom: 2px solid #cbd5e1;
    }
    .table td {
        vertical-align: middle !important;
        font-size: 12.5px;
    }
</style>
@endpush

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="font-weight-bold text-dark"><i class="fa fa-clipboard-list text-primary mr-2"></i>Laporan Harian</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Laporan Harian</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Card Filter QLola Banking Style -->
            <div class="card card-outline card-primary shadow-sm mb-3">
                <div class="card-header bg-white py-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-primary mr-2" style="font-size: 13px; padding: 6px 10px;">
                                <i class="fa fa-filter"></i>
                            </span>
                            <h3 class="card-title font-weight-bold mb-0 text-dark" style="font-size: 15px;">
                                Filter Periode & Transaksi
                            </h3>
                        </div>
                        <div class="text-muted small">
                            <i class="fa fa-info-circle text-info mr-1"></i> Format Filter Berdasarkan Tanggal Operasional Laporan
                        </div>
                    </div>
                </div>
                <div class="card-body py-3" style="background-color: #fbfcfe;">
                    <!-- Quick Filter Buttons -->
                    <div class="row align-items-center mb-3">
                        <div class="col-auto">
                            <span class="font-weight-bold text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                                <i class="fa fa-bolt text-warning mr-1"></i> Filter Cepat:
                            </span>
                        </div>
                        <div class="col">
                            <div class="btn-group btn-group-toggle flex-wrap" data-toggle="buttons">
                                <button type="button" class="btn btn-outline-primary btn-sm btn-quick-filter px-3 mr-1 mb-1 rounded-pill" data-period="today">
                                    <i class="fa fa-calendar-day mr-1"></i> 1 Hari (Hari Ini)
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-quick-filter px-3 mr-1 mb-1 rounded-pill" data-period="7days">
                                    <i class="fa fa-calendar-week mr-1"></i> 7 Hari
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-quick-filter px-3 mr-1 mb-1 rounded-pill" data-period="30days">
                                    <i class="fa fa-calendar-alt mr-1"></i> 30 Hari
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-quick-filter px-3 mr-1 mb-1 rounded-pill" data-period="60days">
                                    <i class="fa fa-calendar-alt mr-1"></i> 60 Hari
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-quick-filter px-3 mr-1 mb-1 rounded-pill font-weight-bold" data-period="90days" style="border-color: #00529C;">
                                    <i class="fa fa-calendar-check mr-1 text-primary"></i> 90 Hari (Maks)
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-quick-filter active px-3 mr-1 mb-1 rounded-pill" data-period="this_month">
                                    <i class="fa fa-calendar-alt mr-1"></i> Bulan Ini
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-quick-filter px-3 mr-1 mb-1 rounded-pill" data-period="last_month">
                                    <i class="fa fa-history mr-1"></i> Bulan Lalu
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Controls: Shop + Dates + Action Buttons -->
                    <div class="row align-items-end">
                        @if (Auth::user()->role == 'operator')
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label class="small font-weight-bold text-muted mb-1">Cabang / Pertashop</label>
                                <div class="form-control bg-light text-truncate font-weight-bold" style="border: 1px solid #ced4da;">
                                    <i class="fa fa-store-alt text-primary mr-1"></i>
                                    {{ Auth::user()->operator->shop->kode . ' ' . Auth::user()->operator->shop->nama }}
                                </div>
                                <input type="hidden" id="filter_shop_id" value="{{ Auth::user()->operator->shop_id }}">
                            </div>
                        @elseif(Auth::user()->role == 'admin')
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label class="small font-weight-bold text-muted mb-1">Cabang / Pertashop</label>
                                <div class="form-control bg-light text-truncate font-weight-bold" style="border: 1px solid #ced4da;">
                                    <i class="fa fa-store-alt text-primary mr-1"></i>
                                    {{ Auth::user()->admin->shop->kode . ' ' . Auth::user()->admin->shop->nama }}
                                </div>
                                <input type="hidden" id="filter_shop_id" value="{{ Auth::user()->admin->shop_id }}">
                            </div>
                        @else
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label class="small font-weight-bold text-muted mb-1">Pilih Cabang / Pertashop</label>
                                <select id="filter_shop_id" name="shop_id" class="form-control font-weight-bold" style="border: 1px solid #cbd5e1;">
                                    @foreach ($shops as $s)
                                        <option value="{{ $s->id }}" {{ (isset($selectedShopId) && $selectedShopId == $s->id) ? 'selected' : '' }}>
                                            {{ $s->kode . ' ' . $s->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="small font-weight-bold text-muted mb-1">Tanggal Mulai</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0"><i class="fa fa-calendar text-primary"></i></span>
                                </div>
                                <input type="date" id="start_date" name="start_date" class="form-control border-left-0 font-weight-bold" value="{{ $startDate ?? '' }}">
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="small font-weight-bold text-muted mb-1">Tanggal Selesai</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0"><i class="fa fa-calendar text-primary"></i></span>
                                </div>
                                <input type="date" id="end_date" name="end_date" class="form-control border-left-0 font-weight-bold" value="{{ $endDate ?? '' }}">
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="d-flex">
                                <button type="button" id="btn-filter" class="btn btn-primary font-weight-bold flex-fill mr-1 shadow-sm">
                                    <i class="fa fa-search mr-1"></i> Tampilkan
                                </button>
                                <button type="button" id="btn-reset" class="btn btn-outline-secondary" title="Reset ke Bulan Ini">
                                    <i class="fa fa-undo"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Action Strip: Active Filter Badge & Export Buttons -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap mt-2 pt-2 border-top">
                        <div class="text-muted small py-1">
                            <span class="badge badge-light border px-2 py-1">
                                <i class="fa fa-store text-primary mr-1"></i> Cabang: <strong id="lbl-active-shop">{{ $shop->nama ?? 'Semua' }}</strong>
                            </span>
                            <span class="badge badge-light border px-2 py-1 ml-1">
                                <i class="fa fa-calendar-alt text-success mr-1"></i> Periode: <strong id="lbl-active-period">-</strong>
                            </span>
                        </div>

                        <div class="d-flex align-items-center flex-wrap py-1">
                            <span class="font-weight-bold text-muted small mr-2 d-none d-md-inline">Ekspor Data:</span>
                            <a id="btn-export-excel" href="#" class="btn btn-sm btn-success font-weight-bold mr-2 shadow-sm" title="Unduh data sesuai filter ke file Excel (.xlsx)">
                                <i class="fa fa-file-excel mr-1"></i> Unduh Excel
                            </a>
                            <a id="btn-export-pdf" href="#" target="_blank" class="btn btn-sm btn-danger font-weight-bold shadow-sm" title="Unduh data sesuai filter ke format PDF">
                                <i class="fa fa-file-pdf mr-1"></i> Unduh PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card card-outline card-secondary shadow-sm">
                <div class="card-header bg-white py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title font-weight-bold mb-0 text-dark">
                            <i class="fa fa-table text-secondary mr-1"></i> Rekapitulasi Data Operasional Harian
                        </h3>
                        @if (Auth::user()->role == 'operator')
                            <a href="{{ route('daily-reports.create') }}" class="btn btn-sm btn-primary font-weight-bold shadow-sm">
                                <i class="fa fa-plus mr-1"></i> Tambah Laporan Harian
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0 p-md-3">
                    <div class="table-responsive">
                        <table id="table" class="table table-bordered table-hover table-striped w-100">
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            // Pastikan preloader langsung tertutup dengan aman
            setTimeout(function() {
                var $preloader = $('.preloader');
                if ($preloader.length) {
                    $preloader.css('height', 0);
                    $preloader.children().hide();
                }
            }, 100);

            // Helper tanggal YYYY-MM-DD
            function getFormattedDate(d) {
                let year = d.getFullYear();
                let month = String(d.getMonth() + 1).padStart(2, '0');
                let day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function formatDateDisplay(dStr) {
                if (!dStr) return '-';
                try {
                    let parts = dStr.split('-');
                    if (parts.length === 3) {
                        let months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                        return parts[2] + ' ' + (months[parseInt(parts[1], 10) - 1] || parts[1]) + ' ' + parts[0];
                    }
                } catch (e) {}
                return dStr;
            }

            // Update URL tautan Export Excel & PDF agar selalu sinkron dengan filter aktif
            function updateExportLinks() {
                try {
                    let shopId = $('#filter_shop_id').val();
                    let start = $('#start_date').val();
                    let end = $('#end_date').val();

                    let excelUrl = "{{ route('daily-reports.export-excel') }}?shop_id=" + encodeURIComponent(shopId || '') + "&start_date=" + encodeURIComponent(start || '') + "&end_date=" + encodeURIComponent(end || '');
                    let pdfUrl = "{{ route('daily-reports.export-pdf') }}?shop_id=" + encodeURIComponent(shopId || '') + "&start_date=" + encodeURIComponent(start || '') + "&end_date=" + encodeURIComponent(end || '');

                    $('#btn-export-excel').attr('href', excelUrl);
                    $('#btn-export-pdf').attr('href', pdfUrl);

                    // Update teks info filter aktif
                    let shopText = '';
                    if ($('#filter_shop_id').is('select')) {
                        shopText = $('#filter_shop_id option:selected').text().trim();
                    } else {
                        shopText = $('#filter_shop_id').closest('.col-md-3').find('.form-control').text().trim();
                    }
                    if (!shopText) shopText = "{{ $shop->nama ?? 'Semua' }}";

                    $('#lbl-active-shop').text(shopText);
                    $('#lbl-active-period').text((start ? formatDateDisplay(start) : '-') + ' s/d ' + (end ? formatDateDisplay(end) : '-'));
                } catch (e) {
                    console.warn('Gagal memperbarui tautan ekspor:', e);
                }
            }

            // Inisialisasi awal tautan ekspor
            updateExportLinks();

            // DataTable Laporan Harian
            var dataTable = $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('daily-reports.index') }}",
                    data: function(d) {
                        d.shop_id = $('#filter_shop_id').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                columns: [
                    {
                        title: 'Tanggal',
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                let formatted = formatDate(data);
                                if (row.sumber_data === 'import_excel_arsip') {
                                    return `<div><span>${formatted}</span> <span class="badge badge-info ml-1" style="font-size: 10px; padding: 2px 5px;" title="Data diimpor dari Arsip Backdate"><i class="fa fa-history mr-1"></i>Backdate</span></div>`;
                                }
                                return formatted;
                            }
                            return data;
                        }
                    },
                    {
                        title: 'Operator',
                        data: 'operator',
                        name: 'operator.user.name',
                        render: function(data, type, row) {
                            if (data && data.user) {
                                return data.user.name;
                            }
                            if (row.sumber_data === 'import_excel_arsip') {
                                return '<span class="text-muted font-italic"><i class="fa fa-file-excel mr-1 text-success"></i>Arsip / Sistem</span>';
                            }
                            return '-';
                        }
                    },
                    {
                        title: 'Penerimaan (&ell;)',
                        data: 'penerimaan',
                        name: 'penerimaan',
                        className: 'text-right',
                        render: function(data, type) {
                            return (type === 'display' && data !== null && data !== undefined) 
                                ? parseFloat(Number(data).toFixed(2)).toLocaleString('id-ID') 
                                : (data || 0);
                        }
                    },
                    {
                        title: 'Vol Jual (&ell;)',
                        data: 'volume_penjualan',
                        name: 'volume_penjualan',
                        className: 'text-right font-weight-bold',
                        render: function(data, type) {
                            return (type === 'display' && data !== null && data !== undefined) 
                                ? parseFloat(Number(data).toFixed(3)).toLocaleString('id-ID') 
                                : (data || 0);
                        }
                    },
                    {
                        title: 'Rupiah Penjualan',
                        data: 'rupiah_penjualan',
                        name: 'rupiah_penjualan',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display' && data !== null && data !== undefined) {
                                return `<div class="d-flex justify-content-between"><span class="mr-1 text-muted">Rp</span><span class="font-weight-bold">${parseFloat(Number(data).toFixed(0)).toLocaleString('id-ID')}</span></div>`;
                            }
                            return data || 0;
                        }
                    },
                    {
                        title: 'Stok Akhir (&ell;)',
                        data: 'stok_akhir_aktual',
                        name: 'stok_akhir_aktual',
                        className: 'text-right',
                        render: function(data, type) {
                            return (type === 'display' && data !== null && data !== undefined) 
                                ? parseFloat(Number(data).toFixed(2)).toLocaleString('id-ID') 
                                : (data || 0);
                        }
                    },
                    {
                        title: 'Losses / Gain (&ell;)',
                        data: 'losses_gain',
                        name: 'losses_gain',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display' && data !== null && data !== undefined) {
                                let val = parseFloat(data);
                                if (isNaN(val)) return data;
                                let formatted = parseFloat(val.toFixed(3)).toLocaleString('id-ID');
                                if (val < 0) {
                                    return `<span style="color: #dc3545; font-weight: 700;">${formatted}</span>`;
                                } else if (val > 0) {
                                    return `<span style="color: #28a745; font-weight: 700;">+${formatted}</span>`;
                                }
                                return formatted;
                            }
                            return data;
                        }
                    },
                    {
                        title: 'Pengeluaran',
                        data: 'pengeluaran',
                        name: 'pengeluaran',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display' && data !== null && data !== undefined) {
                                return `<div class="d-flex justify-content-between"><span class="mr-1 text-muted">Rp</span><span>${parseFloat(Number(data).toFixed(0)).toLocaleString('id-ID')}</span></div>`;
                            }
                            return data || 0;
                        }
                    },
                    {
                        title: 'Pendapatan',
                        data: 'pendapatan',
                        name: 'pendapatan',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display' && data !== null && data !== undefined) {
                                return `<div class="d-flex justify-content-between"><span class="mr-1 text-muted">Rp</span><span class="font-weight-bold">${parseFloat(Number(data).toFixed(0)).toLocaleString('id-ID')}</span></div>`;
                            }
                            return data || 0;
                        }
                    },
                    {
                        title: 'Saldo Belum Setor',
                        data: 'running_balance_setoran',
                        name: 'running_balance_setoran',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                let val = parseFloat(data) || 0;
                                let formatted = 'Rp ' + Math.abs(val).toLocaleString('id-ID');
                                if (val < -100) {
                                    return `<span style="color:#28a745;font-weight:700;" title="Lebih setor Rp ${Math.abs(val).toLocaleString('id-ID')}">▼ ${formatted}</span>`;
                                } else if (val > 100) {
                                    return `<span style="color:#dc3545;font-weight:700;" title="Kurang setor Rp ${val.toLocaleString('id-ID')}">▲ ${formatted}</span>`;
                                }
                                return `<span style="color:#6c757d;">${formatted}</span>`;
                            }
                            return data;
                        }
                    },
                    {
                        title: 'Status',
                        data: 'status_lifecycle',
                        name: 'status_lifecycle',
                        className: 'text-center',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                const map = {
                                    'draft':     ['secondary', 'Draft'],
                                    'imported':  ['info',      'Diimpor'],
                                    'validated': ['primary',   'Tervalidasi'],
                                    'approved':  ['success',   'Disetujui'],
                                    'locked':    ['dark',      'Terkunci'],
                                };
                                let [cls, label] = map[data] || ['secondary', data || '-'];
                                return `<span class="badge badge-${cls}">${label}</span>`;
                            }
                            return data;
                        }
                    },
                    {
                        title: 'Aksi',
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center text-nowrap'
                    },
                ],
                order: [
                    [0, 'desc']
                ],
                columnDefs: [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: -1 }
                ],
                responsive: true,
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ laporan",
                    infoEmpty: "Menampilkan 0 s/d 0 dari 0 laporan",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    zeroRecords: "Tidak ada data laporan harian yang sesuai dengan filter.",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "»",
                        previous: "«"
                    }
                }
            });

            // Handler Tombol Quick Filter
            $('.btn-quick-filter').on('click', function() {
                let period = $(this).data('period');
                let today = new Date();
                let startDate, endDate;

                $('.btn-quick-filter').removeClass('active');
                $(this).addClass('active');

                if (period === 'today') {
                    startDate = getFormattedDate(today);
                    endDate = getFormattedDate(today);
                } else if (period === '7days') {
                    let past7 = new Date();
                    past7.setDate(today.getDate() - 6);
                    startDate = getFormattedDate(past7);
                    endDate = getFormattedDate(today);
                } else if (period === '30days') {
                    let past30 = new Date();
                    past30.setDate(today.getDate() - 29);
                    startDate = getFormattedDate(past30);
                    endDate = getFormattedDate(today);
                } else if (period === '60days') {
                    let past60 = new Date();
                    past60.setDate(today.getDate() - 59);
                    startDate = getFormattedDate(past60);
                    endDate = getFormattedDate(today);
                } else if (period === '90days') {
                    let past90 = new Date();
                    past90.setDate(today.getDate() - 89);
                    startDate = getFormattedDate(past90);
                    endDate = getFormattedDate(today);
                } else if (period === 'this_month') {
                    let startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    let endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    startDate = getFormattedDate(startOfMonth);
                    endDate = getFormattedDate(endOfMonth);
                } else if (period === 'last_month') {
                    let startOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    let endOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                    startDate = getFormattedDate(startOfLastMonth);
                    endDate = getFormattedDate(endOfLastMonth);
                }

                $('#start_date').val(startDate);
                $('#end_date').val(endDate);

                updateExportLinks();
                dataTable.ajax.reload();
            });

            // Jika user mengubah tanggal secara manual di input, lepaskan state aktif tombol quick filter
            $('#start_date, #end_date').on('change', function() {
                $('.btn-quick-filter').removeClass('active');
                updateExportLinks();
            });

            // Tombol Tampilkan / Filter
            $('#btn-filter').on('click', function() {
                let sVal = $('#start_date').val();
                let eVal = $('#end_date').val();
                if (sVal && eVal) {
                    let d1 = new Date(sVal);
                    let d2 = new Date(eVal);
                    let diffTime = d2.getTime() - d1.getTime();
                    let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    if (diffDays > 90) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Rentang Waktu Maksimal 90 Hari',
                            text: 'Anda memilih rentang ' + diffDays + ' hari. Batas maksimal partisi filter laporan harian adalah 90 hari untuk menjaga kecepatan dan akurasi query.',
                            confirmButtonColor: '#00529C'
                        });
                        return;
                    }
                    if (diffDays < 1) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Tanggal Tidak Valid',
                            text: 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.',
                            confirmButtonColor: '#00529C'
                        });
                        return;
                    }
                }
                updateExportLinks();
                dataTable.ajax.reload();
            });

            // Ganti Cabang / Pertashop
            $('#filter_shop_id').on('change', function() {
                updateExportLinks();
                dataTable.ajax.reload();
            });

            // Tombol Reset (kembali ke default: Bulan Ini)
            $('#btn-reset').on('click', function() {
                let today = new Date();
                let startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                let endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                $('#start_date').val(getFormattedDate(startOfMonth));
                $('#end_date').val(getFormattedDate(endOfMonth));

                $('.btn-quick-filter').removeClass('active');
                $('.btn-quick-filter[data-period="this_month"]').addClass('active');

                updateExportLinks();
                dataTable.ajax.reload();
            });

            // Handler Hapus Laporan
            $('#table').on('click', '.btn-delete', function() {
                var id = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data laporan ini akan dihapus secara permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "DELETE",
                            url: "{{ url('') }}" + "/daily-reports/" + id,
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(response) {
                                dataTable.ajax.reload();
                                Swal.fire(
                                    'Terhapus!',
                                    response.message || 'Data laporan berhasil dihapus.',
                                    'success'
                                );
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Gagal!',
                                    xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus data.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
