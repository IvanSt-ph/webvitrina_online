<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 16);
            $table->char('token_hash', 64)->unique();
            $table->unsignedBigInteger('telegram_user_id')->nullable();
            $table->unsignedBigInteger('active_telegram_user_id')->nullable()->unique();
            $table->unsignedBigInteger('start_update_id')->nullable()->unique();
            $table->unsignedBigInteger('start_message_id')->nullable();
            $table->unsignedBigInteger('contact_update_id')->nullable()->unique();
            $table->timestamp('bound_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_phone_verifications');
    }
};
