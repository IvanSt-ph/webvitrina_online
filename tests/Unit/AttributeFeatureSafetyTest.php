<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\AttributeFeatureTestCase;
use Tests\Support\TestDatabaseSafety;

/** Pure policy tests: no Laravel application, PDO or DB connection. */
final class AttributeFeatureSafetyTest extends TestCase
{
    public function test_unset_cleanup_mode_is_allowed(): void
    {
        AttributeFeatureTestCase::assertNonCleaningMode(false);
        AttributeFeatureTestCase::assertNonCleaningMode('');
        self::assertTrue(true);
    }

    #[DataProvider('cleanupModes')]
    public function test_cleanup_modes_are_blocked_before_bootstrap(string $mode): void
    {
        $this->expectException(\RuntimeException::class);
        AttributeFeatureTestCase::assertNonCleaningMode($mode);
    }

    public static function cleanupModes(): array
    {
        return [['1'], ['0'], ['true']];
    }

    #[DataProvider('databases')]
    public function test_only_separate_testing_database_is_allowed(string $name, bool $allowed): void
    {
        $config = ['default' => 'mysql', 'connections' => ['mysql' => [
            'driver' => 'mysql', 'database' => $name, 'username' => 'webvitrina_test',
            'host' => '127.127.126.9', 'port' => '3306',
        ]]];
        if (! $allowed) {
            $this->expectException(\RuntimeException::class);
        }
        TestDatabaseSafety::assertConfiguration('testing', $config);
        if ($allowed) {
            self::assertTrue(true);
        }
    }

    public static function databases(): array
    {
        return [['webv3_testing', true], ['webv3', false], ['webv3_attributes_test', false]];
    }
}
