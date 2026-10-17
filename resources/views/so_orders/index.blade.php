@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-file-invoice text-primary"></i> Order SO (Multi-Trip)</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Order SO</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            {{-- Alert Messages --}}
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            @endif

            {{-- Tombol Tambah Order SO --}}
            @if (Auth::user()->role == 'admin' || Auth::user()->role == 'super-admin')
            <div class="mb-3">
                <a href="{{ route('so-orders.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Tambah Order SO Baru
                </a>
            </div>
            @endif

            {{-- Tabel Data --}}
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            @if (Auth::user()->role == 'admin')
                                <h3 class="card-title mr-2">
                                    {{ Auth::user()->admin->shop->kode . ' ' . Auth::user()->admin->shop->nama }}</h3>
                            @else
                                <select name="shop_id" class="form-control mr-2" style="width: 220px" id="filter-shop">
                                    @foreach ($shops as $shop)
                                        <option value="{{ $shop->id }}">{{ $shop->kode . ' ' . $shop->nama }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="so-orders-table" class="table table-bordered table-hover">
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
        var dataTable = $('#so-orders-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('so-orders.index') }}",
                data: function(d) {
                    var filterShop = $('#filter-shop').val();
                    if (filterShop) {
                        d.shop_id = filterShop;
                    }
                }
            },
            columns: [
                {
                    title: 'Tanggal Order',
                    data: 'order_date',
                    name: 'order_date',
                    render: function(data, type) {
                        if (type === 'display' && data) {
                            return formatDate(data);
                        }
                        return data;
                    }
                },
                {
                    title: 'No. SO',
                    data: 'so_number',
                    name: 'so_number',
                    render: function(data) {
                        return '<span class="font-weight-bold text-primary">' + (data || '-') + '</span>';
                    }
                },
                {
                    title: 'No. LO',
                    data: 'lo_number',
                    name: 'lo_number',
                    defaultContent: '-'
                },
                {
                    title: 'Supplier',
                    data: 'supplier.nama',
                    name: 'supplier.nama',
                    defaultContent: '-'
                },
                {
                    title: 'Total Volume (L)',
                    data: 'total_quantity_liter',
                    name: 'total_quantity_liter',
                    className: 'text-right',
                    render: function(data, type) {
                        if (type === 'display') return formatNumber(data);
                        return data;
                    }
                },
                {
                    title: 'Total Bayar (Rp)',
                    data: 'total_amount',
                    name: 'total_amount',
                    className: 'text-right',
                    render: function(data, type) {
                        if (type === 'display') return formatNumber(data, 0);
                        return data;
                    }
                },
                {
                    title: 'Trip',
                    data: 'trips_info',
                    name: 'trips_info',
                    className: 'text-center',
                    orderable: false,
                    searchable: false
                },
                {
                    title: 'Status',
                    data: 'status_label',
                    name: 'status_label',
                    className: 'text-center',
                    orderable: false,
                    searchable: false
                },
                {
                    title: 'Aksi',
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
            ],
            order: [[0, 'desc']],
            responsive: true
        });

        // Filter by shop
        $('#filter-shop').on('change', function() {
            dataTable.ajax.reload();
        });

        // Delete SO Order
        $('#so-orders-table').on('click', '.btn-delete-so', function() {
            var soId = $(this).data('id');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Order SO beserta seluruh data trip akan dihapus!",
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
                        url: "{{ url('so-orders') }}/" + soId,
                        data: { "_token": "{{ csrf_token() }}" },
                        success: function(response) {
                            dataTable.ajax.reload();
                            Swal.fire('Terhapus!', response.message, 'success');
                        },
                        error: function(xhr) {
                            Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
