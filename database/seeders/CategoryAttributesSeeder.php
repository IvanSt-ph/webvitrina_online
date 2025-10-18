<?php
namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoryAttributesSeeder extends Seeder
{
    public function run(): void
    {
        // Находим категории по slug
        $watch = Category::where('slug','Wristwatch')->first();
        $dress = Category::where('slug','dresses')->first();

        // ЧАСЫ
        $brand = Attribute::firstOrCreate(['name'=>'Бренд'], ['type'=>'select','options'=>json_encode(['Casio','Seiko','Tissot','Apple','Xiaomi'])]);
        $mechanism = Attribute::firstOrCreate(['name'=>'Механизм'], ['type'=>'select','options'=>json_encode(['кварцевый','механический','электронный'])]);
        $strap = Attribute::firstOrCreate(['name'=>'Размер браслета (мм)'], ['type'=>'number']);
        $color = Attribute::firstOrCreate(['name'=>'Цвет'], ['type'=>'color','options'=>json_encode(['#000000','#ffffff','#c0c0c0','#b87333','#d4af37'])]);

        if ($watch) {
            $watch->attributes()->syncWithoutDetaching([$brand->id,$mechanism->id,$strap->id,$color->id]);
        }

        // ПЛАТЬЯ
        $dressColor = Attribute::firstOrCreate(['name'=>'Цвет'], ['type'=>'color','options'=>json_encode(['#000000','#ffffff','#ff0000','#0000ff','#00b894'])]);
        $size = Attribute::firstOrCreate(['name'=>'Размер'], ['type'=>'select','options'=>json_encode(['XS','S','M','L','XL'])]);
        $fabric = Attribute::firstOrCreate(['name'=>'Ткань'], ['type'=>'select','options'=>json_encode(['хлопок','лён','шёлк','полиэстер','вискоза'])]);

        if ($dress) {
            $dress->attributes()->syncWithoutDetaching([$dressColor->id,$size->id,$fabric->id]);
        }
    }
}
