<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Country;
use App\Models\City;
use App\Models\Attribute;
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



/** ⚡ AJAX: атрибуты по выбранной категории */
public function getCategoryAttributes(Category $category)
{
    $attributes = \App\Models\Attribute::whereIn('id', function ($q) use ($category) {
            $q->select('attribute_id')
              ->from('attribute_category')
              ->where('category_id', $category->id);
        })
        ->orderBy('name')
        ->get()
        ->map(function ($attr) {
            // ✅ Исправленный блок
            if (is_array($attr->options)) {
                // уже массив — ничего не делаем
                $attr->options = $attr->options;
            } elseif (is_string($attr->options) && $attr->options !== '') {
                // строка — пробуем декодировать
                $attr->options = json_decode($attr->options, true) ?? [];
            } else {
                // пусто или null
                $attr->options = [];
            }
            return $attr;
        });

    return response()->json($attributes);
}


    /** ⚡ Возврат готового HTML-partial для Blade (используется при смене категории без перезагрузки) */
public function getCategoryAttributesView(Category $category)
{
    $product = new \App\Models\Product(['category_id' => $category->id]);
    return view('seller.products.partials.attributes', compact('product'));
}



    /**
     * 🧩 Общая форма для create/edit
     * Загружает все нужные справочники и, если категория выбрана — подгружает атрибуты.
     */
    protected function formView(Product $product)
    {
        // 🌲 Корневые категории
        $rootCategories = Cache::remember('root_categories', 3600, fn() =>
            Category::whereNull('parent_id')->orderBy('name')->get()
        );

        // 🌍 Список стран
        $countries = Cache::remember('countries_list', 3600, fn() =>
            Country::orderBy('name')->get()
        );

        // 🔗 Цепочка категорий от текущей вверх (для отображения подкатегорий)
        $categoryChain = collect();
        if ($product->category_id) {
            $cat = Category::with('parent')->find($product->category_id);
            while ($cat) {
                $categoryChain->prepend($cat);
                $cat = $cat->parent;
            }
        }

        // ⚡ Все категории одним списком (для клиентской фильтрации)
        $categoriesTree = Cache::remember('all_categories_tree', 3600, fn() =>
            Category::select('id', 'name', 'parent_id')->orderBy('name')->get()
        );

        // 🧩 Атрибуты текущей категории
$attributes = Attribute::whereIn('id', function ($q) use ($product) {
        $q->select('attribute_id')
          ->from('attribute_category')
          ->where('category_id', $product->category_id);
    })
    ->orderBy('name')
    ->get()
    ->map(function ($attr) use ($product) {

        // ✅ безопасная обработка options
        if (is_array($attr->options)) {
            $attr->options = $attr->options;
        } elseif (is_string($attr->options) && $attr->options !== '') {
            $attr->options = json_decode($attr->options, true) ?? [];
        } else {
            $attr->options = [];
        }

        // Подставляем сохранённое значение, если редактируется товар
        $attr->value = optional($product->attributeValues->firstWhere('attribute_id', $attr->id))->value;

        return $attr;
    });

        

        // 🖼 Отдаём все данные во view
        return view('seller.products.form', compact(
            'product',
            'rootCategories',
            'categoryChain',
            'countries',
            'categoriesTree',
            'attributes'
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

    // 💱 Автоматический пересчёт цен
    $svc = app(\App\Services\CurrencyService::class);
    $map = ['PRB' => 'price_prb', 'MDL' => 'price_mdl', 'UAH' => 'price_uah'];
    foreach ($map as $code => $field) {
        if (is_null($product->{$field})) {
            $product->{$field} = $svc->convert((float)$product->price, $currencyBase, $code);
        }
    }

    // 🖼 Изображения
    $image   = $request->file('image');
    $gallery = $request->file('gallery', []);

    // 🧠 Сохраняем товар через сервис
    $product = $this->products->store($product->toArray(), $image, $gallery, $userId);

    // ✅ Теперь сохраняем характеристики
    if ($request->filled('attributes')) {
        $rows = [];
        foreach ($request->input('attributes') as $attrId => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            if ($value === null || $value === '') continue;

            $rows[] = [
                'product_id'   => $product->id,
                'attribute_id' => (int)$attrId,
                'value'        => (string)$value,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        if ($rows) {
            \DB::table('attribute_values')->insert($rows);
        }
    }

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

    // ✅ Обновляем характеристики
    \DB::table('attribute_values')->where('product_id', $product->id)->delete();

    if ($request->filled('attributes')) {
        $rows = [];
        foreach ($request->input('attributes') as $attrId => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            if ($value === null || $value === '') continue;

            $rows[] = [
                'product_id'   => $product->id,
                'attribute_id' => (int)$attrId,
                'value'        => (string)$value,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        if ($rows) {
            \DB::table('attribute_values')->insert($rows);
        }
    }

    return redirect()->route('seller.products.index')->with('success', '✅ Изменения сохранены');
}

public function destroy(Product $product)
{
    $this->authorize('delete', $product);

    // 💥 Физическое удаление
    if ($product->image && \Storage::disk('public')->exists($product->image)) {
        \Storage::disk('public')->delete($product->image);
    }

    if (is_array($product->gallery)) {
        foreach ($product->gallery as $path) {
            if ($path && \Storage::disk('public')->exists($path)) {
                \Storage::disk('public')->delete($path);
            }
        }
    }

    // Удаляем связанные данные
    \DB::table('attribute_values')->where('product_id', $product->id)->delete();
    \DB::table('favorites')->where('product_id', $product->id)->delete();
    \DB::table('cart_items')->where('product_id', $product->id)->delete();

    $product->forceDelete();

    return back()->with('success', '🗑️ Товар удалён полностью');
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
