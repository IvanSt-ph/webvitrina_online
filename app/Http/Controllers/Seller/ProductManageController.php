<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductManageController extends Controller
{
    public function __construct(
        protected ProductService $products
    ) {}

    /** 📋 Список товаров продавца */
    public function index()
    {
        $sellerId = Auth::id();

        $products = Product::query()
            ->where('user_id', $sellerId)
            ->with(['category', 'city.country'])
            ->latest('created_at')
            ->paginate(20);

        return view('seller.products.index', compact('products'));
    }

    /** ➕ Форма создания товара */
    public function create()
    {
        return $this->formView(new Product());
    }

    /** ✏️ Форма редактирования товара */
    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        return $this->formView($product);
    }

    /** ⚡ AJAX: атрибуты по выбранной категории (JSON) */
    public function getCategoryAttributes(Category $category)
    {
        $attributes = Attribute::query()
            ->whereIn('id', function ($q) use ($category) {
                $q->select('attribute_id')
                    ->from('attribute_category')
                    ->where('category_id', $category->id);
            })
            ->orderBy('name')
            ->get()
            ->map(function (Attribute $attr) {
                // приведение options к массиву
                if (is_array($attr->options)) {
                    // уже массив
                } elseif (is_string($attr->options) && $attr->options !== '') {
                    $attr->options = json_decode($attr->options, true) ?? [];
                } else {
                    $attr->options = [];
                }

                return $attr;
            });

        return response()->json($attributes);
    }

    /** ⚡ AJAX: partial с полями атрибутов для Blade */
    public function getCategoryAttributesView(Category $category)
    {
        $product = new Product(['category_id' => $category->id]);

        return view('seller.products.partials.attributes', compact('product'));
    }

    /**
     * 🧩 Общая форма для create/edit
     * Здесь только подготовка данных для шаблона, никакой тяжёлой логики.
     */
    protected function formView(Product $product)
    {
        // 🌲 Корневые категории
        $rootCategories = Cache::remember(
            'root_categories',
            3600,
            fn () => Category::query()
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get()
        );

        // 🌍 Список стран
        $countries = Cache::remember(
            'countries_list',
            3600,
            fn () => Country::query()
                ->orderBy('name')
                ->get()
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

        // ⚡ Все категории для фронта
        $categoriesTree = Cache::remember(
            'all_categories_tree',
            3600,
            fn () => Category::query()
                ->select('id', 'name', 'parent_id')
                ->orderBy('name')
                ->get()
        );

        // 🧩 Атрибуты текущей категории (если есть)
        $attributes = collect();
        if ($product->category_id) {
            // подгружаем значения атрибутов одним запросом
            $product->loadMissing('attributeValues');

            $attributes = Attribute::query()
                ->whereIn('id', function ($q) use ($product) {
                    $q->select('attribute_id')
                        ->from('attribute_category')
                        ->where('category_id', $product->category_id);
                })
                ->orderBy('name')
                ->get()
                ->map(function (Attribute $attr) use ($product) {
                    // приведение options к массиву
                    if (is_array($attr->options)) {
                        // уже массив
                    } elseif (is_string($attr->options) && $attr->options !== '') {
                        $attr->options = json_decode($attr->options, true) ?? [];
                    } else {
                        $attr->options = [];
                    }

                    // сохранённое значение для edit
                    $attr->value = optional(
                        $product->attributeValues->firstWhere('attribute_id', $attr->id)
                    )->value;

                    return $attr;
                });
        }

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
        $data   = $this->validateProduct($request);
        $userId = Auth::id();

        // 🖼 Файлы
        $image   = $request->file('image');
        $gallery = $request->file('gallery', []);
        $attrs   = $request->input('attributes', []);

        // Определяем базовую валюту по городу
        $city = City::with('country')->findOrFail($data['city_id']);
        $currencyBase = $city->country->currency ?? 'MDL';

        // Собираем объект, чтобы не сломать текущий ProductService::store
        $product = new Product($data);
        $product->user_id       = $userId;
        $product->currency_base = $currencyBase;

        // Цены в разных валютах (как у тебя было)
        $product->price      = $request->input('price');
        $product->price_prb  = $request->input('price_prb');
        $product->price_mdl  = $request->input('price_mdl');
        $product->price_uah  = $request->input('price_uah');

        // 💱 Автоматический пересчёт, если какие-то валюты не заданы
        $svc = app(\App\Services\CurrencyService::class);
        $map = ['PRB' => 'price_prb', 'MDL' => 'price_mdl', 'UAH' => 'price_uah'];

        foreach ($map as $code => $field) {
            if (is_null($product->{$field})) {
                $product->{$field} = $svc->convert(
                    (float) $product->price,
                    $currencyBase,
                    $code
                );
            }
        }

        // 🧠 Сохраняем товар через сервис
        $product = $this->products->store(
            $product->toArray(),
            $image,
            $gallery,
            $userId
        );

        // 🧬 Сохраняем атрибуты
        $this->syncAttributes($product, $attrs);

        return redirect()
            ->route('seller.products.index')
            ->with('success', '✅ Товар добавлен');
    }

    /** 🔄 Обновление существующего товара */
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $data           = $this->validateProduct($request);
        $image          = $request->file('image');
        $galleryNew     = $request->file('gallery', []);
        $galleryToDelete = $request->input('delete_gallery', []);
        $attrs          = $request->input('attributes', []);

        // Обновляем товар через сервис
        $product = $this->products->update(
            $product,
            $data,
            $image,
            $galleryNew,
            $galleryToDelete
        );

        // Перезаписываем атрибуты
        $this->syncAttributes($product, $attrs);

        return redirect()
            ->route('seller.products.index')
            ->with('success', '✅ Изменения сохранены');
    }

    /** 🗑️ Полное удаление товара продавцом */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        DB::transaction(function () use ($product) {
            // Удаляем связанные данные
            DB::table('attribute_values')->where('product_id', $product->id)->delete();
            DB::table('favorites')->where('product_id', $product->id)->delete();
            DB::table('cart_items')->where('product_id', $product->id)->delete();

            // Удаляем файлы + сам товар через сервис
            $this->products->delete($product);
        });

        return back()->with('success', '🗑️ Товар удалён полностью');
    }

    /** 🧽 Удаление одного фото из галереи (AJAX) */
    public function deleteGalleryImage(Product $product, Request $request)
    {
        $this->authorize('update', $product);

        $path = $request->input('path');
        $this->products->deleteFromGallery($product, [$path]);

        return response()->json(['success' => true]);
    }

    /** ✅ Валидация общих полей товара */
    protected function validateProduct(Request $request): array
    {
        return $request->validate([
            'title'       => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',

            'category_id' => 'required|exists:categories,id',
            'country_id'  => 'required|exists:countries,id',
            'city_id'     => 'required|exists:cities,id',

            'address'   => 'nullable|string|max:255',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'image'     => 'nullable|image|max:4096',
            'gallery.*' => 'nullable|image|max:4096',
        ]);
    }

    /**
     * 🧬 Синхронизация атрибутов товара
     * Полностью пересобираем attribute_values для товара.
     */
    protected function syncAttributes(Product $product, array $attributes): void
    {
        // удаляем старые
        DB::table('attribute_values')
            ->where('product_id', $product->id)
            ->delete();

        if (empty($attributes)) {
            return;
        }

        $rows = [];
        foreach ($attributes as $attrId => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            if ($value === null || $value === '') {
                continue;
            }

            $rows[] = [
                'product_id'   => $product->id,
                'attribute_id' => (int) $attrId,
                'value'        => (string) $value,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        if ($rows) {
            DB::table('attribute_values')->insert($rows);
        }
    }
}
