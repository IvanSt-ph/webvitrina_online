<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('buyer_deleted_at')->nullable()->after('locked_reason')->index();
            $table->timestamp('seller_deleted_at')->nullable()->after('buyer_deleted_at')->index();
            $table->timestamp('admin_deleted_at')->nullable()->after('seller_deleted_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['buyer_deleted_at', 'seller_deleted_at', 'admin_deleted_at']);
        });
    }
};
