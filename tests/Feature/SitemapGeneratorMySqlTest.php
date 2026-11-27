<?php

namespace Tests\Feature;

use App\Services\SitemapGenerator;
use Illuminate\Database\Connection;
use Tests\Support\SitemapGeneratorTestCase;

/** Opt-in only; requires the separately approved, pre-created fixture schema. No migration traits. */
final class SitemapGeneratorMySqlTest extends SitemapGeneratorTestCase
{
    private ?Connection $connection = null;
    private array $owned = [];

    protected function setUp(): void
    {
        if (getenv('WV_SITEMAP_MYSQL_TEST') !== '1') {
            $this->markTestSkipped('MySQL sitemap tests require explicit opt-in and a pre-created isolated schema.');
        }
        // Exact allowlist BEFORE opening any connection; never inherit DB_* or .env values.
        if (getenv('WV_SITEMAP_TEST_DATABASE') !== 'webvitrina_sitemap_testing'
            || getenv('WV_SITEMAP_TEST_USERNAME') !== 'webvitrina_test') {
            throw new \RuntimeException('Refusing non-isolated sitemap database/user.');
        }
        parent::setUp();
        $manager = $this->installDatabase([
            'driver' => 'mysql', 'host' => '127.127.126.9',
            'port' => (int) (getenv('WV_SITEMAP_TEST_PORT') ?: 3306),
            'database' => 'webvitrina_sitemap_testing', 'username' => 'webvitrina_test',
            'password' => getenv('WV_SITEMAP_TEST_PASSWORD') ?: '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '', 'strict' => true, 'options' => [],
        ]);
        $this->connection = $manager->connection();
        $this->assertSame('webvitrina_sitemap_testing', $this->connection->selectOne('SELECT DATABASE() AS name')->name);
        foreach (['products', 'shops', 'categories', 'users'] as $table) {
            $this->assertSame(0, $this->connection->table($table)->count(), 'Fixture database must be empty: '.$table);
        }
    }

    private function insert(string $table, array $rows): void
    {
        $this->connection->table($table)->insert($rows);
        $this->owned[$table] = array_merge($this->owned[$table] ?? [], array_column($rows, 'id'));
    }

    protected function tearDown(): void
    {
        try {
            if ($this->connection !== null && $this->owned !== []) {
                $this->assertSame('webvitrina_sitemap_testing', $this->connection->getDatabaseName());
                foreach (['products', 'shops', 'categories', 'users'] as $table) {
                    foreach (array_chunk($this->owned[$table] ?? [], 500) as $ids) {
                        $this->connection->table($table)->whereIn('id', $ids)->delete();
                    }
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_public_objects_gapped_ids_over_1000_and_generation_consistency(): void
    {
        $this->insert('users', [['id' => 1, 'deleted_at' => null], ['id' => 2, 'deleted_at' => '2026-01-01 00:00:00']]);
        $this->insert('categories', [
            ['id' => 1, 'slug' => 'active', 'is_active' => 1],
            ['id' => 3, 'slug' => 'hidden', 'is_active' => 0],
            ['id' => 5, 'slug' => '', 'is_active' => 1],
        ]);
        $this->insert('shops', [
            ['id' => 1, 'slug' => 'public-shop', 'user_id' => 1],
            ['id' => 2, 'slug' => 'deleted-owner', 'user_id' => 2],
            ['id' => 3, 'slug' => 'missing-owner', 'user_id' => 999],
            ['id' => 4, 'slug' => '', 'user_id' => 1],
        ]);
        $expected = [];
        $rows = [];
        for ($i = 1; $i <= 1005; $i++) {
            $rows[] = ['id' => $i * 3, 'slug' => 'product-'.$i, 'status' => 'active', 'deleted_at' => null];
            $expected[] = config('sitemap.origin').'/p/product-'.$i;
        }
        foreach (array_chunk($rows, 250) as $chunk) {
            $this->insert('products', $chunk);
        }
        $this->insert('products', [
            ['id' => 4000, 'slug' => 'draft', 'status' => 'draft', 'deleted_at' => null],
            ['id' => 4001, 'slug' => 'blocked', 'status' => 'blocked', 'deleted_at' => null],
            ['id' => 4002, 'slug' => 'deleted', 'status' => 'active', 'deleted_at' => '2026-01-01 00:00:00'],
            ['id' => 4003, 'slug' => '', 'status' => 'active', 'deleted_at' => null],
        ]);
        $this->app['config']->set('sitemap.max_urls', 500);
        $result = (new SitemapGenerator())->generate();
        $directory = config('sitemap.directory').'/'.$result['generation'];
        $productFiles = glob($directory.'/products-*.xml');
        $this->assertCount(3, $productFiles);
        $actual = [];
        foreach ($productFiles as $file) {
            $actual = array_merge($actual, $this->locations($file));
            $this->assertStringNotContainsString('<lastmod>', file_get_contents($file));
        }
        $this->assertSame($expected, $actual);
        $this->assertCount(1005, array_unique($actual));
        $this->assertSame([config('sitemap.origin').'/category/active'], $this->locations($directory.'/categories-0001.xml'));
        $this->assertSame([config('sitemap.origin').'/seller/public-shop'], $this->locations($directory.'/shops-0001.xml'));
        $index = $this->locations($directory.'/sitemap.xml');
        $this->assertCount(6, $index);
        foreach ($index as $url) {
            $this->assertStringStartsWith(config('sitemap.origin').'/sitemaps/'.$result['generation'].'/', $url);
            $this->assertFileExists($directory.'/'.basename($url));
        }
        $this->assertSame([], glob(config('sitemap.directory').'/.tmp-*'));
    }

    public function test_failure_preserves_previous_generation_and_cleans_only_own_temporary_files(): void
    {
        $service = new SitemapGenerator();
        $first = $service->generate();
        $root = config('sitemap.directory');
        $before = [];
        foreach (glob($root.'/'.$first['generation'].'/*.xml') as $file) {
            $before[$file] = hash_file('sha256', $file);
        }
        mkdir($root.'/.tmp-other-run');
        file_put_contents($root.'/.tmp-other-run/keep.xml', 'other run');
        $this->app['config']->set('sitemap.max_urls', 1); // Static map cannot fit: failure after transaction/temp creation.
        try {
            $service->generate();
            $this->fail('Expected static-map limit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(7100, $exception->getCode());
        }
        foreach ($before as $file => $hash) {
            $this->assertSame($hash, hash_file('sha256', $file));
        }
        $this->assertSame([$root.'/.tmp-other-run'], glob($root.'/.tmp-*'));
        $this->assertSame('other run', file_get_contents($root.'/.tmp-other-run/keep.xml'));
        $this->assertCount(1, array_filter(glob($root.'/*'), 'is_dir'));
        foreach ($this->app['db']->getConnections() as $connection) {
            $this->assertSame(0, $connection->transactionLevel());
        }
    }
}
