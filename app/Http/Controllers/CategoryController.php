<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CategoryController extends Controller
{
    /**
     * 🔹 Показать категорию — товары или подкатегории
     */
    public function show(string $slug)
    {
        // Кэшируем категорию и все связи
        $cacheKey = "category_page:{$slug}";

        $category = Cache::remember($cacheKey, 3600, function () use ($slug) {
            return Category::query()
                ->select('id', 'name', 'slug', 'parent_id', 'icon', 'image')
                ->with([
                    'children:id,name,slug,parent_id,icon,image',
                    'parent:id,name,slug'
                ])
                ->where('slug', $slug)
                ->firstOrFail();
        });

        // 🧭 Хлебные крошки
        $breadcrumbs = ['Категории' => route('category.index')];
        $parent = $category->parent;
        $stack = [];

       while ($parent && $parent->slug) {
            $stack[$parent->name] = route('category.show', $parent->slug);
            $parent = $parent->parent;
                                        }


        $breadcrumbs = array_merge($breadcrumbs, array_reverse($stack));
        $breadcrumbs[$category->name] = '#';

        // Если есть подкатегории
        if ($category->children->isNotEmpty()) {
            return view('categories.subcategories', compact('category', 'breadcrumbs'));
        }

        // 📦 Если товаров нет — кэшируем и их
        $categoryIds = $category->allChildrenIds();
        $productKey = 'products:' . md5(json_encode([
            'category' => $categoryIds,
            'country'  => request('country_id'),
            'city'     => request('city_id'),
            'search'   => request('q'),
            'sort'     => request('sort', 'popular'),
            'page'     => request('page', 1),
        ]));

        $products = Cache::remember($productKey, 600, function () use ($categoryIds) {
            $query = Product::whereIn('category_id', $categoryIds)
                ->with([
                    'category',
                    'seller',
                    'city.country',
                    'reviews',
                ])
                ->withCount([
                    'reviews as reviews_count' => fn($q) => $q->where('status', 'approved'),
                ])
                ->withAvg([
                    'reviews as reviews_avg_rating' => fn($q) => $q->where('status', 'approved'),
                ], 'rating');

            if (request()->filled('country_id')) {
                $countryId = (int) request('country_id');
                $query->whereHas('city', fn($q) => $q->where('country_id', $countryId));

                if (request()->filled('city_id')) {
                    $query->where('city_id', (int)request('city_id'));
                }
            }

            if (request()->filled('q')) {
                $query->where('title', 'like', '%' . request('q') . '%');
            }

            $sort = request('sort', 'popular');
            match ($sort) {
                'price_asc'  => $query->orderBy('price', 'asc'),
                'price_desc' => $query->orderBy('price', 'desc'),
                'rating'     => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating'),
                'new'        => $query->orderByDesc('created_at'),
                default      => $query->orderByDesc('created_at'),
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
        $categories = Cache::remember('categories:root', 3600, function () {
            return Category::whereNull('parent_id')
                ->select('id', 'name', 'slug', 'icon', 'image')
                ->with(['children:id,name,slug,parent_id,icon,image'])
                ->orderBy('name')
                ->get();
        });

        $breadcrumbs = ['Категории' => '#'];
        return view('categories.index', compact('categories', 'breadcrumbs'));
    }

    /**
     * 💾 Сохранение новой категории
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'slug'      => 'required|string|max:255|unique:categories',
            'parent_id' => 'nullable|exists:categories,id',
            'icon'      => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'image'     => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
        ]);

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories/icons', 'public');
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            // сохраняем оригинал
            $path = $file->store('categories/original', 'public');
            $thumbPath = 'categories/thumbs/' . basename($path);

            // создаем миниатюру через ImageManager
            $manager = new ImageManager(new Driver());
            $img = $manager->read($file->getRealPath())
                ->scale(width: 300)
                ->toWebp(80);

            Storage::disk('public')->put($thumbPath, $img->toString());

            $data['image'] = $thumbPath;
        }

        Category::create($data);
        Cache::forget('categories:root');

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно добавлена.');
    }

    /**
     * ✏️ Обновление категории
     */
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'slug'      => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'icon'      => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'image'     => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
        ]);

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories/icons', 'public');
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('categories/original', 'public');
            $thumbPath = 'categories/thumbs/' . basename($path);

            $manager = new ImageManager(new Driver());
            $img = $manager->read($file->getRealPath())
                ->scale(width: 300)
                ->toWebp(80);

            Storage::disk('public')->put($thumbPath, $img->toString());

            $data['image'] = $thumbPath;
        }

        $category->update($data);

        // 💡 Автоочистка кэша при обновлении
        Cache::forget('categories:root');
        Cache::forget("category:{$category->slug}");

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно обновлена.');
    }
}
