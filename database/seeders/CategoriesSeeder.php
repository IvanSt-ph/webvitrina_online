<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        /*
        |--------------------------------------------------------------------------
        | Helper: создание категории с защитой от дублей slug
        |--------------------------------------------------------------------------
        */
        $add = function (string $name, $parent = null, string $slug = null) {
            $finalSlug = $slug ?: Str::rusSlug($name);

            // защита от дублей slug
            $base = $finalSlug;
            $i = 1;

            while (DB::table('categories')->where('slug', $finalSlug)->exists()) {
                $finalSlug = $base . '-' . $i++;
            }

            return DB::table('categories')->insertGetId([
                'name'       => $name,
                'slug'       => $finalSlug,
                'parent_id'  => $parent,
                'sort_order' => 0,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        };

        /*
        |--------------------------------------------------------------------------
        | ROOT CATEGORIES
        |--------------------------------------------------------------------------
        */

        $electronics = $add('Электроника');
        $clothes     = $add('Одежда');
        $shoes       = $add('Обувь');
        $beauty      = $add('Красота и уход');
        $kids        = $add('Детские товары');
        $home        = $add('Дом и быт');
        $auto        = $add('Автотовары');
        $food        = $add('Продукты');

        /*
        |--------------------------------------------------------------------------
        | ОБУВЬ
        |--------------------------------------------------------------------------
        */

        $menShoes = $add('Мужская обувь', $shoes, 'men-shoes');
        $add('Кроссовки', $menShoes, 'krossovki-men');
        $add('Ботинки', $menShoes, 'botinki-men');

        $womenShoes = $add('Женская обувь', $shoes, 'women-shoes');
        $add('Кроссовки', $womenShoes, 'krossovki-women');
        $add('Босоножки', $womenShoes, 'bosonozhki-women');
        $add('Туфли', $womenShoes, 'tufli-women');

        $kidsShoes = $add('Детская обувь', $shoes, 'kids-shoes');
        $add('Кеды', $kidsShoes, 'kedy-kids');
        $add('Сандалии', $kidsShoes, 'sandalii-kids');

        /*
        |--------------------------------------------------------------------------
        | ЭЛЕКТРОНИКА
        |--------------------------------------------------------------------------
        */

        $phones = $add('Смартфоны и гаджеты', $electronics, 'smartfony-i-gadzhety');
        $add('Смартфоны', $phones, 'smartfony');
        $add('Аксессуары', $phones, 'aksessuary-smartfony');

        $pc = $add('Ноутбуки и компьютеры', $electronics, 'noutbuki-kompyutery');
        $add('Игровые ноутбуки', $pc, 'igrovye-noutbuki');
        $add('Офисные ноутбуки', $pc, 'ofisnye-noutbuki');
        $add('Периферия', $pc, 'periferiya');
        $add('Комплектующие', $pc, 'komplektuyushchie');

        $tv = $add('ТВ и мультимедиа', $electronics, 'tv-i-multimedia');
        $add('Телевизоры', $tv, 'televizory');
        $add('Саундбары', $tv, 'soundbary');

        $smart = $add('Умный дом', $electronics, 'umnyi-dom');
        $add('Умные лампочки', $smart, 'umnye-lampy');
        $add('Камеры', $smart, 'umnye-kamery');

        /*
        |--------------------------------------------------------------------------
        | ОДЕЖДА
        |--------------------------------------------------------------------------
        */

        $men = $add('Мужская одежда', $clothes, 'men-wear');
        $add('Футболки', $men, 'futbolki-men');
        $add('Джинсы', $men, 'dzhinsy-men');
        $add('Брюки', $men, 'bryuki-men');
        $add('Верхняя одежда', $men, 'verkhnyaya-odezhda-men');

        $women = $add('Женская одежда', $clothes, 'women-wear');
        $add('Платья', $women, 'platya-women');
        $add('Блузки', $women, 'bluzki-women');
        $add('Топы', $women, 'topy-women');
        $add('Кофты', $women, 'kofty-women');

        $kidsWear = $add('Детская одежда', $clothes, 'kids-wear');
        $add('До 1 года', $kidsWear, 'do-1-goda');
        $add('1–7 лет', $kidsWear, '1-7-let');
        $add('7–14 лет', $kidsWear, '7-14-let');

        /*
        |--------------------------------------------------------------------------
        | КРАСОТА
        |--------------------------------------------------------------------------
        */

        $add('Парфюм', $beauty, 'parfyum');
        $add('Уход за лицом', $beauty, 'uhod-za-litsom');
        $add('Уход за телом', $beauty, 'uhod-za-telom');
        $add('Макияж', $beauty, 'makiyazh');

        /*
        |--------------------------------------------------------------------------
        | ДЕТСКИЕ
        |--------------------------------------------------------------------------
        */

        $add('Игрушки', $kids, 'igrushki');
        $add('Одежда', $kids, 'odezhda-kids');
        $add('Школьные товары', $kids, 'shkolnye-tovary');

        /*
        |--------------------------------------------------------------------------
        | ДОМ И БЫТ
        |--------------------------------------------------------------------------
        */

        $light = $add('Освещение', $home, 'osveshchenie');
        $add('Лампочки', $light, 'lampochki');
        $add('Торшеры', $light, 'torshery');

        $textile = $add('Текстиль', $home, 'tekstil');
        $add('Пледы', $textile, 'pledy');
        $add('Постельное белье', $textile, 'postelnoe-bele');

        $storage = $add('Хранение', $home, 'khranenie');
        $add('Корзины', $storage, 'korziny');
        $add('Контейнеры', $storage, 'konteinery');

        /*
        |--------------------------------------------------------------------------
        | АВТО
        |--------------------------------------------------------------------------
        */

        $add('Шины и диски', $auto, 'shiny-diski');
        $add('Аксессуары для авто', $auto, 'aksessuary-avto');
        $add('Автохимия', $auto, 'avtohimiya');

        /*
        |--------------------------------------------------------------------------
        | ПРОДУКТЫ
        |--------------------------------------------------------------------------
        */

        $add('Фрукты', $food, 'frukty');
        $add('Овощи', $food, 'ovoshchi');
        $add('Мясо', $food, 'myaso');
        $add('Напитки', $food, 'napitki');
    }
}

