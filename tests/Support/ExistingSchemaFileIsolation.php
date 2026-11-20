<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Env;
use RuntimeException;

/** Test-owned paths only. Artifacts are deliberately retained, including on failure. */
final class ExistingSchemaFileIsolation
{
    private array $environment = [];
    private bool $closed = false;

    private const CACHE_FILES = [
        'APP_CONFIG_CACHE' => 'config.php',
        'APP_ROUTES_CACHE' => 'routes.php',
        'APP_EVENTS_CACHE' => 'events.php',
        'APP_SERVICES_CACHE' => 'services.php',
        'APP_PACKAGES_CACHE' => 'packages.php',
    ];

    private function __construct(private readonly string $root) {}

    public static function create(): self
    {
        $scope = new self(dirname(__DIR__, 2).'/storage/framework/testing/existing-schema-'.bin2hex(random_bytes(16)));
        try {
            $scope->directory($scope->root, fresh: true);
            foreach (['storage', 'storage/framework', 'storage/framework/views',
                'storage/framework/cache', 'storage/framework/cache/data', 'storage/framework/sessions',
                'storage/framework/testing', 'storage/framework/testing/disks',
                'storage/app', 'storage/app/public', 'storage/app/private',
                'storage/app/private/review-images', 'storage/logs', 'bootstrap-cache'] as $relative) {
                $scope->directory($scope->root.'/'.$relative, fresh: true);
            }
            // Before Application construction: package manifests capture their path lazily.
            foreach (self::CACHE_FILES as $key => $file) {
                $scope->setEnvironment($key, $scope->root.'/bootstrap-cache/'.$file);
            }
            $scope->setEnvironment('LARAVEL_STORAGE_PATH', $scope->storagePath());
            $scope->setEnvironment('VIEW_COMPILED_PATH', $scope->storagePath().'/framework/views');

            return $scope;
        } catch (\Throwable $exception) {
            $scope->close();
            throw $exception;
        }
    }

    public function storagePath(): string
    {
        return $this->root.'/storage';
    }

    /** Adopt the parent's validated scope; never create, clean or replace it. */
    public static function inherit(): self
    {
        $storage = getenv('LARAVEL_STORAGE_PATH');
        $prefix = dirname(__DIR__, 2).'/storage/framework/testing/existing-schema-';
        if (! is_string($storage) || ! preg_match('~\A'.preg_quote($prefix, '~').'[a-f0-9]{32}/storage\z~D', $storage)) {
            throw new RuntimeException('Worker requires an explicit parent isolation directory.');
        }
        $scope = new self(dirname($storage));
        $scope->assertPaths();
        $expected = ['LARAVEL_STORAGE_PATH' => $storage, 'VIEW_COMPILED_PATH' => $storage.'/framework/views'];
        foreach (self::CACHE_FILES as $key => $file) {
            $expected[$key] = $scope->root.'/bootstrap-cache/'.$file;
        }
        foreach ($expected as $key => $path) {
            if (getenv($key) !== $path || Env::get($key) !== $path) {
                throw new RuntimeException('Inherited worker path mismatch: '.$key);
            }
        }

        return $scope;
    }

    /** Must run before the kernel bootstraps configuration or providers. */
    public function attach(Application $app): void
    {
        $this->assertEarly($app);
        if ($app->bound('config') || $app->hasBeenBootstrapped()) {
            throw new RuntimeException('File isolation must precede configuration loading.');
        }
        $this->assertPaths();
        $app->useStoragePath($this->storagePath());
        // Laravel otherwise treats drive-letter paths as relative to base_path().
        $app->addAbsoluteCachePathPrefix($this->root.'/');
        $this->assertCachePaths($app);
    }

    /** Called immediately after LoadConfiguration, before providers resolve services. */
    public function configure(Application $app): void
    {
        $this->assertEarly($app);
        $this->assertPaths();
        $this->assertCachePaths($app);
        if ($app->storagePath() !== $this->storagePath()) {
            throw new RuntimeException('Isolated storage path was replaced.');
        }
        $config = $app['config'];
        if ($app->bound('config_loaded_from_cache') && $app['config_loaded_from_cache']) {
            throw new RuntimeException('Existing-schema file isolation requires uncached configuration.');
        }
        $config->set('view.compiled', $this->storagePath().'/framework/views');
        $config->set('backup.lock_path', $this->storagePath().'/framework/backup-writes.lock');
        $config->set('session.files', $this->storagePath().'/framework/sessions');
        foreach ($config->get('cache.stores', []) as $name => $store) {
            if (($store['driver'] ?? null) === 'file') {
                $this->assertName((string) $name);
                $path = $this->storagePath().'/framework/cache/'.$name;
                $this->directory($path);
                $config->set('cache.stores.'.$name.'.path', $path);
                $config->set('cache.stores.'.$name.'.lock_path', $path);
            }
        }
        foreach (['public' => 'app/public', 'local' => 'app/private', 'review_images' => 'app/private/review-images'] as $name => $relative) {
            if ($config->get('filesystems.disks.'.$name.'.driver') !== 'local') {
                throw new RuntimeException('Expected a local test disk: '.$name);
            }
            $config->set('filesystems.disks.'.$name.'.root', $this->storagePath().'/'.$relative);
        }
        // Keep real logging, including named channels, but never external transports or taps.
        foreach ($config->get('logging.channels', []) as $name => $channel) {
            $this->assertName((string) $name);
            $path = $this->storagePath().'/logs/'.$name.'.log';
            if ($name === 'emergency') {
                $config->set('logging.channels.'.$name, ['path' => $path]);
            } elseif (($channel['handler'] ?? null) === \Monolog\Handler\NullHandler::class) {
                $config->set('logging.channels.'.$name, [
                    'driver' => 'monolog', 'handler' => \Monolog\Handler\NullHandler::class,
                ]);
            } elseif (($channel['driver'] ?? null) !== 'stack') {
                $config->set('logging.channels.'.$name, [
                    'driver' => ($channel['driver'] ?? null) === 'daily' ? 'daily' : 'single',
                    'path' => $path, 'level' => $channel['level'] ?? 'debug',
                    'days' => $channel['days'] ?? 14,
                ]);
            } else {
                $config->set('logging.channels.'.$name, [
                    'driver' => 'stack', 'channels' => $channel['channels'] ?? [],
                    'ignore_exceptions' => $channel['ignore_exceptions'] ?? false,
                ]);
            }
        }
    }

    /** No filesystem deletion, and no service resolution during restoration. */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        foreach ($this->environment as $key => [$process, $envExists, $env, $serverExists, $server]) {
            $process === false ? putenv($key) : putenv($key.'='.$process);
            if ($envExists) {
                $_ENV[$key] = $env;
            } else {
                unset($_ENV[$key]);
            }
            if ($serverExists) {
                $_SERVER[$key] = $server;
            } else {
                unset($_SERVER[$key]);
            }
        }
        $this->closed = true;
    }

    private function assertEarly(Application $app): void
    {
        if ($this->closed || $app->isBooted()) {
            throw new RuntimeException('File isolation must be installed before application bootstrap.');
        }
        foreach (['blade.compiler', 'view', 'view.engine.resolver', 'filesystem', 'filesystem.disk',
            'filesystem.cloud', 'log', 'cache', 'cache.store', 'session', 'session.store',
            PackageManifest::class, \App\Services\BackupWriteBarrier::class] as $service) {
            if ($app->resolved($service)) {
                throw new RuntimeException('File isolation refuses an already resolved service: '.$service);
            }
        }
    }

    private function assertCachePaths(Application $app): void
    {
        foreach (['getCachedConfigPath' => 'config.php', 'getCachedRoutesPath' => 'routes.php',
            'getCachedEventsPath' => 'events.php', 'getCachedServicesPath' => 'services.php',
            'getCachedPackagesPath' => 'packages.php'] as $method => $file) {
            if ($app->$method() !== $this->root.'/bootstrap-cache/'.$file) {
                throw new RuntimeException('Bootstrap cache escaped the test scope: '.$method);
            }
            $path = $app->$method();
            if (is_link($path) || (file_exists($path) && ! is_file($path))) {
                throw new RuntimeException('Unsafe bootstrap cache file.');
            }
            if ($file === 'config.php' && file_exists($path)) {
                throw new RuntimeException('Refusing to load cached configuration in a test scope.');
            }
        }
    }

    private function assertPaths(): void
    {
        foreach ([$this->root, $this->storagePath(), $this->root.'/bootstrap-cache',
            $this->storagePath().'/framework/views', $this->storagePath().'/framework/cache',
            $this->storagePath().'/framework/sessions', $this->storagePath().'/framework/testing/disks',
            $this->storagePath().'/app/public', $this->storagePath().'/app/private/review-images',
            $this->storagePath().'/logs'] as $path) {
            self::assertDirectory($path);
        }
        $lock = $this->storagePath().'/framework/backup-writes.lock';
        if (is_link($lock) || (file_exists($lock) && ! is_file($lock))) {
            throw new RuntimeException('Unsafe backup lock file.');
        }
    }

    private function setEnvironment(string $key, string $value): void
    {
        $this->environment[$key] = [getenv($key), array_key_exists($key, $_ENV), $_ENV[$key] ?? null,
            array_key_exists($key, $_SERVER), $_SERVER[$key] ?? null];
        if (! putenv($key.'='.$value)) {
            throw new RuntimeException('Cannot isolate test environment path: '.$key);
        }
        $_ENV[$key] = $_SERVER[$key] = $value;
        if (Env::get($key) !== $value) {
            throw new RuntimeException('Laravel environment repository rejected isolated path: '.$key);
        }
    }

    private function assertName(string $name): void
    {
        if (! preg_match('/\A[a-zA-Z0-9_-]+\z/', $name)) {
            throw new RuntimeException('Unsafe filesystem resource name.');
        }
    }

    private function directory(string $path, bool $fresh = false): void
    {
        self::assertDirectory($path, false);
        if (file_exists($path) || is_link($path)) {
            if ($fresh) {
                throw new RuntimeException('Refusing to reuse an isolation directory.');
            }
            return;
        }
        // Parents were validated above even on a checkout without a testing directory.
        // Recursive creation is permitted; recursive deletion is never performed.
        self::assertDirectory($path, false);
        if (! mkdir($path, 0700, true)) {
            throw new RuntimeException('Cannot create isolation directory.');
        }
        self::assertDirectory($path);
    }

    public static function assertDirectory(string $path, bool $mustExist = true): void
    {
        clearstatcache(true);
        TestFileSafety::assertDirectory($path, $mustExist);
        for ($ancestor = $path; ; $ancestor = $parent) {
            if (is_link($ancestor)) {
                throw new RuntimeException('Refusing a linked isolation path or ancestor.');
            }
            $parent = dirname($ancestor);
            if ($parent === $ancestor) {
                break;
            }
        }
        // Not an atomic handle-based check: concurrent hostile path replacement is unsupported.
    }
}
