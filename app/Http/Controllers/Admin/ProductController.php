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

        $this->productService->create(
            data: $data,
            image: $request->file('image'),
            gallery: $request->file('gallery', [])
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', '✅ Товар успешно создан!');
    }

    /** ✏️ Редактирование */
    public function edit(Product $product)
    {
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $sellers = User::where('role', 'seller')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        $countryId = optional($product->city)->country_id;
        $cities = $countryId
            ? City::where('country_id', $countryId)->get()
            : collect();

        return view('admin.products.edit', compact(
            'product', 'categories', 'sellers', 'countries', 'cities'
        ));
    }

    /** 🔄 Обновление */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();
        
        $galleryToDelete = $request->input('gallery_to_delete', []);
        
        if (is_string($galleryToDelete)) {
            $galleryToDelete = json_decode($galleryToDelete, true) ?? [];
        }

        $this->productService->update(
            product: $product,
            data: $data,
            image: $request->file('image'),
            galleryNew: $request->file('gallery', []),
            galleryToDelete: $galleryToDelete
        );

        return redirect()->route('admin.products.index')->with('success', '✅ Товар обновлён.');
    }

    /** 🗑 Удаление */
    public function destroy(Product $product)
    {
        $this->productService->delete($product);

        return redirect()->route('admin.products.index')->with('success', '🗑️ Товар удалён.');
    }

    /** 🔍 Live-поиск по названию и артикулу (SKU) */
    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::select('id', 'title', 'price', 'image', 'sku')
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%");
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    /** 🖼️ Удаление изображения из галереи товара (AJAX) - ИСПРАВЛЕНО */
    public function deleteGalleryImage(Request $request, Product $product)
    {
        $path = $request->input('path');

        if (!$path) {
            return response()->json(['error' => 'Путь к изображению не указан'], 400);
        }

        // Вся логика удаления в сервисе!
        $this->productService->deleteGalleryImage($product, $path);

        return response()->json(['success' => true]);
    }
}