<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // ===============================
    // Витрина (каталог + товар)
    // ===============================
    public function index(Request $request)
    {
        $query = Product::query()->with(['city.country']);

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('country_id')) {
            $countryId = (int) $request->country_id;
            $query->whereHas('city', fn($q) => $q->where('country_id', $countryId));

            if ($request->filled('city_id')) {
                $query->where('city_id', (int) $request->city_id);
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc': $query->orderBy('price', 'asc'); break;
                case 'price_desc': $query->orderBy('price', 'desc'); break;
                case 'rating': $query->withAvg('reviews', 'rating')->orderBy('reviews_avg_rating', 'desc'); break;
                case 'new': $query->latest(); break;
                case 'benefit': $query->orderByRaw('(price / greatest(stock,1)) asc'); break;
                default: $query->latest(); break;
            }
        } else {
            $query->latest();
        }

        $products = $query->paginate(12)->withQueryString();
        return view('shop.index', compact('products'));
    }

    public function show($slug)
    {
        $product = Product::with([
            'seller' => fn($q) => $q->withAvg('reviews', 'rating')->withCount('reviews'),
            'reviews.user',
            'category.parent'
        ])
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->where('slug', $slug)
        ->firstOrFail();

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
            'image' => 'nullable|mimes:jpg,jpeg,png,webp,avif|max:2048',
            'gallery.*' => 'nullable|mimes:jpg,jpeg,png,webp,avif|max:2048',
            'category_id' => 'required|exists:categories,id',
            'city_id' => 'required|exists:cities,id',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

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

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        if ($request->hasFile('gallery')) {
            // добавляем новые, не трогая старые
            $gallery = is_array($product->gallery) ? $product->gallery : [];
            foreach ($request->file('gallery') as $file) {
                $gallery[] = $file->store('products/gallery', 'public');
            }
            $data['gallery'] = $gallery;
        }

        $product->update($data);
        return redirect()->route('seller.products.index')->with('success', 'Товар обновлён');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        if (is_array($product->gallery)) {
            foreach ($product->gallery as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $product->delete();
        return redirect()->route('seller.products.index')->with('success', 'Товар удалён');
    }

    // ===============================
    // AJAX — удаление одного фото из галереи
    // ===============================
    public function deleteGalleryImage(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        // Защита: продавец может удалить только у своих товаров
        if ($product->user_id !== auth()->id()) {
            return response()->json(['error' => '⛔ У вас нет прав для этого действия.'], 403);
        }

        $path = trim($request->input('path'));
        if (!$path) {
            return response()->json(['error' => 'Не указан путь к файлу'], 400);
        }

        // Убедимся, что галерея — массив
        $gallery = is_array($product->gallery)
            ? $product->gallery
            : (json_decode($product->gallery, true) ?? []);

        // Проверим, что файл принадлежит этому товару
        if (!in_array($path, $gallery, true)) {
            return response()->json(['error' => 'Фото не найдено в галерее'], 404);
        }

        // Удаляем сам файл
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        // Убираем только одно фото
        $gallery = array_values(array_filter($gallery, fn($img) => trim($img) !== $path));

        // Сохраняем обновлённый список
        $product->update(['gallery' => $gallery]);

        return response()->json(['success' => true]);
    }
}
