@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Laporan Harian</h1>
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
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <div class=" d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            @if (Auth::user()->role == 'operator')
                                <h3 class="card-title mr-2">
                                    {{ Auth::user()->operator->shop->kode . ' ' . Auth::user()->operator->shop->nama }}</h3>
                            @elseif(Auth::user()->role == 'admin')
                                <h3 class="card-title mr-2">
                                    {{ Auth::user()->admin->shop->kode . ' ' . Auth::user()->admin->shop->nama }}</h3>
                            @else
                                <select name="shop_id" class="form-control mr-2" style="width: 200px">
                                    @foreach ($shops as $shop)
                                        <option value="{{ $shop->id }}">{{ $shop->kode . ' ' . $shop->nama }}</option>
                                    @endforeach
                                </select>
                            @endif

                        </div>
                        @if (Auth::user()->role == 'operator')
                            <a href="{{ route('daily-reports.create') }}" class="btn btn-primary"><i
                                    class="fa fa-plus mr-2"></i>Tambah
                                Laporan Harian</a>
                        @endif
                    </div>

                </div>
                <div class="card-body">

                    <div class="table-responsive-lg">
                        <table id="table" class="table table-bordered">
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
            var dataTable = $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('daily-reports.index') }}",
                columns: [
                    // {
                    //     title: '#',
                    //     data: 'DT_RowIndex',
                    //     name: 'DT_RowIndex',
                    //     width: '20',
                    // },
                    {
                        title: 'Tanggal',
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatDate(data)
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
                            return '-';
                        }
                    },
                    // {
                    //     title: 'Totalisator Awal',
                    //     data: 'totalisator_awal',
                    //     name: 'totalisator_awal',
                    // },
                    // {
                    //     title: 'Totalisator Akhir',
                    //     data: 'totalisator_akhir',
                    //     name: 'totalisator_akhir',
                    // },
                    // {
                    //     title: 'Test Pump (&ell;)',
                    //     data: 'test_pump',
                    //     name: 'test_pump',
                    // },
                    {
                        title: 'Penerimaan (&ell;)',
                        data: 'penerimaan',
                        name: 'penerimaan',
                        render: function(data, type) {
                            return (type === 'display' && data) ? parseFloat(Number(data).toFixed(3)).toLocaleString('id-ID') : data;
                        }
                    },
                    {
                        title: 'Volume Penjualan (&ell;)',
                        data: 'volume_penjualan',
                        name: 'volume_penjualan',
                        render: function(data, type) {
                            return (type === 'display' && data) ? parseFloat(Number(data).toFixed(3)).toLocaleString('id-ID') : data;
                        }
                    },
                    {
                        title: 'Rupiah Penjualan',
                        data: 'rupiah_penjualan',
                        name: 'rupiah_penjualan',
                        render: function(data, type) {
                            if (type === 'display') {
                                return `<div class="justify-content-between"><span class="mr-1">Rp</span><span>${parseFloat(Number(data).toFixed(3)).toLocaleString('id-ID')}</span></div>`
                            }
                            return data;
                        }
                    },
                    // {
                    //     title: 'Stok Awal (&ell;)',
                    //     data: 'stok_awal',
                    //     name: 'stok_awal',
                    // },

                    // {
                    //     title: 'Stok Akhir Teoritis (&ell;)',
                    //     data: 'stok_akhir_teoritis',
                    //     name: 'stok_akhir_teoritis',
                    // },
                    {
                        title: 'Stok Akhir (&ell;)',
                        data: 'stok_akhir_aktual',
                        name: 'stok_akhir_aktual',
                        render: function(data, type) {
                            return (type === 'display' && data) ? parseFloat(Number(data).toFixed(3)).toLocaleString('id-ID') : data;
                        }
                    },
                    {
                        title: 'Losses / Gain (&ell;)',
                        data: 'losses_gain',
                        name: 'losses_gain',
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
                        render: function(data, type) {
                            if (type === 'display') {
                                return `<div class="justify-content-between"><span class="mr-1">Rp</span><span>${parseFloat(Number(data).toFixed(3)).toLocaleString('id-ID')}</span></div>`
                            }
                            return data;
                        }
                    },

                    {
                        title: 'Pendapatan',
                        data: 'pendapatan',
                        name: 'pendapatan',
                        render: function(data, type) {
                            if (type === 'display') {
                                return `<div class="justify-content-between"><span class="mr-1">Rp</span><span>${parseFloat(Number(data).toFixed(3)).toLocaleString('id-ID')}</span></div>`
                            }
                            return data;
                        }
                    },

                    // {
                    //     title: 'Disetorkan',
                    //     data: 'disetorkan',
                    //     name: 'disetorkan',
                    //     render: function(data, type) {
                    //         if (type === 'display') {
                    //             return `<div class="justify-content-between"><span class="mr-1">Rp</span><span>${formatNumber(data)}</span></div>`
                    //         }
                    //         return data;
                    //     }
                    // },

                    // {
                    //     title: 'Selisih Setoran',
                    //     data: 'selisih_setoran',
                    //     name: 'selisih_setoran',
                    //     render: function(data, type) {
                    //         if (type === 'display') {
                    //             return `<div class="justify-content-between"><span class="mr-1">Rp</span><span>${formatNumber(data)}</span></div>`
                    //         }
                    //         return data;
                    //     }
                    // },
                    // {
                    //     title: 'Belum Disetorkan',
                    //     data: 'belum_disetorkan',
                    //     name: 'belum_disetorkan',
                    //     render: function(data, type) {
                    //         if (type === 'display') {
                    //             return `<div class="justify-content-between"><span class="mr-1">Rp</span><span>${formatNumber(data)}</span></div>`
                    //         }
                    //         return data;
                    //     }
                    // },

                    {
                        title: 'Aksi',
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [0, 'desc']
                ],
                columnDefs: [{
                        responsivePriority: 1,
                        targets: 0
                    },
                    {
                        responsivePriority: 2,
                        targets: -1
                    }
                ],
                responsive: {
                    details: {
                        display: DataTable.Responsive.display.modal({
                            header: function(row) {
                                var data = row.data();
                                return 'Detail Laporan Harian';
                            }
                        }),
                        renderer: DataTable.Responsive.renderer.tableAll({
                            tableClass: 'table'
                        })
                    }
                }
            });

            $('select[name=shop_id]').on('change', function() {
                dataTable.ajax.url(`?shop_id=${this.value}`).load();
            });


            $('#table').on('click', '.btn-delete', function() {
                var id = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data laporan akan dihapus secara permanen!",
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
                            success: function(response) {
                                dataTable.ajax.reload();
                                Swal.fire(
                                    'Terhapus!',
                                    response.message,
                                    'success'
                                );
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
