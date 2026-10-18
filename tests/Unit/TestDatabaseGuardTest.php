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
                    'username' => 'webvitrina_test',
                    'url' => null,
                ],
            ],
        ];

        TestCase::assertSafeTestDatabaseConfiguration('testing', $safe);

        $unsafe = [];
        $unsafe[] = ['production', $safe];

        foreach (['database' => 'webv3', 'username' => 'root', 'host' => 'db.example.com', 'port' => '3307', 'url' => 'mysql://example'] as $key => $value) {
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

        foreach ([null, '', 'WEBVITRINA_TEST', 'webvitrina_test '] as $username) {
            $config = $safe;
            $config['connections']['mysql']['username'] = $username;
            $unsafe[] = ['testing', $config];
        }
        $config = $safe;
        unset($config['connections']['mysql']['username']);
        $unsafe[] = ['testing', $config];

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
