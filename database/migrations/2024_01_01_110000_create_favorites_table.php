<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('favorites', function (Blueprint $t){
      $t->id();
      $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
      $t->timestamps();
      $t->unique(['user_id','product_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('favorites'); }
};
