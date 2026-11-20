<?php

namespace App\Services\AttributeImport;

use PDO;
use Throwable;

/** Standalone PDO runner: no Laravel bootstrap, observers, cache or session writes. */
final class MainAttributeTransfer
{
    public const WORKBOOK_SHA256 = '0abae98dd17409236a6d5b21555aa4e5856a912ed224531f77d770578105cebb';
    public const BACKUP_SHA256 = 'dd6b5e0eba2aa4ea13fa469dda3fb23681586cb3bbbc674b4119a1c76563b148';
    private const TABLES = ['categories', 'products', 'colors', 'attributes', 'attribute_category', 'attribute_color', 'attribute_values'];
    private array $rows;

    public function __construct(private PDO $pdo, string $workbook, private string $directory, private ?array $cacheConfiguration = null)
    {
        if (hash_file('sha256', $workbook) !== self::WORKBOOK_SHA256) {
            throw new ImportException('Workbook differs from the approved file.');
        }
        $this->rows = (new XlsxReader)->read($workbook);
        if (count($this->rows) !== 2101 || count(array_filter($this->rows, fn ($r) => $r['attribute_name'] === 'Цвет')) !== 78) {
            throw new ImportException('Expected 2101 rows and 78 color rows.');
        }
    }

    private function guard(): array
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new ImportException('MySQL required.');
        }
        $identity = $this->pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account, @@hostname AS host, @@port AS port')->fetch(PDO::FETCH_ASSOC);
        if ($identity['db'] !== 'webv3') { throw new ImportException('Actual database must be webv3.'); }
        $check = $this->pdo->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'webv3'")->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach (self::TABLES as $table) {
            if (($check[$table] ?? null) !== 'InnoDB') { throw new ImportException('InnoDB required for '.$table); }
        }
        if ((int) $this->pdo->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='webv3'")->fetchColumn() !== 0) {
            throw new ImportException('Triggers require a separate side-effect review.');
        }
        return $identity;
    }

    private function snapshot(bool $lock): array
    {
        $snapshot = ['identity' => $this->guard(), 'schema' => []];
        foreach (self::TABLES as $table) {
            $snapshot['schema'][$table] = $this->pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
            $snapshot[$table] = $this->pdo->query('SELECT * FROM `'.$table.'` ORDER BY id'.($lock ? ' FOR UPDATE' : ''))->fetchAll(PDO::FETCH_ASSOC);
        }
        foreach (['archived_at', 'archive_reason'] as $field) {
            if (! str_contains($snapshot['schema']['attribute_values'], '`'.$field.'`')) {
                throw new ImportException('Archive schema missing; no automatic migrations.');
            }
        }
        return $snapshot;
    }

    public function preview(): array
    {
        if ($this->pdo->inTransaction()) { throw new ImportException('Dedicated connection required.'); }
        $this->guard();
        $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->pdo->exec('SET TRANSACTION READ ONLY');
        $this->pdo->beginTransaction();
        try {
            $snapshot = $this->snapshot(false);
            $plan = (new MainAttributePlan)->build($snapshot, $this->rows);
            return ['database' => 'webv3', 'identity' => $snapshot['identity'], 'workbook_sha256' => self::WORKBOOK_SHA256,
                'fingerprint' => $plan['fingerprint'], 'counts' => $plan['counts'], 'operations' => $plan['operations'],
                'cache_keys_after_commit' => MainAttributeCache::keys($snapshot, $plan['expected'], $plan['operations'])];
        } finally { $this->pdo->rollBack(); }
    }

    public function apply(string $fingerprint, string $confirmation, string $backup): array
    {
        if ($confirmation !== 'APPLY-REVIEWED-ATTRIBUTES-TO-webv3-OFFLINE' || ! preg_match('/^[a-f0-9]{64}$/D', $fingerprint)) {
            throw new ImportException('Explicit offline confirmation and preview fingerprint required.');
        }
        if (! is_file($backup) || hash_file('sha256', $backup) !== self::BACKUP_SHA256) {
            throw new ImportException('Verified original backup required.');
        }
        if ($this->pdo->inTransaction()) { throw new ImportException('Dedicated connection required.'); }
        $this->guard();
        // SERIALIZABLE full-range locks protect rows AND insert gaps during application.
        $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        $this->pdo->exec('SET TRANSACTION READ WRITE');
        $this->pdo->beginTransaction();
        $prepared = null;
        try {
            $before = $this->snapshot(true);
            $plan = (new MainAttributePlan)->build($before, $this->rows);
            if (! hash_equals($plan['fingerprint'], $fingerprint)) { throw new ImportException('State changed; preview again.'); }
            if ($plan['operations'] === []) { $this->pdo->rollBack(); return ['changed' => false]; }
            $cacheKeys = MainAttributeCache::keys($before, $plan['expected'], $plan['operations']);
            $prepared = $this->journal(['state' => 'prepared', 'backup_sha256' => self::BACKUP_SHA256,
                'workbook_sha256' => self::WORKBOOK_SHA256, 'before' => $before, 'plan' => $plan, 'cache_keys_after_commit' => $cacheKeys]);
            $ids = [];
            foreach ($plan['operations'] as $op) {
                $this->guardIdentity($before['identity']);
                $data = self::resolve($op['table'], $op['data'], $ids);
                $columns = array_keys($data);
                if ($op['action'] === 'insert') {
                    $sql = 'INSERT INTO `'.$op['table'].'` (`'.implode('`,`', $columns).'`) VALUES ('.implode(',', array_fill(0, count($columns), '?')).')';
                    $this->pdo->prepare($sql)->execute(array_values($data));
                    $ids[$op['id']] = (int) $this->pdo->lastInsertId();
                } else {
                    $sql = 'UPDATE `'.$op['table'].'` SET '.implode(',', array_map(fn ($col) => '`'.$col.'`=?', $columns)).' WHERE id=?';
                    $statement = $this->pdo->prepare($sql);
                    $statement->execute([...array_values($data), $op['id']]);
                    if ($statement->rowCount() !== 1) { throw new ImportException('Update affected unexpected row count.'); }
                }
            }
            $after = $this->snapshot(true);
            self::verify($plan['expected'], $after, $ids);
            if ((new MainAttributePlan)->build($after, $this->rows)['operations'] !== []) {
                throw new ImportException('Post-application workbook check failed.');
            }
            $verified = $this->journal(['state' => 'verified-before-commit', 'prepared' => $prepared, 'id_mapping' => $ids, 'after' => $after]);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            // Prepared/verified files intentionally survive rollback and are NOT commit proof.
            throw $error;
        }
        $cache = MainAttributeCache::invalidate($this->pdo, $cacheKeys, $before['identity'], $this->cacheConfiguration);
        try {
            $receipt = $this->journal(['state' => 'committed', 'prepared' => $prepared, 'verified' => $verified, 'cache' => $cache]);
        } catch (Throwable) {
            return ['changed' => true, 'committed' => true, 'prepared' => $prepared, 'verified' => $verified, 'cache' => $cache,
                'warning' => 'COMMITTED, but receipt failed. Do not retry or restore automatically.'];
        }
        return ['changed' => true, 'committed' => true, 'receipt' => $receipt, 'counts' => $plan['counts'], 'verified_rows' => count($this->rows),
            'cache' => $cache, 'warning' => $cache['success'] ? null : 'COMMITTED; cache cleanup incomplete. Retry failed_keys before reopening the site; do not repeat data transfer.'];
    }

    private function guardIdentity(array $expected): void
    {
        $actual = $this->pdo->query('SELECT DATABASE() AS db, CURRENT_USER() AS account, @@hostname AS host, @@port AS port')->fetch(PDO::FETCH_ASSOC);
        if ($actual !== $expected || $actual['db'] !== 'webv3') { throw new ImportException('Connection identity changed.'); }
    }

    public static function resolve(string $table, array $row, array $ids): array
    {
        foreach (['id', 'attribute_id', 'color_id'] as $field) {
            if (isset($row[$field]) && (int) $row[$field] < 0) {
                $row[$field] = $ids[$row[$field]] ?? throw new ImportException('Unresolved ID reference.');
            }
        }
        if ($table === 'attribute_values' && isset($row['value']) && preg_match('/^@color:(-[1-9][0-9]*)$/D', (string) $row['value'], $match)) {
            $row['value'] = (string) ($ids[(int) $match[1]] ?? throw new ImportException('Unresolved color value.'));
        }
        return $row;
    }

    public static function verify(array $expected, array $actual, array $ids): void
    {
        foreach (self::TABLES as $table) {
            if (count($expected[$table]) !== count($actual[$table])) { throw new ImportException('Unexpected row count: '.$table); }
            $index = array_column($actual[$table], null, 'id');
            foreach ($expected[$table] as $row) {
                $row = self::resolve($table, $row, $ids);
                $found = $index[$row['id']] ?? throw new ImportException('Missing row after transfer.');
                foreach ($row as $field => $value) {
                    $same = $field === 'options' ? json_decode($value ?? 'null', true) === json_decode($found[$field] ?? 'null', true)
                        : ($value === null ? $found[$field] === null : (string) $value === (string) $found[$field]);
                    if (! $same) { throw new ImportException('Unexpected data change: '.$table.' #'.$row['id'].' '.$field); }
                }
            }
        }
    }

    private function journal(array $data): array
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0700, true) && ! is_dir($this->directory)) { throw new ImportException('Journal directory unavailable.'); }
        $path = $this->directory.'/'.bin2hex(random_bytes(16)).'.json';
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $handle = fopen($path, 'x+b');
        if ($handle === false) { throw new ImportException('Cannot create journal.'); }
        try {
            if (fwrite($handle, $json) !== strlen($json) || ! fflush($handle) || ! fsync($handle)) { throw new ImportException('Journal durability failure.'); }
        } finally { fclose($handle); }
        $sha = hash('sha256', $json);
        if (hash_file('sha256', $path) !== $sha) { throw new ImportException('Journal checksum mismatch.'); }
        return ['path' => $path, 'sha256' => $sha];
    }
}
