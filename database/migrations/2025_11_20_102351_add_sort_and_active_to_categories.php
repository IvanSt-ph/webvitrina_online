<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('categories', function (Blueprint $table) {
        $table->unsignedInteger('sort_order')->default(0)->after('parent_id');
        $table->boolean('is_active')->default(true)->after('sort_order');
    });
}

public function down()
{
    Schema::table('categories', function (Blueprint $table) {
        $table->dropColumn(['sort_order', 'is_active']);
    });
}

};
