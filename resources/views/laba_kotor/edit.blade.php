@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Laba Kotor {{ $date->monthName . ' ' . $date->year }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        {{-- <li class="breadcrumb-item"><a href="/laporan">Laporan</a></li> --}}
                        <li class="breadcrumb-item"><a href="{{ route('laba-kotor.index') }}">Laba Kotor</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card card-primary card-outline">
                <div class="card-header">
                    <div class=" d-flex justify-content-between align-items-center">
                        <h3 class="card-title">{{ $shop->kode . ' ' . $shop->nama }}</h3>
                    </div>

                </div>

                <div class="card-body">

                    <div class="laba-kotor container-fluid text-sm" id="section-to-print">
                        <div class="pb-2 mb-2 d-flex justify-content-center" style="border-bottom: 4px solid #000">
                            <h3 class="card-title font-weight-bold text-uppercase text-center">Laporan Stock, Penjualan &
                                Laba Kotor
                                {{ $date->startOfMonth()->format('d') }} s/d
                                {{ $date->endOfMonth()->format('d') . ' ' . $date->monthName . ' ' . $date->year }}
                                <br>PERTASHOP {{ $shop->kode }} {{ $shop->alamat }} <br> {{ $shop->corporation->nama }}
                            </h3>
                        </div>
                        <p class="font-italic mb-0">PERTAMAX:</p>
                        @foreach ($reports as $i => $r)
                            <div class="row">
                                <div class="col-md-3">Harga Beli {{ $i + 1 }} : Rp <span
                                        class="number">{{ $r['harga_beli'] }}</span></div>
                                <div class="col-md-3">Harga Jual {{ $i + 1 }}: Rp <span
                                        class="number">{{ $r['harga_jual'] }}</span></div>
                                @if ($loop->last)
                                    <div class="col-md-6 text-md-right">Rata-rata omset Harian (&ell;) = <span
                                            class="number">{{ $reports->sum('rata_rata_omset_harian') / $reports->count() }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        <hr>

                        <div class="table-responsive-lg">

                            @foreach ($reports as $i => $r)
                                <table style="width: 100%">
                                    <tr class="font-weight-bold">
                                        <td>I.</td>
                                        <td>PEMBELIAN {{ $i + 1 }}</td>
                                        <td colspan="100%"></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td width="300" class="pl-3">Stok Awal</td>
                                        <td class="px-2">=</td>
                                        <td class="text-right"><span class="number">{{ $r['stok_awal'] }}</span><span
                                                class="ml-2">&ell;</span></td>
                                        <td class="px-3">x</td>
                                        <td class="d-flex justify-content-between">
                                            <span>Rp</span><span class="number">{{ $r['stok_awal_harga_beli'] }}</span>
                                        </td>
                                        <td class="px-5">&#10132;</td>
                                        <td class="d-flex justify-content-between">
                                            <span>Rp</span><span
                                                class="number-int">{{ $r['stok_awal'] * $r['stok_awal_harga_beli'] }}</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="d-flex justify-content-between pl-3">
                                            <span class="mr-2">Datang</span>
                                            <span>
                                                <span>{{ $r['count_datang'] }}</span>
                                                <span class="px-3">x</span>
                                                <span class="number-int">2000</span>
                                                <span class="ml-2">&ell;</span>
                                            </span>
                                        </td>
                                        <td class="px-2">=</td>
                                        <td class="text-right line-bottom"><span
                                                class="number">{{ $r['datang'] }}</span><span class="ml-2">&ell;</span>
                                        </td>
                                        <td class="px-3">x</td>
                                        <td class="d-flex justify-content-between">
                                            <span>Rp</span>
                                            <span class="number">{{ $r['harga_beli'] }}</span>
                                        </td>
                                        <td class="px-5">&#10132;</td>
                                        <td class="d-flex justify-content-between line-bottom"><span>Rp</span><span
                                                class="number-int">{{ $r['datang'] * $r['harga_beli'] }}</span>
                                        </td>
                                        <td class="px-3">+</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="font-weight-bold font-italic">A. Jumlah Pembelian 1
                                        </td>
                                        <td class="px-2"></td>
                                        <td class="text-right"><span
                                                class="number">{{ $r['jumlah_pembelian'] }}</span><span
                                                class="ml-2">&ell;</span></td>
                                        <td></td>
                                        <td></td>
                                        <td class="px-5"></td>
                                        <td class="d-flex justify-content-between font-weight-bold">
                                            <span>Rp</span><span class="number-int">{{ $r['jumlah_pembelian_rp'] }}</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="font-weight-bold">
                                        <td>II.</td>
                                        <td>PENJUALAN {{ $i + 1 }}</td>
                                        <td colspan="100%"></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="pl-3">a. Totalisator Akhir {{ $i + 1 }}</td>
                                        <td class="px-2">=</td>
                                        <td class="text-right"><span
                                                class="number">{{ $r['totalisator_akhir'] }}</span><span
                                                class="ml-2">&ell;</span></td>
                                        <td colspan="100%"></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="pl-3">b. Totalisator Awal {{ $i + 1 }}</td>
                                        <td class="px-2">=</td>
                                        <td class="text-right line-bottom"><span
                                                class="number">{{ $r['totalisator_awal'] }}</span><span
                                                class="ml-2">&ell;</span></td>
                                        <td class="px-3">-</td>
                                        <td colspan="100%"></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="font-weight-bold pl-3">c. Total Penjualan (a-b)</td>
                                        <td class="px-2">=</td>
                                        <td class="text-right font-weight-bold"><span
                                                class="number">{{ $r['total_penjualan'] }}</span><span
                                                class="ml-2">&ell;</span></td>
                                        <td colspan="100%"></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="pl-3">d. Percobaan 1 (Test Pump)</td>
                                        <td class="px-2">=</td>
                                        <td class="text-right line-bottom"><span
                                                class="number">{{ $r['test_pump'] }}</span><span
                                                class="ml-2">&ell;</span>
                                        </td>
                                        <td class="px-3">-</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="font-weight-bold font-italic">B. Jumlah Penjualan {{ $i + 1 }}
                                            (c-d)
                                        </td>
                                        <td class="px-2">=</td>
                                        <td class="text-right"><span
                                                class="number">{{ $r['jumlah_penjualan'] }}</span><span
                                                class="ml-2">&ell;</span></td>
                                        <td class="px-3">x</td>
                                        <td class="d-flex justify-content-between"><span>Rp</span><span
                                                class="number">{{ $r['harga_jual'] }}</span>
                                        </td>
                                        <td class="px-5">&#10132;</td>
                                        <td class="d-flex justify-content-between">
                                            <span>Rp</span><span
                                                class="number-int">{{ $r['jumlah_penjualan'] * $r['harga_jual'] }}</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="font-weight-bold font-italic pl-3">Sisa Stock {{ $i + 1 }} (A-B)
                                        </td>
                                        <td class="px-2">=</td>
                                        <td class="text-right"><span class="number">{{ $r['sisa_stok'] }}</span><span
                                                class="ml-2">&ell;</span></td>
                                        <td class="px-3">x</td>
                                        <td class="d-flex justify-content-between"><span>Rp</span><span
                                                class="number">{{ $r['harga_beli'] }}</span>
                                        </td>
                                        <td class="px-5">&#10132;</td>
                                        <td class="d-flex justify-content-between line-bottom">
                                            <span>Rp</span><span
                                                class="number-int">{{ $r['sisa_stok'] * $r['harga_beli'] }}</span>
                                        </td>
                                        <td class="px-3">+</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="pl-3">Jumlah</td>
                                        <td colspan="5"></td>
                                        <td class="d-flex justify-content-between">
                                            <span>Rp</span><span
                                                class="number-int">{{ $r['jumlah_penjualan'] * $r['harga_jual'] + $r['sisa_stok'] * $r['harga_beli'] }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="d-flex justify-content-between pl-3">
                                            <span>Losses/Gain</span>
                                            <span class="px-3">&#10132;</span>
                                            <span class="text-danger mr-2">Losses</span>
                                            <span class="text-danger">(<span
                                                    class="number">{{ $r['persen_losses_gain'] }}</span>) %</span>
                                        </td>
                                        <td class="px-2">=</td>
                                        <td class="text-right text-danger">(<span
                                                class="number">{{ $r['losses_gain'] }}</span>)<span
                                                class="ml-2">&ell;</span></td>
                                        <td class="px-3">x</td>
                                        <td class="d-flex justify-content-between"><span>Rp</span><span
                                                class="number">{{ $r['harga_beli'] }}</span>
                                        </td>
                                        <td class="px-5">&#10132;</td>
                                        <td class="d-flex justify-content-between text-danger line-bottom">
                                            <span>Rp</span><span
                                                class="number-int">{{ $r['losses_gain'] * $r['harga_beli'] }}</span>
                                        </td>
                                        <td class="px-3">+</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="font-weight-bold font-italic">C. Jumlah Penjualan Bersih
                                            {{ $i + 1 }}
                                        </td>
                                        <td colspan="5"></td>
                                        <td class="d-flex justify-content-between font-weight-bold">
                                            <span>Rp</span><span
                                                class="number-int">{{ $r['jumlah_penjualan_bersih_rp'] }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">III.</td>
                                        <td class="d-flex justify-content-between"><span class="font-weight-bold ">SISA
                                                STOK
                                                AKHIR
                                                {{ $i + 1 }}</span> <span><span
                                                    class="number">{{ $r['stik_akhir'] }}</span><span
                                                    class="ml-2">cm</span></span>
                                        </td>
                                        <td class="px-2">=</td>
                                        <td class="text-right"><span
                                                class="number">{{ $r['sisa_stok_akhir'] }}</span><span
                                                class="ml-2">&ell;</span></td>
                                        <td class="px-3">x</td>
                                        <td class="d-flex justify-content-between"><span>Rp</span><span
                                                class="number">{{ $r['harga_beli'] }}</span>
                                        </td>
                                        <td class="px-5">&#10132;</td>
                                        <td class="d-flex justify-content-between"><span>Rp</span><span
                                                class="number-int">{{ $r['sisa_stok_akhir'] * $r['harga_beli'] }}</span>
                                        </td>
                                    </tr>
                                </table>
                                <hr>
                            @endforeach

                            <div class="d-flex justify-content-end">
                                @if ($reports->count() > 1)
                                    <div class="">
                                        @foreach ($reports as $i => $r)
                                            <table class="mb-2">
                                                <tr>
                                                    <td width="120" class="p-2 text-xl text-center"
                                                        style="border: 2px solid #000">
                                                        {{ $i + 1 }}
                                                    </td>
                                                    <td width="360" class="p-2" style="border: 2px solid #000">
                                                        <table width="100%">
                                                            <tr>
                                                                <td width="180" class="text-right">Jumlah Penjualan
                                                                    Bersih
                                                                    {{ $i + 1 }}
                                                                </td>
                                                                <td class="px-2">=</td>
                                                                <td class="d-flex justify-content-between">
                                                                    <span>Rp</span><span
                                                                        class="number-int">{{ $r['jumlah_penjualan_bersih_rp'] }}</span>
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-right">Jumlah Pembelian
                                                                    {{ $i + 1 }}
                                                                </td>
                                                                <td class="px-2">=</td>
                                                                <td class="d-flex justify-content-between line-bottom">
                                                                    <span>Rp</span><span
                                                                        class="number-int">{{ $r['jumlah_pembelian_rp'] }}</span>
                                                                </td>
                                                                <td class="px-1">-</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-right font-weight-bold">Laba Kotor
                                                                    {{ $i + 1 }}
                                                                    (B-A)
                                                                </td>
                                                                <td class="px-2">=</td>
                                                                <td
                                                                    class="d-flex justify-content-between font-weight-bold">
                                                                    <span>Rp</span><span
                                                                        class="number-int">{{ $r['laba_kotor'] }}</span>
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        @endforeach
                                        <table class="mb-2">
                                            <tr>
                                                <td width="120" class="p-2 text-xl text-center"
                                                    style="border: 2px solid #000">
                                                    1 + 2
                                                </td>
                                                <td width="360" class="p-2" style="border: 2px solid #000">
                                                    <table width="100%">
                                                        @foreach ($reports as $i => $r)
                                                            <tr>
                                                                <td width="180" class="text-right">Laba Kotor
                                                                    {{ $i + 1 }} (B-A)
                                                                </td>
                                                                <td class="px-2">=</td>
                                                                <td
                                                                    class="d-flex justify-content-between @if ($loop->last) line-bottom @endif">
                                                                    <span>Rp</span><span
                                                                        class="number-int">{{ $r['laba_kotor'] }}</span>
                                                                </td>
                                                                <td class="px-1">
                                                                    @if ($loop->last)
                                                                        -
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach

                                                        <tr>
                                                            <td class="text-right font-weight-bold ">Grand Total Laba Kotor
                                                            </td>
                                                            <td class="px-2">=</td>
                                                            <td class="d-flex justify-content-between font-weight-bold">
                                                                <span>Rp</span><span
                                                                    class="number-int">{{ $reports->sum('laba_kotor') }}</span>
                                                            </td>
                                                            <td></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                @else
                                    <div class="">
                                        <table class="mb-2">
                                            <tr>
                                                <td width="120" class="p-2 text-lg text-center"
                                                    style="border: 2px solid #000">
                                                    Laba Kotor
                                                </td>
                                                <td width="360" class="p-2" style="border: 2px solid #000">
                                                    <table width="100%">
                                                        <tr>
                                                            <td width="180" class="text-right">Jumlah Penjualan Bersih
                                                            </td>
                                                            <td class="px-2">=</td>
                                                            <td class="d-flex justify-content-between"><span>Rp</span><span
                                                                    class="number-int">{{ count($reports) > 0 ? $reports[0]['jumlah_penjualan_bersih_rp'] : 0 }}</span>
                                                            </td>
                                                            <td></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-right">Jumlah Pembelian
                                                            </td>
                                                            <td class="px-2">=</td>
                                                            <td class="d-flex justify-content-between line-bottom">
                                                                <span>Rp</span><span
                                                                    class="number-int">{{ count($reports) ? $reports[0]['jumlah_pembelian_rp'] : 0 }}</span>
                                                            </td>
                                                            <td class="px-1">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-right font-weight-bold">Total Laba Kotor
                                                            </td>
                                                            <td class="px-2">=</td>
                                                            <td class="d-flex justify-content-between font-weight-bold">
                                                                <span>Rp</span><span
                                                                    class="number-int">{{ count($reports) ? $reports[0]['laba_kotor'] : 0 }}</span>
                                                            </td>
                                                            <td></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                @endif


                            </div>

                        </div>
                        <p class="text-right px-3">Banyumas,
                            {{ $date->endOfMonth()->format('d') . ' ' . $date->monthName . ' ' . $date->year }}
                        <p>
                        <div class="row">
                            <div class="col-sm-9">
                                <div class="text-center">Disetujui Oleh,</div>
                                <div class="row">
                                    @foreach ($shop->investors as $investor)
                                        <div class="col-sm mb-2 px-1">
                                            <div class="text-center">
                                                <p style="margin-top: 3cm">{{ $investor->user->name }}</p>
                                            </div>
                                        </div>
                                    @endforeach

                                </div>
                            </div>
                            <div class="col-sm-3 mb-2 px-1">
                                <div class="text-center ">
                                    <div>Dibuat Oleh,</div>
                                    <p style="margin-top: 3cm">{{ Auth::user()->name }}</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card-footer">
                    <div class="text-right">
                        <button class="btn btn-warning" onclick="window.print()">
                            <i class="fas fa-print mr-2 "></i>
                            <span>Cetak</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>


    </section>


@endsection

@push('style')
    <style>
        .line-bottom {
            border-bottom: 2px solid #000;
        }

        @media print {
            body {
                visibility: hidden;
            }

            #section-to-print {
                visibility: visible;
                position: absolute;
                left: 0;
                top: 0;
            }
        }
    </style>
@endpush
