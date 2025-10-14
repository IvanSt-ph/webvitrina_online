<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляем колонку status в таблицу reviews
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('body'); 
            // значения: pending = ожидает, approved = одобрен, rejected = отклонён
        });
    }

    /**
     * Удаляем колонку при откате миграции
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
