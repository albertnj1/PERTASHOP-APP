@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Import Laporan Excel</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Import Excel</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    {{ session('error') }}
                </div>
            @endif

            <div class="card card-primary card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Riwayat Upload Laporan Excel</h3>
                    <div class="ml-auto">
                        <a href="{{ route('excel-imports.trash') }}" class="btn btn-warning mr-2">
                            <i class="fa fa-trash-restore mr-1"></i> Tong Sampah
                        </a>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#uploadModal">
                            <i class="fa fa-upload mr-2"></i> Upload Berkas Baru
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Berkas</th>
                                    <th>Periode</th>
                                    <th>Outlet/Shop</th>
                                    <th>Di-upload Oleh</th>
                                    <th>Ukuran Berkas</th>
                                    <th>Waktu Upload</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($uploads as $index => $upload)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <i class="fa fa-file-excel text-success mr-2"></i>
                                            <strong>{{ $upload->nama_file }}</strong>
                                        </td>
                                        <td><span class="badge badge-info">{{ $upload->periode }}</span></td>
                                        <td>{{ $upload->shop->nama }} ({{ $upload->shop->kode }})</td>
                                        <td>{{ $upload->user->name }}</td>
                                        <td>{{ number_format($upload->file_size / 1024, 2) }} KB</td>
                                        <td>{{ $upload->created_at->format('d M Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('excel-imports.show', $upload->id) }}" class="btn btn-sm btn-info">
                                                <i class="fa fa-eye mr-1"></i> Detail
                                            </a>
                                            <form action="{{ route('excel-imports.destroy', $upload->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data import ini? Semua hitungan harian terkait akan terhapus.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fa fa-trash mr-1"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Belum ada riwayat upload laporan Excel.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('excel-imports.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Upload Laporan Excel Baru</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Pilih Outlet/Shop</label>
                            <select name="shop_id" class="form-control" required>
                                <option value="">-- Pilih Outlet --</option>
                                @foreach ($shops as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->nama }} ({{ $shop->kode }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Periode Laporan (e.g. Juni 2026)</label>
                            <input type="text" name="periode" class="form-control" placeholder="e.g. Juni 2026" required>
                        </div>
                        <div class="form-group">
                            <label>Pilih Berkas Excel (.xlsx, .xls)</label>
                            <input type="file" name="file" class="form-control-file" accept=".xlsx, .xls" required>
                            <small class="text-muted">Pastikan format sheet sesuai standar laporan historis.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-upload mr-2"></i> Mulai Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
