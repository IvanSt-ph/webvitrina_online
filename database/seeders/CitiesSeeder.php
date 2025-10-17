<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cities')->insertOrIgnore([
            ['id' => 1, 'name' => 'Тирасполь', 'country_id' => 1],
            ['id' => 2, 'name' => 'Бендеры', 'country_id' => 1],
            ['id' => 3, 'name' => 'Рыбница', 'country_id' => 1],
            ['id' => 4, 'name' => 'Дубоссары', 'country_id' => 1],
            ['id' => 5, 'name' => 'Кишинёв', 'country_id' => 2],
            ['id' => 6, 'name' => 'Бельцы', 'country_id' => 2],
            ['id' => 7, 'name' => 'Каушаны', 'country_id' => 2],
            ['id' => 8, 'name' => 'Унгены', 'country_id' => 2],
            ['id' => 9, 'name' => 'Одесса', 'country_id' => 3],
            ['id' => 10, 'name' => 'Киев', 'country_id' => 3],
            ['id' => 11, 'name' => 'Львов', 'country_id' => 3],
            ['id' => 12, 'name' => 'Харьков', 'country_id' => 3],
        ]);
    }
}
