<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::factory()->create(['email'=>'seller@example.com','role'=>'seller']);
        $buyer = User::factory()->create(['email'=>'buyer@example.com','role'=>'buyer']);

        for($i=1;$i<=12;$i++){
            Product::create([
                'user_id'=>$seller->id,
                'title'=>"Товар #$i",
                'slug'=>Str::slug("Товар $i")."-".Str::random(5),
                'price'=>rand(1000,9999),
                'stock'=>rand(0,20),
                'description'=>'Демо-описание товара.',
            ]);
        }
    }
}
