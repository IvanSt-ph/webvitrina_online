<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;
use XMLReader;
use XMLWriter;

final class SitemapGenerator
{
    private const STATIC_ROUTES = [
        'home', 'faq', 'about', 'contacts', 'legal.rules', 'legal.privacy',
        'legal.delivery-returns', 'legal.seller-terms', 'legal.cookies',
        'legal.prohibited-products', 'legal.review-rules',
    ];

    public function generate(): array
    {
        if (! class_exists(XMLWriter::class) || ! class_exists(XMLReader::class)) {
            throw new RuntimeException('Sitemap generation requires PHP XMLWriter and XMLReader extensions.', 7100);
        }
        $origin = $this->origin();
        $batch = $this->integerSetting('batch_size', 1, 1000);
        $maxUrls = $this->integerSetting('max_urls', 1, 5000);
        $maxBytes = $this->integerSetting('max_bytes', 1024, 45 * 1024 * 1024);
        $this->integerSetting('retention_days', 1, 3650);
        $root = $this->directory();
        $lock = fopen($root.DIRECTORY_SEPARATOR.'generation.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Cannot open sitemap generation lock.', 7100);
        }
        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            throw new RuntimeException('Another sitemap generation is already running.', 7100);
        }

        $connection = null;
        $temporary = null;
        try {
            $generation = gmdate('Ymd\THis\Z').'-'.bin2hex(random_bytes(8));
            $candidate = $root.DIRECTORY_SEPARATOR.'.tmp-'.$generation;
            if (! mkdir($candidate, 0700)) {
                throw new RuntimeException('Cannot create temporary sitemap directory.', 7100);
            }
            $temporary = $candidate; // Cleanup ownership starts only after successful creation.
            if (file_put_contents($temporary.DIRECTORY_SEPARATOR.SitemapPublication::MARKER, '1') !== 1) {
                throw new RuntimeException('Cannot mark sitemap generation for pointer publication.', 7100);
            }

            // A dedicated connection avoids changing isolation/session state of the application connection.
            $configuration = config('database.connections.'.config('database.default'));
            if (! is_array($configuration) || ($configuration['driver'] ?? null) !== 'mysql') {
                throw new RuntimeException('Sitemap snapshots require a MySQL connection with InnoDB tables.', 7100);
            }
            $configuration['name'] = 'sitemap-generation-'.$generation;
            $connection = DB::build($configuration);
            $this->checkSchema($connection);
            $connection->getPdo()->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $connection->getPdo()->exec('SET TRANSACTION READ ONLY');
            $connection->beginTransaction();

            $files = [];
            $staticUrls = array_map(fn ($name) => $this->url($origin, $name), self::STATIC_ROUTES);
            if (count(array_unique($staticUrls)) !== count($staticUrls)) {
                throw new RuntimeException('Duplicate static sitemap URLs.', 7100);
            }
            $this->writeParts($temporary, 'static', $staticUrls, $maxUrls, $maxBytes, $files, single: true);

            foreach (['categories', 'shops', 'products'] as $type) {
                $this->writeParts($temporary, $type, $this->urls($connection, $type, $origin, $batch), $maxUrls, $maxBytes, $files);
            }
            $connection->commit();

            $indexUrls = array_map(fn ($name) => $origin.'/sitemaps/'.$generation.'/'.$name, array_keys($files));
            $this->writeParts($temporary, 'sitemap', $indexUrls, $maxUrls, $maxBytes, $files, single: true, root: 'sitemapindex');
            foreach ($files as $name => $count) {
                $this->validateXml($temporary.DIRECTORY_SEPARATOR.$name, $count, $maxBytes,
                    $name === 'sitemap.xml' ? 'sitemapindex' : 'urlset');
            }

            $target = $root.DIRECTORY_SEPARATOR.$generation;
            if (file_exists($target) || ! rename($temporary, $target)) {
                throw new RuntimeException('Cannot finalize sitemap generation; previous generations are unchanged.', 7100);
            }
            $temporary = null;

            // Files are complete, but become current only after the atomic pointer replacement.
            (new SitemapPublication())->publish($root, $generation);
            return ['generation' => $generation, 'files' => count($files), 'urls' => array_sum($files) - $files['sitemap.xml']];
        } finally {
            if ($connection !== null) {
                try {
                    if ($connection->transactionLevel() > 0) {
                        $connection->rollBack();
                    }
                } catch (Throwable) {
                    // A lost connection must not prevent temporary-file cleanup.
                } finally {
                    try {
                        $connection->disconnect();
                    } catch (Throwable) {
                        // Continue releasing filesystem resources even after a connection failure.
                    }
                }
            }
            $cleanupFailed = false;
            if ($temporary !== null) {
                $marker = $temporary.DIRECTORY_SEPARATOR.SitemapPublication::MARKER;
                if (is_file($marker) && ! is_link($marker) && ! @unlink($marker)) {
                    $cleanupFailed = true;
                }
                // Flat, exclusively owned random directory only. Never recurse or follow symlinks.
                foreach (glob($temporary.DIRECTORY_SEPARATOR.'*.xml') ?: [] as $file) {
                    if (is_file($file) && ! is_link($file)) {
                        if (! @unlink($file)) {
                            $cleanupFailed = true;
                        }
                    }
                }
                if (! @rmdir($temporary)) {
                    $cleanupFailed = true;
                }
            }
            flock($lock, LOCK_UN);
            fclose($lock);
            if ($cleanupFailed) {
                throw new RuntimeException('Generation failed and its private temporary directory could not be fully removed. Previous generations are unchanged; check storage permissions.', 7100);
            }
        }
    }

    private function urls(Connection $connection, string $type, string $origin, int $batch): \Generator
    {
        $lastId = 0;
        do {
            $query = $connection->table($type)->useWritePdo()
                ->select($type.'.id', $type.'.slug')->where($type.'.id', '>', $lastId)
                ->whereNotNull($type.'.slug')->whereRaw('TRIM('.$connection->getQueryGrammar()->wrap($type.'.slug').") <> ''")
                ->orderBy($type.'.id')->limit($batch);
            if ($type === 'products') {
                $query->where('status', Product::STATUS_ACTIVE)->whereNull('deleted_at');
            } elseif ($type === 'categories') {
                $query->where('is_active', true);
            } else {
                $query->whereExists(function ($owners) {
                    $owners->selectRaw('1')->from('users')->whereColumn('users.id', 'shops.user_id')->whereNull('users.deleted_at');
                });
            }
            $rows = $query->get();
            foreach ($rows as $row) {
                $lastId = (int) $row->id;
                // Match public route input validation; malformed legacy slugs are not public URLs.
                if (mb_strlen($row->slug) > 255 || ! preg_match('/^[\pL\pN_-]+$/uD', $row->slug)) {
                    continue;
                }
                $route = match ($type) {
                    'products' => 'product.show',
                    'categories' => 'category.show',
                    'shops' => 'seller.show',
                };
                yield $this->url($origin, $route, [$row->slug]);
            }
        } while ($rows->isNotEmpty());
    }

    private function writeParts(string $directory, string $type, iterable $urls, int $maxUrls, int $maxBytes, array &$files, bool $single = false, string $root = 'urlset'): void
    {
        $part = 1;
        $name = $single ? $type.'.xml' : sprintf('%s-%04d.xml', $type, $part);
        $writer = new SitemapXmlFile($directory.DIRECTORY_SEPARATOR.$name, $root, $maxUrls, $maxBytes);
        try {
            foreach ($urls as $url) {
                if (! $writer->append($url)) {
                    if ($single) {
                        throw new RuntimeException('Static sitemap or index exceeds configured limits; generation was not published.', 7100);
                    }
                    $files[$name] = $writer->finish();
                    $name = sprintf('%s-%04d.xml', $type, ++$part);
                    $writer = new SitemapXmlFile($directory.DIRECTORY_SEPARATOR.$name, $root, $maxUrls, $maxBytes);
                    if (! $writer->append($url)) {
                        throw new RuntimeException('Cannot add URL to a new sitemap part; generation was not published.', 7100);
                    }
                }
            }
            $count = $writer->finish();
            if ($count > 0) {
                $files[$name] = $count;
            } elseif (! unlink($directory.DIRECTORY_SEPARATOR.$name)) {
                throw new RuntimeException('Cannot remove empty temporary sitemap file.', 7100);
            }
        } finally {
            $writer->close(); // Close failed output before Windows cleanup, even if exception traces retain it.
        }
    }

    private function url(string $origin, string $route, array $parameters = []): string
    {
        $path = route($route, $parameters, false);
        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '?') || str_contains($path, '#')) {
            throw new RuntimeException('Sitemap route must resolve to a local path without query or fragment.', 7100);
        }

        return $origin.$path;
    }

    private function origin(): string
    {
        $origin = rtrim((string) config('sitemap.origin'), '/');
        $parts = parse_url($origin);
        $host = strtolower($parts['host'] ?? '');
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || $host === ''
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment']) || ! empty($parts['path'])
            || filter_var($origin, FILTER_VALIDATE_URL) === false
            || ! str_contains($host, '.') || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)
            || preg_match('/(^|\.)(localhost|local|test|invalid|example)$/i', $host)
            || preg_match('/(^|\.)example\.(com|org|net)$/i', $host)) {
            throw new RuntimeException('Set SITEMAP_ORIGIN to the confirmed public HTTPS origin (no path, credentials, localhost or placeholder domain).', 7100);
        }

        return $origin;
    }

    private function integerSetting(string $key, int $min, int $max): int
    {
        $value = filter_var(config('sitemap.'.$key), FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            throw new RuntimeException('Invalid sitemap configuration: '.$key, 7100);
        }

        return $value;
    }

    private function directory(): string
    {
        $base = realpath(storage_path('app/private'));
        if ($base === false) {
            throw new RuntimeException('Create the private storage directory before generating sitemaps.', 7100);
        }
        $public = realpath(public_path());
        $normalizedBase = strtolower(str_replace('\\', '/', $base));
        $normalizedPublic = strtolower(str_replace('\\', '/', (string) $public));
        if ($public !== false && ($normalizedBase === $normalizedPublic || str_starts_with($normalizedBase, $normalizedPublic.'/'))) {
            throw new RuntimeException('Private sitemap storage must not resolve inside the public directory.', 7100);
        }
        $path = str_replace('\\', '/', (string) config('sitemap.directory'));
        $prefix = rtrim(str_replace('\\', '/', storage_path('app/private')), '/').'/';
        if (! str_starts_with($path, $prefix)) {
            throw new RuntimeException('Sitemap directory must be inside storage/app/private.', 7100);
        }
        $current = $base;
        foreach (explode('/', substr($path, strlen($prefix))) as $segment) {
            if (! preg_match('/^[a-zA-Z0-9_-]+$/D', $segment)) {
                throw new RuntimeException('Invalid private sitemap directory segment.', 7100);
            }
            $current .= DIRECTORY_SEPARATOR.$segment;
            if (is_link($current) || (file_exists($current) && ! is_dir($current))) {
                throw new RuntimeException('Sitemap directory must not contain symlinks or files.', 7100);
            }
            if (! is_dir($current) && ! mkdir($current, 0700)) {
                throw new RuntimeException('Cannot create private sitemap directory.', 7100);
            }
        }

        return $current;
    }

    private function checkSchema(Connection $connection): void
    {
        foreach (['products', 'categories', 'shops', 'users'] as $table) {
            $name = $connection->getTablePrefix().$table;
            $engine = $connection->query()->fromRaw('information_schema.TABLES')->useWritePdo()
                ->where('TABLE_SCHEMA', $connection->getDatabaseName())->where('TABLE_NAME', $name)->value('ENGINE');
            if (strtolower((string) $engine) !== 'innodb') {
                throw new RuntimeException('Sitemap snapshot requires InnoDB table: '.$table, 7100);
            }
            if ($table === 'users') {
                continue;
            }
            // Migrations declare unique slug; verify it at execution instead of silently emitting duplicates.
            $indexes = $connection->query()->fromRaw('information_schema.STATISTICS')->useWritePdo()
                ->where('TABLE_SCHEMA', $connection->getDatabaseName())->where('TABLE_NAME', $name)
                ->where('NON_UNIQUE', 0)->get(['INDEX_NAME', 'COLUMN_NAME', 'SUB_PART']);
            $uniqueSlug = $indexes->groupBy('INDEX_NAME')->contains(fn ($columns) => $columns->count() === 1
                && $columns->first()->COLUMN_NAME === 'slug' && $columns->first()->SUB_PART === null);
            if (! $uniqueSlug) {
                throw new RuntimeException('Sitemap requires a unique full slug index on '.$table.'.', 7100);
            }
        }
    }

    private function validateXml(string $path, int $expected, int $maxBytes, string $root): void
    {
        clearstatcache(true, $path);
        $size = filesize($path);
        if ($size === false || $size > $maxBytes) {
            throw new RuntimeException('Generated sitemap exceeds XML size limit.', 7100);
        }
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $reader = new XMLReader();
        try {
            if (! $reader->open($path, 'UTF-8', LIBXML_NONET)) {
                throw new RuntimeException('Cannot validate generated sitemap XML.', 7100);
            }
            $namespace = 'http://www.sitemaps.org/schemas/sitemap/0.9';
            $entry = $root === 'sitemapindex' ? 'sitemap' : 'url';
            $stack = [];
            $rootSeen = false;
            $rootClosed = false;
            $locCount = 0;
            $locHasText = false;
            $count = 0;
            $invalid = static function (): never {
                throw new RuntimeException('Generated sitemap has invalid XML structure or namespace.', 7100);
            };
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    $depth = count($stack);
                    $wanted = match ($depth) { 0 => $root, 1 => $entry, 2 => 'loc', default => null };
                    if ($rootClosed || $reader->depth !== $depth || $reader->localName !== $wanted
                        || $reader->namespaceURI !== $namespace || $reader->isEmptyElement) {
                        $invalid();
                    }
                    if ($depth === 0) {
                        if ($rootSeen) {
                            $invalid();
                        }
                        $rootSeen = true;
                    } elseif ($depth === 1) {
                        $locCount = 0;
                    } else {
                        if (++$locCount !== 1) {
                            $invalid();
                        }
                        $locHasText = false;
                    }
                    $stack[] = $reader->localName;
                } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                    if ($stack === [] || $reader->localName !== end($stack)
                        || $reader->depth !== count($stack) - 1 || $reader->namespaceURI !== $namespace) {
                        $invalid();
                    }
                    $closed = array_pop($stack);
                    if ($closed === 'loc' && ! $locHasText) {
                        $invalid();
                    } elseif ($closed === $entry) {
                        if ($locCount !== 1) {
                            $invalid();
                        }
                        $count++;
                    } elseif ($closed === $root) {
                        $rootClosed = true;
                    }
                } elseif (in_array($reader->nodeType, [XMLReader::TEXT, XMLReader::CDATA, XMLReader::WHITESPACE, XMLReader::SIGNIFICANT_WHITESPACE], true)) {
                    if (trim($reader->value) !== '') {
                        if (end($stack) !== 'loc') {
                            $invalid();
                        }
                        $locHasText = true;
                    }
                } elseif ($reader->nodeType !== XMLReader::COMMENT && $reader->nodeType !== XMLReader::XML_DECLARATION) {
                    // No DTD, entities or processing instructions in generated sitemap documents.
                    $invalid();
                }
            }
            if (libxml_get_errors() !== [] || ! $rootSeen || ! $rootClosed || $stack !== [] || $count !== $expected) {
                throw new RuntimeException('Generated sitemap XML failed validation.', 7100);
            }
        } finally {
            $reader->close();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
