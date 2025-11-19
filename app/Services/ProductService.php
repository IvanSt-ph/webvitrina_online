<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSlug;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    /* ==========================================================
     |  СОЗДАНИЕ ТОВАРА
     ========================================================== */
    public function store(array $data, ?UploadedFile $image = null, array $galleryFiles = [], ?int $userId = null): Product
    {
        return DB::transaction(function () use ($data, $image, $galleryFiles, $userId) {

            $payload = $this->prepareData($data, $userId);

            // Фото
            if ($image instanceof UploadedFile) {
                $payload['image'] = $this->uploadImage($image, 'products/' . date('Y/m'));
            }

            // SKU
            if (empty($payload['sku'])) {
                do {
                    $sku = 'PRD-' . random_int(10000, 99999);
                } while (Product::where('sku', $sku)->exists());
                $payload['sku'] = $sku;
            }

            // Создаём товар
            $product = Product::create($payload);

            // Галерея
            if (!empty($galleryFiles)) {
                $this->appendGallery($product, $galleryFiles);
            }

            return $product;
        });
    }

    /* ==========================================================
     |  ОБНОВЛЕНИЕ ТОВАРА
     ========================================================== */
    public function update(Product $product, array $data, ?UploadedFile $image = null, array $galleryNew = [], array $galleryToDelete = []): Product
    {
        return DB::transaction(function () use ($product, $data, $image, $galleryNew, $galleryToDelete) {

            // 🔥 Берём данные строго из формы
            $payload = $this->prepareData($data, null, updating: true);

            /* ---------- SLUG ЛОГИКА ---------- */

            if (!empty($payload['slug']) && $payload['slug'] !== $product->slug) {
                ProductSlug::create([
                    'product_id' => $product->id,
                    'slug'       => $product->slug,
                ]);
            }

            if (empty($payload['slug'])) {
                $payload['slug'] = $product->slug;
            }

            /* ---------- Фото товара ---------- */

            if ($image instanceof UploadedFile) {
                $this->deletePath($product->image);
                $payload['image'] = $this->uploadImage($image, 'products/' . date('Y/m'));
            }

            /* ---------- Обновление ---------- */

            $product->update($payload);

            Cache::forget("product_by_slug:{$product->slug}");
            Cache::forget("product_by_id:{$product->id}");

            /* ---------- Галерея ---------- */

            if (!empty($galleryToDelete)) {
                $this->deleteFromGallery($product, $galleryToDelete);
            }

            if (!empty($galleryNew)) {
                $this->appendGallery($product, $galleryNew);
            }

            return $product;
        });
    }

    /* ==========================================================
     |  УДАЛЕНИЕ ТОВАРА
     ========================================================== */
    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $this->deletePath($product->image);

            foreach ((array) $product->gallery as $path) {
                $this->deletePath($path);
            }

            $product->delete();
        });
    }

    /* ==========================================================
     |  ВСПОМОГАТЕЛЬНЫЕ
     ========================================================== */
    protected function prepareData(array $data, ?int $userId = null, bool $updating = false): array
    {
        $payload = Arr::only($data, [
            'title','slug','sku','price','stock','description',
            'category_id','city_id','country_id','address',
            'latitude','longitude','status','active','user_id'
        ]);

        // Если user_id не передан — подставляем запасной
        if (empty($payload['user_id']) && $userId) {
            $payload['user_id'] = $userId;
        }

        // Авто-slug
        if (empty($payload['slug']) && !empty($payload['title'])) {
            $payload['slug'] = Str::slug($payload['title']) . '-' . Str::random(5);
        }

        // Приведение типов
        if (isset($payload['price'])) $payload['price'] = (float)$payload['price'];
        if (isset($payload['stock'])) $payload['stock'] = (int)$payload['stock'];

        // При обновлении удаляем пустые поля
        if ($updating) {
            $payload = array_filter($payload, fn($v) => $v !== null && $v !== '');
        }

        return $payload;
    }

    protected function uploadImage(UploadedFile $file, string $dir): string
    {
        return $file->store($dir, 'public');
    }

    protected function deletePath(?string $path): void
    {
        if (!$path) return;

        $clean = trim($path);
        $clean = ltrim($clean, '/\\');

        if ($clean === '' || str_contains($clean, '[')) {
            return;
        }

        if (Storage::disk('public')->exists($clean)) {
            Storage::disk('public')->delete($clean);
        }
    }

    protected function appendGallery(Product $product, array $files): void
    {
        $gallery = (array)$product->gallery;

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $gallery[] = $this->uploadImage($file, 'products/gallery/' . date('Y/m'));
            }
        }

        $product->update(['gallery' => array_values(array_unique($gallery))]);
    }

    public function deleteFromGallery(Product $product, array $pathsToDelete): void
    {
        $gallery = (array)$product->gallery;

        foreach ($pathsToDelete as $path) {
            $clean = str_replace(['storage/', '/storage/'], '', trim($path));
            $this->deletePath($clean);

            $gallery = array_values(array_filter($gallery, fn($p) => $p !== $path && $p !== $clean));
        }

        $product->update(['gallery' => $gallery]);
    }
}
