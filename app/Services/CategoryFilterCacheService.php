<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryFilterCacheService
{
    /**
     * 🔥 Получить фильтры категории (атрибуты is_filterable=1)
     * Ключ: cat.filters.{id}
     */
    public static function getFilters(Category $category)
    {
        $key = "cat.filters.{$category->id}";

        return Cache::remember($key, 3600, function () use ($category) {

            // IDs категории + всех потомков
            $allIds = $category->allChildrenIds();

            // Запрос атрибутов
            return Attribute::query()
                ->select('attributes.id', 'name', 'type', 'unit', 'options', 'is_filterable')
                ->join('attribute_category', 'attributes.id', '=', 'attribute_category.attribute_id')
                ->where('attributes.is_filterable', 1)
                ->whereIn('attribute_category.category_id', $allIds)
                ->groupBy('attributes.id', 'name', 'type', 'unit', 'options', 'is_filterable')
                ->orderBy('name')
                ->get()
                ->map(function ($attr) {

                    // Приводим options
                    if (is_string($attr->options)) {
                        $decoded = json_decode($attr->options, true);
                        $attr->options = is_array($decoded) ? $decoded : [];
                    }

                    return $attr;
                });
        });
    }

    /**
     * 🔥 Сбросить фильтры конкретной категории
     */
    public static function clearFor(Category $category)
    {
        Cache::forget("cat.filters.{$category->id}");
    }

    /**
     * 🔥 Полная очистка всех фильтров категорий
     */
    public static function clearAll()
    {
        // При file-store стираем физические файлы
        if (Cache::getStore() instanceof \Illuminate\Cache\FileStore) {
            foreach (glob(storage_path('framework/cache/data/*')) as $file) {
                if (str_contains($file, 'cat.filters.')) {
                    @unlink($file);
                }
            }
        } else {
            // Redis / Memcached
            try {
                foreach (Cache::getMemcached()->getAllKeys() ?? [] as $key) {
                    if (str_contains($key, 'cat.filters.')) {
                        Cache::forget(str_replace('laravel:', '', $key));
                    }
                }
            } catch (\Throwable $e) {
                // На всякий случай
            }
        }
    }
}
