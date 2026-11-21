<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_phone_verifications', function (Blueprint $table) {
            // Nullable for existing history. Unbound pending requests fail closed.
            $table->char('session_hash', 64)->nullable();
            $table->timestamp('contact_verified_at')->nullable();
            $table->index(['user_id', 'session_hash'], 'telegram_phone_user_session_index');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_phone_verifications', function (Blueprint $table) {
            $table->dropIndex('telegram_phone_user_session_index');
            $table->dropColumn(['session_hash', 'contact_verified_at']);
        });
    }
};
