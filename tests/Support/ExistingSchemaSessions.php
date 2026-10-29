<?php

namespace Tests\Support;

use PDO;
use RuntimeException;
use WeakMap;

/** Process-local ownership; never retain a PDO or trust a previously closed ID. */
final class ExistingSchemaSessions
{
    private static ?WeakMap $owned = null;

    public static function register(PDO $pdo): void
    {
        $statement = $pdo->query('SELECT CONNECTION_ID()');
        if ($statement === false) {
            throw new RuntimeException('Cannot identify the test PDO session.');
        }
        try {
            $id = (int) $statement->fetchColumn();
        } finally {
            $statement->closeCursor();
        }
        if ($id <= 0) {
            throw new RuntimeException('Invalid test PDO session ID.');
        }
        self::$owned ??= new WeakMap();
        self::$owned[$pdo] = $id;
    }

    public static function assertCanClean(int $currentId, array $processes): void
    {
        $live = [];
        foreach (self::$owned ?? [] as $pdo => $id) {
            $live[$id] = $pdo;
        }
        foreach ($processes as $process) {
            $id = (int) $process->Id;
            if ($id === $currentId) {
                continue;
            }
            // Sleep alone is not ownership. A live PDO from this process is mandatory.
            if (! isset($live[$id]) || $live[$id]->inTransaction()
                || ($process->Command ?? null) !== 'Sleep'
                || ($process->db ?? null) !== 'webv3_testing'
                || ($process->User ?? null) !== 'webvitrina_test') {
                throw new RuntimeException('Unknown, active or transactional MySQL session '.$id.'; fixture cleanup refused. Stop other test runs/workers first.');
            }
        }
    }
}
