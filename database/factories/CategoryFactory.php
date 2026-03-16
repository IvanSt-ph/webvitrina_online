<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            // 'description' => fake()->sentence(), // УБИРАЕМ, так как поля нет
            'parent_id' => null,
            // Если есть другие поля, добавь их здесь
        ];
    }
}