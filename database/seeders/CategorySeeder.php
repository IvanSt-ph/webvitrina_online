<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Пример: Одежда -> Мужская -> Штаны
        $clothes = Category::create(['name' => 'Одежда', 'slug' => 'clothes']);
        $men = Category::create(['name' => 'Мужская', 'slug' => 'men', 'parent_id' => $clothes->id]);
        Category::create(['name' => 'Штаны', 'slug' => 'pants', 'parent_id' => $men->id]);
        Category::create(['name' => 'Рубашки', 'slug' => 'shirts', 'parent_id' => $men->id]);

        $women = Category::create(['name' => 'Женская', 'slug' => 'women', 'parent_id' => $clothes->id]);
        Category::create(['name' => 'Платья', 'slug' => 'dresses', 'parent_id' => $women->id]);

        // Электроника -> Телефоны
        $electronics = Category::create(['name' => 'Электроника', 'slug' => 'electronics']);
        Category::create(['name' => 'Телефоны', 'slug' => 'phones', 'parent_id' => $electronics->id]);
        Category::create(['name' => 'Телевизоры', 'slug' => 'tv', 'parent_id' => $electronics->id]);

        // Продукты
        $food = Category::create(['name' => 'Продукты', 'slug' => 'food']);
        Category::create(['name' => 'Фрукты', 'slug' => 'fruits', 'parent_id' => $food->id]);
        Category::create(['name' => 'Овощи', 'slug' => 'vegetables', 'parent_id' => $food->id]);
    }
}
