<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\CategoryCacheService; // ← ДОБАВЛЕНО

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

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'attribute_category');
    }

    /* ============================================================
     | 🔥 КЭШ: СТРУКТУРА ДЛЯ РЕКУРСИЙ
     ============================================================ */

    public static function tree()
    {
        return Cache::remember('cat.tree', 3600, function () {
            return self::select('id', 'parent_id')
                ->get()
                ->groupBy('parent_id');
        });
    }

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
     | 🔥 КЭШ: ПОЛНОЕ ДЕРЕВО
     ============================================================ */

    public static function fullTree()
    {
        return Cache::remember('cat.full', 3600, function () {
            return self::query()
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

    /* ============================================================
     | 🔧 УТИЛИТЫ
     ============================================================ */

    public function siblings()
    {
        return self::where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

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
     | ⚙️ ХУКИ
     ============================================================ */

    protected static function boot()
    {
        parent::boot();

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

        static::updating(function (Category $category) {
            if ($category->isDirty('slug')) {
                $oldSlug = $category->getOriginal('slug');
                if ($oldSlug) {
                    Cache::forget("cat.page.{$oldSlug}");
                }
            }
        });

        static::saved(function (Category $category) {

            // 🔥 ДОБАВЛЕНО
            CategoryCacheService::clear();

            Cache::forget('cat.tree');
            Cache::forget('cat.full');
            Cache::forget('cat.root');

            if ($category->slug) {
                Cache::forget("cat.page.{$category->slug}");
            }
        });

        static::deleted(function (Category $category) {

            // 🔥 ДОБАВЛЕНО
            CategoryCacheService::clear();

            Cache::forget('cat.tree');
            Cache::forget('cat.full');
            Cache::forget('cat.root');

            if ($category->slug) {
                Cache::forget("cat.page.{$category->slug}");
            }
        });
    }
}
