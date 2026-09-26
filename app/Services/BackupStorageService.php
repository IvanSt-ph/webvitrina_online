<?php

namespace App\Services;

use App\Support\BackupHealth;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BackupStorageService
{
    /**
     * @return array{files: int, bytes: int}
     */
    public function archiveDirectory(string $source, string $archiveRoot, string $tarPath, string $tarGzPath): array
    {
        @unlink($tarPath);
        @unlink($tarGzPath);

        $archive = new \PharData($tarPath);
        $archive->addEmptyDir($archiveRoot);
        $stats = ['files' => 0, 'bytes' => 0];

        if (is_dir($source)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isLink()) {
                    continue;
                }

                $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));
                $archivePath = $archiveRoot . '/' . $relativePath;

                if ($file->isDir()) {
                    $archive->addEmptyDir($archivePath);
                } else {
                    $archive->addFile($file->getPathname(), $archivePath);
                    $stats['files']++;
                    $stats['bytes'] += $file->getSize();
                }
            }
        }

        unset($archive);
        $this->gzipFile($tarPath, $tarGzPath);
        @unlink($tarPath);

        if (! is_file($tarGzPath) || (filesize($tarGzPath) ?: 0) <= 0) {
            throw new \RuntimeException('Архив storage не создан или пустой: ' . basename($tarGzPath));
        }

        return $stats;
    }

    private function gzipFile(string $source, string $target): void
    {
        $input = fopen($source, 'rb');
        $output = gzopen($target, 'wb9');

        if (! $input || ! $output) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                gzclose($output);
            }

            throw new \RuntimeException('Не удалось открыть storage archive для gzip-сжатия.');
        }

        try {
            while (! feof($input)) {
                $chunk = fread($input, 1024 * 1024);
                if ($chunk === false || gzwrite($output, $chunk) !== strlen($chunk)) {
                    throw new \RuntimeException('Не удалось сжать storage archive.');
                }
            }
        } finally {
            fclose($input);
            if (! gzclose($output)) {
                throw new \RuntimeException('Cannot finalize storage gzip.');
            }
        }
    }

    public function restore(string $backupDirectory, string $publicRoot, string $privateRoot): void
    {
        app(BackupWriteBarrier::class)->run(
            fn () => $this->restoreProtected($backupDirectory, $publicRoot, $privateRoot),
            exclusive: true,
        );
    }

    private function restoreProtected(string $backupDirectory, string $publicRoot, string $privateRoot): void
    {
        $health = BackupHealth::inspectDirectory($backupDirectory, true);
        if (! $health['ok']) {
            throw new \RuntimeException('Backup неполный или повреждён: ' . implode(' ', $health['issues']));
        }
        [$publicRoot, $privateRoot] = app(RestoreDestination::class)->storageRoots($publicRoot, $privateRoot, $backupDirectory);
        if (dirname($publicRoot) !== dirname($privateRoot) || $publicRoot === $privateRoot) {
            throw new \RuntimeException('Public и private storage должны иметь разные имена и общий родительский каталог.');
        }
        $stage = dirname($publicRoot) . DIRECTORY_SEPARATOR . '.backup-restore-' . Str::uuid();
        if (! mkdir($stage, 0700)) {
            throw new \RuntimeException('Cannot create restore staging directory: ' . $stage);
        }
        $roots = [
            ['live' => $publicRoot, 'new' => $stage . '/public', 'old' => $stage . '/.previous-public', 'saved' => false, 'installed' => false],
            ['live' => $privateRoot . '/chat-images', 'new' => $stage . '/private/chat-images', 'old' => $stage . '/.previous-chat-images', 'saved' => false, 'installed' => false],
        ];
        try {
            $this->extractArchive($backupDirectory . '/storage-public.tar.gz', $stage, 'public');
            $this->extractArchive($backupDirectory . '/storage-private-chat-images.tar.gz', $stage, 'private/chat-images');
            foreach ($roots as $root) {
                if (! is_dir($root['new'])) {
                    throw new \RuntimeException('Missing required archive directory: ' . $root['new']);
                }
            }
            if (! is_dir($privateRoot) && ! mkdir($privateRoot, 0750)) {
                throw new \RuntimeException('Cannot create private storage: ' . $privateRoot);
            }
            foreach ($roots as &$root) {
                if (file_exists($root['live']) || is_link($root['live'])) {
                    $this->moveDirectory($root['live'], $root['old']);
                    $root['saved'] = true;
                }
                $this->moveDirectory($root['new'], $root['live']);
                $root['installed'] = true;
            }
            unset($root);
            $this->applyPrivatePermissions($privateRoot . '/chat-images');
        } catch (\Throwable $original) {
            unset($root);
            $failures = [];
            foreach (array_reverse($roots) as $root) {
                try {
                    // Move new files aside; preserve the old copy until rollback succeeds.
                    if ($root['installed']) {
                        $this->moveDirectory($root['live'], $root['new']);
                    }
                    if ($root['saved']) {
                        $this->moveDirectory($root['old'], $root['live']);
                    }
                } catch (\Throwable $rollback) {
                    $failures[] = $root['live'] . ': ' . $rollback->getMessage();
                }
            }
            if ($failures !== []) {
                throw new \RuntimeException($original->getMessage() . '; ROLLBACK FAILED. Preserve recovery directory ' . $stage
                    . '; restore .previous-public / .previous-chat-images to the reported roots after stopping writers. '
                    . implode('; ', $failures), 0, $original);
            }
            try {
                $this->removeDirectory($stage);
            } catch (\Throwable $cleanup) {
                throw new \RuntimeException($original->getMessage() . '; rollback completed, cleanup failed at ' . $stage . ': ' . $cleanup->getMessage(), 0, $original);
            }
            throw $original;
        }
        // Commit point: cleanup failure must not roll back partially removed old copies.
        try {
            $this->removeDirectory($stage);
        } catch (\Throwable $cleanup) {
            throw new \RuntimeException('Restore installed successfully, but cleanup failed. Keep active storage; inspect ' . $stage . ': ' . $cleanup->getMessage(), 0, $cleanup);
        }
    }

    protected function moveDirectory(string $source, string $target): void
    {
        if (! rename($source, $target)) {
            throw new \RuntimeException('Cannot rename ' . $source . ' to ' . $target);
        }
    }
    protected function extractArchive(string $archivePath, string $destination, string $requiredRoot): void
    {
        $archive = new \PharData($archivePath);
        $prefix = str_replace('\\', '/', realpath($archivePath)) . '/';
        $foundRoot = false;

        foreach (new RecursiveIteratorIterator($archive, RecursiveIteratorIterator::SELF_FIRST) as $file) {
            if ($file->isLink()) {
                throw new \RuntimeException('Archive links are not permitted.');
            }
            $path = str_replace('\\', '/', $file->getPathname());
            $entry = str_starts_with($path, 'phar://') ? substr($path, strlen('phar://')) : $path;
            $entry = str_starts_with($entry, $prefix) ? substr($entry, strlen($prefix)) : basename($entry);
            $entry = ltrim($entry, '/');

            if ($entry === $requiredRoot || str_starts_with($entry, $requiredRoot . '/')) {
                $foundRoot = true;
            } elseif (str_starts_with($requiredRoot, $entry . '/')) {
                // Parent directory implicitly added by PharData for a nested archive root.
            } else {
                throw new \RuntimeException('Архив содержит неожиданный путь: ' . $entry);
            }

            if (str_contains('/' . $entry . '/', '/../') || str_starts_with($entry, '/')) {
                throw new \RuntimeException('Архив содержит небезопасный путь: ' . $entry);
            }
        }

        if (! $foundRoot) {
            throw new \RuntimeException('В архиве отсутствует каталог ' . $requiredRoot . '.');
        }

        $archive->extractTo($destination, null, true);
    }

    protected function applyPrivatePermissions(string $directory): void
    {
        if (! chmod($directory, 0750)) {
            throw new \RuntimeException('Cannot set private permissions: ' . $directory);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (! chmod($file->getPathname(), $file->isDir() ? 0750 : 0640)) {
                throw new \RuntimeException('Cannot set private permissions: ' . $file->getPathname());
            }
        }
    }

    protected function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $ok = $file->isDir() && ! $file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            if (! $ok) {
                throw new \RuntimeException('Cannot remove restore artifact: ' . $file->getPathname());
            }
        }

        if (! rmdir($directory)) {
            throw new \RuntimeException('Cannot remove restore directory: ' . $directory);
        }
    }
}
