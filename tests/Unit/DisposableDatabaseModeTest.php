<?php

namespace Tests\Unit;

use Illuminate\Database\Connection;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\DisposableDatabaseMode;

/** No application bootstrap, network, SQL execution or real PDO connections. */
final class DisposableDatabaseModeTest extends TestCase
{
    private function pdo(string $database = 'webv3_full_testing'): PDO
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->with(PDO::FETCH_NUM)->willReturn([$database, 'webvitrina_full_test@%']);
        $statement->expects($this->once())->method('closeCursor');
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->with(PDO::ATTR_DRIVER_NAME)->willReturn('mysql');
        $pdo->expects($this->once())->method('query')->with('SELECT DATABASE(), CURRENT_USER()')->willReturn($statement);

        return $pdo;
    }

    public function test_disconnected_query_validates_before_reconnect_and_checks_restored_pdo(): void
    {
        $events = [];
        $pdo = $this->pdo();
        $connection = new Connection(null);
        $connection->setReconnector(function (Connection $connection) use ($pdo, &$events): void {
            $events[] = 'reconnect';
            $connection->setPdo($pdo);
        });
        DisposableDatabaseMode::prepareQueryConnection($connection, function () use (&$events): void {
            $events[] = 'validate';
        });
        $this->assertSame(['validate', 'reconnect', 'validate'], $events);
        $this->assertSame($pdo, $connection->getRawPdo());
    }

    public function test_rejected_configuration_never_reconnects_or_resolves_pdo(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('reconnectIfMissingConnection');
        $connection->expects($this->never())->method('getPdo');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsafe configuration');
        DisposableDatabaseMode::prepareQueryConnection($connection, static function (): void {
            throw new RuntimeException('Unsafe configuration');
        });
    }

    public function test_configuration_changed_during_reconnect_is_rejected_before_pdo_resolution(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('reconnectIfMissingConnection');
        $connection->expects($this->never())->method('getPdo');
        $checks = 0;
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration changed');
        DisposableDatabaseMode::prepareQueryConnection($connection, static function () use (&$checks): void {
            if (++$checks === 2) {
                throw new RuntimeException('Configuration changed');
            }
        });
    }

    public function test_restored_pdo_cannot_target_production(): void
    {
        $pdo = $this->pdo('webv3');
        $connection = new Connection(null);
        $connection->setReconnector(static function (Connection $connection) use ($pdo): void {
            $connection->setPdo($pdo);
        });
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Actual PDO is not the approved disposable database/account.');
        DisposableDatabaseMode::prepareQueryConnection($connection, static function (): void {});
    }

    public function test_connected_query_keeps_original_pdo(): void
    {
        $pdo = $this->pdo();
        $connection = new Connection($pdo);
        $connection->setReconnector(static function (): void {
            throw new RuntimeException('Unexpected reconnect');
        });
        DisposableDatabaseMode::prepareQueryConnection($connection, static function (): void {});
        $this->assertSame($pdo, $connection->getRawPdo());
    }
}
