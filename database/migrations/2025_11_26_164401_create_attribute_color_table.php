<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::create('attribute_color', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('attribute_id');
        $table->unsignedBigInteger('color_id');

        $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
        $table->foreign('color_id')->references('id')->on('colors')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_color');
    }
};
