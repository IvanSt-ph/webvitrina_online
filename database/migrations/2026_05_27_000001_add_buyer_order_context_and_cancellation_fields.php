<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('cancellation_requested_at')->nullable()->after('canceled_at');
            $table->text('cancellation_reason')->nullable()->after('cancellation_requested_at');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('related_conversation_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancellation_requested_at', 'cancellation_reason']);
        });
    }
};
