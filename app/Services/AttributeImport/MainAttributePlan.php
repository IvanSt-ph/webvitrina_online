<?php

namespace App\Services\AttributeImport;

/** Pure, deterministic planner. Negative IDs are references, never database IDs. */
final class MainAttributePlan
{
    public const HEX = ['#FFFFFF', '#000000', '#808080', '#FF0000', '#0011FF', '#00BFFF', '#008000', '#FFFF00', '#FFA500', '#FFC0CB', '#800080', '#8B4513', '#F5F5DC', '#FFD700', '#C0C0C0'];

    private array $state;
    private array $ops;
    private int $next;

    public function build(array $snapshot, array $rows): array
    {
        $this->state = $snapshot;
        $this->ops = [];
        $this->next = -1;
        $colorIds = [];
        $names = $hexes = [];
        foreach ($snapshot['colors'] as $color) {
            $key = ColorPalette::key($color['name']);
            $hex = strtoupper($color['hex']);
            if (isset($names[$key]) || isset($hexes[$hex])) {
                throw new ImportException('Duplicate color name or HEX.');
            }
            $names[$key] = $color;
            $hexes[$hex] = true;
        }
        foreach (ColorPalette::INITIAL as $i => $name) {
            $existing = $names[ColorPalette::key($name)] ?? null;
            if ($existing && strtoupper($existing['hex']) !== self::HEX[$i]) {
                throw new ImportException('Existing palette HEX differs: '.$name);
            }
            if (! $existing && isset($hexes[self::HEX[$i]])) {
                throw new ImportException('Palette HEX belongs to another name.');
            }
            $colorIds[] = $existing ? (int) $existing['id'] : $this->insert('colors', ['name' => $name, 'hex' => self::HEX[$i]]);
        }
        sort($colorIds, SORT_NUMERIC);
        $oldAttributes = array_column($snapshot['attributes'], null, 'id');
        foreach ($snapshot['attributes'] as $attribute) {
            if ($attribute['name'] !== 'Цвет') {
                continue;
            }
            if (! in_array($attribute['type'], ['text', 'select', 'color'], true)) {
                throw new ImportException('Unsupported original color type.');
            }
            $this->update('attributes', $attribute['id'], ['type' => 'color', 'unit' => null, 'options' => null, 'is_filterable' => 1]);
            $existing = $this->palette($attribute['id']);
            if (array_diff($existing, $colorIds)) {
                throw new ImportException('Unexpected existing palette; no destructive sync allowed.');
            }
            foreach (array_diff($colorIds, $existing) as $id) {
                $this->insert('attribute_color', ['attribute_id' => $attribute['id'], 'color_id' => $id]);
            }
        }
        $catalog = array_column($this->state['colors'], 'id', 'name');
        $colorByKey = [];
        foreach ($catalog as $name => $id) {
            $colorByKey[ColorPalette::key($name)] = $id;
        }
        foreach ($snapshot['attribute_values'] as $value) {
            if (str_starts_with((string) $value['value'], '@color:')) {
                throw new ImportException('Reserved reference syntax in existing value.');
            }
            $attribute = $oldAttributes[$value['attribute_id']] ?? throw new ImportException('Orphan attribute value.');
            if ($attribute['name'] !== 'Цвет') {
                continue;
            }
            $id = $attribute['type'] === 'color' ? $value['value'] : ($colorByKey[ColorPalette::key($value['value'])] ?? null);
            if ($id === null || ! in_array((string) $id, array_map('strval', $colorIds), true)) {
                throw new ImportException('Unknown color in value #'.$value['id']);
            }
            $this->update('attribute_values', $value['id'], ['value' => (int) $id < 0 ? '@color:'.$id : (string) $id]);
        }
        $categories = [];
        $parents = array_filter(array_column($snapshot['categories'], 'parent_id'));
        foreach ($snapshot['categories'] as $category) {
            if (isset($categories[$category['slug']])) {
                throw new ImportException('Ambiguous category slug.');
            }
            $categories[$category['slug']] = $category;
        }
        $seen = [];
        foreach ($rows as $row) {
            $category = $categories[$row['category_slug']] ?? throw new ImportException('Missing category.');
            if (in_array($category['id'], $parents)) {
                throw new ImportException('Workbook category must be a leaf.');
            }
            $key = $category['id'].'/'.self::nameKey($row['attribute_name']);
            if (isset($seen[$key])) {
                throw new ImportException('Duplicate workbook category/name.');
            }
            $seen[$key] = true;
            $data = $this->definition($row);
            $palette = $data['type'] === 'color' ? $colorIds : [];
            $signature = self::signature($data, $palette);
            $attrs = array_column($this->state['attributes'], null, 'id');
            $attached = array_values(array_filter($this->state['attribute_category'], fn ($link) => $link['category_id'] == $category['id'] && self::nameKey($attrs[$link['attribute_id']]['name']) === self::nameKey($data['name'])));
            if (count($attached) > 1) {
                throw new ImportException('Multiple attached definitions with the same name.');
            }
            $oldLink = $attached[0] ?? null;
            if ($oldLink && (int) $oldLink['attribute_id'] === 260) {
                throw new ImportException('Protected attribute #260 would be changed.');
            }
            $target = null;
            foreach ($this->state['attributes'] as $a) {
                if (self::signature($a, $this->palette($a['id'])) === $signature) {
                    $target ??= $a['id'];
                    if ($oldLink && $oldLink['attribute_id'] == $a['id']) {
                        $target = $a['id'];
                        break;
                    }
                }
            }
            if ($target === null) {
                $target = $this->insert('attributes', $data);
                foreach ($palette as $color) {
                    $this->insert('attribute_color', ['attribute_id' => $target, 'color_id' => $color]);
                }
            }
            if ($oldLink) {
                $this->update('attribute_category', $oldLink['id'], ['attribute_id' => $target, 'sort_order' => (int) $row['sort_order']]);
                if ($oldLink['attribute_id'] != $target) {
                    $products = array_column($snapshot['products'], null, 'id');
                    foreach ($this->state['attribute_values'] as $v) {
                        if ($v['attribute_id'] != $oldLink['attribute_id'] || $v['archived_at'] !== null || ($products[$v['product_id']]['category_id'] ?? null) != $category['id']) {
                            continue;
                        }
                        if (! self::compatible($v['value'], $data, $palette)) {
                            throw new ImportException('Incompatible value #'.$v['id']);
                        }
                        $this->update('attribute_values', $v['id'], ['attribute_id' => $target]);
                    }
                }
            } else {
                $this->insert('attribute_category', ['category_id' => $category['id'], 'attribute_id' => $target, 'sort_order' => (int) $row['sort_order']]);
            }
        }
        $active = [];
        foreach ($this->state['attribute_values'] as $v) {
            if ($v['archived_at'] !== null) {
                continue;
            }
            $key = $v['product_id'].'/'.$v['attribute_id'];
            if (isset($active[$key])) {
                throw new ImportException('Duplicate active product value after transfer.');
            }
            $active[$key] = true;
        }
        $counts = [];
        foreach ($this->ops as $op) {
            $key = $op['table'].'.'.$op['action'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        return ['operations' => $this->ops, 'counts' => $counts, 'expected' => $this->state,
            'fingerprint' => hash('sha256', json_encode([1, $snapshot, $rows, $this->ops], JSON_THROW_ON_ERROR))];
    }

    private function definition(array $row): array
    {
        ColorPalette::assertType($row['attribute_name'], $row['type']);
        if (! in_array($row['type'], ['text', 'number', 'select', 'color'], true)
            || ! in_array($row['is_filterable'], ['0', '1'], true)
            || ! preg_match('/^(0|[1-9][0-9]{0,9})$/D', $row['sort_order']) || (float) $row['sort_order'] > 4294967295) {
            throw new ImportException('Invalid workbook definition.');
        }
        foreach (['attribute_name' => 255, 'unit' => 50] as $field => $max) {
            if (mb_strlen($row[$field]) > $max || trim($row[$field]) !== $row[$field] || preg_match('/[\x00-\x1f\x7f]/u', $row[$field])) {
                throw new ImportException('Invalid workbook text.');
            }
        }
        if ($row['attribute_name'] === '') {
            throw new ImportException('Empty attribute name.');
        }
        if ($row['type'] === 'color') {
            // Only the approved exact-name color rows belong to this transfer.
            if ($row['attribute_name'] !== 'Цвет' || $row['options'] !== '' || $row['unit'] !== '' || $row['is_filterable'] !== '1') {
                throw new ImportException('Unexpected color definition.');
            }
            $names = json_decode($row['colors'], true, 16, JSON_THROW_ON_ERROR);
            if (! is_array($names) || ! array_is_list($names) || count($names) !== 15 || count(array_filter($names, 'is_string')) !== 15) {
                throw new ImportException('Invalid palette.');
            }
            $keys = array_map([ColorPalette::class, 'key'], $names);
            $expected = array_map([ColorPalette::class, 'key'], ColorPalette::INITIAL);
            sort($keys); sort($expected);
            if ($keys !== $expected) {
                throw new ImportException('Incomplete palette.');
            }
        } elseif (($row['colors'] ?? '') !== '') {
            throw new ImportException('Colors on non-color definition.');
        }
        $options = $row['options'] === '' ? null : json_decode($row['options'], true, 16, JSON_THROW_ON_ERROR);
        if ($row['type'] === 'select') {
            if (! is_array($options) || ! array_is_list($options) || count($options) < 1 || count($options) > 100) {
                throw new ImportException('Invalid options.');
            }
            $keys = [];
            foreach ($options as $option) {
                if (! is_string($option) || $option === '' || trim($option) !== $option || mb_strlen($option) > 255 || preg_match('/[\x00-\x1f\x7f]/u', $option)) {
                    throw new ImportException('Invalid option.');
                }
                $key = self::nameKey($option);
                if (isset($keys[$key])) { throw new ImportException('Duplicate option.'); }
                $keys[$key] = true;
            }
        } elseif ($row['options'] !== '') {
            throw new ImportException('Options on non-select definition.');
        }
        return ['name' => $row['attribute_name'], 'type' => $row['type'], 'unit' => $row['unit'] === '' ? null : $row['unit'], 'is_filterable' => (int) $row['is_filterable'], 'options' => $options === null ? null : json_encode($options, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
    }

    private static function nameKey(string $name): string { return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name))); }

    private static function signature(array $a, array $palette): string
    {
        sort($palette, SORT_NUMERIC);
        return json_encode([$a['name'], $a['type'], $a['unit'], (bool) $a['is_filterable'], is_string($a['options']) ? json_decode($a['options'], true, 16, JSON_THROW_ON_ERROR) : $a['options'], $palette], JSON_THROW_ON_ERROR);
    }

    private function palette(int $id): array
    {
        $ids = array_map('intval', array_column(array_filter($this->state['attribute_color'], fn ($p) => $p['attribute_id'] == $id), 'color_id'));
        if (count(array_unique($ids)) !== count($ids)) { throw new ImportException('Duplicate palette links.'); }
        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    private static function compatible(string $value, array $data, array $palette): bool
    {
        return mb_strlen($value) <= 255 && match ($data['type']) {
            'text' => true,
            'number' => is_numeric($value) && is_finite((float) $value),
            'select' => in_array($value, json_decode($data['options'], true), true),
            'color' => in_array($value, array_map(fn ($id) => $id < 0 ? '@color:'.$id : (string) $id, $palette), true),
            default => false,
        };
    }

    private function insert(string $table, array $data): int
    {
        $id = $this->next--;
        $this->ops[] = ['action' => 'insert', 'table' => $table, 'id' => $id, 'data' => $data];
        $this->state[$table][] = ['id' => $id] + $data;
        return $id;
    }

    private function update(string $table, int $id, array $data): void
    {
        foreach ($this->state[$table] as &$row) {
            if ($row['id'] != $id) { continue; }
            $patch = array_filter($data, fn ($value, $key) => $value !== $row[$key] && ! (is_int($value) && (string) $value === (string) $row[$key]), ARRAY_FILTER_USE_BOTH);
            if ($patch) {
                $this->ops[] = ['action' => 'update', 'table' => $table, 'id' => $id, 'before' => $row, 'data' => $patch];
                $row = array_replace($row, $patch);
            }
            return;
        }
        throw new ImportException('Missing update target.');
    }
}
