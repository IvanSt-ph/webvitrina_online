<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        }
        if (! Schema::hasColumn('orders', 'buyer_contact')) {
            Schema::table('orders', fn (Blueprint $table) => $table->json('buyer_contact')->nullable());
        }

        // Legacy orders can only capture the currently available contact, not past edits.
        DB::statement("UPDATE orders JOIN users ON users.id = orders.user_id
            SET orders.buyer_contact = JSON_OBJECT('name', users.name, 'email', users.email, 'phone', users.phone)
            WHERE orders.buyer_contact IS NULL");

        // One ALTER avoids a window without FK enforcement. Never restore CASCADE.
        DB::statement('ALTER TABLE orders
            DROP FOREIGN KEY orders_user_id_foreign,
            DROP FOREIGN KEY orders_seller_id_foreign,
            ADD CONSTRAINT orders_buyer_history_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
            ADD CONSTRAINT orders_seller_history_foreign FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE RESTRICT');
    }

    public function down(): void
    {
        throw new RuntimeException('Account history protection cannot be rolled back automatically: retain anonymized users, order snapshots and RESTRICT foreign keys.');
    }
};
