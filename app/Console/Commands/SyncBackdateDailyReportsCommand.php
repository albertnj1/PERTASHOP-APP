<?php

namespace App\Console\Commands;

use App\Models\BackdateExcelFile;
use App\Services\BackdateDailyReportIngestionService;
use App\Services\MonthlyReportCalculationService;
use Illuminate\Console\Command;

class SyncBackdateDailyReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backdate:sync-daily-reports {id? : ID BackdateExcelFile spesifik (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest & Sinkronkan baris-baris data dari berkas Excel Backdate ke tabel daily_reports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');

        if ($id) {
            $files = BackdateExcelFile::where('id', $id)->get();
            if ($files->isEmpty()) {
                $this->error("BackdateExcelFile dengan ID {$id} tidak ditemukan.");
                return 1;
            }
        } else {
            $files = BackdateExcelFile::orderBy('id', 'asc')->get();
        }

        $totalFiles = $files->count();
        $this->info("Memulai sinkronisasi {$totalFiles} berkas Excel Backdate ke tabel daily_reports...");

        $totalRowsImported = 0;
        $bar = $this->output->createProgressBar($totalFiles);
        $bar->start();

        foreach ($files as $file) {
            try {
                // Ingest daily reports
                $importedCount = BackdateDailyReportIngestionService::ingest($file);
                $totalRowsImported += $importedCount;

                // Sync monthly report calculation
                try {
                    MonthlyReportCalculationService::syncFromBackdateExcel($file);
                } catch (\Throwable $e) {}

            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("Gagal memproses file [{$file->original_filename}]: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("SELESAI: Berhasil memproses {$totalFiles} berkas Excel. Total {$totalRowsImported} baris data harian telah dipetakan ke tabel daily_reports.");

        return 0;
    }
}
