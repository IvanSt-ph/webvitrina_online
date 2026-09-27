<?php

namespace App\Support;

use App\Http\Middleware\NormalizeCatalogInput;
use App\Models\Category;
use App\Services\CategoryFilterCacheService;
use Illuminate\Http\Request;

class CatalogAttributeFilters
{
    public static function normalize(Request $request, Category $category): void
    {
        $allowed = CategoryFilterCacheService::getFilters($category)->keyBy('id');
        $clean = [];
        foreach ($request->query('filters', []) as $key => $value) {
            $id = NormalizeCatalogInput::positiveInteger($key);
            $attribute = $id === null ? null : $allowed->get($id);
            if (! $attribute) {
                continue;
            }
            if ($attribute->type === 'number') {
                if (! is_array($value) || array_diff(array_keys($value), ['from', 'to'])) {
                    continue;
                }
                $range = [];
                foreach ($value as $bound => $number) {
                    if ($number === null || $number === '') {
                        continue;
                    }
                    if (! is_scalar($number) || ! preg_match('/^-?\d{1,12}(?:\.\d{1,6})?$/D', (string) $number)) {
                        continue 2;
                    }
                    $range[$bound] = (string) $number;
                }
                if (isset($range['from'], $range['to']) && (float) $range['from'] > (float) $range['to']) {
                    continue;
                }
                if ($range) {
                    $clean[$id] = $range;
                }
                continue;
            }
            $values = is_array($value) ? $value : [$value];
            if (! array_is_list($values) || count($values) > 50) {
                continue;
            }
            $options = match ($attribute->type) {
                'select', 'checkbox' => array_map('strval', $attribute->options ?? []),
                'color' => $attribute->colors->pluck('id')->map(fn ($id) => (string) $id)->all(),
                default => null,
            };
            foreach ($values as $item) {
                if (! is_string($item) || $item === '' || mb_strlen($item) > 255) {
                    continue;
                }
                if ($options !== null && ! in_array($item, $options, true)) {
                    continue;
                }
                $clean[$id][] = $item;
            }
        }
        $request->query->set('filters', $clean);
    }
}
