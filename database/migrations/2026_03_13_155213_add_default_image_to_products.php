<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Обновляем существующие товары
        DB::table('products')
            ->whereNull('image')
            ->orWhere('image', '')
            ->update(['image' => 'default/no-image.png']);
        
        // Меняем структуру
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->default('default/no-image.png')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->default(null)->change();
        });
        
        DB::table('products')
            ->where('image', 'default/no-image.png')
            ->update(['image' => null]);
    }
};