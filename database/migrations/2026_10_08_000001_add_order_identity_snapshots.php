<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_title')->nullable();
            $table->string('product_sku')->nullable();
            $table->string('product_image_path')->nullable();
            $table->string('identity_snapshot_source', 24)->nullable();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->json('seller_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Dropping order identity snapshots would destroy order history.');
    }
};
