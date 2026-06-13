<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class RunBackup extends Command
{
    protected $signature = 'backup:run
        {--path= : Directory where backup folders are stored}
        {--keep-days= : How many days of old backups to keep}';

    protected $description = 'Create a database and public storage backup without relying on external shell dump tools.';

    public function handle(): int
    {
        $backupPath = (string) ($this->option('path') ?: config('backup.path'));
        $keepDays = (int) ($this->option('keep-days') ?: config('backup.keep_days', 14));
        $stamp = now()->format('Ymd-His');
        $targetDir = rtrim($backupPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $stamp;
        $workDir = $targetDir . '.tmp';

        $databaseSql = $workDir . DIRECTORY_SEPARATOR . 'database.sql';
        $databaseGz = $workDir . DIRECTORY_SEPARATOR . 'database.sql.gz';
        $storageTar = $workDir . DIRECTORY_SEPARATOR . 'storage-public.tar';
        $storageTarGz = $workDir . DIRECTORY_SEPARATOR . 'storage-public.tar.gz';
        $manifestFile = $workDir . DIRECTORY_SEPARATOR . 'manifest.json';
        $checksumFile = $workDir . DIRECTORY_SEPARATOR . 'SHA256SUMS';

        try {
            if (is_dir($workDir)) {
                $this->removeDirectory($workDir);
            }

            if (! is_dir($workDir) && ! mkdir($workDir, 0755, true) && ! is_dir($workDir)) {
                throw new \RuntimeException('Не удалось создать папку backup: ' . $workDir);
            }

            $this->dumpDatabase($databaseSql);
            $this->gzipFile($databaseSql, $databaseGz);
            @unlink($databaseSql);

            $this->archivePublicStorage($storageTar, $storageTarGz);
            $this->writeManifest($manifestFile);
            $this->writeChecksums($checksumFile, [$databaseGz, $storageTarGz, $manifestFile]);
            $this->removeOldBackups($backupPath, $keepDays);

            if (! rename($workDir, $targetDir)) {
                throw new \RuntimeException('Не удалось завершить backup: временная папка не переименована.');
            }

            $this->info('Backup created: ' . $targetDir);

            return self::SUCCESS;
        } catch (Throwable $e) {
            if (is_dir($workDir)) {
                $this->removeDirectory($workDir);
            }

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function dumpDatabase(string $outputPath): void
    {
        $pdo = DB::connection()->getPdo();
        $database = DB::connection()->getDatabaseName();

        if ($database === '') {
            throw new \RuntimeException('DB_DATABASE не задан.');
        }

        $handle = fopen($outputPath, 'wb');

        if (! $handle) {
            throw new \RuntimeException('Не удалось создать файл дампа БД.');
        }

        try {
            fwrite($handle, "-- WebVitrina database backup\n");
            fwrite($handle, "-- Created at: " . now()->toDateTimeString() . "\n");
            fwrite($handle, "-- Database: " . $database . "\n\n");
            fwrite($handle, "SET NAMES utf8mb4;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($this->databaseTables($pdo, $database) as $table) {
                $this->dumpTable($pdo, $handle, $table);
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }

        if (! is_file($outputPath) || (filesize($outputPath) ?: 0) <= 0) {
            throw new \RuntimeException('Дамп БД не создан или пустой.');
        }
    }

    private function databaseTables(\PDO $pdo, string $database): array
    {
        $statement = $pdo->prepare(
            'select table_name from information_schema.tables where table_schema = ? and table_type = ? order by table_name'
        );
        $statement->execute([$database, 'BASE TABLE']);

        return $statement->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    private function dumpTable(\PDO $pdo, mixed $handle, string $table): void
    {
        $quotedTable = $this->quoteIdentifier($table);
        $createStatement = $pdo->query('SHOW CREATE TABLE ' . $quotedTable);
        $createRow = $createStatement?->fetch(\PDO::FETCH_ASSOC);
        $createSql = $createRow['Create Table'] ?? null;

        if (! is_string($createSql) || $createSql === '') {
            throw new \RuntimeException('Не удалось получить CREATE TABLE для ' . $table . '.');
        }

        fwrite($handle, "\nDROP TABLE IF EXISTS {$quotedTable};\n");
        fwrite($handle, $createSql . ";\n\n");

        $rows = $pdo->query('SELECT * FROM ' . $quotedTable, \PDO::FETCH_ASSOC);

        if (! $rows) {
            return;
        }

        $columns = null;
        $values = [];
        $batchSize = 100;

        foreach ($rows as $row) {
            if ($columns === null) {
                $columns = array_keys($row);
            }

            $values[] = '(' . implode(', ', array_map(fn ($value) => $this->quoteValue($pdo, $value), array_values($row))) . ')';

            if (count($values) >= $batchSize) {
                $this->writeInsertBatch($handle, $quotedTable, $columns, $values);
                $values = [];
            }
        }

        if ($columns !== null && $values !== []) {
            $this->writeInsertBatch($handle, $quotedTable, $columns, $values);
        }
    }

    private function writeInsertBatch(mixed $handle, string $quotedTable, array $columns, array $values): void
    {
        $quotedColumns = implode(', ', array_map(fn ($column) => $this->quoteIdentifier((string) $column), $columns));

        fwrite($handle, 'INSERT INTO ' . $quotedTable . ' (' . $quotedColumns . ') VALUES' . "\n");
        fwrite($handle, implode(",\n", $values) . ";\n");
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function quoteValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    }

    private function gzipFile(string $source, string $target): void
    {
        $input = fopen($source, 'rb');
        $output = gzopen($target, 'wb9');

        if (! $input || ! $output) {
            throw new \RuntimeException('Не удалось открыть файл для gzip-сжатия.');
        }

        while (! feof($input)) {
            gzwrite($output, fread($input, 1024 * 1024));
        }

        fclose($input);
        gzclose($output);
    }

    private function archivePublicStorage(string $tarPath, string $tarGzPath): void
    {
        if (is_file($tarPath)) {
            @unlink($tarPath);
        }

        if (is_file($tarGzPath)) {
            @unlink($tarGzPath);
        }

        $storagePublic = storage_path('app/public');
        $archive = new \PharData($tarPath);
        $archive->addEmptyDir('public');

        if (is_dir($storagePublic)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($storagePublic, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($storagePublic) + 1));
                $archivePath = 'public/' . $relativePath;

                if ($file->isDir()) {
                    $archive->addEmptyDir($archivePath);
                } else {
                    $archive->addFile($file->getPathname(), $archivePath);
                }
            }
        }

        $archive->compress(\Phar::GZ);
        unset($archive);

        @unlink($tarPath);

        if (! is_file($tarGzPath) || filesize($tarGzPath) === 0) {
            throw new \RuntimeException('Архив storage не создан или пустой.');
        }
    }

    private function writeChecksums(string $checksumFile, array $files): void
    {
        $lines = [];

        foreach ($files as $file) {
            $lines[] = hash_file('sha256', $file) . '  ' . basename($file);
        }

        file_put_contents($checksumFile, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    private function writeManifest(string $manifestFile): void
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $database = $connection->getDatabaseName();
        $tables = $this->databaseTables($pdo, $database);
        $importantTables = [
            'users',
            'shops',
            'products',
            'categories',
            'orders',
            'reviews',
            'ad_campaigns',
            'conversations',
            'messages',
        ];
        $rowCounts = [];

        foreach ($importantTables as $table) {
            if (in_array($table, $tables, true)) {
                $rowCounts[$table] = (int) $pdo
                    ->query('SELECT COUNT(*) FROM ' . $this->quoteIdentifier($table))
                    ->fetchColumn();
            }
        }

        file_put_contents($manifestFile, json_encode([
            'created_at' => now()->toIso8601String(),
            'database' => $database,
            'tables_total' => count($tables),
            'row_counts' => $rowCounts,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function removeOldBackups(string $backupPath, int $keepDays): void
    {
        if ($keepDays <= 0 || ! is_dir($backupPath)) {
            return;
        }

        $cutoff = now()->subDays($keepDays)->getTimestamp();

        foreach (scandir($backupPath) ?: [] as $entry) {
            if (in_array($entry, ['.', '..'], true)) {
                continue;
            }

            $path = rtrim($backupPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path) && (filemtime($path) ?: time()) < $cutoff) {
                $this->removeDirectory($path);
            }
        }
    }

    private function removeDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
