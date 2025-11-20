<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            // Удаляем FK
            $table->dropForeign('attribute_values_attribute_id_foreign');

            // Удаляем старый индекс
            $table->dropIndex('attribute_values_attribute_id_value_index');
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            // Создаём новый составной индекс
            $table->index(['attribute_id', DB::raw('value(100)')], 'attr_val_filter_idx');

            // Возвращаем FK
            $table->foreign('attribute_id')
                ->references('id')->on('attributes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            // Откат: удаляем новый индекс
            $table->dropIndex('attr_val_filter_idx');

            // Удаляем новый FK
            $table->dropForeign(['attribute_id']);

            // Восстанавливаем старый индекс
            $table->index(['attribute_id', 'value'], 'attribute_values_attribute_id_value_index');

            // Восстанавливаем FK как было
            $table->foreign('attribute_id')
                ->references('id')->on('attributes')
                ->onDelete('cascade');
        });
    }
};
