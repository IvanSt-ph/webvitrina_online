<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categories
    ) {}

    /**
     * 🔹 Показать категорию — товары или подкатегории
     */
    public function show(string $slug)
    {
        // 📌 Категория (с кешом через сервис)
        $category = $this->categories->getBySlug($slug);

        // Атрибуты категории (по всем потомкам)
        $allIds = $category->allChildrenIds();

        $attributes = Attribute::query()
            ->select('attributes.id', 'name', 'type', 'unit', 'options', 'is_filterable')
            ->join('attribute_category', 'attributes.id', '=', 'attribute_category.attribute_id')
            ->where('is_filterable', 1)
            ->whereIn('attribute_category.category_id', $allIds)
            ->groupBy('attributes.id', 'name', 'type', 'unit', 'options', 'is_filterable')
            ->get();

        $category->setRelation('attributes', $attributes);

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

        // 📦 Если есть подкатегории — показываем список подкатегорий
        if ($category->children->isNotEmpty()) {
            return view('categories.subcategories', [
                'category'       => $category,
                'breadcrumbs'    => $breadcrumbs,
            ]);
        }

        // 📌 ТОВАРЫ — БЕЗ КЭША (важно!)
        $categoryIds = $category->allChildrenIds();

        $products = (function () use ($categoryIds) {

            $query = Product::whereIn('category_id', $categoryIds)
                ->with(['category', 'seller', 'city.country', 'reviews'])
                ->withCount([
                    'reviews as reviews_count' => fn($q) => $q->where('status', 'approved'),
                ])
                ->withAvg([
                    'reviews as reviews_avg_rating' => fn($q) => $q->where('status', 'approved'),
                ], 'rating');

            // 🌍 Фильтр по стране/городу
            if (request()->filled('country_id')) {
                $countryId = (int) request('country_id');
                $query->whereHas('city', fn($q) => $q->where('country_id', $countryId));

                if (request()->filled('city_id')) {
                    $query->where('city_id', (int)request('city_id'));
                }
            }

            // 🔍 Поиск
            if (request()->filled('q')) {
                $q = trim(request('q'));
                $query->where('title', 'like', "%{$q}%");
            }

            // 🔥 Фильтры по атрибутам
            if ($filters = request('filters')) {
                foreach ($filters as $attributeId => $value) {

                    $query->whereHas('attributeValues', function ($q) use ($attributeId, $value) {

                        $q->where('attribute_id', $attributeId);

                        // Числовой диапазон
                        if (is_array($value) && (isset($value['from']) || isset($value['to']))) {

                            if (!empty($value['from'])) {
                                $q->where('value', '>=', $value['from']);
                            }

                            if (!empty($value['to'])) {
                                $q->where('value', '<=', $value['to']);
                            }
                        } else {

                            // Мультивыбор (checkbox/select)
                            $values = array_filter((array)$value, fn($v) => $v !== null && $v !== '');

                            if (!empty($values)) {
                                $q->whereIn('value', $values);
                            }
                        }
                    });
                }
            }

            // 🔽 Сортировка
            $sort = request('sort', 'popular');

            match ($sort) {
                'price_asc'  => $query->orderBy('price', 'asc'),
                'price_desc' => $query->orderBy('price', 'desc'),
                'rating'     => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating'),
                'new'        => $query->orderByDesc('created_at'),
                default      => $query->orderByDesc('created_at'),
            };

            return $query->paginate(20)->withQueryString();
        })();

        return view('products.index', [
            'category'         => $category,
            'products'         => $products,
            'breadcrumbs'      => $breadcrumbs,
            'activeCategoryId' => $category->id,
        ]);
    }

    /**
     * 🔹 Страница всех категорий
     */
    public function index()
    {
        $categories  = $this->categories->root();
        $breadcrumbs = ['Категории' => '#'];

        return view('categories.index', compact('categories', 'breadcrumbs'));
    }

    /**
     * 💾 Создание категории
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'slug'      => 'nullable|string|max:255|unique:categories',
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

        Category::create($data);
        
        CategoryCacheService::clear();


        // Кеши категорий чистятся в хуках модели Category

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
            'slug'      => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
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

        // Все нужные кеши чистятся в хуках модели

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно обновлена.');
    }
}
