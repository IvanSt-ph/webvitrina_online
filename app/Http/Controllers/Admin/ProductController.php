<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** Список товаров */
    public function index()
    {
        $products   = Product::with('category', 'seller', 'city', 'country')
            ->orderByDesc('created_at')
            ->paginate(20);

        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /** Форма создания */
    public function create()
    {
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $sellers    = User::where('role', 'seller')->orderBy('name')->get();
        $countries  = Country::orderBy('name')->get();
        $cities     = collect(); // при создании города пустые, подтянутся через AJAX

        return view('admin.products.create', compact('categories', 'sellers', 'countries', 'cities'));
    }

    /** Сохранение нового товара */
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
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $file) {
                $gallery[] = $file->store('products', 'public');
            }
            $data['gallery'] = $gallery;
        }

        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Товар успешно создан.');
    }

    /** Форма редактирования */
    public function edit(Product $product)
    {
        $categories = Category::whereNull('parent_id')->orderBy('name')->get();
        $sellers    = User::where('role', 'seller')->orderBy('name')->get();
        $countries  = Country::orderBy('name')->get();

        // если у товара есть страна — загрузим только её города
        $cities = $product->country_id
            ? City::where('country_id', $product->country_id)->orderBy('name')->get()
            : collect();

        return view('admin.products.edit', compact(
            'product', 'categories', 'sellers', 'countries', 'cities'
        ));
    }

    /** Обновление товара */
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
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $file) {
                $gallery[] = $file->store('products', 'public');
            }
            $data['gallery'] = $gallery;
        }

        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Товар успешно обновлён.');
    }

    /** Удаление товара */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Товар удалён.');
    }
}
