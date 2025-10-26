<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('countries', function (Blueprint $t) {
            $t->string('currency', 3)->default('MDL')->after('name');       // PRB | MDL | UAH
            $t->string('currency_symbol', 10)->default('L')->after('currency'); // '₽ ПМР' | 'L' | '₴'
        });

        // ⚠️ Подстрой имена под свои (я предположил русские названия)
        DB::table('countries')->where('name', 'like', '%Приднестров%')
            ->update(['currency' => 'PRB', 'currency_symbol' => '₽ ПМР']);
        DB::table('countries')->where('name', 'like', '%Молд%')
            ->update(['currency' => 'MDL', 'currency_symbol' => 'L']);
        DB::table('countries')->where('name', 'like', '%Украин%')
            ->update(['currency' => 'UAH', 'currency_symbol' => '₴']);
    }

    public function down(): void {
        Schema::table('countries', function (Blueprint $t) {
            $t->dropColumn(['currency','currency_symbol']);
        });
    }
};
