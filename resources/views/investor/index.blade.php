@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Investor</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
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
                        <a href="{{ route('investors.create') }}" class="btn btn-primary"><i
                                class="fa fa-plus mr-2"></i>Tambah Investor</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        @foreach ($shops as $shop)
                            @if ($shop->investors->count() > 0)
                                <div class="col-md-6 mb-4 shop-chart-container" data-shop-id="{{ $shop->id }}">
                                    <div class="card shadow-sm border-0 h-100" style="background: #f8f9fa;">
                                        <div class="card-header border-0" style="background: #ffffff; border-bottom: 2px solid #3498db !important;">
                                            <h5 class="mb-0 text-primary font-weight-bold" style="font-size: 15px;"><i class="fas fa-gas-pump mr-2"></i>{{ $shop->nama }} ({{ $shop->kode }})</h5>
                                            <div class="text-muted mt-1" style="font-size: 12px;">Total Investasi: <strong class="text-dark">Rp {{ number_format($shop->total_investasi, 0, ',', '.') }}</strong></div>
                                        </div>
                                        <div class="card-body bg-white">
                                            <div class="row align-items-center">
                                                <div class="col-sm-5 text-center mb-3 mb-sm-0">
                                                    <canvas id="investorChart-{{ $shop->id }}" style="height: 180px; max-height: 180px; width: 100%;"></canvas>
                                                </div>
                                                <div class="col-sm-7">
                                                    <ul class="list-unstyled mb-0" style="font-size: 13px;">
                                                        @php $colors = ['#00796B', '#2e7d32', '#f57c00', '#d32f2f', '#673ab7', '#1976D2', '#C2185B', '#0097A7', '#FBC02D', '#8D6E63']; @endphp
                                                        @foreach ($shop->investors as $idx => $inv)
                                                            @php $c = $colors[$idx % count($colors)]; @endphp
                                                            <li class="mb-2 pb-2 @if(!$loop->last) border-bottom @endif">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span class="font-weight-600 text-dark">
                                                                        <span class="dot mr-1" style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:{{ $c }};"></span> 
                                                                        {{ $inv->user->name }}
                                                                    </span>
                                                                    <span class="badge badge-success px-2 py-1">{{ number_format($inv->pivot->persentase, 2) }}%</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 11px;">
                                                                    <span>Nominal:</span>
                                                                    <span class="font-weight-bold text-dark">Rp {{ number_format($inv->pivot->nominal, 0, ',', '.') }}</span>
                                                                </div>
                                                                @if ($inv->pivot->sub_investors)
                                                                    @php $subs = json_decode($inv->pivot->sub_investors, true); @endphp
                                                                    <ul class="list-unstyled pl-3 mt-1 text-muted" style="font-size: 11px;">
                                                                    @foreach ($subs as $sub)
                                                                        <li>- {{ $sub['name'] }} @if(isset($sub['nominal'])) <span class="font-weight-bold ml-1">(Rp {{ number_format($sub['nominal'], 0, ',', '.') }})</span> @endif</li>
                                                                    @endforeach
                                                                    </ul>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

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
                ajax: "{{ route('investors.index') }}",
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
                        title: 'Pertashop',
                        data: 'shops',
                        name: 'shops',
                        render: function(data) {
                            return data.map(function(d) {
                                return d.nama;
                            }).join(', ');
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
                                return 'Detail Investor';
                            }
                        }),
                        renderer: DataTable.Responsive.renderer.tableAll({
                            tableClass: 'table'
                        })
                    }
                }
            });

            $('select[name=shop_id]').on('change', function() {
                var selectedShopId = this.value;
                dataTable.ajax.url(`?shop_id=${selectedShopId}`).load();
                
                if (selectedShopId) {
                    $('.shop-chart-container').hide();
                    $('.shop-chart-container[data-shop-id="' + selectedShopId + '"]').fadeIn();
                } else {
                    $('.shop-chart-container').fadeIn();
                }
            });


            $('#table').on('click', '.btn-delete', function() {
                var id = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data investor akan dihapus secara permanen!",
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
                            url: "{{ route('investors.index') }}" + "/" + id,
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

            // Render Donut Charts
            const chartPalette = ['#00796B', '#2e7d32', '#f57c00', '#d32f2f', '#673ab7', '#1976D2', '#C2185B', '#0097A7', '#FBC02D', '#8D6E63'];
            
            @foreach ($shops as $shop)
                @if ($shop->investors->count() > 0)
                    (function() {
                        const ctx = document.getElementById('investorChart-{{ $shop->id }}');
                        if (ctx) {
                            const labels = {!! json_encode($shop->investors->pluck('user.name')) !!};
                            const data = {!! json_encode($shop->investors->pluck('pivot.persentase')) !!};
                            const bgColors = labels.map((_, i) => chartPalette[i % chartPalette.length]);
                            
                            new Chart(ctx.getContext('2d'), {
                                type: 'doughnut',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        data: data,
                                        backgroundColor: bgColors,
                                        borderWidth: 2,
                                        borderColor: '#ffffff'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return ' ' + context.label + ': ' + context.raw + '%';
                                                }
                                            }
                                        }
                                    },
                                    cutout: '70%'
                                }
                            });
                        }
                    })();
                @endif
            @endforeach
        });
    </script>
@endpush
