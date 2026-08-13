@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 style="font-weight:700;color:#0f172a;">
                        <a href="{{ route('prices.index') }}" class="btn btn-outline-secondary mr-2" style="border-radius: 50%; width: 40px; height: 40px; padding: 0; line-height: 38px; text-align: center;">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        Tambah Harga BBM
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('prices.index') }}">Harga BBM</a></li>
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
                        <h3 class="card-title">Harga BBM</h3>
                    </div>

                </div>
                <form id="insertForm" action="{{ route('prices.store') }}" method="POST" class="needs-validation"
                    novalidate>
                    @csrf
                    <div class="card-body">

                        @if(Auth::user()->role == 'operator')
                            <input type="hidden" name="shop_id" value="{{ Auth::user()->operator->shop_id }}">
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Pertashop</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" value="{{ Auth::user()->operator->shop->nama ?? '' }}" readonly>
                                </div>
                            </div>
                        @else
                            <div class="form-group row">
                                <label for="shop_id" class="col-sm-4 col-form-label">Pertashop</label>
                                <div class="col-sm-8">
                                    <select class="form-control @error('shop_id') is-invalid @enderror" id="shop_id" name="shop_id" required>
                                        <option value="">-- Pilih Pertashop --</option>
                                        @foreach($shops as $shop)
                                            <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                                                {{ $shop->kode }} {{ $shop->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('shop_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Waktu Perubahan</label>
                            <div class="col-sm-8">
                                <div class="d-flex align-items-center justify-content-between p-3 mb-2" 
                                     style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); color: #ffffff;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar-alt mr-3" style="font-size: 1.8rem; opacity: 0.9;"></i>
                                        <div>
                                            <div id="realtime-day-date" class="font-weight-bold" style="font-size: 1.15rem; letter-spacing: 0.5px;">
                                                Memuat...
                                            </div>
                                            <div style="font-size: 0.85rem; opacity: 0.75;">Tanggal Pencatatan Harga Baru</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div id="realtime-time" class="font-weight-bold" style="font-size: 1.6rem; font-family: 'Courier New', Courier, monospace; letter-spacing: 2px; text-shadow: 0 2px 5px rgba(0,0,0,0.3); color: #00ffcc;">
                                            00:00:00
                                        </div>
                                        <div id="realtime-location" style="font-size: 0.8rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">
                                            <i class="fas fa-map-marker-alt mr-1"></i> Mendeteksi lokasi device...
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">Waktu di atas berjalan secara real-time dan akan otomatis tercatat ke detik yang paling akurat saat Anda menekan tombol Simpan.</small>
                                <!-- Hidden inputs to submit the exact time and location -->
                                <input type="hidden" id="created_at" name="created_at">
                                <input type="hidden" id="jam" name="jam">
                                <input type="hidden" id="lokasi_device" name="lokasi_device">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="totalisator_perubahan" class="col-sm-4 col-form-label">Totalisator Saat Berubah</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" step="0.001" class="form-control @error('totalisator_perubahan') is-invalid @enderror"
                                        id="totalisator_perubahan" name="totalisator_perubahan" value="{{ old('totalisator_perubahan') }}" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">&ell;</span>
                                    </div>
                                </div>
                                <small class="text-muted">Angka totalisator tepat pada saat harga baru mulai berlaku</small>
                                @error('totalisator_perubahan')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if(Auth::user()->role !== 'operator')
                        <div class="form-group row">
                            <label for="harga_beli" class="col-sm-4 col-form-label">Harga Beli Baru</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control @error('harga_beli') is-invalid @enderror"
                                        id="harga_beli" name="harga_beli" value="{{ old('harga_beli') }}" required>
                                </div>
                                @error('harga_beli')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        @endif

                        <div class="form-group row">
                            <label for="harga_jual" class="col-sm-4 col-form-label">Harga Jual</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control @error('harga_jual') is-invalid @enderror"
                                        id="harga_jual" name="harga_jual" value="{{ old('harga_jual') }}">
                                </div>
                                @error('harga_jual')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
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
    function updateClock() {
        const now = new Date();
        
        // Format Day and Date (e.g., Senin, 14 Juli 2026)
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        const dayName = days[now.getDay()];
        const date = now.getDate();
        const monthName = months[now.getMonth()];
        const year = now.getFullYear();
        
        const fullDateStr = `${dayName}, ${date} ${monthName} ${year}`;
        document.getElementById('realtime-day-date').innerText = fullDateStr;

        // Format Time (e.g., 23:56:30)
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        const timeStr = `${hours}:${minutes}:${seconds}`;
        document.getElementById('realtime-time').innerText = timeStr;

        // Fill hidden inputs
        // For date, format: YYYY-MM-DD
        const monthNum = String(now.getMonth() + 1).padStart(2, '0');
        const dateNum = String(now.getDate()).padStart(2, '0');
        document.getElementById('created_at').value = `${year}-${monthNum}-${dateNum}`;
        
        // For jam, format: HH:mm (or HH:mm:ss if the server needs seconds)
        document.getElementById('jam').value = `${hours}:${minutes}`;
    }

    // Run clock immediately, then every second
    updateClock();
    setInterval(updateClock, 1000);

    // Get Geolocation
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            // Reverse geocoding using free Nominatim API
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
                .then(response => response.json())
                .then(data => {
                    let locationName = "Lokasi tidak diketahui";
                    if (data && data.address) {
                        const city = data.address.city || data.address.town || data.address.village || data.address.county;
                        const state = data.address.state;
                        if (city && state) {
                            locationName = `${city}, ${state}`;
                        } else if (city) {
                            locationName = city;
                        } else if (state) {
                            locationName = state;
                        }
                    }
                    document.getElementById('realtime-location').innerHTML = `<i class="fas fa-map-marker-alt text-danger mr-1"></i> ${locationName}`;
                    document.getElementById('lokasi_device').value = locationName;
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('realtime-location').innerHTML = `<i class="fas fa-map-marker-alt text-warning mr-1"></i> Gagal memuat lokasi`;
                });
        }, function(error) {
            document.getElementById('realtime-location').innerHTML = `<i class="fas fa-map-marker-alt text-danger mr-1"></i> Akses lokasi ditolak`;
        });
    } else {
        document.getElementById('realtime-location').innerHTML = `<i class="fas fa-map-marker-alt text-warning mr-1"></i> GPS tidak didukung`;
    }
</script>
@endpush
