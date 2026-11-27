<?php

namespace Tests\Feature;

use App\Services\PublishedSitemaps;
use App\Services\SitemapGenerator;
use App\Services\SitemapPublication;
use App\Services\SitemapXmlFile;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\SitemapGeneratorTestCase;

/** No application bootstrap, .env, production providers or external database. */
final class PublicSitemapTest extends SitemapGeneratorTestCase
{
    private const OLD = '20261126T120000Z-0123456789abcdef';
    private const NEW = '20261127T120000Z-fedcba9876543210';
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind('db', static function () {
            throw new \LogicException('Prepared sitemap requests must not access any database.');
        });
        $this->app->bind('auth', static function () {
            throw new \LogicException('Sitemap requests must not resolve authentication.');
        });
        $this->app->bind('session', static function () {
            throw new \LogicException('Sitemap requests must not resolve sessions.');
        });
        $this->router = new Router($this->app['events'], $this->app);
        $this->app->instance('router', $this->router);
        require dirname(__DIR__, 2).'/routes/sitemaps.php';
    }

    public function test_anonymous_index_and_every_link_are_served_without_database_or_session(): void
    {
        $directory = $this->publish(self::OLD);
        $response = $this->get('/sitemap.xml');
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame(file_get_contents($directory.'/sitemap.xml'), $this->body($response));
        foreach ($this->locations($directory.'/sitemap.xml') as $url) {
            $this->assertStringStartsWith(config('sitemap.origin').'/sitemaps/'.self::OLD.'/', $url);
            $part = $this->get(parse_url($url, PHP_URL_PATH));
            $this->assertSame(200, $part->getStatusCode());
            $this->assertSame('application/xml; charset=UTF-8', $part->headers->get('Content-Type'));
            $this->assertSame(file_get_contents($directory.'/'.basename($url)), $this->body($part));
        }
        foreach ($this->router->getRoutes() as $route) {
            $this->assertSame([], $route->gatherMiddleware());
        }
    }

    public function test_finalization_switches_index_and_preserves_old_links(): void
    {
        $old = $this->publish(self::OLD);
        $new = $this->publish(self::NEW, temporary: true);
        $this->assertSame(file_get_contents($old.'/sitemap.xml'), $this->body($this->get('/sitemap.xml')));
        rename($new, config('sitemap.directory').'/'.self::NEW);
        $this->assertSame(file_get_contents($old.'/sitemap.xml'), $this->body($this->get('/sitemap.xml')));
        (new SitemapPublication())->publish(config('sitemap.directory'), self::NEW);
        $this->assertStringContainsString(self::NEW, $this->body($this->get('/sitemap.xml')));
        $this->assertSame(200, $this->get('/sitemaps/'.self::OLD.'/static.xml')->getStatusCode());
    }

    public function test_failed_generation_keeps_previous_public_version(): void
    {
        $old = $this->publish(self::OLD);
        // Fails after staging is created, before any DB connection can be built.
        $this->app['config']->set('database', ['default' => 'unsupported', 'connections' => []]);
        try {
            (new SitemapGenerator())->generate();
            $this->fail('Expected generation failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(7100, $exception->getCode());
        }
        $this->assertSame([], glob(config('sitemap.directory').'/.tmp-*'));
        $this->assertSame(file_get_contents($old.'/sitemap.xml'), $this->body($this->get('/sitemap.xml')));
    }

    public function test_pointer_orders_publications_in_same_second_independently_of_suffix(): void
    {
        $first = '20261127T120000Z-ffffffffffffffff';
        $last = '20261127T120000Z-0000000000000000';
        $this->publish($first);
        $this->publish($last);
        $this->assertStringContainsString($last, $this->body($this->get('/sitemap.xml')));
        $this->assertSame(200, $this->get('/sitemaps/'.$first.'/static.xml')->getStatusCode());
    }

    public static function brokenPointers(): array
    {
        return [[null], [''], ["../secret\n"], ['.tmp-'.self::NEW], [self::NEW.'extra'], [str_repeat('x', 200)]];
    }

    #[DataProvider('brokenPointers')]
    public function test_missing_or_invalid_current_uses_previous_pointer(?string $value): void
    {
        $this->publish(self::OLD);
        $this->publish(self::NEW);
        $pointer = config('sitemap.directory').'/current';
        if ($value === null) {
            unlink($pointer);
        } else {
            file_put_contents($pointer, $value);
        }
        $this->assertStringContainsString(self::OLD, $this->body($this->get('/sitemap.xml')));
    }

    public function test_corrupt_current_generation_uses_previous_publication(): void
    {
        $this->publish(self::OLD);
        $directory = $this->publish(self::NEW);
        file_put_contents($directory.'/products-0001.xml', '<urlset>');
        $this->assertStringContainsString(self::OLD, $this->body($this->get('/sitemap.xml')));
    }

    public static function pointerReplacementFailures(): array
    {
        return [['previous'], ['current']];
    }

    #[DataProvider('pointerReplacementFailures')]
    public function test_pointer_failure_keeps_previous_and_does_not_discover_unpublished_directory(string $pointer): void
    {
        $this->publish(self::OLD);
        $directory = $this->publish(self::NEW, temporary: true);
        rename($directory, config('sitemap.directory').'/'.self::NEW);
        // A directory prevents atomic replacement, even under privileged Windows users.
        if ($pointer === 'current') {
            rename(config('sitemap.directory').'/current', config('sitemap.directory').'/previous');
        }
        mkdir(config('sitemap.directory').'/'.$pointer);
        try {
            (new SitemapPublication())->publish(config('sitemap.directory'), self::NEW);
            $this->fail('Expected pointer replacement failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(7100, $exception->getCode());
        }
        $this->assertStringContainsString(self::OLD, $this->body($this->get('/sitemap.xml')));
        $this->assertSame([], glob(config('sitemap.directory').'/.pointer-*'));
        rmdir(config('sitemap.directory').'/'.$pointer);
        foreach (['current', 'previous'] as $name) {
            if (is_file(config('sitemap.directory').'/'.$name)) {
                unlink(config('sitemap.directory').'/'.$name);
            }
        }
        $this->assertNull((new PublishedSitemaps())->index());
    }

    public function test_legacy_fallback_skips_ambiguous_timestamps_and_damaged_generations(): void
    {
        $old = $this->publish(self::OLD, legacy: true);
        $this->publish('20261127T120000Z-ffffffffffffffff', legacy: true);
        $this->publish('20261127T120000Z-0000000000000000', legacy: true);
        $damaged = $this->publish('20261128T120000Z-0000000000000000', legacy: true);
        unlink($damaged.'/static.xml');
        file_put_contents(config('sitemap.directory').'/current', 'invalid');
        $this->assertSame(file_get_contents($old.'/sitemap.xml'), $this->body($this->get('/sitemap.xml')));
        unlink(config('sitemap.directory').'/current');
        $this->assertSame(file_get_contents($old.'/sitemap.xml'), $this->body($this->get('/sitemap.xml')));
    }

    public function test_missing_part_returns_xml_404(): void
    {
        $this->publish(self::OLD);
        $response = $this->get('/sitemaps/'.self::OLD.'/products-9999.xml');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringNotContainsString($this->directory, $response->getContent());
    }

    public static function unsafePaths(): array
    {
        return [
            ['..', 'static.xml'], [self::OLD, '../sitemap.xml'],
            [self::OLD, '..\\secret.xml'], [self::OLD, '%2e%2e%2fsecret.xml'],
            ['.tmp-'.self::OLD, 'static.xml'], [self::OLD, 'sitemap.xml'],
            [self::OLD, 'generation.lock'], [self::OLD, 'static.xml:secret'],
            [self::OLD, 'static.xml/secret'],
        ];
    }

    #[DataProvider('unsafePaths')]
    public function test_unsafe_names_are_rejected_by_reader_and_routes(string $generation, string $file): void
    {
        $this->publish(self::OLD);
        $this->assertNull((new PublishedSitemaps())->part($generation, $file));
        $uri = '/sitemaps/'.$generation.'/'.$file;
        // Symfony rejects backslashes before routing (400); other unsafe paths miss the route (404).
        // Requiring an exception also ensures that a successful response cannot pass this test.
        $this->expectException(str_contains($uri, '\\') ? BadRequestException::class : NotFoundHttpException::class);
        $this->get($uri);
    }

    public function test_symlink_part_cannot_expose_another_file(): void
    {
        $directory = $this->publish(self::OLD);
        $secret = $this->directory.'/secret.xml';
        file_put_contents($secret, 'private');
        if (! @symlink($secret, $directory.'/products-0002.xml')) {
            $this->markTestSkipped('Creating symlinks is not permitted on this platform.');
        }
        $this->assertSame(404, $this->get('/sitemaps/'.self::OLD.'/products-0002.xml')->getStatusCode());
    }

    public function test_directory_outside_private_storage_is_not_read(): void
    {
        $this->publish(self::OLD);
        $this->app['config']->set('sitemap.directory', $this->directory);
        $this->assertNull((new PublishedSitemaps())->index());
        $this->assertNull((new PublishedSitemaps())->part(self::OLD, 'static.xml'));
    }

    public function test_legacy_fallback_renders_existing_template_until_first_finalization(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('The isolated fallback fixture requires pdo_sqlite.');
        }
        $manager = $this->installDatabase(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $previous = EloquentModel::getConnectionResolver();
        EloquentModel::setConnectionResolver($manager);
        try {
            $connection = $manager->connection();
            foreach (['products', 'categories', 'shops'] as $table) {
                $connection->statement('CREATE TABLE '.$table.' (id INTEGER PRIMARY KEY, slug TEXT, updated_at TEXT, status TEXT, is_active INTEGER, deleted_at TEXT)');
                $connection->table($table)->insert([
                    'slug' => 'fixture-'.$table, 'updated_at' => '2026-11-26 12:00:00',
                    'status' => \App\Models\Product::STATUS_ACTIVE, 'is_active' => 1,
                ]);
            }
            $this->app['config']->set('view.paths', [dirname(__DIR__, 2).'/resources/views']);
            $this->app['config']->set('view.compiled', $this->directory.'/views');
            mkdir($this->directory.'/views');
            $this->app->register(\Illuminate\Filesystem\FilesystemServiceProvider::class);
            $this->app->register(\Illuminate\View\ViewServiceProvider::class);
            $connection->enableQueryLog();
            foreach ([false, true] as $staging) {
                if ($staging) {
                    $this->publish(self::NEW, temporary: true);
                }
                $response = $this->get('/sitemap.xml');
                $this->assertSame(200, $response->getStatusCode());
                $this->assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));
                $xml = simplexml_load_string($response->getContent());
                $this->assertNotFalse($xml);
                $this->assertSame('urlset', $xml->getName());
                foreach (['/p/fixture-products', '/category/fixture-categories', '/seller/fixture-shops'] as $path) {
                    $this->assertStringContainsString($path, $response->getContent());
                }
            }
            $this->assertCount(6, $connection->getQueryLog());
            foreach ($connection->getQueryLog() as $query) {
                $this->assertStringStartsWith('select ', strtolower($query['query']));
            }
        } finally {
            if ($previous === null) {
                EloquentModel::unsetConnectionResolver();
            } else {
                EloquentModel::setConnectionResolver($previous);
            }
        }
    }

    private function get(string $uri): \Symfony\Component\HttpFoundation\Response
    {
        $request = Request::create(config('sitemap.origin').$uri, 'GET', server: ['HTTP_USER_AGENT' => 'Googlebot']);
        $this->app->instance('request', $request);

        return $this->router->dispatch($request);
    }

    private function body(BinaryFileResponse $response): string
    {
        ob_start();
        try {
            $response->sendContent();

            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    private function publish(string $generation, bool $temporary = false, bool $legacy = false): string
    {
        $directory = config('sitemap.directory').'/'.($temporary ? '.tmp-' : '').$generation;
        mkdir($directory, 0700, true);
        if (! $legacy) {
            file_put_contents($directory.'/'.SitemapPublication::MARKER, '1');
        }
        $files = ['static.xml', 'products-0001.xml', 'categories-0001.xml', 'shops-0001.xml'];
        foreach ($files as $file) {
            $writer = new SitemapXmlFile($directory.'/'.$file, 'urlset', 5000, 1048576);
            $writer->append(config('sitemap.origin').'/');
            $writer->finish();
        }
        $writer = new SitemapXmlFile($directory.'/sitemap.xml', 'sitemapindex', 5000, 1048576);
        foreach ($files as $file) {
            $writer->append(config('sitemap.origin').'/sitemaps/'.$generation.'/'.$file);
        }
        $writer->finish();

        if (! $temporary && ! $legacy) {
            (new SitemapPublication())->publish(config('sitemap.directory'), $generation);
        }

        return $directory;
    }
}
