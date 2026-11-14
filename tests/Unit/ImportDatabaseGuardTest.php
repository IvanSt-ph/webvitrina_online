<?php

namespace Tests\Unit;

use App\Http\Middleware\GuardAttributeImportDatabase;
use App\Services\AttributeImport\ImportDatabaseGuard;
use App\Services\AttributeImport\ImportException;
use Illuminate\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/** No Laravel bootstrap, PDO, .env loading, or working database connections. */
final class ImportDatabaseGuardTest extends TestCase
{
    private function config(): Repository
    {
        return new Repository([
            'attribute_import' => ['allowed_database' => 'webv3_attributes_test'],
            'cache' => ['default' => 'database', 'stores' => ['database' => ['driver' => 'database']]],
            'session' => ['driver' => 'file'],
        ]);
    }

    private function connection(?string $actual): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDriverName')->willReturn('mysql');
        $connection->expects(self::once())->method('selectOne')
            ->with('SELECT DATABASE() AS database_name', [], false)
            ->willReturn((object) ['database_name' => $actual]);
        return $connection;
    }

    private function guard(Repository $config, Connection $default, ?Connection $secondary = null): ImportDatabaseGuard
    {
        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('connection')->willReturnCallback(function ($name) use ($default, $secondary) {
            self::assertContains($name, [null, 'secondary']);
            return $name === 'secondary' ? $secondary : $default;
        });
        return new ImportDatabaseGuard($manager, $config);
    }

    public function test_exact_database_is_allowed_using_write_pdo_and_connections_are_deduplicated(): void
    {
        $this->guard($this->config(), $this->connection('webv3_attributes_test'))->assertAllowed();
    }

    public static function rejectedDatabases(): array
    {
        return [['webv3'], ['webv3_testing'], ['WEBV3_ATTRIBUTES_TEST'], ['webv3_attributes_test '], [''], [null]];
    }

    #[DataProvider('rejectedDatabases')]
    public function test_other_or_unknown_database_is_rejected(?string $actual): void
    {
        $guard = $this->guard($this->config(), $this->connection($actual));
        $this->expectException(ImportException::class);
        $guard->assertAllowed();
    }

    public static function missingSettings(): array
    {
        return [[null], [''], [' '], [false], ['webv3'], ['WEBV3']];
    }

    #[DataProvider('missingSettings')]
    public function test_missing_setting_denies_before_resolving_connections(mixed $allowed): void
    {
        $config = $this->config();
        $config->set('attribute_import.allowed_database', $allowed);
        $manager = $this->createMock(DatabaseManager::class);
        $manager->expects(self::never())->method('connection');
        $this->expectException(ImportException::class);
        (new ImportDatabaseGuard($manager, $config))->assertAllowed();
    }

    public function test_connection_failure_is_closed_without_exposing_secrets(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDriverName')->willReturn('mysql');
        $connection->method('selectOne')->willThrowException(new \RuntimeException('private credentials'));
        try {
            $this->guard($this->config(), $connection)->assertAllowed();
            self::fail('Expected refusal');
        } catch (ImportException $exception) {
            self::assertStringNotContainsString('private credentials', $exception->getMessage());
            self::assertStringContainsString('Импорт заблокирован', $exception->getMessage());
        }
    }

    public function test_deployment_setting_can_allow_a_future_database(): void
    {
        $config = $this->config();
        $config->set('attribute_import.allowed_database', 'future_catalog');
        $this->guard($config, $this->connection('future_catalog'))->assertAllowed();
    }

    public static function secondaryConnections(): array
    {
        return [
            ['cache.stores.database.connection'],
            ['cache.stores.database.lock_connection'],
            ['session.connection'],
            ['cache.stores.limiter.connection'],
        ];
    }

    #[DataProvider('secondaryConnections')]
    public function test_secondary_write_connection_is_also_checked(string $key): void
    {
        $config = $this->config();
        $config->set('session.driver', 'database');
        $config->set('cache.limiter', 'limiter');
        $config->set('cache.stores.limiter.driver', 'database');
        $config->set($key, 'secondary');
        $guard = $this->guard($config, $this->connection('webv3_attributes_test'), $this->connection('webv3'));
        $this->expectException(ImportException::class);
        $guard->assertAllowed();
    }

    public static function importRoutes(): array
    {
        return [['preview'], ['apply']];
    }

    #[DataProvider('importRoutes')]
    public function test_http_refusal_never_reaches_session_throttle_or_controller(string $action): void
    {
        $guard = $this->guard($this->config(), $this->connection('webv3'));
        $request = Request::create('/admin/attribute-import/'.$action, 'POST', [
            'allowed_database' => 'webv3', 'database' => 'webv3_attributes_test',
        ]);
        $route = (new Route('POST', 'admin/attribute-import/'.$action, fn () => null))->name('admin.attribute-import.'.$action);
        $request->setRouteResolver(fn () => $route);
        $response = (new GuardAttributeImportDatabase($guard))->handle($request, function () {
            self::fail('No downstream middleware or controller may run');
        });
        self::assertSame(503, $response->getStatusCode());
        self::assertStringContainsString('Импорт заблокирован', $response->getContent());
    }

    public function test_unrelated_requests_do_not_probe_database(): void
    {
        $manager = $this->createMock(DatabaseManager::class);
        $manager->expects(self::never())->method('connection');
        $middleware = new GuardAttributeImportDatabase(new ImportDatabaseGuard($manager, $this->config()));
        self::assertSame(200, $middleware->handle(Request::create('/'), fn () => new Response('ok'))->getStatusCode());
    }

    public function test_allowed_http_request_continues_to_existing_checks(): void
    {
        $guard = $this->guard($this->config(), $this->connection('webv3_attributes_test'));
        $request = Request::create('/admin/attribute-import/apply', 'POST');
        $request->setRouteResolver(fn () => (new Route('POST', 'admin/attribute-import/apply', fn () => null))->name('admin.attribute-import.apply'));
        $response = new Response('downstream validation', 422);
        self::assertSame($response, (new GuardAttributeImportDatabase($guard))->handle($request, fn () => $response));
    }
}
