<?php

namespace Tests\Support;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use RuntimeException;

/** Opt-in pilot: existing schema, guarded fixture cleanup, no schema commands. */
final class ExistingSchemaMode
{
    private static mixed $runLock = null;
    private static bool $cleaning = false;

    private const TESTS = [
        \Tests\Feature\Auth\CredentialMutationConcurrencyTest::class,
        \Tests\Feature\ProductMutationConcurrencyTest::class,
        \Tests\Feature\ReviewModerationConcurrencyTest::class,
        \Tests\Feature\PublicReviewRatingTest::class,
        \Tests\Feature\CheckoutIdempotencyTest::class,
        // First expansion: self-contained fixtures, standard RefreshDatabase transactions.
        \Tests\Feature\RoleAccessTest::class,
        \Tests\Feature\OwnershipAccessTest::class,
        \Tests\Feature\Auth\AuthenticationTest::class,
        \Tests\Feature\Auth\EmailVerificationTest::class,
        // Second expansion: password changes, resets and local throttle fixtures.
        \Tests\Feature\Auth\PasswordUpdateTest::class,
        \Tests\Feature\Auth\PasswordResetTest::class,
        \Tests\Feature\Auth\CurrentPasswordThrottleTest::class,
        // Third expansion: session revocation, rotation and in-process stale snapshots.
        \Tests\Feature\Auth\PasswordSessionRevocationTest::class,
        \Tests\Feature\Auth\PasswordSecurityRotationTest::class,
        \Tests\Feature\Auth\CredentialMutationRaceTest::class,
        // Fourth expansion: profile, account deletion and email verification policy.
        \Tests\Feature\ProfileTest::class,
        \Tests\Feature\AccountDeletionTest::class,
        \Tests\Feature\AdminUserEmailVerificationTest::class,
        \Tests\Feature\Auth\EmailVerificationPolicyTest::class,
        // Fifth expansion: chat access, snapshots and explicitly rolled-back order fixtures.
        \Tests\Feature\ChatAccessRegressionTest::class,
        \Tests\Feature\OrderSellerChatSnapshotTest::class,
        \Tests\Feature\OrderParticipantAccessRegressionTest::class,
        // Sixth expansion: product authorization/validation and guarded attribute rollback.
        \Tests\Feature\ProductMutationAuthorizationTest::class,
        \Tests\Feature\AdminProductValidationTest::class,
        // No RefreshDatabase: cleanup precedes setUp's transaction; teardown rolls back all levels.
        \Tests\Feature\ProductAttributeValidationTest::class,
        // Seventh expansion: transactional review moderation and isolated image storage.
        \Tests\Feature\ReviewModerationIntegrityTest::class,
        \Tests\Feature\ReviewImagePrivacyTest::class,
        // SellerReputationTest remains excluded: its trait repeats truncation outside prepare().
        // Eighth expansion: isolated checkout storage and rejected account deletion.
        \Tests\Feature\MoneyIntegrityTest::class,
        \Tests\Feature\OrderStockTest::class,
        \Tests\Feature\CheckoutCurrencyTest::class,
        \Tests\Feature\CheckoutDeliverySemanticsTest::class,
        // Ninth expansion: real commits and notification preferences, isolated public storage.
        \Tests\Feature\CheckoutNotificationTest::class,
        \Tests\Feature\NotificationPreferencesTest::class,
        // CheckoutNotificationAtomicityTest stays excluded: its factory probe bypasses
        // ConnectionEstablished SQL guards and opens PDO before its disconnect finally.
        // Tenth expansion: pickup lifecycle and participant actions in RefreshDatabase transactions.
        \Tests\Feature\PickupOrderWorkflowTest::class,
        \Tests\Feature\PickupSellerOrderActionsTest::class,
        \Tests\Feature\PickupBuyerOrderActionsTest::class,
        // Real-commit, worker/probe and DDL coverage remains outside this group.
    ];

    public static function enabled(): bool
    {
        $value = getenv('WV_EXISTING_SCHEMA');
        if ($value !== false && $value !== '' && $value !== '1') {
            throw new RuntimeException('WV_EXISTING_SCHEMA must be unset or exactly 1.');
        }
        return $value === '1';
    }

    public static function assertTestAllowed(string $class): void
    {
        if (! in_array($class, self::TESTS, true)) {
            throw new RuntimeException('Existing-schema pilot does not allow '.$class.'. Migration/schema tests and unreviewed classes are excluded.');
        }
    }

    public static function lockRun(): void
    {
        if (is_resource(self::$runLock)) {
            return;
        }
        $handle = fopen(sys_get_temp_dir().'/webvitrina-existing-schema-testing.lock', 'c+b');
        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new RuntimeException('Another existing-schema test run is active. Run targets sequentially.');
        }
        // Process-owned; do not release between PHPUnit data sets or unlink the file.
        self::$runLock = $handle;
    }

    public static function install(Application $app): void
    {
        $database = $app['config']->get('database');
        TestDatabaseSafety::assertConfiguration($app->environment(), $database);
        // Prevent accidental resolution of other configured database connections.
        $database['connections'] = ['mysql' => $database['connections']['mysql']];
        $app['config']->set('database', $database);
        // Covers lazy PDO resolution, reconnects and db.factory probe connections.
        $app->bind('db.connector.mysql', static fn () => new ExistingSchemaMySqlConnector($app->environment()));
        $app['events']->listen(CommandStarting::class, static function (CommandStarting $event): void {
            self::assertCommandAllowed((string) $event->command);
        });
        $app['events']->listen(ConnectionEstablished::class, static function (ConnectionEstablished $event) use ($app): void {
            TestDatabaseSafety::assertMetadata($app->environment(), $app['config']->get('database'), $event->connection);
            $event->connection->beforeExecuting(static function ($sql, $bindings, $connection) use ($app): void {
                TestDatabaseSafety::assertMetadata($app->environment(), $app['config']->get('database'), $connection);
                self::assertSqlAllowed($sql);
            });
        });
    }

    public static function assertCommandAllowed(string $command): void
    {
        if (preg_match('/^(?:migrate(?::|$)|db:(?:wipe|seed)(?:$|:)|schema:)/i', $command)) {
            throw new RuntimeException('Command forbidden in existing-schema mode: '.$command);
        }
    }

    public static function assertSqlAllowed(string $sql): void
    {
        if (preg_match('/;\s*\S/', $sql)) {
            throw new RuntimeException('Compound SQL is forbidden in existing-schema mode.');
        }
        // Ignore leading comments; executable MySQL comments are never allowed.
        if (str_contains($sql, '/*!')) {
            throw new RuntimeException('Executable SQL comments are forbidden in existing-schema mode.');
        }
        $sql = preg_replace('/\A(?:\s+|\/\*.*?\*\/|--[^\r\n]*(?:\r?\n|$)|\#[^\r\n]*(?:\r?\n|$))*/s', '', $sql);
        if (preg_match('/\ASET\s+(?:GLOBAL|PERSIST|PERSIST_ONLY)\b/i', $sql)) {
            throw new RuntimeException('Global SQL settings are forbidden in existing-schema mode.');
        }
        if (self::$cleaning && preg_match('/\ATRUNCATE\s+TABLE\s+`webv3_testing`\.`[a-zA-Z0-9_]+`\s*;?\z/i', $sql)) {
            return;
        }
        if (! preg_match('/\A(?:SELECT|INSERT|UPDATE|DELETE|REPLACE|SET|SHOW|EXPLAIN|WITH|SAVEPOINT|RELEASE|ROLLBACK)\b/i', $sql)) {
            throw new RuntimeException('Schema/administrative SQL forbidden in existing-schema mode.');
        }
    }

    public static function prepare(Application $app): void
    {
        $connection = $app['db']->connection();
        TestDatabaseSafety::assertMetadata($app->environment(), $app['config']->get('database'), $connection);
        $connection->getPdo();
        TestDatabaseSafety::assertConnected($app->environment(), $app['config']->get('database'), $connection);
        if ($connection->transactionLevel() !== 0 || $connection->getPdo()->inTransaction()) {
            throw new RuntimeException('Cannot clean fixtures inside an open transaction.');
        }
        $id = (int) $connection->selectOne('SELECT CONNECTION_ID() AS id')->id;
        ExistingSchemaSessions::assertCanClean($id, $connection->select('SHOW PROCESSLIST'));
        self::$cleaning = true;
        try {
            (new ExistingSchemaCleaner($app))->clean();
        } finally {
            self::$cleaning = false;
        }
        // Set only AFTER guarded cleanup. Existing beginDatabaseTransaction overrides
        // still run unchanged, retaining genuine commits and worker-visible fixtures.
        RefreshDatabaseState::$migrated = true;
    }
}
