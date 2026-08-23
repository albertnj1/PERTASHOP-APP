@extends('layouts._new_admin')

@section('title', 'Rekapitulasi Nilai Modal')

@section('content')
    <div class="metrics-card">
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

        @php
            $selectedShop = request('shop_id') ? $shops->firstWhere('id', request('shop_id')) : $shops->first();
            $modalDasar = $selectedShop && $selectedShop->modal_awal > 0 ? floatval($selectedShop->modal_awal) : 60000000;
            $latestRecap = $recaps->last();
            $totalAkumulasi = $latestRecap ? floatval($latestRecap->akumulasi_penambahan_penyusutan) : 0;
            $grandTotalModal = $latestRecap ? floatval($latestRecap->posisi_akhir_modal) : $modalDasar;
            $persenPenambahan = $modalDasar > 0 ? ($totalAkumulasi / $modalDasar) * 100 : 0;
            $persenTotal = 100 + $persenPenambahan;
        @endphp

        {{-- Executive KPI Metrics --}}
        @if($recaps->isNotEmpty())
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-white border rounded shadow-xs" style="border-left: 4px solid #2563eb !important;">
                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 11px;">Nilai Modal Dasar (100%)</small>
                    <h4 class="font-weight-bold text-dark mb-0">Rp {{ number_format($modalDasar, 0, ',', '.') }}</h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white border rounded shadow-xs" style="border-left: 4px solid #10b981 !important;">
                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 11px;">Akumulasi Penambahan Modal</small>
                    <h4 class="font-weight-bold text-success mb-0">+ Rp {{ number_format($totalAkumulasi, 0, ',', '.') }} <small style="font-size: 13px;">(+{{ number_format($persenPenambahan, 2) }}%)</small></h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-white border rounded shadow-xs" style="border-left: 4px solid #f59e0b !important;">
                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 11px;">Posisi Grand Total Modal</small>
                    <h4 class="font-weight-bold text-primary mb-0">Rp {{ number_format($grandTotalModal, 0, ',', '.') }} <small style="font-size: 13px;">({{ number_format($persenTotal, 2) }}%)</small></h4>
                </div>
            </div>
        </div>
        @endif

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <form method="GET" action="{{ route('capital-recaps.index') }}" class="form-inline" style="display: flex; align-items: center; gap: 12px;">
                <label style="margin: 0; font-weight: 600;">Pertashop:</label>
                <select name="shop_id" class="form-control" onchange="this.form.submit()" style="padding: 8px 16px; border-radius: 20px; border: 1px solid rgba(0,0,0,0.15); outline: none; background: #fff; cursor: pointer;">
                    <option value="">-- Semua Pertashop --</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
                    @endforeach
                </select>
            </form>

            <div class="d-flex align-items-center gap-2">
                @if($selectedShop)
                <form action="{{ route('capital-recaps.recalculate', $selectedShop->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Recalculate seluruh running saldo modal berantai untuk {{ $selectedShop->nama }}?')">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary" style="border-radius: 20px; padding: 8px 16px; font-weight: 600;">
                        <i class="fa fa-sync-alt mr-1"></i> Sinkronisasi Cascading Modal
                    </button>
                </form>
                @endif
                <a href="{{ route('capital-recaps.import') }}" class="btn btn-success" style="background: var(--success); border-color: var(--success); border-radius: 20px; padding: 8px 16px; font-weight: 600;">
                    <i class="fa fa-file-excel mr-1"></i> Import dari Excel (Multi-Sheet)
                </a>
            </div>
        </div>
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
@endsection
