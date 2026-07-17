@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Detail Kalkulasi Laporan Harian</h1>
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

            <div class="row">
                <div class="col-md-8">
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Hasil Perhitungan Otomatis</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%">Outlet / Shop</th>
                                        <td>{{ $report->shop->nama }} ({{ $report->shop->kode }})</td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Laporan</th>
                                        <td>{{ \Carbon\Carbon::parse($report->tanggal)->format('d F Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Volume Terjual</th>
                                        <td>
                                            <span class="text-info" title="Totalisator Akhir">{{ number_format($report->totalisator_akhir, 2) }}</span> - 
                                            <span class="text-info" title="Totalisator Awal">{{ number_format($report->totalisator_awal, 2) }}</span> 
                                            @if($report->test_pump > 0)
                                            - <span class="text-warning" title="Test Pump">{{ number_format($report->test_pump, 2) }}</span>
                                            @endif
                                            = 
                                            <strong>{{ number_format($report->volume_terjual, 2) }} Liter</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Harga Jual Berlaku</th>
                                        <td>Rp {{ number_format($report->harga_jual, 0, ',', '.') }} / Liter</td>
                                    </tr>
                                    <tr class="bg-light">
                                        <th>1. PENDAPATAN OPERATOR</th>
                                        <td><strong class="text-primary">Rp {{ number_format($report->pendapatan_operator, 0, ',', '.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>2. JUMLAH PENDAPATAN BERSIH</th>
                                        <td><strong class="text-primary">Rp {{ number_format($report->jumlah_pendapatan_bersih, 0, ',', '.') }}</strong> <small class="text-muted">(Total Cash + QRIS)</small></td>
                                    </tr>
                                    <tr>
                                        <th>3. PEMBAYARAN QRIS</th>
                                        <td><strong class="text-warning">Rp {{ number_format($report->qris, 0, ',', '.') }}</strong></td>
                                    </tr>
                                    @if($report->pengeluaran > 0)
                                    <tr>
                                        <th>Pengeluaran Lainnya</th>
                                        <td>
                                            <strong class="text-danger">Rp {{ number_format($report->pengeluaran, 0, ',', '.') }}</strong>
                                            @if($report->keterangan_pengeluaran)
                                                <br><small class="text-muted">Keterangan: {{ $report->keterangan_pengeluaran }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    <tr class="bg-success text-white">
                                        <th>4. JUMLAH YANG DISETORKAN (Wajib)</th>
                                        <td><h4>Rp {{ number_format($report->jumlah_disetorkan, 0, ',', '.') }}</h4></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('daily-report-uploads.index') }}" class="btn btn-primary"><i class="fa fa-list mr-1"></i> Kembali ke Daftar Laporan</a>
                            <form action="{{ route('daily-report-uploads.destroy', $report->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus laporan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger float-right"><i class="fa fa-trash mr-1"></i> Hapus Laporan</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Bukti Lampiran</h3>
                        </div>
                        <div class="card-body text-center">
                            @if($report->file_path)
                                @php 
                                    $ext = pathinfo($report->file_path, PATHINFO_EXTENSION); 
                                    $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png']);
                                @endphp
                                
                                @if($isImage)
                                    <img src="{{ Storage::url($report->file_path) }}" alt="Bukti Laporan" class="img-fluid rounded mb-3" style="max-height: 300px;">
                                @else
                                    <div class="alert alert-info">
                                        <i class="fa fa-file-excel fa-3x mb-3"></i>
                                        <h5>File Excel Terlampir</h5>
                                    </div>
                                @endif
                                <button onclick="downloadPDF()" class="btn btn-danger btn-block"><i class="fa fa-file-pdf mr-1"></i> Unduh Laporan (PDF)</button>
                            @else
                                <div class="text-muted py-5">
                                    <i class="fa fa-file-image fa-4x mb-3 text-light"></i>
                                    <p>Tidak ada file bukti yang dilampirkan.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        // Hides the button during PDF generation to keep it clean
        const element = document.querySelector('.content');
        const opt = {
            margin:       0.5,
            filename:     'Laporan_Harian_{{ preg_replace('/[^a-zA-Z0-9]/', '_', $report->shop->nama) }}_{{ $report->tanggal }}.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>
@endsection
