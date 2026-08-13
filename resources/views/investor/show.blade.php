@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detail Investor</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('investors.export-pdf', $investor->id) }}" class="btn btn-danger mr-1"><i class="fas fa-file-pdf"></i> Unduh PDF</a>
                <a href="{{ route('investors.edit', $investor->id) }}" class="btn btn-info mr-1"><i class="fas fa-edit"></i> Edit</a>
                <button class="btn btn-danger btn-delete" data-id="{{ $investor->id }}"><i class="fas fa-trash"></i> Hapus</button>
                <a href="{{ route('investors.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <!-- Profile Image -->
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle"
                                src="https://ui-avatars.com/api/?name={{ str_replace(' ', '+', $investor->user->name) }}&background=C89B3C&color=fff"
                                alt="User profile picture">
                        </div>

                        <h3 class="profile-username text-center">{{ $investor->user->name }}</h3>
                        <p class="text-muted text-center">{{ $investor->nama_lengkap_gelar }}</p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>No. HP</b> <a class="float-right">{{ $investor->no_hp ?? '-' }}</a>
                            </li>
                            @if($investor->nik)
                            <li class="list-group-item">
                                <b>NIK</b> <a class="float-right">{{ $investor->nik }}</a>
                            </li>
                            @endif
                            <li class="list-group-item">
                                <b>Bank</b> <a class="float-right">{{ $investor->nama_bank }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>No. Rekening</b> <a class="float-right">{{ $investor->no_rekening }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Atas Nama</b> <a class="float-right">{{ $investor->atas_nama_rekening }}</a>
                            </li>
                            <li class="list-group-item" style="background-color: #f1f5f9;">
                                <b style="font-size: 16px;">Total Nilai Saham</b> <a class="float-right" style="font-size: 16px; font-weight: bold; color: #2C4643;">Rp. {{ number_format($totalInvestasi, 0, ',', '.') }}</a>
                            </li>
                            @if(Auth::user()->role === 'super-admin')
                            <li class="list-group-item">
                                <b>Email Login (Sistem)</b> <a class="float-right">{{ $investor->user->email ?? '' }}</a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #2C4643; color: #fff;">
                        <h3 class="card-title m-0" style="color: #fff !important;"><i class="fas fa-store mr-2"></i> Portofolio Investasi Pertashop</h3>
                        <button type="button" class="btn btn-sm btn-light" data-toggle="modal" data-target="#modal-tambah-investasi">
                            <i class="fas fa-plus"></i> Tambah Investasi
                        </button>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Pertashop</th>
                                    <th>Persentase Saham</th>
                                    <th>Nominal Investasi</th>
                                    <th>Status / Info Tambahan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($investor->shops as $shop)
                                <tr>
                                    <td>{{ $shop->kode }}</td>
                                    <td>{{ $shop->nama }}</td>
                                    <td>
                                        <span class="badge badge-success-premium" style="font-size: 14px;">{{ $shop->pivot->persentase }}%</span>
                                    </td>
                                    <td style="font-weight: bold;">Rp. {{ number_format($shop->pivot->nominal, 0, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $sub = json_decode($shop->pivot->sub_investors, true);
                                        @endphp
                                        @if(isset($sub['is_hibah']) && $sub['is_hibah'])
                                            <span class="badge badge-warning">Saham Hibah</span>
                                        @endif
                                        @if(isset($sub['sub_name']) && $sub['sub_name'])
                                            <span class="badge badge-info">Sub: {{ $sub['sub_name'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada investasi di Pertashop manapun.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Tambah Investasi -->
<div class="modal fade" id="modal-tambah-investasi" tabindex="-1" role="dialog" aria-labelledby="modalTambahInvestasiLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('investors.add-investment', $investor->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahInvestasiLabel">Tambah Investasi Pertashop</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="shop_id">Pertashop</label>
                        <select name="shop_id" id="shop_id" class="form-control" required>
                            <option value="">-- Pilih Pertashop --</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}">{{ $shop->kode }} - {{ $shop->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nominal">Nominal Investasi (Rupiah)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" name="nominal" id="nominal" class="form-control" required min="1">
                        </div>
                        <small class="text-muted">Persentase saham akan dihitung otomatis: (Nominal / Modal Tetap Pertashop) * 100.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Investasi</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script')
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.btn-delete').on('click', function() {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data investor akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('investors.index') }}" + "/" + id,
                        success: function(response) {
                            Swal.fire(
                                'Terhapus!',
                                response.message,
                                'success'
                            ).then(() => {
                                window.location.href = "{{ route('investors.index') }}";
                            });
                        },
                        error: function(err) {
                            Swal.fire('Error', 'Gagal menghapus data.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
