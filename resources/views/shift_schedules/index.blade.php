@extends('layouts._new_admin')
@section('title', 'Jadwal Shift Operator')

@push('style')
<style>
  :root {
    --shift-1-bg: #dcfce7;
    --shift-1-text: #15803d;
    --shift-1-border: #86efac;

    --shift-2-bg: #dbeafe;
    --shift-2-text: #1e40af;
    --shift-2-border: #93c5fd;

    --shift-3-bg: #ede9fe;
    --shift-3-text: #6d28d9;
    --shift-3-border: #c4b5fd;
  }

  /* Weekend Styling (Sabtu & Minggu) */
  .col-weekend {
    background-color: #fff1f2 !important;
  }
  .col-weekend-header {
    color: #e11d48 !important;
    background-color: #ffe4e6 !important;
    font-weight: 800 !important;
  }

  /* Interactive Shift Pill Slot */
  .shift-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    margin: 1px;
    position: relative;
  }

  .shift-pill:hover {
    transform: scale(1.15);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    z-index: 5;
  }

  .shift-pill.shift-1 {
    background-color: var(--shift-1-bg);
    color: var(--shift-1-text);
    border: 1px solid var(--shift-1-border);
  }

  .shift-pill.shift-2 {
    background-color: var(--shift-2-bg);
    color: var(--shift-2-text);
    border: 1px solid var(--shift-2-border);
  }

  .shift-pill.shift-3 {
    background-color: var(--shift-3-bg);
    color: var(--shift-3-text);
    border: 1px solid var(--shift-3-border);
  }

  /* Daily Presence Badge for Non-Shift Branches */
  .presence-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 3px 8px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    margin: 1px;
    white-space: nowrap;
  }

  .presence-badge:hover {
    transform: scale(1.08);
    box-shadow: 0 4px 10px rgba(0,0,0,0.14);
    z-index: 5;
  }

  .presence-hadir {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #86efac;
  }

  .presence-alpha {
    background-color: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
  }

  .presence-izin {
    background-color: #fef3c7;
    color: #d97706;
    border: 1px solid #fcd34d;
  }

  .presence-sakit {
    background-color: #eff6ff;
    color: #2563eb;
    border: 1px solid #93c5fd;
  }

  .presence-dijadwalkan {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
  }

  /* Status Indicator Badges */
  .status-dot {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: 1.5px solid #ffffff;
  }
  .status-dot.hadir { background-color: #10b981; }
  .status-dot.alpha { background-color: #ef4444; }
  .status-dot.izin { background-color: #f59e0b; }
  .status-dot.sakit { background-color: #3b82f6; }

  /* Empty Slot Button (+ Quick Assign) */
  .btn-slot-empty {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 1px dashed #cbd5e1;
    background: transparent;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
  }

  .btn-slot-empty:hover {
    border-color: #10b981;
    color: #059669;
    background-color: #ecfdf5;
    transform: scale(1.12);
  }

  /* Status Pill Legend */
  .status-badge-legend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
  }

  /* Quick Shift Selector Buttons */
  .btn-shift-option {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    text-align: left;
    width: 100%;
    transition: all 0.2s ease;
    background: #ffffff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .btn-shift-option:hover {
    border-color: #10b981;
    background-color: #f0fdf4;
  }

  .btn-shift-option input[type="radio"]:checked ~ div {
    font-weight: 800;
  }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap" style="gap: 16px;">
  <div>
    <h1 class="page-title mb-1" style="font-size: 24px; font-weight: 900; color: #0f172a;">Jadwal Shift Operator</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">Pengaturan kalender kerja harian, rotasi shift otomatis, dan rekapitulasi kehadiran operator.</p>
  </div>

  <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
    @if ($isShift)
      {{-- Button Trigger Modal Generate Pola Shift (Hanya untuk Gumelar & Sumingkir) --}}
      <button type="button" class="btn font-weight-bold" data-toggle="modal" data-target="#modalBulkPattern" style="background: #0f766e; color: #ffffff; border-radius: 9999px; padding: 9px 18px; font-size: 13px; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.2);">
        <i class="fas fa-magic mr-1"></i> Generate Pola Shift Otomatis
      </button>
    @else
      {{-- Label Info untuk Cabang Non-Shift --}}
      <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 8px 16px; border-radius: 9999px; font-size: 12.5px; font-weight: 700; border: 1px solid #bae6fd;">
        <i class="fas fa-business-time mr-1"></i> Jam Kerja Reguler Harian (Non-Shift)
      </span>
    @endif
  </div>
</div>

@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 12px; border: none; background: #dcfce7; color: #15803d; font-weight: 600;">
    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif
@if (isset($errors) && $errors->any())
  <div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius: 12px; border: none; background: #fee2e2; color: #b91c1c; font-weight: 600;">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
@endif

{{-- ======== FILTER ATAS ======== --}}
<div class="panel mb-4" style="padding: 14px 20px; border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
  <form method="GET" action="{{ route('shift-schedules.index') }}" class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 16px;">
    <div class="d-flex align-items-center flex-wrap" style="gap: 16px;">
      <div class="d-flex align-items-center">
        <label class="text-muted mb-0 mr-2" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Pertashop:</label>
        <select name="shop_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; min-width: 180px;" onchange="this.form.submit()">
          @foreach ($shops as $shop)
            <option value="{{ $shop->id }}" {{ $selectedShopId == $shop->id ? 'selected' : '' }}>
              {{ $shop->nama }} {{ $shop->isShiftBased() ? '(Shift)' : '(Reguler)' }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="d-flex align-items-center">
        <label class="text-muted mb-0 mr-2" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Bulan:</label>
        <input type="month" name="bulan" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px;" value="{{ $selectedMonth }}" onchange="this.form.submit()">
      </div>
    </div>

    <div class="d-flex align-items-center" style="gap: 8px;">
      <span class="badge" style="background: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 8px; font-size: 12px;">
        <i class="fas fa-calendar-alt text-primary mr-1"></i> Periode: <strong>{{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</strong>
      </span>
    </div>
  </form>
</div>

<div class="row">
  {{-- ======== TABEL KALENDER SHIFT / PRESENSI (KIRI/UTAMA) ======== --}}
  <div class="col-lg-8 mb-4">
    
    <div class="panel mb-4" style="border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
      <div class="panel-head d-flex align-items-center justify-content-between px-4 py-3" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
        <div class="panel-title font-weight-bold" style="font-size: 16px; color: #0f172a;">
          @if ($isShift)
            <i class="fas fa-calendar-check mr-2 text-success"></i>Kalender Shift — {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}
          @else
            <i class="fas fa-calendar-alt mr-2 text-primary"></i>Presensi & Jadwal Harian — {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}
          @endif
        </div>
        <small class="text-muted">
          @if ($isShift)
            Klik slot <strong>(+)</strong> untuk isi jadwal cepat
          @else
            Klik slot <strong>(+)</strong> untuk catat presensi harian
          @endif
        </small>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered mb-0" style="min-width: 760px; font-size: 0.8rem; border-color: #f1f5f9;">
          <thead>
            <tr style="background: #f8fafc;">
              <th style="min-width: 140px; color: #475569; font-size: 11px; text-transform: uppercase; vertical-align: middle; padding: 10px 14px; background: #ffffff;" class="sticky-left">
                Operator
              </th>
              @foreach ($days as $day)
                @php
                  $isWeekend = $day->isSaturday() || $day->isSunday();
                @endphp
                <th class="text-center p-1 {{ $isWeekend ? 'col-weekend-header' : '' }}" style="min-width: 34px; font-size: 11px; vertical-align: middle;" title="{{ $day->translatedFormat('l, d F Y') }}">
                  <div style="font-size: 12px; font-weight: 800;">{{ $day->format('d') }}</div>
                  <div style="font-size: 9px; opacity: 0.85; text-transform: uppercase;">{{ $day->format('D') }}</div>
                </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @forelse ($operators as $operator)
            <tr>
              <td style="font-size: 13px; font-weight: 700; color: #1e293b; vertical-align: middle; padding: 10px 14px; background: #ffffff;" class="sticky-left">
                {{ $operator->user->short_name ?? $operator->user->name }}
              </td>
              @foreach ($days as $day)
                @php
                  $isWeekend = $day->isSaturday() || $day->isSunday();
                  $dateStr = $day->format('Y-m-d');
                  $formattedIndo = $day->translatedFormat('d M Y');
                  $daySchedules = $schedules->get($dateStr, collect())
                    ->where('operator_id', $operator->id);
                @endphp
                <td class="text-center p-1 {{ $isWeekend ? 'col-weekend' : '' }}" style="vertical-align: middle;">
                  @if ($daySchedules->isEmpty())
                    <button type="button" class="btn-slot-empty" 
                            onclick="openQuickAssignModal('{{ $operator->id }}', '{{ addslashes($operator->user->short_name ?? $operator->user->name) }}', '{{ $dateStr }}', '{{ $formattedIndo }}')" 
                            title="{{ $isShift ? 'Assign Shift: ' . $formattedIndo : 'Catat Presensi: ' . $formattedIndo }}">
                      +
                    </button>
                  @else
                    @if ($isShift)
                      @foreach ($daySchedules as $s)
                        @php
                          $shiftClass = 'shift-' . min(2, max(1, $s->shift_ke));
                          $shiftInfo = $shiftConfig['shifts'][$s->shift_ke] ?? null;
                          $shiftLabel = $shiftInfo['nama'] ?? "Shift {$s->shift_ke}";
                          $badgeTitle = "{$shiftLabel} (" . ($shiftInfo['jam_label'] ?? '') . ") — Status: " . ucfirst($s->status) . ($s->keterangan ? " ({$s->keterangan})" : '');
                        @endphp
                        <span class="shift-pill {{ $shiftClass }}" 
                              title="{{ $badgeTitle }}"
                              onclick="openEditScheduleModal({{ $s->id }}, '{{ addslashes($operator->user->short_name ?? $operator->user->name) }}', '{{ $formattedIndo }}', '{{ $s->shift_ke }}', '{{ $s->status }}', '{{ addslashes($s->keterangan ?? '') }}', '{{ addslashes($shiftLabel) }}')">
                          {{ $s->shift_ke }}
                          @if($s->status !== 'dijadwalkan')
                            <span class="status-dot {{ $s->status }}" title="Status: {{ ucfirst($s->status) }}"></span>
                          @endif
                        </span>
                      @endforeach
                    @else
                      @foreach ($daySchedules as $s)
                        @php
                          $statusLabels = [
                            'hadir'       => ['label' => 'Hadir', 'icon' => 'fa-check', 'class' => 'presence-hadir'],
                            'alpha'       => ['label' => 'Alpha', 'icon' => 'fa-times', 'class' => 'presence-alpha'],
                            'izin'        => ['label' => 'Izin',  'icon' => 'fa-calendar-minus', 'class' => 'presence-izin'],
                            'sakit'       => ['label' => 'Sakit', 'icon' => 'fa-medkit', 'class' => 'presence-sakit'],
                            'dijadwalkan' => ['label' => 'Jadwal', 'icon' => 'fa-clock', 'class' => 'presence-dijadwalkan'],
                          ];
                          $st = $statusLabels[$s->status] ?? $statusLabels['hadir'];
                          $badgeTitle = "Presensi: " . ucfirst($s->status) . ($s->keterangan ? " ({$s->keterangan})" : '');
                        @endphp
                        <span class="presence-badge {{ $st['class'] }}" 
                              title="{{ $badgeTitle }}"
                              onclick="openEditScheduleModal({{ $s->id }}, '{{ addslashes($operator->user->short_name ?? $operator->user->name) }}', '{{ $formattedIndo }}', 1, '{{ $s->status }}', '{{ addslashes($s->keterangan ?? '') }}', 'Jam Reguler')">
                          <i class="fas {{ $st['icon'] }} mr-1" style="font-size: 9px;"></i>{{ $st['label'] }}
                        </span>
                      @endforeach
                    @endif
                  @endif
                </td>
              @endforeach
            </tr>
            @empty
            <tr>
              <td colspan="{{ count($days) + 1 }}" class="text-center text-muted py-5">
                <i class="fas fa-user-slash fa-2x mb-2 text-muted"></i>
                <div>Belum ada operator terdaftar di outlet ini.</div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Legend Status & Shift --}}
      <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-wrap" style="font-size: 11.5px; background: #f8fafc; gap: 12px;">
        <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
          @if ($isShift)
            <span class="font-weight-bold text-muted text-uppercase" style="font-size: 10.5px;">Shift:</span>
            @foreach ($shiftConfig['shifts'] ?? [] as $shiftNum => $sc)
              <span class="status-badge-legend" style="background: var(--shift-{{ $shiftNum }}-bg); color: var(--shift-{{ $shiftNum }}-text); border: 1px solid var(--shift-{{ $shiftNum }}-border);">
                {{ $shiftNum }} = {{ $sc['nama'] }} ({{ $sc['jam_label'] }})
              </span>
            @endforeach
          @else
            <span class="status-badge-legend" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
              <i class="fas fa-business-time mr-1"></i> Jam Kerja Operasional Penuh (Non-Shift)
            </span>
          @endif
        </div>

        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
          <span class="font-weight-bold text-muted text-uppercase" style="font-size: 10.5px;">Status:</span>
          <span class="status-badge-legend" style="background: #ecfdf5; color: #047857;"><i class="fas fa-circle text-success mr-1" style="font-size: 8px;"></i> Hadir</span>
          <span class="status-badge-legend" style="background: #fee2e2; color: #b91c1c;"><i class="fas fa-circle text-danger mr-1" style="font-size: 8px;"></i> Alpha</span>
          <span class="status-badge-legend" style="background: #fef3c7; color: #d97706;"><i class="fas fa-circle text-warning mr-1" style="font-size: 8px;"></i> Izin</span>
          <span class="status-badge-legend" style="background: #eff6ff; color: #2563eb;"><i class="fas fa-circle text-primary mr-1" style="font-size: 8px;"></i> Sakit</span>
        </div>
      </div>
    </div>

    {{-- ======== REKAP KEHADIRAN BULAN INI ======== --}}
    <div class="panel" style="border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
      <div class="panel-head px-4 py-3" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
        <div class="panel-title font-weight-bold" style="font-size: 16px; color: #0f172a;">
          <i class="fas fa-chart-pie mr-2 text-primary"></i>Rekap Kehadiran Bulan Ini
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr style="background: #f8fafc;">
              <th style="font-size: 11px; text-transform: uppercase; color: #475569; padding: 12px 16px;">Operator</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #475569;">Dijadwalkan</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #475569;">Hadir</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #475569;">Alpha</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #475569;">Izin</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #475569;">Sakit</th>
              <th class="text-center" style="font-size: 11px; text-transform: uppercase; color: #475569;">% Hadir</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($operators as $op)
              @php $recap = $attendanceRecaps->get($op->id); @endphp
              <tr>
                <td style="font-size: 13px; font-weight: 700; color: #1e293b; padding: 12px 16px;">
                  {{ $op->user->name ?? '-' }}
                </td>
                <td class="text-center font-weight-bold" style="font-size: 13px;">{{ $recap->total_dijadwalkan ?? 0 }}</td>
                <td class="text-center font-weight-bold" style="font-size: 13px; color: #15803d;">{{ $recap->total_hadir ?? 0 }}</td>
                <td class="text-center font-weight-bold" style="font-size: 13px; color: #dc2626;">{{ $recap->total_alpha ?? 0 }}</td>
                <td class="text-center font-weight-bold" style="font-size: 13px; color: #d97706;">{{ $recap->total_izin ?? 0 }}</td>
                <td class="text-center font-weight-bold" style="font-size: 13px; color: #2563eb;">{{ $recap->total_sakit ?? 0 }}</td>
                <td class="text-center" style="font-size: 13px;">
                  @if (($recap->total_dijadwalkan ?? 0) > 0)
                    <span class="badge badge-success px-2 py-1" style="border-radius: 6px; font-size: 12px;">
                      {{ round(($recap->total_hadir ?? 0) / $recap->total_dijadwalkan * 100, 1) }}%
                    </span>
                  @else
                    <span class="text-muted">–</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-4" style="font-size: 13px;">Belum ada data rekap.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>

  {{-- ======== PANEL KANAN: TAMBAH SINGLE & BULK GENERATOR ======== --}}
  <div class="col-lg-4">
    
    @if ($isShift)
      {{-- Form Tambah Jadwal Single untuk Cabang Bershift --}}
      <div class="panel mb-4" style="border-radius: 20px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 22px;">
        <div class="panel-title font-weight-bold mb-3" style="font-size: 15px; color: #0f172a;">
          <i class="fas fa-plus-circle text-success mr-1"></i> Tambah Jadwal Shift Manual
        </div>
        
        <form method="POST" action="{{ route('shift-schedules.store') }}">
          @csrf
          <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
          
          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Operator</label>
            <select name="operator_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required>
              <option value="">-- Pilih Operator --</option>
              @foreach ($operators as $op)
                <option value="{{ $op->id }}">{{ $op->user->name ?? $op->id }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">
              Tanggal <small class="text-muted">(DD/MM/YYYY)</small>
            </label>
            <input type="date" name="tanggal" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required value="{{ now()->format('Y-m-d') }}">
            <small class="text-muted d-block mt-1">Hari ini: {{ now()->translatedFormat('d F Y') }}</small>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Shift Kerja</label>
            <select name="shift_ke" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required>
              @foreach ($shiftConfig['shifts'] ?? [] as $sn => $sc)
                <option value="{{ $sn }}">{{ $sc['label'] }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Status Awal</label>
            <select name="status" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;">
              <option value="dijadwalkan">Dijadwalkan (Standar)</option>
              <option value="hadir">Hadir</option>
              <option value="alpha">Alpha</option>
              <option value="izin">Izin</option>
              <option value="sakit">Sakit</option>
            </select>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Keterangan (opsional)</label>
            <input type="text" name="keterangan" class="form-control form-control-sm" style="border-radius: 10px;" placeholder="Contoh: Standby / Backup...">
          </div>

          <button type="submit" class="btn btn-block font-weight-bold" style="background: #0f766e; color: #ffffff; border-radius: 10px; padding: 10px; font-size: 13px;">
            <i class="fas fa-save mr-1"></i> Simpan Jadwal Shift
          </button>
        </form>
      </div>

      {{-- Shortcut Card Generate 1 Bulan (Hanya untuk Cabang Bershift) --}}
      <div class="panel" style="border-radius: 20px; background: linear-gradient(145deg, #f0fdf4 0%, #ecfdf5 100%); border: 1px solid #a7f3d0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 22px;">
        <div class="d-flex align-items-center mb-2" style="gap: 10px;">
          <div style="width: 36px; height: 36px; border-radius: 10px; background: #10b981; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 16px;">
            <i class="fas fa-magic"></i>
          </div>
          <div>
            <h6 class="mb-0 font-weight-bold" style="color: #064e3b; font-size: 14px;">Generator Pola Shift</h6>
            <small style="color: #047857;">Buat jadwal rotasi 1 bulan dalam 1 klik</small>
          </div>
        </div>

        <p style="font-size: 12px; color: #065f46; margin: 12px 0 16px;">
          Gunakan generator otomatis untuk menerapkan pola kerja rotasi shift (misal: 2 hari Shift 1, 2 hari Shift 2, 1 hari libur) untuk cabang <strong>{{ $selectedShop->nama }}</strong>.
        </p>

        <button type="button" class="btn btn-block font-weight-bold" data-toggle="modal" data-target="#modalBulkPattern" style="background: #047857; color: #ffffff; border-radius: 10px; padding: 10px; font-size: 13px;">
          <i class="fas fa-calendar-plus mr-1"></i> Buka Wizard Pola Bulk
        </button>
      </div>
    @else
      {{-- Form Pencatatan Presensi Harian untuk Cabang Non-Shift --}}
      <div class="panel mb-4" style="border-radius: 20px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 22px;">
        <div class="panel-title font-weight-bold mb-3" style="font-size: 15px; color: #0f172a;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> Catat Presensi Harian
        </div>
        
        <form method="POST" action="{{ route('shift-schedules.store') }}">
          @csrf
          <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
          <input type="hidden" name="shift_ke" value="1">
          
          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Operator</label>
            <select name="operator_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required>
              <option value="">-- Pilih Operator --</option>
              @foreach ($operators as $op)
                <option value="{{ $op->id }}">{{ $op->user->name ?? $op->id }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">
              Tanggal <small class="text-muted">(DD/MM/YYYY)</small>
            </label>
            <input type="date" name="tanggal" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required value="{{ now()->format('Y-m-d') }}">
            <small class="text-muted d-block mt-1">Hari ini: {{ now()->translatedFormat('d F Y') }}</small>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Status Kehadiran</label>
            <select name="status" class="form-control form-control-sm font-weight-bold" style="border-radius: 10px;" required>
              <option value="hadir">✅ Hadir (Masuk Kerja Penuh)</option>
              <option value="alpha">❌ Alpha (Tidak Hadir)</option>
              <option value="izin">⚠️ Izin</option>
              <option value="sakit">🩺 Sakit</option>
              <option value="dijadwalkan">🗓️ Terjadwal</option>
            </select>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Keterangan (opsional)</label>
            <input type="text" name="keterangan" class="form-control form-control-sm" style="border-radius: 10px;" placeholder="Contoh: Sakit flu / Izin dinas...">
          </div>

          <button type="submit" class="btn btn-block font-weight-bold" style="background: #2563eb; color: #ffffff; border-radius: 10px; padding: 10px; font-size: 13px;">
            <i class="fas fa-save mr-1"></i> Simpan Presensi
          </button>
        </form>
      </div>

      {{-- Information Card Sistem Jam Kerja Reguler --}}
      <div class="panel" style="border-radius: 20px; background: #f8fafc; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); padding: 22px;">
        <div class="d-flex align-items-center mb-2" style="gap: 10px;">
          <div style="width: 36px; height: 36px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 16px;">
            <i class="fas fa-business-time"></i>
          </div>
          <div>
            <h6 class="mb-0 font-weight-bold" style="color: #0f172a; font-size: 14px;">Jam Kerja Reguler</h6>
            <small class="text-muted">Cabang Non-Shift</small>
          </div>
        </div>

        <p style="font-size: 12.5px; color: #475569; line-height: 1.5; margin: 12px 0 0;">
          Cabang <strong>{{ $selectedShop->nama }}</strong> beroperasi dengan sistem kerja normal / harian biasa (satu jam kerja operasional penuh per hari) tanpa pembagian slot rotasi shift.
        </p>
        <div class="mt-3 p-2 text-center" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 11.5px; color: #64748b;">
          <i class="fas fa-check-circle text-success mr-1"></i> Pencatatan kehadiran dicatat langsung per hari
        </div>
      </div>
    @endif

  </div>
</div>

{{-- =========================================================================
     MODAL 1: QUICK ASSIGN / PRESENSI CEPAT
     ========================================================================= --}}
<div class="modal fade" id="modalQuickAssign" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content" style="border-radius: 18px; border: none; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
      <div class="modal-header px-4 py-3" style="background: {{ $isShift ? '#f0fdf4' : '#eff6ff' }}; border-bottom: 1px solid {{ $isShift ? '#d1fae5' : '#dbeafe' }};">
        <div>
          <h6 class="modal-title font-weight-bold mb-0" style="font-size: 14px; color: {{ $isShift ? '#065f46' : '#1e40af' }};">
            <i class="fas {{ $isShift ? 'fa-user-clock' : 'fa-calendar-check' }} mr-1"></i> 
            {{ $isShift ? 'Quick Assign Shift' : 'Catat Presensi Harian' }}
          </h6>
          <small class="text-muted" id="quick-assign-subtitle">-</small>
        </div>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <form method="POST" action="{{ route('shift-schedules.store') }}">
        @csrf
        <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
        <input type="hidden" name="operator_id" id="qa-operator-id">
        <input type="hidden" name="tanggal" id="qa-tanggal">

        <div class="modal-body p-4">
          @if ($isShift)
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;" class="mb-2">Pilih Shift Kerja</label>
            
            <div class="d-flex flex-column" style="gap: 10px;">
              @foreach ($shiftConfig['shifts'] ?? [] as $shiftNum => $sc)
                <label class="btn-shift-option mb-0">
                  <div class="d-flex align-items-center" style="gap: 10px;">
                    <input type="radio" name="shift_ke" value="{{ $shiftNum }}" {{ $loop->first ? 'checked' : '' }}>
                    <div>
                      <div style="font-weight: 700; color: {{ $shiftNum == 1 ? '#15803d' : '#1e40af' }}; font-size: 13px;">{{ $sc['nama'] }}</div>
                      <small class="text-muted">{{ $sc['jam_label'] }}</small>
                    </div>
                  </div>
                  <span class="badge badge-{{ $shiftNum == 1 ? 'success' : 'primary' }} px-2 py-1" style="border-radius: 6px;">{{ $sc['badge'] }}</span>
                </label>
              @endforeach
            </div>

            <div class="form-group mt-3 mb-0">
              <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Status Kehadiran</label>
              <select name="status" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px;">
                <option value="dijadwalkan">Dijadwalkan (Standar)</option>
                <option value="hadir">Hadir</option>
                <option value="alpha">Alpha</option>
                <option value="izin">Izin</option>
                <option value="sakit">Sakit</option>
              </select>
            </div>
          @else
            <input type="hidden" name="shift_ke" value="1">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;" class="mb-2">Status Kehadiran Hari Ini</label>
            
            <div class="d-flex flex-column" style="gap: 8px;">
              <label class="btn-shift-option mb-0 p-2">
                <div class="d-flex align-items-center" style="gap: 10px;">
                  <input type="radio" name="status" value="hadir" checked>
                  <span style="font-weight: 700; color: #15803d; font-size: 13px;"><i class="fas fa-check text-success mr-1"></i> Hadir</span>
                </div>
                <span class="badge badge-success px-2 py-1" style="border-radius: 6px;">Masuk</span>
              </label>

              <label class="btn-shift-option mb-0 p-2">
                <div class="d-flex align-items-center" style="gap: 10px;">
                  <input type="radio" name="status" value="alpha">
                  <span style="font-weight: 700; color: #dc2626; font-size: 13px;"><i class="fas fa-times text-danger mr-1"></i> Alpha</span>
                </div>
                <span class="badge badge-danger px-2 py-1" style="border-radius: 6px;">Tidak Hadir</span>
              </label>

              <label class="btn-shift-option mb-0 p-2">
                <div class="d-flex align-items-center" style="gap: 10px;">
                  <input type="radio" name="status" value="izin">
                  <span style="font-weight: 700; color: #d97706; font-size: 13px;"><i class="fas fa-calendar-minus text-warning mr-1"></i> Izin</span>
                </div>
                <span class="badge badge-warning px-2 py-1" style="border-radius: 6px; color: #ffffff;">Izin</span>
              </label>

              <label class="btn-shift-option mb-0 p-2">
                <div class="d-flex align-items-center" style="gap: 10px;">
                  <input type="radio" name="status" value="sakit">
                  <span style="font-weight: 700; color: #2563eb; font-size: 13px;"><i class="fas fa-medkit text-primary mr-1"></i> Sakit</span>
                </div>
                <span class="badge badge-primary px-2 py-1" style="border-radius: 6px;">Sakit</span>
              </label>

              <label class="btn-shift-option mb-0 p-2">
                <div class="d-flex align-items-center" style="gap: 10px;">
                  <input type="radio" name="status" value="dijadwalkan">
                  <span style="font-weight: 700; color: #475569; font-size: 13px;"><i class="fas fa-clock text-secondary mr-1"></i> Terjadwal</span>
                </div>
                <span class="badge badge-secondary px-2 py-1" style="border-radius: 6px;">Rencana</span>
              </label>
            </div>
          @endif

          <div class="form-group mt-3 mb-0">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Keterangan</label>
            <input type="text" name="keterangan" class="form-control form-control-sm" style="border-radius: 8px;" placeholder="Opsional...">
          </div>
        </div>

        <div class="modal-footer px-4 py-3" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
          <button type="button" class="btn btn-light btn-sm" style="border-radius: 8px;" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success btn-sm font-weight-bold" style="border-radius: 8px; padding: 6px 18px;">
            <i class="fas fa-check mr-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- =========================================================================
     MODAL 2: EDIT & UPDATE STATUS
     ========================================================================= --}}
<div class="modal fade" id="modalEditSchedule" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
    <div class="modal-content" style="border-radius: 18px; border: none; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
      <div class="modal-header px-4 py-3" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
        <div>
          <h6 class="modal-title font-weight-bold mb-0" style="font-size: 14px; color: #0f172a;">
            <i class="fas fa-edit mr-1 text-primary"></i> 
            {{ $isShift ? 'Edit Status Shift' : 'Edit Presensi Harian' }}
          </h6>
          <small class="text-muted" id="edit-schedule-subtitle">-</small>
        </div>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <form id="formUpdateSchedule" method="POST" action="">
        @csrf
        @method('PUT')
        
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Status Kehadiran</label>
            <select name="status" id="edit-status-select" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px;">
              <option value="dijadwalkan">{{ $isShift ? 'Dijadwalkan (Standar)' : 'Terjadwal' }}</option>
              <option value="hadir">Hadir</option>
              <option value="alpha">Alpha (Tidak Hadir)</option>
              <option value="izin">Izin</option>
              <option value="sakit">Sakit</option>
            </select>
          </div>

          <div class="form-group mb-0">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Keterangan</label>
            <input type="text" name="keterangan" id="edit-keterangan-input" class="form-control form-control-sm" style="border-radius: 8px;" placeholder="Opsional...">
          </div>
        </div>

        <div class="modal-footer px-4 py-3 d-flex justify-content-between" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
          <button type="button" class="btn btn-outline-danger btn-sm" style="border-radius: 8px;" onclick="submitDeleteSchedule()">
            <i class="fas fa-trash-alt"></i> Hapus
          </button>
          
          <div>
            <button type="button" class="btn btn-light btn-sm mr-1" style="border-radius: 8px;" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary btn-sm font-weight-bold" style="border-radius: 8px;">
              Simpan
            </button>
          </div>
        </div>
      </form>

      {{-- Hidden form delete --}}
      <form id="formDeleteSchedule" method="POST" action="" style="display: none;">
        @csrf
        @method('DELETE')
      </form>
    </div>
  </div>
</div>

@if ($isShift)
{{-- =========================================================================
     MODAL 3: BULK SHIFT & ROTATION PATTERN GENERATOR (HANYA UNTUK GUMELAR & SUMINGKIR)
     ========================================================================= --}}
<div class="modal fade" id="modalBulkPattern" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.2);">
      <div class="modal-header px-4 py-3" style="background: linear-gradient(135deg, #0f766e 0%, #064e3b 100%); color: #ffffff;">
        <div>
          <h5 class="modal-title font-weight-bold mb-0" style="font-size: 16px;">
            <i class="fas fa-magic mr-1 text-warning"></i> Generator Pola Shift 1 Bulan — {{ $selectedShop->nama }}
          </h5>
          <small style="color: #a7f3d0;">Otomatisasi pengisian jadwal kerja rotasi operator</small>
        </div>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <form method="POST" action="{{ route('shift-schedules.bulk') }}">
        @csrf
        <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">

        <div class="modal-body p-4">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Operator</label>
              <select name="operator_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px;" required>
                <option value="">-- Pilih Operator --</option>
                @foreach ($operators as $op)
                  <option value="{{ $op->id }}">{{ $op->user->name ?? $op->id }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Bulan Target</label>
              <input type="month" name="bulan" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px;" required value="{{ $selectedMonth }}">
            </div>
          </div>

          <div class="form-group mb-3">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Pilih Pola Rotasi</label>
            <select name="pola" id="bulk-pola-select" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px;" onchange="togglePolaOptions(this.value)">
              <option value="2p_2s_1l">⚡ Pola Rotasi (2 Hari Shift 1, 2 Hari Shift 2, 1 Hari Libur)</option>
              @if(isset($shiftConfig['shifts'][1]))
                <option value="full_pagi">☀️ Full {{ $shiftConfig['shifts'][1]['nama'] }} (Setiap Hari)</option>
              @endif
              @if(isset($shiftConfig['shifts'][2]))
                <option value="full_siang">🌙 Full {{ $shiftConfig['shifts'][2]['nama'] }} (Setiap Hari)</option>
              @endif
              <option value="fixed">⚙️ Tetapkan 1 Nomor Shift Tertentu</option>
            </select>
          </div>

          <div class="form-group mb-3" id="fixed-shift-group" style="display: none;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Nomor Shift Tetap</label>
            <select name="shift_ke" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px;">
              @foreach ($shiftConfig['shifts'] ?? [] as $sn => $sc)
                <option value="{{ $sn }}">{{ $sc['label'] }}</option>
              @endforeach
            </select>
          </div>

          <div class="p-3 mb-0" style="background: #f0fdf4; border-radius: 12px; border: 1px solid #bbf7d0;">
            <div style="font-size: 12px; font-weight: 700; color: #065f46;">
              <i class="fas fa-info-circle mr-1"></i> Standar Jam Shift Cabang {{ $selectedShop->nama }}:
            </div>
            <ul class="mb-0 mt-1 pl-3 text-muted" style="font-size: 11.5px; color: #047857 !important;">
              @foreach ($shiftConfig['shifts'] ?? [] as $sn => $sc)
                <li><strong>{{ $sc['nama'] }}:</strong> {{ $sc['jam_label'] }}</li>
              @endforeach
            </ul>
          </div>
        </div>

        <div class="modal-footer px-4 py-3" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
          <button type="button" class="btn btn-light btn-sm" style="border-radius: 8px;" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success btn-sm font-weight-bold" style="border-radius: 8px; padding: 8px 20px;">
            <i class="fas fa-bolt mr-1"></i> Jalankan Generator Bulk
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
// 1. Quick Assign Modal Trigger
function openQuickAssignModal(operatorId, operatorName, dateStr, formattedIndo) {
  $('#qa-operator-id').val(operatorId);
  $('#qa-tanggal').val(dateStr);
  $('#quick-assign-subtitle').text(operatorName + ' • ' + formattedIndo);
  $('#modalQuickAssign').modal('show');
}

// 2. Edit Schedule Modal Trigger
var activeScheduleId = null;
function openEditScheduleModal(id, operatorName, formattedIndo, shiftKe, status, keterangan, shiftLabel) {
  activeScheduleId = id;
  var updateUrl = "{{ url('shift-schedules') }}/" + id;
  $('#formUpdateSchedule').attr('action', updateUrl);
  $('#formDeleteSchedule').attr('action', updateUrl);
  
  var labelText = shiftLabel ? shiftLabel : ('Shift ' + shiftKe);
  $('#edit-schedule-subtitle').text(operatorName + ' • ' + formattedIndo + ' (' + labelText + ')');
  
  $('#edit-status-select').val(status);
  $('#edit-keterangan-input').val(keterangan);
  $('#modalEditSchedule').modal('show');
}

function submitDeleteSchedule() {
  if (confirm('Hapus jadwal / presensi ini?')) {
    $('#formDeleteSchedule').submit();
  }
}

// 3. Toggle Pola Bulk
function togglePolaOptions(val) {
  if (val === 'fixed') {
    $('#fixed-shift-group').slideDown(200);
  } else {
    $('#fixed-shift-group').slideUp(200);
  }
}
</script>
@endpush
