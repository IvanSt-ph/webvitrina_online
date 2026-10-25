<?php

namespace App\Support;

class SafeRedirect
{
    public static function internalPath(?string $url): ?string
    {
        if (preg_match('/[\x00-\x1f\x7f\\\\]/', (string) $url)) {
            return null;
        }
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return self::safePath($url) ? $url : null;
        }

        $target = parse_url($url);
        $app = parse_url((string) config('app.url'));

        if (! is_array($target) || ! is_array($app)) {
            return null;
        }

        if (isset($target['user']) || isset($target['pass'])) {
            return null;
        }

        $targetHost = strtolower((string) ($target['host'] ?? ''));
        $appHost = strtolower((string) ($app['host'] ?? ''));

        if ($targetHost === '' || $targetHost !== $appHost) {
            return null;
        }

        if (($target['scheme'] ?? null) !== ($app['scheme'] ?? null)) {
            return null;
        }

        if (($target['port'] ?? null) !== ($app['port'] ?? null)) {
            return null;
        }

        $path = $target['path'] ?? '/';
        $query = isset($target['query']) ? '?' . $target['query'] : '';
        $fragment = isset($target['fragment']) ? '#' . $target['fragment'] : '';

        $relative = $path . $query . $fragment;

        return self::safePath($relative) ? $relative : null;
    }

    private static function safePath(string $url): bool
    {
        $path = rawurldecode(explode('?', explode('#', $url, 2)[0], 2)[0]);

        return str_starts_with($path, '/')
            && ! str_starts_with($path, '//')
            && ! preg_match('/[\x00-\x1f\x7f\\\\]/', $path);
    }

    public static function isInternal(?string $url): bool
    {
        return self::internalPath($url) !== null;
    }
}
