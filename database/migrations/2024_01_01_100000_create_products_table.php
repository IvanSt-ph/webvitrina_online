<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('products', function (Blueprint $t) {
      $t->id();
      $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $t->string('title');
      $t->string('slug')->unique();
      $t->unsignedInteger('price');
      $t->unsignedInteger('stock')->default(0);
      $t->string('image')->nullable();
      $t->text('description')->nullable();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('products'); }
};
