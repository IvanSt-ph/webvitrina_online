<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSlug;
use App\Rules\ImageUploadConstraints;
use App\Repositories\ProductCrudRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        return app(BackupWriteBarrier::class)->transaction(function () use ($data, $image, $gallery, $attrs) {
            $files = new ProductImageOperation($this->images, DB::connection());

            /* ---------- 1. Подготовка данных ---------- */
            $payload = $this->prepareData($data);

            /* ---------- 2. Генерация SKU ---------- */
            $payload['sku'] = $payload['sku'] ?? $this->generateSku();

            /* ---------- 3. Загрузка главного фото ---------- */
            if ($image) {
                $payload['image'] = $files->upload($image, 'products/' . date('Y/m'));
            }
            // Если нет изображения - оставляем null (НЕ сохраняем путь к no-image.png)

            /* ---------- 4. Создание товара ---------- */
            $product = $this->repo->create($payload);

            /* ---------- 5. Галерея ---------- */
            if ($gallery) {
                $this->appendGallery($product, $gallery, $files);
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
        return app(BackupWriteBarrier::class)->transaction(function () use ($product, $data, $image, $galleryNew, $galleryToDelete, $attrs) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $files = new ProductImageOperation($this->images, DB::connection());

            /* ---------- 1. Подготовка данных ---------- */
            $payload = $this->prepareData($data, updating: true);

            /* ---------- 2. Сохранение старого slug ---------- */
            $this->handleSlugHistory($product, $payload);

            /* ---------- 3. Обновление главного фото ---------- */
            if ($image) {
                $newImagePath = $files->upload($image, 'products/' . date('Y/m'));

                // Старое фото удаляется только после окончательного commit.
                $files->deleteAfterCommit($product->image);
                $payload['image'] = $newImagePath;
            }

            /* ---------- 4. Обновление товара ---------- */
            $product = $this->repo->update($product, $payload);

            /* ---------- 5. Удаление файлов галереи ---------- */
            if ($galleryToDelete) {
                foreach ($galleryToDelete as $path) {
                    $this->removeGalleryImage($product, $path, $files);
                }
            }

            /* ---------- 6. Добавление новых фото ---------- */
            if ($galleryNew) {
                $this->appendGallery($product, $galleryNew, $files);
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
        app(BackupWriteBarrier::class)->transaction(function () use ($product) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $files = new ProductImageOperation($this->images, DB::connection());

            // Планируем очистку файлов после окончательного commit.
            $files->deleteAfterCommit($product->image);

            // Галерея использует ту же отложенную очистку.
            foreach ((array)$product->gallery as $path) {
                $files->deleteAfterCommit($path);
            }

            // Удаляем товар
            $this->repo->delete($product);
            
            Log::info("✅ Товар удален: ID {$product->id} - {$product->title}");
        });
    }

    public function purge(Product $product, \DateTimeInterface $deletedBefore): bool
    {
        return app(BackupWriteBarrier::class)->transaction(function () use ($product, $deletedBefore) {
            $product = Product::onlyTrashed()->whereKey($product->id)
                ->where('deleted_at', '<', $deletedBefore)->lockForUpdate()->first();
            if (! $product) {
                return false;
            }

            $files = new ProductImageOperation($this->images, DB::connection());
            foreach (array_filter([$product->image, ...(array) $product->gallery]) as $path) {
                $files->deleteAfterCommit($path);
            }
            $product->forceDelete();

            return true;
        });
    }

    /* ============================================================
     |  ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     ============================================================ */

    protected function prepareData(array $data, bool $updating = false): array
    {
        $allowed = [
            'title', 'slug', 'sku', 'price', 'old_price', 'stock', 'description',
            'category_id', 'city_id', 'country_id', 'address',
            'latitude', 'longitude', 'status', 'active', 'user_id',
            'currency_base', 'price_prb', 'old_price_prb', 'price_mdl', 'old_price_mdl', 'price_uah', 'old_price_uah'
        ];

        $payload = array_intersect_key($data, array_flip($allowed));
        $clearable = ['old_price', 'old_price_prb', 'old_price_mdl', 'old_price_uah'];

        foreach ($clearable as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] === '') {
                $payload[$field] = null;
            }
        }

        if ($updating) {
            $payload = array_filter(
                $payload,
                fn($v, $key) => in_array($key, $clearable, true) || ($v !== null && $v !== ''),
                ARRAY_FILTER_USE_BOTH
            );
        }

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

    protected function appendGallery(Product $product, array $uploads, ProductImageOperation $files): void
    {
        if (count((array) $product->gallery) + count($uploads) > ImageUploadConstraints::MAX_GALLERY_IMAGES) {
            throw ValidationException::withMessages([
                'gallery' => 'В галерее товара может быть не более ' . ImageUploadConstraints::MAX_GALLERY_IMAGES . ' изображений.',
            ]);
        }

        $paths = [];
        foreach ($uploads as $upload) {
            if ($upload instanceof UploadedFile) {
                $paths[] = $files->upload($upload, 'products/gallery/' . date('Y/m'));
            }
        }

        $gallery = array_unique(array_merge(
            (array)$product->gallery,
            $paths
        ));

        $product->update(['gallery' => array_values($gallery)]);
    }

    protected function removeGalleryImage(Product $product, string $path, ProductImageOperation $files): void
    {
        $gallery = (array) $product->gallery;
        $cleanPath = $this->normalizeGalleryPath($path);

        if (!in_array($cleanPath, $gallery, true)) {
            abort(403, 'Изображение не принадлежит галерее этого товара.');
        }

        // Файл сохраняется до окончательного commit удаления ссылки.
        $files->deleteAfterCommit($cleanPath);

        $gallery = array_filter($gallery, fn($p) => $p !== $cleanPath);

        $product->update(['gallery' => array_values($gallery)]);
    }

    protected function normalizeGalleryPath(string $path): string
    {
        return ltrim(str_replace(['storage/', '/storage/'], '', $path), '/');
    }

    public function deleteFromGallery(Product $product, string $path): void
    {
        app(BackupWriteBarrier::class)->transaction(function () use ($product, $path) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $files = new ProductImageOperation($this->images, DB::connection());
            $this->removeGalleryImage($product, $path, $files);
        });
    }
}
