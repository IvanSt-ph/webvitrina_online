<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\Color;
use Illuminate\Support\Facades\DB;

class AttributeService
{
    /**
     * 📥 Получить атрибуты категории (с options + colors)
     */
    public function getByCategory(int $categoryId)
    {
        return Attribute::query()
            ->select('id', 'name', 'type', 'options')
            ->whereIn('id', function ($q) use ($categoryId) {
                $q->select('attribute_id')
                    ->from('attribute_category')
                    ->where('category_id', $categoryId);
            })
            ->with('colors:id,name,hex') // важное добавление
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
                    // Для color options НЕ используются
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

            // массив (для мульти выбора)
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
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
