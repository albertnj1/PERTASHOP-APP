@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Posisi Modal Kerja {{ $date->monthName . ' ' . $date->year }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        {{-- <li class="breadcrumb-item"><a href="/laporan">Laporan</a></li> --}}
                        <li class="breadcrumb-item"><a href="{{ route('modal.index') }}">Posisi Modal Kerja</a></li>
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
                            <h3 class="card-title font-weight-bold text-uppercase text-center">
                                Posisi Modal Kerja Periode
                                {{ $date->startOfMonth()->format('d') }} s/d
                                {{ $date->endOfMonth()->format('d') . ' ' . $date->monthName . ' ' . $date->year }}
                                <br>PERTASHOP {{ $shop->kode }} {{ $shop->alamat }} <br> {{ $shop->corporation->nama }}
                            </h3>
                        </div>


                        <br>
                        <div class="table-responsive-lg">

                            <h6 class="text-center font-weight-bold">POSISI MODAL KERJA</h6>
                            <table id="table" style="border: 1.5px solid black">
                                <tr style="border: 1.5px solid black">
                                    <th colspan="2" class="text-right font-italic">Saldo Awal Modal Periode Bulan
                                        Sebelumnya</th>
                                    <th width="20">:</th>
                                    <th class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">1500000</span>
                                    </th>
                                    <th width="5"></th>
                                    <th width="20"></th>
                                </tr>
                                <tr>
                                    <td width="20">1.</td>
                                    <td class="d-flex justify-content-between">
                                        <span>DO yang masih ada di Pertamina</span>
                                        <span>{{ $sisa_do }} &ell; <span class="px-2">x</span>Rp <span
                                                class="number">{{ $harga_beli }}</span></span>
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">{{ $rupiah_sisa_do }}</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td width="20">2.</td>
                                    <td>
                                        Uang Di Bank Periode Bulan ini
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">1500000</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td width="20">3.</td>
                                    <td>
                                        Kas Kecil di Pertashop (TUNAI)
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">1500000</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td width="20">4.</td>
                                    <td>
                                        Hasil Penjualan yang Belum Disetor di Akhir Periode (TUNAI)
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">{{ $belum_disetorkan }}</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td width="20">5.</td>
                                    <td>
                                        Piutang
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between line-bottom">
                                        <span>Rp</span><span class="number-int">1500000</span>
                                    </td>
                                    <td class="px-1">+</td>
                                    <td><button class="btn btn-sm btn-link"><i class="fas fa-edit"></i></button></td>
                                </tr>
                                <tr style="border-bottom: 1.5px solid black">
                                    <td width="20"></td>
                                    <td class="text-right font-italic">
                                        A. Sub Total Saldo Akhir Modal
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">1500000</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td width="20">6.</td>
                                    <td>
                                        Bunga Bank Periode Bulan ini
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">1500000</span>
                                    </td>
                                    <td></td>
                                    <td><button class="btn btn-sm btn-link"><i class="fas fa-edit"></i></button></td>
                                </tr>
                                <tr>
                                    <td width="20">7.</td>
                                    <td>
                                        Pajak Bank Periode Bulan ini
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">1500000</span>
                                    </td>
                                    <td></td>
                                    <td><button class="btn btn-sm btn-link"><i class="fas fa-edit"></i></button></td>
                                </tr>
                                <tr>
                                    <td width="20">8.</td>
                                    <td>
                                        Profit Sharing yang dibagi ke Investor
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">{{ $profit_sharing }}</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td width="20">9.</td>
                                    <td>
                                        Penambahan Modal dari Keuntungan bulan ini
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between line-bottom">
                                        <span>Rp</span><span class="number-int">{{ $alokasi_keuntungan }}</span>
                                    </td>
                                    <td class="px-1">+</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td width="20"></td>
                                    <td class="text-right font-italic">
                                        B. Sub Total Penambahan Modal Modal
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">1500000</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr class="font-weight-bold" style="border-top: 1.5px solid black">
                                    <td width="20"></td>
                                    <td class="text-right">
                                        Total Saldo Akhir Modal (A+B)
                                    </td>
                                    <th width="20">:</th>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span><span class="number-int">1500000</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </table>

                            <br>

                            <div class="d-flex justify-content-end">
                                <div class="text-center px-4">
                                    <div>Banyumas,
                                        {{ $date->endOfMonth()->format('d') . ' ' . $date->monthName . ' ' . $date->year }}
                                    </div>
                                    <div style="margin-bottom: 3cm">Dibuat Oleh,</div>
                                    <div>{{ Auth::user()->name }}</div>
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
        #table {
            width: 100%;
        }

        #table td,
        #table th {
            padding: 5px;
        }

        .line-bottom {
            border-bottom: 2px solid #000;
        }

        @media print {
            body {
                visibility: hidden;
            }

            .btn-link {
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
