<?php

namespace App\Support;

use Illuminate\Support\Collection;

class BackupHealth
{
    public const REQUIRED_FILES = [
        'database.sql.gz',
        'storage-public.tar.gz',
        'SHA256SUMS',
    ];

    public static function latest(?string $path = null, ?int $maxAgeHours = null, bool $verifyChecksums = true): array
    {
        $backupPath = $path ?: (string) config('backup.path');
        $maxAge = max(1, $maxAgeHours ?: (int) config('backup.max_age_hours', 30));
        $latest = self::directories($backupPath)->first();

        if (! $latest) {
            return [
                'ok' => false,
                'path' => $backupPath,
                'latest' => null,
                'latest_name' => null,
                'created_at' => null,
                'age_hours' => null,
                'value' => 'не найден',
                'detail' => 'Папка бэкапов: ' . $backupPath,
                'issues' => ['Backup не найден или папка недоступна для чтения.'],
                'files' => [],
            ];
        }

        $modifiedAt = filemtime($latest) ?: time();
        $ageHours = round((time() - $modifiedAt) / 3600, 1);
        $files = self::files($latest);
        $issues = [];

        if ($ageHours > $maxAge) {
            $issues[] = 'Backup старше лимита: ' . $ageHours . ' ч при лимите ' . $maxAge . ' ч.';
        }

        foreach (self::REQUIRED_FILES as $file) {
            if (! ($files[$file]['exists'] ?? false)) {
                $issues[] = 'Нет файла ' . $file . '.';
            } elseif (($files[$file]['size'] ?? 0) <= 0) {
                $issues[] = 'Файл ' . $file . ' пустой.';
            }
        }

        if ($verifyChecksums && ($files['SHA256SUMS']['exists'] ?? false)) {
            $issues = array_merge($issues, self::checksumIssues($latest));
        }

        return [
            'ok' => $issues === [],
            'path' => $backupPath,
            'latest' => $latest,
            'latest_name' => basename($latest),
            'created_at' => date('d.m.Y H:i', $modifiedAt),
            'age_hours' => $ageHours,
            'value' => 'Последний: ' . date('d.m.Y H:i', $modifiedAt),
            'detail' => 'Папка: ' . basename($latest) . '. Возраст: ' . $ageHours . ' ч. Проверяются database.sql.gz, storage-public.tar.gz и SHA256SUMS.',
            'issues' => $issues,
            'files' => $files,
        ];
    }

    private static function directories(string $backupPath): Collection
    {
        if (! is_dir($backupPath) || ! is_readable($backupPath)) {
            return collect();
        }

        return collect(scandir($backupPath) ?: [])
            ->reject(fn ($entry) => in_array($entry, ['.', '..'], true))
            ->map(fn ($entry) => rtrim($backupPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry)
            ->filter(fn ($path) => is_dir($path))
            ->sortByDesc(fn ($path) => filemtime($path) ?: 0)
            ->values();
    }

    private static function files(string $directory): array
    {
        $files = [];

        foreach (self::REQUIRED_FILES as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            $size = is_file($path) ? (filesize($path) ?: 0) : 0;

            $files[$file] = [
                'exists' => is_file($path),
                'size' => $size,
                'size_human' => self::formatBytes($size),
            ];
        }

        return $files;
    }

    private static function checksumIssues(string $directory): array
    {
        $checksumPath = $directory . DIRECTORY_SEPARATOR . 'SHA256SUMS';
        $lines = file($checksumPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $issues = [];
        $seen = [];

        foreach ($lines as $line) {
            if (! preg_match('/^(?<hash>[a-f0-9]{64})\s+(?<file>.+)$/i', trim($line), $matches)) {
                $issues[] = 'Некорректная строка SHA256SUMS: ' . trim($line);
                continue;
            }

            $file = basename($matches['file']);
            $seen[] = $file;
            $path = $directory . DIRECTORY_SEPARATOR . $file;

            if (! is_file($path)) {
                $issues[] = 'В SHA256SUMS указан отсутствующий файл ' . $file . '.';
                continue;
            }

            if (! hash_equals(strtolower($matches['hash']), hash_file('sha256', $path))) {
                $issues[] = 'Checksum не совпадает для ' . $file . '.';
            }
        }

        foreach (['database.sql.gz', 'storage-public.tar.gz'] as $file) {
            if (! in_array($file, $seen, true)) {
                $issues[] = 'В SHA256SUMS нет записи для ' . $file . '.';
            }
        }

        return $issues;
    }

    private static function formatBytes(float|int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = max(0, (float) $bytes);
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return number_format($size, $unit === 0 ? 0 : 1, '.', ' ') . ' ' . $units[$unit];
    }
}
