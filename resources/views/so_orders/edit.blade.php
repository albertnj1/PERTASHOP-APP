@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-edit text-info"></i> Edit Order SO</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('so-orders.index') }}">Order SO</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                <strong><i class="fas fa-exclamation-triangle"></i> Terdapat kesalahan:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
            @endif

            <form id="soOrderForm" action="{{ route('so-orders.update', $soOrder->id) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')

                {{-- Card: Informasi Utama --}}
                <div class="card card-primary card-outline shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-invoice"></i> Informasi Order SO — {{ $soOrder->so_number }}</h3>
                        <div class="card-tools">
                            @php
                                $badgeMap = ['PENDING' => 'badge-secondary', 'PARTIAL' => 'badge-warning', 'COMPLETED' => 'badge-success'];
                            @endphp
                            <span class="badge {{ $badgeMap[$soOrder->status] ?? 'badge-secondary' }} px-3 py-2">{{ $soOrder->status }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Kolom Kiri --}}
                            <div class="col-md-6 border-right pr-4">
                                <h5 class="font-weight-bold mb-3 border-bottom pb-2"><i class="fas fa-info-circle text-primary"></i> Data Order</h5>

                                <div class="form-group">
                                    <label for="shop_id"><i class="fas fa-store text-primary"></i> Pertashop <span class="text-danger">*</span></label>
                                    <select class="form-control" name="shop_id" id="shop_id" required>
                                        <option value="">--Pilih Pertashop--</option>
                                        @foreach ($shops as $shop)
                                            <option value="{{ $shop->id }}" @selected(old('shop_id', $soOrder->shop_id) == $shop->id)>{{ $shop->kode }} - {{ $shop->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="order_date"><i class="far fa-calendar-alt text-primary"></i> Tanggal Pembelian <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old('order_date', $soOrder->order_date->format('Y-m-d')) }}" required>
                                </div>

                                <div class="form-group">
                                    <label for="supplier_id"><i class="fas fa-truck text-primary"></i> Supplier <span class="text-danger">*</span></label>
                                    <select class="form-control" name="supplier_id" id="supplier_id" required>
                                        <option value="">--Pilih Supplier--</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected(old('supplier_id', $soOrder->supplier_id) == $supplier->id)>{{ $supplier->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="so_number"># Nomor SO <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="so_number" name="so_number" value="{{ old('so_number', $soOrder->so_number) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="lo_number"># Nomor LO <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="lo_number" name="lo_number" value="{{ old('lo_number', $soOrder->lo_number) }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Kolom Kanan --}}
                            <div class="col-md-6 pl-4">
                                <h5 class="font-weight-bold mb-3 border-bottom pb-2"><i class="fas fa-calculator text-primary"></i> Kuantitas & Pembayaran</h5>

                                <div class="form-group">
                                    <label for="total_quantity_liter"><i class="fas fa-tint text-primary"></i> Total Kuantitas (Liter) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="total_quantity_liter" name="total_quantity_liter" value="{{ old('total_quantity_liter', $soOrder->total_quantity_liter) }}" required>
                                        <div class="input-group-append"><span class="input-group-text">Liter</span></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="total_amount"><i class="fas fa-money-bill-wave text-primary"></i> Total Bayar <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="number" class="form-control" id="total_amount" name="total_amount" value="{{ old('total_amount', $soOrder->total_amount) }}" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="total_trips"><i class="fas fa-route text-primary"></i> Jumlah Trip <span class="text-danger">*</span></label>
                                    <select class="form-control" id="total_trips" name="total_trips" required>
                                        @for ($i = 1; $i <= 10; $i++)
                                            <option value="{{ $i }}" @selected(old('total_trips', $soOrder->total_trips) == $i)>{{ $i }} Kali Trip</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="alert mb-0 pb-2 pt-3 shadow-sm" style="background-color: #f0fdf4; border-left: 4px solid #22c55e;">
                                    <h6 class="font-weight-bold" style="color: #166534;"><i class="fas fa-chart-bar"></i> Preview</h6>
                                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.95rem;">
                                        <tr>
                                            <td class="py-1">Harga / Liter</td>
                                            <td class="text-right py-1 font-weight-bold" id="preview_harga_per_liter">Rp 0</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card: Jadwal Pengiriman per Trip --}}
                <div class="card card-info card-outline shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-shipping-fast"></i> Jadwal Pengiriman per Trip</h3>
                        <div class="card-tools">
                            <span class="badge badge-info" id="trip-volume-status">Total: 0 / 0 L</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0" id="trips-table">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" style="width: 60px;">Trip #</th>
                                    <th>Tanggal Kirim <span class="text-danger">*</span></th>
                                    <th>Volume Liter <span class="text-danger">*</span></th>
                                    <th class="text-center" style="width: 80px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="trips-body">
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div id="volume-validation-msg" class="text-danger font-weight-bold" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i> <span></span>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="card shadow-sm">
                    <div class="card-body bg-white d-flex justify-content-between align-items-center">
                        <a href="{{ route('so-orders.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Batal</a>
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('script')
<script>
$(document).ready(function() {
    // Existing trips from DB
    const existingTrips = @json($soOrder->trips->map(fn($t) => [
        'estimated_delivery_date' => $t->estimated_delivery_date->format('Y-m-d'),
        'quantity_liter' => $t->quantity_liter,
        'status' => $t->status,
    ])->values());

    const oldTrips = @json(old('trips', []));
    const trips = Object.keys(oldTrips).length > 0 ? oldTrips : existingTrips;
    const totalTripsSelect = $('#total_trips');
    const tripsBody = $('#trips-body');

    function formatRp(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }
    function formatNum(number) {
        return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(number);
    }

    function renderTripRows(count) {
        tripsBody.empty();
        for (let i = 0; i < count; i++) {
            let tripNum = i + 1;
            let date = (trips[i] && trips[i].estimated_delivery_date) ? trips[i].estimated_delivery_date : '';
            let vol = (trips[i] && trips[i].quantity_liter) ? trips[i].quantity_liter : '';
            let status = (trips[i] && trips[i].status) ? trips[i].status : 'PENDING';
            let badgeClass = status === 'RECEIVED' ? 'badge-success' : 'badge-secondary';

            let row = `
                <tr>
                    <td class="text-center align-middle font-weight-bold text-primary">${tripNum}</td>
                    <td><input type="date" class="form-control form-control-sm" name="trips[${i}][estimated_delivery_date]" value="${date}" required></td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" class="form-control trip-volume" name="trips[${i}][quantity_liter]" value="${vol}" required>
                            <div class="input-group-append"><span class="input-group-text">L</span></div>
                        </div>
                    </td>
                    <td class="text-center align-middle"><span class="badge ${badgeClass}">${status}</span></td>
                </tr>
            `;
            tripsBody.append(row);
        }
        updateVolumeValidation();
    }

    function updateVolumeValidation() {
        let totalOrder = parseFloat($('#total_quantity_liter').val()) || 0;
        let totalTrip = 0;
        $('.trip-volume').each(function() { totalTrip += parseFloat($(this).val()) || 0; });

        let statusBadge = $('#trip-volume-status');
        let msgDiv = $('#volume-validation-msg');
        statusBadge.text(`Total: ${formatNum(totalTrip)} / ${formatNum(totalOrder)} L`);

        if (totalOrder > 0 && Math.abs(totalTrip - totalOrder) > 0.01) {
            let diff = totalTrip - totalOrder;
            let text = diff > 0
                ? `Volume trip melebihi total order sebesar ${formatNum(diff)} L`
                : `Volume trip kurang dari total order sebesar ${formatNum(Math.abs(diff))} L`;
            statusBadge.removeClass('badge-info badge-success').addClass('badge-danger');
            msgDiv.show().find('span').text(text);
        } else if (totalOrder > 0) {
            statusBadge.removeClass('badge-info badge-danger').addClass('badge-success');
            msgDiv.hide();
        }
    }

    function updatePreview() {
        let totalVol = parseFloat($('#total_quantity_liter').val()) || 0;
        let totalAmt = parseFloat($('#total_amount').val()) || 0;
        let harga = totalVol > 0 ? (totalAmt / totalVol) : 0;
        $('#preview_harga_per_liter').text(formatRp(harga) + ' / L');
    }

    totalTripsSelect.on('change', function() { renderTripRows(parseInt(this.value) || 1); });
    $('#total_quantity_liter, #total_amount').on('keyup change', function() { updatePreview(); updateVolumeValidation(); });
    $(document).on('keyup change', '.trip-volume', updateVolumeValidation);

    // Initial render
    renderTripRows(parseInt(totalTripsSelect.val()) || 1);
    updatePreview();
});
</script>
@endpush
