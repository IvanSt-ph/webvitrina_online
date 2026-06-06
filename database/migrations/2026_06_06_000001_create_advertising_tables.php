<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_slots', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('placement')->index();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_slot_id')->constrained('ad_slots')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_type', 20)->default('product')->index();
            $table->string('title');
            $table->string('label')->default('Продвигается');
            $table->text('description')->nullable();
            $table->string('destination_url')->nullable();
            $table->unsignedInteger('sort_order')->default(100)->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamps();

            $table->index(['ad_slot_id', 'is_active', 'starts_at', 'ends_at'], 'idx_ad_campaigns_slot_live');
            $table->index(['target_type', 'is_active', 'sort_order'], 'idx_ad_campaigns_target_sort');
        });

        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 120)->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('page_url', 500)->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['ad_campaign_id', 'occurred_at'], 'idx_ad_impressions_campaign_time');
        });

        Schema::create('ad_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 120)->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('page_url', 500)->nullable();
            $table->string('target_url', 500)->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['ad_campaign_id', 'occurred_at'], 'idx_ad_clicks_campaign_time');
        });

        DB::table('ad_slots')->insert([
            [
                'key' => 'home_featured_products',
                'name' => 'Рекомендуемые товары',
                'placement' => 'home',
                'description' => 'Ручной блок продвигаемых товаров на главной странице.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'home_weekly_shops',
                'name' => 'Магазины недели',
                'placement' => 'home',
                'description' => 'Ручной блок продвигаемых магазинов на главной странице.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'category_featured_products',
                'name' => 'Популярное в категории',
                'placement' => 'category',
                'description' => 'Будущий слот для ручного продвижения товаров внутри категорий.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'product_related_promoted',
                'name' => 'Партнёрский блок в карточке товара',
                'placement' => 'product',
                'description' => 'Будущий слот для аккуратных рекомендаций на странице товара.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_clicks');
        Schema::dropIfExists('ad_impressions');
        Schema::dropIfExists('ad_campaigns');
        Schema::dropIfExists('ad_slots');
    }
};
