<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Индекс по статусу — для фильтрации и аналитики
            $table->index('status');

            // Временные метки жизненного цикла заказа (всё nullable, чтобы не ломать старые записи)
            $table->timestamp('accepted_at')->nullable()->after('status');   // продавец принял
            $table->timestamp('shipped_at')->nullable()->after('accepted_at'); // отправлен
            $table->timestamp('delivered_at')->nullable()->after('shipped_at'); // получен покупателем
            $table->timestamp('canceled_at')->nullable()->after('delivered_at'); // отменён
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['accepted_at', 'shipped_at', 'delivered_at', 'canceled_at']);
        });
    }
};
