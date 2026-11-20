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
            ['TRUNCATE TABLE `webv3_testing`.`telegram_phone_verifications`'],
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

    public static function twelfthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\Auth\PasswordConfirmationTest::class],
            [\Tests\Feature\Auth\GoogleAuthenticationTest::class],
        ];
    }

    #[DataProvider('twelfthExpansionTargets')]
    public function test_twelfth_transactional_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function twelfthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::twelfthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('twelfthExpansionLookalikes')]
    public function test_twelfth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function thirteenthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\BuyerPhoneProfileTest::class],
            [\Tests\Feature\PhoneVerificationRaceTest::class],
        ];
    }

    #[DataProvider('thirteenthExpansionTargets')]
    public function test_thirteenth_transactional_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function thirteenthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::thirteenthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('thirteenthExpansionLookalikes')]
    public function test_thirteenth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function fourteenthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\FavoriteCartIntegrityTest::class],
            [\Tests\Feature\ShopPhoneProfileTest::class],
            [\Tests\Feature\PhoneUniquenessTest::class],
            [\Tests\Feature\ProductStatTest::class],
            [\Tests\Feature\CurrencyProxySecurityTest::class],
            [\Tests\Feature\PickupOrderActionTabsTest::class],
            [\Tests\Feature\PickupOrderInterfaceCompatibilityTest::class],
            [\Tests\Feature\PickupOrderStatisticsTest::class],
        ];
    }

    #[DataProvider('fourteenthExpansionTargets')]
    public function test_fourteenth_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function fourteenthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::fourteenthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('fourteenthExpansionLookalikes')]
    public function test_fourteenth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function fifteenthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\ProductSlugTest::class],
            [\Tests\Feature\PublicLegalPagesTest::class],
            [\Tests\Feature\CategoryAjaxResultsTest::class],
            [\Tests\Feature\PublicCatalogValidationTest::class],
            [\Tests\Feature\StructuredDataTest::class],
            [\Tests\Feature\ExampleTest::class],
            [\Tests\Feature\ProductTransferQuotaTest::class],
            [\Tests\Feature\ProductCountryCityValidationTest::class],
        ];
    }

    #[DataProvider('fifteenthExpansionTargets')]
    public function test_fifteenth_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function fifteenthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::fifteenthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('fifteenthExpansionLookalikes')]
    public function test_fifteenth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function sixteenthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\ProductSearchTest::class],
            [\Tests\Feature\ProductQuotaTest::class],
            [\Tests\Feature\SellerPlanQuotaTest::class],
            [\Tests\Feature\SellerPlanRequestStateTest::class],
            [\Tests\Feature\AdminMembershipTest::class],
            [\Tests\Feature\CheckoutPickupWorkflowTest::class],
        ];
    }

    #[DataProvider('sixteenthExpansionTargets')]
    public function test_sixteenth_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function sixteenthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::sixteenthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('sixteenthExpansionLookalikes')]
    public function test_sixteenth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function seventeenthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\UnsupportedOrderWorkflowTest::class],
            [\Tests\Feature\PickupReceiptReminderTest::class],
            [\Tests\Feature\Auth\CredentialLifecycleTest::class],
        ];
    }

    #[DataProvider('seventeenthExpansionTargets')]
    public function test_seventeenth_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function seventeenthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::seventeenthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('seventeenthExpansionLookalikes')]
    public function test_seventeenth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function eighteenthExpansionTargets(): array
    {
        return [
            [\Tests\Feature\ProfileDeletionRaceTest::class],
            [\Tests\Feature\ReviewImageMigrationTest::class],
        ];
    }

    #[DataProvider('eighteenthExpansionTargets')]
    public function test_eighteenth_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function eighteenthExpansionLookalikes(): array
    {
        $cases = [];
        foreach (self::eighteenthExpansionTargets() as [$class]) {
            $cases[] = [$class.'Subclass'];
            $cases[] = [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)];
            $cases[] = [strtolower($class)];
        }

        return $cases;
    }

    #[DataProvider('eighteenthExpansionLookalikes')]
    public function test_eighteenth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function nineteenthExpansionTargets(): array
    {
        return [[\Tests\Feature\MarketplaceFunctionalRegressionTest::class]];
    }

    #[DataProvider('nineteenthExpansionTargets')]
    public function test_nineteenth_group_is_allowed(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
    }

    public static function nineteenthExpansionLookalikes(): array
    {
        $class = \Tests\Feature\MarketplaceFunctionalRegressionTest::class;

        return [
            [$class.'Subclass'],
            [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)],
            [strtolower($class)],
        ];
    }

    #[DataProvider('nineteenthExpansionLookalikes')]
    public function test_nineteenth_group_requires_exact_class_names(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public function test_telegram_transactional_test_is_allowed(): void
    {
        ExistingSchemaMode::assertTestAllowed(\Tests\Feature\TelegramPhoneVerificationTest::class);
        $this->addToAssertionCount(1);
    }

    public static function telegramLookalikes(): array
    {
        $class = \Tests\Feature\TelegramPhoneVerificationTest::class;

        return [
            [$class.'Subclass'],
            [str_replace('Tests\\Feature\\', 'Tests\\Unit\\', $class)],
            [strtolower($class)],
        ];
    }

    #[DataProvider('telegramLookalikes')]
    public function test_telegram_allowlist_requires_exact_class_name(string $class): void
    {
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class);
    }

    public static function excludedExpansionTargets(): array
    {
        return [
            // Group eighteen inventory: all remaining Feature classes stay excluded.
            [\Tests\Feature\AdminAvatarConcurrencyTest::class],
            [\Tests\Feature\BackupAtomicityTest::class],
            [\Tests\Feature\BackupRetentionTest::class],
            [\Tests\Feature\BackupSnapshotTest::class],
            [\Tests\Feature\BackupWriteBarrierTest::class],
            [\Tests\Feature\PrivateBackupTest::class],
            [\Tests\Feature\ReleaseExperienceTest::class],
            [\Tests\Feature\SafeRestoreTest::class],
            [\Tests\Feature\SellerMetricsConcurrencyTest::class],
            [\Tests\Feature\SellerPlanQuotaConcurrencyTest::class],
            [\Tests\Feature\SellerPlanRequestStateConcurrencyTest::class],
            // Group seventeen: real commits/probes, workers or shared filesystem cleanup.
            [\Tests\Feature\OperationalHealthTest::class],
            [\Tests\Feature\PurgeOrderHistoryTest::class],
            // Real commits/workers, repeated truncation and DDL remain excluded.
            [\Tests\Feature\AdminMembershipConcurrencyTest::class],
            [\Tests\Feature\ShopBannerLifecycleTest::class],
            [\Tests\Feature\AccountDeletionMigrationTest::class],
            [\Tests\Feature\ProfileDeletionConcurrencyTest::class],
            // Real image workers remain outside the isolated in-process profile group.
            [\Tests\Feature\ChatImageConcurrencyTest::class],
            [\Tests\Support\CheckoutTestCase::class],
            [\Tests\Feature\ProductQuotaConcurrencyTest::class],
            [\Tests\Feature\ProductImageLifecycleTest::class],
            [\Tests\Feature\ProductTransferQuotaConcurrencyTest::class],
            // DatabaseTruncation would run again after prepare() closes the cleanup scope.
            [\Tests\Feature\SellerReputationTest::class],
            [\Tests\Feature\SellerSalesAfterCommitTest::class],
            // Separate worker sessions and a factory-created PDO lock probe need their own audit.
            [\Tests\Feature\UserAddressConcurrencyTest::class],
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
        ExistingSchemaMode::assertTestAllowed(\Tests\Feature\ReleaseExperienceTest::class);
    }

    public static function priorityTargets(): array
    {
        return [
            [\Tests\Feature\CheckoutIdempotencyConcurrencyTest::class],
            [\Tests\Feature\OrderStockConcurrencyTest::class],
            [\Tests\Feature\PickupOrderWorkflowConcurrencyTest::class],
            [\Tests\Feature\PhoneUniquenessConcurrencyTest::class],
            [\Tests\Feature\Auth\CredentialConcurrencyTest::class],
            [\Tests\Feature\TransactionCallbackContractTest::class],
            [\Tests\Feature\AdminAvatarLifecycleTest::class],
            [\Tests\Feature\ChatImageLifecycleTest::class],
            [\Tests\Feature\OrderIdentitySnapshotTest::class],
            [\Tests\Feature\InputRenderingSecurityTest::class],
            [\Tests\Feature\Auth\RegistrationTest::class],
            [\Tests\Feature\CheckoutNotificationAtomicityTest::class],
            [\Tests\Feature\SecurityRegressionTest::class],
        ];
    }

    #[DataProvider('priorityTargets')]
    public function test_reviewed_priority_class_is_allowed_but_subclasses_are_not(string $class): void
    {
        ExistingSchemaMode::assertTestAllowed($class);
        $this->addToAssertionCount(1);
        $this->expectException(RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed($class.'Subclass');
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
