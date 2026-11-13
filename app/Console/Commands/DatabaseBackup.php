<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Otomatisasi backup database ke folder storage/app/backups/';

    public function handle()
    {
        $this->info('Memulai proses backup database...');

        $connection   = config('database.default');
        $databaseName = config("database.connections.{$connection}.database");
        $username     = config("database.connections.{$connection}.username");
        $password     = config("database.connections.{$connection}.password");
        $host         = config("database.connections.{$connection}.host");
        $port         = config("database.connections.{$connection}.port", $connection === 'pgsql' ? 5432 : 3306);

        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup_' . $databaseName . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        if ($connection === 'pgsql') {
            return $this->backupPostgres($host, $port, $username, $password, $databaseName, $filePath);
        }

        return $this->backupMysql($host, $port, $username, $password, $databaseName, $filePath);
    }

    protected function backupPostgres($host, $port, $username, $password, $databaseName, $filePath)
    {
        $pgDumpPath = 'pg_dump';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && file_exists('C:\Program Files\PostgreSQL\18\bin\pg_dump.exe')) {
            $pgDumpPath = 'C:\Program Files\PostgreSQL\18\bin\pg_dump.exe';
        }

        if (!empty($password)) {
            putenv("PGPASSWORD={$password}");
        }

        $command = sprintf(
            '"%s" --host="%s" --port="%s" --username="%s" --no-owner --no-acl -f "%s" "%s"',
            $pgDumpPath,
            $host,
            $port,
            $username,
            $filePath,
            $databaseName
        );

        $returnVar = null;
        $output = [];
        exec($command, $output, $returnVar);
        putenv('PGPASSWORD');

        if ($returnVar === 0 && file_exists($filePath) && filesize($filePath) > 0) {
            $sizeKb = round(filesize($filePath) / 1024, 2);
            $this->info("Backup database PostgreSQL berhasil diselesaikan!");
            $this->info("Lokasi: {$filePath} ({$sizeKb} KB)");
            return Command::SUCCESS;
        }

        $this->warn('pg_dump CLI tidak merespon, menggunakan metode PHP Export fallback...');
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
        $sqlScript = "-- PERTASHOP POSTGRESQL DATABASE BACKUP\n-- Date: " . date('Y-m-d H:i:s') . "\n\nSET session_replication_role = replica;\n\n";

        foreach ($tables as $tableObj) {
            $tableName = $tableObj->tablename;
            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $arr = (array) $row;
                $cols = implode(', ', array_map(fn($c) => "\"{$c}\"", array_keys($arr)));
                $values = array_map(function ($val) {
                    if (is_null($val)) return 'NULL';
                    if (is_bool($val)) return $val ? 'TRUE' : 'FALSE';
                    return DB::getPdo()->quote($val);
                }, array_values($arr));
                $sqlScript .= "INSERT INTO \"{$tableName}\" ({$cols}) VALUES (" . implode(', ', $values) . ");\n";
            }
            $sqlScript .= "\n";
        }
        $sqlScript .= "SET session_replication_role = DEFAULT;\n";

        file_put_contents($filePath, $sqlScript);
        $sizeKb = round(filesize($filePath) / 1024, 2);

        $this->info("Backup database via PHP Export berhasil!");
        $this->info("Lokasi: {$filePath} ({$sizeKb} KB)");

        return Command::SUCCESS;
    }

    protected function backupMysql($host, $port, $username, $password, $databaseName, $filePath)
    {
        $mysqldumpPath = 'mysqldump';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && file_exists('C:\xampp\mysql\bin\mysqldump.exe')) {
            $mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';
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
            $this->info("Backup database berhasil diselesaikan!");
            $this->info("Lokasi: {$filePath} ({$sizeKb} KB)");
            return Command::SUCCESS;
        }

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

        $this->info("Backup database via PHP Export berhasil!");
        $this->info("Lokasi: {$filePath} ({$sizeKb} KB)");

        return Command::SUCCESS;
    }
}