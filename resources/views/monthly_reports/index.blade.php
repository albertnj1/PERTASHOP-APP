@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Riwayat Laporan Bulanan</h1>
        <a href="{{ route('monthly-reports.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Upload Laporan Bulanan
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Pertashop</th>
                            <th>Periode</th>
                            <th>Total Setoran</th>
                            <th>Waktu Upload</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $index => $report)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $report->shop->nama }}</td>
                                <td>{{ date('F Y', strtotime($report->bulan_tahun)) }}</td>
                                <td class="font-weight-bold text-success">
                                    Rp {{ number_format($report->grand_totals['disetorkan'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td>{{ $report->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('monthly-reports.show', $report->id) }}" class="btn btn-sm btn-info text-white">
                                        <i class="fas fa-eye"></i> Lihat Data
                                    </a>
                                    <form action="{{ route('monthly-reports.destroy', $report->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini secara permanen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data laporan bulanan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
