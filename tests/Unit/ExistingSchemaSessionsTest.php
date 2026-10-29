<?php

namespace Tests\Unit;

use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\ExistingSchemaSessions;

class ExistingSchemaSessionsTest extends TestCase
{
    private function ownedPdo(int $id, bool $transaction = false): PDO
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchColumn')->willReturn($id);
        $statement->expects($this->once())->method('closeCursor');
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('query')->with('SELECT CONNECTION_ID()')->willReturn($statement);
        $pdo->method('inTransaction')->willReturn($transaction);
        ExistingSchemaSessions::register($pdo);
        return $pdo;
    }

    private function session(int $id, string $command = 'Sleep', string $database = 'webv3_testing'): object
    {
        return (object) ['Id' => $id, 'Command' => $command, 'db' => $database, 'User' => 'webvitrina_test'];
    }

    public function test_current_and_own_idle_previous_scenario_connection_are_allowed(): void
    {
        $pdo = $this->ownedPdo(101);
        ExistingSchemaSessions::assertCanClean(102, [$this->session(101), $this->session(102, 'Query')]);
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public static function unsafeSessions(): array
    {
        return [['Sleep', false, 'webv3_testing', false], // unknown idle worker/runner
            ['Query', false, 'webv3_testing', false], // unknown active session
            ['Query', false, 'webv3_testing', true], // own active connection
            ['Sleep', true, 'webv3_testing', true], // own uncommitted transaction
            ['Sleep', false, 'webv3', true]];
    }

    #[DataProvider('unsafeSessions')]
    public function test_unknown_active_or_transactional_connections_block_cleanup(string $command, bool $transaction, string $database, bool $owned): void
    {
        static $nextId = 1000;
        $id = $nextId++;
        $pdo = $owned ? $this->ownedPdo($id, $transaction) : null;
        $this->expectException(RuntimeException::class);
        ExistingSchemaSessions::assertCanClean(202, [$this->session($id, $command, $database)]);
    }

    public function test_dead_pdo_id_is_not_an_ownership_allowlist(): void
    {
        // A real object without a PHPUnit mock reference retained by the test case.
        $pdo = new class extends PDO {
            public function __construct() {}
            public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
            {
                return new class extends PDOStatement {
                    public function __construct() {}
                    public function fetchColumn(int $column = 0): mixed { return 301; }
                    public function closeCursor(): bool { return true; }
                };
            }
        };
        ExistingSchemaSessions::register($pdo);
        $reference = \WeakReference::create($pdo);
        unset($pdo);
        $this->assertNull($reference->get());
        $this->expectException(RuntimeException::class);
        ExistingSchemaSessions::assertCanClean(302, [$this->session(301)]);
    }
}
