<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductRepository
{
    /**
     * 🔍 УНИВЕРСАЛЬНАЯ ФИЛЬТРАЦИЯ ТОВАРОВ ДЛЯ ВИТРИНЫ
     * Этот метод используется на:
     *  - Главной витрине
     *  - Категориях
     *  - Поиске
     *  - Фильтрах продавцов
     *
     * Здесь обязательно нужен eager-loading (with()), иначе будут N+1 запросы.
     */
    public function getFilteredProducts($request)
    {
        // 🚀 Подгружаем ВСЁ, что нужно на витрине, одним запросом
        $query = Product::query()
            ->with([
                'category',          // Категория товара
                'seller',            // Продавец товара
                'city.country',      // Город + страна
                'reviews',           // Отзывы (без них рейтинг тормозит)
            ])
            ->withAvg([            // Средняя оценка товара
                'reviews as reviews_avg_rating' => fn($q) => $q->where('status', 'approved'),
            ], 'rating')
            ->withCount([          // Количество отзывов
                'reviews as reviews_count' => fn($q) => $q->where('status', 'approved'),
            ]);

        // 🔎 Поиск по названию товара
        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        // 📦 Фильтр по продавцу
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->user_id);
        }

        // 🌍 Фильтры по стране и городу
        if ($request->filled('country_id')) {
            $countryId = (int) $request->country_id;

            $query->whereHas('city', fn($q) => $q->where('country_id', $countryId));

            if ($request->filled('city_id')) {
                $query->where('city_id', (int) $request->city_id);
            }
        }

        // 📂 Конкретная категория
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        // 📊 Сортировки
        $sort = $request->get('sort', 'new');
        match ($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating'     => $query->orderBy('reviews_avg_rating', 'desc'),
            'benefit'    => $query->orderByRaw('(price / greatest(stock,1)) asc'),
            default      => $query->latest(),  // Новинки
        };

        // 📄 Пагинация
        return $query->paginate(
            (int) ($request->get('per_page', 20)) ?: 20
        )->withQueryString();
    }

    /**
     * 🧾 ПОЛУЧЕНИЕ ТОВАРА ПО SLUG ИЛИ ID
     * Используется на странице товара.
     * Здесь мы грузим:
     *  - город + страна
     *  - категорию + родителя
     *  - продавца
     *  - отзывы с пользователями
     *  - кол-во отзывов
     *  - средний рейтинг
     */
    public function getProductBySlugOrId($key)
    {
        // 💡 Если пользователь открыл товар по числовому ID, мы подтягиваем связи
        if (is_numeric($key)) {
            $product = Product::with([
                'city.country',
                'category.parent',
                'seller',
            ])->find($key);

            // 🔄 И делаем 301-редирект на корректный slug
            if ($product) {
                return redirect()->route('product.show', $product->slug, 301);
            }
        }

        // 🧠 Ключ для кэша
        $cacheKey = "product_by_slug:{$key}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($key) {

            // 🚀 Загружаем товар со всеми нужными связями
            $product = Product::with([
                'city.country',
                'category.parent',
                'seller',
                'reviews' => function ($q) {
                    $q->where('status', 'approved')
                      ->with(['user', 'images'])
                      ->latest();
                },
            ])
            ->withCount([
                'reviews as reviews_count' => fn($q) => $q->where('status', 'approved'),
            ])
            ->withAvg([
                'reviews as reviews_avg_rating' => fn($q) => $q->where('status', 'approved'),
            ], 'rating')
            ->where('slug', $key)
            ->first();

            // 🥲 Проверяем, может товар переехал (старый slug)
            if (!$product) {
                $oldSlug = \App\Models\ProductSlug::where('slug', $key)->first();

                if ($oldSlug && $oldSlug->product) {
                    return redirect()->route('product.show', $oldSlug->product->slug, 301);
                }

                abort(404); // ❌ Товар не найден
            }

            return $product;
        });
    }

    /**
     * 🔄 ПОЛУЧЕНИЕ ПОХОЖИХ ТОВАРОВ
     * Показывается на странице товара.
     * Грузим только нужные поля + категорию (иначе будет N+1).
     */
    public function getRelatedProducts($product)
    {
        return Cache::remember("related_products:{$product->id}", now()->addMinutes(10), function () use ($product) {
            return Product::select('id', 'slug', 'title', 'price', 'image')
                ->with('category')    // ❗ Без этого будет N+1
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->take(4)
                ->get();
        });
    }

    /**
     * 🧹 Очистка кэша товара (после изменения данных или отзыва)
     */
    public static function clearProductCache($product)
    {
        if (!$product) return;

        Cache::forget("product_by_slug:{$product->slug}");
        Cache::forget("related_products:{$product->id}");
    }
}
