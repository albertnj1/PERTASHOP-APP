@extends('layouts.app')

@section('content')
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
                        <i class="fas fa-gas-pump mr-1"></i> {{ $shop->nama ?? ($currentPrice->shop->nama ?? 'Outlet') }}
                    </span>
                </h1>
                <p class="text-muted mb-0 ml-5 pl-1" style="font-size: 13px;">
                    Pantau harga jual aktif ke konsumen dan konfirmasi penyesuaian harga di totem/dispenser.
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
                <strong><i class="fas fa-exclamation-circle mr-1"></i> Mohon lengkapi formulir konfirmasi:</strong>
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

        {{-- ── 1. ALERT / NOTIFIKASI JIKA ADA PERUBAHAN HARGA BARU ── --}}
        @php
            $targetPending = $pendingPrice ?? ($hasNewPriceUpdate ? $latestPrice : null);
            $isWaitingConfirm = $targetPending && (!$confirmation || $confirmation->status_operator === 'menunggu_konfirmasi');
        @endphp

        @if($isWaitingConfirm && $targetPending)
        <div class="alert border-0 shadow-sm p-4 mb-4" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 6px solid #f59e0b !important; border-radius: 14px;">
            <div class="d-flex align-items-start mb-3">
                <div class="mr-3 text-warning" style="font-size: 32px; line-height: 1;">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading font-weight-bold mb-1" style="color: #92400e; font-size: 19px;">
                        Perhatian: Ada Penyesuaian Harga Baru!
                    </h5>
                    <p class="mb-2" style="color: #78350f; font-size: 15px;">
                        Super Admin telah memperbarui harga BBM eceran menjadi:
                        <strong class="badge font-weight-bold px-3 py-1 ml-1 shadow-sm" style="font-size: 17px; background: #ea580c; color: #ffffff; border-radius: 8px;">
                            Rp {{ number_format($targetPending->harga_jual, 0, ',', '.') }} / Liter
                        </strong>
                        @if($targetPending->effective_at)
                            <span class="badge badge-light px-2.5 py-1 ml-2" style="font-size: 12.5px; color: #92400e; background: rgba(254, 243, 199, 0.9); border: 1px solid #fde68a;">
                                <i class="far fa-clock mr-1"></i> Berlaku mulai: {{ \Carbon\Carbon::parse($targetPending->effective_at)->translatedFormat('d F Y, H:i') }} WIB
                            </span>
                        @endif
                    </p>
                    <div class="p-2.5" style="background: rgba(255, 255, 255, 0.7); border-radius: 8px; border: 1px dashed #f59e0b; color: #b45309; font-size: 13px;">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Instruksi Lapangan:</strong> Harap segera ubah angka harga pada <u>mesin dispenser</u> dan <u>papan nama/totem outlet</u>, lalu kirim foto bukti melalui form di bawah ini.
                    </div>
                </div>
            </div>

            <!-- Form Konfirmasi & Bukti Foto -->
            <div class="card bg-white p-4 border-0 rounded-3 shadow-sm" style="border-radius: 12px !important; border: 1px solid #fed7aa !important;">
                <h6 class="font-weight-bold text-dark mb-3" style="font-size: 15px;">
                    <i class="fas fa-camera text-primary mr-1.5"></i> Form Konfirmasi Perubahan Fisik di Outlet
                </h6>
                <form action="{{ route('prices.confirm', $targetPending->id) }}" method="POST" enctype="multipart/form-data" id="formConfirmOperator">
                    @csrf
                    <input type="hidden" name="price_id" value="{{ $targetPending->id }}">

                    <div class="row">
                        <div class="col-12 col-md-7">
                            <div class="form-group mb-3">
                                <label class="form-label small font-weight-bold text-dark mb-1">
                                    Upload Foto Bukti Display / Totem <span class="text-danger">*</span>
                                </label>
                                <div class="custom-file mb-1">
                                    <input type="file" name="foto_bukti" id="foto_bukti" class="custom-file-input" accept="image/*" capture="environment" required>
                                    <label class="custom-file-label text-truncate" id="label_foto_bukti" for="foto_bukti">
                                        Ambil Foto Kamera / Pilih Berkas...
                                    </label>
                                </div>
                                <div class="form-text text-muted" style="font-size: 11.5px;">
                                    <i class="fas fa-camera text-muted mr-1"></i> Pada smartphone/tablet, tombol otomatis membuka kamera belakang. Ambil foto jelas display dispenser atau papan totem yang sudah menampilkan harga baru.
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label small font-weight-bold text-dark mb-1">
                                    Catatan / Angka Totalisator <span class="text-muted font-weight-normal">(Opsional)</span>
                                </label>
                                <input type="text" name="catatan" class="form-control" placeholder="Contoh: Display totem & dispenser nomor 1 telah diubah">
                            </div>

                            <div class="form-group mb-3">
                                <div class="custom-control custom-checkbox p-2.5" style="background: #f8fafc; border-radius: 8px; border: 1.5px solid #cbd5e1;">
                                    <input type="checkbox" class="custom-control-input" id="checkConfirm" name="checkConfirm" value="1" required>
                                    <label class="custom-control-label small font-weight-bold text-dark" for="checkConfirm" style="cursor: pointer; line-height: 1.4;">
                                        Saya menyatakan telah mengubah angka harga pada mesin dispenser dan papan nama/totem outlet dengan benar.
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success font-weight-bold px-4 py-2 shadow-sm" style="background: #16a34a; border: none; border-radius: 8px;">
                                <i class="fas fa-check-circle mr-1.5"></i> Kirim Konfirmasi & Foto Bukti
                            </button>
                        </div>

                        <div class="col-12 col-md-5 mt-3 mt-md-0">
                            <label class="form-label small font-weight-bold text-dark mb-1">Pratinjau Foto:</label>
                            <div id="preview-box" class="border rounded d-flex flex-column align-items-center justify-content-center p-3 text-center" 
                                 style="min-height: 200px; background: #f8fafc; border-style: dashed !important; border-color: #cbd5e1 !important; border-radius: 10px;">
                                <div id="preview-placeholder">
                                    <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                    <p class="text-muted mb-0 small">Foto bukti yang diambil akan muncul di sini</p>
                                </div>
                                <img id="preview-img" src="#" alt="Pratinjau Foto Bukti" class="img-fluid rounded shadow-sm" style="display: none; max-height: 220px; object-fit: contain;">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- ── Status Konfirmasi Telah Dikirim ── --}}
        @if($confirmation && $confirmation->status_operator === 'dikonfirmasi')
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px; overflow: hidden; border-left: 6px solid #16a34a !important; background: #ffffff;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-8">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge badge-success px-3 py-1.5 mr-2" style="font-size: 13px; font-weight: 700; background: #16a34a;">
                                    <i class="fas fa-check-circle mr-1"></i> Telah Disesuaikan oleh Operator
                                </span>
                                <span class="text-muted small">
                                    <i class="far fa-calendar-check mr-1"></i>
                                    {{ $confirmation->dikonfirmasi_pada ? $confirmation->dikonfirmasi_pada->translatedFormat('d F Y, H:i') : '-' }} WIB
                                </span>
                            </div>
                            <h5 class="font-weight-bold text-dark mb-1">
                                Penyesuaian Harga Telah Dikonfirmasi
                            </h5>
                            <p class="text-muted mb-2 small">
                                Dikonfirmasi oleh: <strong>{{ $confirmation->operator?->user?->name ?? (Auth::user()->name ?? 'Operator') }}</strong>
                                @if($confirmation->catatan)
                                    <br><span class="text-dark"><i class="fas fa-comment-dots text-info mr-1"></i> Catatan: <em>"{{ $confirmation->catatan }}"</em></span>
                                @endif
                            </p>
                            <span class="badge badge-light border text-muted small">
                                <i class="fas fa-shield-alt mr-1 text-success"></i> Menunggu verifikasi dari Super Admin
                            </span>
                        </div>
                        <div class="col-12 col-md-4 mt-3 mt-md-0 text-md-right">
                            @if($confirmation->foto_bukti)
                                <a href="{{ asset('storage/' . $confirmation->foto_bukti) }}" target="_blank" class="d-inline-block text-center border p-1 rounded bg-light shadow-sm" style="text-decoration: none;">
                                    <img src="{{ asset('storage/' . $confirmation->foto_bukti) }}" alt="Foto Bukti" class="rounded" style="height: 85px; width: 115px; object-fit: cover;">
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

        {{-- ── 2. KARTU HARGA JUAL AKTIF SAAT INI (HERO CARD) ── --}}
        @php
            $activePriceItem = $currentPrice ?? $activePrice;
        @endphp
        <div class="card border-0 shadow-sm rounded-3 mb-4" style="border-radius: 16px !important; background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #e2e8f0 !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge px-3 py-2 rounded-pill font-weight-bold shadow-sm" style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; font-size: 12.5px;">
                        <i class="fas fa-check-circle mr-1.5"></i> Harga Aktif Saat Ini
                    </span>
                    <span class="text-muted small">
                        <i class="far fa-calendar-alt text-primary mr-1"></i>
                        Berlaku Sejak: <strong>{{ $activePriceItem?->effective_at ? date('d M Y, H:i', strtotime($activePriceItem->effective_at)) . ' WIB' : 'Telah Berlaku' }}</strong>
                    </span>
                </div>

                <div class="my-3">
                    <span class="text-muted d-block small font-weight-bold text-uppercase" style="letter-spacing: 0.5px;">PRODUK BBM</span>
                    <h3 class="font-weight-bold text-dark mb-0" style="font-size: 26px;">
                        {{ $activePriceItem->nama_produk ?? 'PERTAMAX' }}
                    </h3>
                </div>

                <hr class="text-muted opacity-25 my-3">

                <div class="row align-items-center">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <span class="text-muted small d-block font-weight-bold">HARGA JUAL KONSUMEN</span>
                        <h2 class="font-weight-bold mb-0" style="color: #0284c7; font-size: 36px; letter-spacing: -0.5px;">
                            Rp {{ number_format($activePriceItem->harga_jual ?? 0, 0, ',', '.') }}
                            <span class="font-weight-normal text-muted" style="font-size: 16px;">/ liter</span>
                        </h2>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded" style="background: #f1f5f9; border-radius: 12px;">
                            <span class="text-muted small d-block font-weight-bold">LOKASI OUTLET PERTASHOP</span>
                            <h6 class="font-weight-bold text-dark mt-1 mb-0" style="font-size: 15px;">
                                <i class="fas fa-map-marker-alt text-danger mr-1.5"></i>
                                {{ $shop->nama ?? ($activePriceItem->shop->nama ?? 'Outlet Pertashop') }}
                            </h6>
                            <small class="text-muted">Kode Outlet: {{ $shop->kode ?? ($activePriceItem->shop->kode ?? '4P.XXXXX') }}</small>
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
    // Instant image preview for camera upload
    $('#foto_bukti').on('change', function(e) {
        const file = this.files[0];
        if (file) {
            $('#label_foto_bukti').text(file.name);
            const reader = new FileReader();
            reader.onload = function(event) {
                $('#preview-placeholder').hide();
                $('#preview-img').attr('src', event.target.result).show();
            };
            reader.readAsDataURL(file);
        } else {
            $('#label_foto_bukti').text('Ambil Foto Kamera / Pilih Berkas...');
            $('#preview-img').hide();
            $('#preview-placeholder').show();
        }
    });

    $('#formConfirmOperator').on('submit', function(e) {
        if (!$('#checkConfirm').is(':checked')) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Persetujuan Wajib',
                text: 'Harap centang kotak pernyataan bahwa harga fisik pada mesin dispenser dan totem telah disesuaikan.',
            });
            return false;
        }

        const fileInput = document.getElementById('foto_bukti');
        if (!fileInput.files || fileInput.files.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Foto Bukti Wajib',
                text: 'Harap ambil atau unggah foto bukti fisik display totem / dispenser.',
            });
            return false;
        }
    });
});
</script>
@endpush
@endsection
