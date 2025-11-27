<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CategoryCacheService
{
    /**
     * Полная очистка всех кэшей, связанных с категориями.
     */
    public static function clear(): void
    {
        // Основные ключи
        Cache::forget('categories_tree');
        Cache::forget('categories_full_tree');
        Cache::forget('categories:root');
        Cache::forget('all_categories_tree');

        // Кэш страниц категорий: category_page:{slug}
        if (Cache::getStore() instanceof \Illuminate\Cache\FileStore) {
            // FILE — безопасный вариант: просто удаляем всё starts with category_page
            $files = glob(storage_path('framework/cache/data/*'));
            foreach ($files as $file) {
                if (strpos($file, 'category_page:') !== false) {
                    @unlink($file);
                }
            }
        } else {
            // Redis / Memcached
            foreach (Cache::getMemcached()->getAllKeys() ?? [] as $key) {
                if (str_contains($key, 'category_page:')) {
                    Cache::forget(str_replace('laravel:', '', $key));
                }
            }
        }
    }
}
