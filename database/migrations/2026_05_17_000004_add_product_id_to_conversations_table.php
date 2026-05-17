<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->index('buyer_id');
            $table->index('seller_id');
            $table->dropUnique(['buyer_id', 'seller_id']);
            $table->foreignId('product_id')
                ->nullable()
                ->after('seller_id')
                ->constrained()
                ->nullOnDelete();
            $table->index(['buyer_id', 'seller_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['buyer_id', 'seller_id', 'product_id']);
            $table->dropConstrainedForeignId('product_id');
            $table->unique(['buyer_id', 'seller_id']);
            $table->dropIndex(['buyer_id']);
            $table->dropIndex(['seller_id']);
        });
    }
};
