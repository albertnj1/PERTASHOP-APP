<?php

namespace App\Services\Validation;

use Illuminate\Support\Carbon;

/**
 * BusinessValidator — implements ValidatorInterface
 *
 * Mengecek aturan bisnis matematis per-baris data.
 * Threshold dibaca dari $context->businessConfig (berasal dari config/validation.php).
 *
 * Error codes: CAL001, CAL002, CAL004, CAL006, CAL008, CAL009
 * Note: CAL010 (Shift Consistency) dijadwalkan Fase C — membutuhkan data shift.
 */
class BusinessValidator implements ValidatorInterface
{
    public function name(): string { return 'Business'; }
    public function isCriticalValidator(): bool { return true; }

    public function validate(ValidationContext $context): array
    {
        $messages = [];
        $registry = app(\App\Services\Registry\BusinessRuleRegistryService::class);
        $firstDate = $context->firstRow()['tanggal'] ?? null;

        $maxVolumePerDay  = (float) $registry->getValue('MAX_DAILY_VOLUME', $firstDate, 15000.0);
        $extremeIncomeMin = (float) $registry->getValue('EXTREME_INCOME_MIN', $firstDate, -5000000.0);
        $extremeIncomeMax = 300000000.0;

        // CAL006 — Duplikat tanggal dalam file
        $seenDates = [];
        $dupDates  = [];
        foreach ($context->parsedRows as $i => $row) {
            $tgl = $row['tanggal'];
            if (in_array($tgl, $seenDates)) {
                $dupDates[] = Carbon::parse($tgl)->format('d M Y');
            } else {
                $seenDates[] = $tgl;
            }
        }
        if (!empty($dupDates)) {
            $messages[] = ValidationMessage::make(
                ValidationCode::DUPLICATE_DATE,
                '[CAL006] ' . count($dupDates) . ' tanggal duplikat dalam file: '
                . implode(', ', array_unique($dupDates)) . '. Baris duplikat akan di-skip.',
                module:  $this->name(),
                context: ['duplicate_dates' => array_unique($dupDates)]
            );
        }

        $criticalCount = 0;
        $skala = (float) ($context->shop->skala ?? 1);

        foreach ($context->parsedRows as $i => $row) {
            $tanggalLabel = Carbon::parse($row['tanggal'])->format('d M Y');
            $toliAwal     = (float) ($row['totalisator_awal']  ?? 0);
            $toliAkhir    = (float) ($row['totalisator_akhir'] ?? 0);
            $volSistem    = (float) ($row['vol_sistem']        ?? 0);
            $stikAwal     = (float) ($row['stik_awal']         ?? 0);
            $penerimaan   = (float) ($row['penerimaan']        ?? 0);
            $bbmLain      = (float) ($row['bbm_keluar_lain']   ?? 0);
            $testPump     = (float) ($row['test_pump']         ?? 0);
            $rupiahSistem = (float) ($row['rupiah_sistem']     ?? 0);

            // CAL001 — Totalisator Balik/Invalid (Critical)
            if ($toliAwal > 0 && $toliAkhir < $toliAwal) {
                $messages[] = ValidationMessage::make(
                    ValidationCode::TOTALISATOR_INVALID,
                    sprintf('[CAL001] %s: Totalisator akhir (%.3f) < awal (%.3f). Selisih: %.3f L.',
                        $tanggalLabel, $toliAkhir, $toliAwal, $toliAwal - $toliAkhir),
                    module:  $this->name(),
                    row:     $i,
                    field:   'totalisator_akhir',
                    context: ['tanggal' => $row['tanggal'], 'toli_awal' => $toliAwal, 'toli_akhir' => $toliAkhir]
                );
                $criticalCount++;
            }

            // CAL002 — Volume Negatif (Critical)
            if ($volSistem < 0) {
                $messages[] = ValidationMessage::make(
                    ValidationCode::VOLUME_NEGATIVE,
                    sprintf('[CAL002] %s: Volume penjualan aktual (%.3f L) negatif.', $tanggalLabel, $volSistem),
                    module:  $this->name(),
                    row:     $i,
                    field:   'vol_sistem',
                    context: ['tanggal' => $row['tanggal'], 'vol_aktual' => $volSistem, 'test_pump' => $testPump]
                );
                $criticalCount++;
            }

            // CAL004 — Stok Teoritis Negatif (Warning)
            $stokAwal     = $stikAwal * $skala;
            $stokTeoritis = $stokAwal + $penerimaan - $bbmLain - $volSistem;
            if ($stokTeoritis < 0) {
                $messages[] = ValidationMessage::make(
                    ValidationCode::STOCK_NEGATIVE,
                    sprintf('[CAL004] %s: Stok teoritis (%.3f L) negatif. Periksa penerimaan atau stik awal.',
                        $tanggalLabel, $stokTeoritis),
                    module:  $this->name(),
                    row:     $i,
                    field:   'stok_teoritis',
                    context: ['tanggal' => $row['tanggal'], 'stok_awal' => $stokAwal, 'penerimaan' => $penerimaan, 'stok_teoritis' => $stokTeoritis]
                );
            }

            // CAL008 — Volume Terlalu Besar (Warning, Outlier)
            if ($volSistem > $maxVolumePerDay) {
                $messages[] = ValidationMessage::make(
                    ValidationCode::VOLUME_TOO_LARGE,
                    sprintf('[CAL008] %s: Volume (%.3f L) melebihi batas harian wajar (%.0f L).',
                        $tanggalLabel, $volSistem, $maxVolumePerDay),
                    module:  $this->name(),
                    row:     $i,
                    field:   'vol_sistem',
                    context: ['tanggal' => $row['tanggal'], 'vol_aktual' => $volSistem, 'max_volume' => $maxVolumePerDay]
                );
            }

            // CAL009 — Pendapatan Rupiah Ekstrem (Warning, Financial Outlier)
            if ($rupiahSistem < $extremeIncomeMin || $rupiahSistem > $extremeIncomeMax) {
                $messages[] = ValidationMessage::make(
                    ValidationCode::INCOME_EXTREME,
                    sprintf('[CAL009] %s: Rupiah penjualan (Rp %s) di luar batas normal.',
                        $tanggalLabel, number_format($rupiahSistem, 0, ',', '.')),
                    module:  $this->name(),
                    row:     $i,
                    field:   'rupiah_sistem',
                    context: ['tanggal' => $row['tanggal'], 'rupiah' => $rupiahSistem, 'batas_min' => $extremeIncomeMin, 'batas_max' => $extremeIncomeMax]
                );
            }
        }

        // Success message jika tidak ada Critical
        if ($criticalCount === 0 && $context->rowCount() > 0) {
            $messages[] = ValidationMessage::success(
                ValidationCode::TOTALISATOR_INVALID,
                '✓ Semua ' . $context->rowCount() . ' baris data lolos validasi bisnis (totalisator & volume valid).',
                module: $this->name(),
            );
        }

        return $messages;
    }
}
