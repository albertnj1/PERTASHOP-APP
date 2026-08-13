@extends('layouts._new_admin')
@section('title', 'UPLOAD FILE BACKDATE — Arsip File Excel per Pertashop')

@section('content')
<div class="container-fluid py-3">
  
  {{-- Header Halaman --}}
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="page-title mb-1" style="font-size: 24px; font-weight: 800; color: #1e293b;">UPLOAD FILE BACKDATE</h1>
      <p class="text-muted mb-0" style="font-size: 14px;">Arsip &amp; Penyimpanan Berkas Excel Backdate Laporan Pertashop</p>
    </div>
  </div>

  {{-- Alert Notifikasi --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 8px;">
      {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius: 8px;">
      {{ session('error') }}
      <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius: 8px;">
      <ul class="mb-0 pl-3">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
  @endif

  {{-- PANEL UPLOAD FILE BACKDATE BARU --}}
  @if(Auth::user()->role !== 'investor')
  <div class="panel mb-4" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <h5 class="font-weight-bold mb-3" style="font-size: 16px; color: #0f172a;">Upload File Excel Backdate Baru</h5>
    
    <form action="{{ route('backdate-excel-files.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="row align-items-end">
        
        {{-- Pilih Pertashop --}}
        <div class="col-md-3 mb-3">
          <label for="shop_id" class="form-label" style="font-weight: 600; font-size: 13px; color: #334155;">Target Pertashop</label>
          <select name="shop_id" id="shop_id" class="form-control form-control-sm" required style="border-radius: 6px;">
            <option value="">-- Pilih Target Pertashop --</option>
            <option value="auto_multi" style="font-weight: 700; color: #0284c7;" {{ old('shop_id') == 'auto_multi' ? 'selected' : '' }}>
              🌐 Otomatis Semua Pertashop (Master File Multi-Sheet)
            </option>
            <optgroup label="Toko Spesifik">
              @foreach($shops as $shop)
                <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                  {{ $shop->nama }} ({{ $shop->kode }})
                </option>
              @endforeach
            </optgroup>
          </select>
          <small class="text-muted d-block mt-1" style="font-size: 11px;">
            💡 Opsi <strong>Otomatis Semua Pertashop</strong> akan menguraikan sheet &amp; mendistribusikan ke kontainer toko terkait.
          </small>
        </div>

        {{-- Periode Bulan & Tahun --}}
        <div class="col-md-3 mb-3">
          <label for="bulan_tahun" class="form-label" style="font-weight: 600; font-size: 13px; color: #334155;">
            Periode Bulan &amp; Tahun <span class="text-muted font-weight-normal" style="font-size: 11px;">(Opsional)</span>
          </label>
          <input type="month" name="bulan_tahun" id="bulan_tahun" class="form-control form-control-sm" value="{{ old('bulan_tahun') }}" style="border-radius: 6px;">
          <small class="text-muted d-block mt-1" style="font-size: 11px;">
            💡 Kosongkan jika file berisi multi-bulan / multi-tahun (misal: 2025-2026).
          </small>
        </div>

        {{-- File Excel --}}
        <div class="col-md-4 mb-3">
          <label for="file_excel" class="form-label" style="font-weight: 600; font-size: 13px; color: #334155;">File Excel Backdate (.xlsx / .xls)</label>
          <input type="file" name="file_excel" id="file_excel" class="form-control-file form-control-sm" accept=".xlsx,.xls" required style="border-radius: 6px; padding: 3px 0;">
        </div>

        {{-- Submit Button --}}
        <div class="col-md-2 mb-3">
          <button type="submit" class="btn btn-success btn-sm w-100" style="font-weight: 600; border-radius: 6px; padding: 7px 0;">
            Upload File
          </button>
        </div>

        {{-- Keterangan / Catatan Opsional --}}
        <div class="col-md-12">
          <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Catatan / Keterangan tambahan (Opsional)" value="{{ old('keterangan') }}" style="border-radius: 6px;">
        </div>

      </div>
    </form>
  </div>
  @endif

  {{-- KONTAINER UNTUK MASING-MASING PERTASHOP --}}
  <h5 class="font-weight-bold mb-3" style="font-size: 17px; color: #0f172a;">Arsip Berkas Per Pertashop</h5>

  @forelse($shops as $shop)
    <div class="panel mb-4" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
      
      {{-- Header Kontainer Pertashop --}}
      <div class="d-flex align-items-center justify-content-between p-3" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
        <div>
          <h6 class="mb-0 font-weight-bold" style="font-size: 16px; color: #0f172a;">{{ $shop->nama }}</h6>
          <small class="text-muted">Kode Outlet: {{ $shop->kode }} | Lokasi: {{ $shop->lokasi ?? '-' }}</small>
        </div>
        <div>
          <span class="badge badge-info" style="font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 20px;">
            {{ $shop->backdateExcelFiles->count() }} File Tersimpan
          </span>
        </div>
      </div>

      {{-- Isi Tabel Berkas Excel di Dalam Kontainer --}}
      <div class="p-3">
        @if($shop->backdateExcelFiles->count() > 0)
          <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" style="font-size: 13px;">
              <thead style="background: #f1f5f9; color: #475569;">
                <tr>
                  <th style="width: 50px;" class="text-center">No</th>
                  <th style="width: 140px;">Periode</th>
                  <th>Nama File Excel</th>
                  <th style="width: 110px;">Ukuran</th>
                  <th style="width: 160px;">Tanggal Upload</th>
                  <th>Keterangan</th>
                  <th style="width: 220px;" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($shop->backdateExcelFiles as $idx => $file)
                  <tr>
                    <td class="text-center align-middle">{{ $idx + 1 }}</td>
                    <td class="align-middle font-weight-bold" style="color: #1e293b;">
                      {{ $file->formatted_period }}
                    </td>
                    <td class="align-middle">
                      <span style="font-weight: 600; color: #0f172a;">{{ $file->original_filename }}</span>
                    </td>
                    <td class="align-middle text-muted">{{ $file->formatted_file_size }}</td>
                    <td class="align-middle text-muted">{{ $file->created_at->format('d M Y H:i') }}</td>
                    <td class="align-middle text-muted">{{ $file->keterangan ?? '-' }}</td>
                    <td class="text-center align-middle">
                      <div class="btn-group" role="group">
                        
                        {{-- Tombol Lihat Isi File --}}
                        <a href="{{ route('backdate-excel-files.show', $file->id) }}" class="btn btn-sm btn-info" style="font-size: 12px; font-weight: 600; border-radius: 4px; margin-right: 4px;" title="Lihat Isi Tabel Excel">
                          Lihat File
                        </a>

                        {{-- Tombol Unduh File --}}
                        <a href="{{ route('backdate-excel-files.download', $file->id) }}" class="btn btn-sm btn-outline-primary" style="font-size: 12px; font-weight: 600; border-radius: 4px; margin-right: 4px;" title="Unduh Berkas Asli">
                          Unduh
                        </a>

                        {{-- Tombol Hapus File --}}
                        @if(Auth::user()->role !== 'investor')
                        <form id="delete-form-{{ $file->id }}" action="{{ route('backdate-excel-files.destroy', $file->id) }}" method="POST" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="button" onclick="confirmDeleteBackdateFile(event, '{{ $file->id }}', '{{ addslashes($file->original_filename) }}')" class="btn btn-sm btn-outline-danger" style="font-size: 12px; font-weight: 600; border-radius: 4px;" title="Hapus Berkas">
                            Hapus
                          </button>
                        </form>
                        @endif

                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="text-center py-4" style="background: #fafafa; border: 1px dashed #e2e8f0; border-radius: 6px; color: #64748b;">
            Belum ada file Excel backdate yang di-upload untuk Pertashop ini.
          </div>
        @endif
      </div>

    </div>
  @empty
    <div class="alert alert-secondary text-center py-4">
      Belum ada data Pertashop terdaftar.
    </div>
  @endforelse

</div>

<script>
function confirmDeleteBackdateFile(e, fileId, filename) {
  e.preventDefault();
  const form = document.getElementById('delete-form-' + fileId);
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Hapus Berkas Arsip',
      text: `Apakah Anda yakin ingin menghapus file "${filename}" dari arsip?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Hapus',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm(`Apakah Anda yakin ingin menghapus file ${filename} dari arsip?`)) {
      form.submit();
    }
  }
}
</script>
@endsection
