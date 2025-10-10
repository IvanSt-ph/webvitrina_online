<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /** 🧾 Список товаров */
    public function index()
    {
        $products = Product::with(['category', 'seller', 'city.country'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /** ➕ Форма создания */
    public function create()
    {
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $sellers    = User::where('role', 'seller')->orderBy('name')->get();
        $countries  = Country::orderBy('name')->get();
        $cities     = collect(); // города подтянутся через AJAX
        $product    = new Product(); // чтобы карта не падала при пустых координатах

        return view('admin.products.create', compact('categories', 'sellers', 'countries', 'cities', 'product'));
    }

    /** 💾 Сохранение нового товара */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:products,slug',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'user_id'     => 'required|exists:users,id',
            'country_id'  => 'required|exists:countries,id',
            'city_id'     => 'required|exists:cities,id',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:2048',
            'gallery.*'   => 'nullable|image|max:2048',
            'status'      => 'nullable|boolean',
            'address'     => 'nullable|string|max:255',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
        ]);

        // 📸 Главное фото
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        // 📸 Галерея
        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $file) {
                $gallery[] = $file->store('products/gallery', 'public');
            }
            $data['gallery'] = $gallery;
        }

        Product::create($data);

        return redirect()->route('admin.products.index')
            ->with('success', '✅ Товар успешно создан.');
    }

    /** ✏️ Редактирование */
    public function edit(Product $product)
    {
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $sellers    = User::where('role', 'seller')->orderBy('name')->get();
        $countries  = Country::orderBy('name')->get();

        $cities = $product->country_id
            ? City::where('country_id', $product->country_id)->orderBy('name')->get()
            : collect();

        return view('admin.products.edit', compact('product', 'categories', 'sellers', 'countries', 'cities'));
    }

    /** 🔄 Обновление */
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'user_id'     => 'required|exists:users,id',
            'country_id'  => 'required|exists:countries,id',
            'city_id'     => 'required|exists:cities,id',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:2048',
            'gallery.*'   => 'nullable|image|max:2048',
            'status'      => 'nullable|boolean',
            'address'     => 'nullable|string|max:255',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
        ]);

        // 📸 Обновляем главное фото
        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        // 📸 Добавляем новую галерею
        if ($request->hasFile('gallery')) {
            $gallery = $product->gallery ?? [];
            foreach ($request->file('gallery') as $file) {
                $gallery[] = $file->store('products/gallery', 'public');
            }
            $data['gallery'] = $gallery;
        }

        $product->update($data);

        return redirect()->route('admin.products.index')
            ->with('success', '✅ Товар успешно обновлён.');
    }

    /** 🗑️ Удаление товара */
    public function destroy(Product $product)
    {
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        if (is_array($product->gallery)) {
            foreach ($product->gallery as $img) {
                if ($img && Storage::disk('public')->exists($img)) {
                    Storage::disk('public')->delete($img);
                }
            }
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', '🗑️ Товар удалён.');
    }

    /** 🖼️ Удаление одного фото из галереи (AJAX) */
public function deleteGalleryImage(Request $request, Product $product)
{
    $path = $request->input('path');

    if (!$path || !is_array($product->gallery)) {
        return response()->json(['error' => 'Неверный путь к файлу'], 400);
    }

    // Проверяем, принадлежит ли файл этому товару
    if (!in_array($path, $product->gallery)) {
        return response()->json(['error' => 'Фото не принадлежит этому товару'], 403);
    }

    // Удаляем сам файл
    if (Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
    }

    // Обновляем массив галереи без удалённого пути
    $gallery = collect($product->gallery)
        ->reject(fn($img) => trim($img) === trim($path))
        ->values()
        ->all();

    $product->update(['gallery' => $gallery]);

    return response()->json(['success' => true]);
}

/** 🔍 Быстрый поиск товаров (FULLTEXT + fallback) */
public function search(Request $request)
{
    $query = trim($request->get('q', ''));

    // если меньше 2 символов — сразу выходим
    if (strlen($query) < 2) {
        return response()->json([]);
    }

    // ⚡ Пробуем сначала быстрый FULLTEXT-поиск
    $products = Product::query()
        ->whereRaw("MATCH(title) AGAINST(? IN NATURAL LANGUAGE MODE)", [$query])
        ->limit(10)
        ->get(['id', 'title', 'price', 'image']);

    // 🩹 Если ничего не найдено (или FULLTEXT не работает), fallback на обычный LIKE
    if ($products->isEmpty()) {
        $products = Product::query()
            ->where('title', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'title', 'price', 'image']);
    }

    return response()->json($products);
}



}
