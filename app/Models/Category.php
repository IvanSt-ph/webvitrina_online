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

    /* -------------------------------------------------
     | 🔹 СВЯЗИ
     |--------------------------------------------------*/

    /** Родитель (с безопасным fallback) */
public function parent()
{
    return $this->belongsTo(Category::class, 'parent_id');
}


    /** Прямые дети */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /** Товары в этой категории */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /** Атрибуты, привязанные к категории */
    public function attributes()
    {
        return $this->belongsToMany(\App\Models\Attribute::class, 'attribute_category');
    }


    /* -------------------------------------------------
     | 🔥 КЭШИРОВАННОЕ ДЕРЕВО ДЛЯ СУПЕР-БЫСТРОЙ РЕКУРСИИ
     |--------------------------------------------------*/

    /**
     * Получить сгруппированное дерево всех категорий:
     * [
     *   parent_id => [ Category, Category ],
     * ]
     */
    public static function tree()
    {
        return Cache::remember('categories_tree', 3600, function () {
            return self::select('id', 'parent_id')->get()->groupBy('parent_id');
        });
    }

    /**
     * Очень быстрая рекурсия без лишних SQL.
     * Возвращает ВСЕ ID: текущей + всех потомков.
     */
    public function allChildrenIds()
    {
        $tree = self::tree();
        $ids  = collect([$this->id]);
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


    /* -------------------------------------------------
     | 🔧 УТИЛИТЫ
     |--------------------------------------------------*/

    /** Брать соседей категории (полезно для меню) */
    public function siblings()
    {
        return self::where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    /** Товары текущей + всех дочерних категорий */
    public function allProducts()
    {
        return Product::whereIn('category_id', $this->allChildrenIds());
    }


    /* -------------------------------------------------
     | 🖼 Аксессоры
     |--------------------------------------------------*/

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }


    /* -------------------------------------------------
     | ⚙️ Хуки модели
     |--------------------------------------------------*/

    protected static function boot()
    {
        parent::boot();

        // Генерация slug при создании
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $slug = Str::slug($category->name);
                $base = $slug;
                $i = 1;

                // Проверка уникальности slug
                while (self::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }

                $category->slug = $slug;
            }
        });

        // Очищаем кэш дерева при добавлении/изменении/удалении
        static::saved(fn() => Cache::forget('categories_tree'));
        static::deleted(fn() => Cache::forget('categories_tree'));
    }
}
