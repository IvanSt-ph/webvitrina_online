<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('products', function (Blueprint $t) {
            $t->string('currency_base', 3)->nullable()->after('price');
            $t->decimal('price_prb', 10, 2)->nullable()->after('currency_base');
            $t->decimal('price_mdl', 10, 2)->nullable()->after('price_prb');
            $t->decimal('price_uah', 10, 2)->nullable()->after('price_mdl');
        });

        // 🧩 Заполним currency_base по связке product -> city -> country
        // У тебя products.city_id -> cities.country_id -> countries.currency
        DB::statement("
            UPDATE products p
            JOIN cities ci ON ci.id = p.city_id
            JOIN countries co ON co.id = ci.country_id
            SET p.currency_base = co.currency
            WHERE p.currency_base IS NULL
        ");
    }

    public function down(): void {
        Schema::table('products', function (Blueprint $t) {
            $t->dropColumn(['currency_base','price_prb','price_mdl','price_uah']);
        });
    }
};
