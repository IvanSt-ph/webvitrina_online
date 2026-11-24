<?php

namespace Tests\Unit;

use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\DisposableTestEnvironment;

/** No application bootstrap, processes or real PDO connections. */
final class DisposableTestEnvironmentTest extends TestCase
{
    public static function initMounts(): array
    {
        return \Tests\Support\DockerInitMountCases::cases();
    }

    #[DataProvider('initMounts')]
    public function test_init_mount_requires_runtime_identity(string $mount, string $type, array $options, array $evidence, bool $allowed): void
    {
        if (! $allowed) {
            $this->expectException(RuntimeException::class);
        }
        DisposableTestEnvironment::assertDockerInitMount($mount, $type, $options, $evidence);
        $this->addToAssertionCount(1);
    }
    public function test_image_root_and_runtime_mounts_are_accepted(): void
    {
        DisposableTestEnvironment::assertMounts([
            '1 0 0:1 / / rw - overlay overlay rw',
            '2 1 0:2 / /proc rw - proc proc rw',
            '3 1 0:3 / /run/mysqld rw - ext4 /dev/test rw',
            '4 1 0:4 / /run/secrets/full_test_db_password ro - ext4 /dev/test ro',
        ]);
        $this->addToAssertionCount(1);
    }

    public static function unsafeMounts(): array
    {
        return [
            'workspace' => [['1 0 0:1 / / rw - overlay overlay rw', '2 1 0:2 / /app rw - ext4 host rw']],
            'nested workspace' => [['1 0 0:1 / / rw - overlay overlay rw', '2 1 0:2 / /app/tests rw - ext4 host rw']],
            'other host path' => [['1 0 0:1 / / rw - overlay overlay rw', '2 1 0:2 / /workspace rw - ext4 host rw']],
            'host tmp' => [['1 0 0:1 / / rw - overlay overlay rw', '2 1 0:2 / /tmp rw - ext4 host rw']],
            'host under dev' => [['1 0 0:1 / / rw - overlay overlay rw', '2 1 0:2 / /dev/workspace rw - ext4 host rw']],
            'docker socket' => [['1 0 0:1 / / rw - overlay overlay rw', '2 1 0:2 / /run/docker.sock rw - ext4 host rw']],
            'host root' => [['1 0 0:1 / / rw - ext4 host rw']],
            'missing evidence' => [[]],
        ];
    }

    #[DataProvider('unsafeMounts')]
    public function test_host_mounts_and_missing_evidence_are_rejected(array $mounts): void
    {
        $this->expectException(RuntimeException::class);
        DisposableTestEnvironment::assertMounts($mounts);
    }

    public static function identities(): array
    {
        return [
            ['webv3_full_testing', 'webvitrina_full_test@%', true],
            ['webv3', 'webvitrina_full_test@%', false],
            ['webv3_testing', 'webvitrina_full_test@%', false],
            ['webv3_full_testing', 'root@localhost', false],
            ['webv3_full_testing', 'webvitrina_test@localhost', false],
        ];
    }

    #[DataProvider('identities')]
    public function test_live_identity_requires_both_database_and_privilege_account(string $database, string $user, bool $allowed): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('fetch')->with(PDO::FETCH_NUM)->willReturn([$database, $user]);
        $statement->expects($this->once())->method('closeCursor');
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->with(PDO::ATTR_DRIVER_NAME)->willReturn('mysql');
        $pdo->expects($this->once())->method('query')->with('SELECT DATABASE(), CURRENT_USER()')->willReturn($statement);
        if (! $allowed) {
            $this->expectException(RuntimeException::class);
        }
        DisposableTestEnvironment::assertPdo($pdo);
    }
}
