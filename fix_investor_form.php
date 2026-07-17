<?php
$file = 'resources/views/monthly_reports/create.blade.php';
$content = file_get_contents($file);

$investorHtml = '
                <hr>
                <h5 class="mb-3">Pembagian Laba Bersih (Profit Sharing)</h5>
                <p class="text-muted small">Masukkan nama investor dan persentase pembagian laba bersih (misal: 70 untuk 70%).</p>
                <div id="investor-container">
                    <div class="row mb-2 investor-row">
                        <div class="col-md-7">
                            <input type="text" name="investor_nama[]" class="form-control" placeholder="Nama Investor (Contoh: PT SAM)">
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="0.01" name="investor_persen[]" class="form-control" placeholder="Persentase (%)">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-remove-investor" disabled><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-success mt-2" id="btn-add-investor">
                    <i class="fas fa-plus"></i> Tambah Investor
                </button>

                <div class="mt-4">
';

$content = str_replace('<div class="mt-4">', $investorHtml, $content);

$investorJs = "
    // Dynamic Investor Table
    const invContainer = document.getElementById('investor-container');
    const btnAddInv = document.getElementById('btn-add-investor');

    btnAddInv.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'row mb-2 investor-row';
        row.innerHTML = `
            <div class=\"col-md-7\">
                <input type=\"text\" name=\"investor_nama[]\" class=\"form-control\" placeholder=\"Nama Investor\">
            </div>
            <div class=\"col-md-4\">
                <input type=\"number\" step=\"0.01\" name=\"investor_persen[]\" class=\"form-control\" placeholder=\"Persentase (%)\">
            </div>
            <div class=\"col-md-1\">
                <button type=\"button\" class=\"btn btn-danger btn-remove-investor\"><i class=\"fas fa-trash\"></i></button>
            </div>
        `;
        invContainer.appendChild(row);
        
        row.querySelector('.btn-remove-investor').addEventListener('click', function() {
            row.remove();
        });
    });
";

$content = str_replace('// Dynamic BBM Table', $investorJs . "\n    // Dynamic BBM Table", $content);

file_put_contents($file, $content);
echo "Done modifying create.blade.php\n";
