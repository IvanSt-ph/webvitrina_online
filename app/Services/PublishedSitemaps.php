<?php

namespace App\Services;

/** Final directories are immutable: the generator exposes them with a single rename. */
final class PublishedSitemaps
{
    public const GENERATION = '[0-9]{8}T[0-9]{6}Z-[a-f0-9]{16}';
    public const PART = '(?:static|(?:products|categories|shops)-[0-9]{4,})\.xml';

    public function index(): ?string
    {
        $root = $this->root();
        if ($root === null) {
            return null;
        }

        $generations = @scandir($root, SCANDIR_SORT_DESCENDING) ?: [];
        foreach ($generations as $generation) {
            if (preg_match('/^'.self::GENERATION.'$/D', $generation)) {
                $path = $this->resolve($root, $generation, 'sitemap.xml');
                if ($path !== null) {
                    return $path;
                }
            }
        }

        return null;
    }

    public function part(string $generation, string $file): ?string
    {
        if (! preg_match('/^'.self::GENERATION.'$/D', $generation)
            || ! preg_match('/^'.self::PART.'$/D', $file)) {
            return null;
        }
        $root = $this->root();

        return $root === null ? null : $this->resolve($root, $generation, $file);
    }

    private function resolve(string $root, string $generation, string $file): ?string
    {
        $directory = $root.DIRECTORY_SEPARATOR.$generation;
        $path = $directory.DIRECTORY_SEPARATOR.$file;
        // Reject symlinks and Windows junctions escaping the expected parent.
        if (is_link($directory) || realpath($directory) !== $directory
            || is_link($path) || realpath($path) !== $path
            || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        return $path;
    }

    private function root(): ?string
    {
        $base = realpath(storage_path('app/private'));
        $public = realpath(public_path());
        if ($base === false) {
            return null;
        }
        $normalizedBase = strtolower(str_replace('\\', '/', $base));
        $normalizedPublic = strtolower(str_replace('\\', '/', (string) $public));
        if ($public !== false && ($normalizedBase === $normalizedPublic || str_starts_with($normalizedBase, $normalizedPublic.'/'))) {
            return null;
        }
        $path = str_replace('\\', '/', (string) config('sitemap.directory'));
        $prefix = rtrim(str_replace('\\', '/', storage_path('app/private')), '/').'/';
        if (! str_starts_with($path, $prefix)) {
            return null;
        }
        $current = $base;
        foreach (explode('/', substr($path, strlen($prefix))) as $segment) {
            if (! preg_match('/^[a-zA-Z0-9_-]+$/D', $segment)) {
                return null;
            }
            $current .= DIRECTORY_SEPARATOR.$segment;
            if (is_link($current) || ! is_dir($current) || realpath($current) !== $current) {
                return null;
            }
        }

        return $current;
    }
}
