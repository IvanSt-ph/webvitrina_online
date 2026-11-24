<?php

namespace Tests\Support;

use PDO;
use RuntimeException;

/** Evidence checked in every PHP process; an environment flag alone is not proof. */
final class DisposableTestEnvironment
{
    public const DATABASE = 'webv3_full_testing';
    public const USER = 'webvitrina_full_test';
    public const SOCKET = '/var/run/mysqld/mysqld.sock';

    public static function assertIsolation(): void
    {
        foreach (['WV_DISPOSABLE_FULL_TEST' => '1', 'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => 'localhost', 'DB_PORT' => '3306',
            'DB_DATABASE' => self::DATABASE, 'DB_USERNAME' => self::USER,
            'DB_SOCKET' => self::SOCKET] as $key => $value) {
            if (getenv($key) !== $value) {
                throw new RuntimeException('Unsafe disposable environment: '.$key);
            }
        }
        foreach (['DB_URL', 'MYSQL_ATTR_SSL_CA', 'WV_EXISTING_SCHEMA',
            'WV_TEST_RUN_MODE', 'WV_ALLOW_ATTRIBUTE_SEEDER_TEST', 'DOCKER_HOST'] as $key) {
            if (! in_array(getenv($key), [false, ''], true)) {
                throw new RuntimeException('Forbidden disposable override: '.$key);
            }
        }
        if (! is_file('/.dockerenv') || realpath(dirname(__DIR__, 2)) !== '/app'
            || getcwd() !== '/app' || file_exists('/var/run/docker.sock')
            || file_exists('/run/docker.sock') || @filetype(self::SOCKET) !== 'socket') {
            throw new RuntimeException('Expected isolated Docker filesystem and MySQL socket.');
        }
        foreach (['/app/.env', '/app/.env.testing', '/app/bootstrap/cache/config.php'] as $path) {
            if (file_exists($path)) {
                throw new RuntimeException('Environment files and cached configuration are forbidden in Docker tests.');
            }
        }
        $interfaces = array_map('basename', glob('/sys/class/net/*') ?: []);
        sort($interfaces);
        if ($interfaces !== ['lo']) {
            throw new RuntimeException('Disposable tests require loopback-only networking.');
        }
        $mounts = @file('/proc/self/mountinfo', FILE_IGNORE_NEW_LINES);
        if (! $mounts) {
            throw new RuntimeException('Cannot inspect container mounts.');
        }
        self::assertMounts($mounts);
    }

    /** Init mounts additionally require evidence from the running container. */
    public static function assertMounts(array $mounts): void
    {
        $overlayRoot = false;
        $initSeen = false;
        foreach ($mounts as $line) {
            $parts = explode(' - ', $line, 2);
            $fields = explode(' ', $parts[0]);
            $mount = $fields[4] ?? '';
            $type = explode(' ', $parts[1] ?? '')[0];
            if ($mount === '/' && $type === 'overlay') {
                $overlayRoot = true;
                continue;
            }
            if (in_array($mount, ['/sbin/docker-init', '/usr/sbin/docker-init'], true)) {
                if ($initSeen) {
                    throw new RuntimeException('Duplicate Docker init mount is forbidden.');
                }
                $initSeen = true;
                self::assertDockerInitMount($mount, $type, explode(',', $fields[5] ?? ''), [
                    'legacy' => realpath('/sbin/docker-init'),
                    'merged' => realpath('/usr/sbin/docker-init'),
                    'target' => realpath($mount),
                    'pid1' => @realpath('/proc/1/exe'),
                    'comm' => trim((string) @file_get_contents('/proc/1/comm')),
                    'file' => @stat($mount),
                    'process' => @stat('/proc/1/exe'),
                ]);
                continue;
            }
            // Only runtime system mounts, the socket volume and the single app
            // secret are allowed. In particular, no /app or host workspace mount.
            if ((preg_match('~^/(?:proc|sys|dev)(?:/|$)~D', $mount)
                    && in_array($type, ['proc', 'sysfs', 'tmpfs', 'devpts', 'mqueue', 'cgroup', 'cgroup2'], true))
                || ($mount === '/tmp' && $type === 'tmpfs')
                || in_array($mount, ['/etc/hosts', '/etc/hostname', '/etc/resolv.conf',
                    '/run/secrets/full_test_db_password',
                    '/run/mysqld', '/var/run/mysqld'], true)) {
                continue;
            }
            throw new RuntimeException(self::mountRejectionDiagnostic($mount, $type, $mounts));
        }
        if (! $overlayRoot) {
            throw new RuntimeException('Disposable tests require an image-backed overlay root. mount=/; fs=<unavailable>; reason=overlay_root_not_observed');
        }
    }

    /** Pure policy for regression fixtures; assertMounts always gathers its own evidence. */
    public static function assertDockerInitMount(string $mount, string $type, array $options, array $evidence): void
    {
        $canonical = $evidence['target'] ?? false;
        $file = $evidence['file'] ?? false;
        $process = $evidence['process'] ?? false;
        if (! in_array($mount, ['/sbin/docker-init', '/usr/sbin/docker-init'], true)
            || $type !== 'overlay' || ! in_array('ro', $options, true) || in_array('rw', $options, true)
            || ! in_array($canonical, ['/sbin/docker-init', '/usr/sbin/docker-init'], true)
            || ($evidence['legacy'] ?? false) !== $canonical
            || ($evidence['merged'] ?? false) !== $canonical
            || ($evidence['pid1'] ?? false) !== $canonical
            || ($evidence['comm'] ?? '') !== 'docker-init'
            || ! is_array($file) || ! is_array($process)
            || ! isset($file['dev'], $file['ino'], $file['mode'], $file['uid'], $process['dev'], $process['ino'])
            || $file['dev'] !== $process['dev'] || $file['ino'] !== $process['ino']
            || $file['uid'] !== 0 || ($file['mode'] & 0170000) !== 0100000
            || ($file['mode'] & 0022) !== 0 || ($file['mode'] & 0111) === 0) {
            // No paths, process details, mount source or secret values in failures.
            throw new RuntimeException('Docker init mount rejected: expected read-only overlay, canonical aliases and root-owned PID 1 executable identity.');
        }
    }

    /** Diagnostics only: never print mount roots, sources, options or raw records. */
    private static function mountRejectionDiagnostic(string $mount, string $type, array $mounts): string
    {
        // Decode only for diagnostics. Keep the admission policy unchanged.
        // strtr performs one pass: a literal escaped backslash is not decoded twice.
        $decoded = self::decodeMountPoint($mount);
        $class = match (true) {
            $decoded === '/' => 'root',
            $decoded === '/app' || str_starts_with($decoded, '/app/') => 'application_path',
            // A name-based diagnostic category, NOT evidence of Docker ownership.
            in_array($decoded, ['/.dockerenv', '/run/.containerenv', '/dev/init',
                '/sbin/docker-init', '/usr/sbin/docker-init', '/usr/bin/docker-init'], true) => 'docker_internal',
            (bool) preg_match('~^/(?:proc|sys|dev|etc|run|var/run|tmp)(?:/|$)~D', $decoded) => 'system_path',
            default => 'unknown',
        };
        $samePointRecords = 0;
        $samePointOverlayRecords = 0;
        $rootOverlayRecords = 0;
        foreach ($mounts as $line) {
            $parts = explode(' - ', $line, 2);
            $fields = explode(' ', $parts[0]);
            $recordPoint = self::decodeMountPoint($fields[4] ?? '');
            $recordType = explode(' ', $parts[1] ?? '')[0];
            if ($recordPoint === $decoded) {
                ++$samePointRecords;
                if ($recordType === 'overlay') {
                    ++$samePointOverlayRecords;
                }
            }
            if ($recordPoint === '/' && $recordType === 'overlay') {
                ++$rootOverlayRecords;
            }
        }
        $reason = match (true) {
            $mount === '' || $type === '' => 'incomplete_mount_record',
            $mount === '/' => 'root_filesystem_not_overlay',
            in_array($mount, ['/run/docker.sock', '/var/run/docker.sock'], true) => 'docker_socket_mount_forbidden',
            $mount === '/app' || str_starts_with($mount, '/app/') => 'project_tree_mount_forbidden',
            $mount === '/tmp' => 'tmp_filesystem_not_tmpfs',
            (bool) preg_match('~^/(?:proc|sys|dev)(?:/|$)~D', $mount) => 'system_filesystem_not_allowlisted',
            default => 'mountpoint_not_allowlisted',
        };

        // Do not echo arbitrary filesystem labels or malformed/control characters.
        $publicTypes = ['overlay', 'overlayfs', 'fuse.overlayfs', 'ext4', 'ext3', 'ext2',
            'xfs', 'btrfs', 'zfs', 'erofs', 'squashfs', 'tmpfs', 'ramfs', 'rootfs',
            'proc', 'sysfs', 'devtmpfs', 'devpts', 'mqueue', 'cgroup', 'cgroup2',
            '9p', 'virtiofs', 'fuse', 'fuse.grpcfuse', 'fuse.osxfs', 'ntfs', 'ntfs3',
            'drvfs', 'nfs', 'nfs4', 'cifs', 'debugfs', 'tracefs', 'securityfs', 'binfmt_misc'];
        $safeType = in_array($type, $publicTypes, true)
            ? $type : ($type === '' ? '<unavailable>' : '<unrecognized-redacted>');

        // Exact public runtime name only; never print a resolved arbitrary path.
        // Confirm merged-/usr aliasing diagnostically without admitting the mount.
        $initDiagnostic = '';
        if ($decoded === '/usr/sbin/docker-init') {
            $aliasMatches = realpath('/sbin/docker-init') === '/usr/sbin/docker-init'
                && is_file('/usr/sbin/docker-init');
            $initDiagnostic = '; service_path=/usr/sbin/docker-init; init_alias='
                .($aliasMatches ? 'matches' : 'unverified');
        }

        return sprintf('Unexpected mount in disposable test container. class=%s; point_sha256=%s; fs=%s; reason=%s; escaped=%s; residual_backslash=%s; same_point_records=%d; same_point_overlay_records=%d; root_overlay_records=%d',
            $class, substr(hash('sha256', $decoded), 0, 12), $safeType, $reason,
            $decoded !== $mount ? 'yes' : 'no', str_contains($decoded, '\\') ? 'yes' : 'no',
            $samePointRecords, $samePointOverlayRecords, $rootOverlayRecords).$initDiagnostic;
    }

    private static function decodeMountPoint(string $point): string
    {
        // Kernel seq_path_root escapes space, tab, newline and backslash.
        // Do not use stripcslashes: it accepts escapes outside the mountinfo format.
        return strtr($point, ['\\040' => ' ', '\\011' => "\t", '\\012' => "\n", '\\134' => '\\']);
    }

    public static function assertConfiguration(string $environment, array $database): void
    {
        self::assertIsolation();
        $config = $database['connections']['mysql'] ?? [];
        if ($environment !== 'testing' || ($database['default'] ?? null) !== 'mysql'
            || ($config['driver'] ?? null) !== 'mysql'
            || ($config['database'] ?? null) !== self::DATABASE
            || ($config['username'] ?? null) !== self::USER
            || ($config['host'] ?? null) !== 'localhost'
            || (string) ($config['port'] ?? '') !== '3306'
            || ($config['unix_socket'] ?? null) !== self::SOCKET
            || ! empty($config['url']) || ! empty($config['read']) || ! empty($config['write'])
            || ! empty($config['prefix']) || ! empty($config['options'])
            || ! is_string($config['password'] ?? null)
            || $config['password'] === '' || $config['password'] !== getenv('DB_PASSWORD')) {
            throw new RuntimeException('Connection is not the approved disposable MySQL target.');
        }
    }

    public static function assertPdo(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new RuntimeException('Disposable tests require MySQL PDO.');
        }
        $statement = $pdo->query('SELECT DATABASE(), CURRENT_USER()');
        if ($statement === false) {
            throw new RuntimeException('Cannot verify disposable PDO.');
        }
        try {
            if ($statement->fetch(PDO::FETCH_NUM) !== [self::DATABASE, self::USER.'@%']) {
                throw new RuntimeException('Actual PDO is not the approved disposable database/account.');
            }
        } finally {
            $statement->closeCursor();
        }
    }
}
