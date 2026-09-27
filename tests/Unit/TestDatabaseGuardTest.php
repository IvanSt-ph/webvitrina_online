<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Tests\TestCase;

class TestDatabaseGuardTest extends PHPUnitTestCase
{
    public function test_only_explicit_local_test_database_is_allowed(): void
    {
        $safe = [
            'default' => 'mysql',
            'connections' => [
                'mysql' => [
                    'driver' => 'mysql',
                    'host' => '127.127.126.9',
                    'port' => '3306',
                    'database' => 'webv3_testing',
                    'url' => null,
                ],
            ],
        ];

        TestCase::assertSafeTestDatabaseConfiguration('testing', $safe);

        $unsafe = [];
        $unsafe[] = ['production', $safe];

        foreach (['database' => 'webv3', 'host' => 'db.example.com', 'port' => '3307', 'url' => 'mysql://example'] as $key => $value) {
            $config = $safe;
            $config['connections']['mysql'][$key] = $value;
            $unsafe[] = ['testing', $config];
        }

        foreach (['default' => 'sqlite', 'read' => ['host' => 'db.example.com'], 'write' => ['host' => 'db.example.com']] as $key => $value) {
            $config = $safe;

            if ($key === 'default') {
                $config[$key] = $value;
            } else {
                $config['connections']['mysql'][$key] = $value;
            }

            $unsafe[] = ['testing', $config];
        }

        foreach ($unsafe as [$environment, $config]) {
            try {
                TestCase::assertSafeTestDatabaseConfiguration($environment, $config);
                $this->fail('Unsafe test database configuration was accepted.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('explicitly approved', $exception->getMessage());
            }
        }
    }
}
