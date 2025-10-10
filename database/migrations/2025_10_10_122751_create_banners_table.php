<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('banners', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->string('image');                 // путь в storage/app/public/...
        $table->string('link')->nullable();      // куда везём по клику
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('active')->default(true);
        $table->timestamps();
    });
}

};
