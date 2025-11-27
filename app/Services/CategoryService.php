<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    /**
     * 🔹 Минимальное дерево для рекурсий (id → parent_id)
     * Ключ: cat.tree
     */
    public function tree()
    {
        return Cache::remember('cat.tree', 3600, function () {
            return Category::select('id', 'parent_id')
                ->get()
                ->groupBy('parent_id');
        });
    }

    /**
     * 🔹 Полное дерево категорий (для меню / форм / витрины)
     * Ключ: cat.full
     */
    public function fullTree()
    {
        return Cache::remember('cat.full', 3600, function () {
            return Category::query()
                ->select('id', 'name', 'slug', 'parent_id', 'icon', 'image')
                ->with([
                    'children' => function ($q) {
                        $q->select('id', 'name', 'slug', 'parent_id', 'icon', 'image')
                          ->orderBy('name');
                    },
                    'children.children' => function ($q) {
                        $q->select('id', 'name', 'slug', 'parent_id', 'icon', 'image')
                          ->orderBy('name');
                    },
                ])
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * 🔹 Корневые категории (страница "Все категории")
     * Ключ: cat.root
     */
    public function root()
    {
        return Cache::remember('cat.root', 3600, function () {
            return Category::query()
                ->whereNull('parent_id')
                ->select('id', 'name', 'slug', 'icon', 'image', 'parent_id')
                ->with([
                    'children' => function ($q) {
                        $q->select('id', 'name', 'slug', 'icon', 'image', 'parent_id')
                          ->orderBy('name');
                    },
                ])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * 🔹 Страница конкретной категории (без товаров!)
     * Ключ: cat.page.{slug}
     */
    public function getBySlug(string $slug): Category
    {
        $cacheKey = "cat.page.{$slug}";

        return Cache::remember($cacheKey, 3600, function () use ($slug) {
            return Category::query()
                ->select('id', 'name', 'slug', 'parent_id', 'icon', 'image')
                ->with([
                    'children:id,name,slug,parent_id,icon,image',
                    'parent:id,name,slug,parent_id',
                ])
                ->where('slug', $slug)
                ->firstOrFail();
        });
    }

    /**
     * 🔥 Полный сброс кеша категорий
     */
    public function clearAllCache(): void
    {
        Cache::forget('cat.tree');
        Cache::forget('cat.full');
        Cache::forget('cat.root');
        // cat.page.* сбрасываются точечно в модели Category
    }

    /**
     * 🔥 Сброс кеша для конкретной страницы категории
     */
    public function clearPageBySlug(?string $slug): void
    {
        if ($slug) {
            Cache::forget("cat.page.{$slug}");
        }
    }
}
