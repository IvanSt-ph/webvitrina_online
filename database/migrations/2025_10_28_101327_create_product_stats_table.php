<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            // 👇 Дата (одна строка на день)
            $table->date('date')->index();

            // 🔢 Метрики
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('favorites')->default(0);
            $table->unsignedBigInteger('carts')->default(0);

            $table->timestamps();

            // 🚀 Защита от дублей: один день на товар
            $table->unique(['product_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stats');
    }
};
