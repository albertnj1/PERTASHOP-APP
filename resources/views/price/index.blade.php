@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-3 align-items-center">
            <div class="col-8">
                <h1 style="font-weight:700;color:#0f172a;">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary mr-2" style="border-radius: 50%; width: 40px; height: 40px; padding: 0; line-height: 38px; text-align: center;">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    Riwayat Harga BBM
                </h1>
            </div>
            <div class="col-4 text-right">
                @if(Auth::user()->role !== 'investor')
                <a href="{{ route('prices.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus mr-2"></i>Tambah Harga
                </a>
                @endif
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        @if(Auth::user()->role !== 'operator')
        <!-- Active Price Cards -->
        <div id="active-price-row" class="row mb-4">
            <!-- populated by JS -->
        </div>
        @endif

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-0" id="priceTabs" style="border-bottom:2px solid #e2e8f0;">
            <li class="nav-item">
                <a class="nav-link active" id="tab-history" data-toggle="tab" href="#panel-history"
                   style="font-weight:600;color:#0f172a;border:0;border-bottom:3px solid #00796B;">
                    <i class="fas fa-history mr-1"></i> Riwayat Harga
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-audit" data-toggle="tab" href="#panel-audit"
                   style="font-weight:600;color:#64748b;border:0;">
                    <i class="fas fa-shield-alt mr-1"></i> Log Perubahan
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- Riwayat Harga Tab -->
            <div class="tab-pane fade show active" id="panel-history">
                <div class="card" style="border-top-left-radius:0;border-radius:0 0 12px 12px;">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="table-prices" class="table table-hover" style="width:100%;">
                                <thead>
                                    <tr style="background:#f8fafc;">
                                        <th>#</th>
                                        <th>Outlet</th>
                                        <th>Berlaku Sejak</th>
                                        @if(Auth::user()->role !== 'operator')
                                        <th class="text-right">Harga Beli (Rp)</th>
                                        @endif
                                        <th class="text-right">Harga Jual (Rp)</th>
                                        @if(Auth::user()->role !== 'operator')
                                        <th class="text-right">Margin (Rp)</th>
                                        @endif
                                        <th>Lokasi</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Log Tab -->
            <div class="tab-pane fade" id="panel-audit">
                <div class="card" style="border-top-left-radius:0;border-radius:0 0 12px 12px;">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="table-audit" class="table table-hover" style="width:100%;">
                                <thead>
                                    <tr style="background:#f8fafc;">
                                        <th>#</th>
                                        <th>Waktu</th>
                                        <th>Outlet</th>
                                        <th>Aksi</th>
                                        <th>Pengguna</th>
                                        @if(Auth::user()->role !== 'operator')
                                        <th class="text-right">Harga Beli Lama</th>
                                        @endif
                                        <th class="text-right">Harga Jual Lama</th>
                                        @if(Auth::user()->role !== 'operator')
                                        <th class="text-right">Harga Beli Baru</th>
                                        @endif
                                        <th class="text-right">Harga Jual Baru</th>
                                    </tr>
                                </thead>
                                <tbody id="audit-log-body">
                                    <tr><td colspan="9" class="text-center text-muted">Memuat data log...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection

@push('script')
<script>
$(document).ready(function() {

    // --- Riwayat Harga DataTable ---
    const priceTable = $('#table-prices').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('prices.index') }}",
        columns: [
            { title: '#',         data: 'DT_RowIndex', orderable: false, searchable: false, width: '40' },
            { title: 'Outlet',    data: 'shop',        name: 'shop',
                render: d => d ? `<span class="badge badge-pill" style="background:#e8f5e9;color:#2e7d32;font-weight:600;">${d}</span>` : `<span class="badge badge-pill" style="background:#e3f2fd;color:#1565c0;font-weight:600;">Global</span>`
            },
            { title: 'Berlaku Sejak', data: 'effective_at', name: 'effective_at',
                render: d => d ? new Date(d).toLocaleString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}) : '—'
            },
            @if(Auth::user()->role !== 'operator')
            { title: 'Harga Beli (Rp)', data: 'harga_beli', name: 'harga_beli', className: 'text-right',
                render: d => formatNumber(d)
            },
            @endif
            { title: 'Harga Jual (Rp)', data: 'harga_jual', name: 'harga_jual', className: 'text-right',
                render: d => formatNumber(d)
            },
            @if(Auth::user()->role !== 'operator')
            { title: 'Margin (Rp)', data: null, orderable: false, className: 'text-right',
                render: (d, t, r) => `<strong class="text-success">+${formatNumber(r.harga_jual - r.harga_beli)}</strong>`
            },
            @endif
            { title: 'Lokasi', data: 'lokasi_device', name: 'lokasi_device',
                render: d => d ? `<span style="font-size:11px;color:#64748b;"><i class="fas fa-map-marker-alt mr-1"></i>${d}</span>` : '<span style="font-size:11px;color:#94a3b8;">Tidak ada info</span>'
            },
            { title: 'Status', data: null, orderable: false,
                render: (d, t, r) => {
                    if (!r.effective_at) return '<span class="badge badge-secondary">—</span>';
                    const now = new Date();
                    const effAt = new Date(r.effective_at);
                    return effAt <= now
                        ? '<span class="badge badge-success" style="font-size:11px;">Aktif</span>'
                        : '<span class="badge badge-warning" style="font-size:11px;">Mendatang</span>';
                }
            },
            { title: 'Aksi', data: 'action', orderable: false, searchable: false },
        ],
        order: [[2, 'desc']],
        language: { processing: 'Memuat data...' }
    });

    // Delete handler
    $('#table-prices').on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus harga ini?',
            text: 'Data harga akan dihapus permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'DELETE',
                    url: "{{ route('prices.index') }}/" + id,
                    success: response => {
                        priceTable.ajax.reload();
                        Swal.fire('Terhapus!', response.message, 'success');
                    }
                });
            }
        });
    });

    @if(Auth::user()->role !== 'operator')
    // --- Active Price Cards ---
    function renderActivePrices() {
        const outletColors = {
            'Kalitapen': '#00796B', 'Kalibenda': '#2e7d32',
            'Pageralang': '#f57c00', 'Gumelar': '#d32f2f', 'Kemutug Lor': '#673ab7'
        };
        $.ajax({
            url: "{{ route('dashboard') }}",
            method: 'GET',
            data: { filter: 'day', shop_id: '' },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(data) {
                let html = '';
                data.summaries.forEach(s => {
                    const color = outletColors[s.shop.nama] || '#00796B';
                    const effAt = s.effective_at ? new Date(s.effective_at).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'}) : '—';
                    html += `
                    <div class="col-6 col-sm-6 col-md-4 col-lg-2-4 px-1 px-sm-2 mb-2 mb-sm-3">
                        <div class="card h-100 mb-0" style="border-top:3px solid ${color};border-radius:10px;">
                            <div class="card-body p-2 p-sm-3">
                                <div class="d-flex align-items-center mb-1 mb-sm-2">
                                    <span style="width:8px;height:8px;border-radius:50%;background:${color};display:inline-block;margin-right:6px;flex-shrink:0;"></span>
                                    <strong style="font-size:12px;" class="text-truncate">${s.shop.nama}</strong>
                                </div>
                                <div class="d-flex justify-content-between" style="font-size:11px;color:#555;">
                                    <span>Harga Jual</span>
                                    <span class="font-weight-700 text-success">${formatCurrency(s.harga_jual_aktif, 0)}</span>
                                </div>
                                <div class="d-flex justify-content-between" style="font-size:11px;color:#555;">
                                    <span>Harga Beli</span>
                                    <span class="font-weight-700">${formatCurrency(s.harga_beli_aktif, 0)}</span>
                                </div>
                                <div class="d-flex justify-content-between" style="font-size:11px;color:#555;">
                                    <span>Margin</span>
                                    <span class="font-weight-700 text-${s.harga_jual_aktif - s.harga_beli_aktif > 0 ? 'success' : 'danger'}">${formatCurrency(s.harga_jual_aktif - s.harga_beli_aktif, 0)}</span>
                                </div>
                                <div class="text-muted mt-1" style="font-size:9px;">Berlaku: ${effAt}</div>
                            </div>
                        </div>
                    </div>`;
                });
                $('#active-price-row').html(html);
            }
        });
    }
    @endif

    // --- Audit Log Tab ---
    $('#tab-audit').on('shown.bs.tab', function() {
        $.getJSON("{{ route('price-audit-logs.index') }}", function(logs) {
            if (!logs.length) {
                $('#audit-log-body').html('<tr><td colspan="9" class="text-center text-muted">Belum ada log perubahan harga.</td></tr>');
                return;
            }
            const actionBadge = { CREATE: 'success', CREATE_FROM_REPORT: 'info', UPDATE: 'warning', DELETE: 'danger' };
            const actionLabel = { CREATE: 'Tambah', CREATE_FROM_REPORT: 'Input Laporan', UPDATE: 'Ubah', DELETE: 'Hapus' };
            let rows = logs.map((l, i) => {
                const ts = new Date(l.created_at).toLocaleString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});
                const badge = actionBadge[l.action] || 'secondary';
                const label = actionLabel[l.action] || l.action;
                return `<tr>
                    <td>${i + 1}</td>
                    <td style="font-size:12px;">${ts}</td>
                    <td>${l.shop}</td>
                    <td><span class="badge badge-${badge}">${label}</span></td>
                    <td>${l.user}</td>
                    @if(Auth::user()->role !== 'operator')
                    <td class="text-right">${l.harga_beli_lama ? formatNumber(l.harga_beli_lama) : '—'}</td>
                    @endif
                    <td class="text-right">${l.harga_jual_lama ? formatNumber(l.harga_jual_lama) : '—'}</td>
                    @if(Auth::user()->role !== 'operator')
                    <td class="text-right font-weight-700">${formatNumber(l.harga_beli_baru)}</td>
                    @endif
                    <td class="text-right font-weight-700 text-success">${formatNumber(l.harga_jual_baru)}</td>
                </tr>`;
            }).join('');
            $('#audit-log-body').html(rows);
        });
    });

    @if(Auth::user()->role !== 'operator')
    renderActivePrices();
    @endif
});

// Tab nav styling
$('.nav-link').on('shown.bs.tab', function() {
    $('.nav-link').css({'border-bottom':'3px solid transparent', 'color':'#64748b', 'font-weight':'600'});
    $(this).css({'border-bottom':'3px solid #00796B', 'color':'#0f172a'});
});
</script>

<style>
    .col-lg-2-4 { flex: 0 0 20%; max-width: 20%; }
    @media (max-width: 991px) { .col-lg-2-4 { flex: 0 0 50%; max-width: 50%; } }
    @media (max-width: 575px) { .col-lg-2-4 { flex: 0 0 100%; max-width: 100%; } }
    #table-prices thead th, #table-audit thead th { font-weight: 600; font-size: 12px; color: #475569; white-space: nowrap; }
    .nav-tabs .nav-link { border-radius: 0; padding: 10px 18px; }
    .nav-tabs .nav-link.active { background: #fff; border-bottom: 3px solid #00796B !important; color: #0f172a !important; }
</style>
@endpush
