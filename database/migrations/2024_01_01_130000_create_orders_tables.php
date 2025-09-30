<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('orders', function (Blueprint $t){
      $t->id();
      $t->foreignId('user_id')->constrained('users');
      $t->unsignedInteger('total')->default(0);
      $t->string('status')->default('new'); // new, paid, shipped, done, canceled
      $t->timestamps();
    });
    Schema::create('order_items', function (Blueprint $t){
      $t->id();
      $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
      $t->foreignId('product_id')->constrained('products');
      $t->unsignedInteger('price');
      $t->unsignedInteger('qty');
      $t->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('order_items');
    Schema::dropIfExists('orders');
  }
};
