<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'provider_token')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('provider_token');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'provider_token')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->text('provider_token')->nullable()->after('provider_id');
        });
    }
};
