<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('old_price', 10, 2)->nullable()->after('price');
            $table->decimal('old_price_prb', 10, 2)->nullable()->after('price_prb');
            $table->decimal('old_price_mdl', 10, 2)->nullable()->after('price_mdl');
            $table->decimal('old_price_uah', 10, 2)->nullable()->after('price_uah');
            $table->index(['status', 'old_price'], 'idx_products_active_old_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_active_old_price');
            $table->dropColumn(['old_price', 'old_price_prb', 'old_price_mdl', 'old_price_uah']);
        });
    }
};
