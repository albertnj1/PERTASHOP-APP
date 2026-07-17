@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Tambah Penerimaan</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('incomings.index') }}">Penerimaan</a></li>
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
                        <h3 class="card-title">Penerimaan</h3>
                    </div>

                </div>
                <form id="insertForm" action="{{ route('incomings.store') }}" method="POST" class="needs-validation"
                    novalidate>
                    @csrf
                    <div class="card-body">

                        <div class="form-group row">
                            <label for="incoming_date" class="col-sm-4 col-form-label">Hari/Tanggal</label>
                            <div class="col-sm-8">
                                <input type="date" class="form-control @error('incoming_date') is-invalid @enderror"
                                    id="incoming_date" name="incoming_date" value="{{ old('incoming_date', date('Y-m-d')) }}">
                                @error('incoming_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="no_so" class="col-sm-4 col-form-label">Order (No. SO)</label>
                            <div class="col-sm-8">
                                <select name="purchase_id" id="purchase_id"
                                    class="form-control @error('purchase_id') is-invalid @enderror">
                                    <option value="">--Pilih No. SO--</option>
                                    @foreach ($purchases as $purchase)
                                        <option value="{{ $purchase->id }}" @selected($purchase->id == old('purchase_id'))
                                            data-purchase='@json($purchase)'>
                                            {{ $purchase->no_so }}</option>
                                    @endforeach
                                </select>
                                @error('purchase_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="sopir" class="col-sm-4 col-form-label">Sopir</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('sopir') is-invalid @enderror"
                                    id="sopir" name="sopir" value="{{ old('sopir') }}">
                                @error('sopir')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="no_polisi" class="col-sm-4 col-form-label">Plat Tanki</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('no_polisi') is-invalid @enderror"
                                    id="no_polisi" name="no_polisi" value="{{ old('no_polisi') }}">
                                @error('no_polisi')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="asal_pengirim" class="col-sm-4 col-form-label">Asal Pengirim</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control @error('asal_pengirim') is-invalid @enderror"
                                    id="asal_pengirim" name="asal_pengirim" value="{{ old('asal_pengirim') }}">
                                @error('asal_pengirim')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>
                        <h5 class="font-weight-bold">Pengukuran</h5>

                        
                        <div class="form-group row">
                            <label for="maos_volume" class="col-sm-4 col-form-label">Volume</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="maos_volume" name="maos_volume" value="{{ old('maos_volume') }}">
                                    <div class="input-group-append"><span class="input-group-text">&ell;</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="maos_suhu" class="col-sm-4 col-form-label">Suhu</label>
                            <div class="col-sm-8">
                                <input type="number" step="0.01" class="form-control" id="maos_suhu" name="maos_suhu" value="{{ old('maos_suhu') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="maos_density" class="col-sm-4 col-form-label">Density</label>
                            <div class="col-sm-8">
                                <input type="number" step="0.01" class="form-control" id="maos_density" name="maos_density" value="{{ old('maos_density') }}">
                            </div>
                        </div>

                        <hr>
                        <h5 class="font-weight-bold">Waktu Penerimaan</h5>
                        <div class="form-group row">
                            <label for="jam_berangkat" class="col-sm-4 col-form-label">Jam Berangkat</label>
                            <div class="col-sm-8">
                                <input type="time" class="form-control" id="jam_berangkat" name="jam_berangkat" value="{{ old('jam_berangkat') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="jam_tiba" class="col-sm-4 col-form-label">Jam Tiba</label>
                            <div class="col-sm-8">
                                <input type="time" class="form-control" id="jam_tiba" name="jam_tiba" value="{{ old('jam_tiba') }}">
                            </div>
                        </div>

                        @if(Auth::user()->role == 'admin' || Auth::user()->role == 'super-admin')
                        <hr>
                        <h5 class="font-weight-bold text-danger">Data Admin / Superadmin</h5>
                        <div class="form-group row">
                            <label for="stock_terima_bbm" class="col-sm-4 col-form-label">Stock Terima BBM</label>
                            <div class="col-sm-8">
                                <input type="number" step="0.01" class="form-control" id="stock_terima_bbm" name="stock_terima_bbm" value="{{ old('stock_terima_bbm') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="dens_temp" class="col-sm-4 col-form-label">Dens/Temp</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="dens_temp" name="dens_temp" value="{{ old('dens_temp') }}">
                            </div>
                        </div>
                        @endif

                        <hr>
                        <h5 class="font-weight-bold">Pengukuran Terima (Real)</h5>

                        <div class="form-group row">
                            <label for="stik_awal" class="col-sm-4 col-form-label">🅰️ Sebelum Curah (Stik)</label>
                            <div class="col-sm-8">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control @error('stik_awal') is-invalid @enderror" id="stik_awal" name="stik_awal" value="{{ old('stik_awal') }}">
                                            <div class="input-group-append"><span class="input-group-text">cm</span></div>
                                        </div>
                                        @error('stik_awal')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="stok_awal" name="stok_awal" value="{{ old('stok_awal') }}" readonly>
                                            <div class="input-group-append"><span class="input-group-text">&ell;</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="stik_akhir" class="col-sm-4 col-form-label">🅱️ Stik Setelah Curah</label>
                            <div class="col-sm-8">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control @error('stik_akhir') is-invalid @enderror" id="stik_akhir" name="stik_akhir" value="{{ old('stik_akhir') }}">
                                            <div class="input-group-append"><span class="input-group-text">cm</span></div>
                                        </div>
                                        @error('stik_akhir')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="stok_akhir" name="stok_akhir" value="{{ old('stok_akhir') }}" readonly>
                                            <div class="input-group-append"><span class="input-group-text">&ell;</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="penerimaan_real" class="col-sm-4 col-form-label text-success font-weight-bold">✅ Penerimaan Real (🅱️-🅰️)</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control @error('penerimaan_real') is-invalid @enderror" id="penerimaan_real" name="penerimaan_real" value="{{ old('penerimaan_real') }}" readonly style="background-color: #e8f5e9; font-weight: bold;">
                                    <div class="input-group-append"><span class="input-group-text">&ell;</span></div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="font-weight-bold">Data Terima Lapangan</h5>
                        <div class="form-group row">
                            <label for="terima_volume" class="col-sm-4 col-form-label">Volume Terima</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="terima_volume" name="terima_volume" value="{{ old('terima_volume') }}">
                                    <div class="input-group-append"><span class="input-group-text">&ell;</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="terima_suhu" class="col-sm-4 col-form-label">Suhu Terima</label>
                            <div class="col-sm-8">
                                <input type="number" step="0.01" class="form-control" id="terima_suhu" name="terima_suhu" value="{{ old('terima_suhu') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="terima_density" class="col-sm-4 col-form-label">Density Terima</label>
                            <div class="col-sm-8">
                                <input type="number" step="0.01" class="form-control" id="terima_density" name="terima_density" value="{{ old('terima_density') }}">
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('script')
    <script>
        const table53Grid = {
            0.700: {0.0: 0.6869, 5.0: 0.6913, 10.0: 0.6957, 15.0: 0.7000, 20.0: 0.7043, 25.0: 0.7085, 30.0: 0.7127, 35.0: 0.7168, 40.0: 0.7209, 45.0: 0.7249, 50.0: 0.7289},
            0.710: {0.0: 0.6971, 5.0: 0.7014, 10.0: 0.7057, 15.0: 0.7100, 20.0: 0.7142, 25.0: 0.7184, 30.0: 0.7225, 35.0: 0.7265, 40.0: 0.7305, 45.0: 0.7345, 50.0: 0.7384},
            0.720: {0.0: 0.7073, 5.0: 0.7116, 10.0: 0.7158, 15.0: 0.7200, 20.0: 0.7241, 25.0: 0.7282, 30.0: 0.7323, 35.0: 0.7362, 40.0: 0.7402, 45.0: 0.7441, 50.0: 0.7479},
            0.730: {0.0: 0.7175, 5.0: 0.7217, 10.0: 0.7259, 15.0: 0.7300, 20.0: 0.7341, 25.0: 0.7381, 30.0: 0.7421, 35.0: 0.7460, 40.0: 0.7499, 45.0: 0.7536, 50.0: 0.7573},
            0.740: {0.0: 0.7277, 5.0: 0.7319, 10.0: 0.7360, 15.0: 0.7400, 20.0: 0.7440, 25.0: 0.7480, 30.0: 0.7518, 35.0: 0.7557, 40.0: 0.7594, 45.0: 0.7631, 50.0: 0.7667},
            0.750: {0.0: 0.7379, 5.0: 0.7420, 10.0: 0.7460, 15.0: 0.7500, 20.0: 0.7539, 25.0: 0.7578, 30.0: 0.7616, 35.0: 0.7653, 40.0: 0.7689, 45.0: 0.7728, 50.0: 0.7767},
            0.760: {0.0: 0.7471, 5.0: 0.7511, 10.0: 0.7551, 15.0: 0.7590, 20.0: 0.7628, 25.0: 0.7666, 30.0: 0.7708, 35.0: 0.7748, 40.0: 0.7787, 45.0: 0.7825, 50.0: 0.7863}
        };

        function getDensity15C(obsDens, obsTemp) {
            if (obsDens < 0.700 || obsDens > 0.760 || obsTemp < 0 || obsTemp > 50) return null;
            let densKeys = Object.keys(table53Grid).map(Number).sort((a,b)=>a-b);
            let tempKeys = Object.keys(table53Grid[0.7]).map(Number).sort((a,b)=>a-b);

            let d1 = Math.max(...densKeys.filter(d => d <= obsDens));
            let d2 = Math.min(...densKeys.filter(d => d >= obsDens));
            let t1 = Math.max(...tempKeys.filter(t => t <= obsTemp));
            let t2 = Math.min(...tempKeys.filter(t => t >= obsTemp));

            let val_t1, val_t2;
            if (d1 === d2) {
                val_t1 = table53Grid[d1][t1];
                val_t2 = table53Grid[d1][t2];
            } else {
                let fd = (obsDens - d1) / (d2 - d1);
                val_t1 = table53Grid[d1][t1] + fd * (table53Grid[d2][t1] - table53Grid[d1][t1]);
                val_t2 = table53Grid[d1][t2] + fd * (table53Grid[d2][t2] - table53Grid[d1][t2]);
            }
            if (t1 === t2) return val_t1;
            let ft = (obsTemp - t1) / (t2 - t1);
            return val_t1 + ft * (val_t2 - val_t1);
        }

        $(document).ready(function() {
            //calculate volume aktual (stok akhir - stok awal)
            $('#stik_akhir, #stik_awal, #purchase_id').on('input change', function() {
                let stik_akhir = parseFloat($('#stik_akhir').val()) || 0;
                let stik_awal = parseFloat($('#stik_awal').val()) || 0;
                let skala = {{ $shop->skala ?? 1 }};
                let stok_akhir = stik_akhir * skala;
                let stok_awal = stik_awal * skala;
                let volume_aktual = stok_akhir - stok_awal;
                $('#stok_awal').val(stok_awal.toFixed(2))
                $('#stok_akhir').val(stok_akhir.toFixed(2));
                $('#penerimaan_real').val(volume_aktual.toFixed(2));

                // Calculate losses/gain
                let selectedSO = $('#purchase_id').find(':selected');
                if (selectedSO.val() && selectedSO.data('purchase')) {
                    let purchaseVol = parseFloat(selectedSO.data('purchase').volume) || 0;
                    let diff = volume_aktual - purchaseVol;
                    
                    let lgText = diff > 0 ? `Gain: +${diff.toFixed(2)} &ell;` : (diff < 0 ? `Loses: ${diff.toFixed(2)} &ell;` : `Loses/Gain: 0`);
                    let lgColor = diff > 0 ? 'text-success' : (diff < -6 ? 'text-danger' : 'text-primary'); // Assuming 6L tolerance
                    
                    if ($('#losses_gain_feedback').length === 0) {
                        $('#penerimaan_real').parent().after(`<div id="losses_gain_feedback" class="mt-2 font-weight-bold ${lgColor}">${lgText}</div>`);
                    } else {
                        $('#losses_gain_feedback').removeClass('text-success text-danger text-primary').addClass(lgColor).html(lgText);
                    }
                }
            });

            // Calculate density at 15C and Pertamax validity
            $('#terima_suhu, #terima_density').on('input', function() {
                let suhu = parseFloat($('#terima_suhu').val());
                let density = parseFloat($('#terima_density').val());
                
                if (!isNaN(suhu) && !isNaN(density)) {
                    let d15 = getDensity15C(density, suhu);
                    let isPertamax = density >= 0.700 && density <= 0.759;
                    
                    let feedbackHtml = '';
                    if (isPertamax) {
                        feedbackHtml = `<span class="text-success font-weight-bold"><i class="fas fa-check-circle"></i> Terindikasi Pertamax (Density 15°C: ${d15 ? d15.toFixed(4) : '-'})</span>`;
                    } else {
                        feedbackHtml = `<span class="text-danger font-weight-bold"><i class="fas fa-times-circle"></i> Bukan Pertamax (Di luar rentang 0.700-0.759)</span>`;
                    }
                    
                    if ($('#pertamax_feedback').length === 0) {
                        $('#terima_density').parent().after(`<div id="pertamax_feedback" class="mt-2">${feedbackHtml}</div>`);
                    } else {
                        $('#pertamax_feedback').html(feedbackHtml);
                    }
                } else {
                    $('#pertamax_feedback').remove();
                }
            });
        })
    </script>
@endpush
