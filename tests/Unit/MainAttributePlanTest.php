<?php

namespace Tests\Unit;

use App\Services\AttributeImport\{ColorPalette, ImportException, MainAttributePlan, MainAttributeTransfer, XlsxReader};
use PHPUnit\Framework\TestCase;

final class MainAttributePlanTest extends TestCase
{
    private function snapshot(): array
    {
        return [
            'categories' => [['id' => 41, 'slug' => 'parent', 'parent_id' => null], ['id' => 44, 'slug' => 'leaf', 'parent_id' => 41]],
            'products' => [['id' => 5, 'category_id' => 44]],
            'colors' => [['id' => 81, 'name' => 'Черный', 'hex' => '#000000']],
            'attributes' => [
                ['id' => 134, 'name' => 'Цвет', 'type' => 'select', 'unit' => null, 'is_filterable' => 1, 'options' => '["Синий"]'],
                ['id' => 199, 'name' => 'Бренд', 'type' => 'select', 'unit' => null, 'is_filterable' => 1, 'options' => '["IKEA"]'],
                ['id' => 260, 'name' => 'цвет', 'type' => 'color', 'unit' => null, 'is_filterable' => 1, 'options' => null],
            ],
            'attribute_category' => [
                ['id' => 8, 'category_id' => 44, 'attribute_id' => 134, 'sort_order' => 9],
                ['id' => 9, 'category_id' => 44, 'attribute_id' => 199, 'sort_order' => 7],
                ['id' => 10, 'category_id' => 41, 'attribute_id' => 260, 'sort_order' => 0],
            ],
            'attribute_color' => [['id' => 1, 'attribute_id' => 260, 'color_id' => 81]],
            'attribute_values' => [
                ['id' => 25, 'product_id' => 5, 'attribute_id' => 134, 'value' => 'Синий', 'archived_at' => null, 'archive_reason' => null, 'created_at' => '2025-01-01', 'updated_at' => '2025-01-02'],
                ['id' => 22, 'product_id' => 5, 'attribute_id' => 199, 'value' => 'IKEA', 'archived_at' => null, 'archive_reason' => null],
                ['id' => 36, 'product_id' => 5, 'attribute_id' => 199, 'value' => 'old', 'archived_at' => '2025-01-01', 'archive_reason' => 'explicit_clear'],
            ],
        ];
    }

    private function rows(): array
    {
        return [
            ['category_slug' => 'leaf', 'attribute_name' => 'Цвет', 'type' => 'color', 'unit' => '', 'options' => '', 'colors' => json_encode(ColorPalette::INITIAL), 'is_filterable' => '1', 'sort_order' => '1'],
            ['category_slug' => 'leaf', 'attribute_name' => 'Бренд', 'type' => 'text', 'unit' => '', 'options' => '', 'colors' => '', 'is_filterable' => '0', 'sort_order' => '2'],
        ];
    }

    public function test_preserves_ids_extras_archive_and_dates_and_transfers_active_value(): void
    {
        $s = $this->snapshot();
        $plan = (new MainAttributePlan)->build($s, $this->rows());
        self::assertSame(14, $plan['counts']['colors.insert']);
        self::assertSame(15, $plan['counts']['attribute_color.insert']);
        self::assertSame(1, $plan['counts']['attributes.insert']);
        $expected = $plan['expected'];
        self::assertSame($s['attribute_values'][2], $expected['attribute_values'][2]);
        self::assertSame($s['attributes'][2], $expected['attributes'][2]);
        self::assertSame($s['attribute_category'][2], $expected['attribute_category'][2]);
        self::assertSame($s['colors'][0], $expected['colors'][0]);
        self::assertSame(134, $expected['attribute_values'][0]['attribute_id']);
        self::assertSame('2025-01-02', $expected['attribute_values'][0]['updated_at']);
        self::assertNull($expected['attribute_values'][0]['archived_at']);
        self::assertLessThan(0, $expected['attribute_values'][1]['attribute_id']);
        self::assertSame('IKEA', $expected['attribute_values'][1]['value']);
        self::assertSame($plan, (new MainAttributePlan)->build($s, $this->rows()));
        $s['attribute_values'][1]['value'] = 'Other';
        self::assertNotSame($plan['fingerprint'], (new MainAttributePlan)->build($s, $this->rows())['fingerprint']);
    }

    public function test_repeat_plan_is_empty_after_resolving_real_ids(): void
    {
        $plan = (new MainAttributePlan)->build($this->snapshot(), $this->rows());
        $ids = [];
        foreach ($plan['operations'] as $op) {
            if ($op['action'] === 'insert') { $ids[$op['id']] = 1000 + count($ids); }
        }
        $after = [];
        foreach ($plan['expected'] as $table => $rows) {
            $after[$table] = array_map(fn ($r) => MainAttributeTransfer::resolve($table, $r, $ids), $rows);
        }
        MainAttributeTransfer::verify($plan['expected'], $after, $ids);
        self::assertSame([], (new MainAttributePlan)->build($after, $this->rows())['operations']);
        $after['products'][0]['category_id'] = 41;
        $this->expectException(ImportException::class);
        MainAttributeTransfer::verify($plan['expected'], $after, $ids);
    }

    public function test_negative_number_is_not_a_color_reference(): void
    {
        self::assertSame(['value' => '-1'], MainAttributeTransfer::resolve('attribute_values', ['value' => '-1'], [-1 => 123]));
    }

    public function test_string_database_ids_do_not_create_duplicate_color_definition(): void
    {
        $s = $this->snapshot();
        foreach ($s as &$table) {
            foreach ($table as &$row) {
                foreach ($row as &$value) { if (is_int($value)) { $value = (string) $value; } }
                unset($value);
            }
            unset($row);
        }
        unset($table);
        $plan = (new MainAttributePlan)->build($s, $this->rows());
        self::assertSame(1, $plan['counts']['attributes.insert']);
        self::assertSame('134', $plan['expected']['attribute_values'][0]['attribute_id']);
    }

    public function test_incompatible_active_value_blocks_plan(): void
    {
        $rows = $this->rows(); $rows[1]['type'] = 'number';
        $this->expectException(ImportException::class);
        (new MainAttributePlan)->build($this->snapshot(), $rows);
    }

    public function test_unknown_archived_color_blocks_plan(): void
    {
        $s = $this->snapshot(); $s['attribute_values'][0]['value'] = 'Unknown'; $s['attribute_values'][0]['archived_at'] = '2025-01-01';
        $this->expectException(ImportException::class);
        (new MainAttributePlan)->build($s, $this->rows());
    }

    public function test_normalized_duplicate_color_blocks_plan(): void
    {
        $s = $this->snapshot(); $s['colors'][] = ['id' => 99, 'name' => 'ЧЁРНЫЙ', 'hex' => '#000001'];
        $this->expectException(ImportException::class);
        (new MainAttributePlan)->build($s, $this->rows());
    }

    public function test_incomplete_palette_blocks_plan(): void
    {
        $rows = $this->rows(); $rows[0]['colors'] = '["Синий"]';
        $this->expectException(ImportException::class);
        (new MainAttributePlan)->build($this->snapshot(), $rows);
    }

    public function test_existing_different_hex_blocks_plan(): void
    {
        $s = $this->snapshot(); $s['colors'][0]['hex'] = '#111111';
        $this->expectException(ImportException::class);
        (new MainAttributePlan)->build($s, $this->rows());
    }

    public function test_duplicate_active_target_blocks_plan(): void
    {
        $s = $this->snapshot(); $s['attribute_values'][] = $s['attribute_values'][1];
        $s['attribute_values'][3]['id'] = 999;
        $this->expectException(ImportException::class);
        (new MainAttributePlan)->build($s, $this->rows());
    }

    public function test_checked_in_workbook_is_the_approved_2101_rows(): void
    {
        $path = dirname(__DIR__, 2).'/docs/templates/category-attributes-colors-2026-11-13.xlsx';
        self::assertSame(MainAttributeTransfer::WORKBOOK_SHA256, hash_file('sha256', $path));
        $rows = (new XlsxReader)->read($path);
        self::assertCount(2101, $rows);
        self::assertCount(78, array_filter($rows, fn ($r) => $r['attribute_name'] === 'Цвет'));
    }
}
