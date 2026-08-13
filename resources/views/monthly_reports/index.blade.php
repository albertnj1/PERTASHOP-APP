@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Bulanan</h1>
        @if(Auth::user()->role !== 'investor')
        <div>
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#generateModal">
                <i class="fas fa-bolt mr-1"></i> GENERATE LAPORAN BULANAN
            </button>
        </div>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
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
                            <th>Waktu Pembuatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $index => $report)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $report->shop->nama ?? '-' }}</strong></td>
                                <td>{{ $report->bulan_tahun }}</td>
                                <td class="font-weight-bold text-success">
                                    Rp {{ number_format($report->grand_totals['total_disetorkan'] ?? $report->grand_totals['disetorkan'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td>{{ $report->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('monthly-reports.show', $report->id) }}" class="btn btn-sm btn-info text-white" title="Lihat Detail">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                    <a href="{{ route('monthly-reports.export-pdf', $report->id) }}" class="btn btn-sm btn-danger text-white ml-1" title="Unduh PDF Siap Kirim">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                    @if(Auth::user()->role !== 'investor')
                                    <form action="{{ route('monthly-reports.destroy', $report->id) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada data laporan bulanan. Gunakan tombol <strong>Generate Otomatis</strong> untuk membuat laporan dari Laporan Harian.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL GENERATE OTOMATIS --}}
@if(Auth::user()->role !== 'investor')
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--page-bg); border-bottom: 1px solid var(--border);">
                <h5 class="modal-title font-weight-bold" style="font-size: 15px;">Generate Laporan Bulanan Otomatis</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('monthly-reports.generate') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted style-sm">Sistem akan mengumpulkan dan menghitung agregat dari seluruh <strong>Laporan Harian</strong> toko pada bulan yang dipilih.</p>
                    <div class="form-group">
                        <label class="font-weight-bold">Pertashop / Toko</label>
                        <select name="shop_id" class="form-control" required>
                            @php
                                $shopsList = \App\Models\Shop::all();
                                if (Auth::user()->role === 'admin') {
                                    $shopsList = \App\Models\Shop::where('id', Auth::user()->admin->shop_id)->get();
                                }
                            @endphp
                            @foreach($shopsList as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Bulan & Tahun</label>
                        <input type="month" name="year_month" class="form-control" required value="{{ date('Y-m') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success font-weight-bold">Generate Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
