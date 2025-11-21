<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insertOrIgnore([

            // ========== ROOT ==========
            [
                'id' => 7,
                'name' => 'Электроника',
                'slug' => 'electronics',
                'icon' => 'categories/icons/8H4uQlXnSC4ZPx5yHKpdWfXzqrCVIquMXhdSQLnl.png',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ========== CHILDREN OF ELECTRONICS ==========
            [
                'id' => 8,
                'name' => 'Телефоны',
                'slug' => 'phones',
                'icon' => 'categories/icons/LggIu88P6i9S64CRwXVHZn7hbHWoQx1yMliUKVK7.png',
                'parent_id' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'name' => 'Телевизоры',
                'slug' => 'tv',
                'icon' => 'categories/icons/RTlwdyLDwOJ9r0vJeRNS5JfuPG7nMI1XhbiBQBYk.png',
                'parent_id' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 35,
                'name' => 'Часы',
                'slug' => 'watch',
                'icon' => 'categories/icons/UuyBbR6qwtqIo1adMqGyQJojSM8OR4rZEUh45UMg.png',
                'parent_id' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ========== Часы — подкатегории ==========
            [
                'id' => 36,
                'name' => 'Наручные часы',
                'slug' => 'wristwatch',
                'icon' => 'categories/icons/iQvAIbZezkd9KrN4RScpVkzDriGVY6l98H7TbQlj.png',
                'parent_id' => 35,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ========== FOOD ==========
            [
                'id' => 10,
                'name' => 'Продукты',
                'slug' => 'food',
                'icon' => 'categories/icons/8H4uQlXnSC4ZPx5yHKpdWfXzqrCVIquMXhdSQLnl.png',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'name' => 'Фрукты',
                'slug' => 'fruits',
                'icon' => 'categories/icons/pPPfSQhJ74OWn8PAq6nYO2XAgsU46yJYgqVWExa2.png',
                'parent_id' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'name' => 'Овощи',
                'slug' => 'vegetables',
                'icon' => 'categories/icons/LggIu88P6i9S64CRwXVHZn7hbHWoQx1yMliUKVK7.png',
                'parent_id' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ========== CLOTHES ==========
            [
                'id' => 23,
                'name' => 'Одежда',
                'slug' => 'clothes',
                'icon' => 'categories/icons/RTlwdyLDwOJ9r0vJeRNS5JfuPG7nMI1XhbiBQBYk.png',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Мужская
            [
                'id' => 24,
                'name' => 'Мужская',
                'slug' => 'man',
                'icon' => 'categories/icons/lbZQz37bsgY77MMTSgo5ZfenvWrKCdxSIGIyPBC5.png',
                'parent_id' => 23,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 26,
                'name' => 'Штаны',
                'slug' => 'pants',
                'icon' => 'categories/icons/NGeCXqy3TIrTJ9j6fnbyRsHalRkqhSwTCgKwEJbn.png',
                'parent_id' => 24,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Женская
            [
                'id' => 27,
                'name' => 'Женская',
                'slug' => 'woman',
                'icon' => 'categories/icons/pPPfSQhJ74OWn8PAq6nYO2XAgsU46yJYgqVWExa2.png',
                'parent_id' => 23,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 28,
                'name' => 'Платье',
                'slug' => 'dresses',
                'icon' => 'categories/icons/UeV82oVyIWV0cLZo7nTmHk1yTHnBediSEr0I7oML.png',
                'parent_id' => 27,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ========== HOME INTERIOR ==========
            [
                'id' => 33,
                'name' => 'Домашний интерьер',
                'slug' => 'home_interier',
                'icon' => 'categories/icons/GrDmhhADaLKK2M8mi289m8ZCKgG6bds0oS1eakl4.png',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 34,
                'name' => 'Ковры и дорожки',
                'slug' => 'carpets_and_rugs',
                'icon' => 'categories/icons/NGeCXqy3TIrTJ9j6fnbyRsHalRkqhSwTCgKwEJbn.png',
                'parent_id' => 33,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ========== AUTO ==========
            [
                'id' => 37,
                'name' => 'Автотовары',
                'slug' => 'auto',
                'icon' => 'categories/icons/jUb4Z9duKkaJckG5kBOQUcqSAcQ8EYSpMy9a0tam.png',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 38,
                'name' => 'Колесо',
                'slug' => 'wheel',
                'icon' => 'categories/icons/bhq2G2oRfsZ2AuuZNxtw4XJ3bpUaHonDTdl3xkmA.png',
                'parent_id' => 37,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
