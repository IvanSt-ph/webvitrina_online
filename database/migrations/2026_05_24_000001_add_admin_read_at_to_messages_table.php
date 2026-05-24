<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('admin_read_at')->nullable()->after('read_at')->index();
        });

        DB::table('messages')
            ->whereNotNull('read_at')
            ->update(['admin_read_at' => DB::raw('read_at')]);
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('admin_read_at');
        });
    }
};
