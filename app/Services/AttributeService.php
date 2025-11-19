<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class AttributeService
{
    /**
     * 📥 Получить атрибуты категории (с options)
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
            ->orderBy('name')
            ->get()
            ->map(function ($attr) {

                // Приведение options к массиву
                if (is_array($attr->options)) {
                    // ок
                } elseif (is_string($attr->options) && $attr->options !== '') {
                    $attr->options = json_decode($attr->options, true) ?? [];
                } else {
                    $attr->options = [];
                }

                return $attr;
            });
    }

    /**
     * 📥 Получить атрибуты категории + значения товара
     */
    public function getForProduct(Product $product)
    {
        $attrs = $this->getByCategory($product->category_id);

        // Подгружаем значения, если нужно
        $product->loadMissing('attributeValues');

        return $attrs->map(function ($attr) use ($product) {
            $attr->value = optional(
                $product->attributeValues->firstWhere('attribute_id', $attr->id)
            )->value;

            return $attr;
        });
    }

    /**
     * 🔄 Синхронизировать атрибуты товара
     */
    public function sync(Product $product, array $values): void
    {
        // Полное удаление старых
        DB::table('attribute_values')
            ->where('product_id', $product->id)
            ->delete();

        if (empty($values)) {
            return;
        }

        $rows = [];

        foreach ($values as $attrId => $value) {

            // multiple: []
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

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

        if ($rows) {
            DB::table('attribute_values')->insert($rows);
        }
    }
}
