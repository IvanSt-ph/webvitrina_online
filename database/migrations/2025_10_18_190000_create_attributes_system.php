<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Справочник атрибутов
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Цвет", "Размер", "Механизм"
            $table->enum('type', ['string','number','boolean','select','color'])->default('string');
            $table->json('options')->nullable(); // для select/color: ["Красный","Синий"] или ["кварцевый","механический"]
            $table->timestamps();
        });

        // 2) Какие атрибуты доступны в какой категории
        Schema::create('attribute_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->unique(['category_id','attribute_id']);
        });

        // 3) Значения атрибутов у товара
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value'); // храним как строку (для number тоже, фильтр сравнит как число при необходимости)
            $table->timestamps();

            // индексы для быстрых фильтров
            $table->index(['attribute_id', 'value']);
            $table->index(['product_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attribute_category');
        Schema::dropIfExists('attributes');
    }
};
