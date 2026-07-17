@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Rekap Profit Sharing</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Rekap Profit Sharing</li>
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
                            @if (Auth::user()->role == 'admin')
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
                        {{-- <a href="" class="btn btn-primary"><i class="fa fa-plus mr-2"></i>Tambah
                            Laporan Bulanan</a> --}}
                    </div>

                </div>
                <div class="card-body">

                    <div class="table-responsive">
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
            let columns = [{
                    title: '#',
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    width: 16,
                    className: 'text-right'
                },

                {
                    title: 'Bulan',
                    data: 'bulan',
                    name: 'bulan',
                    render: function(data, type) {
                        if (type === 'display') {
                            return formatYearMonth(data)
                        }
                        return data;
                    }
                },
                {
                    title: 'Nilai Profit Sharing (Rp)',
                    data: 'profit_sharing',
                    name: 'profit_sharing',
                    className: 'text-right',
                    render: function(data, type) {
                        if (type === 'display') {
                            return formatNumber(data, 0);
                        }
                        return data;
                    }
                },
                {
                    title: 'Alokasi Modal (Rp)',
                    data: 'alokasi_modal',
                    name: 'alokasi_modal',
                    className: 'text-right',
                    render: function(data, type) {
                        if (type === 'display') {
                            return formatNumber(data, 0);
                        }
                        return data;
                    }
                },
                {
                    title: 'Sisa Keuntungan (Rp)',
                    data: 'sisa_keuntungan',
                    name: 'sisa_keuntungan',
                    className: 'text-right',
                    render: function(data, type) {
                        if (type === 'display') {
                            return formatNumber(data, 0);
                        }
                        return data;
                    }
                },
                {
                    title: 'Return On Invesment To Go (Rp)',
                    data: 'roi',
                    name: 'roi',
                    className: 'text-right',
                    render: function(data, type) {
                        if (type === 'display') {
                            return formatNumber(data, 0);
                        }
                        return data;
                    }
                },

                // {
                //     title: 'Aksi',
                //     data: 'action',
                //     name: 'action',
                //     orderable: false,
                //     searchable: false
                // },
            ];

            let investors = @json($investors);

            investors.forEach(investor => {
                columns.splice(5, 0, {
                    title: investor.user.name + " " + investor.pivot.persentase + '%',
                    data: investor.user.name.toLowerCase().split(' ').join('_'),
                    name: investor.user.name.toLowerCase().split(' ').join('_'),
                    className: 'text-right',
                    render: function(data, type) {
                        if (type === 'display') {
                            return formatNumber(data, 0);
                        }
                        return data;
                    }
                });
            });

            console.log(columns);

            var dataTable = $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('profit-sharing.index') }}",
                columns: columns,
                order: [
                    [0, 'desc']
                ],
                // columnDefs: [{
                //         responsivePriority: 1,
                //         targets: 0
                //     },
                //     {
                //         responsivePriority: 2,
                //         targets: -1
                //     }
                // ],
                // responsive: {
                //     details: {
                //         display: DataTable.Responsive.display.modal({
                //             header: function(row) {
                //                 var data = row.data();
                //                 return 'Detail modal kerja';
                //             }
                //         }),
                //         renderer: DataTable.Responsive.renderer.tableAll({
                //             tableClass: 'table'
                //         })
                //     }
                // }
            });

            $('select[name=shop_id]').on('change', function() {
                dataTable.ajax.url(`?shop_id=${this.value}`).load();
            });



        });
    </script>
@endpush
