@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Investor Pertashop</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('shops.index') }}">Pertashop</a></li>
                        <li class="breadcrumb-item active">Investor</li>
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
                            <h3 class="card-title mr-2">{{ $shop->kode }} {{ $shop->nama }}</h3>

                        </div>
                        <button class="btn btn-primary btn-add"><i class="fa fa-plus mr-2"></i>Tambah
                            Investor</button>

                    </div>

                </div>

                <div class="card-body">

                    <table id="table" class="table table-bordered"></table>

                </div>

            </div>
        </div>
    </section>

    <div class="modal fade" id="modal_investor">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Investor</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="form_investor" action="{{ route('shops.investors.store', $shop->id) }}" method="POST">
                    <div class="modal-body">
                        <div class="form-group row">
                            <label for="investor_id" class="col-sm-4 col-form-label">Investor</label>
                            <div class="col-sm-8">
                                <select name="investor_id" id="investor_id" class="form-control" required>
                                    <option value="">--Pilih Investor--</option>
                                    @foreach ($investors as $investor)
                                        <option value="{{ $investor->id }}">
                                            {{ $investor->user->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" id="investor_name" class="form-control" readonly>
                                <input type="hidden" id="id" name="id">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="persentase" class="col-sm-4 col-form-label">Persentase</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="persentase" name="persentase"
                                        value="" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>

        </div>

    </div>
@endsection


@push('script')
    <script>
        $(document).ready(function() {
            var dataTable = $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('shops.investors', $shop->id) }}",
                columns: [{
                        title: '#',
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        width: '20',
                    },
                    {
                        title: 'Nama',
                        data: 'user.name',
                        name: 'user.name',
                    },
                    {
                        title: 'Persentase',
                        data: 'pivot.persentase',
                        name: 'pivot.persentase',
                        class: 'text-right',
                        width: 100,
                        render: function(data) {
                            return formatNumber(data, 0) + '%';
                        }
                    },
                    {
                        title: 'Aksi',
                        data: 'action',
                        name: 'action',
                        width: 80,
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
                                return 'Detail Investor Pertashop';
                            }
                        }),
                        renderer: DataTable.Responsive.renderer.tableAll({
                            tableClass: 'table'
                        })
                    }
                }
            });


            //show modal add
            $('.btn-add').on('click', function() {
                $('#modal_investor').modal('show');
                $('#modal_investor .modal-title').text('Tambah Investor');
                $('#form_investor').attr('action', "{{ route('shops.investors.store', $shop->id) }}");
                $('#form_investor').attr('method', "POST");
                $('#form_investor').trigger('reset');
                $('#investor_id').removeClass('d-none').attr('required', true);
                $('#investor_name').attr('type', 'hidden')
            });

            //show modal edit
            $('.table').on('click', '.btn-edit', function() {
                let id = $(this).data('id');
                let persentase = $(this).data('persentase');
                let nama = $(this).data('nama');
                $('#modal_investor').modal('show');
                $('#modal_investor .modal-title').text('Edit Investor');
                $('#form_investor').attr('action', "{{ route('shops.investors.update', $shop->id) }}");
                $('#form_investor').attr('method', "PUT");
                $('#investor_id').addClass('d-none').attr('required', false);
                $('#id').val(id);
                $('#investor_name').attr('type', 'text').val(nama)
                $('#persentase').val(persentase);
            });

            //submit form investor
            $('#form_investor').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    type: $(this).attr('method'),
                    url: $(this).attr('action'),
                    data: formData,
                    success: function(response) {
                        dataTable.ajax.reload();
                        $('#modal_investor').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500 // milliseconds
                        });
                    }
                });
            })

            //delete investor
            $('.table').on('click', '.btn-delete', function() {
                var id = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data investor akan dihapus!",
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
                            url: "{{ route('shops.investors.destroy', $shop->id) }}",
                            data: {
                                id
                            },
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
