@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Laporan Excel: {{ $upload->nama_file }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('excel-imports.index') }}">Import Excel</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Summary Info -->
            <div class="card card-info card-outline">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong><i class="fa fa-store mr-1"></i> Outlet:</strong>
                            <p class="text-muted">{{ $upload->shop->nama }} ({{ $upload->shop->kode }})</p>
                        </div>
                        <div class="col-md-3">
                            <strong><i class="fa fa-calendar mr-1"></i> Periode:</strong>
                            <p class="text-muted">{{ $upload->periode }}</p>
                        </div>
                        <div class="col-md-3">
                            <strong><i class="fa fa-info-circle mr-1"></i> Parameter Awal:</strong>
                            <p class="text-muted">
                                Tot Awal: {{ number_format($upload->initial_totalisator, 3, ',', '.') }} L<br>
                                Stok Awal: {{ number_format($upload->initial_stock, 1, ',', '.') }} L<br>
                                Saldo Awal Yusuf: {{ number_format($upload->initial_balance, 0, ',', '.') }} Rp
                            </p>
                        </div>
                        <div class="col-md-3">
                            <strong><i class="fa fa-history mr-1"></i> Log Perubahan Terakhir:</strong>
                            @if ($upload->changeLogs->count() > 0)
                                <p class="text-muted text-sm">
                                    {{ $upload->changeLogs->last()->created_at->format('d M H:i') }} -
                                    Kolom <code>{{ $upload->changeLogs->last()->field }}</code> diubah oleh
                                    {{ $upload->changeLogs->last()->user->name }}
                                </p>
                            @else
                                <p class="text-muted text-sm">Belum ada pengeditan data.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabbed Excel Structure -->
            <div class="card card-primary card-tabs">
                <div class="card-header p-0 pt-1">
                    <ul class="nav nav-tabs" id="excelTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="operasional-tab" data-toggle="pill" href="#operasional" role="tab" aria-controls="operasional" aria-selected="true">
                                <i class="fa fa-list mr-1"></i> Laporan Operasional Harian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="setoran-tab" data-toggle="pill" href="#setoran" role="tab" aria-controls="setoran" aria-selected="false">
                                <i class="fa fa-wallet mr-1"></i> Setoran & Saldo Akumulasi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="rekap-tab" data-toggle="pill" href="#rekap" role="tab" aria-controls="rekap" aria-selected="false">
                                <i class="fa fa-calculator mr-1"></i> Rekap Bulanan (Laba Rugi)
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="excelTabContent">
                        
                        <!-- TAB 1: OPERASIONAL HARIAN -->
                        <div class="tab-pane fade show active" id="operasional" role="tabpanel" aria-labelledby="operasional-tab">
                            <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
                                <table class="table table-bordered table-head-fixed text-nowrap text-sm text-center">
                                    <thead style="background-color: #e2f0d9; color: #385723;">
                                        <tr>
                                            <th>TANGGAL</th>
                                            <th>OPERATOR</th>
                                            <th>TOTALISATOR AWAL</th>
                                            <th>TOTALISATOR AKHIR <i class="fa fa-edit text-xs"></i></th>
                                            <th>PENJUALAN LITER</th>
                                            <th>TEST PUMP <i class="fa fa-edit text-xs"></i></th>
                                            <th>CURAH <i class="fa fa-edit text-xs"></i></th>
                                            <th>STIK MALAM <i class="fa fa-edit text-xs"></i></th>
                                            <th>STOK AKTUAL</th>
                                            <th>GAIN/LOSS</th>
                                            <th>PENGELUARAN <i class="fa fa-edit text-xs"></i></th>
                                            <th>KETERANGAN <i class="fa fa-edit text-xs"></i></th>
                                            <th>PENDAPATAN OPERATOR</th>
                                            <th>DAILY GROSS PROFIT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $total_liter = 0;
                                            $total_pengeluaran = 0;
                                            $total_pendapatan = 0;
                                            $total_profit = 0;
                                        @endphp
                                        @foreach ($calculatedRows as $row)
                                            @php
                                                $isFirstInDay = $row->daily_total_liter !== null;
                                                if ($isFirstInDay) {
                                                    $total_liter += $row->daily_total_liter;
                                                    $total_pengeluaran += $row->computed_pendapatan - $row->computed_rupiah_jual; // aggregate later
                                                    $total_profit += $row->daily_gross_profit;
                                                }
                                                $total_pendapatan += $row->computed_pendapatan;
                                            @endphp
                                            <tr style="{{ $isFirstInDay ? 'border-top: 2px solid #548235;' : '' }}">
                                                <td class="font-weight-bold" style="background-color: #f2f2f2;">
                                                    {{ $isFirstInDay ? date('d-m-Y', strtotime($row->tanggal)) : '' }}
                                                </td>
                                                <td>{{ $row->excel_operator_name }}</td>
                                                <td style="background-color: #fafafa;">{{ number_format($row->computed_tot_awal, 3, ',', '.') }}</td>
                                                
                                                <!-- Editable Totalisator Akhir -->
                                                <td class="editable-cell text-primary font-weight-bold" data-id="{{ $row->id }}" data-field="totalisator_akhir">
                                                    {{ number_format($row->totalisator_akhir, 3, ',', '.') }}
                                                </td>
                                                
                                                <td class="font-weight-bold">{{ number_format($row->computed_liter_terjual, 3, ',', '.') }}</td>
                                                
                                                <!-- Editable Test Pump -->
                                                <td class="editable-cell text-primary" data-id="{{ $row->id }}" data-field="test_pump">
                                                    {{ $row->test_pump > 0 ? number_format($row->test_pump, 1, ',', '.') : '-' }}
                                                </td>
                                                
                                                <!-- Editable Curah (Incoming) -->
                                                <td class="editable-cell text-primary" data-id="{{ $row->id }}" data-field="curah">
                                                    {{ $row->curah > 0 ? number_format($row->curah, 1, ',', '.') : '-' }}
                                                </td>
                                                
                                                <!-- Editable Stik Malam -->
                                                <td class="editable-cell text-primary font-weight-bold" data-id="{{ $row->id }}" data-field="stik_malam">
                                                    {{ $row->stik_malam !== null ? number_format($row->stik_malam, 1, ',', '.') : '-' }}
                                                </td>
                                                
                                                <td>{{ number_format($row->computed_stok_akhir, 1, ',', '.') }}</td>
                                                
                                                <td class="{{ $row->computed_gain_loss < 0 ? 'text-danger' : 'text-success' }} font-weight-bold">
                                                    {{ $row->stik_malam !== null ? number_format($row->computed_gain_loss, 2, ',', '.') : '-' }}
                                                </td>
                                                
                                                <!-- Editable Pengeluaran -->
                                                <td class="editable-cell text-primary text-right" data-id="{{ $row->id }}" data-field="pengeluaran">
                                                    {{ $row->pengeluaran > 0 ? number_format($row->pengeluaran, 0, ',', '.') : '-' }}
                                                </td>
                                                
                                                <!-- Editable Keterangan Pengeluaran -->
                                                <td class="editable-cell text-primary text-left" data-id="{{ $row->id }}" data-field="keterangan_pengeluaran">
                                                    {{ $row->keterangan_pengeluaran ?: '-' }}
                                                </td>
                                                
                                                <td class="text-right">{{ number_format($row->computed_pendapatan, 0, ',', '.') }}</td>
                                                
                                                @if ($isFirstInDay)
                                                    <td rowspan="3" class="align-middle font-weight-bold text-right" style="background-color: #fff2cc;">
                                                        {{ number_format($row->daily_gross_profit, 0, ',', '.') }}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background-color: #ffd966; font-weight: bold; border-top: 3px double #333;">
                                            <td colspan="4">JUMLAH TOTAL</td>
                                            <td>{{ number_format($total_liter, 3, ',', '.') }} L</td>
                                            <td colspan="7"></td>
                                            <td class="text-right">{{ number_format($total_pendapatan, 0, ',', '.') }} Rp</td>
                                            <td class="text-right" style="background-color: #ffc000;">{{ number_format($total_profit, 0, ',', '.') }} Rp</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 2: SETORAN & SALDO AKUMULASI -->
                        <div class="tab-pane fade" id="setoran" role="tabpanel" aria-labelledby="setoran-tab">
                            <div class="table-responsive">
                                <table class="table table-bordered text-center text-sm text-nowrap">
                                    <thead style="background-color: #d9e1f2; color: #1f497d;">
                                        <tr>
                                            <th>TANGGAL</th>
                                            <th>OPERATOR</th>
                                            <th>SETORAN OPERATOR (YUSUF)</th>
                                            <th>KOREKSI SETORAN (ADJUSTMENT) <i class="fa fa-edit text-xs"></i></th>
                                            <th>PEMBAYARAN QRIS <i class="fa fa-edit text-xs"></i></th>
                                            <th>DISETOR KE SINERGY</th>
                                            <th>SALDO BELUM DISETOR (KUMULATIF)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $total_setoran = 0;
                                            $total_qris = 0;
                                            $total_sinergy = 0;
                                        @endphp
                                        @foreach ($calculatedRows as $row)
                                            @php
                                                $isFirstInDay = $row->daily_total_liter !== null;
                                                $total_setoran += $row->computed_setoran_yusuf;
                                                $total_qris += $row->qris;
                                                $total_sinergy += $row->computed_disetor_sinergy;
                                            @endphp
                                            <tr style="{{ $isFirstInDay ? 'border-top: 2px solid #2f5597;' : '' }}">
                                                <td class="font-weight-bold" style="background-color: #f2f2f2;">
                                                    {{ $isFirstInDay ? date('d-m-Y', strtotime($row->tanggal)) : '' }}
                                                </td>
                                                <td>{{ $row->excel_operator_name }}</td>
                                                <td class="text-right">{{ number_format($row->computed_setoran_yusuf, 0, ',', '.') }}</td>
                                                
                                                <!-- Editable Setoran Adjustment -->
                                                <td class="editable-cell text-primary text-right" data-id="{{ $row->id }}" data-field="setoran_adjustment">
                                                    {{ $row->setoran_adjustment != 0 ? number_format($row->setoran_adjustment, 0, ',', '.') : '-' }}
                                                </td>
                                                
                                                <!-- Editable QRIS -->
                                                <td class="editable-cell text-primary text-right" data-id="{{ $row->id }}" data-field="qris">
                                                    {{ $row->qris > 0 ? number_format($row->qris, 0, ',', '.') : '-' }}
                                                </td>
                                                
                                                <td class="text-right text-success font-weight-bold">
                                                    {{ $row->computed_disetor_sinergy > 0 ? number_format($row->computed_disetor_sinergy, 0, ',', '.') : '-' }}
                                                </td>
                                                
                                                <td class="text-right font-weight-bold {{ $row->computed_belum_disetor < 0 ? 'text-danger' : 'text-dark' }}">
                                                    {{ number_format($row->computed_belum_disetor, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background-color: #ffd966; font-weight: bold; border-top: 3px double #333;">
                                            <td colspan="2">JUMLAH TOTAL</td>
                                            <td class="text-right">{{ number_format($total_setoran, 0, ',', '.') }} Rp</td>
                                            <td></td>
                                            <td class="text-right">{{ number_format($total_qris, 0, ',', '.') }} Rp</td>
                                            <td class="text-right" style="background-color: #a9d08e;">{{ number_format($total_sinergy, 0, ',', '.') }} Rp</td>
                                            <td class="text-right" style="background-color: #f4b084;">{{ number_format($calculatedRows->last()->computed_belum_disetor, 0, ',', '.') }} Rp</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: REKAP BULANAN -->
                        <div class="tab-pane fade p-3" id="rekap" role="tabpanel" aria-labelledby="rekap-tab">
                            @if ($upload->rekap)
                                <div class="row">
                                    <!-- Laba Rugi Card -->
                                    <div class="col-md-6">
                                        <div class="card card-success card-outline">
                                            <div class="card-header">
                                                <h3 class="card-title font-weight-bold">Laporan Laba Rugi Komparatif</h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-striped table-bordered text-sm">
                                                    <thead>
                                                        <tr class="bg-success text-white">
                                                            <th>Komponen Pengeluaran & Hasil</th>
                                                            <th class="text-right">Nominal (Rupiah)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($dynamicRekap['detail_pengeluaran_rutin'] as $item)
                                                            <tr>
                                                                <td>{{ $item['label'] }}</td>
                                                                <td class="text-right font-weight-bold">{{ number_format($item['rupiah'], 0, ',', '.') }} Rp</td>
                                                            </tr>
                                                        @endforeach
                                                        <tr class="bg-light font-weight-bold">
                                                            <td>Total Pengeluaran Gaji & Admin</td>
                                                            <td class="text-right text-danger">
                                                                {{ number_format($dynamicRekap['pengeluaran_rutin_total'], 0, ',', '.') }} Rp
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- DO Pertamax Card -->
                                    <div class="col-md-6">
                                        <div class="card card-warning card-outline">
                                            <div class="card-header">
                                                <h3 class="card-title font-weight-bold">Modal Pembelian DO Pertamax</h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-bordered text-sm">
                                                    <thead>
                                                        <tr class="bg-warning text-dark font-weight-bold">
                                                            <th>Item Pembelian</th>
                                                            <th class="text-right">Volume (L)</th>
                                                            <th class="text-right">Rupiah</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($upload->rekap->detail_do as $do)
                                                            <tr>
                                                                <td>{{ $do['label'] }}</td>
                                                                <td class="text-right">{{ number_format($do['liter'], 1, ',', '.') }}</td>
                                                                <td class="text-right font-weight-bold">{{ number_format($do['rupiah'], 0, ',', '.') }} Rp</td>
                                                            </tr>
                                                        @endforeach
                                                        <tr class="bg-light font-weight-bold">
                                                            <td>Total Laba Kotor Harian</td>
                                                            <td class="text-right text-primary" colspan="2">{{ number_format($dynamicRekap['total_gross_profit'], 0, ',', '.') }} Rp</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Investor Profit Sharing Card -->
                                <div class="card card-info card-outline mt-3">
                                    <div class="card-header">
                                        <h3 class="card-title font-weight-bold">Pembagian Hasil Keuntungan Investor</h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-bordered text-sm">
                                            <thead>
                                                <tr class="bg-info text-white">
                                                    <th>Nama Investor / Pihak</th>
                                                    <th class="text-right">Nominal Laba Bersih (Rp)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($dynamicRekap['detail_pembagian_hasil'] as $hasil)
                                                    <tr>
                                                        <td>{{ $hasil['label'] }} ({{ $hasil['percentage'] }})</td>
                                                        <td class="text-right font-weight-bold text-success">{{ number_format($hasil['rupiah'], 0, ',', '.') }} Rp</td>
                                                    </tr>
                                                @endforeach
                                                <tr class="bg-light font-weight-bold">
                                                    <td>LABA BERSIH TOTAL</td>
                                                    <td class="text-right text-primary">{{ number_format($dynamicRekap['laba_bersih'], 0, ',', '.') }} Rp</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <p class="text-center text-muted">Data rekap bulanan tidak tersedia.</p>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cell Edit Script -->
    @push('style')
        <style>
            .editable-cell {
                cursor: pointer;
                transition: background-color 0.2s;
            }
            .editable-cell:hover {
                background-color: #e8f0fe !important;
            }
            .editing-input {
                width: 100%;
                box-sizing: border-box;
                text-align: center;
                border: 2px solid #007bff;
                border-radius: 4px;
            }
        </style>
    @endpush

    <script>
        $(document).ready(function() {
            $('.editable-cell').on('dblclick', function() {
                if ($(this).find('input').length > 0) return;

                const cell = $(this);
                const id = cell.data('id');
                const field = cell.data('field');
                const originalText = cell.text().trim();
                
                // Get clean numeric value or keep text
                let cleanVal = originalText.replace(/\./g, '').replace(/,/g, '.').replace(/ Rp/g, '').replace(/ L/g, '');
                if (cleanVal === '-') cleanVal = '';

                const input = $('<input type="text" class="editing-input">').val(cleanVal);
                cell.html(input);
                input.focus();

                input.on('blur keyup', function(e) {
                    if (e.type === 'keyup' && e.key !== 'Enter') return;

                    const newVal = input.val().trim();
                    if (newVal === cleanVal) {
                        cell.text(originalText);
                        return;
                    }

                    // Send AJAX Update
                    $.ajax({
                        url: "{{ route('excel-imports.update-cell', $upload->id) }}",
                        method: "POST",
                        data: {
                            row_id: id,
                            field: field,
                            value: newVal
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Data Berhasil Diupdate!',
                                showConfirmButton: false,
                                timer: 1200
                            }).then(() => {
                                // Force page reload to trigger server recalculations
                                window.location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Update Data',
                                text: xhr.responseJSON.error || 'Terjadi kesalahan sistem.'
                            });
                            cell.text(originalText);
                        }
                    });
                });
            });
        });
    </script>
@endsection
