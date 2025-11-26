<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttributesSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |------------------------------------------------------
        |  Очистка таблицы атрибутов (если хочешь оставить)
        |------------------------------------------------------
        */
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        DB::table('attributes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        /*
        |------------------------------------------------------
        |  Сидер полностью отключён
        |------------------------------------------------------
        |  Ниже return — всё, что было раньше, больше
        |  не выполняется, но код не удалён.
        |  Хочешь включить обратно — просто убираешь return.
        */
        return;


        /*
        |------------------------------------------------------
        | ВСЁ, ЧТО БЫЛО НИЖЕ — НЕ РАБОТАЕТ
        |------------------------------------------------------
        | Твои атрибуты, JSON options, фильтры и т.д.
        | Оставлено здесь для истории и возможности включения.
        */

        $attributes = [
            // пример:
            // ['name' => 'Бренд', 'type' => 'text', 'unit' => null, 'filterable' => 1],
            // и так далее...
        ];

        foreach ($attributes as $attr) {
            DB::table('attributes')->insert([
                'name'         => $attr['name'],
                'type'         => $attr['type'],
                'unit'         => $attr['unit'] ?? null,
                'is_filterable'=> $attr['filterable'] ?? 1,
                'options'      => isset($attr['options']) ? json_encode($attr['options']) : null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
