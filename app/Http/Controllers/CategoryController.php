<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // 🔹 Показать товары категории
    public function show(string $slug)
    {
        $category = Category::with('children')->where('slug', $slug)->firstOrFail();

        $categoryIds = $category->allChildrenIds();

        $query = Product::whereIn('category_id', $categoryIds)
            ->with(['city.country'])
            ->latest();

        // Фильтры
        if (request()->filled('country_id')) {
            $countryId = (int) request('country_id');
            $query->whereHas('city', function ($q) use ($countryId) {
                $q->where('country_id', $countryId);
            });

            if (request()->filled('city_id')) {
                $query->where('city_id', (int) request('city_id'));
            }
        }

        // Поиск
        if (request()->filled('q')) {
            $query->where('title', 'like', '%' . request('q') . '%');
        }

        // Сортировка
        if (request()->filled('sort')) {
            switch (request('sort')) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'rating':
                    $query->withAvg('reviews', 'rating')->orderBy('reviews_avg_rating', 'desc');
                    break;
                case 'new':
                    $query->latest();
                    break;
                case 'benefit':
                    $query->orderByRaw('(stock / price) desc');
                    break;
                default:
                    $query->latest();
            }
        }

        $products = $query->paginate(20)->withQueryString();

        return view('products.index', [
            'category' => $category,
            'products' => $products,
            'activeCategoryId' => $category->id,
        ]);
    }

    // 🔹 Сохранение новой категории
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

        return redirect()->route('categories.index')->with('success', 'Категория добавлена');
    }

    // 🔹 Обновление категории
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

        return redirect()->route('categories.index')->with('success', 'Категория обновлена');
    }
}
