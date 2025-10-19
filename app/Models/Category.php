<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'icon',
        'image', // ✅ добавил сюда
    ];

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

    /**
     * Рекурсивно собрать все ID потомков (включая саму категорию).
     */
    public function allChildrenIds()
    {
        $ids = collect([$this->id]);

        foreach ($this->children as $child) {
            $ids = $ids->merge($child->allChildrenIds());
        }

        return $ids->unique();
    }

    public function attributes()
    {
        return $this->belongsToMany(\App\Models\Attribute::class, 'attribute_category');
    }


    public function getImageUrlAttribute()
{
    return $this->image ? asset('storage/'.$this->image) : null;
}

}
