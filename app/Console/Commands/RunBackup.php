<?php

namespace App\Console\Commands;

use App\Services\BackupStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RunBackup extends Command
{
    protected $signature = 'backup:run
        {--path= : Directory where backup folders are stored}
        {--keep-days= : How many days of old backups to keep}';

    protected $description = 'Create a database, public storage, and private uploads backup without external shell dump tools.';

    public function handle(BackupStorageService $storage): int
    {
        try {
            return app(\App\Services\BackupWriteBarrier::class)->run(fn () => $this->createBackup($storage), exclusive: true);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }

    private function createBackup(BackupStorageService $storage): int
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
        $reviewImagesTar = $workDir . DIRECTORY_SEPARATOR . 'storage-private-review-images.tar';
        $reviewImagesTarGz = $workDir . DIRECTORY_SEPARATOR . 'storage-private-review-images.tar.gz';
        $manifestFile = $workDir . DIRECTORY_SEPARATOR . 'manifest.json';
        $checksumFile = $workDir . DIRECTORY_SEPARATOR . 'SHA256SUMS';
        $ownsWorkDir = false;

        try {
            if (file_exists($workDir) || file_exists($targetDir)) {
                throw new \RuntimeException('Backup destination already exists; retry with a new timestamp.');
            }

            if (! is_dir($workDir) && ! mkdir($workDir, 0755, true) && ! is_dir($workDir)) {
                throw new \RuntimeException('Не удалось создать папку backup: ' . $workDir);
            }
            $ownsWorkDir = true;

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
            $reviewImagesStats = $storage->archiveDirectory(
                (string) config('filesystems.disks.review_images.root'),
                'private/review-images',
                $reviewImagesTar,
                $reviewImagesTarGz
            );
            $this->writeManifest($manifestFile, $publicStats, $privateStats, $reviewImagesStats, $databaseStats);
            $this->writeChecksums($checksumFile, [
                $databaseGz,
                $storageTarGz,
                $privateTarGz,
                $reviewImagesTarGz,
                $manifestFile,
            ]);

            if (! rename($workDir, $targetDir)) {
                throw new \RuntimeException('Не удалось завершить backup: временная папка не переименована.');
            }

            $this->removeOldBackups($backupPath, $keepDays);

            $this->info('Backup created: ' . $targetDir);

            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($ownsWorkDir && is_dir($workDir)) {
                try {
                    $this->removeDirectory($workDir);
                } catch (Throwable $cleanup) {
                    $this->error('Backup cleanup failed; inspect ' . $workDir . ': ' . $cleanup->getMessage());
                }
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

            $this->writeStream($handle, "-- WebVitrina database backup\n");
            $this->writeStream($handle, "-- Created at: " . now()->toDateTimeString() . "\n");
            $this->writeStream($handle, "-- Database: " . $database . "\n\n");
            $this->writeStream($handle, "SET NAMES utf8mb4;\n");
            $this->writeStream($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($tables as $table) {
                $this->dumpTable($pdo, $handle, $table);
                if (in_array($table, ['users', 'shops', 'products', 'categories', 'orders', 'reviews', 'ad_campaigns', 'conversations', 'messages'], true)) {
                    $rowCounts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM ' . $this->quoteIdentifier($table))->fetchColumn();
                }
            }

            $this->writeStream($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
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

        $this->writeStream($handle, "\nDROP TABLE IF EXISTS {$quotedTable};\n");
        $this->writeStream($handle, $createSql . ";\n\n");

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

        $this->writeStream($handle, 'INSERT INTO ' . $quotedTable . ' (' . $quotedColumns . ') VALUES' . "\n");
        $this->writeStream($handle, implode(",\n", $values) . ";\n");
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
            if (is_resource($input)) { fclose($input); }
            if (is_resource($output)) { gzclose($output); }
            throw new \RuntimeException('Не удалось открыть файл для gzip-сжатия.');
        }

        try {
            while (! feof($input)) {
                $chunk = fread($input, 1024 * 1024);
                if ($chunk === false || gzwrite($output, $chunk) !== strlen($chunk)) {
                    throw new \RuntimeException('Incomplete database gzip write.');
                }
            }
        } finally {
            fclose($input);
            if (! gzclose($output)) {
                throw new \RuntimeException('Cannot finalize database gzip.');
            }
        }
    }

    private function writeStream(mixed $handle, string $contents): void
    {
        if (fwrite($handle, $contents) !== strlen($contents)) {
            throw new \RuntimeException('Incomplete database dump write.');
        }
    }

    private function writeChecksums(string $checksumFile, array $files): void
    {
        $lines = [];

        foreach ($files as $file) {
            $hash = hash_file('sha256', $file);
            if ($hash === false) {
                throw new \RuntimeException('Cannot hash backup file: ' . $file);
            }
            $lines[] = $hash . '  ' . basename($file);
        }

        $this->writeFile($checksumFile, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    private function writeManifest(
        string $manifestFile,
        array $publicStats,
        array $privateStats,
        array $reviewImagesStats,
        array $databaseStats,
    ): void
    {
        $this->writeFile($manifestFile, json_encode([
            'version' => 3,
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
                'private_review_images' => [
                    'archive' => 'storage-private-review-images.tar.gz',
                    'root' => 'private/review-images',
                    ...$reviewImagesStats,
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    protected function writeFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) !== strlen($contents)) {
            throw new \RuntimeException('Cannot write complete backup file: ' . $path);
        }
    }

    private function removeOldBackups(string $backupPath, int $keepDays): void
    {
        if ($keepDays <= 0) {
            return;
        }
        $root = $this->checkedDirectory($backupPath);
        $cutoff = now()->subDays($keepDays)->getTimestamp();
        foreach ($this->directoryEntries($root) as $entry) {
            if (! $this->isBackupName($entry)) {
                continue;
            }
            $path = $root . DIRECTORY_SEPARATOR . $entry;
            try {
                $this->checkedDirectory($path);
                $modified = filemtime($path);
                if ($modified === false) {
                    throw new \RuntimeException('Cannot read directory modification time.');
                }
                if ($modified >= $cutoff) {
                    continue;
                }
                $files = $this->checkedBackupFiles($path);
                $this->removeFlatDirectory($root, $path, $files);
            } catch (Throwable $exception) {
                $this->error('Backup retention skipped ' . $entry . ': ' . $exception->getMessage());
            }
        }
    }

    private function isBackupName(string $name): bool
    {
        if (! preg_match('/\A[0-9]{8}-[0-9]{6}\z/', $name)) {
            return false;
        }
        $date = \DateTimeImmutable::createFromFormat('!Ymd-His', $name);
        return $date !== false && $date->format('Ymd-His') === $name;
    }

    private function pathKey(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        return PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
    }

    private function checkedDirectory(string $path): string
    {
        clearstatcache(true);
        if ($path === '' || str_contains($path, "\0")
            || ! preg_match('~^(?:/(?!/)|[A-Za-z]:[/\\\\])~', $path)
            || preg_match('~(?:^|[/\\\\])\.{1,2}(?:[/\\\\]|$)~', $path)) {
            throw new \RuntimeException('Backup cleanup requires an absolute path without traversal.');
        }
        $real = realpath($path);
        if ($real === false || ! is_dir($path) || is_link($path)
            || $this->pathKey($path) !== $this->pathKey($real)) {
            throw new \RuntimeException('Backup cleanup rejects missing directories, symlinks and aliases.');
        }
        if ($this->pathKey($real) === '' || preg_match('/\A[a-z]:\z/i', $this->pathKey($real))
            || $this->pathKey($real) === $this->pathKey(base_path())) {
            throw new \RuntimeException('Backup cleanup rejects filesystem and project roots.');
        }
        for ($ancestor = $real; dirname($ancestor) !== $ancestor; $ancestor = dirname($ancestor)) {
            if (is_link($ancestor)) {
                throw new \RuntimeException('Backup cleanup rejects linked ancestors.');
            }
        }
        return $real;
    }

    private function directoryEntries(string $path): array
    {
        $entries = @scandir($path);
        if ($entries === false) {
            throw new \RuntimeException('Cannot list backup directory.');
        }
        return array_values(array_diff($entries, ['.', '..']));
    }

    private function checkedFile(string $path): void
    {
        clearstatcache(true, $path);
        $stat = @lstat($path);
        if ($stat === false || ($stat['mode'] & 0170000) !== 0100000 || is_link($path)
            || $this->pathKey(realpath($path) ?: '') !== $this->pathKey($path)) {
            throw new \RuntimeException('Backup cleanup requires regular, unlinked files.');
        }
    }

    private function checkedBackupFiles(string $path): array
    {
        $files = $this->directoryEntries($path);
        $allowed = ['database.sql.gz', 'storage-public.tar.gz', 'storage-private-chat-images.tar.gz',
            'storage-private-review-images.tar.gz', 'manifest.json', 'SHA256SUMS'];
        if (array_diff($files, $allowed) !== [] || ! in_array('manifest.json', $files, true)) {
            throw new \RuntimeException('Unrecognized backup contents; nothing deleted.');
        }
        foreach ($files as $file) {
            $this->checkedFile($path . DIRECTORY_SEPARATOR . $file);
        }
        $contents = @file_get_contents($path . '/manifest.json');
        if ($contents === false) {
            throw new \RuntimeException('Cannot read backup manifest.');
        }
        $manifest = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        $version = $manifest['version'] ?? null;
        if (! in_array($version, [2, 3], true)) {
            throw new \RuntimeException('Unknown backup manifest version.');
        }
        $roots = ['public' => 'public', 'private_chat_images' => 'private/chat-images'];
        if ($version === 3) {
            $roots['private_review_images'] = 'private/review-images';
        } else {
            $allowed = array_values(array_diff($allowed, ['storage-private-review-images.tar.gz']));
        }
        if (array_diff($allowed, $files) !== [] || array_diff($files, $allowed) !== []) {
            throw new \RuntimeException('Incomplete or unexpected backup file set.');
        }
        foreach ($roots as $key => $root) {
            $archive = 'storage-' . str_replace('/', '-', $root) . '.tar.gz';
            if (($manifest['storage'][$key]['archive'] ?? null) !== $archive
                || ($manifest['storage'][$key]['root'] ?? null) !== $root) {
                throw new \RuntimeException('Unrecognized backup manifest storage layout.');
            }
        }
        $checksums = @file($path . '/SHA256SUMS', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($checksums === false) {
            throw new \RuntimeException('Cannot read backup checksums.');
        }
        $seen = [];
        foreach ($checksums as $line) {
            if (! preg_match('/\A([a-f0-9]{64})  ([a-zA-Z0-9.-]+)\z/', $line, $match)
                || ! in_array($match[2], $allowed, true) || $match[2] === 'SHA256SUMS'
                || isset($seen[$match[2]])) {
                throw new \RuntimeException('Invalid backup checksum entry.');
            }
            $file = $path . DIRECTORY_SEPARATOR . $match[2];
            $this->checkedFile($file);
            $hash = @hash_file('sha256', $file);
            if ($hash === false || ! hash_equals($match[1], $hash) || filesize($file) <= 0) {
                throw new \RuntimeException('Backup checksum mismatch or unreadable/empty file.');
            }
            $seen[$match[2]] = true;
        }
        if (array_diff($allowed, ['SHA256SUMS'], array_keys($seen)) !== []) {
            throw new \RuntimeException('Incomplete backup checksums.');
        }
        return $files;
    }

    /** No recursive traversal, even for cleanup of our failed temporary backup. */
    private function removeDirectory(string $directory): void
    {
        $root = $this->checkedDirectory(dirname($directory));
        $this->checkedDirectory($directory);
        $name = basename($directory);
        if (! str_ends_with($name, '.tmp') || ! $this->isBackupName(substr($name, 0, -4))) {
            throw new \RuntimeException('Refusing cleanup of an unrecognized temporary backup.');
        }
        $files = $this->directoryEntries($directory);
        $allowed = ['database.sql', 'database.sql.gz', 'manifest.json', 'SHA256SUMS'];
        foreach (['storage-public', 'storage-private-chat-images', 'storage-private-review-images'] as $archive) {
            $allowed[] = $archive . '.tar';
            $allowed[] = $archive . '.tar.gz';
        }
        if (array_diff($files, $allowed) !== []) {
            throw new \RuntimeException('Unexpected files in temporary backup; cleanup skipped.');
        }
        $this->removeFlatDirectory($root, $directory, $files);
    }

    private function removeFlatDirectory(string $root, string $directory, array $files): void
    {
        $check = function () use ($root, $directory): void {
            $this->checkedDirectory($root);
            $real = $this->checkedDirectory($directory);
            if ($this->pathKey(dirname($real)) !== $this->pathKey($root)) {
                throw new \RuntimeException('Backup deletion target escaped its parent directory.');
            }
        };
        $check();
        // Validate the whole flat set before the first deletion.
        foreach ($files as $file) {
            $this->checkedFile($directory . DIRECTORY_SEPARATOR . $file);
        }
        foreach ($files as $file) {
            $check();
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            $this->checkedFile($path);
            if (! @unlink($path)) {
                throw new \RuntimeException('Cannot delete backup file: ' . $file);
            }
        }
        $check();
        if (! @rmdir($directory)) {
            throw new \RuntimeException('Cannot remove backup directory; inspect remaining contents.');
        }
    }
}
