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

        foreach (['current', 'previous'] as $pointer) {
            $file = $root.DIRECTORY_SEPARATOR.$pointer;
            if (! is_link($file) && is_file($file) && realpath($file) === $file) {
                $value = @file_get_contents($file, false, null, 0, 128);
                if (is_string($value) && preg_match('/^('.self::GENERATION.')\\n?$/D', $value, $matches)) {
                    $path = $this->checkedIndex($root, $matches[1]);
                    if ($path !== null) {
                        return $path;
                    }
                }
            }
        }

        // Compatibility only for generations predating pointer publication.
        // Never infer the order of generations with the same timestamp from random suffixes.
        $groups = [];
        $generations = @scandir($root, SCANDIR_SORT_DESCENDING) ?: [];
        foreach ($generations as $generation) {
            if (preg_match('/^'.self::GENERATION.'$/D', $generation)
                && ! file_exists($root.DIRECTORY_SEPARATOR.$generation.DIRECTORY_SEPARATOR.SitemapPublication::MARKER)
                && ! is_link($root.DIRECTORY_SEPARATOR.$generation.DIRECTORY_SEPARATOR.SitemapPublication::MARKER)) {
                $groups[substr($generation, 0, 16)][] = $generation;
            }
        }
        foreach ($groups as $group) {
            if (count($group) === 1) {
                $path = $this->checkedIndex($root, $group[0]);
                if ($path !== null) {
                    return $path;
                }
            }
        }

        return null;
    }

    public function generationIndex(string $generation): ?string
    {
        if (! preg_match('/^'.self::GENERATION.'$/D', $generation)) {
            return null;
        }
        $root = $this->root();

        return $root === null ? null : $this->checkedIndex($root, $generation);
    }

    private function checkedIndex(string $root, string $generation): ?string
    {
        $path = $this->resolve($root, $generation, 'sitemap.xml');
        if ($path === null) {
            return null;
        }
        $locations = $this->xmlLocations($path, 'sitemapindex');
        if ($locations === null || $locations === []) {
            return null;
        }
        foreach ($locations as $location) {
            $parts = parse_url($location);
            $prefix = '/sitemaps/'.$generation.'/';
            if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
                || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])
                || ! str_starts_with($parts['path'] ?? '', $prefix)) {
                return null;
            }
            $name = substr($parts['path'], strlen($prefix));
            if (! preg_match('/^'.self::PART.'$/D', $name)) {
                return null;
            }
            $part = $this->resolve($root, $generation, $name);
            if ($part === null || $this->xmlLocations($part, 'urlset') === null) {
                return null;
            }
        }

        return $path;
    }

    /** Stream validation also rejects truncated/corrupt XML without loading large parts into memory. */
    private function xmlLocations(string $path, string $root): ?array
    {
        if (! class_exists(\XMLReader::class)) {
            return null;
        }
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $reader = new \XMLReader();
        try {
            if (! @$reader->open($path, 'UTF-8', LIBXML_NONET)) {
                return null;
            }
            $seen = false;
            $locations = [];
            $count = 0;
            while ($reader->read()) {
                if (in_array($reader->nodeType, [\XMLReader::DOC_TYPE, \XMLReader::ENTITY_REF], true)) {
                    return null;
                }
                if ($reader->nodeType === \XMLReader::ELEMENT) {
                    $expected = match ($reader->depth) {
                        0 => $root,
                        1 => $root === 'sitemapindex' ? 'sitemap' : 'url',
                        2 => 'loc',
                        default => null,
                    };
                    if ($reader->localName !== $expected || $reader->isEmptyElement
                        || $reader->namespaceURI !== 'http://www.sitemaps.org/schemas/sitemap/0.9') {
                        return null;
                    }
                    if ($reader->depth === 0) {
                        if ($seen || $reader->localName !== $root || $reader->namespaceURI !== 'http://www.sitemaps.org/schemas/sitemap/0.9') {
                            return null;
                        }
                        $seen = true;
                    }
                    if ($reader->localName === 'loc' && $reader->depth === 2) {
                        $location = $reader->readString();
                        if (trim($location) === '') {
                            return null;
                        }
                        $count++;
                        if ($root === 'sitemapindex') {
                            $locations[] = $location;
                        }
                    }
                }
            }

            return $seen && $count > 0 && libxml_get_errors() === [] ? $locations : null;
        } finally {
            $reader->close();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
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
