<?php

namespace App\Console\Commands;

use App\Services\BackupStorageService;
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

    protected $description = 'Create a database, public storage, and private chat uploads backup without external shell dump tools.';

    public function handle(BackupStorageService $storage): int
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
        $privateTar = $workDir . DIRECTORY_SEPARATOR . 'storage-private-chat-images.tar';
        $privateTarGz = $workDir . DIRECTORY_SEPARATOR . 'storage-private-chat-images.tar.gz';
        $manifestFile = $workDir . DIRECTORY_SEPARATOR . 'manifest.json';
        $checksumFile = $workDir . DIRECTORY_SEPARATOR . 'SHA256SUMS';

        try {
            if (is_dir($workDir)) {
                $this->removeDirectory($workDir);
            }

            if (! is_dir($workDir) && ! mkdir($workDir, 0755, true) && ! is_dir($workDir)) {
                throw new \RuntimeException('Не удалось создать папку backup: ' . $workDir);
            }

            $databaseStats = $this->dumpDatabase($databaseSql);
            $this->gzipFile($databaseSql, $databaseGz);
            @unlink($databaseSql);

            $publicStats = $storage->archiveDirectory(
                (string) config('filesystems.disks.public.root'),
                'public',
                $storageTar,
                $storageTarGz
            );
            $privateStats = $storage->archiveDirectory(
                rtrim((string) config('filesystems.disks.local.root'), '/\\') . DIRECTORY_SEPARATOR . 'chat-images',
                'private/chat-images',
                $privateTar,
                $privateTarGz
            );
            $this->writeManifest($manifestFile, $publicStats, $privateStats, $databaseStats);
            $this->writeChecksums($checksumFile, [$databaseGz, $storageTarGz, $privateTarGz, $manifestFile]);
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

    private function dumpDatabase(string $outputPath): array
    {
        // A fresh connection must not commit a caller's transaction or use a read replica.
        $config = DB::connection()->getConfig();
        $config['options'][\PDO::ATTR_PERSISTENT] = false;
        $connection = app('db.factory')->make($config);
        $pdo = $connection->getPdo();
        $database = $connection->getDatabaseName();

        if ($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new \RuntimeException('Consistent database backup requires MySQL/InnoDB.');
        }

        if ($database === '') {
            throw new \RuntimeException('DB_DATABASE не задан.');
        }

        $handle = fopen($outputPath, 'wb');

        if (! $handle) {
            throw new \RuntimeException('Не удалось создать файл дампа БД.');
        }

        try {
            $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
            $tables = $this->databaseTables($pdo, $database);
            $rowCounts = [];

            // Hold metadata locks until the transaction ends; ordinary DML remains allowed.
            foreach ($tables as $table) {
                $pdo->query('SELECT * FROM ' . $this->quoteIdentifier($table) . ' LIMIT 0')->closeCursor();
            }
            $engines = $pdo->prepare('SELECT table_name AS backup_table, engine AS backup_engine FROM information_schema.tables WHERE table_schema = ? AND table_type = ?');
            $engines->execute([$database, 'BASE TABLE']);
            foreach ($engines->fetchAll(\PDO::FETCH_ASSOC) as $entry) {
                if (strcasecmp((string) $entry['backup_engine'], 'InnoDB') !== 0) {
                    throw new \RuntimeException('Consistent backup requires InnoDB: ' . $entry['backup_table'] . ' uses ' . ($entry['backup_engine'] ?? 'unknown') . '.');
                }
            }

            fwrite($handle, "-- WebVitrina database backup\n");
            fwrite($handle, "-- Created at: " . now()->toDateTimeString() . "\n");
            fwrite($handle, "-- Database: " . $database . "\n\n");
            fwrite($handle, "SET NAMES utf8mb4;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($tables as $table) {
                $this->dumpTable($pdo, $handle, $table);
                if (in_array($table, ['users', 'shops', 'products', 'categories', 'orders', 'reviews', 'ad_campaigns', 'conversations', 'messages'], true)) {
                    $rowCounts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM ' . $this->quoteIdentifier($table))->fetchColumn();
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            $pdo->commit();
        } finally {
            try {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } finally {
                fclose($handle);
                $connection->disconnect();
            }
        }

        if (! is_file($outputPath) || (filesize($outputPath) ?: 0) <= 0) {
            throw new \RuntimeException('Дамп БД не создан или пустой.');
        }

        return ['database' => $database, 'tables_total' => count($tables), 'row_counts' => $rowCounts];
    }

    private function databaseTables(\PDO $pdo, string $database): array
    {
        $statement = $pdo->prepare(
            'select table_name from information_schema.tables where table_schema = ? and table_type = ? order by table_name'
        );
        $statement->execute([$database, 'BASE TABLE']);

        return $statement->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    protected function dumpTable(\PDO $pdo, mixed $handle, string $table): void
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

    private function writeChecksums(string $checksumFile, array $files): void
    {
        $lines = [];

        foreach ($files as $file) {
            $lines[] = hash_file('sha256', $file) . '  ' . basename($file);
        }

        file_put_contents($checksumFile, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    private function writeManifest(string $manifestFile, array $publicStats, array $privateStats, array $databaseStats): void
    {
        file_put_contents($manifestFile, json_encode([
            'version' => 2,
            'created_at' => now()->toIso8601String(),
            ...$databaseStats,
            'storage' => [
                'public' => [
                    'archive' => 'storage-public.tar.gz',
                    'root' => 'public',
                    ...$publicStats,
                ],
                'private_chat_images' => [
                    'archive' => 'storage-private-chat-images.tar.gz',
                    'root' => 'private/chat-images',
                    ...$privateStats,
                ],
            ],
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
