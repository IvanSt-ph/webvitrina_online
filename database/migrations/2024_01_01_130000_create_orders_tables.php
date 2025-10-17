<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 🧾 Таблица заказов
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Покупатель
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete(); // Продавец (если один продавец)
            $table->string('number')->unique(); // Номер заказа, например "ORD-000123"
            $table->enum('status', [
                'pending',     // Ожидает оплаты
                'paid',        // Оплачен
                'shipped',     // Отправлен
                'completed',   // Завершён
                'canceled'     // Отменён
            ])->default('pending');
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('currency', 10)->default('RUB');
            $table->text('delivery_address')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('delivery_method', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // 📦 Таблица товаров в заказе
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2); // Цена на момент покупки
            $table->decimal('total', 10, 2); // = quantity * price
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
