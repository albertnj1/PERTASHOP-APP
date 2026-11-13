@extends('layouts._new_admin')
@section('title', 'Aturan Bisnis (Business Rules)')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Aturan Bisnis (Business Rules)</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Kelola aturan operasional dan histori versi parameter sistem</p>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 10px;">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif

@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius: 10px;">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif

<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="thead-light">
          <tr>
            <th>Kode Aturan</th>
            <th>Nama & Deskripsi</th>
            <th>Tipe</th>
            <th>Nilai Aktif</th>
            <th>Mulai Berlaku</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rules as $rule)
            @php
              $activeVersion = $rule->versions->firstWhere('is_active', true) ?? $rule->versions->first();
            @endphp
            <tr>
              <td><code>{{ $rule->code }}</code></td>
              <td>
                <strong>{{ $rule->name }}</strong>
                @if($rule->description)
                  <br><small class="text-muted">{{ $rule->description }}</small>
                @endif
              </td>
              <td><span class="badge badge-secondary">{{ $rule->rule_type ?? 'numeric' }}</span></td>
              <td>
                <span class="badge badge-primary font-weight-bold" style="font-size: 13px;">
                  {{ $activeVersion?->value_numeric ?? ($activeVersion?->value_json ? json_encode($activeVersion->value_json) : '-') }}
                </span>
              </td>
              <td>
                {{ $activeVersion?->effective_from ? \Carbon\Carbon::parse($activeVersion->effective_from)->isoFormat('D MMM YYYY') : '-' }}
              </td>
              <td>
                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="collapse" data-target="#history-{{ $rule->id }}">
                  <i class="fa fa-history mr-1"></i> Histori ({{ $rule->versions->count() }})
                </button>
              </td>
            </tr>
            <tr class="collapse" id="history-{{ $rule->id }}">
              <td colspan="6" class="p-3 bg-light">
                <div class="mb-3">
                  <strong>Histori Versi Aturan: {{ $rule->name }}</strong>
                </div>
                <table class="table table-sm table-bordered bg-white mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>Versi</th>
                      <th>Nilai</th>
                      <th>Mulai Berlaku</th>
                      <th>Pembuat</th>
                      <th>Alasan Perubahan</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($rule->versions as $v)
                      <tr>
                        <td>v{{ $v->version_number }}</td>
                        <td>{{ $v->value_numeric ?? json_encode($v->value_json) }}</td>
                        <td>{{ \Carbon\Carbon::parse($v->effective_from)->format('d/m/Y') }}</td>
                        <td>{{ $v->creator?->name ?? 'System' }}</td>
                        <td>{{ $v->change_reason ?? '-' }}</td>
                        <td>
                          @if($v->is_active)
                            <span class="badge badge-success">Aktif</span>
                          @else
                            <span class="badge badge-light">Arsip</span>
                          @endif
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada versi aturan tercatat.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">Belum ada aturan bisnis terdaftar di sistem.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

