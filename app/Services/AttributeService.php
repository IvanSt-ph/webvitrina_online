<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\Color;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttributeService
{
    public const MAX_VALUE_LENGTH = 255;

    /** Validate against the destination category, not the browser's controls. */
    public function validateValues(mixed $values, ?int $categoryId): array
    {
        if ($values === null || $values === []) {
            return [];
        }
        if (! is_array($values)) {
            throw ValidationException::withMessages(['attributes' => 'Характеристики должны быть массивом.']);
        }

        $allowed = $this->getByCategory($categoryId)->keyBy('id');
        $errors = [];
        $validated = [];
        foreach ($values as $id => $value) {
            if (! preg_match('/^[1-9][0-9]*$/D', (string) $id) || ! $allowed->has($id)) {
                $errors['attributes'][] = 'Характеристика не принадлежит выбранной категории.';
                continue;
            }
            $attribute = $allowed->get($id);
            // Empty optional controls are retained so an edit can clear values.
            if ($value === null || $value === '') {
                $validated[$id] = $value;
                continue;
            }
            $valid = is_scalar($value) && mb_strlen((string) $value) <= self::MAX_VALUE_LENGTH;
            $valid = $valid && match ($attribute->type) {
                'text', 'string' => is_string($value),
                'number' => ! is_bool($value) && is_numeric($value) && is_finite((float) $value),
                'boolean' => in_array($value, [true, false, 0, 1, '0', '1'], true),
                'select' => ! is_bool($value) && in_array((string) $value, array_map('strval', $attribute->options ?? []), true),
                'color' => ! is_bool($value) && in_array((string) $value, $attribute->colors->pluck('id')->map(fn ($id) => (string) $id)->all(), true),
                default => false,
            };
            if (! $valid) {
                $errors['attributes.' . $id] = 'Недопустимое значение характеристики (максимум 255 символов).';
                continue;
            }
            $validated[$id] = is_bool($value) ? (string) (int) $value : (string) $value;
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    /**
     * 📥 Получить атрибуты категории (с options + colors)
     */
public function getByCategory(?int $categoryId)
{
    // Если категории нет — возвращаем пустые атрибуты
    if (!$categoryId) {
        return collect();
    }

    return Attribute::query()
        ->select('id', 'name', 'type', 'options')
        ->whereIn('id', function ($q) use ($categoryId) {
            $q->select('attribute_id')
                ->from('attribute_category')
                ->where('category_id', $categoryId);
        })
        ->with('colors:id,name,hex')
        ->orderBy('name')
        ->get()
        ->map(function ($attr) {

            // Приведение обычных options (для select)
            if ($attr->type !== 'color') {
                if (is_array($attr->options)) {
                    // ок
                } elseif (is_string($attr->options) && $attr->options !== '') {
                    $attr->options = json_decode($attr->options, true) ?? [];
                } else {
                    $attr->options = [];
                }
            } else {
                // color — options пустые
                $attr->options = [];
            }

            return $attr;
        });
}


    /**
     * 📥 Атрибуты + значения товара
     */
public function getForProduct(Product $product)
{
    if (!$product->category_id) {
        return collect();
    }

    $attrs = $this->getByCategory($product->category_id);

    $product->loadMissing('attributeValues');

    return $attrs->map(function ($attr) use ($product) {
        $attr->value = optional(
            $product->attributeValues->firstWhere('attribute_id', $attr->id)
        )->value;

        return $attr;
    });
}


    /**
     * 🔄 Синхронизировать значения атрибутов товара
     */
    public function sync(Product $product, array $values): void
    {
        // Guard direct callers as well, before deleting any existing values.
        $values = $this->validateValues($values, $product->category_id);
        DB::table('attribute_values')
            ->where('product_id', $product->id)
            ->delete();

        if (empty($values)) {
            return;
        }

        $rows = [];

        foreach ($values as $attrId => $value) {

            // color id приходит строкой ("3"), делаем int
            if ($value === null || $value === '') {
                continue;
            }

            $rows[] = [
                'product_id'   => $product->id,
                'attribute_id' => (int)$attrId,
                'value'        => (string)$value,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        if (!empty($rows)) {
            DB::table('attribute_values')->insert($rows);
        }
    }
}
