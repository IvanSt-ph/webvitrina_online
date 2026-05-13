<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function addIndex(string $table, string $index, string $columns): void
    {
        if (! $this->indexExists($table, $index)) {
            DB::statement("CREATE INDEX {$index} ON {$table} ({$columns})");
        }
    }

    private function dropIndex(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("DROP INDEX {$index} ON {$table}");
        }
    }

    public function up(): void
    {
        $this->addIndex('products', 'idx_products_active_latest', 'status, deleted_at, id');
        $this->addIndex('products', 'idx_products_active_category_latest', 'status, deleted_at, category_id, id');
        $this->addIndex('products', 'idx_products_active_category_price', 'status, deleted_at, category_id, price, id');
        $this->addIndex('products', 'idx_products_active_user_latest', 'status, deleted_at, user_id, id');
        $this->addIndex('products', 'idx_products_active_city_latest', 'status, deleted_at, city_id, id');

        $this->addIndex('reviews', 'idx_reviews_product_status_rating', 'product_id, status, rating');
        $this->addIndex('reviews', 'idx_reviews_product_status_created', 'product_id, status, created_at');

        $this->addIndex('attribute_values', 'idx_attr_values_filter_product', 'attribute_id, value(100), product_id');
        $this->addIndex('cities', 'idx_cities_country_id', 'country_id');
    }

    public function down(): void
    {
        $this->dropIndex('cities', 'idx_cities_country_id');
        $this->dropIndex('attribute_values', 'idx_attr_values_filter_product');

        $this->dropIndex('reviews', 'idx_reviews_product_status_created');
        $this->dropIndex('reviews', 'idx_reviews_product_status_rating');

        $this->dropIndex('products', 'idx_products_active_city_latest');
        $this->dropIndex('products', 'idx_products_active_user_latest');
        $this->dropIndex('products', 'idx_products_active_category_price');
        $this->dropIndex('products', 'idx_products_active_category_latest');
        $this->dropIndex('products', 'idx_products_active_latest');
    }
};
