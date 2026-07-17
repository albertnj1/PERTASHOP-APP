@extends('layouts.app')


@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Laba Bersih {{ $date->monthName . ' ' . $date->year }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        {{-- <li class="breadcrumb-item"><a href="/laporan">Laporan</a></li> --}}
                        <li class="breadcrumb-item"><a href="{{ route('laba-bersih.index') }}">Laba Bersih</a></li>
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
                                Perhitungan Laba Bersih
                                {{ $date->startOfMonth()->format('d') }} s/d
                                {{ $date->endOfMonth()->format('d') . ' ' . $date->monthName . ' ' . $date->year }}
                                <br>PERTASHOP {{ $shop->kode }} {{ $shop->alamat }} <br> {{ $shop->corporation->nama }}
                            </h3>
                        </div>


                        <div class="table-responsive-lg">

                            <table style="width: 100%">
                                <tr>
                                    <td class="font-weight-bold">PENDAPATAN</td>
                                    <td width="50"></td>
                                    <td width="150"></td>
                                    <td width="20"></td>
                                    <td width="150"></td>
                                    <td width="20"></td>
                                </tr>
                                <tr>
                                    <td><span style="width: 20px; display: inline-block">1.</span>
                                        <span>LABA KOTOR</span>
                                    </td>
                                    <td>=</td>
                                    <td class="line-bottom d-flex justify-content-between">
                                        <span>Rp</span>
                                        <span class="number-int">{{ $report['laba_kotor'] }}</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="d-flex justify-content-between font-weight-bold">
                                        <span>Rp</span>
                                        <span class="number-int">{{ $report['laba_kotor'] }}</span>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">PENGELURARAN</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                @foreach ($spendings as $spending)
                                    <tr>
                                        <td><span style="width: 20px; display: inline-block">{{ $loop->iteration }}.</span>
                                            <span class="text-uppercase">{{ $spending['pengeluaran'] }}</span>
                                        </td>
                                        <td>=</td>
                                        <td
                                            class="d-flex justify-content-between @if ($loop->last) line-bottom @endif">
                                            <span>Rp</span>
                                            <span class="number-int">{{ $spending['jumlah'] }}</span>
                                        </td>
                                        <td class="px-2">
                                            @if ($loop->last)
                                                +
                                            @endif
                                        </td>
                                        <td></td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td class="d-flex justify-content-between">
                                        <span>Rp</span>
                                        <span class="number-int">{{ $spendings->sum('jumlah') }}</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>

                            </table>
                            <br>
                            <table style="width: 100%">
                                <tr class="font-weight-bold">
                                    <td class="text-right">Total Laba Kotor</td>
                                    <td width="20" class="px-2">=</td>
                                    <td width="150">
                                        <div class="d-flex justify-content-between">
                                            <span>Rp</span>
                                            <span class="number-int">{{ $report['laba_kotor'] }}</span>
                                        </div>

                                    </td>
                                    <td width="20"></td>
                                </tr>
                                <tr class="font-weight-bold">
                                    <td class="text-right">Total Biaya</td>
                                    <td width="20" class="px-2">=</td>
                                    <td width="150">
                                        <div class="line-bottom d-flex justify-content-between">
                                            <span>Rp</span>
                                            <span class="number-int">{{ $report['total_biaya'] }}</span>
                                        </div>

                                    </td>
                                    <td width="20" class="px-2">-</td>
                                </tr>
                                <tr class="font-weight-bold">
                                    <td class="text-right">Laba Bersih</td>
                                    <td width="20" class="px-2">=</td>
                                    <td width="150">
                                        <div class="d-flex justify-content-between">
                                            <span>Rp</span>
                                            <span class="number-int">{{ $report['laba_bersih'] }}</span>
                                        </div>

                                    </td>
                                    <td width="20"></td>
                                </tr>
                                <tr class="font-weight-bold">
                                    <td class="text-right"><button class="btn btn-sm btn-link btn-alokasi"><i
                                                class="fas fa-edit"></i></button>Alokasi Modal Dasar dari
                                        {{ $report['persentase_alokasi_modal'] }}% Profit</td>
                                    <td width="20" class="px-2">=</td>
                                    <td width="150">
                                        <div class="line-bottom d-flex justify-content-between">
                                            <span>Rp</span>
                                            <span class="number-int">{{ $report['alokasi_modal'] }}</span>
                                        </div>

                                    </td>
                                    <td width="20" class="px-2">+</td>
                                </tr>
                                <tr class="font-weight-bold">
                                    <td class="text-right">Saldo Laba Bersih Dibagi</td>
                                    <td width="20" class="px-2">=</td>
                                    <td width="150">
                                        <div class="d-flex justify-content-between">
                                            <span>Rp</span>
                                            <span class="number-int">{{ $report['laba_bersih_dibagi'] }}</span>
                                        </div>

                                    </td>
                                    <td width="20"></td>
                                </tr>
                            </table>
                            <br>
                            <table style="min-width: 50%">
                                <tr class="font-weight-bold">
                                    <td>Pembagian Laba Bersih</td>
                                </tr>
                                @foreach ($shop->investors as $investor)
                                    <tr>
                                        <td class="d-flex justify-content-between">
                                            <span>
                                                <span
                                                    style="width: 20px; display: inline-block">{{ $loop->iteration }}.</span>
                                                <span>{{ $investor->user->name }}</span>
                                            </span>
                                            <span class="ml-1">
                                                <span class="number-int">{{ $investor->pivot->persentase }}</span>%
                                            </span>
                                        </td>

                                        <td width="20" class="px-2">=</td>
                                        <td width="150">
                                            <div
                                                class="@if ($loop->last) line-bottom @endif d-flex justify-content-between">
                                                <span>Rp</span>
                                                <span
                                                    class="number-int">{{ ($report['laba_bersih_dibagi'] * $investor->pivot->persentase) / 100 }}</span>
                                            </div>

                                        </td>
                                        <td width="20" class="px-2">
                                            @if ($loop->last)
                                                +
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="text-right"></td>
                                    <td width="20" class="px-2"></td>
                                    <td width="150">
                                        <div class="d-flex justify-content-between">
                                            <span>Rp</span>
                                            <span class="number-int">{{ $report['laba_bersih_dibagi'] }}</span>
                                        </div>

                                    </td>
                                </tr>
                            </table>
                            <br>
                            <table style="width: 100%">
                                <tr class="font-weight-bold">
                                    <td>Catatan</td>
                                </tr>
                                <tr>Bila disetujui maka laba akan segera ditransferkan ke rekening</tr>
                                @foreach ($shop->investors as $investor)
                                    <tr>
                                        <td class="d-flex justify-content-between">
                                            <span>
                                                <span
                                                    style="width: 20px; display: inline-block">{{ $loop->iteration }}.</span>
                                                <span>{{ $investor->nama_bank }} {{ $investor->no_rekening }} a/n
                                                    {{ $investor->atas_nama_rekening }}</span>
                                            </span>
                                        </td>

                                        <td width="150">
                                            <div class="d-flex justify-content-between">
                                                <span>Rp</span>
                                                <span
                                                    class="number-int">{{ ($report['laba_bersih_dibagi'] * $investor->pivot->persentase) / 100 }}</span>
                                            </div>

                                        </td>

                                    </tr>
                                @endforeach
                            </table>

                        </div>
                        <br>
                        <br>
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
    <div class="modal fade" id="modal_laba_kotor">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Alokasi Modal Dasar</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="form_laba_kotor"
                    action="{{ route('laba-bersih.alokasi-modal', ['shop_id' => $shop->id, 'year_month' => $date->format('Y-m')]) }}"
                    method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <div class="input-group">

                                <input type="number" class="form-control" id="persentase"
                                    name="persentase_alokasi_modal"
                                    value="{{ old('persentase_alokasi_modal', $report['persentase_alokasi_modal']) }}"
                                    required>
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>

        </div>

    </div>
@endsection


@push('style')
    <style>
        .dot-fill {
            width: 200,
                overflow: hidden;
            white-space: nowrap;
        }

        .dot-fill:after {
            content: "......................................................................................................................................................................................................"
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

@push('script')
    <script>
        $(document).ready(function() {
            $('.btn-alokasi').on('click', function() {
                $('#modal_laba_kotor').modal('show');
            });
        })
    </script>
@endpush
