@extends('layouts._new_admin')

@section('title', 'Import Rekap Modal dari Excel')

@section('content')
<div class="metrics-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h4 style="margin: 0; font-weight: 700; color: var(--text-primary);">
                <i class="fas fa-file-excel mr-2" style="color: #1D6F42;"></i> Import Rekap Modal &amp; Laporan Bulanan
            </h4>
            <p class="text-muted small mb-0 mt-1">Upload satu file Excel berisi banyak sheet (satu sheet per bulan). Sistem otomatis membuat Rekap Modal &amp; Laporan Bulanan.</p>
        </div>
        <a href="{{ route('capital-recaps.index') }}" class="btn btn-secondary" style="border-radius: 20px; padding: 8px 20px;">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('capital-recaps.import.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf

                <div class="row">
                    {{-- Pertashop --}}
                    <div class="col-md-12 mb-3">
                        <label class="form-label font-weight-bold">Pertashop <span class="text-danger">*</span></label>
                        <select name="shop_id" id="shop_id" class="form-control" required>
                            <option value="">-- Pilih Pertashop --</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->kode }} — {{ $shop->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Rentang Periode --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold">Dari Bulan <span class="text-danger">*</span></label>
                        <input type="month" name="dari_bulan" id="dari_bulan" class="form-control"
                               value="{{ old('dari_bulan') }}" required>
                        <small class="text-muted">Bulan pertama yang akan diimport</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold">Sampai Bulan <span class="text-danger">*</span></label>
                        <input type="month" name="sampai_bulan" id="sampai_bulan" class="form-control"
                               value="{{ old('sampai_bulan') }}" required>
                        <small class="text-muted">Bulan terakhir yang akan diimport</small>
                    </div>

                    {{-- Modal Awal --}}
                    <div class="col-md-12 mb-3">
                        <label class="form-label font-weight-bold">Modal Awal (Posisi Modal Bulan Pertama) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="modal_awal" id="modal_awal" class="form-control"
                                   value="{{ old('modal_awal', '0') }}" required
                                   placeholder="Contoh: 500000000"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <small class="text-muted">Nilai modal awal bulan pertama. Bulan berikutnya dihitung berantai secara otomatis.</small>
                    </div>

                    {{-- File Upload — cara paling simpel & kompatibel --}}
                    <div class="col-md-12 mb-3">
                        <label class="form-label font-weight-bold">File Excel (.xlsx / .xls) <span class="text-danger">*</span></label>

                        {{-- Visible styled input --}}
                        <div id="drop-area" style="border: 2px dashed #adb5bd; border-radius: 8px; background: #f8f9fa; padding: 32px; text-align: center; cursor: pointer; transition: all 0.2s;"
                             onclick="document.getElementById('excel_file').click();"
                             ondragover="event.preventDefault(); this.style.borderColor='#28a745'; this.style.background='#e8f5e9';"
                             ondragleave="this.style.borderColor='#adb5bd'; this.style.background='#f8f9fa';"
                             ondrop="onFileDrop(event)">
                            <div id="upload-placeholder">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted" style="display:block; margin-bottom:12px;"></i>
                                <p style="margin:0; font-weight:600; color:#555;">Klik di sini atau drag &amp; drop file Excel</p>
                                <p style="margin:4px 0 0; font-size:13px; color:#888;">Format: .xlsx atau .xls &nbsp;|&nbsp; Nama sheet contoh: <strong>Juli 2025</strong>, <strong>Agustus 2025</strong></p>
                            </div>
                            <div id="file-selected" style="display:none;">
                                <i class="fas fa-file-excel fa-3x" style="color:#1D6F42; display:block; margin-bottom:10px;"></i>
                                <p style="margin:0; font-weight:700;" id="fname"></p>
                                <p style="margin:2px 0 0; font-size:13px; color:#888;" id="fsize"></p>
                                <span style="color:#28a745; font-size:13px;"><i class="fas fa-check-circle"></i> File siap diproses</span>
                            </div>
                        </div>

                        {{-- Input yang benar-benar visible tapi tersembunyi visual --}}
                        <input type="file"
                               name="excel_file"
                               id="excel_file"
                               accept=".xlsx,.xls"
                               required
                               style="opacity:0; width:0.1px; height:0.1px; position:absolute; z-index:-1;">
                    </div>

                    {{-- Sheet Preview --}}
                    <div class="col-md-12 mb-3" id="sheet-preview" style="display:none;">
                        <div class="alert alert-info mb-0">
                            <strong><i class="fas fa-table mr-1"></i> Sheet yang terdeteksi dalam file:</strong>
                            <div id="sheet-list" class="mt-2"></div>
                        </div>
                    </div>
                </div>

                {{-- Info Box --}}
                <div class="alert alert-warning mt-2">
                    <i class="fas fa-info-circle"></i>
                    <strong>Catatan penting:</strong>
                    <ul class="mb-0 mt-1">
                        <li>Setiap sheet harus berisi <strong>rekap laporan harian</strong> untuk satu bulan.</li>
                        <li>Nama sheet harus mengandung nama bulan dan tahun, contoh: <code>Juli 2025</code>, <code>Agustus-2025</code>.</li>
                        <li>Sistem akan <strong>menimpa data</strong> bulan yang sama jika sudah ada.</li>
                        <li>Laba Kotor = (Volume Penjualan × Harga Jual) − (Volume Penjualan × Harga Beli).</li>
                        <li>Rekap Modal dihitung berantai: Posisi akhir bulan N = Modal awal bulan N+1.</li>
                    </ul>
                </div>

                <div class="mt-3 text-right">
                    <button type="submit" class="btn btn-success btn-lg" id="btn-submit">
                        <i class="fas fa-cogs mr-2"></i> Proses Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Script langsung di bawah @endsection (bukan @push) karena layout pakai @stack('scripts') --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    var fileInputEl   = document.getElementById('excel_file');
    var dropArea      = document.getElementById('drop-area');
    var placeholder   = document.getElementById('upload-placeholder');
    var fileSelected  = document.getElementById('file-selected');
    var fnameEl       = document.getElementById('fname');
    var fsizeEl       = document.getElementById('fsize');
    var sheetPreview  = document.getElementById('sheet-preview');
    var sheetList     = document.getElementById('sheet-list');
    var btnSubmit     = document.getElementById('btn-submit');

    var bulanIndo = {
        'januari':1,'februari':2,'maret':3,'april':4,'mei':5,'juni':6,
        'juli':7,'agustus':8,'september':9,'oktober':10,'november':11,'desember':12
    };

    function showFile(file) {
        if (!file) return;
        placeholder.style.display  = 'none';
        fileSelected.style.display = 'block';
        fnameEl.textContent = file.name;
        fsizeEl.textContent = (file.size / 1024).toFixed(1) + ' KB';
        dropArea.style.borderColor = '#28a745';
        dropArea.style.background  = '#e8f5e9';

        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var data = new Uint8Array(e.target.result);
                var wb   = XLSX.read(data, { type: 'array' });
                var html = '<div style="display:flex; flex-wrap:wrap; gap:8px;">';
                wb.SheetNames.forEach(function(name) {
                    var lower = name.toLowerCase();
                    var ok    = false;
                    Object.keys(bulanIndo).forEach(function(m) {
                        if (!ok && lower.indexOf(m) >= 0 && /20\d{2}/.test(lower)) ok = true;
                    });
                    html += ok
                        ? '<span class="badge badge-success" style="font-size:12px;padding:5px 10px;"><i class="fas fa-check"></i> ' + name + '</span>'
                        : '<span class="badge badge-secondary" style="font-size:12px;padding:5px 10px;"><i class="fas fa-times"></i> ' + name + ' <small>(dilewati)</small></span>';
                });
                html += '</div><small class="text-muted d-block mt-2">✅ Hijau = dikenali &nbsp; ⬜ Abu = akan dilewati</small>';
                sheetList.innerHTML  = html;
                sheetPreview.style.display = 'block';
            } catch(err) {
                sheetList.innerHTML = '<span class="text-danger">Error baca file: ' + err.message + '</span>';
                sheetPreview.style.display = 'block';
            }
        };
        reader.readAsArrayBuffer(file);
    }

    fileInputEl.addEventListener('change', function() {
        if (this.files.length > 0) showFile(this.files[0]);
    });

    function onFileDrop(e) {
        e.preventDefault();
        dropArea.style.borderColor = '#adb5bd';
        dropArea.style.background  = '#f8f9fa';
        if (e.dataTransfer && e.dataTransfer.files.length > 0) {
            try {
                var dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                fileInputEl.files = dt.files;
            } catch(ex) {}
            showFile(e.dataTransfer.files[0]);
        }
    }

    document.getElementById('importForm').addEventListener('submit', function(e) {
        if (!fileInputEl.files || fileInputEl.files.length === 0) {
            e.preventDefault();
            alert('Pilih file Excel terlebih dahulu!');
            return;
        }
        btnSubmit.disabled    = true;
        btnSubmit.textContent = 'Memproses... Mohon tunggu.';
    });
</script>
@endsection
