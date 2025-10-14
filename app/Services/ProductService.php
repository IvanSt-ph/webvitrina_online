<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;


class ProductService
{
    /**
     * Создание нового товара.
     */
    public function store(array $data, ?UploadedFile $image = null, array $galleryFiles = [], ?int $userId = null): Product
    {
        return DB::transaction(function () use ($data, $image, $galleryFiles, $userId) {
            $payload = $this->prepareData($data, $userId);

            // главное фото
            if ($image instanceof UploadedFile) {
                $payload['image'] = $this->uploadImage($image, 'products/'.date('Y/m'));
            }

            // создаём товар
            /** @var Product $product */
            $product = Product::create($payload);

            // галерея
            if (!empty($galleryFiles)) {
                $this->appendGallery($product, $galleryFiles);
            }

            return $product;
        });
    }

    /**
     * Обновление товара.
     */
public function update(Product $product, array $data, ?UploadedFile $image = null, array $galleryNew = [], array $galleryToDelete = []): Product
{
    return DB::transaction(function () use ($product, $data, $image, $galleryNew, $galleryToDelete) {
        $payload = $this->prepareData($data, $product->user_id, updating: true);

        // 🔹 если slug изменился — сохраняем старый в таблицу product_slugs
        if (!empty($payload['slug']) && $payload['slug'] !== $product->slug) {
            \App\Models\ProductSlug::create([
                'product_id' => $product->id,
                'slug' => $product->slug,
            ]);
        }

        // 🔹 если slug не указан — сохраняем прежний
        if (empty($payload['slug']) && $product->slug) {
            $payload['slug'] = $product->slug;
        }

        // новое главное фото
        if ($image instanceof UploadedFile) {
            $this->deletePath($product->image);
            $payload['image'] = $this->uploadImage($image, 'products/'.date('Y/m'));
        }

        // обновляем запись
        $product->update($payload);
        // 🧹 Очищаем кэш, чтобы показать свежие данные
Cache::forget("product_by_slug:{$product->slug}");
Cache::forget("product_by_id:{$product->id}");


        // удаляем выбранные изображения из галереи
        if (!empty($galleryToDelete)) {
            $this->deleteFromGallery($product, $galleryToDelete);
        }

        // добавляем новые
        if (!empty($galleryNew)) {
            $this->appendGallery($product, $galleryNew);
        }

        return $product;
    });
}


    /**
     * Удаление товара с файлами.
     */
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

    // -----------------------------------------------
    // Вспомогательные
    // -----------------------------------------------

    protected function prepareData(array $data, ?int $userId = null, bool $updating = false): array
    {
        $payload = Arr::only($data, [
            'title','slug','price','stock','description',
            'category_id','city_id','country_id','address',
            'latitude','longitude','status','active','user_id'
        ]);

        if ($userId) $payload['user_id'] = $userId;

        if (empty($payload['slug']) && !empty($payload['title'])) {
            $payload['slug'] = Str::slug($payload['title']).'-'.Str::random(5);
        }

        if (isset($payload['price']))  $payload['price']  = (float) str_replace(',', '.', $payload['price']);
        if (isset($payload['stock']))  $payload['stock']  = (int) $payload['stock'];

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
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function appendGallery(Product $product, array $files): void
    {
        $gallery = (array) $product->gallery;

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $gallery[] = $this->uploadImage($file, 'products/gallery/'.date('Y/m'));
            }
        }

        $product->update(['gallery' => array_values(array_unique($gallery))]);
    }

    protected function deleteFromGallery(Product $product, array $pathsToDelete): void
    {
        $gallery = (array) $product->gallery;

        foreach ($pathsToDelete as $path) {
            $this->deletePath($path);
            $gallery = array_values(array_filter($gallery, fn($p) => $p !== $path));
        }

        $product->update(['gallery' => $gallery]);
    }
}
