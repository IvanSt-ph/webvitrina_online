<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('context_key')->default('general')->after('product_id');
        });

        DB::table('conversations')
            ->whereNotNull('product_id')
            ->update([
                'context_key' => DB::raw("CONCAT('product:', product_id)"),
            ]);

        $duplicates = DB::table('conversations')
            ->select('buyer_id', 'seller_id', 'context_key', DB::raw('MIN(id) as keep_id'))
            ->groupBy('buyer_id', 'seller_id', 'context_key')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $duplicateIds = DB::table('conversations')
                ->where('buyer_id', $duplicate->buyer_id)
                ->where('seller_id', $duplicate->seller_id)
                ->where('context_key', $duplicate->context_key)
                ->where('id', '!=', $duplicate->keep_id)
                ->pluck('id');

            DB::table('messages')
                ->whereIn('conversation_id', $duplicateIds)
                ->update(['conversation_id' => $duplicate->keep_id]);

            DB::table('conversations')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }

        Schema::table('conversations', function (Blueprint $table) {
            $table->unique(['buyer_id', 'seller_id', 'context_key'], 'conversations_unique_context');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'read_at', 'sender_id'], 'messages_unread_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_unread_lookup_index');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_unique_context');
            $table->dropColumn('context_key');
        });
    }
};
