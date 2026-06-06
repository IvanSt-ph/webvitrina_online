<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    /* ============================================================
     |  КАТАЛОГ ТОВАРОВ ДЛЯ АДМИНИСТРАТОРА
     ============================================================ */
    public function getFilteredAdminProducts($request)
    {
        $query = Product::query()
            ->with([
                'category',
                'seller.shop',
                'city.country',
            ]);

        if ($request->filled('q')) {
            $this->applySearchFilter($query, $request->q);
        }

        if ($request->filled('status') && in_array($request->status, Product::statuses(), true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        if ($request->filled('seller_id')) {
            $query->where('user_id', (int) $request->seller_id);
        }

        match ($request->get('stock')) {
            'out' => $query->where('stock', 0),
            'low' => $query->whereBetween('stock', [1, 5]),
            'available' => $query->where('stock', '>', 5),
            default => null,
        };

        if ($request->boolean('discount')) {
            $query->onSale();
        }

        match ($request->get('sort', 'latest')) {
            'oldest' => $query->oldest('id'),
            'price_asc' => $query->orderBy('price')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            'stock_asc' => $query->orderBy('stock')->orderByDesc('id'),
            'views_desc' => $query->orderByDesc('views_count')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        return $query->paginate(20)->withQueryString();
    }

    /* ============================================================
     |  ФИЛЬТРАЦИЯ ТОВАРОВ ДЛЯ ВИТРИНЫ
     ============================================================ */
    public function getFilteredProducts($request)
    {
        return $this->buildFilteredProductsQuery($request);
    }
    
    /**
     * Построение запроса фильтрации
     */
    private function buildFilteredProductsQuery($request)
    {
        $query = Product::query()
            ->active()
            ->with([
                'category',
                'seller.shop',
                'city.country',
            ])
            ->withAvg([
                'reviews as reviews_avg_rating' => function ($q) {
                    $q->where('status', 'approved');
                },
            ], 'rating')
            ->withCount([
                'reviews as reviews_count' => function ($q) {
                    $q->where('status', 'approved');
                },
            ]);

        /*
         |----------------------------------------------------------------
         | Количество конкретного товара в корзине пользователя
         |----------------------------------------------------------------
        */
        if (auth()->check()) {
            $query->withSum([
                'cartItems as cart_quantity' => function ($q) {
                    $q->where('user_id', auth()->id());
                }
            ], 'qty');

            $query->withExists([
                'favorites as is_favorited' => function ($q) {
                    $q->where('user_id', auth()->id());
                },
            ]);
        }

        /* ======================
         | ФИЛЬТРЫ
         ====================== */

        // 🔎 Поиск (УМНЫЙ - масштабируется под объем)
        if ($request->filled('q')) {
            $this->applySearchFilter($query, $request->q);
        }

        // 📦 Продавец
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->user_id);
        }

        // 🌍 Страна
        if ($request->filled('country_id')) {
            $query->whereHas('city', function ($q) use ($request) {
                $q->where('country_id', (int) $request->country_id);
            });
        }

        // 🏙 Город
        if ($request->filled('city_id')) {
            $query->where('city_id', (int) $request->city_id);
        }

        // 📂 Категория
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        /* ======================
         | СОРТИРОВКА
         ====================== */

        $this->applySorting($query, $request);

        $perPage = min((int) $request->get('per_page', 20), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Умный поиск с адаптацией под объем данных
     */
    private function applySearchFilter($query, $searchTerm)
    {
        $search = trim($searchTerm);
        $search = mb_substr(strip_tags($search), 0, 100);
        $escapedSearch = $this->escapeLike($search);
        
        // Минимальная длина поиска
        if (mb_strlen($search) < 2) {
            return;
        }
        
        // Счётчик нужен для выбора стратегии поиска. Держим его живым, чтобы поиск не
        // уходил в неверную ветку после массовых изменений каталога.
        $totalProducts = Product::count();
        
        $query->where(function ($q) use ($search, $escapedSearch, $totalProducts) {
            
            // ВСЕГДА: точное совпадение по артикулу (самое быстрое)
            $q->orWhere('sku', $search);
            
            // ВСЕГДА: поиск по названию (индексируется)
            $q->orWhere('title', 'like', $escapedSearch . '%');
            
            // Если товаров МАЛО (< 20 000) - ищем везде
            if ($totalProducts < 20000) {
                $q->orWhere('title', 'like', '%' . $escapedSearch . '%');

                // Поиск по описанию
                $q->orWhere('description', 'like', '%' . $escapedSearch . '%');
                
                // Поиск по категории
                $q->orWhereHas('category', function ($cat) use ($escapedSearch) {
                    $cat->where('name', 'like', '%' . $escapedSearch . '%');
                });
                
                // Поиск по продавцу подключаем только для более явного запроса,
                // иначе короткие слова случайно совпадают с именем продавца и размывают товарную выдачу.
                if (mb_strlen($search) > 3) {
                    $q->orWhereHas('seller', function ($seller) use ($escapedSearch) {
                        $seller->where('name', 'like', '%' . $escapedSearch . '%');
                    });
                }
            } 
            // Если товаров МНОГО - умное ограничение
            else {
                if ($this->supportsFullTextSearch()) {
                    $q->orWhereFullText('title', $search);
                } else {
                    $q->orWhere('title', 'like', '%' . $escapedSearch . '%');
                }

                // Для длинных запросов (> 3 символов) добавляем описание
                if (mb_strlen($search) > 3) {
                    $q->orWhere('description', 'like', '%' . $escapedSearch . '%');
                }
                
                // Поиск по категории ТОЛЬКО если запрос похож на название категории
                if ($this->looksLikeCategory($search)) {
                    $q->orWhereHas('category', function ($cat) use ($escapedSearch) {
                        $cat->where('name', 'like', '%' . $escapedSearch . '%');
                    });
                }
                
                // Поиск по продавцу ТОЛЬКО если запрос похож на имя
                if ($this->looksLikeName($search)) {
                    $q->orWhereHas('seller', function ($seller) use ($escapedSearch) {
                        $seller->where('name', 'like', '%' . $escapedSearch . '%');
                    });
                }
            }
        });
        
        // Приоритет точного SKU
        $query->orderByRaw("CASE WHEN sku = ? THEN 0 ELSE 1 END", [$search]);
    }

    /**
     * Применение сортировки
     */
    private function applySorting($query, $request)
    {
        match ($request->get('sort', 'new')) {
            'price_asc'  => $query->orderBy('price', 'asc')->orderBy('id'),
            'price_desc' => $query->orderBy('price', 'desc')->orderBy('id'),
            'rating'     => $query->orderByDesc('reviews_avg_rating')->orderBy('id'),
            'benefit'    => $query->orderByRaw('(price / GREATEST(stock, 1)) ASC')->orderBy('id'),
            default      => $query->latest('id'), // сортировка по ID быстрее
        };
    }

    /* ============================================================
     |  ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     ============================================================ */
    
    /**
     * Экранирование спецсимволов для LIKE запроса
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function supportsFullTextSearch(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    /**
     * Проверка, похож ли запрос на категорию
     */
    private function looksLikeCategory(string $search): bool
    {
        $categoryKeywords = ['категор', 'раздел', 'тип', 'вид', 'сорт', 'класс'];
        foreach ($categoryKeywords as $keyword) {
            if (mb_stripos($search, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверка, похож ли запрос на имя
     */
    private function looksLikeName(string $search): bool
    {
        // Имена обычно не содержат цифр и спецсимволов
        return preg_match('/^[а-яА-Яa-zA-Z\s-]+$/u', $search) && mb_strlen($search) > 3;
    }

    /* ============================================================
     |  ПОЛУЧЕНИЕ ТОВАРА ПО SLUG ИЛИ ID
     ============================================================ */
    public function getProductBySlugOrId(string|int $key)
    {
        // Если передали ID — редирект на slug
        if (is_numeric($key)) {
            $product = Product::with([
                'city.country',
                'category.parent',
                'seller.shop',
            ])->active()->find($key);

            if ($product) {
                return redirect()->route('product.show', $product->slug, 301);
            }
        }

        $cacheKey = "product_page:{$key}";

        return Cache::remember($cacheKey, 600, function () use ($key) {

            $product = Product::with([
                'city.country',
                'category.parent',
                'seller.shop',
                'attributes.colors',
            ])
            ->withCount([
                'reviews as reviews_count' => function ($q) {
                    $q->where('status', 'approved');
                },
            ])
            ->withAvg([
                'reviews as reviews_avg_rating' => function ($q) {
                    $q->where('status', 'approved');
                },
            ], 'rating')
            ->active()
            ->where('slug', $key)
            ->first();

            if (!$product) {
                $old = \App\Models\ProductSlug::where('slug', $key)->first();

                if ($old && $old->product && $old->product->status === 'active') {
                    return redirect()->route('product.show', $old->product->slug, 301);
                }

                abort(404);
            }

            return $product;
        });
    }

    /* ============================================================
     |  ПОХОЖИЕ ТОВАРЫ
     ============================================================ */
    public function getRelatedProducts(Product $product)
    {
        return Cache::remember("related:{$product->id}", 600, function () use ($product) {

            return Product::query()
                ->active()
                ->select('id', 'user_id', 'category_id', 'city_id', 'slug', 'title', 'price', 'price_prb', 'price_mdl', 'price_uah', 'currency_base', 'image')
                ->with('category')
                ->withCount([
                    'reviews as reviews_count' => function ($q) {
                        $q->where('status', 'approved');
                    },
                ])
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->limit(4)
                ->get();
        });
    }

    /* ============================================================
     |  ОЧИСТКА КЭША
     ============================================================ */
    public function clearCache(Product $product): void
    {
        self::clearProductCache($product);
    }

    public static function clearProductCache(Product $product): void
    {
        $slugs = collect([
            $product->slug,
            $product->getOriginal('slug'),
        ])
            ->merge($product->oldSlugs()->pluck('slug'))
            ->filter()
            ->unique();

        foreach ($slugs as $slug) {
            Cache::forget("product_page:{$slug}");
            Cache::forget("product_by_slug:{$slug}");
        }

        Cache::forget("related:{$product->id}");
        Cache::forget("product_page:{$product->id}");
        Cache::forget("product_by_id:{$product->id}");
    }
}
