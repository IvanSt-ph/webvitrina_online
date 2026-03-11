<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        // Используем updateOrCreate вместо insert
        $countries = [
            ['slug' => 'pmr', 'name' => 'Приднестровье', 'currency' => 'RUB', 'currency_symbol' => '₽'],
            ['slug' => 'md', 'name' => 'Молдова', 'currency' => 'MDL', 'currency_symbol' => 'L'],
            ['slug' => 'ua', 'name' => 'Украина', 'currency' => 'UAH', 'currency_symbol' => '₴'],
        ];

        foreach ($countries as $country) {
            DB::table('countries')->updateOrInsert(
                ['slug' => $country['slug']], // поиск по slug
                $country // данные для обновления или вставки
            );
        }
    }
}