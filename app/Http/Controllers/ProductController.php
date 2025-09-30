<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // ===============================
    // Витрина (каталог + товар)
    // ===============================

    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['city.country']); // убрали ->latest()

        // Поиск
        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        // Фильтр по стране
        if ($request->filled('country_id')) {
            $countryId = (int) $request->country_id;

            $query->whereHas('city', function ($q) use ($countryId) {
                $q->where('country_id', $countryId);
            });

            // Фильтр по городу только если выбрана страна
            if ($request->filled('city_id')) {
                $query->where('city_id', (int) $request->city_id);
            }
        }

        // Фильтр по категории
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        // Сортировка
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'rating':
                    $query->withAvg('reviews', 'rating')
                          ->orderBy('reviews_avg_rating', 'desc');
                    break;
                case 'new':
                    $query->latest(); // created_at DESC
                    break;
                case 'benefit':
                    $query->orderByRaw('(price / greatest(stock,1)) asc');
                    break;
                case 'popular':
                default:
                    $query->latest(); // по умолчанию
                    break;
            }
        } else {
            // если сортировка не выбрана — показываем новые
            $query->latest();
        }

        // Пагинация с сохранением параметров
        $products = $query->paginate(12)->withQueryString();

        return view('shop.index', compact('products'));
    }

    public function show($slug)
    {
        $product = Product::with([
            'seller' => fn($q) => $q->withAvg('reviews', 'rating')
                                    ->withCount('reviews'),
            'reviews.user',
            'category.parent'
        ])
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->where('slug', $slug)
        ->firstOrFail();

        // Похожие товары
        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return view('shop.product-show', compact('product', 'related'));
    }

    // ===============================
    // Панель продавца (CRUD)
    // ===============================

    public function create()
    {
        $rootCategories = Category::whereNull('parent_id')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('seller.products.form', [
            'product' => new Product(),
            'rootCategories' => $rootCategories,
            'countries' => $countries,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'gallery.*' => 'nullable|image|max:2048',
            'category_id' => 'required|exists:categories,id',
            'city_id' => 'required|exists:cities,id',
        ]);

        // Главное фото
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        // Галерея
        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $file) {
                $gallery[] = $file->store('products/gallery', 'public');
            }
            $data['gallery'] = $gallery;
        }

        $data['user_id'] = auth()->id();

        Product::create($data);

        return redirect()->route('seller.products.index')->with('success', 'Товар создан');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $rootCategories = Category::whereNull('parent_id')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('seller.products.form', compact('product', 'rootCategories', 'countries'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'gallery.*' => 'nullable|image|max:2048',
            'category_id' => 'required|exists:categories,id',
            'city_id' => 'required|exists:cities,id',
        ]);

        // Главное фото
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        // Галерея (несколько фото)
        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $file) {
                if ($file) {
                    $gallery[] = $file->store('products/gallery', 'public');
                }
            }
            $data['gallery'] = $gallery;
        }

        $product->update($data);

        return redirect()->route('seller.products.index')->with('success', 'Товар обновлён');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('seller.products.index')->with('success', 'Товар удалён');
    }
}
