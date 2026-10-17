@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-plus-circle text-primary"></i> Tambah Order SO</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('so-orders.index') }}">Order SO</a></li>
                        <li class="breadcrumb-item active">Tambah</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            {{-- Validation Errors --}}
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

            <form id="soOrderForm" action="{{ route('so-orders.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf

                {{-- Card: Informasi Utama --}}
                <div class="card card-primary card-outline shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-invoice"></i> Informasi Order SO</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Kolom Kiri --}}
                            <div class="col-md-6 border-right pr-4">
                                <h5 class="font-weight-bold mb-3 border-bottom pb-2"><i class="fas fa-info-circle text-primary"></i> Data Order</h5>

                                <div class="form-group">
                                    <label for="shop_id"><i class="fas fa-store text-primary"></i> Pertashop (Outlet) <span class="text-danger">*</span></label>
                                    <select class="form-control @error('shop_id') is-invalid @enderror" name="shop_id" id="shop_id" required>
                                        <option value="">--Pilih Pertashop--</option>
                                        @foreach ($shops as $shop)
                                            <option value="{{ $shop->id }}" @selected(old('shop_id') == $shop->id)>{{ $shop->kode }} - {{ $shop->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="order_date"><i class="far fa-calendar-alt text-primary"></i> Hari & Tanggal Pembelian <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('order_date') is-invalid @enderror" id="order_date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" required>
                                </div>

                                <div class="form-group">
                                    <label for="supplier_id"><i class="fas fa-truck text-primary"></i> Supplier <span class="text-danger">*</span></label>
                                    <select class="form-control @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplier_id" required>
                                        <option value="">--Pilih Supplier--</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="so_number"><span class="text-primary font-weight-bold">#</span> Nomor SO <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('so_number') is-invalid @enderror" id="so_number" name="so_number" value="{{ old('so_number') }}" placeholder="Nomor SO" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="lo_number"><span class="text-primary font-weight-bold">#</span> Nomor LO <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('lo_number') is-invalid @enderror" id="lo_number" name="lo_number" value="{{ old('lo_number') }}" placeholder="Nomor LO" required>
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
                                        <input type="number" step="0.01" class="form-control @error('total_quantity_liter') is-invalid @enderror" id="total_quantity_liter" name="total_quantity_liter" value="{{ old('total_quantity_liter') }}" placeholder="Contoh: 4000" required>
                                        <div class="input-group-append"><span class="input-group-text">Liter</span></div>
                                    </div>
                                    <small class="text-muted">Total seluruh volume BBM yang dipesan pada SO ini.</small>
                                </div>

                                <div class="form-group">
                                    <label for="total_amount"><i class="fas fa-money-bill-wave text-primary"></i> Total Bayar <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="number" class="form-control @error('total_amount') is-invalid @enderror" id="total_amount" name="total_amount" value="{{ old('total_amount') }}" placeholder="Total pembayaran" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="total_trips"><i class="fas fa-route text-primary"></i> Jumlah Trip Pengiriman <span class="text-danger">*</span></label>
                                    <select class="form-control @error('total_trips') is-invalid @enderror" id="total_trips" name="total_trips" required>
                                        @for ($i = 1; $i <= 10; $i++)
                                            <option value="{{ $i }}" @selected(old('total_trips', 1) == $i)>{{ $i }} Kali Trip</option>
                                        @endfor
                                    </select>
                                    <small class="text-muted">Pilih jumlah trip, lalu isi jadwal pengiriman per trip di bawah.</small>
                                </div>

                                {{-- Preview Harga per Liter --}}
                                <div class="alert mb-0 pb-2 pt-3 shadow-sm" style="background-color: #f0fdf4; border-left: 4px solid #22c55e;">
                                    <h6 class="font-weight-bold" style="color: #166534;"><i class="fas fa-chart-bar"></i> Preview</h6>
                                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.95rem;">
                                        <tr>
                                            <td class="py-1">Harga / Liter</td>
                                            <td class="text-right py-1 font-weight-bold" id="preview_harga_per_liter">Rp 0</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1">Volume / Trip (rata-rata)</td>
                                            <td class="text-right py-1 font-weight-bold" id="preview_vol_per_trip">0 L</td>
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
                                {{-- Rows generated by JavaScript --}}
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
                        <button type="submit" class="btn btn-primary btn-lg px-5" id="btn-submit">
                            <i class="fas fa-save mr-2"></i> Simpan Order SO
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
    const oldTrips = @json(old('trips', []));
    const totalTripsSelect = $('#total_trips');
    const tripsBody = $('#trips-body');

    function formatRp(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    function formatNum(number) {
        return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(number);
    }

    /**
     * Render N baris trip di tabel
     */
    function renderTripRows(count) {
        tripsBody.empty();
        let totalVolume = parseFloat($('#total_quantity_liter').val()) || 0;
        let defaultVolPerTrip = count > 0 ? (totalVolume / count) : 0;

        for (let i = 0; i < count; i++) {
            let tripNum = i + 1;
            let oldDate = (oldTrips[i] && oldTrips[i].estimated_delivery_date) ? oldTrips[i].estimated_delivery_date : '';
            let oldVol = (oldTrips[i] && oldTrips[i].quantity_liter) ? oldTrips[i].quantity_liter : (defaultVolPerTrip > 0 ? defaultVolPerTrip.toFixed(2) : '');

            let row = `
                <tr>
                    <td class="text-center align-middle font-weight-bold text-primary">${tripNum}</td>
                    <td>
                        <input type="date" class="form-control form-control-sm" 
                               name="trips[${i}][estimated_delivery_date]" 
                               value="${oldDate}" required>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" class="form-control trip-volume" 
                                   name="trips[${i}][quantity_liter]" 
                                   value="${oldVol}" 
                                   placeholder="Volume Liter" required>
                            <div class="input-group-append"><span class="input-group-text">L</span></div>
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge badge-secondary">PENDING</span>
                    </td>
                </tr>
            `;
            tripsBody.append(row);
        }

        updateVolumeValidation();
    }

    /**
     * Validasi total volume trip vs total kuantitas order
     */
    function updateVolumeValidation() {
        let totalOrder = parseFloat($('#total_quantity_liter').val()) || 0;
        let totalTrip = 0;

        $('.trip-volume').each(function() {
            totalTrip += parseFloat($(this).val()) || 0;
        });

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
        } else {
            statusBadge.removeClass('badge-danger badge-success').addClass('badge-info');
            msgDiv.hide();
        }
    }

    /**
     * Preview harga per liter & volume rata-rata per trip
     */
    function updatePreview() {
        let totalVol = parseFloat($('#total_quantity_liter').val()) || 0;
        let totalAmt = parseFloat($('#total_amount').val()) || 0;
        let trips = parseInt($('#total_trips').val()) || 1;

        let hargaPerLiter = totalVol > 0 ? (totalAmt / totalVol) : 0;
        let volPerTrip = trips > 0 ? (totalVol / trips) : 0;

        $('#preview_harga_per_liter').text(formatRp(hargaPerLiter) + ' / L');
        $('#preview_vol_per_trip').text(formatNum(volPerTrip) + ' L');
    }

    // ── Event Listeners ─────────────────────────────────────────────
    totalTripsSelect.on('change', function() {
        renderTripRows(parseInt(this.value) || 1);
    });

    $('#total_quantity_liter, #total_amount').on('keyup change', function() {
        updatePreview();
        updateVolumeValidation();
    });

    // Live validation saat volume trip berubah
    $(document).on('keyup change', '.trip-volume', function() {
        updateVolumeValidation();
    });

    // Auto-distribute volume merata saat total_quantity_liter berubah dan trip sudah ada
    $('#total_quantity_liter').on('change', function() {
        let totalVol = parseFloat(this.value) || 0;
        let tripCount = parseInt($('#total_trips').val()) || 1;
        let volPerTrip = totalVol / tripCount;

        // Hanya auto-distribute jika user belum mengubah volume trip secara manual
        let allEmpty = true;
        $('.trip-volume').each(function() {
            if ($(this).val() !== '' && parseFloat($(this).val()) !== 0) {
                allEmpty = false;
            }
        });

        if (allEmpty || confirm('Distribusikan volume merata ke seluruh trip?')) {
            $('.trip-volume').val(volPerTrip.toFixed(2));
            updateVolumeValidation();
        }
    });

    // ── Initial Render ──────────────────────────────────────────────
    renderTripRows(parseInt(totalTripsSelect.val()) || 1);
    updatePreview();
});
</script>
@endpush
