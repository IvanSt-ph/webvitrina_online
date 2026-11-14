<?php

namespace Tests\Unit;

use App\Services\AttributeImport\ColorPalette;
use App\Services\AttributeImport\ImportException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ColorPaletteTest extends TestCase
{
    public function test_approved_palette_is_exactly_the_independent_fifteen_names(): void
    {
        self::assertSame(\Tests\Support\ApprovedColorPalette::NAMES, ColorPalette::INITIAL);
        self::assertSame(range(1, 15), ColorPalette::resolve(json_encode(\Tests\Support\ApprovedColorPalette::NAMES), \Tests\Support\ApprovedColorPalette::catalog()));
    }

    public function test_exact_color_accepts_complete_palette_in_any_order(): void
    {
        ColorPalette::assertSelection('Цвет', 'color', array_reverse(range(1, 15)), \Tests\Support\ApprovedColorPalette::catalog());
        self::assertTrue(true);
    }

    #[DataProvider('invalidSelections')]
    public function test_exact_color_rejects_incomplete_extra_duplicate_or_invalid_ids(array $ids): void
    {
        $this->expectException(ImportException::class);
        ColorPalette::assertSelection('Цвет', 'color', $ids, \Tests\Support\ApprovedColorPalette::catalog());
    }

    public static function invalidSelections(): array
    {
        return [[[]], [range(1, 12)], [range(1, 14)], [range(1, 16)], [[...range(1, 15), 1]], [[[], ...range(2, 15)]]];
    }

    public function test_other_names_do_not_require_the_standard_palette(): void
    {
        ColorPalette::assertSelection('Цвет корпуса', 'color', [2], [['id' => 2, 'name' => 'Синий']]);
        ColorPalette::assertSelection('Цвет свечения', 'color', [1], [['id' => 1, 'name' => 'Тёплый']]);
        self::assertTrue(true);
    }

    public function test_names_resolve_to_sorted_ids_with_case_and_yo_normalization(): void
    {
        self::assertSame([2, 9], ColorPalette::resolve('["ЧЁРНЫЙ","синий"]', [
            ['id' => 9, 'name' => 'Черный'], ['id' => 2, 'name' => 'Синий'],
        ]));
    }

    #[DataProvider('invalidPalettes')]
    public function test_invalid_palettes_are_rejected(string $json, array $catalog = []): void
    {
        $this->expectException(ImportException::class);
        ColorPalette::resolve($json, $catalog);
    }

    public static function invalidPalettes(): array
    {
        return [
            [''], ['not json'], ['null'], ['{}'], ['{"0":"Синий"}'], ['[]'], ['[1]'], ['[" Синий"]'],
            ['["Синий"]'], ['["Синий","СИНИЙ"]', [['id' => 1, 'name' => 'Синий']]],
            ['["Чёрный","Черный"]', [['id' => 1, 'name' => 'Черный']]],
            ['["Черный"]', [['id' => 1, 'name' => 'Чёрный'], ['id' => 2, 'name' => 'Черный']]],
            [json_encode(array_fill(0, 101, 'Синий'))],
        ];
    }

    public function test_rule_is_exact_and_does_not_change_other_names(): void
    {
        ColorPalette::assertType('Цвет корпуса', 'text');
        ColorPalette::assertType('Цвет свечения', 'select');
        ColorPalette::assertType('Цвет', 'color');
        $this->expectException(ImportException::class);
        ColorPalette::assertType('Цвет', 'text');
    }
}
