<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('attribute_category', function (Blueprint $table) {
        $table->unsignedInteger('sort_order')->default(0)->after('attribute_id');
    });
}

public function down()
{
    Schema::table('attribute_category', function (Blueprint $table) {
        $table->dropColumn('sort_order');
    });
}

};
