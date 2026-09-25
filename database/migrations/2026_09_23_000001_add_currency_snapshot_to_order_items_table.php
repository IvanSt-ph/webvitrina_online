<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('source_price', 10, 2)->nullable()->after('total');
            $table->string('source_currency', 3)->nullable()->after('source_price');
            $table->decimal('exchange_rate', 18, 8)->nullable()->after('source_currency');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['source_price', 'source_currency', 'exchange_rate']);
        });
    }
};
