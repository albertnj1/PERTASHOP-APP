<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Command
{
    /**
     * Nama dan signature dari perintah console.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * Deskripsi dari perintah console.
     *
     * @var string
     */
    protected $description = 'Otomatisasi backup database MySQL ke folder storage/app/backups/';

    /**
     * Jalankan perintah console.
     */
    public function handle()
    {
        $this->info('Memulai proses backup database...');

        $databaseName = config('database.connections.mysql.database');
        $username     = config('database.connections.mysql.username');
        $password     = config('database.connections.mysql.password');
        $host         = config('database.connections.mysql.host');
        $port         = config('database.connections.mysql.port', 3306);

        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup_' . $databaseName . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        // Coba jalankan mysqldump jika ketersediaan CLI mysqldump ada
        $mysqldumpPath = 'mysqldump';
        // Cek path XAMPP default jika di Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (file_exists('C:\xampp\mysql\bin\mysqldump.exe')) {
                $mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';
            }
        }

        $command = sprintf(
            '"%s" --user="%s" --password="%s" --host="%s" --port="%s" "%s" > "%s"',
            $mysqldumpPath,
            $username,
            $password,
            $host,
            $port,
            $databaseName,
            $filePath
        );

        $returnVar = null;
        $output = [];
        exec($command, $output, $returnVar);

        if ($returnVar === 0 && file_exists($filePath) && filesize($filePath) > 0) {
            $sizeKb = round(filesize($filePath) / 1024, 2);
            $this->info("✅ Backup database berhasil diselesaikan!");
            $this->info("📁 Lokasi: {$filePath} ({$sizeKb} KB)");
            return Command::SUCCESS;
        }

        // Fallback PHP Exporter jika mysqldump tidak tersedia di sistem PATH
        $this->warn('mysqldump CLI tidak merespon, menggunakan metode PHP Export fallback...');
        $tables = DB::select('SHOW TABLES');
        $sqlScript = "-- PERTASHOP DATABASE BACKUP\n-- Date: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $tableObj) {
            $tableName = array_values((array)$tableObj)[0];
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sqlScript .= array_values((array)$createTable[0])[1] . ";\n\n";

            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($val) {
                    if (is_null($val)) return 'NULL';
                    return DB::getPdo()->quote($val);
                }, (array)$row);
                $sqlScript .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sqlScript .= "\n";
        }
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

        file_put_contents($filePath, $sqlScript);
        $sizeKb = round(filesize($filePath) / 1024, 2);

        $this->info("✅ Backup database via PHP Export berhasil!");
        $this->info("📁 Lokasi: {$filePath} ({$sizeKb} KB)");

        return Command::SUCCESS;
    }
}
