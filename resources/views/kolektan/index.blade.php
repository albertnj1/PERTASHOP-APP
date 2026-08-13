@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Data Kolektan</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item active">Data Kolektan</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Kolektan</h3>
                    @if(Auth::user()->role !== 'investor')
                    <a href="{{ route('kolektans.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> Tambah Kolektan
                    </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="kolektan-table" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Pertashop</th>
                                <th>Nama Kolektan</th>
                                <th>PIN</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
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
        var table = $('#kolektan-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('kolektans.index') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'shop.nama', name: 'shop.nama'},
                {data: 'nama_kolektan', name: 'nama_kolektan'},
                {
                    data: 'pin', 
                    name: 'pin',
                    render: function(data) {
                        return '<span class="badge badge-secondary">' + data + '</span>';
                    }
                },
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });

        $('body').on('click', '.btn-delete', function () {
            var id = $(this).data("id");
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data Kolektan ini akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('kolektans') }}"+'/'+id,
                        data: {
                            "_token": "{{ csrf_token() }}",
                        },
                        success: function (data) {
                            table.draw();
                            Swal.fire(
                                'Terhapus!',
                                'Data Kolektan telah dihapus.',
                                'success'
                            )
                        },
                        error: function (data) {
                            Swal.fire(
                                'Gagal!',
                                'Terjadi kesalahan saat menghapus data.',
                                'error'
                            )
                        }
                    });
                }
            })
        });
    });
</script>
@endpush
