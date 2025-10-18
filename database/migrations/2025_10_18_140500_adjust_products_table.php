<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'` WHERE `Key_name` = ?', [$index]);
        return !empty($rows);
    }

    public function up(): void
    {
        // 1️⃣ Удаляем дублирующую колонку country_id (если есть)
        if (Schema::hasColumn('products', 'country_id')) {
            Schema::table('products', function (Blueprint $table) {
                try {
                    $table->dropForeign(['country_id']);
                } catch (\Throwable $e) {
                    // foreign key уже удалён — продолжаем
                }

                $table->dropColumn('country_id');
            });
        }

        // 2️⃣ Добавляем статус (если нет)
        if (!Schema::hasColumn('products', 'status')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('status')->default('draft')->after('longitude');
            });
        }

        // 3️⃣ Добавляем deleted_at (если нет)
        if (!Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 4️⃣ Индексы (создаём, если их нет)
        if (!$this->indexExists('products', 'idx_products_category_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('category_id', 'idx_products_category_id');
            });
        }

        if (!$this->indexExists('products', 'idx_products_user_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('user_id', 'idx_products_user_id');
            });
        }

        if (!$this->indexExists('products', 'idx_products_city_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('city_id', 'idx_products_city_id');
            });
        }
    }

    public function down(): void
    {
        // 🔙 Безопасный откат
        if (Schema::hasColumn('products', 'status')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        foreach (['idx_products_category_id', 'idx_products_user_id', 'idx_products_city_id'] as $idx) {
            try {
                Schema::table('products', function (Blueprint $table) use ($idx) {
                    $table->dropIndex($idx);
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // country_id обратно не добавляем
    }
};
