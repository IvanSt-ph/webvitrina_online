<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductManageController extends Controller

{
    /** 📋 Список товаров продавца */
public function index()
{
    $products = Product::where('user_id', Auth::id())
        ->with(['category', 'city.country'])
        ->orderByDesc('created_at')
        ->paginate(20);

    return view('seller.products.index', compact('products'));
}


    /** ➕ Создание нового товара */
    public function create()
    {
        return $this->formView(new Product());
    }

    /** ✏️ Редактирование */
    public function edit(Product $product)
    {
        $this->authorize('update', $product);
        return $this->formView($product);
    }

    /** 🧩 Вынесенная логика общей формы (чтобы не дублировать код) */
    protected function formView(Product $product)
    {
        // ⚙️ Кэшируем корневые категории и страны
        $rootCategories = Cache::remember('root_categories', 3600, fn() =>
            Category::whereNull('parent_id')->orderBy('name')->get()
        );

        $countries = Cache::remember('countries_list', 3600, fn() =>
            Country::orderBy('name')->get()
        );

        // 🧩 Определяем цепочку категорий (чтобы вывести правильно подкатегории)
        $categoryChain = collect();
        if ($product->category_id) {
            $cat = Category::with('parent')->find($product->category_id);
            while ($cat) {
                $categoryChain->prepend($cat);
                $cat = $cat->parent;
            }
        }

        return view('seller.products.form', compact('product', 'rootCategories', 'categoryChain', 'countries'));
    }

    /** 💾 Сохранение нового товара */
    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        $data['user_id'] = Auth::id();


        // 🖼️ Сохранение фото
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        // 📸 Галерея
        if ($request->hasFile('gallery')) {
            $data['gallery'] = collect($request->file('gallery'))->map(fn($f) =>
                $f->store('products/gallery', 'public')
            )->values()->toArray();
        }

        Product::create($data);

        return redirect()->route('seller.products.index')->with('success', 'Товар добавлен');
    }

//** 🔄 Обновление существующего товара */
public function update(Request $request, Product $product)
{
    $this->authorize('update', $product);

    $data = $this->validateProduct($request);

    // 🖼 Главное изображение
    if ($request->hasFile('image')) {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $data['image'] = $request->file('image')->store('products', 'public');
    }

    // 📸 Галерея
    if ($request->hasFile('gallery')) {
        $newGallery = collect($request->file('gallery'))->map(fn($f) =>
            $f->store('products/gallery', 'public')
        )->values()->toArray();

        $data['gallery'] = array_merge($product->gallery ?? [], $newGallery);
    }

    $product->update($data);

    // 🚀 Просто редиректим обратно к списку без сообщений
    return redirect()->route('seller.products.index');
}



    /** 🗑️ Удаление */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        if ($product->image) Storage::disk('public')->delete($product->image);
        if ($product->gallery) {
            foreach ($product->gallery as $img) {
                Storage::disk('public')->delete($img);
            }
        }

        $product->delete();
        return back()->with('success', 'Товар удалён');
    }

    /** 🧽 Удаление одного фото из галереи */
    public function deleteGalleryImage(Product $product, Request $request)
    {
        $this->authorize('update', $product);

        $path = $request->input('path');
        $gallery = collect($product->gallery)->reject(fn($p) => $p === $path)->values();
        $product->update(['gallery' => $gallery]);

        Storage::disk('public')->delete($path);

        return response()->json(['success' => true]);
    }

    /** ✅ Валидация */
    protected function validateProduct(Request $request)
    {
        return $request->validate([
            'title'        => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'description'  => 'nullable|string|max:1000',
            'category_id'  => 'required|exists:categories,id',
            'country_id'   => 'required|exists:countries,id',
            'city_id'      => 'required|exists:cities,id',
            'address'      => 'nullable|string|max:255',
            'latitude'     => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
            'image'        => 'nullable|image|max:4096',
            'gallery.*'    => 'nullable|image|max:4096',
        ]);
    }
}
