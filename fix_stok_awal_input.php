<?php
$file = 'resources/views/monthly_reports/create.blade.php';
$content = file_get_contents($file);

$stokAwalHtml = '
                        <div class="col-md-6 mb-3">
                            <label>Sisa Stok Bulan Sebelumnya (Liter) <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" name="stok_awal_fisik" class="form-control" value="{{ $lastStokAktual ?? 0 }}" required>
                            <small class="text-muted">Masukkan sisa stok fisik di tangki dari akhir bulan sebelumnya.</small>
                        </div>
';

// Insert after totalisator_awal
$search = '<div class="col-md-6 mb-3">
                            <label>Totalisator Awal (Opsional)</label>';
$content = str_replace($search, $stokAwalHtml . "\n                        " . $search, $content);

file_put_contents($file, $content);
echo "Done adding stok_awal_fisik to create.blade.php\n";
