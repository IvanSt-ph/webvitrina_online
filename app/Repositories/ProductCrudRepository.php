<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductCrudRepository
{
    /** 🔍 Найти товар */
    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    /** 💾 Создать товар */
    public function create(array $data): Product
    {
        $product = Product::create($data);

        $this->clearCache($product);

        return $product;
    }

    /** ✏️ Обновить товар */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        $this->clearCache($product);

        return $product;
    }

    /** 🗑 Удалить товар */
    public function delete(Product $product): void
    {
        $this->clearCache($product);

        $product->delete();
    }

    /** 🧹 Полная очистка кеша */
    public function clearCache(Product $product): void
    {
        Cache::forget("product_page:{$product->slug}");
        Cache::forget("related:{$product->id}");
        Cache::forget("product_by_id:{$product->id}");
        Cache::forget("product_by_slug:{$product->slug}");
    }
}
