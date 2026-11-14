<?php

namespace App\Services\AttributeImport;

final class ColorConversionPlan
{
    /** Pure planner: includes archived values, never detaches or reactivates anything. */
    public function build(array $snapshot): array
    {
        $palette = ColorPalette::resolve(json_encode(ColorPalette::INITIAL, JSON_THROW_ON_ERROR), $snapshot['colors']);
        $attributes = [];
        $changes = [];
        foreach ($snapshot['attributes'] as $attribute) {
            if ($attribute['name'] !== 'Цвет') {
                continue;
            }
            if (! in_array($attribute['type'], ['text', 'select', 'color'], true)) {
                throw new ImportException('Неподдерживаемый исходный тип у #'.$attribute['id']);
            }
            $id = (int) $attribute['id'];
            $attributes[$id] = $attribute;
            $existing = array_values(array_map(fn ($row) => (int) $row['color_id'], array_filter($snapshot['palettes'], fn ($row) => (int) $row['attribute_id'] === $id)));
            sort($existing, SORT_NUMERIC);
            if ($attribute['type'] !== 'color' || $attribute['options'] !== null || $attribute['unit'] !== null || ! $attribute['is_filterable'] || $existing !== $palette) {
                $changes[] = $id;
            }
        }
        $values = [];
        foreach ($snapshot['values'] as $value) {
            $attribute = $attributes[$value['attribute_id']] ?? null;
            if (! $attribute) {
                continue;
            }
            if ($attribute['type'] === 'color') {
                if (! in_array((string) $value['value'], array_map('strval', $palette), true)) {
                    throw new ImportException('Недопустимый ID цвета в значении #'.$value['id']);
                }
                continue;
            }
            try {
                $resolved = ColorPalette::resolve(json_encode([(string) $value['value']], JSON_THROW_ON_ERROR), $snapshot['colors'])[0];
            } catch (ImportException $exception) {
                throw new ImportException('Значение #'.$value['id'].': '.$exception->getMessage());
            }
            if (! in_array($resolved, $palette, true)) {
                throw new ImportException('Значение #'.$value['id'].' вне начальной палитры.');
            }
            $values[] = ['id' => (int) $value['id'], 'before' => $value['value'], 'after' => (string) $resolved];
        }
        $plan = ['version' => 1, 'snapshot' => $snapshot, 'attribute_ids' => $changes, 'color_ids' => $palette, 'value_changes' => $values];
        $plan['fingerprint'] = hash('sha256', json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $plan;
    }
}
