<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $invalidUserPhone = DB::table('users')
            ->whereNotNull('phone')
            ->whereRaw("phone NOT REGEXP '^[+][0-9]{7,15}$'")
            ->exists();
        $invalidShopPhone = DB::table('shops')
            ->whereNotNull('phone')
            ->whereRaw("phone NOT REGEXP '^[+][0-9]{7,15}$'")
            ->exists();
        $duplicateUserPhone = DB::table('users')
            ->select('phone')
            ->whereNotNull('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        $duplicateShopPhone = DB::table('shops')
            ->select('phone')
            ->whereNotNull('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        $crossOwnerConflict = DB::table('users as users_phone_owner')
            ->join('shops as shops_phone_owner', 'shops_phone_owner.phone', '=', 'users_phone_owner.phone')
            ->whereNotNull('users_phone_owner.phone')
            ->whereColumn('users_phone_owner.id', '!=', 'shops_phone_owner.user_id')
            ->exists();

        if ($invalidUserPhone || $invalidShopPhone || $duplicateUserPhone || $duplicateShopPhone || $crossOwnerConflict) {
            throw new RuntimeException(
                'Phone uniqueness preflight failed. Resolve invalid or conflicting phone ownership before adding indexes.',
            );
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('phone', 'users_phone_unique');
        });

        Schema::table('shops', function (Blueprint $table): void {
            $table->unique('phone', 'shops_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->dropUnique('shops_phone_unique');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_phone_unique');
        });
    }
};
