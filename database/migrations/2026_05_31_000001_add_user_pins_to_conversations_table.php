<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('buyer_pinned_at')->nullable()->after('admin_deleted_at')->index();
            $table->timestamp('seller_pinned_at')->nullable()->after('buyer_pinned_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['buyer_pinned_at', 'seller_pinned_at']);
        });
    }
};
