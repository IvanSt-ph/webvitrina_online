<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fail before the first DDL statement if this is not the expected legacy schema.
        // MySQL DDL commits independently; an interrupted migration needs manual review.
        $statusColumn = DB::selectOne(
            'SELECT COLUMN_TYPE AS type, IS_NULLABLE AS nullable, COLUMN_DEFAULT AS default_value
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['orders', 'status'],
        );

        if (DB::connection()->getDriverName() !== 'mysql'
            || $statusColumn?->type !== "enum('pending','processing','paid','shipped','delivered','completed','canceled')"
            || $statusColumn->nullable !== 'NO'
            || $statusColumn->default_value !== 'pending'
            || Schema::hasTable('order_events')
            || Schema::hasTable('order_admin_decisions')) {
            throw new RuntimeException('Unexpected order schema; review the migration target before changing it.');
        }

        foreach ([
            'workflow_version', 'payment_status', 'ready_for_pickup_at',
            'buyer_confirmed_at', 'completed_at', 'confirmation_requested_at',
        ] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                throw new RuntimeException("Unexpected orders.{$column} column; review the partial migration state.");
            }
        }

        // Append only: existing ENUM ordinals and every stored status remain unchanged.
        // INSTANT prevents a silent fallback to a table copy on MySQL 8.4.
        DB::statement("ALTER TABLE orders MODIFY COLUMN status
            ENUM('pending','processing','paid','shipped','delivered','completed','canceled','ready_for_pickup')
            NOT NULL DEFAULT 'pending', ALGORITHM=INSTANT");

        // NULL means legacy/unknown. Checkout will opt new orders in during a later stage.
        // Keep the additive columns in one metadata-only statement, with no FK rebuild.
        DB::statement('ALTER TABLE orders
            ADD COLUMN workflow_version TINYINT UNSIGNED NULL,
            ADD COLUMN payment_status VARCHAR(24) NULL,
            ADD COLUMN ready_for_pickup_at TIMESTAMP NULL,
            ADD COLUMN buyer_confirmed_at TIMESTAMP NULL,
            ADD COLUMN completed_at TIMESTAMP NULL,
            ADD COLUMN confirmation_requested_at TIMESTAMP NULL,
            ALGORITHM=INSTANT');

        Schema::create('order_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('actor_role', 16); // buyer, seller, admin, or system
            $table->string('event_type', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->string('from_payment_status', 24)->nullable();
            $table->string('to_payment_status', 24)->nullable();
            $table->string('event_key', 64)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'created_at']);
        });

        Schema::create('order_admin_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 40);
            $table->text('reason');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Pickup workflow history cannot be dropped automatically; restore a reviewed backup instead.');
    }
};
