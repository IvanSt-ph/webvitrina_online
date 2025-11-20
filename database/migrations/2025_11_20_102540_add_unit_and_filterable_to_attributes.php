<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('attributes', function (Blueprint $table) {
        $table->string('unit')->nullable()->after('type'); // мм, кг, %
        $table->boolean('is_filterable')->default(true)->after('unit');
    });
}

public function down()
{
    Schema::table('attributes', function (Blueprint $table) {
        $table->dropColumn(['unit', 'is_filterable']);
    });
}

};
