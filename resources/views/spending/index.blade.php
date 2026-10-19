@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Pengeluaran</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Pengeluaran</li>
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
                        
                        @if (Auth::user()->role == 'operator' || Auth::user()->role == 'admin')
                            <button class="btn btn-primary btn-add"><i class="fa fa-plus mr-2"></i>Tambah
                                Pengeluaran</button>
                        @endif

                    </div>

                </div>
                <div class="card-body">

                    <div class="table-responsive-lg">
                        <table class="table table-bordered">
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modal_spending">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Pengeluaran</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="form_spending" action="{{ route('spendings.store') }}" method="POST">
                    <div class="modal-body">
                        <div class="form-group row">
                            <label for="created_at" class="col-sm-4 col-form-label">Tanggal</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="date" class="form-control" id="created_at" name="created_at"
                                        value="{{ date('Y-m-d') }}"
                                        @if (Auth::user()->role == 'operator') readonly @else required @endif>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="spending_category_id" class="col-sm-4 col-form-label">Kategori</label>
                            <div class="col-sm-8">
                                <select name="spending_category_id" id="spending_category_id" class="form-control" required>
                                    <option value="">--Pilih Kategori--</option>
                                    @php
                                        $expectedCategories = [
                                            'Ongkos Bongkar',
                                            'Biaya Transfer',
                                            'Fotocopy & ATK',
                                            'Listrik',
                                            'Air Bersih',
                                            'Cashback',
                                            'Internet',
                                            'Lain-lain',
                                        ];
                                        $existingNames = $spendingCategories->pluck('nama')->map(fn($n) => strtolower(trim($n)))->toArray();
                                    @endphp
                                    @foreach ($spendingCategories as $category)
                                        <option value="{{ $category->id }}" data-nama="{{ $category->nama }}">
                                            {{ $category->nama }}</option>
                                    @endforeach
                                    {{-- Fallback jika ada kategori yang belum tersimpan di database --}}
                                    @foreach ($expectedCategories as $expCat)
                                        @if(!in_array(strtolower(trim($expCat)), $existingNames))
                                            <option value="{{ $expCat }}" data-nama="{{ $expCat }}">
                                                {{ $expCat }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- Kolom Keterangan Dinamis: Otomatis tampil wajib saat kategori "Lain-lain" dipilih --}}
                        <div id="wrapperKeteranganLain" class="form-group row" style="display: none;">
                            <label for="keterangan" class="col-sm-4 col-form-label">
                                Keterangan <span class="text-danger">*</span>
                            </label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="keterangan" name="keterangan"
                                        placeholder="Contoh: Beli sabun cuci tangan / perbaikan kran / dll"
                                        value="">
                                </div>
                                <small class="text-muted" style="font-size: 11.5px;">Wajib diisi untuk mendeskripsikan pengeluaran kategori Lain-lain</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="jumlah" class="col-sm-4 col-form-label">Jumlah (Rp)</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" id="jumlah" name="jumlah" value="" min="0" step="any" placeholder="Masukkan jumlah nominal"
                                        required>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success font-weight-bold" style="background:#15803d; border:none; padding: 6px 20px;">
                            <i class="fas fa-save mr-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>

        </div>

    </div>
@endsection


@push('script')
    <script>
        $(document).ready(function() {
            var dataTable = $('.table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('spendings.index') }}",
                columns: [
                    // {
                    //     title: '#',
                    //     data: 'DT_RowIndex',
                    //     name: 'DT_RowIndex',
                    //     width: 20
                    // },
                    {
                        title: 'Tanggal',
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatDate(data);
                            }
                            return data;
                        }
                    },

                    {
                        title: 'Kategori',
                        data: 'category.nama',
                        name: 'category',
                        render: function(data, type, row) {
                            var catName = (row.category && row.category.nama) ? row.category.nama : (data || '-');
                            if (row.keterangan && row.keterangan.trim() !== '') {
                                return `<strong>${catName}</strong><br><small class="text-muted"><i class="fas fa-info-circle mr-1"></i>${row.keterangan}</small>`;
                            }
                            return catName;
                        }
                    },

                    {
                        title: 'Jumlah (Rp)',
                        data: 'jumlah',
                        name: 'jumlah',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data, 0);
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
                        searchable: false,
                        width: 80,
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
                                return 'Detail Pengeluaran';
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



            //show modal add
            function checkKategoriLainLain() {
                var selectedOption = $('#spending_category_id option:selected');
                var val = selectedOption.val();
                var text = (selectedOption.data('nama') || selectedOption.text() || '').trim().toLowerCase();

                if (val == '99' || val === 'Lain-lain' || text === 'lain-lain') {
                    $('#wrapperKeteranganLain').slideDown(200);
                    $('#keterangan').attr('required', 'required');
                } else {
                    $('#wrapperKeteranganLain').slideUp(200);
                    $('#keterangan').removeAttr('required');
                    if (!$('#modal_spending').hasClass('is-editing')) {
                        $('#keterangan').val('');
                    }
                }
            }

            $('#spending_category_id').on('change', function() {
                checkKategoriLainLain();
            });

            //show modal add
            $('.btn-add').on('click', function() {
                $('#modal_spending').removeClass('is-editing').modal('show');
                $('#modal_spending .modal-title').text('Tambah Pengeluaran');
                $('#form_spending').attr('action', "{{ route('spendings.store') }}");
                $('#form_spending').attr('method', "POST");
                $('#form_spending').trigger('reset');
                $('#wrapperKeteranganLain').hide();
                $('#keterangan').removeAttr('required').val('');
            });

            //show modal edit
            $('.table').on('click', '.btn-edit', function() {
                var id = $(this).data('id');
                $.ajax({
                    type: "GET",
                    url: "{{ url('') }}" + "/spendings/" + id + "/edit",
                    success: function(response) {
                        $('#modal_spending').addClass('is-editing').modal('show');
                        $('#modal_spending .modal-title').text('Edit Pengeluaran');
                        $('#form_spending').attr('action', "{{ url('') }}" +
                            "/spendings/" + id);
                        $('#form_spending').attr('method', "PUT");
                        $('#spending_category_id').val(response.spending.spending_category_id);
                        $('#keterangan').val(response.spending.keterangan);
                        $('#jumlah').val(response.spending.jumlah);
                        let dt = new Date(response.spending.created_at);
                        $('#created_at').val(dt.toLocaleDateString("en-CA"));
                        checkKategoriLainLain();
                    }
                });
            });

            //submit form spending
            $('#form_spending').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    type: $(this).attr('method'),
                    url: $(this).attr('action'),
                    data: formData,
                    success: function(response) {
                        dataTable.ajax.reload();
                        $('#modal_spending').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                    },
                    error: function(xhr) {
                        var msg = 'Terjadi kesalahan saat menyimpan data.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menyimpan',
                            html: msg,
                        });
                    }
                });
            });

            //delete spending
            $('.table').on('click', '.btn-delete', function() {
                var id = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data pengeluaran akan dihapus secara permanen!",
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
                            url: "{{ url('') }}" + "/spendings/" + id,
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
