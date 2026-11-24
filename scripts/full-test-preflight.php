<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

function requiredEnvironment(string $name, string $expected): void
{
    $actual = getenv($name);
    if ($actual !== $expected) {
        throw new RuntimeException(sprintf('Unsafe %s value.', $name));
    }
}

function forbiddenEnvironment(string $name): void
{
    $value = getenv($name);
    if ($value !== false && $value !== '') {
        throw new RuntimeException(sprintf('Forbidden environment override: %s.', $name));
    }
}

try {
    \Tests\Support\DisposableTestEnvironment::assertIsolation();
    if (! is_file('/.dockerenv') || ! is_dir('/app') || getcwd() !== '/app') {
        throw new RuntimeException('Disposable preflight must run inside the expected application container.');
    }
    if (file_exists('/var/run/docker.sock')) {
        throw new RuntimeException('Docker socket must not be available to the test container.');
    }
    if (file_exists('/app/.env')) {
        throw new RuntimeException('A project .env file was copied into the disposable image.');
    }
    if (file_exists('/app/public/hot')) {
        throw new RuntimeException('A Vite development server marker is forbidden.');
    }
    $manifest = '/app/public/build/manifest.json';
    if (! is_readable($manifest)
        || ! is_array($assets = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR))
        || $assets === []) {
        throw new RuntimeException('The built Vite manifest is missing or invalid.');
    }
    if (! function_exists('imagewebp') || ! (gd_info()['WebP Support'] ?? false)) {
        throw new RuntimeException('Disposable PHP GD requires WebP encoding support.');
    }
    $image = imagecreatetruecolor(1, 1);
    ob_start();
    try {
        $encoded = imagewebp($image);
        $webp = ob_get_contents();
    } finally {
        ob_end_clean();
        imagedestroy($image);
    }
    if (! $encoded || substr($webp, 0, 4) !== 'RIFF' || substr($webp, 8, 4) !== 'WEBP') {
        throw new RuntimeException('Disposable PHP GD failed its WebP encoding probe.');
    }

    requiredEnvironment('WV_DISPOSABLE_FULL_TEST', '1');
    requiredEnvironment('APP_ENV', 'testing');
    requiredEnvironment('DB_CONNECTION', 'mysql');
    requiredEnvironment('DB_HOST', 'localhost');
    requiredEnvironment('DB_PORT', '3306');
    requiredEnvironment('DB_SOCKET', '/var/run/mysqld/mysqld.sock');
    requiredEnvironment('DB_DATABASE', 'webv3_full_testing');
    requiredEnvironment('DB_USERNAME', 'webvitrina_full_test');

    foreach (['DB_URL', 'MYSQL_ATTR_SSL_CA', 'WV_EXISTING_SCHEMA', 'WV_ALLOW_ATTRIBUTE_SEEDER_TEST'] as $name) {
        forbiddenEnvironment($name);
    }
    if (strcasecmp((string) getenv('DB_DATABASE'), 'webv3') === 0) {
        throw new RuntimeException('The production database is forbidden.');
    }

    $interfaces = array_map('basename', glob('/sys/class/net/*') ?: []);
    sort($interfaces);
    if ($interfaces !== ['lo']) {
        throw new RuntimeException('The full-test container must have no network interface except loopback.');
    }
    // filetype() returns false for a missing path; suppress its filesystem warning.
    if (@filetype('/var/run/mysqld/mysqld.sock') !== 'socket') {
        throw new RuntimeException('The disposable MySQL Unix socket is unavailable.');
    }

    $password = getenv('DB_PASSWORD');
    if ($password === false || $password === '') {
        throw new RuntimeException('The disposable database password is missing.');
    }

    $pdo = new PDO(
        'mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=webv3_full_testing;charset=utf8mb4',
        'webvitrina_full_test',
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_PERSISTENT => false],
    );
    $actual = $pdo->query('SELECT DATABASE(), CURRENT_USER()')->fetch(PDO::FETCH_NUM);
    if ($actual !== ['webv3_full_testing', 'webvitrina_full_test@%']) {
        throw new RuntimeException('Actual write-PDO target does not match the disposable database account.');
    }
    $pdo = null;

    fwrite(STDOUT, "Verified disposable write-PDO: webv3_full_testing / webvitrina_full_test@%.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'FULL-TEST PREFLIGHT FAILED: '.$exception->getMessage()."\n");
    exit(70);
}
