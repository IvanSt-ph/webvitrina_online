<?php

namespace App\Support;

class SafeRedirect
{
    public static function internalPath(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $target = parse_url($url);
        $app = parse_url((string) config('app.url'));

        if (! is_array($target) || ! is_array($app)) {
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

        return $path . $query . $fragment;
    }

    public static function isInternal(?string $url): bool
    {
        return self::internalPath($url) !== null;
    }
}
