@extends('layouts.app')
@section('title', 'Assign Operator ke Penggajian')

@section('content')
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">👤 Assign Operator ke Sistem Penggajian</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a href="{{ route('payroll-operator-assignments.create') }}" class="btn btn-success btn-sm">
          <i class="fas fa-plus"></i> Tambah Assignment
        </a>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif

    {{-- Filter --}}
    @if(Auth::user()->role === 'super-admin')
    <div class="card mb-3">
      <div class="card-body py-2">
        <form method="GET" class="form-inline">
          <label class="mr-2 font-weight-bold">Filter Toko:</label>
          <select name="shop_id" class="form-control form-control-sm" onchange="this.form.submit()">
            <option value="">— Semua —</option>
            @foreach($shops as $shop)
              <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
            @endforeach
          </select>
        </form>
      </div>
    </div>
    @endif

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Daftar Assignment Aktif</h5>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Operator</th>
              <th>Toko</th>
              <th>Sistem Penggajian</th>
              <th>Mulai Berlaku</th>
              <th>Selesai</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($assignments as $i => $a)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td><strong>{{ $a->operator->user?->name ?? '-' }}</strong></td>
              <td><span class="badge badge-secondary">{{ $a->shop->nama }}</span></td>
              <td>{{ $a->payrollSystem->nama_sistem }}</td>
              <td>{{ \Carbon\Carbon::parse($a->tanggal_mulai)->format('d M Y') }}</td>
              <td>
                @if($a->tanggal_selesai)
                  {{ \Carbon\Carbon::parse($a->tanggal_selesai)->format('d M Y') }}
                @else
                  <span class="badge badge-success">Aktif</span>
                @endif
              </td>
              <td>
                @if(is_null($a->tanggal_selesai) || $a->tanggal_selesai >= now()->toDateString())
                  <span class="badge badge-success">Aktif</span>
                @else
                  <span class="badge badge-secondary">Selesai</span>
                @endif
              </td>
              <td>
                <button class="btn-action-modern btn-delete-modern btn-delete-assign"
                  data-id="{{ $a->id }}" title="Hapus">
                  <i class="fa fa-trash"></i>
                </button>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                Belum ada assignment. <a href="{{ route('payroll-operator-assignments.create') }}">Tambah sekarang</a>.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
@endsection

@push('script')
<script>
document.querySelectorAll('.btn-delete-assign').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.dataset.id;
    Swal.fire({
      title: 'Hapus Assignment?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonText: 'Batal',
      confirmButtonText: 'Hapus'
    }).then(result => {
      if (result.isConfirmed) {
        fetch(`/payroll-operator-assignments/${id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        }).then(() => location.reload());
      }
    });
  });
});
</script>
@endpush
