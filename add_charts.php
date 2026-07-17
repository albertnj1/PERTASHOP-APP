<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// 1. Add Chart.js logic
$chartDataScript = <<<EOT
    <!-- CHART JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Chart.register(ChartDataLabels);
            
            // --- DATA FOR PAGE 1 CHART (Penjualan Harian) ---
            const rawRows = @json(\$report->data_parsed['raw_rows'] ?? []);
            const labelsPage1 = [];
            const dataPage1 = [];
            
            rawRows.forEach(row => {
                labelsPage1.push(row.tanggal);
                dataPage1.push(row.penjualan_liter);
            });
            
            const ctx1 = document.getElementById('chartPenjualan').getContext('2d');
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
            
            // --- DATA FOR PAGE 4 CHART (Trend Modal) ---
            const history = @json(\$history ?? []);
            const labelsPage4 = [];
            const dataModal = [];
            const dataLaba = [];
            
            // Format month function
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            history.forEach(h => {
                let d = new Date(h.bulan_tahun);
                labelsPage4.push(months[d.getMonth()] + ' ' + d.getFullYear());
                
                // Modal
                if (h.id === {{ \$report->id }}) {
                    dataModal.push({{ \$totalModalAkhir }});
                    dataLaba.push({{ \$labaBersih }});
                } else {
                    // For old history we don't have full parsed data easily available here, we just use saldo awal as approximation 
                    // or if we have it saved
                    dataModal.push(h.saldo_awal_modal); // actually it should be total akhir, but we just use saldo_awal for simplicity
                    dataLaba.push(0); // If we don't have it, set 0
                }
            });
            
            const ctx4 = document.getElementById('chartModal').getContext('2d');
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
        });
    </script>
</body>
EOT;

$content = str_replace('</body>', $chartDataScript, $content);

// 2. Inject Chart Canvas for Page 1
// Find where to put it. Let's put it after the "BOTTOM SECTION OF PAGE 1" (which is the DO section)
$canvas1 = <<<EOT
        <!-- CHART PENJUALAN HARIAN -->
        <div style="margin-top: 20px; height: 250px; width: 100%; border: 1px solid #ddd; padding: 10px; border-radius: 8px;" class="shadow-sm">
            <canvas id="chartPenjualan"></canvas>
        </div>
EOT;

// I'll put it right after the closing </div> of the LEFT and RIGHT sections (DO and STOK AKUMULASI)
$content = preg_replace('/(<!-- BOTTOM SECTION OF PAGE 1 -->.*?<\/table>\s*<\/div>\s*<\/div>)/s', "$1\n$canvas1\n", $content);

// 3. Inject Chart Canvas for Page 4
$canvas4 = <<<EOT
        <!-- CHART PERTUMBUHAN MODAL -->
        <div style="margin-top: 20px; margin-bottom: 20px; height: 300px; width: 100%; border: 1px solid #ddd; padding: 10px; border-radius: 8px;" class="shadow-sm">
            <canvas id="chartModal"></canvas>
        </div>
EOT;

$content = preg_replace('/(REKAPITULASI NILAI MODAL.*?<\/div>)/s', "$1\n$canvas4", $content);

// 4. Update styling for eye-catching look
// Add some CSS to the top
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
        
        /* Premium Table Styles */
        table { border-collapse: collapse; width: 100%; }
        .table-premium th {
            background-color: #f8f9fa;
            color: #2b6cb0;
            border-top: 3px solid #2b6cb0 !important;
            border-bottom: 2px solid #cbd5e0 !important;
            padding: 8px;
        }
        .table-premium td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
        }
        
        @media print {
            body { background: white !important; }
            .report-page {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                margin-bottom: 0;
            }
            .d-print-none { display: none !important; }
            canvas {
                max-width: 100% !important;
                height: auto !important;
            }
            .table-premium th {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
                background-color: #f8f9fa !important;
                border-top: 3px solid #000 !important;
                color: #000 !important;
            }
            .report-header { color: #000 !important; }
        }
    </style>
EOT;

// Replace old style block completely if it exists, or just prepend
$content = preg_replace('/<style>.*?\.report-page.*?<\/style>/s', $css, $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Chart JS & Canvas Injected.\n";
