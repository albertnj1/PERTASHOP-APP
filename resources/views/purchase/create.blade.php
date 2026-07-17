@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Tambah Pembelian</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Pembelian</a></li>
                        <li class="breadcrumb-item active">Tambah</li>
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
                        <h3 class="card-title">Pembelian</h3>
                    </div>

                </div>
                <form id="insertForm" action="{{ route('purchases.store') }}" method="POST" class="needs-validation"
                    novalidate>
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <!-- Kolom Kiri: Meta Data -->
                            <div class="col-md-6 border-right pr-4">
                                <h5 class="font-weight-bold mb-3 border-bottom pb-2">Informasi DO</h5>
                                <div class="form-group">
                                    <label for="purchase_date">Hari/Tanggal Pembelian</label>
                                    <input type="date" class="form-control @error('purchase_date') is-invalid @enderror"
                                        id="purchase_date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                                </div>

                                <div class="form-group">
                                    <label for="supplier_id">Supplier</label>
                                    <select class="form-control @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplier_id" required>
                                        <option value="">--Pilih Supplier--</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if(Auth::user()->role == 'admin' || Auth::user()->role == 'super-admin')
                                <div class="form-group">
                                    <label for="shop_id">Pertashop</label>
                                    <select class="form-control @error('shop_id') is-invalid @enderror" name="shop_id" id="shop_id" required>
                                        <option value="">--Pilih Pertashop--</option>
                                        @foreach ($shops as $shop)
                                            <option value="{{ $shop->id }}" @selected(old('shop_id') == $shop->id)>{{ $shop->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="no_so">No. SO</label>
                                    <input type="text" class="form-control @error('no_so') is-invalid @enderror" id="no_so" name="no_so" value="{{ old('no_so') }}" required>
                                </div>

                                <div class="form-group">
                                    <label for="no_lo">No. LO</label>
                                    <input type="text" class="form-control @error('no_lo') is-invalid @enderror" id="no_lo" name="no_lo" value="{{ old('no_lo') }}" required>
                                </div>
                                @endif

                                <div class="form-group">
                                    <label for="trip">Trip</label>
                                    <input type="text" class="form-control @error('trip') is-invalid @enderror" id="trip" name="trip" value="{{ old('trip') }}">
                                </div>

                                <div class="form-group">
                                    <label for="delivery_date">Tanggal Pengiriman</label>
                                    <input type="date" class="form-control @error('delivery_date') is-invalid @enderror" id="delivery_date" name="delivery_date" value="{{ old('delivery_date', date('Y-m-d')) }}">
                                </div>
                            </div>

                            <!-- Kolom Kanan: Keuangan & Pajak -->
                            <div class="col-md-6 pl-4">
                                <h5 class="font-weight-bold mb-3 border-bottom pb-2">Rincian Keuangan DO</h5>
                                
                                <div class="form-group">
                                    <label for="jumlah_kl">Jumlah (KL)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('jumlah_kl') is-invalid @enderror" id="jumlah_kl" name="jumlah_kl" value="{{ old('jumlah_kl') }}" required>
                                        <div class="input-group-append"><span class="input-group-text">KL</span></div>
                                    </div>
                                    <small class="text-muted">Akan otomatis disimpan sebagai liter di database (x1000).</small>
                                </div>

                                <div class="form-group">
                                    <label for="total_nominal">Total Nominal (Transfer Pembayaran)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="number" class="form-control @error('total_nominal') is-invalid @enderror" id="total_nominal" name="total_nominal" value="{{ old('total_nominal') }}" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="catatan_debit_credit">Catatan Debit / Credit (Selisih DO)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="number" class="form-control @error('catatan_debit_credit') is-invalid @enderror" id="catatan_debit_credit" name="catatan_debit_credit" value="{{ old('catatan_debit_credit', 0) }}">
                                    </div>
                                    <small class="text-muted">Nominal yang akan dikurangkan dari Total Nominal untuk mencari Total Kotor.</small>
                                </div>

                                <h6 class="mt-4 font-weight-bold">Setting Persentase Pajak (%)</h6>
                                <div class="row">
                                    <div class="col-3">
                                        <label style="font-size: 0.8rem;">Total Net</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm calc-input" id="persen_net" name="persen_net" value="{{ old('persen_net', 85.06) }}" required>
                                    </div>
                                    <div class="col-3">
                                        <label style="font-size: 0.8rem;">PPN</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm calc-input" id="persen_ppn" name="persen_ppn" value="{{ old('persen_ppn', 10.11) }}" required>
                                    </div>
                                    <div class="col-3">
                                        <label style="font-size: 0.8rem;">PPh</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm calc-input" id="persen_pph" name="persen_pph" value="{{ old('persen_pph', 0.23) }}" required>
                                    </div>
                                    <div class="col-3">
                                        <label style="font-size: 0.8rem;">PBBKB</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm calc-input" id="persen_pbbkb" name="persen_pbbkb" value="{{ old('persen_pbbkb', 4.60) }}" required>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-4 mb-0 pb-1 pt-3 shadow-sm" style="background-color: #f8f9fc; border-left: 4px solid #4e73df;">
                                    <h6 class="font-weight-bold text-primary"><i class="fas fa-calculator me-2"></i> Preview Kalkulasi Otomatis</h6>
                                    <table class="table table-sm table-borderless text-dark mb-0" style="font-size: 0.95rem;">
                                        <tr><td class="py-1">Total Kotor (Base)</td><td class="text-right py-1 font-weight-bold" id="prev_kotor">Rp 0</td></tr>
                                        <tr><td class="py-1">Total Net</td><td class="text-right py-1" id="prev_net">Rp 0</td></tr>
                                        <tr><td class="py-1">PPN</td><td class="text-right py-1" id="prev_ppn">Rp 0</td></tr>
                                        <tr><td class="py-1">PPh</td><td class="text-right py-1" id="prev_pph">Rp 0</td></tr>
                                        <tr><td class="py-1">PBBKB</td><td class="text-right py-1" id="prev_pbbkb">Rp 0</td></tr>
                                        <tr style="border-top: 1px dashed #ccc;">
                                            <td class="py-2 text-primary font-weight-bold">Harga / Liter</td>
                                            <td class="text-right py-2 font-weight-bold text-primary" id="prev_harga">Rp 0</td>
                                        </tr>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top">
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('purchases.index') }}" class="btn btn-secondary mr-2">Batal</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save mr-2"></i> Simpan DO</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            function formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number);
            }
            function formatDesimal(number) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2, maximumFractionDigits: 3 }).format(number);
            }

            function hitungPajak() {
                let kl = parseFloat($('#jumlah_kl').val()) || 0;
                let nominal = parseFloat($('#total_nominal').val()) || 0;
                let debitCredit = parseFloat($('#catatan_debit_credit').val()) || 0;
                
                let p_net = parseFloat($('#persen_net').val()) || 0;
                let p_ppn = parseFloat($('#persen_ppn').val()) || 0;
                let p_pph = parseFloat($('#persen_pph').val()) || 0;
                let p_pbbkb = parseFloat($('#persen_pbbkb').val()) || 0;

                // Hitungan
                let kotor = nominal - debitCredit;
                let net = kotor * (p_net / 100);
                let ppn = kotor * (p_ppn / 100);
                let pph = kotor * (p_pph / 100);
                let pbbkb = kotor * (p_pbbkb / 100);
                
                let liter = kl * 1000;
                let hargaLiter = liter > 0 ? (kotor / liter) : 0;

                // Tampilkan di layar
                $('#prev_kotor').text(formatRupiah(kotor));
                $('#prev_net').text(formatRupiah(net));
                $('#prev_ppn').text(formatRupiah(ppn));
                $('#prev_pph').text(formatRupiah(pph));
                $('#prev_pbbkb').text(formatRupiah(pbbkb));
                $('#prev_harga').text(formatDesimal(hargaLiter) + ' / L');
            }

            $('#jumlah_kl, #total_nominal, #catatan_debit_credit, .calc-input').on('keyup change', hitungPajak);
            
            // Initial call on load (in case of validation error and old inputs exist)
            hitungPajak();
        });
    </script>
@endpush
