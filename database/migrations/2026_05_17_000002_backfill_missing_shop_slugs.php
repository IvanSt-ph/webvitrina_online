<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shops')
            ->whereNull('slug')
            ->orderBy('id')
            ->get(['id', 'user_id', 'name'])
            ->each(function (object $shop): void {
                $base = Str::slug($shop->name ?? '') ?: 'shop-' . $shop->user_id;
                $slug = $base;
                $counter = 1;

                while (DB::table('shops')
                    ->where('slug', $slug)
                    ->where('id', '!=', $shop->id)
                    ->exists()) {
                    $slug = $base . '-' . $counter++;
                }

                DB::table('shops')
                    ->where('id', $shop->id)
                    ->update(['slug' => $slug]);
            });
    }

    public function down(): void
    {
        //
    }
};
