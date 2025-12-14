<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\AttributeService;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Http\Requests\Seller\ProductStoreRequest;
use App\Http\Requests\Seller\ProductUpdateRequest;

class ProductManageController extends Controller
{
    public function __construct(
        protected ProductService   $products,
        protected AttributeService $attributes,
        protected CurrencyService  $currency,
    ) {}

    /** 📋 Список товаров продавца */
public function index(Request $request)
{
    $period = (int) $request->get('period', 7);

    $to   = $request->get('to', now()->toDateString());
    $from = $request->get('from', now()->subDays($period)->toDateString());

    /* =========================
     | СВОДКА ЗА ПЕРИОД
     ========================= */
    $summary = DB::table('product_stats')
        ->join('products', 'products.id', '=', 'product_stats.product_id')
        ->where('products.user_id', Auth::id())
        ->whereBetween('product_stats.date', [$from, $to])
        ->selectRaw('
            COALESCE(SUM(views),0) as views,
            COALESCE(SUM(favorites),0) as favorites,
            COALESCE(SUM(carts),0) as carts
        ')
        ->first();

    /* =========================
     | ПРЕДЫДУЩИЙ ПЕРИОД
     ========================= */
    $days = Carbon::parse($from)->diffInDays($to) + 1;

    $prevFrom = Carbon::parse($from)->subDays($days)->toDateString();
    $prevTo   = Carbon::parse($from)->subDay()->toDateString();

    $prev = DB::table('product_stats')
        ->join('products', 'products.id', '=', 'product_stats.product_id')
        ->where('products.user_id', Auth::id())
        ->whereBetween('product_stats.date', [$prevFrom, $prevTo])
        ->selectRaw('
            COALESCE(SUM(views),0) as views,
            COALESCE(SUM(favorites),0) as favorites,
            COALESCE(SUM(carts),0) as carts
        ')
        ->first();

/* =========================
 | ТОВАРЫ + ПРОСМОТРЫ
 ========================= */
$products = Product::where('user_id', Auth::id())
    ->with(['category', 'city.country'])
    ->withSum(['stats as views_sum' => function ($q) use ($from, $to) {
        $q->whereBetween('date', [$from, $to]);
    }], 'views')
    ->latest()
    ->paginate(20);

/* =========================
 | НОВЫЕ ТОВАРЫ ЗА ПЕРИОД
 ========================= */
$newProductsCount = Product::where('user_id', Auth::id())
    ->whereBetween('created_at', [
        Carbon::parse($from)->startOfDay(),
        Carbon::parse($to)->endOfDay()
    ])
    ->count();

/* =========================
 | АКТИВНОСТЬ ПРОДАВЦА
 ========================= */
$totalProducts = max($products->total(), 1);

$rawScore =
    ($summary->views ?? 0) +
    ($summary->favorites ?? 0) * 3 +
    ($summary->carts ?? 0) * 5;

$maxScore = $totalProducts * 50;

$activityPercent = min(100, round(($rawScore / $maxScore) * 100));

/* =========================
 | VIEW
 ========================= */
return view('seller.products.index', compact(
    'products',
    'summary',
    'prev',
    'from',
    'to',
    'activityPercent',
    'newProductsCount'
));

}


    /** ➕ Создать товар */
    public function create()
    {
        return $this->formView(new Product());
    }

    /** ✏️ Редактировать товар */
    public function edit(Product $product)
    {
        $this->authorize('update', $product);
        return $this->formView($product);
    }

    /** 🧩 Общая форма */
    protected function formView(Product $product)
    {
        $rootCategories = Cache::remember('root_categories', 3600, fn() =>
            Category::whereNull('parent_id')->orderBy('name')->get()
        );

        $countries = Cache::remember('countries_list', 3600, fn() =>
            Country::orderBy('name')->get()
        );

        // Цепочка родителей категорий
        $categoryChain = collect();
        if ($product->category_id) {
            $cat = Category::with('parent')->find($product->category_id);
            while ($cat) {
                $categoryChain->prepend($cat);
                $cat = $cat->parent;
            }
        }

        // Атрибуты (если редактирование – подставляются значения)
        $attributes = $product->exists
            ? $this->attributes->getForProduct($product)
            : collect();

            $categoryMissing = !$product->category_id;


        $categoriesTree = Cache::remember('all_categories_tree', 3600, fn() =>
            Category::select('id', 'name', 'parent_id')->orderBy('name')->get()
        );

        return view('seller.products.form', compact(
            'product',
            'rootCategories',
            'categoryChain',
            'countries',
            'categoriesTree',
            'attributes',
            'categoryMissing'
        ));

    }

    /** ⚡ AJAX: JSON атрибутов категории */
    public function getCategoryAttributes(Category $category)
    {
        return response()->json(
            $this->attributes->getByCategory($category->id)
        );
    }

    /** ⚡ AJAX partial HTML атрибутов */
    public function getCategoryAttributesView(Category $category)
    {
        $product = new Product(['category_id' => $category->id]);
        return view('seller.products.partials.attributes', compact('product'));
    }

    /** 💾 Создание товара */
    public function store(ProductStoreRequest $request)
    {
        $data          = $request->validated();
        $data['user_id'] = Auth::id();

        // Определяем валюту
        $city = City::with('country')->findOrFail($data['city_id']);
        $data['currency_base'] = $city->country->currency ?? 'MDL';

        // Цены
        $data['price']      = $request->price;
        $data['price_prb']  = $request->price_prb;
        $data['price_mdl']  = $request->price_mdl;
        $data['price_uah']  = $request->price_uah;

        // Авто-конвертация пропущенных цен
        foreach (['PRB'=>'price_prb','MDL'=>'price_mdl','UAH'=>'price_uah'] as $code => $field) {
            if (is_null($data[$field])) {
                $data[$field] = $this->currency->convert(
                    (float)$data['price'],
                    $data['currency_base'],
                    $code
                );
            }
        }

        // Файлы
        $image   = $request->file('image');
        $gallery = $request->file('gallery', []);
        $attrs   = $request->input('attributes', []);

        // Создание
        $this->products->create($data, $image, $gallery, $attrs);

        return redirect()
            ->route('seller.products.index')
            ->with('success', '✅ Товар создан');
    }

    /** 🔄 Обновление товара */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        // Цены (берем явно)
        $data['price']      = $request->price;
        $data['price_prb']  = $request->price_prb;
        $data['price_mdl']  = $request->price_mdl;
        $data['price_uah']  = $request->price_uah;

        $image           = $request->file('image');
        $galleryNew      = $request->file('gallery', []);
        $galleryToDelete = $request->input('delete_gallery', []);
        $attrs           = $request->input('attributes', []);

        $this->products->update(
            $product,
            $data,
            $image,
            $galleryNew,
            $galleryToDelete,
            $attrs
        );

        return redirect()
            ->route('seller.products.index')
            ->with('success', '✅ Изменения сохранены');
    }

    /** 🗑 Удаление товара */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        $this->products->delete($product);

        return back()->with('success', '🗑️ Товар удалён');
    }

    /** 🧽 AJAX удаление фото */
    public function deleteGalleryImage(Product $product, Request $request)
    {
        $this->authorize('update', $product);

        $this->products->deleteFromGallery($product, $request->path);

        return response()->json(['success' => true]);
    }
}
