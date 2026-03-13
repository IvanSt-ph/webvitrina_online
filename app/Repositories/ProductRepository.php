<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductRepository
{
    /* ============================================================
     |  ФИЛЬТРАЦИЯ ТОВАРОВ ДЛЯ ВИТРИНЫ
     ============================================================ */
    public function getFilteredProducts($request)
    {
        // Кэширование для популярных поисков (без пагинации)
        if ($request->filled('q') && !$request->filled('page') && $request->get('per_page', 20) == 20) {
            $cacheKey = 'search_' . md5($request->fullUrl());
            
            return Cache::remember($cacheKey, 300, function() use ($request) {
                return $this->buildFilteredProductsQuery($request);
            });
        }
        
        return $this->buildFilteredProductsQuery($request);
    }
    
    /**
     * Построение запроса фильтрации
     */
    private function buildFilteredProductsQuery($request)
    {
        $query = Product::query()
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
        
        // Минимальная длина поиска
        if (mb_strlen($search) < 2) {
            return;
        }
        
        // Получаем количество товаров (кешируем на 1 час)
        $totalProducts = Cache::remember('products_total_count', 3600, function() {
            return Product::count();
        });
        
        $query->where(function ($q) use ($search, $totalProducts) {
            
            // ВСЕГДА: точное совпадение по артикулу (самое быстрое)
            $q->orWhere('sku', $search);
            
            // ВСЕГДА: поиск по названию (индексируется)
            $q->orWhere('title', 'like', $this->escapeLike($search) . '%');
            $q->orWhere('title', 'like', '%' . $this->escapeLike($search) . '%');
            
            // Если товаров МАЛО (< 20 000) - ищем везде
            if ($totalProducts < 20000) {
                // Поиск по описанию
                $q->orWhere('description', 'like', '%' . $this->escapeLike($search) . '%');
                
                // Поиск по категории
                $q->orWhereHas('category', function ($cat) use ($search) {
                    $cat->where('name', 'like', '%' . $this->escapeLike($search) . '%');
                });
                
                // Поиск по продавцу
                $q->orWhereHas('seller', function ($seller) use ($search) {
                    $seller->where('name', 'like', '%' . $this->escapeLike($search) . '%');
                });
            } 
            // Если товаров МНОГО - умное ограничение
            else {
                // Для длинных запросов (> 3 символов) добавляем описание
                if (mb_strlen($search) > 3) {
                    $q->orWhere('description', 'like', '%' . $this->escapeLike($search) . '%');
                }
                
                // Поиск по категории ТОЛЬКО если запрос похож на название категории
                if ($this->looksLikeCategory($search)) {
                    $q->orWhereHas('category', function ($cat) use ($search) {
                        $cat->where('name', 'like', '%' . $this->escapeLike($search) . '%');
                    });
                }
                
                // Поиск по продавцу ТОЛЬКО если запрос похож на имя
                if ($this->looksLikeName($search)) {
                    $q->orWhereHas('seller', function ($seller) use ($search) {
                        $seller->where('name', 'like', '%' . $this->escapeLike($search) . '%');
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
            ])->find($key);

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
                'reviews' => function ($q) {
                    $q->where('status', 'approved')
                      ->with(['user', 'images'])
                      ->latest();
                }
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
            ->where('slug', $key)
            ->first();

            if (!$product) {
                $old = \App\Models\ProductSlug::where('slug', $key)->first();

                if ($old && $old->product) {
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
                ->select('id', 'slug', 'title', 'price', 'image')
                ->with('category')
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
        Cache::forget("product_page:{$product->slug}");
        Cache::forget("related:{$product->id}");
        Cache::forget("product_page:{$product->id}");
        
        // Очищаем кэш поиска (можно выборочно по префиксу)
        Cache::forget('products_total_count');
    }

    public static function clearProductCache(Product $product): void
    {
        Cache::forget("product_page:{$product->slug}");
        Cache::forget("related:{$product->id}");
        Cache::forget("product_page:{$product->id}");
        Cache::forget('products_total_count');
    }
}