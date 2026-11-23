<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\TestDatabaseSafety;
use Tests\TestCase;

class ShopPhonePublicMigrationTest extends TestCase
{
    public function test_migration_defaults_existing_and_new_rows_to_false_without_touching_history(): void
    {
        $connection = DB::connection();
        $connection->getPdo();
        TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $connection);
        $schema = $connection->getSchemaBuilder();
        $table = 'test_shop_phone_public_migration';
        $this->assertFalse($schema->hasTable($table));
        // Exercise the real migration on a dedicated probe table, without DDL on
        // shared shops or implicit commits in RefreshDatabase test transactions.
        $schema->create($table, function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->string('phone')->nullable();
            $blueprint->timestamp('phone_verified_at')->nullable();
        });
        try {
            $history = ['phone' => '+37377111222', 'phone_verified_at' => '2026-01-01 12:00:00'];
            $connection->table($table)->insert($history);
            Schema::shouldReceive('table')->with('shops', \Mockery::type('callable'))
                ->once()->andReturnUsing(fn ($name, $callback) => $schema->table($table, $callback));
            $migration = require database_path('migrations/2026_11_22_000001_add_phone_public_to_shops_table.php');
            $migration->up();
            $connection->table($table)->insert(['phone' => null]);
            $rows = $connection->table($table)->orderBy('id')->get();
            $this->assertSame(0, (int) $rows[0]->phone_public);
            $this->assertSame(0, (int) $rows[1]->phone_public);
            $this->assertSame($history['phone'], $rows[0]->phone);
            $this->assertSame($history['phone_verified_at'], $rows[0]->phone_verified_at);
            $column = $connection->selectOne('SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, 'phone_public']);
            $this->assertSame('NO', $column->IS_NULLABLE);
        } finally {
            $schema->drop($table);
            Schema::clearResolvedInstance('db.schema');
            app()->forgetInstance('db.schema');
        }
    }
}
