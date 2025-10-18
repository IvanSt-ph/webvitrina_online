<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * 🔹 Показать категорию — товары или подкатегории
     */
    public function show(string $slug)
    {
        // 🧠 Кэшируем категорию с родителями и потомками
        $category = Cache::remember("category:{$slug}", 3600, function () use ($slug) {
            return Category::with(['children', 'parent'])->where('slug', $slug)->firstOrFail();
        });

        /**
         * 🧭 Генерация хлебных крошек
         */
        $breadcrumbs = [
            'Категории' => route('category.index'),
        ];

        $parent = $category->parent;
        $stack = [];

        while ($parent) {
            $stack[$parent->name] = route('category.show', $parent->slug);
            $parent = $parent->parent;
        }

        $breadcrumbs = array_merge($breadcrumbs, array_reverse($stack));
        $breadcrumbs[$category->name] = '#';

        /**
         * 📂 Если есть подкатегории — показываем их плитками
         */
        if ($category->children->isNotEmpty()) {
            return view('categories.subcategories', compact('category', 'breadcrumbs'));
        }

        /**
         * 🛒 Если подкатегорий нет — показываем товары
         */
        $categoryIds = $category->allChildrenIds();

        // ⚙️ Создаём уникальный ключ для кэша на основе фильтров и сортировки
        $cacheKey = 'products:' . md5(json_encode([
            'category' => $categoryIds,
            'country' => request('country_id'),
            'city' => request('city_id'),
            'search' => request('q'),
            'sort' => request('sort', 'popular'),
            'page' => request('page', 1),
        ]));

        $products = Cache::remember($cacheKey, 600, function () use ($categoryIds) {
            $query = Product::whereIn('category_id', $categoryIds)
                ->with(['city.country']);

            // 🔍 Фильтры
            if (request()->filled('country_id')) {
                $countryId = (int) request('country_id');
                $query->whereHas('city', fn($q) => $q->where('country_id', $countryId));

                if (request()->filled('city_id')) {
                    $query->where('city_id', (int) request('city_id'));
                }
            }

            if (request()->filled('q')) {
                $query->where('title', 'like', '%' . request('q') . '%');
            }

           
// ⚙️ Сортировка
$sort = request('sort', 'popular'); // значение по умолчанию

match ($sort) {
    'price_asc'  => $query->orderBy('price', 'asc'),
    'price_desc' => $query->orderBy('price', 'desc'),
    'rating'     => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating'),
    'new'        => $query->orderByDesc('created_at'),
    'benefit'    => $query->orderByRaw('(stock / NULLIF(price, 0)) DESC'),
    default      => $query->orderByDesc('created_at'), // ← исправлено! вместо views
};


            return $query->paginate(20)->withQueryString();
        });

        return view('products.index', [
            'category' => $category,
            'products' => $products,
            'breadcrumbs' => $breadcrumbs,
            'activeCategoryId' => $category->id,
        ]);
    }

    /**
     * 🔹 Показать все категории плитками
     */
    public function index()
    {
        // 🧠 Кэшируем список всех корневых категорий с потомками
        $categories = Cache::remember('categories:root', 3600, function () {
            return Category::whereNull('parent_id')
                ->with('children')
                ->orderBy('name')
                ->get();
        });

        $breadcrumbs = [
            'Категории' => '#',
        ];

        return view('categories.index', compact('categories', 'breadcrumbs'));
    }

    /**
     * 🔹 Сохранение новой категории
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories',
            'icon' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $category = new Category($request->only(['name', 'slug', 'parent_id']));

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('categories', 'public');
            $category->icon = $path;
        }

        $category->save();

        // ❌ Очистим кэш категорий
        Cache::forget('categories:root');

        return redirect()
            ->route('categories.index')
            ->with('success', 'Категория добавлена');
    }

    /**
     * 🔹 Обновление категории
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'icon' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $category->fill($request->only(['name', 'slug', 'parent_id']));

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('categories', 'public');
            $category->icon = $path;
        }

        $category->save();

        // ❌ Очистим кэш категорий
        Cache::forget('categories:root');
        Cache::forget("category:{$category->slug}");

        return redirect()
            ->route('categories.index')
            ->with('success', 'Категория обновлена');
    }
}
