<?php

namespace Tests\Unit;

use App\Services\AttributeImport\{ImportException, MainAttributeTransfer};
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class MainAttributeTransferSafetyTest extends TestCase
{
    private function runner(PDO $pdo): MainAttributeTransfer
    {
        return new MainAttributeTransfer($pdo, dirname(__DIR__, 2).'/docs/templates/category-attributes-colors-2026-11-13.xlsx', sys_get_temp_dir().'/must-not-be-created-transfer-test');
    }

    public function test_apply_requires_explicit_authorization_before_any_database_call(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::never())->method('query');
        $pdo->expects(self::never())->method('exec');
        $this->expectException(ImportException::class);
        $this->runner($pdo)->apply(str_repeat('a', 64), '', '');
    }

    public function test_wrong_backup_blocks_before_any_database_call(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::never())->method('query');
        $this->expectException(ImportException::class);
        $this->runner($pdo)->apply(str_repeat('a', 64), 'APPLY-REVIEWED-ATTRIBUTES-TO-webv3-OFFLINE', __FILE__);
    }

    public function test_wrong_actual_database_stops_before_transaction(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['db' => 'webv3_attributes_test']);
        $pdo->expects(self::once())->method('query')->willReturn($statement);
        $pdo->expects(self::never())->method('beginTransaction');
        $pdo->expects(self::never())->method('exec');
        $this->expectException(ImportException::class);
        $this->runner($pdo)->preview();
    }

    public function test_preview_failure_rolls_back_without_mutation_or_journal(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->method('query')->willReturnCallback(function (string $sql): PDOStatement {
            $stmt = $this->createMock(PDOStatement::class);
            if (str_starts_with($sql, 'SELECT DATABASE()')) {
                $stmt->method('fetch')->willReturn(['db' => 'webv3', 'account' => 'test', 'host' => 'test', 'port' => 3306]);
            } elseif (str_contains($sql, 'information_schema.TABLES')) {
                $stmt->method('fetchAll')->willReturn(array_fill_keys(['categories', 'products', 'colors', 'attributes', 'attribute_category', 'attribute_color', 'attribute_values'], 'InnoDB'));
            } elseif (str_contains($sql, 'information_schema.TRIGGERS')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } else {
                throw new ImportException('Simulated snapshot failure');
            }
            return $stmt;
        });
        $pdo->expects(self::exactly(2))->method('exec')->willReturnCallback(function (string $sql): int {
            self::assertContains($sql, ['SET TRANSACTION ISOLATION LEVEL REPEATABLE READ', 'SET TRANSACTION READ ONLY']);
            return 0;
        });
        $pdo->expects(self::once())->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::once())->method('rollBack')->willReturn(true);
        $pdo->expects(self::never())->method('commit');
        $pdo->expects(self::never())->method('prepare');
        $this->expectExceptionMessage('Simulated snapshot failure');
        $this->runner($pdo)->preview();
    }

    public function test_apply_sql_failure_rolls_back_and_preserves_prepared_journal(): void
    {
        $root = dirname(__DIR__, 2);
        $backup = $root.'/storage/app/private/backups/webv3_before_colors_20261114_170731_b63f1d.sql';
        if (! is_file($backup)) { self::markTestSkipped('Local approved backup unavailable.'); }
        $workbook = $root.'/docs/templates/category-attributes-colors-2026-11-13.xlsx';
        $rows = (new \App\Services\AttributeImport\XlsxReader)->read($workbook);
        $tables = array_fill_keys(['categories', 'products', 'colors', 'attributes', 'attribute_category', 'attribute_color', 'attribute_values'], []);
        foreach (array_unique(array_column($rows, 'category_slug')) as $slug) {
            $tables['categories'][] = ['id' => count($tables['categories']) + 1, 'slug' => $slug, 'parent_id' => null];
        }
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->method('query')->willReturnCallback(function (string $sql) use ($tables): PDOStatement {
            $stmt = $this->createMock(PDOStatement::class);
            if (str_starts_with($sql, 'SELECT DATABASE()')) {
                $stmt->method('fetch')->willReturn(['db' => 'webv3', 'account' => 'mock', 'host' => 'mock', 'port' => 3306]);
            } elseif (str_contains($sql, 'information_schema.TABLES')) {
                $stmt->method('fetchAll')->willReturn(array_fill_keys(array_keys($tables), 'InnoDB'));
            } elseif (str_contains($sql, 'information_schema.TRIGGERS')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } elseif (str_starts_with($sql, 'SHOW CREATE TABLE')) {
                $stmt->method('fetch')->willReturn(['table', 'CREATE TABLE (`archived_at` timestamp, `archive_reason` text)']);
            } elseif (preg_match('/^SELECT \* FROM `([^`]+)` ORDER BY id(?: FOR UPDATE)?$/D', $sql, $m)) {
                $stmt->method('fetchAll')->willReturn($tables[$m[1]]);
            } else { self::fail('Unexpected query'); }
            return $stmt;
        });
        $transaction = false;
        // Capture by reference: both preview and apply must leave the transaction closed.
        $pdo->method('inTransaction')->willReturnCallback(function () use (&$transaction) { return $transaction; });
        $pdo->expects(self::exactly(2))->method('beginTransaction')->willReturnCallback(function () use (&$transaction) { $transaction = true; return true; });
        $pdo->expects(self::exactly(2))->method('rollBack')->willReturnCallback(function () use (&$transaction) { $transaction = false; return true; });
        $pdo->expects(self::never())->method('commit');
        $pdo->method('exec')->willReturn(0);
        $writes = 0;
        $pdo->method('lastInsertId')->willReturn('1000');
        $pdo->expects(self::exactly(2))->method('prepare')->willReturnCallback(function (string $sql) use (&$writes): PDOStatement {
            self::assertStringStartsWith('INSERT INTO `colors`', $sql);
            if ($writes === 1) { throw new \PDOException('Injected SQL failure'); }
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->expects(self::once())->method('execute')->willReturnCallback(function () use (&$writes) { $writes++; return true; });
            return $stmt;
        });
        $directory = sys_get_temp_dir().'/wv-transfer-unit-'.bin2hex(random_bytes(8));
        try {
            $runner = new MainAttributeTransfer($pdo, $workbook, $directory);
            $preview = $runner->preview();
            self::assertDirectoryDoesNotExist($directory);
            try {
                $runner->apply($preview['fingerprint'], 'APPLY-REVIEWED-ATTRIBUTES-TO-webv3-OFFLINE', $backup);
                self::fail('Expected failure');
            } catch (\PDOException $error) { self::assertSame('Injected SQL failure', $error->getMessage()); }
            self::assertFalse($transaction);
            self::assertSame(1, $writes);
            $files = glob($directory.'/*.json');
            self::assertCount(1, $files);
            self::assertSame('prepared', json_decode(file_get_contents($files[0]), true)['state']);
        } finally {
            foreach (glob($directory.'/*.json') ?: [] as $file) { unlink($file); }
            if (is_dir($directory)) { rmdir($directory); }
        }
    }
}
