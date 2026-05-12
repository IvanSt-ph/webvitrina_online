<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Core reference data needed by the app.
        $this->call([
            CountriesSeeder::class,
            CountryCitySeeder::class,
            CategoriesSeeder::class,
        ]);

        if (filter_var(env('SEED_ADMIN_USER', false), FILTER_VALIDATE_BOOL)) {
            $this->call(UsersSeeder::class);
        }

        if (filter_var(env('SEED_DEMO_PRODUCTS', false), FILTER_VALIDATE_BOOL)) {
            $this->call(ProductsSeeder::class);
        }
    }
}
