<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\{ExistingSchemaMode, ExistingSchemaMySqlConnector, TestDatabaseSafety};

/** Pure guards: no Laravel bootstrap, PDO, files or test database required. */
class ExistingSchemaModeTest extends TestCase
{
    public static function forbiddenCommands(): array
    {
        return [['migrate'], ['migrate:fresh'], ['migrate:rollback'], ['migrate:reset'],
            ['migrate:refresh'], ['migrate:install'], ['db:wipe'], ['db:seed'], ['schema:dump']];
    }

    #[DataProvider('forbiddenCommands')]
    public function test_schema_commands_fail_closed(string $command): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertCommandAllowed($command);
    }

    public static function forbiddenSql(): array
    {
        return [['CREATE TABLE example (id INT)'], ['DROP TABLE users'], ['ALTER TABLE users ADD x INT'],
            ['RENAME TABLE users TO old_users'], ['TRUNCATE TABLE `webv3_testing`.`users`'],
            ['USE webv3'], ['/* comment */ DROP TABLE users'], ['-- comment'."\n".'ALTER TABLE users ADD x INT'],
            ['/*!50000 DROP TABLE users */'], ['SELECT 1; DROP TABLE users'], ['SET GLOBAL sql_mode = 0']];
    }

    #[DataProvider('forbiddenSql')]
    public function test_schema_sql_and_cleanup_outside_authorized_scope_fail_closed(string $sql): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertSqlAllowed($sql);
    }

    public function test_normal_queries_and_pilot_targets_remain_allowed(): void
    {
        foreach (['SELECT * FROM users FOR UPDATE', 'INSERT INTO products (title) VALUES (?)',
            'UPDATE users SET seller_plan = ?', 'DELETE FROM users WHERE id = ?',
            'SET FOREIGN_KEY_CHECKS=0;', 'SHOW PROCESSLIST', 'SAVEPOINT trans2', 'ROLLBACK TO SAVEPOINT trans2'] as $sql) {
            ExistingSchemaMode::assertSqlAllowed($sql);
        }
        foreach ([\Tests\Feature\Auth\CredentialMutationConcurrencyTest::class,
            \Tests\Feature\ProductMutationConcurrencyTest::class, \Tests\Feature\ReviewModerationConcurrencyTest::class,
            \Tests\Feature\PublicReviewRatingTest::class, \Tests\Feature\CheckoutIdempotencyTest::class] as $class) {
            ExistingSchemaMode::assertTestAllowed($class);
        }
        $this->addToAssertionCount(13);
    }

    public static function firstExpansionTargets(): array
    {
        return [
            [\Tests\Feature\RoleAccessTest::class],
            [\Tests\Feature\OwnershipAccessTest::class],
            [\Tests\Feature\Auth\AuthenticationTest::class],
            [\Tests\Feature\Auth\EmailVerificationTest::class],
        ];
    }

    #[DataProvider('firstExpansionTargets')]
    public function test_first_transactional_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function secondExpansionTargets(): array
    {
        return [
            [\Tests\Feature\Auth\PasswordUpdateTest::class],
            [\Tests\Feature\Auth\PasswordResetTest::class],
            [\Tests\Feature\Auth\CurrentPasswordThrottleTest::class],
        ];
    }

    #[DataProvider('secondExpansionTargets')]
    public function test_second_transactional_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function excludedExpansionTargets(): array
    {
        return [
            [\Tests\Feature\Auth\PasswordSessionRevocationTest::class],
            [\Tests\Feature\CheckoutNotificationAtomicityTest::class],
            [\Tests\Feature\ProductQuotaConcurrencyTest::class],
            [\Tests\Feature\ProductImageLifecycleTest::class],
            [\Tests\Feature\OrderAddressSnapshotMigrationTest::class],
            [\Tests\Feature\PickupOrderSchemaCompatibilityTest::class],
            [\Tests\Feature\RoleAccessTest::class.'Subclass'],
            [\Tests\Feature\Auth\PasswordResetTest::class.'Subclass'],
        ];
    }

    #[DataProvider('excludedExpansionTargets')]
    public function test_expansion_does_not_admit_other_classes_or_name_prefixes(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public function test_migration_test_is_rejected_before_application_creation(): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed(\Tests\Feature\AccountDeletionMigrationTest::class);
    }

    public function test_unreviewed_test_is_not_silently_enabled(): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed(\Tests\Feature\SecurityRegressionTest::class);
    }

    public function test_production_database_configuration_is_rejected_without_connecting(): void
    {
        $this->expectException(RuntimeException::class);
        TestDatabaseSafety::assertConfiguration('testing', ['default' => 'mysql', 'connections' => ['mysql' => [
            'driver' => 'mysql', 'database' => 'webv3', 'username' => 'webvitrina_test',
            'host' => '127.127.126.9', 'port' => 3306,
        ]]]);
    }

    public function test_connector_refuses_production_before_constructing_pdo(): void
    {
        $this->expectException(RuntimeException::class);
        (new ExistingSchemaMySqlConnector('testing'))->connect([
            'driver' => 'mysql', 'database' => 'webv3', 'username' => 'webvitrina_test',
            'host' => '127.127.126.9', 'port' => 3306,
        ]);
    }
}
