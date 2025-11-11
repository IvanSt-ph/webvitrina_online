<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Country;
use App\Models\City;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ProductManageController extends Controller
{
    public function __construct(protected ProductService $products) {}

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

    /** 🧩 Общая форма для create/edit */
    protected function formView(Product $product)
    {
        $rootCategories = Cache::remember('root_categories', 3600, fn() =>
            Category::whereNull('parent_id')->orderBy('name')->get()
        );

        $countries = Cache::remember('countries_list', 3600, fn() =>
            Country::orderBy('name')->get()
        );

        // 🔗 Цепочка категорий от текущей вверх
        $categoryChain = collect();
        if ($product->category_id) {
            $cat = Category::with('parent')->find($product->category_id);
            while ($cat) {
                $categoryChain->prepend($cat);
                $cat = $cat->parent;
            }
        }

        // ⚡ ВСЕ категории одним массивом для моментальной фильтрации
        $categoriesTree = Cache::remember('all_categories_tree', 3600, fn() =>
            Category::select('id', 'name', 'parent_id')->orderBy('name')->get()
        );

        return view('seller.products.form', compact(
            'product',
            'rootCategories',
            'categoryChain',
            'countries',
            'categoriesTree'
        ));
    }

    /** 💾 Создание нового товара */
    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        $userId = Auth::id();

        // Определяем валюту по стране
        $city = City::with('country')->findOrFail($request->city_id);
        $currencyBase = $city->country->currency ?? 'MDL';

        $product = new Product($data);
        $product->user_id = $userId;
        $product->currency_base = $currencyBase;
        $product->price = $request->price;
        $product->price_prb = $request->price_prb;
        $product->price_mdl = $request->price_mdl;
        $product->price_uah = $request->price_uah;

        $svc = app(\App\Services\CurrencyService::class);
        $map = ['PRB' => 'price_prb', 'MDL' => 'price_mdl', 'UAH' => 'price_uah'];
        foreach ($map as $code => $field) {
            if (is_null($product->{$field})) {
                $product->{$field} = $svc->convert((float)$product->price, $currencyBase, $code);
            }
        }

        $image   = $request->file('image');
        $gallery = $request->file('gallery', []);
        $this->products->store($product->toArray(), $image, $gallery, $userId);

        return redirect()->route('seller.products.index')->with('success', '✅ Товар добавлен');
    }

    /** 🔄 Обновление существующего товара */
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);
        $data = $this->validateProduct($request);

        $image           = $request->file('image');
        $galleryNew      = $request->file('gallery', []);
        $galleryToDelete = $request->input('delete_gallery', []);

        $this->products->update($product, $data, $image, $galleryNew, $galleryToDelete);

        return redirect()->route('seller.products.index')->with('success', '✅ Изменения сохранены');
    }

    /** 🗑️ Удаление */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        $this->products->delete($product);

        return back()->with('success', '🗑️ Товар удалён');
    }

    /** 🧽 Удаление одного фото из галереи */
    public function deleteGalleryImage(Product $product, Request $request)
    {
        $this->authorize('update', $product);
        $path = $request->input('path');
        $this->products->deleteFromGallery($product, [$path]);
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
