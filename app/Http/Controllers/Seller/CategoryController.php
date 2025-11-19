<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * 🔹 Получить подкатегории выбранной категории
     */
    public function children(Category $parent)
    {
        return Category::query()
            ->select('id', 'name')
            ->where('parent_id', $parent->id)
            ->orderBy('name')
            ->get();
    }

    /**
     * 🔹 Получить всю цепочку категорий вверх (для редактирования товара)
     *     например: Дети → Девочки → Платья → Летние
     */
    public function chain($id)
    {
        $chain = collect();
        $cat = Category::find($id);

        while ($cat) {
            $chain->push($cat);
            $cat = $cat->parent;
        }

        return $chain->reverse()->values();
    }
}
