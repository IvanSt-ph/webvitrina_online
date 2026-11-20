<?php

namespace Tests\Unit;

use App\Services\AttributeImport\{MainAttributeCache, ImportException};
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class MainAttributeCacheTest extends TestCase
{
    private const IDENTITY = ['db' => 'webv3', 'account' => 'mock', 'host' => 'mock', 'port' => 3306];

    public function test_changed_categories_shared_definitions_and_ancestors_only(): void
    {
        $s = ['categories' => [
            ['id' => 1, 'parent_id' => null], ['id' => 2, 'parent_id' => 1],
            ['id' => 3, 'parent_id' => 1], ['id' => 4, 'parent_id' => null],
        ], 'attribute_category' => [
            ['category_id' => 2, 'attribute_id' => 134], ['category_id' => 3, 'attribute_id' => 134], ['category_id' => 4, 'attribute_id' => 260],
        ]];
        $ops = [['table' => 'attributes', 'id' => 134, 'data' => ['type' => 'color']]];
        self::assertSame(['cat.filters.1', 'cat.filters.2', 'cat.filters.3', 'reviews.public-cache-version'], MainAttributeCache::keys($s, $s, $ops));
        $ops = [['table' => 'attribute_category', 'before' => ['category_id' => 2], 'data' => ['sort_order' => 1]]];
        self::assertSame(['cat.filters.1', 'cat.filters.2', 'reviews.public-cache-version'], MainAttributeCache::keys($s, $s, $ops));
        self::assertSame([], MainAttributeCache::keys($s, $s, []));
    }

    public function test_invalid_ancestry_blocks_plan_before_commit(): void
    {
        $s = ['categories' => [['id' => 1, 'parent_id' => 1]], 'attribute_category' => []];
        $this->expectException(ImportException::class);
        MainAttributeCache::keys($s, $s, [['table' => 'attribute_category', 'data' => ['category_id' => 1]]]);
    }

    public function test_no_cleanup_inside_transaction(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('inTransaction')->willReturn(true);
        $pdo->expects(self::never())->method('query');
        $pdo->expects(self::never())->method('prepare');
        $result = MainAttributeCache::invalidate($pdo, ['cat.filters.2'], self::IDENTITY, ['prefix' => 'wv-']);
        self::assertFalse($result['success']);
        self::assertSame(['cat.filters.2'], $result['failed_keys']);
    }

    public function test_exact_prefixed_keys_only_after_commit(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('inTransaction')->willReturn(false);
        $identity = $this->createMock(PDOStatement::class);
        $identity->method('fetch')->willReturn(self::IDENTITY);
        $pdo->expects(self::exactly(2))->method('query')->willReturn($identity);
        $keys = [];
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects(self::exactly(2))->method('execute')->willReturnCallback(function ($args) use (&$keys) { $keys[] = $args[0]; return true; });
        $pdo->expects(self::exactly(2))->method('prepare')->with('DELETE FROM `cache` WHERE `key` = ?')->willReturn($stmt);
        $result = MainAttributeCache::invalidate($pdo, ['cat.filters.2', 'reviews.public-cache-version'], self::IDENTITY, ['prefix' => 'wv-']);
        self::assertTrue($result['success']);
        self::assertSame(['wv-cat.filters.2', 'wv-reviews.public-cache-version'], $keys);
    }

    public function test_failure_keeps_retry_list_and_attempts_remaining_keys(): void
    {
        $pdo = $this->createMock(PDO::class);
        $identity = $this->createMock(PDOStatement::class);
        $identity->method('fetch')->willReturn(self::IDENTITY);
        $pdo->method('query')->willReturn($identity);
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturnCallback(function ($args) { if ($args === ['cat.filters.2']) { throw new \PDOException('sensitive SQL'); } return true; });
        $pdo->method('prepare')->willReturn($stmt);
        $result = MainAttributeCache::invalidate($pdo, ['cat.filters.2', 'reviews.public-cache-version'], self::IDENTITY, ['prefix' => '']);
        self::assertFalse($result['success']);
        self::assertSame(['cat.filters.2'], $result['failed_keys']);
        self::assertStringNotContainsString('sensitive', json_encode($result));
    }

    public function test_wrong_identity_prevents_cache_writes(): void
    {
        $pdo = $this->createMock(PDO::class);
        $identity = $this->createMock(PDOStatement::class);
        $identity->method('fetch')->willReturn(array_replace(self::IDENTITY, ['db' => 'other']));
        $pdo->method('query')->willReturn($identity);
        $pdo->expects(self::never())->method('prepare');
        self::assertFalse(MainAttributeCache::invalidate($pdo, ['cat.filters.2'], self::IDENTITY, ['prefix' => ''])['success']);
    }

    public function test_configuration_uses_laravel_prefix_and_rejects_other_connection(): void
    {
        self::assertSame(['prefix' => 'web-vitrina-cache-'], MainAttributeCache::configuration(['APP_NAME' => 'Web Vitrina'], false));
        self::assertSame(['prefix' => 'explicit_'], MainAttributeCache::configuration(['CACHE_PREFIX' => 'explicit_'], false));
        $this->expectException(ImportException::class);
        MainAttributeCache::configuration(['DB_CACHE_CONNECTION' => 'other'], false);
    }

    public function test_cached_configuration_requires_review(): void
    {
        $this->expectException(ImportException::class);
        MainAttributeCache::configuration([], true);
    }

    public function test_public_cached_entry_is_rejected_after_version_invalidation(): void
    {
        $container = new \Illuminate\Container\Container;
        $cache = new \Illuminate\Cache\Repository(new \Illuminate\Cache\ArrayStore);
        $container->instance('cache', $cache);
        \Illuminate\Support\Facades\Facade::setFacadeApplication($container);
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        try {
            $cache->put('reviews.public-cache-version', 'old');
            $cache->put('related:5', ['review_version' => 'old', 'value' => 'stale']);
            $cache->forget('reviews.public-cache-version');
            self::assertSame('fresh', \App\Services\ReviewRatingCache::remember('related:5', 60, fn () => 'fresh'));
        } finally {
            \Illuminate\Support\Facades\Facade::clearResolvedInstances();
            \Illuminate\Support\Facades\Facade::setFacadeApplication(null);
        }
    }
}
