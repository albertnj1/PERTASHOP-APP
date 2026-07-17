<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

// Fix 1: Aggregate Page 1 Data by Date
$oldPage1Js = <<<EOT
            if (rawRows && Array.isArray(rawRows)) {
                rawRows.forEach(row => {
                    labelsPage1.push(row.tanggal);
                    dataPage1.push(row.penjualan_liter);
                });
            }
EOT;

$newPage1Js = <<<EOT
            if (rawRows && Array.isArray(rawRows)) {
                const aggregated = {};
                rawRows.forEach(row => {
                    let tgl = parseInt(row.tanggal);
                    // Validasi: hanya ambil tanggal 1-31
                    if (!isNaN(tgl) && tgl >= 1 && tgl <= 31) {
                        if (!aggregated[tgl]) aggregated[tgl] = 0;
                        aggregated[tgl] += parseFloat(row.penjualan_liter) || 0;
                    }
                });
                
                // Masukkan ke array chart
                Object.keys(aggregated).forEach(key => {
                    labelsPage1.push(key);
                    dataPage1.push(aggregated[key]);
                });
            }
EOT;

$content = str_replace($oldPage1Js, $newPage1Js, $content);

// Fix 2: Chart Modal Y-Axis Repeated Labels
$oldYAxisJs = <<<EOT
                        scales: {
                            y: { 
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) { return 'Rp ' + (value/1000000).toFixed(1) + 'M'; }
                                }
                            }
                        }
EOT;

$newYAxisJs = <<<EOT
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
EOT;

$content = str_replace($oldYAxisJs, $newYAxisJs, $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Chart JS bugs fixed.";
