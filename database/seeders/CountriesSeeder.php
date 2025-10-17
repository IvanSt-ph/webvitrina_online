<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('countries')->insertOrIgnore([
            ['id' => 1, 'name' => 'Приднестровье', 'slug' => 'pridnestrovie', 'code' => 'PMR', 'emoji' => '🇲🇩', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Молдова', 'slug' => 'moldova', 'code' => 'MD', 'emoji' => '🇲🇩', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Украина', 'slug' => 'ukraine', 'code' => 'UA', 'emoji' => '🇺🇦', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
