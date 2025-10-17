<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('name'); // Тирасполь, Кишинёв, Одесса и т.п.
            $table->timestamps();

            $table->unique(['country_id', 'name']); // чтобы не было дубликатов в одной стране
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
