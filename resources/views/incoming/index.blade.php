@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Penerimaan</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Penerimaan</li>
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
                            <a href="{{ route('incomings.create') }}" class="btn btn-primary"><i
                                    class="fa fa-plus mr-2"></i>Tambah
                                Penerimaan</a>
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
                ajax: "{{ route('incomings.index') }}",
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
                        title: 'No. SO',
                        data: 'purchase.no_so',
                        name: 'purchase.no_so',
                    },
                    {
                        title: 'Supplier',
                        data: 'purchase.supplier.nama',
                        name: 'supplier',
                    },
                    {
                        title: 'Sopir',
                        data: 'sopir',
                        name: 'sopir',
                    },
                    {
                        title: 'No. Polisi',
                        data: 'no_polisi',
                        name: 'no_polisi',
                    },

                    {
                        title: 'Volume (&ell;)',
                        data: 'volume',
                        name: 'volume',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data)
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
                                return 'Detail Penerimaan';
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
                var saleId = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data barang Masuk akan dihapus secara permanen!",
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
                            url: "{{ url('') }}" + "/incomings/" + saleId,
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
