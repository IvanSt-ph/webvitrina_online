<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // 1) email можно сделать nullable — соц сети могут быть без почты
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->change();
            }

            // 2) provider
            if (!Schema::hasColumn('users', 'provider')) {
                $table->string('provider', 50)->nullable()->after('role');
            }

            // 3) provider_id
            if (!Schema::hasColumn('users', 'provider_id')) {
                $table->string('provider_id', 255)->nullable()->after('provider');
            }

            // 4) provider_token
            if (!Schema::hasColumn('users', 'provider_token')) {
                $table->text('provider_token')->nullable()->after('provider_id');
            }

            // 5) Индекс (поиск соц аккаунта)
            $table->index(['provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // вернуть email обратно (может упасть если есть NULL)
            $table->string('email')->nullable(false)->change();

            if (Schema::hasColumn('users', 'provider_token')) {
                $table->dropColumn('provider_token');
            }
            if (Schema::hasColumn('users', 'provider_id')) {
                $table->dropColumn('provider_id');
            }
            if (Schema::hasColumn('users', 'provider')) {
                $table->dropColumn('provider');
            }

            $table->dropIndex(['provider', 'provider_id']);
        });
    }
};
