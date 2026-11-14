<?php

namespace Tests\Unit;

use App\Services\AttributeDuplicateAudit;
use PHPUnit\Framework\TestCase;

class AttributeDuplicateAuditTest extends TestCase
{
    private function definition(int $id, array $changes = []): array
    {
        return array_replace(['id' => $id, 'name' => 'Размер', 'type' => 'select', 'unit' => null,
            'options' => '["S", "M"]', 'is_filterable' => 1], $changes);
    }

    public function test_identity_preserves_type_units_options_order_and_null(): void
    {
        $original = $this->definition(1);
        self::assertSame(AttributeDuplicateAudit::identity($original), AttributeDuplicateAudit::identity($this->definition(2, ['options' => ['S', 'M']])));
        foreach ([['type' => 'text'], ['unit' => 'см'], ['options' => ['M', 'S']], ['options' => null], ['name' => 'размер']] as $change) {
            self::assertNotSame(AttributeDuplicateAudit::identity($original), AttributeDuplicateAudit::identity($this->definition(2, $change)));
        }
        self::assertNotSame(AttributeDuplicateAudit::identity($this->definition(1, ['options' => null])), AttributeDuplicateAudit::identity($this->definition(2, ['options' => []])));
    }

    public function test_palette_filterability_and_sort_order_conflicts_block_merges(): void
    {
        $audit = new AttributeDuplicateAudit;
        $cases = [
            [[$this->definition(1), $this->definition(2)], [], [['attribute_id' => 1, 'color_id' => 4]], 'different_color_links'],
            [[$this->definition(1), $this->definition(2, ['is_filterable' => 0])], [], [], 'different_filterability'],
            [[$this->definition(1), $this->definition(2)], [
                ['attribute_id' => 1, 'category_id' => 3, 'sort_order' => 0],
                ['attribute_id' => 2, 'category_id' => 3, 'sort_order' => 1],
            ], [], 'different_sort_order'],
        ];
        foreach ($cases as [$definitions, $links, $colors, $reason]) {
            $report = $audit->report($definitions, $links, [], $colors);
            self::assertSame(0, $report['candidate_attribute_removals_after_review']);
            self::assertContains($reason, $report['duplicate_groups'][0]['conflicts']);
        }
    }

    public function test_unused_unique_definition_is_not_a_deletion_candidate(): void
    {
        $report = (new AttributeDuplicateAudit)->report([$this->definition(1), $this->definition(2), $this->definition(3, ['unit' => 'см'])], [], []);
        self::assertSame([1, 2, 3], $report['unused_attribute_ids']);
        self::assertSame(1, $report['candidate_attribute_removals_after_review']);
        self::assertSame([2], $report['duplicate_groups'][0]['candidate_ids']);
    }
}
