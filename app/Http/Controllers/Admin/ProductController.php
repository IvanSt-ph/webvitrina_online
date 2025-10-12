<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    /** 🧾 Список */
    public function index()
    {
        $products = Product::with(['category', 'seller', 'city.country'])
            ->orderByDesc('created_at')->paginate(20);

        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /** ➕ Создание */
    public function create()
    {
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $sellers = User::where('role','seller')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();
        $cities = collect();
        $product = new Product();

        return view('admin.products.create', compact('categories','sellers','countries','cities','product'));
    }

    /** 💾 Сохранение */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'=>'required|string|max:255',
            'price'=>'required|numeric|min:0',
            'stock'=>'required|integer|min:0',
            'category_id'=>'nullable|exists:categories,id',
            'user_id'=>'required|exists:users,id',
            'country_id'=>'required|exists:countries,id',
            'city_id'=>'required|exists:cities,id',
            'description'=>'nullable|string',
            'image'=>'nullable|image|max:4096',
            'gallery.*'=>'nullable|image|max:4096',
            'status'=>'nullable|boolean',
            'address'=>'nullable|string|max:255',
            'latitude'=>'nullable|numeric',
            'longitude'=>'nullable|numeric',
        ]);

        $this->productService->store(
            data: $validated,
            image: $request->file('image'),
            galleryFiles: $request->file('gallery', []),
            userId: $validated['user_id']
        );

        return redirect()->route('admin.products.index')->with('success','✅ Товар создан.');
    }

    /** ✏️ Редактирование */
    public function edit(Product $product)
    {
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $sellers = User::where('role','seller')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();
        $cities = $product->country_id ? City::where('country_id',$product->country_id)->get() : collect();

        return view('admin.products.edit', compact('product','categories','sellers','countries','cities'));
    }

    /** 🔄 Обновление */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'title'=>'required|string|max:255',
            'price'=>'required|numeric|min:0',
            'stock'=>'required|integer|min:0',
            'category_id'=>'nullable|exists:categories,id',
            'user_id'=>'required|exists:users,id',
            'country_id'=>'required|exists:countries,id',
            'city_id'=>'required|exists:cities,id',
            'description'=>'nullable|string',
            'image'=>'nullable|image|max:4096',
            'gallery.*'=>'nullable|image|max:4096',
            'status'=>'nullable|boolean',
            'address'=>'nullable|string|max:255',
            'latitude'=>'nullable|numeric',
            'longitude'=>'nullable|numeric',
        ]);

        $this->productService->update(
            product: $product,
            data: $validated,
            image: $request->file('image'),
            galleryNew: $request->file('gallery', [])
        );

        return redirect()->route('admin.products.index')->with('success','✅ Товар обновлён.');
    }

    /** 🗑 Удаление */
    public function destroy(Product $product)
    {
        $this->productService->delete($product);
        return redirect()->route('admin.products.index')->with('success','🗑️ Товар удалён.');
    }
}
