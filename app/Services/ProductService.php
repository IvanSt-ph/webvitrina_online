<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSlug;
use App\Repositories\ProductCrudRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{

    public function __construct(
        protected ProductCrudRepository $repo,
        protected ImageService $images,
        protected AttributeService $attributes,
    ) {}

    /* ============================================================
     |  СОЗДАНИЕ ТОВАРА
     ============================================================ */
    public function create(array $data, ?UploadedFile $image = null, array $gallery = [], array $attrs = []): Product
    {
        return DB::transaction(function () use ($data, $image, $gallery, $attrs) {

            /* ---------- 1. Подготовка данных ---------- */
            $payload = $this->prepareData($data);

            /* ---------- 2. Генерация SKU ---------- */
            $payload['sku'] = $payload['sku'] ?? $this->generateSku();

            /* ---------- 3. Загрузка главного фото ---------- */
            if ($image) {
                $payload['image'] = $this->images->upload($image, 'products/' . date('Y/m'));
            }
            // Если нет изображения - оставляем null (НЕ сохраняем путь к no-image.png)

            /* ---------- 4. Создание товара ---------- */
            $product = $this->repo->create($payload);

            /* ---------- 5. Галерея ---------- */
            if ($gallery) {
                $this->appendGallery($product, $gallery);
            }

            /* ---------- 6. Атрибуты ---------- */
            if ($attrs) {
                $this->attributes->sync($product, $attrs);
            }

            return $product;
        });
    }

    /* ============================================================
     |  ОБНОВЛЕНИЕ ТОВАРА
     ============================================================ */
    public function update(Product $product, array $data, ?UploadedFile $image = null, array $galleryNew = [], array $galleryToDelete = [], array $attrs = []): Product
    {
        return DB::transaction(function () use ($product, $data, $image, $galleryNew, $galleryToDelete, $attrs) {

            /* ---------- 1. Подготовка данных ---------- */
            $payload = $this->prepareData($data, updating: true);

            /* ---------- 2. Сохранение старого slug ---------- */
            $this->handleSlugHistory($product, $payload);

            /* ---------- 3. Обновление главного фото ---------- */
            if ($image) {
                // Удаляем старое фото (защита в ImageService)
                $this->images->delete($product->image);
                $payload['image'] = $this->images->upload($image, 'products/' . date('Y/m'));
            }

            /* ---------- 4. Обновление товара ---------- */
            $product = $this->repo->update($product, $payload);

            /* ---------- 5. Удаление файлов галереи ---------- */
            if ($galleryToDelete) {
                foreach ($galleryToDelete as $path) {
                    $this->removeGalleryImage($product, $path);
                }
            }

            /* ---------- 6. Добавление новых фото ---------- */
            if ($galleryNew) {
                $this->appendGallery($product, $galleryNew);
            }

            /* ---------- 7. Атрибуты ---------- */
            if ($attrs) {
                $this->attributes->sync($product, $attrs);
            }

            return $product;
        });
    }

    /* ============================================================
     |  УДАЛЕНИЕ ТОВАРА
     ============================================================ */
    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {

            // Удаляем главное фото (защита в ImageService)
            $this->images->delete($product->image);

            // Удаляем галерею (защита в ImageService)
            foreach ((array)$product->gallery as $path) {
                $this->images->delete($path);
            }

            // Удаляем товар
            $this->repo->delete($product);
            
            Log::info("✅ Товар удален: ID {$product->id} - {$product->title}");
        });
    }

    /* ============================================================
     |  ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     ============================================================ */

    protected function prepareData(array $data, bool $updating = false): array
    {
        $allowed = [
            'title', 'slug', 'sku', 'price', 'stock', 'description',
            'category_id', 'city_id', 'country_id', 'address',
            'latitude', 'longitude', 'status', 'active', 'user_id',
            'currency_base', 'price_prb', 'price_mdl', 'price_uah'
        ];

        $payload = array_filter(
            array_intersect_key($data, array_flip($allowed)),
            fn($v) => !$updating || ($v !== null && $v !== '')
        );

        return $payload;
    }

    protected function generateSku(): string
    {
        do {
            $sku = 'PRD-' . random_int(10000, 99999);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    protected function handleSlugHistory(Product $product, array & $payload): void
    {
        if (!empty($payload['slug']) && $payload['slug'] !== $product->slug) {

            ProductSlug::create([
                'product_id' => $product->id,
                'slug'       => $product->slug,
            ]);
        }

        if (empty($payload['slug'])) {
            $payload['slug'] = $product->slug;
        }
    }

    protected function appendGallery(Product $product, array $files): void
    {
        $paths = $this->images->uploadGallery($files, 'products/gallery/' . date('Y/m'));

        $gallery = array_unique(array_merge(
            (array)$product->gallery,
            $paths
        ));

        $product->update(['gallery' => array_values($gallery)]);
    }

    protected function removeGalleryImage(Product $product, string $path): void
    {
        // Удаляем через ImageService (защита внутри)
        $this->images->delete($path);

        $gallery = array_filter((array)$product->gallery, fn($p) => $p !== $path);

        $product->update(['gallery' => array_values($gallery)]);
    }

    public function deleteFromGallery(Product $product, string $path): void
    {
        $this->removeGalleryImage($product, $path);
    }
}