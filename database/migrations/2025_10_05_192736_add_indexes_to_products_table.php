<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('user_id');
            $table->index(['country_id', 'city_id']);
        });
    }

    public function down(): void {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['country_id', 'city_id']);
        });
    }
};
