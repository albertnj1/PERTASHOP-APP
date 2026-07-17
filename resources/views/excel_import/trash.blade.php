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
                        <li class="breadcrumb-item"><a href="{{ route('excel-imports.index') }}">Import Excel</a></li>
                        <li class="breadcrumb-item active">Tong Sampah</li>
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
                    <h3 class="card-title">Tong Sampah Laporan Excel</h3>
                    <div class="ml-auto">
                        <a href="{{ route('excel-imports.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left mr-1"></i> Kembali
                        </a>
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
                                @forelse ($trashedUploads as $index => $upload)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <i class="fa fa-file-excel text-secondary mr-2"></i>
                                            <strong class="text-secondary"><del>{{ $upload->nama_file }}</del></strong>
                                        </td>
                                        <td><span class="badge badge-secondary">{{ $upload->periode }}</span></td>
                                        <td>{{ $upload->shop->nama }} ({{ $upload->shop->kode }})</td>
                                        <td>{{ $upload->user->name }}</td>
                                        <td>{{ number_format($upload->file_size / 1024, 2) }} KB</td>
                                        <td>{{ $upload->deleted_at->format('d M Y H:i') }} (Dihapus)</td>
                                        <td>
                                            <form action="{{ route('excel-imports.restore', $upload->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fa fa-trash-restore mr-1"></i> Restore
                                                </button>
                                            </form>
                                            <form action="{{ route('excel-imports.force-delete', $upload->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus permanen data import ini beserta file aslinya? Tindakan ini tidak dapat dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fa fa-ban mr-1"></i> Hapus Permanen
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Tong sampah kosong.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
