<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade'); // если удалить продавца — удалится и магазин

            $table->string('name')->nullable()->comment('Название магазина');
            $table->text('description')->nullable()->comment('Описание магазина');
            $table->string('phone')->nullable()->comment('Телефон магазина');
            $table->string('banner')->nullable()->comment('Изображение баннера');
            $table->string('city')->nullable()->comment('Город или местоположение');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
