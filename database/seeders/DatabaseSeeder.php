<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
public function run(): void
{
    $this->call([
        CountriesSeeder::class,
        CitiesSeeder::class,
        CategoriesSeeder::class,
        UsersSeeder::class,
        CategoryAttributesSeeder::class,
        ProductsSeeder::class,
        
    ]);
}



}
