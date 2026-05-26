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
use App\Services\SellerPlanService;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    private ProductService $productService;
    private ProductRepository $productRepository;

    public function __construct(ProductService $productService, ProductRepository $productRepository, private readonly SellerPlanService $sellerPlans)
    {
        $this->productService = $productService;
        $this->productRepository = $productRepository;
    }

    /** 🧾 Список товаров */
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'draft'])],
            'stock' => ['nullable', Rule::in(['out', 'low', 'available'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'seller_id' => ['nullable', 'integer', 'exists:users,id'],
            'sort' => ['nullable', Rule::in(['latest', 'oldest', 'price_asc', 'price_desc', 'stock_asc', 'views_desc'])],
        ]);

        $products = $this->productRepository->getFilteredAdminProducts($request);
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $sellers = User::where('role', 'seller')->orderBy('name')->get(['id', 'name']);
        $summary = [
            'total' => Product::count(),
            'active' => Product::where('status', 'active')->count(),
            'draft' => Product::where('status', 'draft')->count(),
            'out_of_stock' => Product::where('status', 'active')->where('stock', 0)->count(),
        ];

        return view('admin.products.index', compact('products', 'categories', 'sellers', 'summary'));
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
        $seller = User::findOrFail($data['user_id']);

        if (! $this->sellerPlans->canCreateProduct($seller)) {
            throw ValidationException::withMessages([
                'user_id' => $this->sellerPlans->limitMessage($seller),
            ]);
        }

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

        $sellerPlanProfiles = $sellers->mapWithKeys(fn (User $seller) => [
            $seller->id => $this->sellerPlans->profileFor($seller),
        ]);

        return view('admin.products.edit', compact(
            'product', 'categories', 'sellers', 'countries', 'cities', 'sellerPlanProfiles'
        ));
    }

    /** 🔄 Обновление */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();

        $sellerId = (int) ($data['user_id'] ?? $product->user_id);
        if ($sellerId !== (int) $product->user_id) {
            $seller = User::findOrFail($sellerId);

            if (! $this->sellerPlans->canCreateProduct($seller)) {
                throw ValidationException::withMessages([
                    'user_id' => $this->sellerPlans->limitMessage($seller),
                ]);
            }
        }
        
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
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);

        $products = Product::select('id', 'title', 'price', 'image', 'sku')
            ->where(function ($query) use ($like) {
                $query->where('title', 'like', "%{$like}%")
                      ->orWhere('sku', 'like', "%{$like}%");
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
        $this->productService->deleteFromGallery($product, $path);

        return response()->json(['success' => true]);
    }
}
