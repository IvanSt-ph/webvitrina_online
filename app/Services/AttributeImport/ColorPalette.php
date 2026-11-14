<?php

namespace App\Services\AttributeImport;

/** Pure validation shared by import and the explicit legacy conversion. */
final class ColorPalette
{
    public const INITIAL = ['Белый', 'Чёрный', 'Серый', 'Красный', 'Синий', 'Голубой', 'Зелёный', 'Жёлтый', 'Оранжевый', 'Розовый', 'Фиолетовый', 'Коричневый', 'Бежевый', 'Золотой', 'Серебристый'];

    /** Exact-name policy; other color attributes retain their own palettes. */
    public static function assertSelection(string $name, string $type, array $ids, array $catalog): void
    {
        self::assertType($name, $type);
        if ($name !== 'Цвет') {
            return;
        }
        foreach ($ids as $id) {
            if ((! is_int($id) && ! is_string($id)) || ! preg_match('/^[1-9][0-9]*$/D', (string) $id)) {
                throw new ImportException('Недопустимый ID в палитре «Цвет».');
            }
        }
        $expected = self::resolve(json_encode(self::INITIAL, JSON_THROW_ON_ERROR), $catalog);
        $actual = array_map('strval', $ids);
        sort($actual, SORT_STRING);
        $expected = array_map('strval', $expected);
        sort($expected, SORT_STRING);
        if ($actual !== $expected) {
            throw new ImportException('Характеристика «Цвет» должна использовать полную утверждённую палитру из 15 цветов без повторов.');
        }
    }

    public static function key(string $name): string
    {
        return str_replace('ё', 'е', mb_strtolower($name));
    }

    public static function assertType(string $name, string $type): void
    {
        if ($name === 'Цвет' && $type !== 'color') {
            throw new ImportException('Характеристика «Цвет» должна иметь тип color.');
        }
    }

    public static function resolve(string $json, array $catalog): array
    {
        try {
            // Decode objects as objects: {"0":"Синий"} must not pass as a list.
            $names = json_decode($json, false, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ImportException('colors: требуется JSON-массив названий цветов.');
        }
        if (! is_array($names) || count($names) < 1 || count($names) > 100) {
            throw new ImportException('colors: требуется от 1 до 100 названий.');
        }
        $index = [];
        foreach ($catalog as $color) {
            $index[self::key($color['name'])][] = (int) $color['id'];
        }
        $seen = $ids = [];
        foreach ($names as $name) {
            if (! is_string($name) || $name === '' || mb_strlen($name) > 50 || trim($name) !== $name || preg_match('/[\x00-\x1f\x7f]/u', $name)) {
                throw new ImportException('colors: недопустимое название цвета.');
            }
            $key = self::key($name);
            if (isset($seen[$key])) {
                throw new ImportException('colors: повторяющийся цвет '.$name.'.');
            }
            $seen[$key] = true;
            if (count($index[$key] ?? []) !== 1) {
                throw new ImportException('colors: неизвестный или неоднозначный цвет '.$name.'.');
            }
            $ids[] = $index[$key][0];
        }
        sort($ids, SORT_NUMERIC);

        return $ids;
    }
}
