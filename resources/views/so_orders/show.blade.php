@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-eye text-primary"></i> Detail Order SO</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('so-orders.index') }}">Order SO</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            {{-- Card: Header SO --}}
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice"></i> {{ $soOrder->so_number }}</h3>
                    <div class="card-tools">
                        @php
                            $badgeMap = ['PENDING' => 'badge-secondary', 'PARTIAL' => 'badge-warning', 'COMPLETED' => 'badge-success'];
                        @endphp
                        <span class="badge {{ $badgeMap[$soOrder->status] ?? 'badge-secondary' }} px-3 py-2" style="font-size: 0.9rem;">{{ $soOrder->status }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 160px;">Pertashop</td>
                                    <td class="font-weight-bold">{{ $soOrder->shop->kode ?? '' }} - {{ $soOrder->shop->nama ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Order</td>
                                    <td>{{ $soOrder->order_date->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Supplier</td>
                                    <td>{{ $soOrder->supplier->nama ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nomor LO</td>
                                    <td>{{ $soOrder->lo_number }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 160px;">Total Volume</td>
                                    <td class="font-weight-bold">{{ number_format($soOrder->total_quantity_liter, 0, ',', '.') }} Liter</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Total Bayar</td>
                                    <td class="font-weight-bold text-primary">Rp {{ number_format($soOrder->total_amount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Jumlah Trip</td>
                                    <td>
                                        @php
                                            $received = $soOrder->trips->where('status', 'RECEIVED')->count();
                                        @endphp
                                        <span class="badge {{ $received === $soOrder->total_trips ? 'badge-success' : ($received > 0 ? 'badge-warning' : 'badge-secondary') }}">
                                            {{ $received }}/{{ $soOrder->total_trips }} Trip Diterima
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Harga per Liter</td>
                                    <td>Rp {{ number_format($soOrder->total_quantity_liter > 0 ? $soOrder->total_amount / $soOrder->total_quantity_liter : 0, 2, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Detail Trip Pengiriman --}}
            <div class="card card-info card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shipping-fast"></i> Detail Trip Pengiriman</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center" style="width: 70px;">Trip #</th>
                                <th>Tanggal Kirim (Estimasi)</th>
                                <th class="text-right">Volume (L)</th>
                                <th class="text-center">Status</th>
                                <th>Tanggal Diterima</th>
                                <th>Linked to</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($soOrder->trips as $trip)
                            <tr class="{{ $trip->status === 'RECEIVED' ? 'table-success' : '' }}">
                                <td class="text-center font-weight-bold">{{ $trip->trip_number }}</td>
                                <td>{{ $trip->estimated_delivery_date->format('d M Y') }}</td>
                                <td class="text-right">{{ number_format($trip->quantity_liter, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($trip->status === 'RECEIVED')
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> RECEIVED</span>
                                    @else
                                        <span class="badge badge-secondary"><i class="fas fa-clock"></i> PENDING</span>
                                    @endif
                                </td>
                                <td>
                                    @if($trip->received_at)
                                        {{ $trip->received_at->format('d M Y H:i') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($trip->incoming_id)
                                        <a href="{{ route('incomings.edit', $trip->incoming_id) }}" class="btn btn-xs btn-outline-info">
                                            <i class="fas fa-link"></i> Incoming #{{ $trip->incoming_id }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Back Button --}}
            <a href="{{ route('so-orders.index') }}" class="btn btn-secondary mb-4">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
    </section>
@endsection
