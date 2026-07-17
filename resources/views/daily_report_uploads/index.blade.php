@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Riwayat Upload Laporan Harian</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    {{ session('success') }}
                </div>
            @endif

            <div class="card card-primary card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Laporan Harian</h3>
                    <div class="ml-auto">
                        <a href="{{ route('daily-report-uploads.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus mr-1"></i> Upload Laporan Baru
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Outlet</th>
                                    <th>Tanggal</th>
                                    <th>Volume (L)</th>
                                    <th>Pendapatan</th>
                                    <th>QRIS</th>
                                    <th>Setoran Wajib</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports as $index => $report)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $report->shop->nama }}</td>
                                        <td>{{ \Carbon\Carbon::parse($report->tanggal)->format('d M Y') }}</td>
                                        <td>{{ number_format($report->volume_terjual, 2) }}</td>
                                        <td>Rp {{ number_format($report->pendapatan_operator, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($report->qris, 0, ',', '.') }}</td>
                                        <td><strong class="text-success">Rp {{ number_format($report->jumlah_disetorkan, 0, ',', '.') }}</strong></td>
                                        <td>
                                            <a href="{{ route('daily-report-uploads.show', $report->id) }}" class="btn btn-sm btn-info">
                                                <i class="fa fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">Belum ada data upload laporan harian.</td>
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
