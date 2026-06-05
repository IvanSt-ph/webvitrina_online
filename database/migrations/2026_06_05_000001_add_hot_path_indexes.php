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
        $this->addIndex('orders', 'idx_orders_buyer_status_latest', 'user_id, status, created_at, id');
        $this->addIndex('orders', 'idx_orders_seller_status_latest', 'seller_id, status, created_at, id');
        $this->addIndex('orders', 'idx_orders_status_created', 'status, created_at, id');
        $this->addIndex('orders', 'idx_orders_seller_cancel_attention', 'seller_id, cancellation_requested_at, status');

        $this->addIndex('products', 'idx_products_seller_status_price', 'user_id, status, price, id');
        $this->addIndex('products', 'idx_products_seller_status_stock', 'user_id, status, stock, id');

        $this->addIndex('conversations', 'idx_conversations_buyer_visible_latest', 'buyer_id, buyer_deleted_at, last_message_at');
        $this->addIndex('conversations', 'idx_conversations_seller_visible_latest', 'seller_id, seller_deleted_at, last_message_at');
        $this->addIndex('messages', 'idx_messages_admin_unread_lookup', 'conversation_id, admin_read_at, sender_id');
    }

    public function down(): void
    {
        $this->dropIndex('messages', 'idx_messages_admin_unread_lookup');
        $this->dropIndex('conversations', 'idx_conversations_seller_visible_latest');
        $this->dropIndex('conversations', 'idx_conversations_buyer_visible_latest');

        $this->dropIndex('products', 'idx_products_seller_status_stock');
        $this->dropIndex('products', 'idx_products_seller_status_price');

        $this->dropIndex('orders', 'idx_orders_seller_cancel_attention');
        $this->dropIndex('orders', 'idx_orders_status_created');
        $this->dropIndex('orders', 'idx_orders_seller_status_latest');
        $this->dropIndex('orders', 'idx_orders_buyer_status_latest');
    }
};
