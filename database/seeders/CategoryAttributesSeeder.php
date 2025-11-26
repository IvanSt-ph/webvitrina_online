<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryAttributesSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |------------------------------------------------------
        |  Очистка таблицы связей (если хочешь оставить)
        |------------------------------------------------------
        */
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        DB::table('attribute_category')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        /*
        |------------------------------------------------------
        |  Сидер отключён полностью
        |------------------------------------------------------
        |  Ниже — return, поэтому остальной код НЕ выполняется.
        |  Можешь включить обратно — просто убрав return.
        */
        return;


        /*
        |------------------------------------------------------
        | ВСЁ, ЧТО БЫЛО НИЖЕ — БОЛЬШЕ НЕ РАБОТАЕТ
        |------------------------------------------------------
        | Привязки атрибутов, категории — всё выключено.
        | Код оставлен, чтобы потом при желании восстановить.
        */

        // Пример старой логики, сейчас выключено:
        // $attach = function (string $slug, array $attrNames) {
        //     $category = Category::where('slug', $slug)->first();
        //     if (!$category) return;
        //     $attrIds = Attribute::whereIn('name', $attrNames)->pluck('id')->all();
        //     if (empty($attrIds)) return;
        //     $category->attributes()->syncWithoutDetaching($attrIds);
        // };

        // ...твои 500 строк кода...
    }
}
