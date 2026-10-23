<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_attempts', function (Blueprint $table) {
            $table->char('token_hash', 64)->primary();
            // Retain replay protection independently of account lifecycle.
            $table->unsignedBigInteger('buyer_id');
            $table->json('order_ids');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_attempts');
    }
};
