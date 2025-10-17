<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->nullable();   // 🔹 добавили slug
            $table->string('code', 5)->nullable();     // 🔹 добавили code (PMR, MD, UA)
            $table->string('emoji', 10)->nullable();   // 🔹 добавили emoji (🇲🇩🇺🇦)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
