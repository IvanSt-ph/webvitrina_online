<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('countries', function (Blueprint $table) {
        $table->unsignedBigInteger('id')->autoIncrement()->change();
    });
}

public function down(): void
{
    Schema::table('countries', function (Blueprint $table) {
        $table->bigInteger('id')->autoIncrement()->change();
    });
}

};
