<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // 🌍 Добавляем страну и город
            $table->foreignId('country_id')
                ->nullable()
                ->after('stock')
                ->constrained('countries')
                ->nullOnDelete();

            $table->foreignId('city_id')
                ->nullable()
                ->after('country_id')
                ->constrained('cities')
                ->nullOnDelete();

            // 🏠 Координаты и адрес
            $table->string('address')->nullable()->after('city_id');
            $table->decimal('latitude', 10, 6)->nullable()->after('address');
            $table->decimal('longitude', 10, 6)->nullable()->after('latitude');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Удаляем связи и поля при откате
            $table->dropForeign(['country_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn(['country_id', 'city_id', 'address', 'latitude', 'longitude']);
        });
    }
};
