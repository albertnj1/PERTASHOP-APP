@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Pembelian</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Pembelian</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if (Auth::user()->role == 'admin' || Auth::user()->role == 'super-admin')
            <div class="card shadow-sm mb-4" style="background-color: #f8faff; border-top: 3px solid #0d6efd;">
                <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-primary mb-1"><i class="fas fa-file-invoice text-primary"></i> Tambah Pembelian / Order SO</h5>
                            <small class="text-muted">Input nomor SO untuk Pertashop yang akan menerima BBM</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form id="insertForm" action="{{ route('purchases.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-3">
                                <label for="shop_id"><i class="fas fa-store text-primary"></i> Pertashop</label>
                                <select class="form-control @error('shop_id') is-invalid @enderror" name="shop_id" id="shop_id" required>
                                    <option value="">--Pilih Pertashop--</option>
                                    @foreach ($shops as $shop)
                                        <option value="{{ $shop->id }}" @selected(old('shop_id') == $shop->id)>{{ $shop->nama }}</option>
                                    @endforeach
                                </select>
                                @error('shop_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="purchase_date"><i class="far fa-calendar-alt text-primary"></i> Hari/Tanggal Pembelian</label>
                                <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                                @error('purchase_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="supplier_id"><i class="fas fa-truck text-primary"></i> Supplier</label>
                                <select class="form-control @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplier_id" required>
                                    <option value="">--Pilih Supplier--</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->nama }}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="no_so"><span class="text-primary font-weight-bold">#</span> No. SO</label>
                                <input type="text" class="form-control @error('no_so') is-invalid @enderror" id="no_so" name="no_so" value="{{ old('no_so') }}" placeholder="Masukkan No. SO" required>
                                @error('no_so')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="no_lo"><span class="text-primary font-weight-bold">#</span> No. LO</label>
                                <input type="text" class="form-control @error('no_lo') is-invalid @enderror" id="no_lo" name="no_lo" value="{{ old('no_lo') }}" placeholder="Masukkan No. LO" required>
                                @error('no_lo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-2 mb-3">
                                <label for="trip"><i class="fas fa-route text-primary"></i> Trip</label>
                                <input type="text" class="form-control @error('trip') is-invalid @enderror" id="trip" name="trip" value="{{ old('trip') }}" placeholder="Trip">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label for="volume"><i class="fas fa-tint text-primary"></i> Kuantitas (&ell;)</label>
                                <input type="number" class="form-control @error('volume') is-invalid @enderror" id="volume" name="volume" value="{{ old('volume') }}" placeholder="Liter" required>
                                @error('volume')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-2 mb-3">
                                <label for="delivery_date"><i class="fas fa-shipping-fast text-primary"></i> Tgl Kirim</label>
                                <input type="date" class="form-control @error('delivery_date') is-invalid @enderror" id="delivery_date" name="delivery_date" value="{{ old('delivery_date', date('Y-m-d')) }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="total_bayar"><i class="fas fa-money-bill-wave text-primary"></i> Total Bayar (Rp)</label>
                                <input type="number" class="form-control @error('total_bayar') is-invalid @enderror" id="total_bayar" name="total_bayar" value="{{ old('total_bayar') }}" placeholder="Rupiah" required>
                                @error('total_bayar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Simpan Pembelian</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

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
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive-lg">
                        <table id="purchase-table" class="table table-bordered">
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
            var dataTable = $('#purchase-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "/purchases",
                columns: [
                    // {
                    //     data: 'DT_RowIndex',
                    //     name: 'DT_RowIndex'
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
                        title: 'No. SO',
                        data: 'no_so',
                        name: 'no_so',
                    },
                    {
                        title: 'Supplier',
                        data: 'supplier.nama',
                        name: 'supplier.nama',
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
                        title: 'Diterima (&ell;)',
                        data: 'diterima',
                        name: 'diterima',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data)
                            }
                            return data;
                        }
                    },
                    // {
                    //     title: 'Sisa (&ell;)',
                    //     data: 'sisa',
                    //     name: 'sisa',
                    //     className: 'text-right',
                    //     render: function(data, type) {
                    //         if (type === 'display') {
                    //             return formatNumber(data)
                    //         }
                    //         return data;
                    //     }
                    // },

                    {
                        title: 'Harga per Liter (Rp)',
                        data: 'harga',
                        name: 'harga',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data);
                            }
                            return data;
                        }
                    },
                    {
                        title: 'Total Bayar (Rp)',
                        data: 'total_bayar',
                        name: 'total_bayar',
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
                                return 'Detail Pembelian';
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


            $('#purchase-table').on('click', '.btn-delete', function() {
                var saleId = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data pembelian akan dihapus secara permanen!",
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
                            url: "{{ url('') }}" + "/purchases/" + saleId,
                            data: {
                                "_token": "{{ csrf_token() }}"
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
