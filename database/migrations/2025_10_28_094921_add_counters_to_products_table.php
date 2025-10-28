<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('stock');
            $table->unsignedBigInteger('favorites_count')->default(0)->after('views_count');
            $table->unsignedBigInteger('cart_adds_count')->default(0)->after('favorites_count');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['views_count', 'favorites_count', 'cart_adds_count']);
        });
    }
};
