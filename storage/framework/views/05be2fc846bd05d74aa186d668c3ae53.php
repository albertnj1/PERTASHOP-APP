<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Generate Laporan Bulanan</h1>
        <a href="<?php echo e(route('monthly-reports.index')); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="<?php echo e(route('monthly-reports.store')); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="shop_id" class="form-label">Pertashop</label>
                        <select name="shop_id" id="shop_id" class="form-select" required>
                            <option value="">-- Pilih Pertashop --</option>
                            <?php $__currentLoopData = $shops; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($shop->id); ?>"><?php echo e($shop->nama); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="bulan_tahun" class="form-label">Periode Bulan & Tahun</label>
                        <input type="month" name="bulan_tahun" id="bulan_tahun" class="form-control" required>
                    </div>

                    <div class="col-md-12 mt-4">
                        <h5 class="mb-3 text-primary"><i class="fas fa-wallet"></i> Posisi Modal Kerja & Laba Sebelumnya</h5>
                        <div class="card bg-light border-0">
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="saldo_awal_modal" class="form-label">Saldo Awal Modal</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="saldo_awal_modal" id="saldo_awal_modal" class="form-control" value="<?php echo e(number_format(old('saldo_awal_modal', $lastSaldoModal), 0, '', '')); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="sisa_do_volume" class="form-label">Sisa DO Pertamina (Liter)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="sisa_do_volume" id="sisa_do_volume" class="form-control" value="<?php echo e(old('sisa_do_volume', 0)); ?>" required>
                                            <span class="input-group-text">L</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="kas_kecil" class="form-label">Kas Kecil</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="kas_kecil" id="kas_kecil" class="form-control" value="<?php echo e(old('kas_kecil', 0)); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="piutang" class="form-label">Piutang</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="piutang" id="piutang" class="form-control" value="<?php echo e(old('piutang', 0)); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="bunga_bank" class="form-label">Bunga Bank</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="bunga_bank" id="bunga_bank" class="form-control" value="<?php echo e(old('bunga_bank', 0)); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="pajak_bank" class="form-label">Pajak Bank</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="pajak_bank" id="pajak_bank" class="form-control" value="<?php echo e(old('pajak_bank', 0)); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="penyusutan_modal" class="form-label">Penyusutan Karena Rugi</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="penyusutan_modal" id="penyusutan_modal" class="form-control" value="<?php echo e(old('penyusutan_modal', 0)); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="penambahan_modal" class="form-label">Penambahan Dari Alokasi Keuntungan</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="penambahan_modal" id="penambahan_modal" class="form-control" value="<?php echo e(old('penambahan_modal', 0)); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="harga_beli_pertamax" class="form-label">Harga Beli Pertamax</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="harga_beli_pertamax" id="harga_beli_pertamax" class="form-control" value="<?php echo e(old('harga_beli_pertamax', 0)); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label for="saldo_laba_sebelumnya" class="form-label">Saldo Laba Sebelumnya yang Belum Dibagi (Undistributed Profit Carryover)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" name="saldo_laba_sebelumnya" id="saldo_laba_sebelumnya" class="form-control" value="<?php echo e(old('saldo_laba_sebelumnya', 0)); ?>" required>
                                        </div>
                                        <small class="text-muted">Laba bersih yang sengaja ditahan dari bulan sebelumnya dan belum dibagikan ke investor.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-12 mb-3 mt-3">
                        <div class="alert alert-info">
                            <i class="fas fa-magic"></i> Laporan bulanan ini akan di-<strong>generate secara otomatis</strong> oleh sistem berdasarkan data Laporan Harian, Penerimaan BBM, Test Pump, dan Pengeluaran yang sudah diinput oleh Operator selama bulan tersebut.
                        </div>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="font-weight-bold text-primary"><i class="fas fa-file-excel"></i> Fitur Testing: Upload Excel Manual</h6>
                                <p class="text-muted small mb-2">Jika Anda ingin menimpa data database dengan data dari Excel (khusus testing), silakan upload file Excel Laporan Bulanan di sini.</p>
                                <input type="file" name="excel_file" id="excel_file" class="form-control form-control-sm" accept=".xlsx, .xls">
                                <div id="excel-preview" class="mt-2 text-success small d-none"><i class="fas fa-check-circle"></i> File Excel berhasil dibaca oleh sistem (Client-side).</div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <h5 class="mb-3">Rincian Pengeluaran</h5>
                <p class="text-muted small">Tambahkan pengeluaran khusus seperti Gaji Operator, Listrik, Internet, dll. yang tampil di Halaman 2.</p>
                <div id="pengeluaran-container">
                    <div class="row mb-2 pengeluaran-row">
                        <div class="col-md-7">
                            <input type="text" name="pengeluaran_ket[]" class="form-control" placeholder="Keterangan Pengeluaran">
                        </div>
                        <div class="col-md-4">
                            <input type="text" inputmode="decimal" name="pengeluaran_nom[]" class="form-control" placeholder="Nominal (Rp)">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-remove-pengeluaran" disabled><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-add-pengeluaran">
                    <i class="fas fa-plus"></i> Tambah Pengeluaran
                </button>

                
                <hr>
                <h5 class="mb-3">Pembagian Laba Bersih (Profit Sharing)</h5>
                <p class="text-muted small">Masukkan nama investor dan persentase pembagian laba bersih (misal: 70 untuk 70%).</p>
                <div id="investor-container">
                    <!-- Investor rows will be auto-populated here via JS -->
                </div>
                <button type="button" class="btn btn-sm btn-outline-success mt-2" id="btn-add-investor">
                    <i class="fas fa-plus"></i> Tambah Investor
                </button>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-cogs"></i> Generate Laporan Bulanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic Pengeluaran Table
    const container = document.getElementById('pengeluaran-container');
    const btnAdd = document.getElementById('btn-add-pengeluaran');

    btnAdd.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'row mb-2 pengeluaran-row';
        row.innerHTML = `
            <div class="col-md-7">
                <input type="text" name="pengeluaran_ket[]" class="form-control" placeholder="Keterangan Pengeluaran">
            </div>
            <div class="col-md-4">
                <input type="text" inputmode="decimal" name="pengeluaran_nom[]" class="form-control" placeholder="Nominal (Rp)">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-remove-pengeluaran"><i class="fas fa-trash"></i></button>
            </div>
        `;
        container.appendChild(row);
        
        row.querySelector('.btn-remove-pengeluaran').addEventListener('click', function() {
            row.remove();
        });
    });

    
    // Dynamic Investor Table
    const invContainer = document.getElementById('investor-container');
    const btnAddInv = document.getElementById('btn-add-investor');
    
    // Pass shops data to JS
    const shopsData = <?php echo json_encode($shops, 15, 512) ?>;
    const shopSelect = document.getElementById('shop_id');

    function addInvestorRow(nama = '', persen = '') {
        const row = document.createElement('div');
        row.className = 'row mb-2 investor-row';
        row.innerHTML = `
            <div class="col-md-7">
                <input type="text" name="investor_nama[]" class="form-control" placeholder="Nama Investor" value="${nama}">
            </div>
            <div class="col-md-4">
                <input type="number" step="0.01" name="investor_persen[]" class="form-control" placeholder="Persentase (%)" value="${persen}">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-remove-investor"><i class="fas fa-trash"></i></button>
            </div>
        `;
        invContainer.appendChild(row);
        
        row.querySelector('.btn-remove-investor').addEventListener('click', function() {
            row.remove();
        });
    }

    btnAddInv.addEventListener('click', function() {
        addInvestorRow();
    });

    // Auto-fill investors when shop changes
    shopSelect.addEventListener('change', function() {
        const shopId = this.value;
        invContainer.innerHTML = ''; // Clear current investors
        
        if (shopId) {
            // Fetch modal details (saldo modal akhir and active price)
            fetch(`/shops/${shopId}/modal-details`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('saldo_awal_modal').value = Math.round(data.saldo_akhir_modal);
                    document.getElementById('harga_beli_pertamax').value = Math.round(data.harga_beli);
                })
                .catch(error => {
                    console.error('Error fetching modal details:', error);
                });

            const selectedShop = shopsData.find(s => s.id == shopId);
            if (selectedShop && selectedShop.investors && selectedShop.investors.length > 0) {
                selectedShop.investors.forEach(investor => {
                    const nama = investor.user ? (investor.user.name || investor.user.short_name) : '';
                    const persen = parseFloat(investor.pivot.persentase);
                    addInvestorRow(nama, persen);
                });
            } else {
                addInvestorRow(); // empty row if no investors
            }
        } else {
            addInvestorRow(); // empty row
        }
    });

    // Trigger change on load if a shop is already selected (e.g. from old input)
    if (shopSelect.value) {
        shopSelect.dispatchEvent(new Event('change'));
    }

    // Excel file reading for testing
    const excelInput = document.getElementById('excel_file');
    const excelPreview = document.getElementById('excel-preview');
    if(excelInput) {
        excelInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(!file) {
                excelPreview.classList.add('d-none');
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {type: 'array'});
                console.log("Excel Workbook:", workbook);
                excelPreview.innerHTML = `<i class="fas fa-check-circle"></i> File Excel <b>${file.name}</b> berhasil dibaca! Buka Console (F12) untuk melihat isinya.`;
                excelPreview.classList.remove('d-none');
            };
            reader.readAsArrayBuffer(file);
        });
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\Pertashop App_Laravel\sal-pertashop\resources\views/monthly_reports/create.blade.php ENDPATH**/ ?>