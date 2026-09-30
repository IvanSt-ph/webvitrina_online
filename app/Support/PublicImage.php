<?php

namespace App\Support;

/** Display URLs only: no filesystem discovery, reads or writes. */
class PublicImage
{
    public static function path(?string $path): ?string
    {
        // Reject controls before trimming, including leading/trailing NUL/newline.
        if (preg_match('/[\x00-\x1f\x7f]/', $path ?? '')) {
            return null;
        }
        $path = trim($path ?? '');
        $path = preg_replace('#^/?storage/#', '', $path);
        if ($path === '' || preg_match('#[\\\\:%?\"\'<>\#]#', $path)
            || str_starts_with($path, '/') || preg_match('#(^|/)\.{1,2}(/|$)|//#', $path)) {
            return null;
        }

        return $path;
    }

    public static function candidates(?string $path, string $fallback = 'images/image-placeholder.svg', bool $thumb = false): array
    {
        $path = self::path($path);
        $paths = [];
        if ($path !== null) {
            if ($thumb) {
                // Same medium/legacy naming as ImageService, applied to an already
                // validated disk-relative path. Do not strip another storage/ prefix.
                $dir = dirname($path);
                $base = basename($path);
                $thumbDir = basename($dir) === 'medium' ? dirname($dir) . '/thumb' : $dir . '/thumb';
                $thumbPath = $thumbDir . '/' . (basename($dir) === 'medium' ? $base : pathinfo($base, PATHINFO_FILENAME) . '.webp');
                // Only this trusted internal transform may introduce ./thumb/.
                $paths[] = str_starts_with($thumbPath, './') ? substr($thumbPath, 2) : $thumbPath;
            }
            $paths[] = $path;
        }

        return self::urls($paths, $fallback);
    }

    public static function candidatesFromPaths(array $paths, string $fallback = 'images/image-placeholder.svg'): array
    {
        return self::urls(array_values(array_filter(array_map(self::path(...), $paths), fn ($path) => $path !== null)), $fallback);
    }

    public static function url(?string $path, string $fallback = 'images/image-placeholder.svg', bool $thumb = false): string
    {
        return self::candidates($path, $fallback, $thumb)[0];
    }

    public static function first(array $paths, string $fallback = 'images/image-placeholder.svg'): string
    {
        return self::candidatesFromPaths($paths, $fallback)[0];
    }

    /** Receives canonical internal paths, never normalizes them a second time. */
    private static function urls(array $paths, string $fallback): array
    {
        $urls = array_map(fn ($path) => asset('storage/' . implode('/', array_map('rawurlencode', explode('/', $path)))), $paths);
        $urls[] = asset($fallback); // Application-owned literal, not user input.

        return array_values(array_unique($urls));
    }
}
