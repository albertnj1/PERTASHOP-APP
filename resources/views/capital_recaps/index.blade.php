@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Rekapitulasi Nilai Modal</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item active">Rekapitulasi Modal</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Gagal!</h5>
                {{ session('error') }}
            </div>
        @endif

        <div class="card card-primary card-outline">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form method="GET" action="{{ route('capital-recaps.index') }}" class="form-inline">
                        <div class="form-group mr-2">
                            <label class="mr-2">Pertashop: </label>
                            <select name="shop_id" class="form-control" onchange="this.form.submit()">
                                <option value="">-- Semua Pertashop --</option>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                    <a href="{{ route('monthly-reports.create') }}" class="btn btn-success">
                        <i class="fa fa-file-excel mr-2"></i> Upload dari Laporan Bulanan
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%;">
                    <table class="table table-bordered table-striped" style="white-space: nowrap; font-size: 13px; width: 100%;">
                        <thead>
                            <tr class="text-center">
                                <th>Pertashop</th>
                                <th>Tahun Ke</th>
                                <th>Bulan</th>
                                <th>Nilai Modal Awal</th>
                                <th>Penyusutan Karena Rugi</th>
                                <th>Pajak & Biaya Bank</th>
                                <th>Penambahan (Keuntungan)</th>
                                <th>Penambahan (Bunga Bank)</th>
                                <th>Nilai Penambahan/Penyusutan</th>
                                <th>Akumulasi Modal</th>
                                <th>Posisi Akhir Modal</th>
                                <th>Harga Beli Pertamax</th>
                                <th>Konversi (Liter)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $bulanIndo = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                            @endphp
                            @forelse($recaps as $recap)
                            <tr>
                                <td>{{ $recap->shop->nama }}</td>
                                <td class="text-center">{{ $recap->tahun_ke }}</td>
                                <td class="text-center">{{ $bulanIndo[$recap->bulan] ?? '' }} {{ $recap->tahun }}</td>
                                <td class="text-right">Rp {{ number_format($recap->nilai_modal_awal, 0, ',', '.') }}</td>
                                
                                <td class="text-right" {!! $recap->penyusutan_rugi < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->penyusutan_rugi > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : '') !!}>
                                    Rp {{ number_format($recap->penyusutan_rugi, 0, ',', '.') }}
                                </td>
                                <td class="text-right" {!! $recap->penyusutan_pajak_bank < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->penyusutan_pajak_bank > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : '') !!}>
                                    Rp {{ number_format($recap->penyusutan_pajak_bank, 0, ',', '.') }}
                                </td>
                                <td class="text-right" {!! $recap->penambahan_keuntungan < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->penambahan_keuntungan > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : '') !!}>
                                    Rp {{ number_format($recap->penambahan_keuntungan, 0, ',', '.') }}
                                </td>
                                <td class="text-right" {!! $recap->penambahan_bunga_bank < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->penambahan_bunga_bank > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : '') !!}>
                                    Rp {{ number_format($recap->penambahan_bunga_bank, 0, ',', '.') }}
                                </td>
                                
                                <td class="text-right" {!! $recap->nilai_penambahan_penyusutan < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->nilai_penambahan_penyusutan > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : '') !!}>
                                    Rp {{ number_format($recap->nilai_penambahan_penyusutan, 0, ',', '.') }}
                                </td>
                                
                                <td class="text-right" {!! $recap->akumulasi_penambahan_penyusutan < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($recap->akumulasi_penambahan_penyusutan > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : '') !!}>
                                    Rp {{ number_format($recap->akumulasi_penambahan_penyusutan, 0, ',', '.') }}
                                </td>
                                <td class="text-right font-weight-bold">Rp {{ number_format($recap->posisi_akhir_modal, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($recap->harga_beli_pertamax, 2, ',', '.') }}</td>
                                <td class="text-right font-weight-bold">{{ number_format($recap->konversi_liter, 2, ',', '.') }} L</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="14" class="text-center">Belum ada data Rekapitulasi Modal. Silakan Import Excel.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($recaps) > 0)
                        @php
                            $sum_rugi_idx = $recaps->sum('penyusutan_rugi');
                            $sum_pajak_idx = $recaps->sum('penyusutan_pajak_bank');
                            $sum_keuntungan_idx = $recaps->sum('penambahan_keuntungan');
                            $sum_bunga_idx = $recaps->sum('penambahan_bunga_bank');
                            $sum_net_idx = $recaps->sum('nilai_penambahan_penyusutan');
                        @endphp
                        <tfoot>
                            <tr class="font-weight-bold bg-light" style="font-size: 13px;">
                                <td colspan="3" class="text-center fw-bold">TOTAL PERUBAHAN</td>
                                <td>-</td>
                                <td class="text-right" style="color: #dc3545 !important; font-weight: bold;">Rp {{ number_format($sum_rugi_idx, 0, ',', '.') }}</td>
                                <td class="text-right" style="color: #dc3545 !important; font-weight: bold;">Rp {{ number_format($sum_pajak_idx, 0, ',', '.') }}</td>
                                <td class="text-right" style="color: #28a745 !important; font-weight: bold;">Rp {{ number_format($sum_keuntungan_idx, 0, ',', '.') }}</td>
                                <td class="text-right" style="color: #28a745 !important; font-weight: bold;">Rp {{ number_format($sum_bunga_idx, 0, ',', '.') }}</td>
                                <td class="text-right" {!! $sum_net_idx < 0 ? 'style="color: #dc3545 !important; font-weight: bold;"' : ($sum_net_idx > 0 ? 'style="color: #28a745 !important; font-weight: bold;"' : '') !!}>
                                    Rp {{ number_format($sum_net_idx, 0, ',', '.') }}
                                </td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
