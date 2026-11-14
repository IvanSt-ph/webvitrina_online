<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Category;

final class AttributeDefinitionService
{
    /** All cooperating definition writers acquire the same row before category locks. */
    public static function lock(): void
    {
        Category::orderBy('id')->lockForUpdate()->first();
    }

    public static function findOrCreate(array $data, array $colors = []): Attribute
    {
        \App\Services\AttributeImport\ColorPalette::assertType($data['name'], $data['type']);
        self::lock();
        if ($data['name'] === 'Цвет') {
            \App\Services\AttributeImport\ColorPalette::assertSelection($data['name'], $data['type'], $colors,
                \Illuminate\Support\Facades\DB::table('colors')->orderBy('id')->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all());
        }
        $options = $data['options'] ?? null;
        $query = Attribute::whereRaw('BINARY name = BINARY ?', [$data['name']])->where('type', $data['type'])
            ->where('unit', $data['unit'] ?? null)->where('is_filterable', $data['is_filterable']);
        if (($data['unit'] ?? null) !== null) {
            $query->whereRaw('BINARY unit = BINARY ?', [$data['unit']]);
        }
        $options === null ? $query->whereNull('options')
            : $query->whereRaw('options = CAST(? AS JSON)', [json_encode($options, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
        $colors = array_values(array_unique(array_map('intval', $colors)));
        sort($colors);
        foreach ($query->with('colors')->orderBy('id')->get() as $candidate) {
            if ($candidate->colors->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all() === $colors) {
                return $candidate;
            }
        }
        $attribute = Attribute::create($data);
        if ($colors) {
            $attribute->colors()->sync($colors);
        }

        return $attribute;
    }
}
