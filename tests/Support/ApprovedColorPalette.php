<?php

namespace Tests\Support;

/** Independent specification fixture; never derives expectations from application constants. */
final class ApprovedColorPalette
{
    public const NAMES = ['Белый', 'Чёрный', 'Серый', 'Красный', 'Синий', 'Голубой', 'Зелёный', 'Жёлтый', 'Оранжевый', 'Розовый', 'Фиолетовый', 'Коричневый', 'Бежевый', 'Золотой', 'Серебристый'];

    public static function catalog(): array
    {
        return [
            ['id' => 1, 'name' => 'Черный', 'hex' => '#000000'],
            ['id' => 2, 'name' => 'Синий', 'hex' => '#0011ff'],
            ['id' => 3, 'name' => 'Белый', 'hex' => '#FFFFFF'],
            ['id' => 4, 'name' => 'Серый', 'hex' => '#808080'],
            ['id' => 5, 'name' => 'Красный', 'hex' => '#FF0000'],
            ['id' => 6, 'name' => 'Голубой', 'hex' => '#00BFFF'],
            ['id' => 7, 'name' => 'Зелёный', 'hex' => '#008000'],
            ['id' => 8, 'name' => 'Жёлтый', 'hex' => '#FFFF00'],
            ['id' => 9, 'name' => 'Оранжевый', 'hex' => '#FFA500'],
            ['id' => 10, 'name' => 'Розовый', 'hex' => '#FFC0CB'],
            ['id' => 11, 'name' => 'Фиолетовый', 'hex' => '#800080'],
            ['id' => 12, 'name' => 'Коричневый', 'hex' => '#8B4513'],
            ['id' => 13, 'name' => 'Бежевый', 'hex' => '#F5F5DC'],
            ['id' => 14, 'name' => 'Золотой', 'hex' => '#FFD700'],
            ['id' => 15, 'name' => 'Серебристый', 'hex' => '#C0C0C0'],
        ];
    }
}
