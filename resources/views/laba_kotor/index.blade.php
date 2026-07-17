@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Laporan Laba Kotor</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Laba Kotor</li>
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
                ajax: "{{ route('laba-kotor.index') }}",
                columns: [{
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
                        title: 'Jumlah Pembelian (Rp)',
                        data: 'jumlah_pembelian_rp',
                        name: 'jumlah_pembelian_rp',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data, 0);
                            }
                            return data;
                        }
                    },
                    {
                        title: 'Jumlah Penjualan Bersih (Rp)',
                        data: 'jumlah_penjualan_bersih_rp',
                        name: 'jumlah_penjualan_bersih_rp',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data, 0);
                            }
                            return data;
                        }
                    },
                    {
                        title: 'Rata-rata Omset Harian (&ell;)',
                        data: 'rata_rata_omset_harian',
                        name: 'rata_rata_omset_harian',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data);
                            }
                            return data;
                        }
                    },
                    {
                        title: 'Sisa Stok Akhir (&ell;)',
                        data: 'sisa_stok_akhir',
                        name: 'sisa_stok_akhir',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data);
                            }
                            return data;
                        }
                    },
                    {
                        title: 'Laba Kotor (Rp)',
                        data: 'laba_kotor',
                        name: 'laba_kotor',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data, 0);
                            }
                            return data;
                        }
                    },

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
                                return 'Detail Laporan Laba';
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
                    text: "Data laporan Laba akan dihapus secara permanen!",
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
                            url: "{{ route('laba-kotor.index') }}" + "/" + id,
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
