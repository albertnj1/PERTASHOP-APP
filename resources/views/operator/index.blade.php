@extends('layouts.app')

@push('style')
<style>
    .badge-modern-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
    }
    .badge-modern-secondary {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        box-shadow: 0 2px 4px rgba(107, 114, 128, 0.2);
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
    }
    .btn-action-modern {
        border-radius: 8px;
        border: none;
        padding: 6px 12px;
        margin-right: 6px;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-action-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        color: white;
    }
    .btn-edit-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
    .btn-lock-modern { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
    .btn-unlock-modern { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
    .btn-delete-modern { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
</style>
@endpush

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Operator</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Operator</li>
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
                                    <option value="">-- Semua Pertashop --</option>
                                    @foreach ($shops as $shop)
                                        <option value="{{ $shop->id }}">{{ $shop->kode . ' ' . $shop->nama }}</option>
                                    @endforeach
                                </select>
                            @endif

                        </div>
                        @if(Auth::user()->role !== 'investor')
                        <a href="{{ route('operators.create') }}" class="btn btn-primary"><i
                                class="fa fa-plus mr-2"></i>Tambah Operator</a>
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
                ajax: "{{ route('operators.index') }}",
                columns: [{
                        title: '#',
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        width: '20',
                    },
                    {
                        title: 'Nama',
                        data: 'user',
                        name: 'user.name',
                        render: function(data) {
                            return data ? data.name : '-';
                        }
                    },
                    {
                        title: 'Alamat',
                        data: 'alamat',
                        name: 'alamat',
                    },
                    {
                        title: 'Pertashop',
                        data: 'shop',
                        name: 'shop.nama',
                        render: function(data) {
                            return data ? data.nama : '-';
                        }
                    },
                    {
                        title: 'Status',
                        data: 'status',
                        name: 'status',
                        orderable: false,
                    },
                    {
                        title: 'Aksi',
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                // order: [
                //     [0, 'desc']
                // ],
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
                                return 'Detail Operator';
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
                    text: "Data operator akan dihapus secara permanen!",
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
                            url: "{{ route('operators.index') }}" + "/" + id,
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

            $('#table').on('click', '.btn-toggle-status', function() {
                var id = $(this).data('id');

                Swal.fire({
                    title: 'Konfirmasi',
                    text: "Apakah Anda ingin mengubah status akses operator ini?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, ubah status!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "PATCH",
                            url: "/operators/" + id + "/toggle-status",
                            success: function(response) {
                                dataTable.ajax.reload();
                                Swal.fire(
                                    'Berhasil!',
                                    response.message,
                                    'success'
                                );
                            },
                            error: function() {
                                Swal.fire(
                                    'Error!',
                                    'Terjadi kesalahan saat mengubah status.',
                                    'error'
                                );
                            }
                        });
                    }
                })
            });

        });
    </script>
@endpush
