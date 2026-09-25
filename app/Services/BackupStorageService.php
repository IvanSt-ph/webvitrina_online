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
                if ($chunk === false || gzwrite($output, $chunk) === false) {
                    throw new \RuntimeException('Не удалось сжать storage archive.');
                }
            }
        } finally {
            fclose($input);
            gzclose($output);
        }
    }

    public function restore(string $backupDirectory, string $publicRoot, string $privateRoot): void
    {
        $health = BackupHealth::inspectDirectory($backupDirectory, true);

        if (! $health['ok']) {
            throw new \RuntimeException('Backup неполный или повреждён: ' . implode(' ', $health['issues']));
        }

        $parent = dirname($publicRoot);
        if ($parent !== dirname($privateRoot)) {
            throw new \RuntimeException('Public и private storage должны иметь общий родительский каталог.');
        }

        $stage = $parent . DIRECTORY_SEPARATOR . '.backup-restore-' . Str::uuid();
        $previousPublic = $stage . DIRECTORY_SEPARATOR . '.previous-public';
        $previousChatImages = $stage . DIRECTORY_SEPARATOR . '.previous-chat-images';
        $publicInstalled = false;
        $privateInstalled = false;

        try {
            if (! mkdir($stage, 0700, true) && ! is_dir($stage)) {
                throw new \RuntimeException('Не удалось создать временный каталог восстановления.');
            }

            $this->extractArchive(
                $backupDirectory . DIRECTORY_SEPARATOR . 'storage-public.tar.gz',
                $stage,
                'public'
            );
            $this->extractArchive(
                $backupDirectory . DIRECTORY_SEPARATOR . 'storage-private-chat-images.tar.gz',
                $stage,
                'private/chat-images'
            );

            $stagedPublic = $stage . DIRECTORY_SEPARATOR . 'public';
            $stagedChatImages = $stage . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'chat-images';
            $chatImagesRoot = $privateRoot . DIRECTORY_SEPARATOR . 'chat-images';

            if (! is_dir($stagedPublic) || ! is_dir($stagedChatImages)) {
                throw new \RuntimeException('В архивах отсутствуют обязательные каталоги storage.');
            }

            if (is_dir($publicRoot) && ! rename($publicRoot, $previousPublic)) {
                throw new \RuntimeException('Не удалось подготовить public storage к восстановлению.');
            }

            if (! rename($stagedPublic, $publicRoot)) {
                throw new \RuntimeException('Не удалось установить восстановленный public storage.');
            }
            $publicInstalled = true;

            if (! is_dir($privateRoot) && ! mkdir($privateRoot, 0750, true) && ! is_dir($privateRoot)) {
                throw new \RuntimeException('Не удалось подготовить private storage.');
            }

            if (is_dir($chatImagesRoot) && ! rename($chatImagesRoot, $previousChatImages)) {
                throw new \RuntimeException('Не удалось подготовить private chat images к восстановлению.');
            }

            if (! rename($stagedChatImages, $chatImagesRoot)) {
                throw new \RuntimeException('Не удалось установить восстановленные private chat images.');
            }
            $privateInstalled = true;

            $this->applyPrivatePermissions($chatImagesRoot);
            $this->removeDirectory($previousPublic);
            $this->removeDirectory($previousChatImages);
        } catch (\Throwable $exception) {
            $chatImagesRoot = $privateRoot . DIRECTORY_SEPARATOR . 'chat-images';

            if ($privateInstalled) {
                $this->removeDirectory($chatImagesRoot);
            }
            if (is_dir($previousChatImages)) {
                @rename($previousChatImages, $chatImagesRoot);
            }

            if ($publicInstalled) {
                $this->removeDirectory($publicRoot);
            }
            if (is_dir($previousPublic)) {
                @rename($previousPublic, $publicRoot);
            }

            throw $exception;
        } finally {
            $this->removeDirectory($stage);
        }
    }

    private function extractArchive(string $archivePath, string $destination, string $requiredRoot): void
    {
        $archive = new \PharData($archivePath);
        $prefix = str_replace('\\', '/', $archivePath) . '/';
        $foundRoot = false;

        foreach (new RecursiveIteratorIterator($archive, RecursiveIteratorIterator::SELF_FIRST) as $file) {
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

    private function applyPrivatePermissions(string $directory): void
    {
        @chmod($directory, 0750);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            @chmod($file->getPathname(), $file->isDir() ? 0750 : 0640);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($directory);
    }
}
