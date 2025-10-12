<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Country;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductManageController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index()
    {
        $products = Product::where('user_id', auth()->id())
            ->with(['category','city.country'])->latest()->paginate(15);

        return view('seller.products.index', compact('products'));
    }

    public function create()
    {
        $rootCategories = Category::whereNull('parent_id')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('seller.products.form', [
            'product'=>new Product(),
            'rootCategories'=>$rootCategories,
            'countries'=>$countries,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'=>'required|string|max:255',
            'price'=>'required|numeric|min:0',
            'stock'=>'required|integer|min:0',
            'description'=>'nullable|string',
            'image'=>'nullable|image|max:4096',
            'gallery.*'=>'nullable|image|max:4096',
            'category_id'=>'required|exists:categories,id',
            'city_id'=>'required|exists:cities,id',
            'country_id'=>'nullable|exists:countries,id',
            'address'=>'nullable|string|max:255',
            'latitude'=>'nullable|numeric',
            'longitude'=>'nullable|numeric',
        ]);

        $this->productService->store(
            data: $validated,
            image: $request->file('image'),
            galleryFiles: $request->file('gallery', []),
            userId: auth()->id()
        );

        return redirect()->route('seller.products.index')->with('success','Товар создан');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);
        $rootCategories = Category::whereNull('parent_id')->orderBy('name')->get();
        $countries = Country::orderBy('name')->get();

        return view('seller.products.form', compact('product','rootCategories','countries'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'title'=>'required|string|max:255',
            'price'=>'required|numeric|min:0',
            'stock'=>'required|integer|min:0',
            'description'=>'nullable|string',
            'image'=>'nullable|image|max:4096',
            'gallery.*'=>'nullable|image|max:4096',
            'category_id'=>'required|exists:categories,id',
            'city_id'=>'required|exists:cities,id',
            'country_id'=>'nullable|exists:countries,id',
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

        return redirect()->route('seller.products.index')->with('success','Товар обновлён');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        $this->productService->delete($product);

        return redirect()->route('seller.products.index')->with('success','Товар удалён');
    }
}
