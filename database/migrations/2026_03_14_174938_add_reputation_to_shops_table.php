<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->integer('sales_count')->unsigned()->default(0)->after('reviews_count');
            $table->string('seller_reputation', 50)->default('new')->after('sales_count');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['sales_count', 'seller_reputation']);
        });
    }
};