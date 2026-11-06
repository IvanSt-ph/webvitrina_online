<?php

namespace Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Env;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\ExistingSchemaFileIsolation;

/** No project bootstrap, environment files, providers, processes or database connections. */
class ExistingSchemaFileIsolationTest extends TestCase
{
    private array $scopes = [];
    private Container $previousContainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
    }

    protected function tearDown(): void
    {
        try {
            foreach (array_reverse($this->scopes) as $scope) {
                $scope->close();
            }
        } finally {
            Container::setInstance($this->previousContainer);
            parent::tearDown();
        }
        // Retain all unique artifacts. No recursive cleanup, including failed assertions.
    }

    private function scope(): ExistingSchemaFileIsolation
    {
        return $this->scopes[] = ExistingSchemaFileIsolation::create();
    }

    private function application(): Application
    {
        return new Application(dirname(__DIR__, 2));
    }

    private function configuration(Application $app): Repository
    {
        $config = new Repository([
            'view' => ['compiled' => '/shared/views'],
            'filesystems' => ['disks' => [
                'public' => ['driver' => 'local', 'root' => '/shared/public'],
                'local' => ['driver' => 'local', 'root' => '/shared/private'],
                'review_images' => ['driver' => 'local', 'root' => '/shared/reviews'],
            ]],
            'cache' => ['default' => 'array', 'stores' => ['file' => [
                'driver' => 'file', 'path' => '/shared/cache', 'lock_path' => '/shared/cache',
            ]]],
            'session' => ['driver' => 'database', 'files' => '/shared/sessions'],
            'queue' => ['default' => 'database'],
            'logging' => ['default' => 'stack', 'channels' => [
                'stack' => ['driver' => 'stack', 'channels' => ['daily', 'external']],
                'daily' => ['driver' => 'daily', 'path' => '/shared/log'],
                'external' => ['driver' => 'slack', 'url' => 'https://invalid.example'],
                'emergency' => ['path' => '/shared/emergency'],
            ]],
        ]);
        $app->instance('config', $config);

        return $config;
    }

    public function test_paths_are_installed_before_configuration_without_redirecting_public_or_bootstrap(): void
    {
        $scope = $this->scope();
        $app = $this->application();
        $public = $app->publicPath();
        $bootstrap = $app->bootstrapPath();
        $scope->attach($app);
        $this->assertFalse($app->bound('config'));
        $this->assertFalse($app->hasBeenBootstrapped());
        $this->assertSame($scope->storagePath(), $app->storagePath());
        $this->assertSame($public, $app->publicPath());
        $this->assertSame($bootstrap, $app->bootstrapPath());
        foreach (['getCachedConfigPath', 'getCachedRoutesPath', 'getCachedEventsPath',
            'getCachedServicesPath', 'getCachedPackagesPath'] as $method) {
            $this->assertStringStartsWith(dirname($scope->storagePath()).'/bootstrap-cache/', $app->$method());
            $this->assertFileDoesNotExist($app->$method());
        }
        $this->assertDirectoryExists($scope->storagePath().'/framework/views');
        $this->assertSame($scope->storagePath().'/framework/views', Env::get('VIEW_COMPILED_PATH'));
    }

    public function test_configuration_is_isolated_during_bootstrap_without_faking_queue_or_sessions(): void
    {
        $scope = $this->scope();
        $app = $this->application();
        $scope->attach($app);
        $config = $this->configuration($app);
        // Laravel sets this flag BEFORE LoadConfiguration's afterBootstrapping callback.
        (new \ReflectionProperty(Application::class, 'hasBeenBootstrapped'))->setValue($app, true);
        $scope->configure($app);
        $root = $scope->storagePath();
        $this->assertSame($root.'/framework/views', $config->get('view.compiled'));
        $this->assertSame($root.'/framework/backup-writes.lock', $config->get('backup.lock_path'));
        $this->assertSame($root.'/framework/sessions', $config->get('session.files'));
        $this->assertSame($root.'/framework/cache/file', $config->get('cache.stores.file.path'));
        $this->assertSame($config->get('cache.stores.file.path'), $config->get('cache.stores.file.lock_path'));
        $this->assertSame($root.'/app/public', $config->get('filesystems.disks.public.root'));
        $this->assertSame($root.'/app/private', $config->get('filesystems.disks.local.root'));
        $this->assertSame($root.'/app/private/review-images', $config->get('filesystems.disks.review_images.root'));
        $this->assertSame($root.'/logs/daily.log', $config->get('logging.channels.daily.path'));
        $this->assertSame($root.'/logs/emergency.log', $config->get('logging.channels.emergency.path'));
        $this->assertSame('single', $config->get('logging.channels.external.driver'));
        $this->assertNull($config->get('logging.channels.external.url'));
        $this->assertSame('database', $config->get('queue.default'));
        $this->assertSame('database', $config->get('session.driver'));
        foreach (['blade.compiler', 'filesystem', 'log', 'queue', 'db'] as $service) {
            $this->assertFalse($app->resolved($service));
        }
    }

    public function test_scopes_are_unique_and_close_restores_all_environment_sources_without_deleting_artifacts(): void
    {
        $keys = ['APP_CONFIG_CACHE', 'APP_ROUTES_CACHE', 'APP_EVENTS_CACHE', 'APP_SERVICES_CACHE',
            'APP_PACKAGES_CACHE', 'LARAVEL_STORAGE_PATH', 'VIEW_COMPILED_PATH'];
        $snapshot = static function () use ($keys): array {
            $values = [];
            foreach ($keys as $key) {
                $values[$key] = [getenv($key), array_key_exists($key, $_ENV), $_ENV[$key] ?? null,
                    array_key_exists($key, $_SERVER), $_SERVER[$key] ?? null, Env::get($key)];
            }
            return $values;
        };
        $before = $snapshot();
        $first = $this->scope();
        $duringFirst = $snapshot();
        $second = $this->scope();
        $this->assertNotSame($first->storagePath(), $second->storagePath());
        $second->close();
        $this->assertSame($duringFirst, $snapshot());
        $first->close();
        $first->close();
        $this->assertSame($before, $snapshot());
        $this->assertDirectoryExists($first->storagePath());
        $this->assertDirectoryExists($second->storagePath());
    }

    public static function resolvedServices(): array
    {
        return array_map(static fn ($name) => [$name], [
            'blade.compiler', 'view', 'view.engine.resolver', 'filesystem', 'filesystem.disk',
            'log', 'cache', 'session', \Illuminate\Foundation\PackageManifest::class,
            \App\Services\BackupWriteBarrier::class,
        ]);
    }

    #[DataProvider('resolvedServices')]
    public function test_attach_rejects_previously_resolved_services(string $service): void
    {
        $scope = $this->scope();
        $app = $this->application();
        $app->instance($service, new \stdClass);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already resolved service');
        $scope->attach($app);
    }

    public function test_attach_rejects_configuration_loaded_too_early(): void
    {
        $scope = $this->scope();
        $app = $this->application();
        $this->configuration($app);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('precede configuration');
        $scope->attach($app);
    }

    public function test_configuration_rejects_a_service_resolved_between_bootstrap_phases(): void
    {
        $scope = $this->scope();
        $app = $this->application();
        $scope->attach($app);
        $this->configuration($app);
        $app->instance('blade.compiler', new \stdClass);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already resolved service');
        $scope->configure($app);
    }

    public function test_replaced_storage_path_is_rejected(): void
    {
        $scope = $this->scope();
        $app = $this->application();
        $scope->attach($app);
        $this->configuration($app);
        $app->useStoragePath(dirname(__DIR__, 2).'/storage');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('storage path was replaced');
        $scope->configure($app);
    }

    public function test_same_scope_supports_application_refresh_without_reusing_another_tests_scope(): void
    {
        $scope = $this->scope();
        $first = $this->application();
        $scope->attach($first);
        $second = $this->application();
        $scope->attach($second);
        $this->assertSame($first->storagePath(), $second->storagePath());
        $this->assertSame($first->getCachedServicesPath(), $second->getCachedServicesPath());
    }

    public function test_closed_scope_cannot_be_reused(): void
    {
        $scope = $this->scope();
        $scope->close();
        $this->expectException(RuntimeException::class);
        $scope->attach($this->application());
    }

    public function test_failure_restores_environment_in_finally(): void
    {
        $before = getenv('APP_CONFIG_CACHE');
        $scope = $this->scope();
        try {
            $app = $this->application();
            $scope->attach($app);
            $this->configuration($app);
            $app->instance('config_loaded_from_cache', true);
            try {
                $scope->configure($app);
                $this->fail('Cached configuration was accepted.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('uncached configuration', $exception->getMessage());
            }
        } finally {
            $scope->close();
        }
        $this->assertSame($before, getenv('APP_CONFIG_CACHE'));
    }

    public function test_shared_path_is_rejected_before_any_creation(): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaFileIsolation::assertDirectory(dirname(__DIR__, 2).'/storage/app/public', false);
    }

    public function test_parent_traversal_is_rejected(): void
    {
        $scope = $this->scope();
        $this->expectException(RuntimeException::class);
        ExistingSchemaFileIsolation::assertDirectory($scope->storagePath().'/../escape', false);
    }

    public function test_linked_ancestor_is_rejected_without_cleaning_its_target(): void
    {
        $scope = $this->scope();
        $link = $scope->storagePath().'/redirect';
        if (! @symlink($scope->storagePath().'/app/public', $link)) {
            $this->markTestSkipped('Directory symlink permission is unavailable; junction coverage is separate.');
        }
        try {
            $this->expectException(RuntimeException::class);
            ExistingSchemaFileIsolation::assertDirectory($link.'/child', false);
        } finally {
            // Only our exact link, never its target or any recursive tree.
            if (is_link($link)) {
                PHP_OS_FAMILY === 'Windows' ? rmdir($link) : unlink($link);
            }
        }
    }
}
