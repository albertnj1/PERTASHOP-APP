<?php

namespace App\Services\Validation;

use App\Models\DailyReport;
use App\Models\Price;
use Illuminate\Support\Carbon;

/**
 * ValidationPipeline
 *
 * Orkestrator Validation Engine yang bersifat 100% MODULAR (Open/Closed Principle).
 * Memproses array array dari ValidatorInterface[] yang di-inject.
 *
 * Keuntungan:
 *   - Menambah validator baru (misal CustomValidator / ShiftValidator) CUKUP
 *     mendaftarkannya ke constructor / AppServiceProvider — Pipeline TIDAK PERLU diubah!
 *   - Pipeline memuat ValidationContext sekali saja dan membagikannya ke seluruh validator.
 */
class ValidationPipeline
{
    /** @var ValidatorInterface[] */
    private array $validators;

    /**
     * @param ValidatorInterface[]|null $validators Array validator yang akan dijalankan berurutan.
     * @param DataQualityEngine|null $dataQualityEngine
     */
    public function __construct(
        ?array             $validators        = null,
        private readonly ?DataQualityEngine $dataQualityEngine = null
    ) {
        $this->validators = $validators ?? [
            app(InputValidator::class),
            app(CarryForwardValidator::class),
            app(BusinessValidator::class),
            app(PriceValidator::class),
            app(FormulaValidator::class),
        ];
    }

    /**
     * Pintu utama validasi.
     */
    public function validate(
        array   $parsedRows,
        mixed   $shop,
        array   $mappingConfig,
        ?string $sheetName = null,
        array   $uploadInfo = []
    ): ValidationResultDTO {
        // Pre-load data historis & context sekali di awal pipeline
        $previousReport = null;
        $activePrice    = null;

        if (!empty($parsedRows) && isset($parsedRows[0]['tanggal']) && $shop && isset($shop->id)) {
            $firstDate = Carbon::parse($parsedRows[0]['tanggal']);

            $previousReport = DailyReport::where('shop_id', $shop->id)
                ->whereDate('created_at', '<', $firstDate->toDateString())
                ->orderBy('created_at', 'desc')
                ->first();

            $activePrice = Price::where(function ($q) use ($shop) {
                    $q->where('shop_id', $shop->id)->orWhereNull('shop_id');
                })
                ->where('effective_at', '<=', $firstDate->toDateString())
                ->orderBy('effective_at', 'desc')
                ->first();
        }

        // Buat 1 ValidationContext immutable tunggal
        $context = new ValidationContext(
            shop:           $shop,
            parsedRows:     $parsedRows,
            mappingConfig:  $mappingConfig,
            sheetName:      $sheetName,
            previousReport: $previousReport,
            activePrice:    $activePrice,
            businessConfig: config('validation.business', []),
            uploadInfo:     $uploadInfo
        );

        $allMessages = [];

        // Jalankan semua validator yang terdaftar secara modular
        foreach ($this->validators as $validator) {
            $messages = $validator->validate($context);
            $allMessages = array_merge($allMessages, $messages);

            // Fail-fast strategy jika validator ini bersifat critical gating dan menghasilkan pesan Critical
            if ($validator->isCriticalValidator()) {
                $hasCritical = !empty(array_filter($messages, fn($m) => $m->isCritical()));
                if ($hasCritical) {
                    break;
                }
            }
        }

        // Hitung Data Quality Score
        $qualityEngine = $this->dataQualityEngine ?? app(DataQualityEngine::class);
        $quality = $qualityEngine->calculate($allMessages);

        return new ValidationResultDTO(
            messages:       $allMessages,
            score:          $quality['score'],
            scoreRating:    $quality['rating'],
            scoreBreakdown: $quality['breakdown']
        );
    }
}
