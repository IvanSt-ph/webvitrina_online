<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\City;

class CountryCitySeeder extends Seeder
{
    public function run(): void
    {
        // === Страны ===
        $prid = Country::create(['name' => 'Приднестровье']);
        $mold = Country::create(['name' => 'Молдова']);
        $ukr  = Country::create(['name' => 'Украина']);

        // === Города ПМР ===
        City::insert([
            ['name' => 'Тирасполь', 'country_id' => $prid->id],
            ['name' => 'Бендеры',   'country_id' => $prid->id],
            ['name' => 'Рыбница',   'country_id' => $prid->id],
            ['name' => 'Дубоссары', 'country_id' => $prid->id],
        ]);

        // === Города Молдовы ===
        City::insert([
            ['name' => 'Кишинёв', 'country_id' => $mold->id],
            ['name' => 'Бельцы',  'country_id' => $mold->id],
            ['name' => 'Каушаны', 'country_id' => $mold->id],
            ['name' => 'Унгены',  'country_id' => $mold->id],
        ]);

        // === Города Украины ===
        City::insert([
            ['name' => 'Одесса', 'country_id' => $ukr->id],
            ['name' => 'Киев',   'country_id' => $ukr->id],
            ['name' => 'Львов',  'country_id' => $ukr->id],
            ['name' => 'Харьков','country_id' => $ukr->id],
        ]);
    }
}
