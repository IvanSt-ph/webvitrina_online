<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('shop_id')
                ->constrained('categories')
                ->nullOnDelete();
            $table->unsignedInteger('max_impressions')
                ->nullable()
                ->after('sort_order');

            $table->index(['category_id', 'is_active', 'sort_order'], 'idx_ad_campaigns_category_sort');
        });
    }

    public function down(): void
    {
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropIndex('idx_ad_campaigns_category_sort');
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('max_impressions');
        });
    }
};
