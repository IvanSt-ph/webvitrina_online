<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
public function run()
{
    $colors = [
        ['name' => 'Белый',   'hex' => '#FFFFFF'],
        ['name' => 'Чёрный',  'hex' => '#000000'],
        ['name' => 'Красный', 'hex' => '#FF0000'],
        ['name' => 'Синий',   'hex' => '#0000FF'],
        ['name' => 'Зелёный', 'hex' => '#00FF00'],
        ['name' => 'Жёлтый',  'hex' => '#FFFF00'],
        ['name' => 'Серый',   'hex' => '#808080'],
        ['name' => 'Золотой', 'hex' => '#FFD700'],
    ];

    foreach ($colors as $c) {
        \App\Models\Color::create($c);
    }
}
    
}
