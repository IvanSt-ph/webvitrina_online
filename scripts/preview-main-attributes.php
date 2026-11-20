<?php

// Explicit standalone entry point. Never boot Laravel or infer a database from defaults.
require dirname(__DIR__).'/vendor/autoload.php';

use App\Services\AttributeImport\MainAttributeTransfer;
use App\Services\AttributeImport\MainAttributeCache;

$options = getopt('', ['database:', 'preview', 'apply:', 'confirm:', 'backup:']);
if (($options['database'] ?? null) !== 'webv3'
    || (array_key_exists('preview', $options) === array_key_exists('apply', $options))) {
    fwrite(STDERR, "Specify --database=webv3 and exactly one of --preview or --apply=FINGERPRINT.\n");
    exit(1);
}
if (isset($options['apply']) && (($options['confirm'] ?? '') !== 'APPLY-REVIEWED-ATTRIBUTES-TO-webv3-OFFLINE' || ! isset($options['backup']))) {
    fwrite(STDERR, "Apply requires explicit offline confirmation and verified backup path.\n");
    exit(1);
}
$root = dirname(__DIR__);
try {
    // Read file into an array; do not modify process environment or .env.
    $env = Dotenv\Dotenv::createArrayBacked($root)->load();
    if (($env['DB_CONNECTION'] ?? '') !== 'mysql' || ($env['DB_DATABASE'] ?? '') !== 'webv3'
        || ! empty($env['DB_URL']) || getenv('DB_URL') || ! empty($env['DB_SOCKET'])) {
        throw new RuntimeException('Unexpected database configuration.');
    }
    $host = $env['DB_HOST'] ?? '';
    $port = $env['DB_PORT'] ?? '3306';
    if (! in_array($host, ['localhost', '127.0.0.1', '127.127.126.9'], true) || ! ctype_digit((string) $port)) {
        throw new RuntimeException('Unreviewed host/port.');
    }
    $pdo = new PDO('mysql:host='.$host.';port='.$port.';dbname=webv3;charset=utf8mb4', $env['DB_USERNAME'], $env['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    $cacheConfiguration = MainAttributeCache::configuration($env, is_file($root.'/bootstrap/cache/config.php'));
    $runner = new MainAttributeTransfer($pdo, $root.'/docs/templates/category-attributes-colors-2026-11-13.xlsx', $root.'/storage/app/private/main-attribute-transfer', $cacheConfiguration);
    $result = isset($options['apply'])
        ? $runner->apply($options['apply'], $options['confirm'], $options['backup']) : $runner->preview();
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
    if (! empty($result['warning']) || (($result['cache']['success'] ?? true) === false)) { exit(2); }
} catch (PDOException) {
    // SQL/connection exceptions may contain data and credentials.
    fwrite(STDERR, "Database failure. If commit was attempted, reconcile journal and database before retrying.\n");
    exit(1);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
}
