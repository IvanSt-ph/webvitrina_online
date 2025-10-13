<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use App\Models\Category;
use App\Models\Country;
use App\Services\ProductService;

class ProductManageController extends Controller
{
    public function __construct(private ProductService $productService) {}

    /** 🧾 Список товаров продавца */
    public function index()
    {
        $products = Product::where('user_id', auth()->id())
            ->with(['category', 'city.country'])
            ->latest()
            ->paginate(15);

        return view('seller.products.index', compact('products'));
    }

    /** ➕ Форма создания */
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

    /** 💾 Сохранение */
    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();

        $this->productService->store(
            data: $data,
            image: $request->file('image'),
            galleryFiles: $request->file('gallery', []),
            userId: auth()->id()
        );

        return redirect()->route('seller.products.index')->with('success', '✅ Товар создан.');
    }

    /** ✏️ Редактирование */
    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $rootCategories = Category::whereNull('parent_id')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('seller.products.form', compact('product', 'rootCategories', 'countries'));
    }

    /** 🔄 Обновление */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        $this->productService->update(
            product: $product,
            data: $data,
            image: $request->file('image'),
            galleryNew: $request->file('gallery', [])
        );

        return redirect()->route('seller.products.index')->with('success', '✅ Товар обновлён.');
    }

    /** 🗑 Удаление */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return redirect()->route('seller.products.index')->with('success', '🗑️ Товар удалён.');
    }
}
