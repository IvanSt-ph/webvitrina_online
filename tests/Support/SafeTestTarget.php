<?php

namespace Tests\Support;

use RuntimeException;

/** Pure launch contract; no application, files, environment writes or PDO. */
final class SafeTestTarget
{
    public static function assertTarget(string $mode, string $class): void
    {
        if ($mode === 'existing-schema') {
            ExistingSchemaMode::assertTestAllowed($class);
        } elseif ($mode === 'attribute-rollback') {
            if (! in_array($class, [\Tests\Feature\ProductAttributeValidationTest::class,
                \Tests\Feature\CategoryAttributeImportTest::class], true)) {
                throw new RuntimeException('Unreviewed attribute rollback target.');
            }
        } else {
            throw new RuntimeException('Explicit mode required: existing-schema or attribute-rollback.');
        }
    }

    public static function classForFile(string $file): string
    {
        $file = str_replace('\\', '/', $file);
        if (! preg_match('~\Atests/Feature/([A-Za-z0-9_/]+Test)\.php\z~D', $file)) {
            throw new RuntimeException('Specify one repository-relative Feature test file, not a directory or option.');
        }
        return 'Tests\\'.str_replace('/', '\\', substr($file, 6, -4));
    }

    public static function assertEnvironment(string $mode, array $environment): void
    {
        if (! in_array($mode, ['existing-schema', 'attribute-rollback'], true)) { throw new RuntimeException('Invalid mode.'); }
        foreach (['DB_URL', 'DB_SOCKET', 'WV_ALLOW_ATTRIBUTE_SEEDER_TEST'] as $key) {
            if (($environment[$key] ?? '') !== '') { throw new RuntimeException('Forbidden environment override: '.$key); }
        }
        $existing = $environment['WV_EXISTING_SCHEMA'] ?? '';
        if (($mode === 'attribute-rollback' && $existing !== '') || ($mode === 'existing-schema' && ! in_array($existing, ['', '1'], true))) {
            throw new RuntimeException('Conflicting WV_EXISTING_SCHEMA; use a fresh process.');
        }
        $selected = $environment['WV_TEST_RUN_MODE'] ?? '';
        if ($selected !== '' && $selected !== $mode) { throw new RuntimeException('Conflicting test mode.'); }
        foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'webv3_testing',
            'DB_USERNAME' => 'webvitrina_test', 'DB_HOST' => '127.127.126.9', 'DB_PORT' => '3306'] as $key => $expected) {
            if (isset($environment[$key]) && $environment[$key] !== '' && (string) $environment[$key] !== $expected) {
                throw new RuntimeException('Unsafe environment override: '.$key);
            }
        }
    }

    public static function database(array $environment): array
    {
        $database = ['default' => $environment['DB_CONNECTION'] ?? '', 'connections' => ['mysql' => [
            'driver' => 'mysql', 'host' => $environment['DB_HOST'] ?? '', 'port' => $environment['DB_PORT'] ?? '',
            'database' => $environment['DB_DATABASE'] ?? '', 'username' => $environment['DB_USERNAME'] ?? '',
            'password' => $environment['DB_PASSWORD'] ?? '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '', 'url' => $environment['DB_URL'] ?? null, 'unix_socket' => $environment['DB_SOCKET'] ?? '',
        ]]];
        TestDatabaseSafety::assertConfiguration($environment['APP_ENV'] ?? '', $database);
        return $database;
    }
}
