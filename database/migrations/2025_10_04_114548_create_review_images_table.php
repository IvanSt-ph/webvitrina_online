<?php

// database/migrations/xxxx_xx_xx_create_review_images_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('review_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->string('path'); // путь к файлу
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('review_images');
    }
};
