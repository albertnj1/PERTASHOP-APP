@extends('layouts._new_admin')
@section('title', 'Presensi & Status Libur')

@push('style')
<style>
  .presence-hero {
    border-radius: 20px;
    padding: 24px 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    border: 1px solid rgba(0,0,0,0.05);
  }
  .presence-hero.libur {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #ffffff;
  }
  .presence-hero.hadir {
    background: linear-gradient(135deg, #10b981 0%, #047857 100%);
    color: #ffffff;
  }
  .presence-hero.dijadwalkan {
    background: linear-gradient(135deg, #475569 0%, #1e293b 100%);
    color: #ffffff;
  }
  .presence-hero.izin, .presence-hero.sakit {
    background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
    color: #ffffff;
  }
  .presence-hero.alpha {
    background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
    color: #ffffff;
  }

  .notice-card {
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 16px 18px;
    margin-bottom: 12px;
    background: #ffffff;
    transition: all 0.2s ease;
  }
  .notice-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border-color: #cbd5e1;
  }
  .notice-card.libur {
    border-left: 4px solid #0284c7;
    background: #f0f9ff;
  }
  .notice-card.info-note {
    border-left: 4px solid #3b82f6;
  }

  .badge-status-pill {
    padding: 6px 12px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .badge-status-pill.hadir { background: #dcfce7; color: #15803d; }
  .badge-status-pill.alpha { background: #fee2e2; color: #b91c1c; }
  .badge-status-pill.izin { background: #fef3c7; color: #d97706; }
  .badge-status-pill.sakit { background: #eff6ff; color: #2563eb; }
  .badge-status-pill.libur { background: #e0f2fe; color: #0369a1; border: 1px dashed #38bdf8; }
  .badge-status-pill.dijadwalkan { background: #f1f5f9; color: #475569; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap" style="gap: 16px;">
  <div>
    <h1 class="page-title mb-1" style="font-size: 24px; font-weight: 900; color: #0f172a;">Presensi &amp; Kalender Kerja Saya</h1>
    <p class="text-muted mb-0" style="font-size: 13px;">
      Informasi jadwal shift, status kehadiran harian, serta pemberitahuan libur &amp; pesan dari manajemen.
    </p>
  </div>
  <div>
    <form method="GET" action="{{ route('operator.presensi') }}" class="d-flex align-items-center gap-2">
      <label class="text-muted mb-0 mr-2" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Bulan:</label>
      <input type="month" name="bulan" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px;" value="{{ $selectedMonth }}" onchange="this.form.submit()">
    </form>
  </div>
</div>

{{-- ======== HERO STATUS HARI INI ======== --}}
@php
  $todayStatus = $todaySchedule?->status ?? 'belum_ada';
  $todayClass = in_array($todayStatus, ['libur', 'hadir', 'dijadwalkan', 'izin', 'sakit', 'alpha']) ? $todayStatus : 'dijadwalkan';
@endphp

<div class="presence-hero {{ $todayClass }}">
  <div class="d-flex align-items-start justify-content-between flex-wrap" style="gap: 16px;">
    <div>
      <span class="badge px-3 py-1 mb-2" style="background: rgba(255,255,255,0.2); color: #ffffff; border-radius: 9999px; font-size: 12px; font-weight: 700; letter-spacing: 0.5px;">
        <i class="far fa-calendar-alt mr-1"></i> STATUS HARI INI &mdash; {{ \Carbon\Carbon::today()->translatedFormat('l, d F Y') }}
      </span>

      @if ($todayStatus === 'libur')
        <h2 class="mb-2 font-weight-bold" style="font-size: 26px; color: #ffffff;">
          <i class="fas fa-mug-hot mr-2 text-warning"></i> HARI INI ANDA DILIBURKAN
        </h2>
        <p class="mb-3" style="font-size: 14px; opacity: 0.95; max-width: 650px;">
          Manajemen telah menetapkan status hari ini sebagai hari libur / istirahat untuk Anda.
        </p>
        <div class="p-3" style="background: rgba(255,255,255,0.15); border-radius: 12px; border: 1px solid rgba(255,255,255,0.25); max-width: 650px;">
          <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; opacity: 0.9;">
            <i class="fas fa-comment-alt mr-1"></i> Pesan / Alasan Manajemen:
          </div>
          <div style="font-size: 14px; font-weight: 700; margin-top: 4px;">
            "{{ $todaySchedule->keterangan ?: 'Diliburkan oleh Super Admin / Manajemen Operasional.' }}"
          </div>
        </div>
      @elseif ($todayStatus === 'hadir')
        <h2 class="mb-2 font-weight-bold" style="font-size: 26px; color: #ffffff;">
          <i class="fas fa-check-circle mr-2 text-warning"></i> STATUS HARI INI: HADIR
        </h2>
        <p class="mb-0" style="font-size: 14px; opacity: 0.95;">
          Presensi Anda telah tercatat hadir untuk outlet <strong>{{ $todaySchedule->shop?->nama ?? 'Pertashop' }}</strong>.
          @if ($todaySchedule->shift_ke)
            (Shift {{ $todaySchedule->shift_ke }})
          @endif
        </p>
        @if ($todaySchedule->keterangan)
          <div class="mt-3 p-2 px-3" style="background: rgba(255,255,255,0.15); border-radius: 8px; display: inline-block;">
            <small><strong>Catatan:</strong> {{ $todaySchedule->keterangan }}</small>
          </div>
        @endif
      @elseif ($todayStatus === 'dijadwalkan')
        <h2 class="mb-2 font-weight-bold" style="font-size: 26px; color: #ffffff;">
          <i class="fas fa-calendar-check mr-2 text-warning"></i> STATUS HARI INI: TERJADWAL
        </h2>
        <p class="mb-0" style="font-size: 14px; opacity: 0.95;">
          Anda dijadwalkan bertugas di <strong>{{ $todaySchedule->shop?->nama ?? 'Pertashop' }}</strong>
          @if ($todaySchedule->shift_ke)
            (Shift {{ $todaySchedule->shift_ke }})
          @endif.
          Silakan lakukan operasional harian dan input laporan harian di akhir shift.
        </p>
      @elseif ($todayStatus === 'izin' || $todayStatus === 'sakit')
        <h2 class="mb-2 font-weight-bold" style="font-size: 26px; color: #ffffff;">
          <i class="fas fa-notes-medical mr-2 text-warning"></i> STATUS HARI INI: {{ strtoupper($todayStatus) }}
        </h2>
        <p class="mb-2" style="font-size: 14px; opacity: 0.95;">
          Status kehadiran Anda tercatat sebagai {{ strtoupper($todayStatus) }}.
        </p>
        @if ($todaySchedule->keterangan)
          <div class="p-2 px-3" style="background: rgba(255,255,255,0.15); border-radius: 8px; display: inline-block;">
            <small><strong>Keterangan:</strong> {{ $todaySchedule->keterangan }}</small>
          </div>
        @endif
      @elseif ($todayStatus === 'alpha')
        <h2 class="mb-2 font-weight-bold" style="font-size: 26px; color: #ffffff;">
          <i class="fas fa-times-circle mr-2 text-warning"></i> STATUS HARI INI: ALPHA
        </h2>
        <p class="mb-0" style="font-size: 14px; opacity: 0.95;">
          Tidak hadir tanpa keterangan tercatat pada tanggal hari ini. Hubungi pengelola cabang jika ada kekeliruan.
        </p>
      @else
        <h2 class="mb-2 font-weight-bold" style="font-size: 24px; color: #ffffff;">
          <i class="fas fa-info-circle mr-2 text-warning"></i> Belum Ada Jadwal Khusus Hari Ini
        </h2>
        <p class="mb-0" style="font-size: 14px; opacity: 0.95;">
          Belum ada jadwal shift atau presensi spesifik yang ditugaskan hari ini.
        </p>
      @endif
    </div>

    <div>
      <div class="text-right" style="opacity: 0.9;">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Pertashop Penugasan</div>
        <div style="font-size: 15px; font-weight: 800;">{{ $operator->shop?->nama ?? 'Semua Cabang' }}</div>
      </div>
    </div>
  </div>
</div>

{{-- ======== STATISTIK BULANAN ======== --}}
<div class="summary-grid mb-4">
  <div class="metric-card neutral">
    <div class="metric-label">Jadwal Tugas Bulan Ini</div>
    <div class="metric-value">{{ $recap->total_dijadwalkan ?? $monthSchedules->where('status', '!=', 'libur')->count() }} Hari</div>
    <div class="metric-sub">Total hari kerja terjadwal</div>
  </div>

  <div class="metric-card pos">
    <div class="metric-label">Kehadiran Masuk</div>
    <div class="metric-value pos">{{ $recap->total_hadir ?? $monthSchedules->where('status', 'hadir')->count() }} Hari</div>
    <div class="metric-sub">Tercatat masuk kerja</div>
  </div>

  <div class="metric-card neutral">
    <div class="metric-label">Hari Libur Diberikan</div>
    <div class="metric-value" style="color: #0284c7;">{{ $totalLiburBulanIni }} Hari</div>
    <div class="metric-sub">Diliburkan oleh manajemen</div>
  </div>

  <div class="metric-card {{ ($recap->total_alpha ?? 0) > 0 ? 'neg' : 'neutral' }}">
    <div class="metric-label">Izin / Sakit / Alpha</div>
    <div class="metric-value {{ ($recap->total_alpha ?? 0) > 0 ? 'neg' : '' }}">
      {{ ($recap->total_izin ?? 0) + ($recap->total_sakit ?? 0) }} / <span class="text-danger">{{ $recap->total_alpha ?? 0 }}</span>
    </div>
    <div class="metric-sub">Izin &amp; Sakit / Alpha</div>
  </div>
</div>

<div class="row">
  {{-- ======== KOLOM KIRI: KOTAK MASUK PESAN & NOTIFIKASI MANAJEMEN ======== --}}
  <div class="col-lg-5 mb-4">
    <div class="panel" style="border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); height: 100%;">
      <div class="panel-head px-4 py-3" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
        <div class="panel-title font-weight-bold" style="font-size: 15px; color: #0f172a;">
          <i class="fas fa-bell mr-2 text-warning"></i> Kotak Pemberitahuan &amp; Pesan Manajemen
        </div>
      </div>

      <div class="p-3" style="max-height: 520px; overflow-y: auto;">
        @forelse ($recentNotices as $notice)
          @php
            $isNoticeLibur = $notice->status === 'libur';
          @endphp
          <div class="notice-card {{ $isNoticeLibur ? 'libur' : 'info-note' }}">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="badge {{ $isNoticeLibur ? 'badge-info' : 'badge-light' }} px-2 py-1" style="border-radius: 6px; font-size: 11px; font-weight: 700;">
                <i class="fas {{ $isNoticeLibur ? 'fa-mug-hot' : 'fa-info-circle' }} mr-1"></i>
                {{ $isNoticeLibur ? 'Diliburkan Manajemen' : 'Catatan Presensi' }}
              </span>
              <small class="text-muted font-weight-bold" style="font-size: 11px;">
                {{ $notice->tanggal->translatedFormat('d M Y') }}
              </small>
            </div>

            <div style="font-size: 13px; color: #1e293b; font-weight: 600; margin-top: 6px;">
              {{ $notice->keterangan ?: ($isNoticeLibur ? 'Diliburkan oleh Super Admin.' : 'Catatan status ' . $notice->status) }}
            </div>

            <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top" style="font-size: 11px; color: #64748b;">
              <span><i class="fas fa-map-marker-alt mr-1"></i> {{ $notice->shop?->nama ?? 'Pertashop' }}</span>
              <span>Status: <strong class="text-uppercase">{{ $notice->status }}</strong></span>
            </div>
          </div>
        @empty
          <div class="text-center py-5 text-muted">
            <i class="far fa-bell-slash fa-2x mb-2 text-muted"></i>
            <div style="font-size: 13px;">Belum ada pesan atau pemberitahuan baru dari manajemen.</div>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- ======== KOLOM KANAN: TABEL RIWAYAT PRESENSI BULAN TERPILIH ======== --}}
  <div class="col-lg-7 mb-4">
    <div class="panel" style="border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
      <div class="panel-head px-4 py-3 d-flex align-items-center justify-content-between" style="background: #ffffff; border-bottom: 1px solid #f1f5f9;">
        <div class="panel-title font-weight-bold" style="font-size: 15px; color: #0f172a;">
          <i class="fas fa-calendar-alt mr-2 text-primary"></i> Riwayat Presensi ({{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }})
        </div>
        <span class="badge badge-secondary px-2 py-1" style="border-radius: 6px; font-size: 11px;">
          {{ $monthSchedules->count() }} Entri
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr style="background: #f8fafc;">
              <th style="font-size: 11px; text-transform: uppercase; color: #475569; padding: 12px 16px;">Tanggal</th>
              <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Shift / Waktu</th>
              <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Status</th>
              <th style="font-size: 11px; text-transform: uppercase; color: #475569;">Pesan / Keterangan</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($monthSchedules as $sched)
              @php
                $isTodayRow = $sched->tanggal->isToday();
              @endphp
              <tr style="{{ $isTodayRow ? 'background-color: #f0fdf4;' : '' }}">
                <td style="font-size: 13px; font-weight: 700; color: #1e293b; padding: 12px 16px;">
                  {{ $sched->tanggal->translatedFormat('d M Y') }}
                  <small class="text-muted d-block" style="font-weight: 500;">{{ $sched->tanggal->translatedFormat('l') }}</small>
                  @if ($isTodayRow)
                    <span class="badge badge-success px-2 py-0 mt-1" style="font-size: 10px;">Hari Ini</span>
                  @endif
                </td>
                <td style="font-size: 12.5px; color: #475569;">
                  @if ($sched->shift_ke)
                    <span class="badge badge-primary px-2 py-1" style="border-radius: 6px; font-size: 11px;">
                      Shift {{ $sched->shift_ke }}
                    </span>
                  @else
                    <span class="text-muted">Reguler</span>
                  @endif
                </td>
                <td>
                  <span class="badge-status-pill {{ $sched->status }}">
                    @if ($sched->status === 'hadir')
                      <i class="fas fa-check-circle text-success"></i> Hadir
                    @elseif ($sched->status === 'libur')
                      <i class="fas fa-mug-hot text-info"></i> Libur
                    @elseif ($sched->status === 'alpha')
                      <i class="fas fa-times-circle text-danger"></i> Alpha
                    @elseif ($sched->status === 'izin')
                      <i class="fas fa-calendar-minus text-warning"></i> Izin
                    @elseif ($sched->status === 'sakit')
                      <i class="fas fa-medkit text-primary"></i> Sakit
                    @else
                      <i class="fas fa-clock text-secondary"></i> Terjadwal
                    @endif
                  </span>
                </td>
                <td style="font-size: 12.5px; color: #334155;">
                  {{ $sched->keterangan ?: '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-5" style="font-size: 13px;">
                  <i class="far fa-calendar-times fa-2x mb-2 text-muted"></i>
                  <div>Belum ada jadwal presensi untuk bulan ini.</div>
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
