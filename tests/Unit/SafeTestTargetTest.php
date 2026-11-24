<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\{SafeTestTarget, ExistingSchemaMode};

final class SafeTestTargetTest extends TestCase
{
    public function test_explicit_separate_targets(): void
    {
        foreach (['ProductMutationConcurrencyTest', 'ReviewModerationConcurrencyTest', 'Auth/CredentialMutationConcurrencyTest'] as $name) {
            SafeTestTarget::assertTarget('existing-schema', SafeTestTarget::classForFile('tests/Feature/'.$name.'.php'));
        }
        SafeTestTarget::assertTarget('attribute-rollback', SafeTestTarget::classForFile('tests/Feature/ProductAttributeValidationTest.php'));
        self::assertTrue(true);
    }

    public function test_attribute_suite_is_not_allowed_in_cleaning_mode(): void
    {
        $this->expectException(\RuntimeException::class);
        ExistingSchemaMode::assertTestAllowed(\Tests\Feature\ProductAttributeValidationTest::class);
    }

    #[DataProvider('badEnvironments')]
    public function test_environment_conflicts_fail_before_bootstrap(string $mode, array $env): void
    {
        $this->expectException(\RuntimeException::class);
        SafeTestTarget::assertEnvironment($mode, $env);
    }

    public static function badEnvironments(): array
    {
        return [
            ['attribute-rollback', ['WV_EXISTING_SCHEMA' => '1']],
            ['existing-schema', ['WV_EXISTING_SCHEMA' => '0']],
            ['existing-schema', ['DB_DATABASE' => 'webv3']],
            ['existing-schema', ['DB_DATABASE' => 'webv3_attributes_test']],
            ['existing-schema', ['DB_USERNAME' => 'root']],
            ['existing-schema', ['DB_HOST' => 'localhost']],
            ['existing-schema', ['DB_URL' => 'mysql://invalid']],
            ['existing-schema', ['WV_ALLOW_ATTRIBUTE_SEEDER_TEST' => '1']],
            ['attribute-rollback', ['WV_TEST_RUN_MODE' => 'existing-schema']],
            ['', []],
        ];
    }

    public function test_safe_metadata_contract_and_no_automatic_defaults(): void
    {
        $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'webv3_testing',
            'DB_USERNAME' => 'webvitrina_test', 'DB_HOST' => '127.127.126.9', 'DB_PORT' => '3306'];
        SafeTestTarget::assertEnvironment('existing-schema', $env);
        self::assertSame('webv3_testing', SafeTestTarget::database($env)['connections']['mysql']['database']);
        $this->expectException(\RuntimeException::class);
        SafeTestTarget::database([]);
    }

    public function test_traversal_and_directory_targets_are_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        SafeTestTarget::classForFile('tests/Feature/../Unit/Test.php');
    }

    public function test_concurrency_class_cannot_be_run_in_rollback_mode(): void
    {
        $this->expectException(\RuntimeException::class);
        SafeTestTarget::assertTarget('attribute-rollback', \Tests\Feature\ProductMutationConcurrencyTest::class);
    }

    public function test_cli_default_only_describes_target_without_connecting(): void
    {
        $process = new \Symfony\Component\Process\Process([PHP_BINARY, 'scripts/run-safe-feature-tests.php',
            '--mode=attribute-rollback', '--file=tests/Feature/ProductAttributeValidationTest.php'], dirname(__DIR__, 2), [
                // This child tests the local runner contract, not the parent Docker target.
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql',
                'DB_DATABASE' => 'webv3_testing', 'DB_USERNAME' => 'webvitrina_test',
                'DB_HOST' => '127.127.126.9', 'DB_PORT' => '3306',
                'WV_DISPOSABLE_FULL_TEST' => false,
                'WV_EXISTING_SCHEMA' => false, 'WV_TEST_RUN_MODE' => false, 'WV_ALLOW_ATTRIBUTE_SEEDER_TEST' => false,
                'DB_URL' => false, 'DB_SOCKET' => false,
                // Would fail authentication if the descriptive path tried to connect.
                'DB_PASSWORD' => 'nonexistent-test-password',
            ]);
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($result['executed']);
        self::assertSame('transaction rollback; no global cleanup', $result['cleanup']);
    }

    public function test_cleaning_run_without_ack_stops_before_credentials_or_connection(): void
    {
        $process = new \Symfony\Component\Process\Process([PHP_BINARY, 'scripts/run-safe-feature-tests.php',
            '--mode=existing-schema', '--file=tests/Feature/ProductMutationConcurrencyTest.php', '--run'], dirname(__DIR__, 2), [
                // Reach the missing-ack check with a valid, process-local environment.
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql',
                'DB_DATABASE' => 'webv3_testing', 'DB_USERNAME' => 'webvitrina_test',
                'DB_HOST' => '127.127.126.9', 'DB_PORT' => '3306',
                'WV_DISPOSABLE_FULL_TEST' => false,
                'WV_EXISTING_SCHEMA' => false, 'WV_TEST_RUN_MODE' => false, 'WV_ALLOW_ATTRIBUTE_SEEDER_TEST' => false,
                'DB_URL' => false, 'DB_SOCKET' => false, 'DB_PASSWORD' => 'nonexistent-test-password',
            ]);
        $process->run();
        self::assertSame(1, $process->getExitCode());
        self::assertStringContainsString('Explicit --ack-cleanup=webv3_testing required', $process->getErrorOutput());
    }

    public function test_worker_requires_inherited_scope_before_application_or_pdo(): void
    {
        $database = SafeTestTarget::database(['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'webv3_testing', 'DB_USERNAME' => 'webvitrina_test', 'DB_HOST' => '127.127.126.9', 'DB_PORT' => '3306']);
        $code = 'require "vendor/autoload.php"; Tests\\Support\\TestWorkerBootstrap::boot('.var_export($database, true).');';
        $process = new \Symfony\Component\Process\Process([PHP_BINARY, '-r', $code], dirname(__DIR__, 2), [
            'WV_EXISTING_SCHEMA' => '1', 'LARAVEL_STORAGE_PATH' => false,
        ]);
        $process->run();
        self::assertFalse($process->isSuccessful());
        self::assertStringContainsString('Worker requires an explicit parent isolation directory', $process->getErrorOutput().$process->getOutput());
    }
}
