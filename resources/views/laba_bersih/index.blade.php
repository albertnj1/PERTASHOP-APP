@extends('layouts._new_admin')

@section('title', 'Laporan Laba Bersih')

@section('content')
    <div class="metrics-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                @if (Auth::user()->role == 'operator')
                    <h2 class="card-title" style="margin: 0; font-size: 1.2rem;">
                        {{ Auth::user()->operator->shop->kode . ' ' . Auth::user()->operator->shop->nama }}</h2>
                @elseif(Auth::user()->role == 'admin')
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
            var dataTable = $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('laba-bersih.index') }}",
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
                        title: 'Total Biaya (Rp)',
                        data: 'total_biaya',
                        name: 'total_biaya',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data, 0);
                            }
                            return data;
                        }
                    },
                    {
                        title: 'Laba Bersih (Rp)',
                        data: 'laba_bersih',
                        name: 'laba_bersih',
                        className: 'text-right',
                        render: function(data, type) {
                            if (type === 'display') {
                                return formatNumber(data, 0);
                            }
                            return data;
                        }
                    },

                    {
                        title: 'Laba Bersih Dibagi (Rp)',
                        data: 'laba_bersih_dibagi',
                        name: 'laba_bersih_dibagi',
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
                            url: "{{ route('laba-bersih.index') }}" + "/" + id,
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
