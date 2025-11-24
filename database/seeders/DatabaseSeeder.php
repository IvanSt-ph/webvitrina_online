<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 🏗 Запускаем сидеры строго по порядку
        $this->call([

            // 1) Страны и города
            CountriesSeeder::class,
            CitiesSeeder::class,

            // 2) Категории (корневые + подкатегории)
            CategoriesSeeder::class,

            // 3) Атрибуты и связки
            AttributesSeeder::class,
            CategoryAttributesSeeder::class,

            // 4) Пользователи (опционально)
            UsersSeeder::class,

            // 5) Демо товары (если нужно)
            ProductsSeeder::class,
        ]);
    }
}
