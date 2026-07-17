<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// Fix extends
$content = preg_replace('/\{!! \$__env->make\(\'layouts\.app\'.*?\)->render\(\) !!}/s', '', $content);
$content = "@extends('layouts.app')\n" . $content;

// Fix includes
$content = preg_replace('/\{!! \$__env->make\(\'monthly_reports\.signature\'.*?\)->render\(\) !!}/s', "@include('monthly_reports.signature')", $content);

// Add charts precisely
$chartDataScript = <<<EOT
    <!-- CHART JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof ChartDataLabels !== 'undefined') {
                Chart.register(ChartDataLabels);
            }
            
            // --- DATA FOR PAGE 1 CHART (Penjualan Harian) ---
            const rawRows = @json(\$report->data_parsed['raw_rows'] ?? []);
            const labelsPage1 = [];
            const dataPage1 = [];
            
            if (rawRows && Array.isArray(rawRows)) {
                rawRows.forEach(row => {
                    labelsPage1.push(row.tanggal);
                    dataPage1.push(row.penjualan_liter);
                });
            }
            
            const canvas1 = document.getElementById('chartPenjualan');
            if (canvas1) {
                const ctx1 = canvas1.getContext('2d');
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: labelsPage1,
                        datasets: [{
                            label: 'Penjualan (Liter)',
                            data: dataPage1,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 0 }, // Disable animation for printing
                        plugins: {
                            legend: { display: false },
                            title: {
                                display: true,
                                text: 'Grafik Tren Penjualan Harian (Liter)',
                                font: { family: "'Times New Roman', Times, serif", size: 14 }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: Math.round,
                                font: { size: 9 }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
            
            // --- DATA FOR PAGE 4 CHART (Trend Modal) ---
            const history = @json(\$history ?? []);
            const labelsPage4 = [];
            const dataModal = [];
            
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            if (history && Array.isArray(history)) {
                history.forEach(h => {
                    let d = new Date(h.bulan_tahun);
                    labelsPage4.push(months[d.getMonth()] + ' ' + d.getFullYear());
                    
                    if (h.id === {{ \$report->id }}) {
                        dataModal.push({{ \$totalModalAkhir ?? 0 }});
                    } else {
                        dataModal.push(h.saldo_awal_modal);
                    }
                });
            }
            
            const canvas4 = document.getElementById('chartModal');
            if (canvas4) {
                const ctx4 = canvas4.getContext('2d');
                new Chart(ctx4, {
                    type: 'line',
                    data: {
                        labels: labelsPage4,
                        datasets: [
                            {
                                label: 'Total Modal Akhir',
                                data: dataModal,
                                borderColor: 'rgba(13, 110, 253, 1)',
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true,
                                yAxisID: 'y'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 0 },
                        plugins: {
                            legend: { position: 'bottom' },
                            title: {
                                display: true,
                                text: 'Tren Pertumbuhan Modal',
                                font: { family: "'Times New Roman', Times, serif", size: 14 }
                            },
                            datalabels: { display: false }
                        },
                        scales: {
                            y: { 
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) { return 'Rp ' + (value/1000000).toFixed(1) + 'M'; }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
EOT;

$content = str_replace('@endsection', $chartDataScript . "\n@endsection", $content);

// Inject Canvas 1
$canvas1 = <<<EOT
        <!-- CHART PENJUALAN HARIAN -->
        <div style="margin-top: 20px; height: 300px; width: 100%; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; background-color: #f8f9fa;" class="shadow-sm d-print-table-row">
            <canvas id="chartPenjualan"></canvas>
        </div>
EOT;
$content = preg_replace('/(<!-- BOTTOM TABLE \(Margin\) -->.*?<\/table>\s*<\/div>\s*<\/div>)/s', "$1\n$canvas1\n", $content);

// Inject Canvas 4
$canvas4 = <<<EOT
        <!-- CHART PERTUMBUHAN MODAL -->
        <div style="margin-top: 20px; margin-bottom: 20px; height: 300px; width: 100%; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; background-color: #f8f9fa;" class="shadow-sm d-print-table-row">
            <canvas id="chartModal"></canvas>
        </div>
EOT;
$content = preg_replace('/(REKAPITULASI NILAI MODAL.*?<\/div>)/s', "$1\n$canvas4", $content);

// Apply Premium Styling correctly (using str_replace to be absolutely safe instead of greedy preg_replace)
$content = str_replace('<table class="table-bordered mt-4">', '<table class="table-premium mt-4">', $content);
$content = str_replace('<td colspan="4" class="text-center fw-bold bg-light">PENGHASILAN</td>', '<td colspan="4" class="text-center fw-bold text-white" style="background-color: #2b6cb0;">PENGHASILAN</td>', $content);
$content = str_replace('<td colspan="4" class="text-center fw-bold bg-light">PENGELUARAN</td>', '<td colspan="4" class="text-center fw-bold text-white" style="background-color: #e53e3e;">PENGELUARAN</td>', $content);
$content = str_replace('<td colspan="2" class="fw-bold bg-light">TOTAL PENGHASILAN</td>', '<td colspan="2" class="fw-bold" style="background-color: #ebf8ff; color: #2b6cb0;">TOTAL PENGHASILAN</td>', $content);
$content = str_replace('<td colspan="2" class="fw-bold bg-light">TOTAL PENGELUARAN</td>', '<td colspan="2" class="fw-bold" style="background-color: #fff5f5; color: #e53e3e;">TOTAL PENGELUARAN</td>', $content);
$content = str_replace('<td colspan="2" class="fw-bold bg-light">LABA KOTOR (A-B)</td>', '<td colspan="2" class="fw-bold text-white" style="background-color: #38a169;">LABA KOTOR (A-B)</td>', $content);
$content = str_replace('<td colspan="2" class="fw-bold bg-light" style="font-size: 14px;">LABA BERSIH (C-D)</td>', '<td colspan="2" class="fw-bold text-white" style="background-color: #38a169; font-size: 14px;">LABA BERSIH (C-D)</td>', $content);
$content = str_replace('<thead class="bg-light text-center">', '<thead class="text-center text-white" style="background-color: #2b6cb0;">', $content);

// Inject CSS
$css = <<<EOT
<style>
    .report-page {
        background: white;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-radius: 10px;
        font-family: 'Times New Roman', Times, serif;
        font-size: 12px;
        page-break-after: always;
        position: relative;
    }
    .report-header {
        text-align: center;
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 20px;
        color: #1a365d;
        text-transform: uppercase;
    }
    .table-premium { border-collapse: collapse; width: 100%; margin-bottom: 1rem; color: #212529; }
    .table-premium th, .table-premium td { border: 1px solid #dee2e6; padding: 0.75rem; vertical-align: top; }
    .table-premium th {
        background-color: #f8f9fa;
        color: #2b6cb0;
        border-top: 3px solid #2b6cb0 !important;
        border-bottom: 2px solid #cbd5e0 !important;
    }
    @media print {
        body { background: white !important; }
        .report-page {
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin-bottom: 0 !important;
        }
        .d-print-none { display: none !important; }
        canvas {
            max-width: 100% !important;
            height: auto !important;
        }
        .table-premium th {
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
    }
</style>
EOT;
$content = preg_replace('/<style>.*?\.report-page.*?<\/style>/s', $css, $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Final application completed successfully.";
