<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('products')->insertOrIgnore([
            [
                'id' => 1,
                'user_id' => 2,
                'category_id' => 28,
                'title' => 'Футболка белая мужская',
                'slug' => 'futbolka-belaya-muzhskaya',
                'price' => 1200.00,
                'stock' => 10,
                'image' => 'products/futbolka_white.jpg',
                'description' => 'Качественная хлопковая футболка белого цвета.',
                'address' => 'улица Суворова, 52, Бендеры, Молдавия',
                'latitude' => 46.818487,
                'longitude' => 29.481011,
                'country_id' => 1,
                'city_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'category_id' => 8,
                'title' => 'Смартфон Samsung Galaxy A52',
                'slug' => 'smartfon-samsung-galaxy-a52',
                'price' => 10500.00,
                'stock' => 8,
                'image' => 'products/samsung_a52.jpg',
                'description' => 'Смартфон Samsung с отличной камерой и большим экраном.',
                'address' => 'улица Ленина, 19, Рыбница, Приднестровье',
                'latitude' => 47.765638,
                'longitude' => 29.001160,
                'country_id' => 1,
                'city_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'user_id' => 2,
                'category_id' => 34,
                'title' => 'Ковёр Аладина',
                'slug' => 'kover-aladina',
                'price' => 1500.00,
                'stock' => 3,
                'image' => 'products/kover_aladina.jpg',
                'description' => 'Мягкий ковёр в восточном стиле, “На нём летал Гарри Поттер”.',
                'address' => 'улица 1 Мая, 18, Каушаны, Молдова',
                'latitude' => 46.653133,
                'longitude' => 29.407268,
                'country_id' => 2,
                'city_id' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
