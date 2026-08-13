@extends('layouts._new_admin')

@section('title', 'Rekap Profit Sharing')

@section('content')
    <div class="metrics-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                @if (Auth::user()->role == 'admin')
                    <h2 class="card-title" style="margin: 0; font-size: 1.2rem;">
                        {{ Auth::user()->admin->shop->kode . ' ' . Auth::user()->admin->shop->nama }}</h2>
                @else
                    <select name="shop_id" class="form-control" style="padding: 8px 16px; border-radius: 20px; border: 1px solid rgba(0,0,0,0.1); outline: none; background: #fff; cursor: pointer;">
                        @foreach ($shops as $shop)
                            <option value="{{ $shop->id }}">{{ $shop->kode . ' ' . $shop->nama }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
        <div class="table-responsive-lg">
            <table id="table" class="table table-bordered" style="width: 100%;">
            </table>
        </div>
    </div>
@endsection

@push('scripts')
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
                    title: 'Sisa Modal Belum Kembali (Rp)',
                    data: 'payback_sisa',
                    name: 'payback_sisa',
                    className: 'text-right',
                    render: function(data, type) {
                        if (type === 'display') {
                            return formatNumber(data, 0);
                        }
                        return data;
                    }
                },
                {
                    title: 'Persentase Pengembalian (%)',
                    data: 'persentase_pengembalian',
                    name: 'persentase_pengembalian',
                    className: 'text-right',
                    render: function(data, type) {
                        if (type === 'display') {
                            return data.toFixed(2) + '%';
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
                    data: 'inv_' + investor.id,
                    name: 'inv_' + investor.id,
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
