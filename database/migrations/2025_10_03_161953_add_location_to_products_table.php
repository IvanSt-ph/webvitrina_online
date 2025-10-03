<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('products', function (Blueprint $table) {
        $table->string('address')->nullable()->after('description');
        $table->decimal('latitude', 10, 6)->nullable()->after('address');
        $table->decimal('longitude', 10, 6)->nullable()->after('latitude');
    });
}

public function down()
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn(['address', 'latitude', 'longitude']);
    });
}

};
