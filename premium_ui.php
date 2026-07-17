<?php
$file = 'resources/views/monthly_reports/show.blade.php';
$content = file_get_contents($file);

// 1. UPDATE CSS
$oldCss = <<<EOT
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

$newCss = <<<EOT
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    
    body {
        background-color: #f8fafc; /* Very light slate for premium app feel */
    }
    
    .report-page {
        background: white;
        padding: 50px;
        margin-bottom: 40px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        border-radius: 16px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        page-break-after: always;
        position: relative;
        border: 1px solid #f1f5f9;
    }
    
    .report-header {
        text-align: center;
        font-weight: 700;
        font-size: 18px;
        margin-bottom: 25px;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Premium Table Enhancements */
    .table-premium { border-collapse: collapse; width: 100%; margin-bottom: 1rem; color: #334155; }
    .table-premium th, .table-premium td { border: 1px solid #e2e8f0; padding: 10px 12px; vertical-align: middle; }
    
    /* Premium Header Gradient overrides */
    .header-blue-gradient {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%) !important;
        color: white !important;
        border: none !important;
    }
    .header-red-gradient {
        background: linear-gradient(135deg, #991b1b 0%, #ef4444 100%) !important;
        color: white !important;
        border: none !important;
    }
    .header-green-gradient {
        background: linear-gradient(135deg, #166534 0%, #22c55e 100%) !important;
        color: white !important;
        border: none !important;
    }
    
    .table-premium th {
        font-weight: 600;
    }
    
    .table-premium tbody tr {
        transition: all 0.2s ease;
    }
    .table-premium tbody tr:hover {
        background-color: #f8fafc;
        transform: scale(1.002);
    }
    
    /* Badge styling */
    .badge-up { background-color: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 9999px; font-weight: 600; font-size: 11px; }
    .badge-down { background-color: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 9999px; font-weight: 600; font-size: 11px; }
    
    @media print {
        body { background: white !important; }
        .report-page {
            box-shadow: none !important;
            border-radius: 0 !important;
            border: none !important;
            padding: 0 !important;
            margin-bottom: 0 !important;
            font-family: 'Times New Roman', Times, serif; /* Revert to formal font for print */
            font-size: 12px;
        }
        .d-print-none { display: none !important; }
        canvas {
            max-width: 100% !important;
            height: auto !important;
        }
        .header-blue-gradient, .header-red-gradient, .header-green-gradient {
            background: #f8f9fa !important;
            color: #000 !important;
            border: 1px solid #000 !important;
        }
        .table-premium tbody tr:hover { transform: none; background-color: transparent; }
        .table-premium th, .table-premium td { border: 1px solid #000; }
    }
</style>
EOT;

if (strpos($content, '<style>') !== false) {
    $content = preg_replace('/<style>.*?<\/style>/s', $newCss, $content);
} else {
    $content = str_replace('<div class="report-page">', $newCss . "\n<div class=\"report-page\">", $content);
}

// 2. UPDATE GRADIENT CLASSES
$content = str_replace('style="background-color: #2b6cb0;"', 'class="header-blue-gradient"', $content);
$content = str_replace('style="background-color: #e53e3e;"', 'class="header-red-gradient"', $content);
$content = str_replace('style="background-color: #38a169;"', 'class="header-green-gradient"', $content);
$content = str_replace('class="text-center fw-bold text-white" class="header-', 'class="text-center fw-bold text-white header-', $content);
$content = str_replace('class="fw-bold text-white" class="header-', 'class="fw-bold text-white header-', $content);
$content = str_replace('class="fw-bold text-white header-green-gradient" style="font-size: 14px;"', 'class="fw-bold text-white header-green-gradient" style="font-size: 15px; letter-spacing: 0.5px;"', $content);

// 3. UPDATE CHART JS FOR PREMIUM LOOK
$oldChart1 = <<<EOT
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
EOT;

$newChart1 = <<<EOT
                    type: 'bar',
                    data: {
                        labels: labelsPage1,
                        datasets: [{
                            label: 'Penjualan (Liter)',
                            data: dataPage1,
                            backgroundColor: function(context) {
                                const chart = context.chart;
                                const {ctx, chartArea} = chart;
                                if (!chartArea) return null;
                                let gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)'); // blue-500
                                gradient.addColorStop(1, 'rgba(30, 64, 175, 0.9)');  // blue-800
                                return gradient;
                            },
                            borderWidth: 0,
                            borderRadius: 6,
                            barPercentage: 0.7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            onComplete: function() {}
                        }, 
                        plugins: {
                            legend: { display: false },
                            title: {
                                display: true,
                                text: 'GRAFIK TREN PENJUALAN HARIAN (LITER)',
                                font: { family: "'Inter', sans-serif", size: 15, weight: 'bold' },
                                color: '#1e293b',
                                padding: { bottom: 20 }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: Math.round,
                                font: { size: 10, weight: 'bold', family: "'Inter', sans-serif" },
                                color: '#64748b'
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false, drawBorder: false },
                                ticks: { font: { family: "'Inter', sans-serif", size: 11 }, color: '#64748b' }
                            },
                            y: { 
                                beginAtZero: true,
                                grid: { color: '#f1f5f9', borderDash: [5, 5], drawBorder: false },
                                ticks: { font: { family: "'Inter', sans-serif", size: 11 }, color: '#64748b' }
                            }
                        }
                    }
EOT;
$content = str_replace($oldChart1, $newChart1, $content);

$oldChart4 = <<<EOT
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
                                suggestedMin: Math.min(...dataModal) > 0 ? Math.min(...dataModal) * 0.9 : 0,
                                suggestedMax: Math.max(...dataModal) * 1.1,
                                ticks: {
                                    // Set precision agar tidak ada tick desimal berulang
                                    precision: 0,
                                    callback: function(value, index, values) { 
                                        return 'Rp ' + (value/1000000).toFixed(1) + 'M'; 
                                    }
                                }
                            }
                        }
                    }
EOT;

$newChart4 = <<<EOT
                    type: 'line',
                    data: {
                        labels: labelsPage4,
                        datasets: [
                            {
                                label: 'Total Modal Akhir',
                                data: dataModal,
                                borderColor: '#22c55e', // green-500
                                backgroundColor: function(context) {
                                    const chart = context.chart;
                                    const {ctx, chartArea} = chart;
                                    if (!chartArea) return null;
                                    let gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                    gradient.addColorStop(0, 'rgba(34, 197, 94, 0.05)'); 
                                    gradient.addColorStop(1, 'rgba(34, 197, 94, 0.4)');
                                    return gradient;
                                },
                                borderWidth: 3,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#16a34a',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                tension: 0.4, // smooth curves
                                fill: true,
                                yAxisID: 'y'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: { display: false },
                            title: {
                                display: true,
                                text: 'TREN PERTUMBUHAN MODAL',
                                font: { family: "'Inter', sans-serif", size: 15, weight: 'bold' },
                                color: '#1e293b',
                                padding: { bottom: 20 }
                            },
                            datalabels: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                titleFont: { family: "'Inter', sans-serif", size: 13 },
                                bodyFont: { family: "'Inter', sans-serif", size: 14, weight: 'bold' },
                                padding: 12,
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        let value = context.raw || 0;
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false, drawBorder: false },
                                ticks: { font: { family: "'Inter', sans-serif", size: 11 }, color: '#64748b' }
                            },
                            y: { 
                                beginAtZero: false,
                                suggestedMin: Math.min(...dataModal) > 0 ? Math.min(...dataModal) * 0.9 : 0,
                                suggestedMax: Math.max(...dataModal) * 1.1,
                                grid: { color: '#f1f5f9', borderDash: [5, 5], drawBorder: false },
                                ticks: {
                                    precision: 0,
                                    font: { family: "'Inter', sans-serif", size: 11 }, color: '#64748b',
                                    callback: function(value) { return 'Rp ' + (value/1000000).toFixed(1) + 'M'; }
                                }
                            }
                        }
                    }
EOT;
$content = str_replace($oldChart4, $newChart4, $content);

// 4. Badge styling Update
$content = str_replace('<span style="color:red">{!! $arrow !!} Rp {{ number_format(abs($selisih), 0, \',\', \'.\') }}</span>', '<span class="badge-up">{!! $arrow !!} Rp {{ number_format(abs($selisih), 0, \',\', \'.\') }}</span>', $content);
$content = str_replace('<span style="color:{{$color}}">{!! $arrow !!} Rp {{ number_format(abs($selisih), 0, \',\', \'.\') }}</span>', '<span class="{{ $isNaik ? \'badge-up\' : \'badge-down\' }}">{!! $arrow !!} Rp {{ number_format(abs($selisih), 0, \',\', \'.\') }}</span>', $content);

file_put_contents($file, $content);
echo "Premium UI successfully applied.";
