<?php

namespace App\Services\AttributeImport;

use PDO;
use Throwable;

/** Targeted invalidation on the same verified PDO, never a global cache flush. */
final class MainAttributeCache
{
    public static function keys(array $before, array $expected, array $operations): array
    {
        if ($operations === []) { return []; }
        $categories = array_column($expected['categories'], null, 'id');
        $changed = $attributes = [];
        foreach ($operations as $op) {
            if ($op['table'] === 'attribute_category') {
                $row = array_replace($op['before'] ?? [], $op['data']);
                $changed[$row['category_id']] = true;
            } elseif ($op['table'] === 'attributes') {
                $attributes[$op['id']] = true;
            } elseif ($op['table'] === 'attribute_color') {
                $attributes[$op['data']['attribute_id']] = true;
            }
        }
        foreach (array_merge($before['attribute_category'], $expected['attribute_category']) as $link) {
            if (isset($attributes[$link['attribute_id']])) { $changed[$link['category_id']] = true; }
        }
        $keys = [];
        foreach (array_keys($changed) as $id) {
            $seen = [];
            while ($id !== null) {
                if (isset($seen[$id]) || ! isset($categories[$id])) { throw new ImportException('Invalid category ancestry for cache invalidation.'); }
                $seen[$id] = true;
                $keys['cat.filters.'.$id] = true;
                $id = $categories[$id]['parent_id'];
            }
        }
        // ReviewRatingCache lazily creates a new version after this key is removed.
        // Existing public product/recommendation entries then fail the version check.
        $keys['reviews.public-cache-version'] = true;
        $result = array_keys($keys);
        sort($result, SORT_STRING);
        return $result;
    }

    public static function configuration(array $env, bool $configurationCached): array
    {
        if ($configurationCached || ($env['CACHE_STORE'] ?? 'database') !== 'database'
            || ! empty($env['DB_CACHE_CONNECTION']) || ($env['DB_CACHE_TABLE'] ?? 'cache') !== 'cache') {
            throw new ImportException('Unreviewed cache configuration; targeted cleanup cannot be confirmed.');
        }
        return ['prefix' => $env['CACHE_PREFIX'] ?? \Illuminate\Support\Str::slug((string) ($env['APP_NAME'] ?? 'laravel')).'-cache-'];
    }

    public static function invalidate(PDO $pdo, array $keys, array $identity, ?array $configuration): array
    {
        $failed = [];
        foreach ($keys as $key) {
            try {
                if ($pdo->inTransaction() || $configuration === null || ! isset($configuration['prefix'])) {
                    throw new ImportException('Cleanup requires committed transaction and verified cache configuration.');
                }
                $actual = $pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account, @@hostname AS host, @@port AS port')->fetch(PDO::FETCH_ASSOC);
                if ($actual !== $identity || $actual['db'] !== 'webv3') { throw new ImportException('Cache connection mismatch.'); }
                if (! preg_match('/^(cat\.filters\.[1-9][0-9]*|reviews\.public-cache-version)$/D', $key)) {
                    throw new ImportException('Unexpected cache key.');
                }
                $statement = $pdo->prepare('DELETE FROM `cache` WHERE `key` = ?');
                if (! $statement->execute([$configuration['prefix'].$key])) { throw new ImportException('Cache deletion failed.'); }
            } catch (Throwable) {
                // No exception text: driver errors may contain SQL/data/credentials.
                $failed[] = $key;
            }
        }
        return ['success' => $failed === [], 'keys' => $keys, 'failed_keys' => $failed];
    }
}
