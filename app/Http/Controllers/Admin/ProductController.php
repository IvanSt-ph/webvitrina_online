<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Category;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use App\Models\Product;
use App\Services\ProductService;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private ProductService $productService;
    private ProductRepository $productRepository;

    public function __construct(ProductService $productService, ProductRepository $productRepository)
    {
        $this->productService = $productService;
        $this->productRepository = $productRepository;
    }

    /** 🧾 Список товаров */
    public function index(Request $request)
    {
        $products = $this->productRepository->getFilteredProducts($request);
        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /** ➕ Создание */
    public function create()
    {
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $sellers = User::where('role', 'seller')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();
        $cities = collect();
        $product = new Product();

        return view('admin.products.create', compact('categories', 'sellers', 'countries', 'cities', 'product'));
    }

    /** 💾 Сохранение */
    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();

        $this->productService->store(
            data: $data,
            image: $request->file('image'),
            galleryFiles: $request->file('gallery', []),
            userId: $data['user_id']
        );

        return redirect()->route('admin.products.index')->with('success', '✅ Товар создан.');
    }

    /** ✏️ Редактирование */
    public function edit(Product $product)
    {
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $sellers = User::where('role', 'seller')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();
        $cities = $product->country_id
            ? City::where('country_id', $product->country_id)->get()
            : collect();

        return view('admin.products.edit', compact('product', 'categories', 'sellers', 'countries', 'cities'));
    }

    /** 🔄 Обновление */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();

        $this->productService->update(
            product: $product,
            data: $data,
            image: $request->file('image'),
            galleryNew: $request->file('gallery', [])
        );

        return redirect()->route('admin.products.index')->with('success', '✅ Товар обновлён.');
    }

    /** 🗑 Удаление */
    public function destroy(Product $product)
    {
        $this->productService->delete($product);

        return redirect()->route('admin.products.index')->with('success', '🗑️ Товар удалён.');
    }
}
