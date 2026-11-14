<?php

namespace Tests\Unit;

use App\Services\AttributeImport\ColorConversionPlan;
use App\Services\AttributeImport\ColorPalette;
use App\Services\AttributeImport\ImportException;
use PHPUnit\Framework\TestCase;

final class ColorConversionPlanTest extends TestCase
{
    private function snapshot(): array
    {
        return [
            'attributes' => [
                ['id' => 134, 'name' => 'Цвет', 'type' => 'select', 'unit' => null, 'options' => '["Синий"]', 'is_filterable' => 1],
                ['id' => 263, 'name' => 'Цвет', 'type' => 'text', 'unit' => null, 'options' => null, 'is_filterable' => 0],
            ],
            'colors' => \Tests\Support\ApprovedColorPalette::catalog(),
            'links' => [['id' => 1, 'attribute_id' => 263, 'category_id' => 42, 'sort_order' => 3]],
            'palettes' => [],
            'values' => [
                ['id' => 25, 'attribute_id' => 134, 'product_id' => 1, 'value' => 'Синий', 'archived_at' => '2026-11-12 17:45:25', 'archive_reason' => 'legacy_detached'],
                ['id' => 70, 'attribute_id' => 263, 'product_id' => 21, 'value' => 'ЧЕРНЫЙ', 'archived_at' => null, 'archive_reason' => null],
            ],
        ];
    }

    public function test_plan_preserves_before_images_links_and_archive_metadata_and_is_repeatable(): void
    {
        $snapshot = $this->snapshot();
        $planner = new ColorConversionPlan;
        $plan = $planner->build($snapshot);
        self::assertSame($snapshot, $plan['snapshot']);
        self::assertSame([134, 263], $plan['attribute_ids']);
        self::assertSame([['id' => 25, 'before' => 'Синий', 'after' => '2'], ['id' => 70, 'before' => 'ЧЕРНЫЙ', 'after' => '1']], $plan['value_changes']);
        self::assertSame($plan, $planner->build($snapshot));
        foreach ($snapshot['attributes'] as &$attribute) {
            $attribute['type'] = 'color';
            $attribute['options'] = null;
            $attribute['is_filterable'] = 1;
            foreach ($plan['color_ids'] as $color) {
                $snapshot['palettes'][] = ['attribute_id' => $attribute['id'], 'color_id' => $color];
            }
        }
        unset($attribute);
        $snapshot['values'][0]['value'] = '2';
        $snapshot['values'][1]['value'] = '1';
        $repeat = $planner->build($snapshot);
        self::assertSame([], $repeat['attribute_ids']);
        self::assertSame([], $repeat['value_changes']);
        self::assertSame($plan['snapshot']['links'], $repeat['snapshot']['links']);
        self::assertSame($plan['snapshot']['values'][0]['archived_at'], $repeat['snapshot']['values'][0]['archived_at']);
    }

    public function test_unknown_archived_value_blocks_whole_plan(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['values'][0]['value'] = 'Неизвестный';
        $this->expectException(ImportException::class);
        (new ColorConversionPlan)->build($snapshot);
    }

    public function test_all_seven_ids_and_78_links_survive_with_three_added_colors(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['attributes'] = array_map(fn ($id) => [
            'id' => $id, 'name' => 'Цвет', 'type' => $id === 263 ? 'text' : 'select',
            'unit' => null, 'options' => null, 'is_filterable' => $id !== 263,
        ], [2, 10, 21, 134, 142, 153, 263]);
        $snapshot['links'] = array_map(fn ($id) => ['id' => $id, 'category_id' => $id, 'attribute_id' => 263, 'sort_order' => $id % 6], range(1, 78));
        foreach (['Бежевый', 'Золотой', 'Серебристый'] as $i => $name) {
            $snapshot['values'][] = ['id' => 80 + $i, 'attribute_id' => 134, 'product_id' => 1,
                'value' => $name, 'archived_at' => '2026-11-12 17:45:25', 'archive_reason' => 'legacy_detached'];
        }
        $plan = (new ColorConversionPlan)->build($snapshot);
        self::assertSame([2, 10, 21, 134, 142, 153, 263], $plan['attribute_ids']);
        self::assertSame(range(1, 15), $plan['color_ids']);
        self::assertSame($snapshot, $plan['snapshot']);
        self::assertSame([
            ['id' => 80, 'before' => 'Бежевый', 'after' => '13'],
            ['id' => 81, 'before' => 'Золотой', 'after' => '14'],
            ['id' => 82, 'before' => 'Серебристый', 'after' => '15'],
        ], array_slice($plan['value_changes'], 2));
        self::assertSame(['id' => 25, 'before' => 'Синий', 'after' => '2'], $plan['value_changes'][0]);
        self::assertSame(['id' => 1, 'name' => 'Черный', 'hex' => '#000000'], $plan['snapshot']['colors'][0]);
        self::assertSame(['id' => 2, 'name' => 'Синий', 'hex' => '#0011ff'], $plan['snapshot']['colors'][1]);
    }

    public function test_value_drift_changes_fingerprint(): void
    {
        $snapshot = $this->snapshot();
        $before = (new ColorConversionPlan)->build($snapshot);
        $snapshot['values'][0]['value'] = 'Белый';
        self::assertNotSame($before['fingerprint'], (new ColorConversionPlan)->build($snapshot)['fingerprint']);
    }
}
