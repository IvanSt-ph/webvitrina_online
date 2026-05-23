<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('conversation_type', 24)->default('marketplace')->after('context_key')->index();
            $table->timestamp('locked_at')->nullable()->after('last_message_at')->index();
            $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
            $table->string('locked_reason', 500)->nullable()->after('locked_by');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('type', 24)->default('message')->after('sender_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn(['conversation_type', 'locked_at', 'locked_reason']);
        });
    }
};
