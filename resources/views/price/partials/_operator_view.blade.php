{{-- Tampilan Khusus Operator (/prices) --}}
{{-- Sembunyikan HPP, Margin, Tombol Tambah Harga, dan Log Perubahan --}}

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-3 align-items-center">
            <div class="col-12 col-md-8">
                <h1 style="font-weight:700; color:#0f172a; font-size: 24px;">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary mr-2" style="border-radius: 50%; width: 40px; height: 40px; padding: 0; line-height: 38px; text-align: center;">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    Informasi Harga BBM
                    <span class="badge badge-pill badge-light ml-2" style="font-size: 13px; font-weight: 600; color: #0284c7; background: #e0f2fe; border: 1px solid #bae6fd;">
                        <i class="fas fa-gas-pump mr-1"></i> {{ $shop->nama ?? 'Outlet' }}
                    </span>
                </h1>
                <p class="text-muted mb-0 ml-5 pl-1" style="font-size: 13px;">
                    Pantau harga jual aktif ke konsumen dan konfirmasi penyesuaian harga fisik pada mesin dispenser & totem.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px; border-left: 5px solid #16a34a;">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px; border-left: 5px solid #dc2626;">
                <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">
                <strong><i class="fas fa-exclamation-circle mr-1"></i> Mohon lengkapi formulir:</strong>
                <ul class="mb-0 mt-1 pl-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        {{-- ── 1. Banner Notifikasi Penyesuaian Harga Baru (Hanya Muncul Jika Ada Update dari Admin) ── --}}
        @if($hasNewPriceUpdate && $latestPrice)
            <div class="alert border-0 shadow-sm p-4 mb-4" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 6px solid #f59e0b !important; border-radius: 14px;">
                <div class="d-flex align-items-start">
                    <div class="mr-3 text-warning" style="font-size: 32px; line-height: 1;">
                        <i class="fas fa-exclamation-circle text-warning"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="font-weight-bold mb-1" style="color: #92400e; font-size: 19px;">
                            Perhatian: Terdapat Penyesuaian Harga BBM Baru!
                        </h4>
                        <div class="d-flex flex-wrap align-items-center mb-2" style="gap: 10px;">
                            <span style="color: #78350f; font-size: 15px;">Harga baru:</span>
                            <span class="badge font-weight-bold px-3 py-1 shadow-sm" style="font-size: 17px; background: #f59e0b; color: #ffffff; border-radius: 8px;">
                                Rp {{ number_format($latestPrice->harga_jual, 0, ',', '.') }} / Liter
                            </span>
                            @if($latestPrice->effective_at)
                                <span class="badge badge-light px-2.5 py-1" style="font-size: 13px; color: #92400e; background: rgba(254, 243, 199, 0.9); border: 1px solid #fde68a;">
                                    <i class="far fa-clock mr-1"></i> Berlaku mulai: {{ \Carbon\Carbon::parse($latestPrice->effective_at)->translatedFormat('d F Y, H:i') }} WIB
                                </span>
                            @endif
                        </div>
                        <div class="p-3 mt-2" style="background: rgba(255, 255, 255, 0.7); border-radius: 10px; border: 1px dashed #f59e0b; color: #b45309; font-size: 13.5px;">
                            <strong><i class="fas fa-bullhorn mr-1"></i> Instruksi SOP Operator:</strong>
                            Harap segera sesuaikan harga pada <strong>mesin dispenser</strong> dan <strong>papan display harga (totem)</strong> di outlet Anda, kemudian lakukan konfirmasi dan upload foto bukti fisik melalui form di bawah.
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── 2. Kontainer Konfirmasi / Approval Operator ── --}}
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px; overflow: hidden; border: 1px solid #fed7aa !important;">
                <div class="card-header py-3" style="background: #fff7ed; border-bottom: 1px solid #ffedd5;">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title font-weight-bold mb-0" style="color: #c2410c; font-size: 16px;">
                            <i class="fas fa-clipboard-check mr-2"></i> Formulir Konfirmasi Penyesuaian Harga Lapangan
                        </h5>
                        <span class="badge badge-warning font-weight-bold px-2 py-1" style="background:#ffedd5; color:#c2410c; border: 1px solid #fed7aa;">
                            Menunggu Konfirmasi
                        </span>
                    </div>
                </div>
                <div class="card-body p-4" style="background: #ffffff;">
                    <form action="{{ route('prices.confirm') }}" method="POST" enctype="multipart/form-data" id="formKonfirmasiHarga">
                        @csrf
                        <input type="hidden" name="price_id" value="{{ $latestPrice->id }}">

                        <div class="row">
                            <div class="col-12 col-md-7">
                                {{-- Input Ambil / Upload Foto Bukti --}}
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold text-dark mb-1" style="font-size: 14px;">
                                        Foto Bukti Display Totem / Mesin Dispenser <span class="text-danger">*</span>
                                    </label>
                                    <div class="custom-file mb-2">
                                        <input type="file" name="bukti_perubahan_harga" id="bukti_perubahan_harga" 
                                               class="custom-file-input" accept="image/*" capture="environment" required>
                                        <label class="custom-file-label text-truncate" id="label_foto_bukti" for="bukti_perubahan_harga">
                                            Ambil Foto / Pilih Berkas Foto...
                                        </label>
                                    </div>
                                    <div class="alert alert-light border p-2 mb-0" style="background: #f8fafc; font-size: 12px; color: #475569; border-radius: 8px;">
                                        <i class="fas fa-camera text-primary mr-1"></i>
                                        <strong>Tips:</strong> Pada smartphone/tablet operator, tombol di atas akan otomatis membuka kamera belakang. Pastikan nominal harga baru pada totem atau layar dispenser terbaca dengan jelas.
                                    </div>
                                </div>

                                {{-- Catatan Opsional --}}
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold text-dark mb-1" style="font-size: 13.5px;">
                                        Catatan / Angka Totalisator Saat Ubah Harga <span class="text-muted font-weight-normal">(Opsional)</span>
                                    </label>
                                    <input type="text" name="catatan" class="form-control" 
                                           placeholder="Contoh: Display totem & dispenser diubah pukul 00:05 WIB. Totalisator: 125430.5 L"
                                           style="border-radius: 8px;">
                                </div>

                                {{-- Checkbox Persetujuan --}}
                                <div class="form-group mb-3">
                                    <div class="custom-control custom-checkbox p-3" style="background: #f8fafc; border-radius: 10px; border: 1.5px solid #cbd5e1;">
                                        <input type="checkbox" class="custom-control-input" id="pernyataan_konfirmasi" name="pernyataan_konfirmasi" value="1" required>
                                        <label class="custom-control-label font-weight-bold" for="pernyataan_konfirmasi" style="color: #0f172a; cursor: pointer; font-size: 13.5px; line-height: 1.4;">
                                            Saya telah menyesuaikan harga pada mesin dispenser dan display harga (totem) di outlet secara benar.
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-warning btn-block btn-lg font-weight-bold py-2.5 shadow-sm" style="background: #ea580c; border: none; color: #ffffff; border-radius: 10px;">
                                    <i class="fas fa-paper-plane mr-2"></i> Konfirmasi & Kirim Bukti Penyesuaian Harga
                                </button>
                            </div>

                            {{-- Area Pratinjau Foto --}}
                            <div class="col-12 col-md-5 mt-3 mt-md-0">
                                <label class="font-weight-bold text-dark mb-1" style="font-size: 14px;">Pratinjau Foto Bukti:</label>
                                <div id="preview-container" class="border rounded d-flex flex-column align-items-center justify-content-center p-3 text-center" 
                                     style="min-height: 220px; background: #f8fafc; border-style: dashed !important; border-color: #cbd5e1 !important; border-radius: 10px;">
                                    <div id="preview-placeholder">
                                        <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                        <p class="text-muted mb-0" style="font-size: 12.5px;">Foto bukti yang Anda ambil akan muncul di sini</p>
                                    </div>
                                    <img id="preview-image" src="#" alt="Pratinjau Foto Bukti" class="img-fluid rounded shadow-sm" style="display: none; max-height: 240px; object-fit: contain;">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ── 3. Status Terkonfirmasi (Jika Sudah Disesuaikan) ── --}}
        @if($confirmation && $confirmation->status_operator === 'dikonfirmasi')
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px; overflow: hidden; border-left: 6px solid #16a34a !important; background: #ffffff;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-8">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge badge-success px-3 py-1.5 mr-2" style="font-size: 13px; font-weight: 700; background: #16a34a;">
                                    <i class="fas fa-check-circle mr-1"></i> Telah Disesuaikan oleh Operator
                                </span>
                                <span class="text-muted" style="font-size: 12.5px;">
                                    <i class="far fa-calendar-check mr-1"></i>
                                    {{ $confirmation->dikonfirmasi_pada ? $confirmation->dikonfirmasi_pada->translatedFormat('d F Y, H:i') : '-' }} WIB
                                </span>
                            </div>
                            <h5 class="font-weight-bold text-dark mb-1">
                                Penyesuaian Harga Rp {{ number_format($latestPrice->harga_jual ?? 0, 0, ',', '.') }} / Liter Telah Dikonfirmasi
                            </h5>
                            <p class="text-muted mb-2" style="font-size: 13.5px;">
                                Dikonfirmasi oleh: <strong>{{ $confirmation->operator?->user?->name ?? (Auth::user()->name ?? 'Operator') }}</strong>
                                @if($confirmation->catatan)
                                    <br><span class="text-dark"><i class="fas fa-comment-dots text-info mr-1"></i> Catatan: <em>"{{ $confirmation->catatan }}"</em></span>
                                @endif
                            </p>
                            <span class="badge badge-light border text-muted" style="font-size: 11.5px;">
                                <i class="fas fa-shield-alt mr-1 text-success"></i> Menunggu reviu verifikasi dari Super Admin
                            </span>
                        </div>
                        <div class="col-12 col-md-4 mt-3 mt-md-0 text-md-right">
                            @if($confirmation->foto_bukti)
                                <a href="{{ asset('storage/' . $confirmation->foto_bukti) }}" target="_blank" class="d-inline-block text-center border p-1 rounded bg-light shadow-sm" style="text-decoration: none;">
                                    <img src="{{ asset('storage/' . $confirmation->foto_bukti) }}" alt="Foto Bukti" class="rounded" style="height: 90px; width: 120px; object-fit: cover;">
                                    <div class="mt-1 text-primary font-weight-bold" style="font-size: 11px;">
                                        <i class="fas fa-search-plus mr-1"></i> Lihat Foto Bukti
                                    </div>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── 4. Card Harga Aktif Saat Ini (Hero Information Section) ── --}}
        <div class="row">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 16px; overflow: hidden; background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #e2e8f0;">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="mr-2" style="width: 42px; height: 42px; border-radius: 10px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669; font-size: 20px;">
                                        <i class="fas fa-gas-pump"></i>
                                    </div>
                                    <div>
                                        <span class="text-muted d-block text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; font-weight: 700;">Produk BBM</span>
                                        <strong style="font-size: 17px; color: #0f172a;">Pertamax</strong>
                                    </div>
                                </div>
                                <span class="badge badge-success px-3 py-1.5 shadow-sm" style="font-size: 12px; font-weight: 700; background: #10b981; border-radius: 20px;">
                                    <i class="fas fa-circle mr-1" style="font-size: 8px;"></i> Aktif
                                </span>
                            </div>

                            <hr style="border-top: 1px dashed #e2e8f0;" class="my-3">

                            <div class="mb-3">
                                <span class="text-muted d-block" style="font-size: 13px; font-weight: 600;">Harga Jual Eceran Saat Ini:</span>
                                <div class="d-flex align-items-baseline mt-1">
                                    <h2 class="font-weight-bold mb-0" style="color: #0f172a; font-size: 34px; letter-spacing: -0.5px;">
                                        Rp {{ number_format($activePrice->harga_jual ?? 0, 0, ',', '.') }}
                                    </h2>
                                    <span class="text-muted ml-2 font-weight-bold" style="font-size: 15px;">/ Liter</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3" style="background: #f1f5f9; border-radius: 12px; font-size: 12.5px; color: #475569;">
                            <div class="d-flex justify-content-between mb-1.5">
                                <span><i class="far fa-calendar-alt text-primary mr-1.5"></i> Mulai Berlaku:</span>
                                <strong class="text-dark">
                                    {{ $activePrice && $activePrice->effective_at ? \Carbon\Carbon::parse($activePrice->effective_at)->translatedFormat('d M Y, H:i') . ' WIB' : 'Telah Berlaku' }}
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-map-marker-alt text-danger mr-1.5"></i> Outlet:</span>
                                <strong class="text-dark">{{ $shop->nama ?? 'Semua Outlet' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Edukasi SOP --}}
            <div class="col-12 col-md-6 col-lg-7 mt-3 mt-md-0">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0;">
                    <div class="card-header py-3 bg-white" style="border-bottom: 1px solid #f1f5f9;">
                        <h6 class="font-weight-bold mb-0 text-dark" style="font-size: 14.5px;">
                            <i class="fas fa-book-reader text-info mr-2"></i> Petunjuk Operasional Penyesuaian Harga BBM
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start mb-3">
                            <div class="badge badge-primary px-2.5 py-1.5 mr-3" style="font-size: 13px; border-radius: 8px;">1</div>
                            <div>
                                <strong class="text-dark" style="font-size: 13.5px;">Pantau Notifikasi Perubahan</strong>
                                <p class="text-muted mb-0" style="font-size: 12.5px;">Setiap kali ada instruksi penyesuaian harga dari Super Admin, halaman ini otomatis memunculkan banner peringatan dan nominal harga baru.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3">
                            <div class="badge badge-primary px-2.5 py-1.5 mr-3" style="font-size: 13px; border-radius: 8px;">2</div>
                            <div>
                                <strong class="text-dark" style="font-size: 13.5px;">Ubah Mesin Dispenser & Display Totem Fisik</strong>
                                <p class="text-muted mb-0" style="font-size: 12.5px;">Segera masukkan parameter harga baru pada tombol keypad dispenser Pertashop dan ganti angka pada papan display totem sebelum jam berlaku dimulai.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start">
                            <div class="badge badge-primary px-2.5 py-1.5 mr-3" style="font-size: 13px; border-radius: 8px;">3</div>
                            <div>
                                <strong class="text-dark" style="font-size: 13.5px;">Ambil Foto Bukti & Kirim Konfirmasi</strong>
                                <p class="text-muted mb-0" style="font-size: 12.5px;">Gunakan kamera ponsel untuk mengambil foto bukti fisik display/totem, centang pernyataan kepatuhan, lalu tekan tombol konfirmasi.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

@push('script')
<script>
$(document).ready(function() {
    // Pratinjau Foto Bukti Instan
    $('#bukti_perubahan_harga').on('change', function(e) {
        const file = this.files[0];
        if (file) {
            // Update nama file pada label
            $('#label_foto_bukti').text(file.name);

            // Tampilkan image preview
            const reader = new FileReader();
            reader.onload = function(event) {
                $('#preview-placeholder').hide();
                $('#preview-image').attr('src', event.target.result).show();
            };
            reader.readAsDataURL(file);
        } else {
            $('#label_foto_bukti').text('Ambil Foto / Pilih Berkas Foto...');
            $('#preview-image').hide();
            $('#preview-placeholder').show();
        }
    });

    // Validasi submit form konfirmasi
    $('#formKonfirmasiHarga').on('submit', function(e) {
        if (!$('#pernyataan_konfirmasi').is(':checked')) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Konfirmasi Wajib',
                text: 'Harap centang kotak pernyataan bahwa harga telah disesuaikan pada mesin dispenser & totem.',
            });
            return false;
        }

        const fileInput = document.getElementById('bukti_perubahan_harga');
        if (!fileInput.files || fileInput.files.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Foto Bukti Wajib',
                text: 'Harap ambil atau lampirkan foto bukti fisik display totem / mesin dispenser.',
            });
            return false;
        }
    });
});
</script>
@endpush
