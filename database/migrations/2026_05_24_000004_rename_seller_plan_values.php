<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'seller_plan')) {
            return;
        }

        DB::table('users')->where('seller_plan', 'standard')->update(['seller_plan' => 'starter']);
        DB::table('users')->where('seller_plan', 'gold')->update(['seller_plan' => 'basic']);
        DB::table('users')->where('seller_plan', 'platinum')->update(['seller_plan' => 'pro']);
        DB::table('users')->where('seller_plan', 'vip')->update(['seller_plan' => 'business']);
        DB::table('users')->where('seller_plan', 'president')->update(['seller_plan' => 'enterprise']);

        DB::statement("ALTER TABLE users MODIFY seller_plan VARCHAR(30) NOT NULL DEFAULT 'starter'");
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'seller_plan')) {
            return;
        }

        DB::table('users')->where('seller_plan', 'starter')->update(['seller_plan' => 'standard']);
        DB::table('users')->where('seller_plan', 'basic')->update(['seller_plan' => 'gold']);
        DB::table('users')->where('seller_plan', 'pro')->update(['seller_plan' => 'platinum']);
        DB::table('users')->where('seller_plan', 'business')->update(['seller_plan' => 'vip']);
        DB::table('users')->where('seller_plan', 'enterprise')->update(['seller_plan' => 'president']);

        DB::statement("ALTER TABLE users MODIFY seller_plan VARCHAR(30) NOT NULL DEFAULT 'standard'");
    }
};
