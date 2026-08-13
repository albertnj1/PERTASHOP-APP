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
        <a href="{{ route('payroll-operator-assignments.index') }}" class="btn btn-secondary btn-sm">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Form Assign Operator</h5>
          </div>
          <div class="card-body">

            @if($errors->any())
              <div class="alert alert-danger">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
              </div>
            @endif

            <form action="{{ route('payroll-operator-assignments.store') }}" method="POST">
              @csrf

              <div class="form-group">
                <label class="font-weight-bold">Pertashop <span class="text-danger">*</span></label>
                <select name="shop_id" id="shop_id" class="form-control" required onchange="loadDependents(this.value)">
                  <option value="">— Pilih Toko —</option>
                  @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group">
                <label class="font-weight-bold">Operator <span class="text-danger">*</span></label>
                <select name="operator_id" id="operator_id" class="form-control" required>
                  <option value="">— Pilih Toko dulu —</option>
                  @foreach($operators as $op)
                    <option value="{{ $op->id }}" {{ old('operator_id') == $op->id ? 'selected' : '' }}>{{ $op->user?->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-group">
                <label class="font-weight-bold">Sistem Penggajian <span class="text-danger">*</span></label>
                <select name="payroll_system_id" id="payroll_system_id" class="form-control" required>
                  <option value="">— Pilih Toko dulu —</option>
                  @foreach($systems as $sys)
                    <option value="{{ $sys->id }}" {{ old('payroll_system_id') == $sys->id ? 'selected' : '' }}>{{ $sys->nama_sistem }}</option>
                  @endforeach
                </select>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">Tanggal Mulai Berlaku <span class="text-danger">*</span></label>
                  <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai', date('Y-m-01')) }}" required>
                </div>
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">Tanggal Selesai</label>
                  <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai') }}">
                  <small class="form-text text-muted">Kosongkan jika masih aktif.</small>
                </div>
              </div>

              <div class="mt-3">
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-save"></i> Simpan Assignment
                </button>
                <a href="{{ route('payroll-operator-assignments.index') }}" class="btn btn-secondary ml-2">Batal</a>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('script')
<script>
function loadDependents(shopId) {
  const opSelect  = document.getElementById('operator_id');
  const sysSelect = document.getElementById('payroll_system_id');

  if (!shopId) {
    opSelect.innerHTML  = '<option value="">— Pilih Toko dulu —</option>';
    sysSelect.innerHTML = '<option value="">— Pilih Toko dulu —</option>';
    return;
  }

  // Load operators
  fetch(`/payroll-operator-assignments/operators-by-shop/${shopId}`)
    .then(r => r.json())
    .then(data => {
      opSelect.innerHTML = '<option value="">— Pilih Operator —</option>';
      data.forEach(op => {
        opSelect.innerHTML += `<option value="${op.id}">${op.name}</option>`;
      });
    });

  // Load payroll systems
  fetch(`/payroll-systems/by-shop/${shopId}`)
    .then(r => r.json())
    .then(data => {
      sysSelect.innerHTML = '<option value="">— Pilih Sistem Penggajian —</option>';
      data.forEach(sys => {
        sysSelect.innerHTML += `<option value="${sys.id}">${sys.nama_sistem}</option>`;
      });
    });
}

// Trigger on load if old value exists
const shopIdEl = document.getElementById('shop_id');
if (shopIdEl.value) loadDependents(shopIdEl.value);
</script>
@endpush
