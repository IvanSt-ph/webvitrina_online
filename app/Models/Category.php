<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'icon',
        'image',
    ];

    /* ============================================================
     | 🔗 СВЯЗИ
     ============================================================ */

    /** Родитель */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** Прямые дети */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /** Товары этой категории */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /** Атрибуты */
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'attribute_category');
    }

    /* ============================================================
     | 🔥 КЭШ: СТРУКТУРА ДЛЯ РЕКУРСИЙ (быстрое дерево)
     ============================================================ */

    /**
     * Минимальная структура дерева (id → parent_id), используется
     * для рекурсий и allChildrenIds().
     *
     * [
     *   parent_id => [ Category, Category ]
     * ]
     */
    public static function tree()
    {
        return Cache::remember('categories_tree', 3600, function () {
            return self::select('id', 'parent_id')->get()->groupBy('parent_id');
        });
    }

    /**
     * Возвращает ID текущей категории + всех потомков.
     * Работает мгновенно, без SQL.
     */
    public function allChildrenIds()
    {
        $tree  = self::tree();
        $ids   = collect([$this->id]);
        $stack = [$this->id];

        while (!empty($stack)) {
            $current = array_pop($stack);

            if (!empty($tree[$current])) {
                foreach ($tree[$current] as $child) {
                    $ids->push($child->id);
                    $stack[] = $child->id;
                }
            }
        }

        return $ids->unique();
    }

    /* ============================================================
     | 🔥 КЭШ: ПОЛНОЕ ДЕРЕВО ДЛЯ МЕНЮ / ВИТРИНЫ / ФОРМ
     ============================================================ */

    /**
     * Полное дерево категорий с детьми (до 3 уровней).
     * Используется в:
     * - меню
     * - фильтрах
     * - формах продавца
     * - админке
     */
    public static function fullTree()
    {
        return Cache::remember('categories_full_tree', 3600, function () {
            return self::with([
                'children.children.children'
            ])
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();
        });
    }

    /* ============================================================
     | 🔧 УТИЛИТЫ
     ============================================================ */

    /** Соседние категории */
    public function siblings()
    {
        return self::where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    /** Все товары текущей + дочерних категорий */
    public function allProducts()
    {
        return Product::whereIn('category_id', $this->allChildrenIds());
    }

    /* ============================================================
     | 🖼 АКСЕССОРЫ
     ============================================================ */

    public function getIconUrlAttribute()
    {
        if (!$this->icon) {
            return asset('images/categories/default.png');
        }

        if (str_contains($this->icon, '/')) {
            return asset('storage/' . $this->icon);
        }

        return asset('storage/categories/icons/' . $this->icon);
    }

    /* ============================================================
     | ⚙️ ХУКИ МОДЕЛИ (генерация slug + очистка кеша)
     ============================================================ */

    protected static function boot()
    {
        parent::boot();

        /** Генерация slug */
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $slug = Str::slug($category->name);
                $base = $slug;
                $i = 1;

                while (self::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }

                $category->slug = $slug;
            }
        });

        /** Очищаем кеш после добавления/обновления/удаления */
        static::saved(function () {
            Cache::forget('categories_tree');
            Cache::forget('categories_full_tree');
        });

        static::deleted(function () {
            Cache::forget('categories_tree');
            Cache::forget('categories_full_tree');
        });
    }
}
