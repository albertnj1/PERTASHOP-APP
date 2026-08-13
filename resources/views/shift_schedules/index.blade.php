@extends('layouts._new_admin')
@section('title', 'Jadwal Shift Operator')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="page-title mb-1">Jadwal Shift Operator</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Pengaturan kalender shift dan rekapitulasi kehadiran operator</p>
  </div>
</div>

@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 10px; border: none; background: #dcfce7; color: #15803d;">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif
@if ($errors->any())
  <div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius: 10px; border: none; background: #fee2e2; color: #b91c1c;">
    @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif

{{-- ======== FILTER ======== --}}
<div class="panel mb-4" style="padding: 12px 18px;">
  <form method="GET" action="{{ route('shift-schedules.index') }}" class="d-flex align-items-center gap-3">
    <div class="d-flex align-items-center mr-3">
      <label class="text-muted mb-0 mr-2" style="font-size: 12px; font-weight: 600;">Pertashop:</label>
      <select name="shop_id" class="form-control form-control-sm" style="border-radius: 8px;" onchange="this.form.submit()">
        @foreach ($shops as $shop)
          <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>{{ $shop->nama }}</option>
        @endforeach
      </select>
    </div>
    <div class="d-flex align-items-center">
      <label class="text-muted mb-0 mr-2" style="font-size: 12px; font-weight: 600;">Bulan:</label>
      <input type="month" name="bulan" class="form-control form-control-sm" style="border-radius: 8px;" value="{{ $selectedMonth }}" onchange="this.form.submit()">
    </div>
  </form>
</div>

<div class="row cols-2-1">
  {{-- ======== TABEL KALENDER SHIFT ======== --}}
  <div>
    <div class="panel mb-4">
      <div class="panel-head">
        <div class="panel-title">Kalender Shift — {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0" style="min-width:700px; font-size:0.8rem;">
          <thead>
            <tr style="background: var(--page-bg);">
              <th style="min-width:120px; color: var(--muted); font-size: 11px; text-transform: uppercase;">Operator</th>
              @foreach ($days as $day)
                <th class="text-center" style="min-width:38px; color: var(--muted); font-size: 11px;" title="{{ $day->translatedFormat('l') }}">
                  {{ $day->format('d') }}
                  <div style="font-size:0.6rem; opacity:0.7;">{{ $day->format('D') }}</div>
                </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @forelse ($operators as $operator)
            <tr>
              <td style="font-size: 13px; font-weight: 600;">{{ $operator->user->short_name ?? $operator->user->name }}</td>
              @foreach ($days as $day)
                @php
                  $dateStr = $day->format('Y-m-d');
                  $daySchedules = $schedules->get($dateStr, collect())
                    ->where('operator_id', $operator->id);
                @endphp
                <td class="text-center p-0" style="vertical-align:middle;">
                  @if ($daySchedules->isEmpty())
                    <span style="color:#ccc;">–</span>
                  @else
                    @foreach ($daySchedules as $s)
                      @php
                        $badgeColor = match($s->status) {
                          'hadir'       => '#2f8f52',
                          'alpha'       => '#d1453d',
                          'izin'        => '#e0a032',
                          'sakit'       => '#3b6ea5',
                          default       => '#767f76',
                        };
                        $badgeTitle = "Shift {$s->shift_ke} — {$s->status}";
                      @endphp
                      <span class="status-pill" style="background:{{ $badgeColor }}; color:#fff; font-size:0.65rem; padding: 2px 5px; cursor:pointer;"
                        title="{{ $badgeTitle }}"
                        onclick="openStatusModal({{ $s->id }}, '{{ $s->status }}', '{{ $s->keterangan }}')">
                        {{ $s->shift_ke }}
                      </span>
                    @endforeach
                  @endif
                </td>
              @endforeach
            </tr>
            @empty
            <tr><td colspan="{{ count($days) + 1 }}" class="text-center text-muted py-4">Belum ada operator.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="px-3 py-2 border-top text-muted" style="font-size: 11px; background: var(--page-bg);">
        Status: <span class="status-pill mr-1" style="background:#2f8f52; color:#fff; padding: 1px 6px;">Hadir</span>
        <span class="status-pill mr-1" style="background:#d1453d; color:#fff; padding: 1px 6px;">Alpha</span>
        <span class="status-pill mr-1" style="background:#e0a032; color:#fff; padding: 1px 6px;">Izin</span>
        <span class="status-pill mr-1" style="background:#3b6ea5; color:#fff; padding: 1px 6px;">Sakit</span> | Angka = nomor shift
      </div>
    </div>

    {{-- ======== REKAP KEHADIRAN ======== --}}
    <div class="panel mb-0">
      <div class="panel-head">
        <div class="panel-title">Rekap Kehadiran Bulan Ini</div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr style="background: var(--page-bg);">
              <th style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Operator</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Dijadwalkan</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Hadir</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Alpha</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Izin</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Sakit</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">% Hadir</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($operators as $op)
              @php $recap = $attendanceRecaps->get($op->id); @endphp
              <tr>
                <td style="font-size: 13px; font-weight: 600;">{{ $op->user->name ?? '-' }}</td>
                <td class="text-center" style="font-size: 13px;">{{ $recap->total_dijadwalkan ?? 0 }}</td>
                <td class="text-center font-weight-bold" style="font-size: 13px; color: var(--green);">{{ $recap->total_hadir ?? 0 }}</td>
                <td class="text-center" style="font-size: 13px; color: var(--red);">{{ $recap->total_alpha ?? 0 }}</td>
                <td class="text-center" style="font-size: 13px; color: var(--amber);">{{ $recap->total_izin ?? 0 }}</td>
                <td class="text-center" style="font-size: 13px; color: var(--blue);">{{ $recap->total_sakit ?? 0 }}</td>
                <td class="text-center" style="font-size: 13px;">
                  @if (($recap->total_dijadwalkan ?? 0) > 0)
                    {{ round(($recap->total_hadir ?? 0) / $recap->total_dijadwalkan * 100, 1) }}%
                  @else
                    <span class="text-muted">–</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-3" style="font-size: 13px;">Belum ada data.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ======== PANEL KANAN: TAMBAH JADWAL ======== --}}
  <div>
    {{-- Form Tambah Single --}}
    <div class="panel mb-4">
      <div class="panel-head">
        <div class="panel-title">Tambah Jadwal</div>
      </div>
      <form method="POST" action="{{ route('shift-schedules.store') }}">
        @csrf
        <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
        <div class="form-group mb-3">
          <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Operator</label>
          <select name="operator_id" class="form-control" style="border-radius: 8px;" required>
            <option value="">-- Pilih --</option>
            @foreach ($operators as $op)
              <option value="{{ $op->id }}">{{ $op->user->name ?? $op->id }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group mb-3">
          <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Tanggal</label>
          <input type="date" name="tanggal" class="form-control" style="border-radius: 8px;" required value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="form-group mb-3">
          <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Shift ke-</label>
          <select name="shift_ke" class="form-control" style="border-radius: 8px;" required>
            <option value="1">1 (Pagi)</option>
            <option value="2">2 (Siang)</option>
            <option value="3">3 (Malam)</option>
          </select>
        </div>
        <div class="form-group mb-3">
          <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Keterangan (opsional)</label>
          <input type="text" name="keterangan" class="form-control" style="border-radius: 8px;" placeholder="...">
        </div>
        <button type="submit" class="btn btn-success btn-block" style="border-radius: 8px; font-weight: 600;">Simpan Jadwal</button>
      </form>
    </div>

    {{-- Form Bulk 1 Bulan --}}
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">Buat Jadwal Bulk 1 Bulan</div>
      </div>
      <form method="POST" action="{{ route('shift-schedules.bulk') }}">
        @csrf
        <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
        <div class="form-group mb-3">
          <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Operator</label>
          <select name="operator_id" class="form-control" style="border-radius: 8px;" required>
            <option value="">-- Pilih --</option>
            @foreach ($operators as $op)
              <option value="{{ $op->id }}">{{ $op->user->name ?? $op->id }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group mb-3">
          <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Bulan</label>
          <input type="month" name="bulan" class="form-control" style="border-radius: 8px;" required value="{{ $selectedMonth }}">
        </div>
        <div class="form-group mb-3">
          <label style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Shift ke-</label>
          <select name="shift_ke" class="form-control" style="border-radius: 8px;" required>
            <option value="1">1 (Pagi)</option>
            <option value="2">2 (Siang)</option>
            <option value="3">3 (Malam)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-outline-primary btn-block" style="border-radius: 8px; font-weight: 600;">Generate 1 Bulan</button>
      </form>
    </div>
  </div>
</div>

{{-- MODAL UBAH STATUS --}}
<div class="modal fade" id="modalStatus" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content" style="border-radius: 14px; border: none; overflow: hidden;">
      <div class="modal-header" style="background: var(--page-bg); border-bottom: 1px solid var(--border);">
        <h6 class="modal-title font-weight-bold" style="font-size: 14px;">Update Status Kehadiran</h6>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="formStatus" method="POST" action="">
        @csrf
        @method('PUT')
        <div class="modal-body" style="padding: 16px;">
          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Status</label>
            <select name="status" id="modal-status" class="form-control" style="border-radius: 8px;">
              <option value="dijadwalkan">Dijadwalkan</option>
              <option value="hadir">Hadir</option>
              <option value="alpha">Alpha</option>
              <option value="izin">Izin</option>
              <option value="sakit">Sakit</option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label style="font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase;">Keterangan</label>
            <input type="text" name="keterangan" id="modal-keterangan" class="form-control" style="border-radius: 8px;" placeholder="Opsional...">
          </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid var(--border);">
          <button type="button" class="btn btn-light btn-sm" style="border-radius: 6px;" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 6px; font-weight: 600;">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function openStatusModal(id, currentStatus, currentKet) {
  var url = "{{ url('shift-schedules') }}/" + id + "/status";
  $('#formStatus').attr('action', url);
  $('#modal-status').val(currentStatus);
  $('#modal-keterangan').val(currentKet);
  $('#modalStatus').modal('show');
}
</script>
@endpush
