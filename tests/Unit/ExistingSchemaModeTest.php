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

    public static function thirdExpansionTargets(): array
    {
        return [
            [\Tests\Feature\Auth\PasswordSessionRevocationTest::class],
            [\Tests\Feature\Auth\PasswordSecurityRotationTest::class],
            [\Tests\Feature\Auth\CredentialMutationRaceTest::class],
        ];
    }

    #[DataProvider('thirdExpansionTargets')]
    public function test_third_transactional_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function fourthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\ProfileTest::class],
            [\Tests\Feature\AccountDeletionTest::class],
            [\Tests\Feature\AdminUserEmailVerificationTest::class],
            [\Tests\Feature\Auth\EmailVerificationPolicyTest::class],
        ];
    }

    #[DataProvider('fourthExpansionTargets')]
    public function test_fourth_transactional_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function fifthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\ChatAccessRegressionTest::class],
            [\Tests\Feature\OrderSellerChatSnapshotTest::class],
            [\Tests\Feature\OrderParticipantAccessRegressionTest::class],
        ];
    }

    #[DataProvider('fifthExpansionTargets')]
    public function test_fifth_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function sixthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\ProductMutationAuthorizationTest::class],
            [\Tests\Feature\AdminProductValidationTest::class],
            [\Tests\Feature\ProductAttributeValidationTest::class],
        ];
    }

    #[DataProvider('sixthExpansionTargets')]
    public function test_sixth_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function sixthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::sixthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('sixthExpansionLookalikes')]
    public function test_sixth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function seventhExpansionTargets(): array
    {
        return [
            [\Tests\Feature\ReviewModerationIntegrityTest::class],
            [\Tests\Feature\ReviewImagePrivacyTest::class],
        ];
    }

    #[DataProvider('seventhExpansionTargets')]
    public function test_seventh_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function seventhExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::seventhExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('seventhExpansionLookalikes')]
    public function test_seventh_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function eighthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\MoneyIntegrityTest::class],
            [\Tests\Feature\OrderStockTest::class],
            [\Tests\Feature\CheckoutCurrencyTest::class],
            [\Tests\Feature\CheckoutDeliverySemanticsTest::class],
        ];
    }

    #[DataProvider('eighthExpansionTargets')]
    public function test_eighth_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function eighthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::eighthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('eighthExpansionLookalikes')]
    public function test_eighth_group_lookalikes_remain_excluded(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function ninthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\CheckoutNotificationTest::class],
            [\Tests\Feature\NotificationPreferencesTest::class],
        ];
    }

    #[DataProvider('ninthExpansionTargets')]
    public function test_ninth_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function ninthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::ninthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('ninthExpansionLookalikes')]
    public function test_ninth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function tenthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\PickupOrderWorkflowTest::class],
            [\Tests\Feature\PickupSellerOrderActionsTest::class],
            [\Tests\Feature\PickupBuyerOrderActionsTest::class],
        ];
    }

    #[DataProvider('tenthExpansionTargets')]
    public function test_tenth_transactional_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function tenthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::tenthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('tenthExpansionLookalikes')]
    public function test_tenth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function eleventhExpansionTargets(): array
    {
        return [
            [\Tests\Feature\PickupOrderEventTimelineTest::class],
            [\Tests\Feature\OrderAddressSnapshotTest::class],
            [\Tests\Feature\UserAddressIntegrityTest::class],
        ];
    }

    #[DataProvider('eleventhExpansionTargets')]
    public function test_eleventh_transactional_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function eleventhExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::eleventhExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('eleventhExpansionLookalikes')]
    public function test_eleventh_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function excludedExpansionTargets(): array
    {
        return [
            [\Tests\Feature\Auth\CredentialLifecycleTest::class],
            [\Tests\Feature\ProfileDeletionConcurrencyTest::class],
            [\Tests\Feature\ChatImageConcurrencyTest::class],
            // Factory probes are not configured by DatabaseManager's SQL guard event.
            [\Tests\Feature\CheckoutNotificationAtomicityTest::class],
            [\Tests\Support\CheckoutTestCase::class],
            [\Tests\Feature\CheckoutIdempotencyConcurrencyTest::class],
            [\Tests\Feature\OrderStockConcurrencyTest::class],
            [\Tests\Feature\ProductQuotaConcurrencyTest::class],
            [\Tests\Feature\ProductImageLifecycleTest::class],
            [\Tests\Feature\ProductCountryCityValidationTest::class],
            [\Tests\Feature\ProductTransferQuotaConcurrencyTest::class],
            // DatabaseTruncation would run again after prepare() closes the cleanup scope.
            [\Tests\Feature\SellerReputationTest::class],
            [\Tests\Feature\SellerSalesAfterCommitTest::class],
            // Separate worker sessions and a factory-created PDO lock probe need their own audit.
            [\Tests\Feature\PickupOrderWorkflowConcurrencyTest::class],
            [\Tests\Feature\UserAddressConcurrencyTest::class],
            // Rejected account deletion still opens the non-isolated backup barrier lock file.
            [\Tests\Feature\PickupReceiptReminderTest::class],
            [\Tests\Feature\OrderAddressSnapshotMigrationTest::class],
            [\Tests\Feature\PickupOrderSchemaCompatibilityTest::class],
            [\Tests\Feature\RoleAccessTest::class.'Subclass'],
            [\Tests\Feature\Auth\PasswordResetTest::class.'Subclass'],
            [\Tests\Feature\Auth\CredentialMutationRaceTest::class.'Subclass'],
            [\Tests\Feature\ProfileTest::class.'Subclass'],
            [\Tests\Feature\AccountDeletionTest::class.'Subclass'],
            [\Tests\Feature\AdminUserEmailVerificationTest::class.'Subclass'],
            [\Tests\Feature\Auth\EmailVerificationPolicyTest::class.'Subclass'],
            [\Tests\Feature\ChatAccessRegressionTest::class.'Subclass'],
            [\Tests\Feature\OrderSellerChatSnapshotTest::class.'Subclass'],
            [\Tests\Feature\OrderParticipantAccessRegressionTest::class.'Subclass'],
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
