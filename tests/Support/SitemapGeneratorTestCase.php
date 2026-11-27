<?php
namespace Tests\Support;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;

/** Minimal container: no application bootstrap, .env, providers, migrations or production services. */
abstract class SitemapGeneratorTestCase extends SitemapFileTestCase
{
    protected Application $app;
    private $previousContainer;
    private $previousFacadeApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $this->app = new Application($this->directory);
        $this->app->useStoragePath($this->directory.'/storage');
        $this->app->usePublicPath($this->directory.'/public');

        mkdir($this->directory.'/storage/app/private', 0700, true);
        mkdir($this->directory.'/public', 0700);

        $this->app->instance('config', new Repository([
            'sitemap' => [
                'origin' => 'https://sitemap-fixture.webvitrina.test-host.org',
                'batch_size' => 1000,
                'max_urls' => 5000,
                'max_bytes' => 45 * 1024 * 1024,
                'retention_days' => 7,
                'directory' => $this->directory.'/storage/app/private/sitemaps',
            ],
        ]));

        $this->app->instance('events', new Dispatcher($this->app));

        $routes = new RouteCollection();

        foreach ([
            'home' => '/',
            'faq' => '/voprosy-i-otvety',
            'about' => '/about',
            'contacts' => '/contacts',
            'legal.rules' => '/rules',
            'legal.privacy' => '/privacy',
            'legal.delivery-returns' => '/delivery-returns',
            'legal.seller-terms' => '/seller-terms',
            'legal.cookies' => '/cookies',
            'legal.prohibited-products' => '/prohibited-products',
            'legal.review-rules' => '/review-rules',
            'product.show' => '/p/{identifier}',
            'category.show' => '/category/{slug}',
            'seller.show' => '/seller/{identifier}',
        ] as $name => $uri) {
            $routes->add(
                (new Route('GET', $uri, static fn () => null))->name($name)
            );
        }

        $routes->refreshNameLookups();

        $this->app->instance(
            'url',
            new UrlGenerator($routes, Request::create('http://localhost'))
        );

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
    }

    protected function installDatabase(array $configuration): DatabaseManager
    {
        $this->app['config']->set('database', [
            'default' => 'mysql',
            'connections' => [
                'mysql' => $configuration,
            ],
        ]);

        $manager = new DatabaseManager(
            $this->app,
            new ConnectionFactory($this->app)
        );

        $this->app->instance('db', $manager);

        return $manager;
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->app)) {
                // Do not resolve a database binding created as a test trap.
                if ($this->app->bound('db') && $this->app->resolved('db')) {
                    $manager = $this->app['db'];

                    if ($manager instanceof DatabaseManager) {
                        foreach ($manager->getConnections() as $connection) {
                            $connection->disconnect();
                        }
                    }
                }
            }
        } finally {
            if (isset($this->app)) {
                Facade::clearResolvedInstances();
                Facade::setFacadeApplication($this->previousFacadeApplication);
                Container::setInstance($this->previousContainer);
            }

            parent::tearDown();
        }
    }
}
