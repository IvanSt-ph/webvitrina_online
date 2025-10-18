<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // индексы
            $table->index('category_id');
            $table->index('user_id');
            $table->index('city_id');

            // новые поля
            $table->softDeletes();
            $table->string('status')->default('draft');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // откат
            $table->dropSoftDeletes();
            $table->dropColumn('status');

            $table->dropIndex(['category_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['city_id']);
        });
    }
};
