<?php

// No PowerShell profile, no global migrated override, no implicit test suite.
require dirname(__DIR__).'/vendor/autoload.php';

use Tests\Support\{ExistingSchemaMode, SafeTestTarget, TestDatabaseSafety};

try {
    $options = [];
    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--run') { $name = 'run'; $value = true; }
        elseif (preg_match('/^--(mode|file|filter|ack-cleanup)=(.+)$/D', $argument, $match)) { $name = $match[1]; $value = $match[2]; }
        else { throw new RuntimeException('Unknown argument. Use --mode=... --file=tests/Feature/...Test.php [--filter=...] [--run].'); }
        if (isset($options[$name])) { throw new RuntimeException('Duplicate option: '.$name); }
        $options[$name] = $value;
    }
    $mode = $options['mode'] ?? '';
    $file = $options['file'] ?? '';
    $class = SafeTestTarget::classForFile($file);
    SafeTestTarget::assertTarget($mode, $class);
    $root = dirname(__DIR__);
    if (! is_file($root.'/'.$file) || realpath($root.'/'.$file) !== realpath((new ReflectionClass($class))->getFileName())) {
        throw new RuntimeException('Target does not match the reviewed class.');
    }
    SafeTestTarget::assertEnvironment($mode, getenv());
    if (($options['run'] ?? false) && $mode === 'existing-schema' && ($options['ack-cleanup'] ?? '') !== 'webv3_testing') {
        throw new RuntimeException('Existing-schema truncates test tables. Explicit --ack-cleanup=webv3_testing required.');
    }
    if ($mode === 'attribute-rollback' && isset($options['ack-cleanup'])) { throw new RuntimeException('Cleanup acknowledgement is invalid for rollback mode.'); }
    if (! isset($options['run'])) {
        echo json_encode(['mode' => $mode, 'file' => $file, 'database' => 'webv3_testing', 'user' => 'webvitrina_test',
            'cleanup' => $mode === 'existing-schema' ? 'TRUNCATE fixtures; preserves migrations' : 'transaction rollback; no global cleanup',
            'executed' => false], JSON_PRETTY_PRINT).PHP_EOL;
        exit(0);
    }
    // Only explicit --run reads credentials and may connect. Never fall back to .env.
    if (! is_file($root.'/.env.testing')) { throw new RuntimeException('Existing .env.testing required; no fallback.'); }
    $env = Dotenv\Dotenv::createArrayBacked($root, '.env.testing')->load();
    SafeTestTarget::assertEnvironment($mode, $env);
    $database = SafeTestTarget::database($env);
    if (getenv('DB_PASSWORD') !== false && getenv('DB_PASSWORD') !== $database['connections']['mysql']['password']) {
        throw new RuntimeException('Conflicting test credential override.');
    }
    ExistingSchemaMode::lockRun();
    // Preflight is a direct connection with safe metadata, not a Laravel bootstrap.
    $capsule = new Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($database['connections']['mysql']);
    $connection = $capsule->getConnection();
    try {
        TestDatabaseSafety::assertMetadata('testing', $database, $connection);
        $connection->getPdo();
        TestDatabaseSafety::assertConnected('testing', $database, $connection);
    } finally { $connection->disconnect(); }
    echo "Verified write-PDO: webv3_testing / webvitrina_test@localhost.\n";
    // Changes are process-local; PHPUnit and its children receive the same explicit target.
    $selected = array_intersect_key($env, array_flip(['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD']));
    $selected += ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'WV_TEST_RUN_MODE' => $mode,
        'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array'];
    foreach ($selected as $key => $value) {
        if (! putenv($key.'='.$value)) { throw new RuntimeException('Cannot set test environment: '.$key); }
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    foreach (['WV_EXISTING_SCHEMA', 'WV_ALLOW_ATTRIBUTE_SEEDER_TEST'] as $key) {
        putenv($key); unset($_ENV[$key], $_SERVER[$key]);
    }
    if ($mode === 'existing-schema') {
        putenv('WV_EXISTING_SCHEMA=1'); $_ENV['WV_EXISTING_SCHEMA'] = $_SERVER['WV_EXISTING_SCHEMA'] = '1';
    }
    chdir($root);
    $args = ['phpunit', '--configuration', $root.'/phpunit.xml', '--do-not-cache-result', '--exclude-group', 'attribute-seeder'];
    if (isset($options['filter'])) { $args = [...$args, '--filter', $options['filter']]; }
    exit((new PHPUnit\TextUI\Application)->run([...$args, $file]));
} catch (PDOException) {
    fwrite(STDERR, "Test database preflight failed; connection details suppressed. No tests started.\n");
    exit(1);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
}
