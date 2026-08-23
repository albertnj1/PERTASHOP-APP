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
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap: 12px;">
    <div class="d-flex align-items-center">
      <h5 class="font-weight-bold mb-0" style="font-size: 17px; color: #0f172a;">Arsip Berkas Per Pertashop</h5>
      <span class="badge badge-secondary ml-2" style="font-size: 12px; border-radius: 12px; padding: 4px 10px; font-weight: 600;">
        {{ $totalActiveFilesCount }} File Active
      </span>
    </div>

    @if(Auth::user()->role !== 'investor')
    <div class="d-flex align-items-center" style="gap: 8px;">
      {{-- Tombol Tempat Sampah --}}
      <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#trashModal" style="border-radius: 6px; font-weight: 600; font-size: 13px; border: 1px solid #cbd5e1;">
        <i class="fas fa-trash-alt text-danger mr-1"></i> Tempat Sampah
        @if($trashedCount > 0)
          <span class="badge badge-danger ml-1" style="border-radius: 10px; font-size: 11px;">{{ $trashedCount }}</span>
        @endif
      </button>

      {{-- Tombol Hapus Semua File (Master Global) --}}
      @if($totalActiveFilesCount > 0)
        <button type="button" onclick="confirmDeleteAllMasterFiles()" class="btn btn-danger btn-sm" style="border-radius: 6px; font-weight: 600; font-size: 13px;">
          <i class="fas fa-trash mr-1"></i> Hapus Semua File
        </button>
        <form id="delete-all-master-form" action="{{ route('backdate-excel-files.delete-all') }}" method="POST" style="display: none;">
          @csrf
          @method('DELETE')
        </form>
      @endif
    </div>
    @endif
  </div>

  @forelse($shops as $shop)
    <div class="panel mb-4" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
      
      {{-- Header Kontainer Pertashop --}}
      <div class="d-flex align-items-center justify-content-between p-3" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
        <div>
          <h6 class="mb-0 font-weight-bold" style="font-size: 16px; color: #0f172a;">{{ $shop->nama }}</h6>
          <small class="text-muted">Kode Outlet: {{ $shop->kode }} | Lokasi: {{ $shop->lokasi ?? '-' }}</small>
        </div>
        <div class="d-flex align-items-center" style="gap: 8px;">
          <span class="badge badge-info" style="font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 20px;">
            {{ $shop->backdateExcelFiles->count() }} File Tersimpan
          </span>

          @if(Auth::user()->role !== 'investor' && $shop->backdateExcelFiles->count() > 0)
            <button type="button" onclick="confirmDeleteShopFiles('{{ $shop->id }}', '{{ addslashes($shop->nama) }}')" class="btn btn-outline-danger btn-sm" style="font-size: 12px; font-weight: 600; border-radius: 6px; padding: 4px 10px;" title="Hapus seluruh file di Pertashop ini">
              <i class="fas fa-trash-alt mr-1"></i> Hapus Semua
            </button>
            <form id="delete-shop-files-form-{{ $shop->id }}" action="{{ route('backdate-excel-files.delete-all') }}" method="POST" style="display: none;">
              @csrf
              @method('DELETE')
              <input type="hidden" name="shop_id" value="{{ $shop->id }}">
            </form>
          @endif
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

{{-- MODAL TEMPAT SAMPAH (TRASH BIN) --}}
<div class="modal fade" id="trashModal" tabindex="-1" role="dialog" aria-labelledby="trashModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
      
      {{-- Modal Header --}}
      <div class="modal-header bg-light py-3" style="border-bottom: 1px solid #e2e8f0; border-top-left-radius: 12px; border-top-right-radius: 12px;">
        <div class="d-flex align-items-center">
          <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mr-3" style="width: 38px; height: 38px; font-size: 16px;">
            <i class="fas fa-trash-alt"></i>
          </div>
          <div>
            <h5 class="modal-title font-weight-bold mb-0" id="trashModalLabel" style="font-size: 18px; color: #0f172a;">
              Tempat Sampah Berkas Excel
            </h5>
            <small class="text-muted">Berkas yang dihapus sementara tersimpan di sini dan dapat dipulihkan atau dihapus permanen.</small>
          </div>
        </div>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      {{-- Modal Body --}}
      <div class="modal-body p-3">
        @if($trashedFiles->count() > 0)
          
          {{-- Action Toolbar inside Trash Modal --}}
          <div class="d-flex align-items-center justify-content-between mb-3 p-3 bg-light rounded" style="border: 1px dashed #cbd5e1;">
            <span class="text-dark font-weight-bold" style="font-size: 13px;">
              Total Berkas Terhapus: <span class="badge badge-danger ml-1" style="font-size: 12px; padding: 4px 8px;">{{ $trashedFiles->count() }} Berkas</span>
            </span>
            <div class="d-flex" style="gap: 8px;">
              {{-- Restore All --}}
              <form id="restore-all-form" action="{{ route('backdate-excel-files.restore-all') }}" method="POST" class="d-inline">
                @csrf
                <button type="button" onclick="confirmRestoreAll()" class="btn btn-sm btn-success font-weight-bold" style="border-radius: 6px; padding: 6px 12px;">
                  <i class="fas fa-undo mr-1"></i> Pulihkan Semua
                </button>
              </form>

              {{-- Empty Trash (Hapus Semua Permanen) --}}
              <button type="button" onclick="confirmEmptyTrash()" class="btn btn-sm btn-danger font-weight-bold" style="border-radius: 6px; padding: 6px 12px;">
                <i class="fas fa-dumpster-fire mr-1"></i> Kosongkan Tempat Sampah (Hapus Semua Permanen)
              </button>
              <form id="empty-trash-form" action="{{ route('backdate-excel-files.empty-trash') }}" method="POST" style="display: none;">
                @csrf
                @method('DELETE')
              </form>
            </div>
          </div>

          {{-- Table of Trashed Files --}}
          <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" style="font-size: 13px;">
              <thead style="background: #f1f5f9; color: #475569;">
                <tr>
                  <th style="width: 40px;" class="text-center">No</th>
                  <th style="width: 160px;">Pertashop</th>
                  <th style="width: 120px;">Periode</th>
                  <th>Nama File Excel</th>
                  <th style="width: 100px;">Ukuran</th>
                  <th style="width: 150px;">Tanggal Dihapus</th>
                  <th style="width: 200px;" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($trashedFiles as $tIdx => $tFile)
                  <tr>
                    <td class="text-center align-middle">{{ $tIdx + 1 }}</td>
                    <td class="align-middle font-weight-bold" style="color: #0f172a;">
                      {{ $tFile->shop->nama ?? '-' }}
                    </td>
                    <td class="align-middle font-weight-bold" style="color: #334155;">
                      {{ $tFile->formatted_period }}
                    </td>
                    <td class="align-middle">
                      <span class="text-secondary" style="font-weight: 600;">{{ $tFile->original_filename }}</span>
                    </td>
                    <td class="align-middle text-muted">{{ $tFile->formatted_file_size }}</td>
                    <td class="align-middle text-danger">
                      <small><i class="far fa-clock mr-1"></i>{{ $tFile->deleted_at ? $tFile->deleted_at->format('d M Y H:i') : '-' }}</small>
                    </td>
                    <td class="text-center align-middle">
                      <div class="btn-group" role="group">
                        {{-- Restore button --}}
                        <form action="{{ route('backdate-excel-files.restore', $tFile->id) }}" method="POST" class="d-inline">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-success mr-1" style="font-size: 12px; font-weight: 600; border-radius: 4px;" title="Pulihkan Berkas">
                            <i class="fas fa-undo"></i> Pulihkan
                          </button>
                        </form>

                        {{-- Permanent Delete button --}}
                        <form id="force-delete-form-{{ $tFile->id }}" action="{{ route('backdate-excel-files.force-delete', $tFile->id) }}" method="POST" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="button" onclick="confirmForceDeleteFile('{{ $tFile->id }}', '{{ addslashes($tFile->original_filename) }}')" class="btn btn-sm btn-outline-danger" style="font-size: 12px; font-weight: 600; border-radius: 4px;" title="Hapus Permanen">
                            <i class="fas fa-times"></i> Hapus Permanen
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="text-center py-5" style="color: #94a3b8;">
            <i class="fas fa-trash-restore fa-3x mb-3 text-muted" style="opacity: 0.4;"></i>
            <h6 class="font-weight-bold" style="color: #475569;">Tempat Sampah Kosong</h6>
            <p class="small text-muted mb-0">Tidak ada berkas Excel yang saat ini berada di tempat sampah.</p>
          </div>
        @endif
      </div>

      {{-- Modal Footer --}}
      <div class="modal-footer bg-light py-2" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius: 6px; font-weight: 600;">
          Tutup
        </button>
      </div>

    </div>
  </div>
</div>

<script>
function confirmDeleteBackdateFile(e, fileId, filename) {
  e.preventDefault();
  const form = document.getElementById('delete-form-' + fileId);
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Pindahkan ke Tempat Sampah?',
      text: `File "${filename}" akan dipindahkan ke Tempat Sampah dan dapat dipulihkan kembali.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Pindahkan',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm(`Apakah Anda yakin ingin memindahkan file ${filename} ke Tempat Sampah?`)) {
      form.submit();
    }
  }
}

function confirmDeleteShopFiles(shopId, shopName) {
  const form = document.getElementById('delete-shop-files-form-' + shopId);
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Hapus Semua File Pertashop?',
      text: `Seluruh file Excel di toko "${shopName}" akan dipindahkan ke Tempat Sampah!`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Hapus Semua File',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm(`Seluruh file Excel di toko "${shopName}" akan dipindahkan ke Tempat Sampah. Lanjutkan?`)) {
      form.submit();
    }
  }
}

function confirmDeleteAllMasterFiles() {
  const form = document.getElementById('delete-all-master-form');
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'HAPUS SEMUA FILE ARSIP?',
      text: 'Semua berkas Excel Pertashop active akan dipindahkan ke Tempat Sampah!',
      icon: 'error',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Hapus Semua Active File!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm('APAKAH ANDA YAKIN ingin memindahkan SEMUA file aktif ke Tempat Sampah?')) {
      form.submit();
    }
  }
}

function confirmForceDeleteFile(fileId, filename) {
  const form = document.getElementById('force-delete-form-' + fileId);
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Hapus Permanen?',
      text: `File "${filename}" akan dihapus permanen beserta berkas fisiknya di server! Tindakan ini tidak dapat dibatalkan.`,
      icon: 'error',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Hapus Permanen',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm(`File "${filename}" akan dihapus PERMANEN dari server. Lanjutkan?`)) {
      form.submit();
    }
  }
}

function confirmRestoreAll() {
  const form = document.getElementById('restore-all-form');
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Pulihkan Semua Berkas?',
      text: 'Semua berkas yang ada di Tempat Sampah akan dikembalikan ke daftar arsip aktif.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<i class="fas fa-undo mr-1"></i> Ya, Pulihkan Semua',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm('Pulihkan semua berkas dari Tempat Sampah?')) {
      form.submit();
    }
  }
}

function confirmEmptyTrash() {
  const form = document.getElementById('empty-trash-form');
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'KOSONGKAN TEMPAT SAMPAH?',
      text: 'SELURUH file di Tempat Sampah akan DIHAPUS PERMANEN dari server dan tidak bisa dikembalikan lagi!',
      icon: 'error',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Kosongkan Permanen!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  } else {
    if (confirm('KOSONGKAN TEMPAT SAMPAH? Seluruh file akan dihapus permanen!')) {
      form.submit();
    }
  }
}
</script>
@endsection
